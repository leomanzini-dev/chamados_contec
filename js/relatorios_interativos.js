document.addEventListener('DOMContentLoaded', function() {
    const formFiltros = document.getElementById('form-filtros-relatorio');
    const btnLimpar = document.getElementById('limpar-filtros');
    
    // Referências para os gráficos (para poderem ser destruídos e recriados)
    let graficoStatusInstance = null;
    let graficoCategoriaInstance = null;
    
    // Função para buscar dados e atualizar os gráficos
    function atualizarGraficos() {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);

        // Mostra um indicador de carregamento (opcional, mas bom para UX)
        document.getElementById('graficoStatus').style.opacity = '0.5';
        document.getElementById('graficoCategoria').style.opacity = '0.5';

        fetch(`obter_dados_relatorio.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                // Remove o indicador de carregamento
                document.getElementById('graficoStatus').style.opacity = '1';
                document.getElementById('graficoCategoria').style.opacity = '1';
                
                // Atualiza o gráfico de Status
                const ctxStatus = document.getElementById('graficoStatus').getContext('2d');
                if (graficoStatusInstance) {
                    graficoStatusInstance.destroy();
                }
                graficoStatusInstance = new Chart(ctxStatus, {
                    type: 'pie',
                    data: {
                        labels: data.graficoStatus.labels,
                        datasets: [{
                            label: 'Chamados',
                            data: data.graficoStatus.dados,
                            backgroundColor: data.graficoStatus.cores, // <<< AQUI ESTÁ A MÁGICA!
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });

                // Atualiza o gráfico de Categoria
                const ctxCategoria = document.getElementById('graficoCategoria').getContext('2d');
                if (graficoCategoriaInstance) {
                    graficoCategoriaInstance.destroy();
                }
                graficoCategoriaInstance = new Chart(ctxCategoria, {
                    type: 'bar',
                    data: {
                        labels: data.graficoCategoria.labels,
                        datasets: [{
                            label: 'Nº de Chamados',
                            data: data.graficoCategoria.dados,
                            backgroundColor: '#3C6E71', // Usando sua cor principal
                            borderColor: '#284B63',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y', // Deixa as barras na horizontal
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false // Não precisa de legenda para um único dataset
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Erro ao buscar dados do relatório:', error);
                document.getElementById('graficoStatus').style.opacity = '1';
                document.getElementById('graficoCategoria').style.opacity = '1';
            });
    }

    // Evento ao submeter o formulário de filtros
    formFiltros.addEventListener('submit', function(e) {
        e.preventDefault(); // Impede o recarregamento da página
        atualizarGraficos();
    });

    // Evento para o botão de limpar
    btnLimpar.addEventListener('click', function() {
        formFiltros.reset();
        atualizarGraficos();
    });

    // Carrega os gráficos pela primeira vez
    atualizarGraficos();
});