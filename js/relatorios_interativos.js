// js/relatorios_interativos.js

document.addEventListener('DOMContentLoaded', function () {
    // Referências aos elementos do formulário e dos gráficos
    const form = document.getElementById('form-filtros-relatorio');
    const btnLimpar = document.getElementById('limpar-filtros');
    const ctxStatus = document.getElementById('graficoStatus').getContext('2d');
    const ctxCategoria = document.getElementById('graficoCategoria').getContext('2d');
    let graficoStatus, graficoCategoria; // Variáveis para guardar as instâncias dos gráficos

    // Cores para os gráficos, para manter a consistência
    const coresStatus = ['#3b82f6', '#f59e0b', '#22c55e', '#6b7280', '#ef4444', '#6366f1'];
    const corCategoria = 'rgba(60, 110, 113, 0.7)';

    // Função para renderizar/atualizar o gráfico de pizza (Status)
    function renderizarGraficoStatus(labels, valores) {
        if (graficoStatus) {
            graficoStatus.destroy(); // Destrói o gráfico antigo antes de criar um novo
        }
        graficoStatus = new Chart(ctxStatus, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nº de Chamados',
                    data: valores,
                    backgroundColor: coresStatus,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    // Função para renderizar/atualizar o gráfico de barras (Categoria)
    function renderizarGraficoCategoria(labels, valores) {
        if (graficoCategoria) {
            graficoCategoria.destroy();
        }
        graficoCategoria = new Chart(ctxCategoria, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nº de Chamados',
                    data: valores,
                    backgroundColor: corCategoria,
                }]
            },
            options: {
                indexAxis: 'y', // Barras horizontais
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { precision: 0 } } }
            }
        });
    }

    // Função principal para buscar dados e atualizar os gráficos
    async function atualizarGraficos() {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        // Adiciona um efeito de loading
        document.querySelectorAll('.chart-container').forEach(c => c.style.opacity = 0.5);

        try {
            const response = await fetch(`obter_dados_relatorio.php?${params.toString()}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            // Atualiza os dois gráficos com os novos dados
            renderizarGraficoStatus(data.grafico_status.labels, data.grafico_status.valores);
            renderizarGraficoCategoria(data.grafico_categoria.labels, data.grafico_categoria.valores);

        } catch (error) {
            console.error('Erro ao buscar dados do relatório:', error);
            alert('Não foi possível carregar os dados do relatório.');
        } finally {
            // Remove o efeito de loading
            document.querySelectorAll('.chart-container').forEach(c => c.style.opacity = 1);
        }
    }

    // Evento para o botão "Aplicar Filtros"
    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Impede o recarregamento da página
        atualizarGraficos();
    });

    // Evento para o botão "Limpar"
    btnLimpar.addEventListener('click', function () {
        form.reset(); // Limpa os campos do formulário
        atualizarGraficos(); // Busca os dados novamente sem filtros
    });

    // Carrega os dados iniciais (sem filtros) quando a página abre
    atualizarGraficos();
});