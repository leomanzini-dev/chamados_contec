// js/detalhes_chamado.js

// Este arquivo conterá toda a lógica interativa da página de detalhes do chamado.
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializa a funcionalidade de colar imagens (Ctrl+V) no campo de comentário.
    // A função inicializarPasteUpload deve estar em outro arquivo global ou aqui.
    // Se 'paste-helper.js' já está sendo incluído, esta chamada vai funcionar.
    if (typeof inicializarPasteUpload === 'function') {
        inicializarPasteUpload('comentario-texto', 'preview-container-comentario', 'form-comentario');
    }

    // Adicione aqui qualquer outra lógica JavaScript que seja exclusiva
    // da página de detalhes do chamado no futuro.
});