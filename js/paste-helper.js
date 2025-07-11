function inicializarPasteUpload(triggerId, previewContainerId, formId) {
    const triggerElement = document.getElementById(triggerId);
    const previewContainer = document.getElementById(previewContainerId);
    const form = document.getElementById(formId);
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (!triggerElement || !previewContainer || !form || !submitBtn) {
        console.error("Elementos essenciais para o paste-helper não foram encontrados.");
        return;
    }

    let arquivosColados = [];

    function renderizarPreviews() {
        previewContainer.innerHTML = "";
        previewContainer.style.display = arquivosColados.length > 0 ? "flex" : "none";
        arquivosColados.forEach((arquivo, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const item = document.createElement("div");
                item.className = "paste-preview-item";
                item.innerHTML = `<img src="${e.target.result}" alt="Preview da imagem colada"><button type="button" class="paste-preview-remove" title="Remover">&times;</button>`;
                item.querySelector(".paste-preview-remove").onclick = () => {
                    arquivosColados.splice(index, 1);
                    renderizarPreviews();
                };
                previewContainer.appendChild(item);
            };
            reader.readAsDataURL(arquivo);
        });
    }

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

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        // 1. Criamos um FormData VAZIO
        const formData = new FormData();

        // 2. Adicionamos os campos de texto e outros inputs manualmente
        for (const element of form.elements) {
            if (element.name && element.type !== 'file' && element.type !== 'submit') {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    if (element.checked) {
                        formData.append(element.name, element.value);
                    }
                } else {
                    formData.append(element.name, element.value);
                }
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
        
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

        fetch(form.action, {
            method: form.method,
            body: formData,
        })
        .then(response => {
            if (response.ok && response.redirected) {
                window.location.href = response.url;
                return;
            }
            // Se não redirecionou, apenas recarrega para ver a mensagem de sucesso/erro da sessão
            window.location.reload();
        })
        .catch(error => {
            console.error("Erro de comunicação:", error);
            alert("Ocorreu um erro de comunicação.");
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        });
    });
}