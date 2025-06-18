// js/abrir_chamado.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- Lógica para as Sugestões da Base de Conhecimento ---
    const campoAssunto = document.getElementById('motivo_chamado');
    const containerSugestoes = document.getElementById('sugestoes-kb');
    let timeoutBusca = null;

    if (campoAssunto && containerSugestoes) {
        campoAssunto.addEventListener('keyup', function() {
            clearTimeout(timeoutBusca);
            const termo = this.value;

            if (termo.length < 3) {
                containerSugestoes.style.display = 'none';
                containerSugestoes.innerHTML = '';
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

    // --- Lógica de Submissão do Formulário com Animação ---
    const form = document.getElementById('form-abrir-chamado');
    const loadingOverlay = document.getElementById('loading-overlay');
    const successModal = document.getElementById('success-modal');
    const successMessage = document.getElementById('success-message');

    if (form && loadingOverlay && successModal && successMessage) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            loadingOverlay.style.display = 'flex';
            const formData = new FormData(form);

            fetch(form.action, { // Usa o 'action' do formulário para ser mais robusto
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erro no servidor! Status: ${response.status}`);
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
                    // Usamos um modal ou um alerta mais elegante em vez do alert()
                    alert('Erro ao abrir chamado: ' + data.message);
                }
            })
            .catch(error => {
                loadingOverlay.style.display = 'none';
                alert('Ocorreu um erro de comunicação. Tente novamente.');
                console.error('Erro no fetch:', error);
            });
        });
    }
});
