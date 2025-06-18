// js/websocket_client.js (VERSÃO FINAL E CORRIGIDA)
document.addEventListener('DOMContentLoaded', function() {
    
    const userId = document.body.dataset.usuarioId;
    const paginaAtual = document.body.dataset.paginaAtual;
    const idChamado = document.body.dataset.idChamado;

    if (!userId) return;

    function connect() {
        const conn = new WebSocket('ws://127.0.0.1:8080');

        conn.onopen = function(e) {
            console.log("WebSocket Conectado!");
            conn.send(JSON.stringify({ action: 'register', userId: userId }));

            if (paginaAtual === 'detalhes_chamado' && idChamado > 0) {
                conn.send(JSON.stringify({ action: 'subscribe', topic: `chamado-${idChamado}` }));
            }
            if (paginaAtual === 'painel' || paginaAtual === 'gerenciar_chamados') {
                conn.send(JSON.stringify({ action: 'subscribe', topic: 'dashboard-ti' }));
            }
        };

        conn.onmessage = function(e) {
            const data = JSON.parse(e.data);
            console.log('Mensagem recebida:', data);
            
            // A MUDANÇA ESTÁ AQUI:
            // Em vez de um 'switch' complexo, apenas disparamos um evento com o nome do tipo da mensagem.
            if (data.type) {
                document.dispatchEvent(new CustomEvent('ws:' + data.type, { detail: data.payload }));
            }
        };

        conn.onclose = function(e) {
            console.log("Conexão WebSocket fechada. Tentando reconectar em 5 segundos.");
            setTimeout(connect, 5000); 
        };

        conn.onerror = function(e) {
            console.error("Erro na conexão WebSocket.", e);
            conn.close();
        };
    }

    connect();
});