// js/notificacoes.js (VERSÃO FINAL E CORRETA)
function showToast(mensagem, tipo = 'sucesso') {
    const container = document.getElementById('toast-container');
    if (!container) {
        console.error('O container de toast (#toast-container) não foi encontrado na página.');
        return;
    }

    const toastElement = document.createElement('div');
    toastElement.className = `toast ${tipo}`;

    const icones = {
        sucesso: 'fa-check-circle',
        erro: 'fa-times-circle',
        aviso: 'fa-exclamation-triangle'
    };
    const iconeClasse = icones[tipo] || 'fa-info-circle';

    toastElement.innerHTML = `
        <div class="icon-toast">
            <i class="fa-solid ${iconeClasse}"></i>
        </div>
        <div class="mensagem-toast">${mensagem}</div>
        <div class="progress-bar"></div>
    `;

    container.prepend(toastElement);

    setTimeout(() => {
        toastElement.style.animation = 'toastFadeOut 0.5s ease-out forwards';
        toastElement.addEventListener('animationend', () => {
            toastElement.remove();
        });
    }, 5000);
}