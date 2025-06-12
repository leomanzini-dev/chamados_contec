<?php
// relatorios.php
$titulo_pagina = "Relatórios";
$css_pagina = "relatorios.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas usuários 'ti' podem acessar esta página
if ($tipo_usuario != 'ti') {
    header("Location: painel.php");
    exit();
}

// --- BUSCA DE DADOS AGREGADOS PARA OS GRÁFICOS ---
$sql_status = "SELECT s.nome, COUNT(t.id) AS total 
               FROM tickets AS t 
               JOIN status_tickets AS s ON t.id_status = s.id 
               GROUP BY s.nome ORDER BY total DESC";
$resultado_status = $conexao->query($sql_status);
$dados_status = $resultado_status->fetch_all(MYSQLI_ASSOC);

$sql_categoria = "SELECT c.nome, COUNT(t.id) AS total 
                  FROM tickets AS t 
                  JOIN categorias AS c ON t.id_categoria = c.id 
                  GROUP BY c.nome ORDER BY total DESC LIMIT 10";
$resultado_categoria = $conexao->query($sql_categoria);
$dados_categoria = $resultado_categoria->fetch_all(MYSQLI_ASSOC);


// Preparar dados para o JavaScript em formato JSON
$labels_status = json_encode(array_column($dados_status, 'nome'));
$valores_status = json_encode(array_column($dados_status, 'total'));

$labels_categoria = json_encode(array_column($dados_categoria, 'nome'));
$valores_categoria = json_encode(array_column($dados_categoria, 'total'));

?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        
        <!-- ===== BOTÃO DE EXPORTAÇÃO ADICIONADO AQUI ===== -->
        <a href="exportar_relatorio.php?relatorio=chamados_geral" class="header-btn principal">
            <i class="fa-solid fa-file-excel"></i> Exportar para Excel
        </a>
    </div>

    <div class="content-body">
        <div class="grid-relatorios">
            <div class="card-relatorio">
                <h3>Chamados por Status</h3>
                <canvas id="graficoStatus"></canvas>
            </div>
            <div class="card-relatorio">
                <h3>Top 10 Categorias com Mais Chamados</h3>
                <canvas id="graficoCategoria"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$conexao->close();
?>
    </div> <!-- Fechamento da .dashboard-container -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Passando os dados do PHP para o JavaScript
        const labelsStatus = <?php echo $labels_status; ?>;
        const valoresStatus = <?php echo $valores_status; ?>;

        const labelsCategoria = <?php echo $labels_categoria; ?>;
        const valoresCategoria = <?php echo $valores_categoria; ?>;

        // Configuração do Gráfico de Status (Gráfico de Pizza)
        const ctxStatus = document.getElementById('graficoStatus');
        new Chart(ctxStatus, {
            type: 'pie', // Tipo do gráfico
            data: {
                labels: labelsStatus,
                datasets: [{
                    label: 'Nº de Chamados',
                    data: valoresStatus,
                    backgroundColor: [ // Cores para cada fatia da pizza
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(107, 114, 128, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(99, 102, 241, 0.8)'
                    ],
                    borderColor: 'rgba(255, 255, 255, 0.5)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Configuração do Gráfico de Categorias (Gráfico de Barras)
        const ctxCategoria = document.getElementById('graficoCategoria');
        new Chart(ctxCategoria, {
            type: 'bar', // Tipo do gráfico
            data: {
                labels: labelsCategoria,
                datasets: [{
                    label: 'Nº de Chamados',
                    data: valoresCategoria,
                    backgroundColor: 'rgba(60, 110, 113, 0.7)', // Usando a cor principal com transparência
                    borderColor: 'rgba(60, 110, 113, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Deixa as barras na horizontal para melhor leitura
                responsive: true,
                plugins: {
                    legend: {
                        display: false // Esconde a legenda pois só temos uma série de dados
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
