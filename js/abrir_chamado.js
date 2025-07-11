// js/abrir_chamado.js (VERSÃO FINAL COM ENVIO ROBUSTO)

document.addEventListener('DOMContentLoaded', function() {
    
    // --- Lógica para as Sugestões da Base de Conhecimento (Mantida) ---
    const campoAssunto = document.getElementById('motivo_chamado');
    const containerSugestoes = document.getElementById('sugestoes-kb');
    let timeoutBusca = null;

    if (campoAssunto && containerSugestoes) {
        campoAssunto.addEventListener('keyup', function() {
            clearTimeout(timeoutBusca);
            const termo = this.value;

            if (termo.length < 3) {
                containerSugestoes.style.display = 'none';
                return;
            }

            timeoutBusca = setTimeout(() => {
                fetch(`buscar_artigos.php?termo=${encodeURIComponent(termo)}`)
                    .then(response => response.json())
                    .then(artigos => {
                        containerSugestoes.innerHTML = '';
                        if (artigos.length > 0) {
                            const header = document.createElement('div');
                            header.className = 'sugestoes-header';
                            header.innerHTML = '<span><i class="fa-solid fa-lightbulb"></i> Artigos Sugeridos</span>';
                            containerSugestoes.appendChild(header);

                            artigos.forEach(artigo => {
                                const linkArtigo = document.createElement('a');
                                linkArtigo.href = `ver_artigo.php?id=${artigo.id}`;
                                linkArtigo.target = '_blank';
                                linkArtigo.className = 'sugestao-item';
                                linkArtigo.innerHTML = `<i class="fa-solid fa-book-open"></i> ${artigo.titulo}`;
                                containerSugestoes.appendChild(linkArtigo);
                            });
                            containerSugestoes.style.display = 'block';
                        } else {
                            containerSugestoes.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Erro na busca de artigos:', error));
            }, 500);
        });

        document.addEventListener('click', function(e) {
            if (!containerSugestoes.contains(e.target)) {
                containerSugestoes.style.display = 'none';
            }
        });
    }

    // --- LÓGICA DE PASTE E SUBMISSÃO UNIFICADA ---

    const form = document.getElementById('form-abrir-chamado');
    const loadingOverlay = document.getElementById('loading-overlay');
    const successModal = document.getElementById('success-modal');
    const successMessage = document.getElementById('success-message');

    const triggerElement = document.getElementById('descricao_detalhada');
    const previewContainer = document.getElementById('preview-container-abrir');
    let arquivosColados = [];

    function renderizarPreviews() {
        previewContainer.innerHTML = "";
        previewContainer.style.display = arquivosColados.length > 0 ? "flex" : "none";
        arquivosColados.forEach((arquivo, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const item = document.createElement("div");
                item.className = "paste-preview-item";
                item.innerHTML = `<img src="${e.target.result}" alt="Preview"><button type="button" class="paste-preview-remove" title="Remover">&times;</button>`;
                item.querySelector(".paste-preview-remove").onclick = () => {
                    arquivosColados.splice(index, 1);
                    renderizarPreviews();
                };
                previewContainer.appendChild(item);
            };
            reader.readAsDataURL(arquivo);
        });
    }
    
    if (triggerElement) {
        triggerElement.addEventListener("paste", function(event) {
            const items = (event.clipboardData || window.clipboardData).items;
            let foiImagem = false;
            for (const item of items) {
                if (item.kind === "file" && item.type.startsWith("image/")) {
                    foiImagem = true;
                    const blob = item.getAsFile();
                    const arquivoImagem = new File([blob], `print_${Date.now()}.png`, { type: "image/png" });
                    arquivosColados.push(arquivoImagem);
                }
            }
            if (foiImagem) {
                event.preventDefault();
                setTimeout(renderizarPreviews, 100);
            }
        });
    }

    if (form && loadingOverlay && successModal && successMessage) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            loadingOverlay.style.display = 'flex';
            
            // ==========================================================
            // INÍCIO DA MUDANÇA PARA O ENVIO ROBUSTO
            // ==========================================================

            // 1. Criamos um FormData VAZIO
            const formData = new FormData();

            // 2. Adicionamos os campos de texto e outros inputs manualmente
            for (const element of form.elements) {
                if (element.name && element.type !== 'file' && element.type !== 'submit') {
                    formData.append(element.name, element.value);
                }
            }

            // 3. Adicionamos os arquivos selecionados pelo BOTÃO
            const anexoInput = form.querySelector('input[type="file"]');
            if (anexoInput && anexoInput.files.length > 0) {
                for (const file of anexoInput.files) {
                    formData.append('anexos[]', file, file.name);
                }
            }
            
            // 4. Adicionamos os arquivos que foram COLADOS (Ctrl+V)
            arquivosColados.forEach(arquivo => {
                formData.append("anexos[]", arquivo, arquivo.name);
            });

            // ==========================================================
            // FIM DA MUDANÇA
            // ==========================================================

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Tenta ler o corpo do erro como JSON para uma mensagem mais clara
                    return response.json().then(errData => {
                        throw new Error(errData.message || `Erro no servidor! Status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                loadingOverlay.style.display = 'none';
                if (data.success) {
                    successMessage.textContent = 'Seu chamado nº ' + data.ticket_id + ' foi registrado.';
                    successModal.style.display = 'flex';
                    setTimeout(() => {
                        window.location.href = 'painel.php';
                    }, 3000);
                } else {
                    alert('Erro ao abrir chamado: ' + data.message);
                }
            })
            .catch(error => {
                loadingOverlay.style.display = 'none';
                alert('Ocorreu um erro: ' + error.message);
                console.error('Erro no fetch:', error);
            });
        });
    }
});