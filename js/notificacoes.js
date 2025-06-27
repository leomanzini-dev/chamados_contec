// js/notificacoes.js

function showToast(mensagem, tipo = 'sucesso') {
    const container = document.getElementById('toast-container');
    if (!container) {
        console.error('O container de toast (#toast-container) não foi encontrado na página.');
        return;
    }

    // Cria o elemento principal do toast
    const toastElement = document.createElement('div');
    toastElement.className = `toast ${tipo}`; // Adiciona a classe base e a classe do tipo (sucesso, erro, aviso)

    // Define o ícone correto com base no tipo de notificação
    let iconeClasse = 'fa-info-circle'; // Ícone padrão
    if (tipo === 'sucesso') {
        iconeClasse = 'fa-check-circle';
    } else if (tipo === 'erro') {
        iconeClasse = 'fa-times-circle';
    } else if (tipo === 'aviso') {
        iconeClasse = 'fa-exclamation-triangle';
    }

    // Monta o HTML interno do toast com o ícone e a mensagem
    toastElement.innerHTML = `
        <i class="fa-solid ${iconeClasse}"></i>
        <span class="mensagem-toast">${mensagem}</span>
    `;

    // Adiciona o novo toast no container (usamos prepend para o mais novo aparecer no topo)
    container.prepend(toastElement);

    // Define um tempo para remover o toast automaticamente após 5 segundos
    setTimeout(() => {
        // Adiciona uma animação de saída para suavidade (opcional)
        toastElement.style.animation = 'toastFadeOut 0.5s ease-out forwards';
        
        // Remove o elemento do HTML após a animação de saída terminar
        toastElement.addEventListener('animationend', () => {
            toastElement.remove();
        });
    }, 5000); // Tempo que a notificação fica na tela
}