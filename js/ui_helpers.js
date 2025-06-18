// js/ui_helpers.js

function escapeHTML(str) {
    if (typeof str !== 'string') return '';
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}

function flashElement(element) {
    if (!element) return;
    element.style.transition = 'background-color 0.2s';
    element.style.backgroundColor = '#fffacd'; // Amarelo claro
    setTimeout(() => { element.style.backgroundColor = ''; }, 1500);
}

function atualizarNotificacoes(contagem, listaNotificacoes) {
    const contador = document.getElementById('contador-notificacoes');
    const corpoDropdown = document.getElementById('notificacoes-body');
    if (!contador || !corpoDropdown) return;
    
    const contagemAtual = parseInt(contador.innerText) || 0;
    if (contagem > 0) {
        contador.innerText = contagem;
        contador.style.display = 'inline-block';
        if(contagem > contagemAtual) flashElement(contador);
    } else {
        contador.style.display = 'none';
    }

    let novoHtml = '';
    if (listaNotificacoes.length === 0) {
        novoHtml = `<div class="notificacao-item"><div class="mensagem">Nenhuma notificação nova.</div></div>`;
    } else {
        listaNotificacoes.forEach(notif => {
            const dataFormatada = new Date(notif.data_criacao).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            novoHtml += `<a href="detalhes_chamado.php?id=${notif.id_ticket}" class="notificacao-item"><div class="icon"><i class="fa-solid fa-ticket"></i></div><div><div class="mensagem">${escapeHTML(notif.mensagem)}</div><div class="data">${dataFormatada}</div></div></a>`;
        });
    }
    corpoDropdown.innerHTML = novoHtml;
}