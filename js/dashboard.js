// js/dashboard.js
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Lógica para o Menu Lateral Retrátil (Versão Melhorada) ---
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const body = document.body;

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            // Adiciona ou remove a classe do body
            body.classList.toggle('sidebar-collapsed');

            // Guarda o estado no navegador para a próxima visita
            if (body.classList.contains('sidebar-collapsed')) {
                localStorage.setItem('sidebarState', 'collapsed');
            } else {
                localStorage.setItem('sidebarState', 'expanded');
            }
        });
    }

    // Ao carregar a página, verifica se um estado foi guardado da última vez
    if (localStorage.getItem('sidebarState') === 'collapsed') {
        body.classList.add('sidebar-collapsed');
    }

    // --- Lógica para o Filtro Retrátil (Mantida do seu ficheiro) ---
    const filtrosHeader = document.querySelector('.filtros-header');
    const filtrosContainer = document.querySelector('.filtros-container');
    if (filtrosHeader) {
        filtrosHeader.addEventListener('click', () => filtrosContainer.classList.toggle('collapsed'));
    }

    // --- LÓGICA PARA O DROPDOWN DE NOTIFICAÇÕES (Mantida do seu ficheiro) ---
    const sinoWrapper = document.querySelector('.notificacao-sino');
    const dropdown = document.querySelector('.notificacoes-dropdown');
    const contador = document.querySelector('.notificacao-sino .contador');

    if (sinoWrapper && dropdown) {
        sinoWrapper.addEventListener('click', function(event) {
            event.stopPropagation(); // Impede que o clique feche o dropdown imediatamente
            dropdown.classList.toggle('show');

            // Se o dropdown for aberto e houver notificações, marca como lidas
            if (dropdown.classList.contains('show') && contador) {
                // Remove o contador visualmente na hora
                setTimeout(() => {
                    if(contador) contador.style.display = 'none';
                }, 500);
                
                // Comunica ao servidor para marcar como lidas no banco
                fetch('marcar_notificacoes_lidas.php')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Falha ao marcar notificações como lidas.');
                        }
                    })
                    .catch(err => console.error('Erro na requisição para marcar notificações:', err));
            }
        });
    }

    // Fecha o dropdown se o utilizador clicar fora dele
    window.addEventListener('click', function(event) {
        if (dropdown && dropdown.classList.contains('show') && !sinoWrapper.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
});
