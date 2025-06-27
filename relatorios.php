<?php
// relatorios.php - VERSÃO COM FILTROS INTERATIVOS

$titulo_pagina = "Relatórios";
$css_pagina = "relatorios.css"; // Você pode criar um CSS para estilizar os novos filtros
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas usuários 'ti' podem acessar esta página
if ($tipo_usuario != 'ti') {
    header("Location: painel.php");
    exit();
}

// ===== NOVO: BUSCAR DADOS PARA OS MENUS DE FILTRO =====
$lista_status = $conexao->query("SELECT id, nome FROM status_tickets ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$lista_categorias = $conexao->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <a href="exportar_relatorio.php?relatorio=chamados_geral" class="btn btn-secondary">
            <i class="fa-solid fa-file-excel"></i> Exportar Relatório Geral
        </a>
    </div>

    <div class="content-body">

        <!-- ===== NOVO: FORMULÁRIO DE FILTROS ===== -->
        <div class="card-section filtros-relatorio" style="margin-bottom: 25px;">
            <h3><i class="fa-solid fa-filter"></i> Filtrar Relatórios</h3>
            <div class="card-content">
                <form id="form-filtros-relatorio" class="filtros-form">
                    <div class="filtro-item">
                        <label for="filtro_status">Status do Chamado</label>
                        <select name="status" id="filtro_status">
                            <option value="">Todos os Status</option>
                            <?php foreach($lista_status as $status): ?>
                                <option value="<?php echo $status['id']; ?>"><?php echo htmlspecialchars($status['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtro-item">
                        <label for="filtro_categoria">Categoria do Chamado</label>
                        <select name="categoria" id="filtro_categoria">
                            <option value="">Todas as Categorias</option>
                            <?php foreach($lista_categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtro-botoes">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Aplicar Filtros</button>
                        <button type="button" id="limpar-filtros" class="btn btn-secondary">Limpar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid-relatorios">
            <div class="card-relatorio">
                <h3>Chamados por Status</h3>
                <div class="chart-container">
                    <canvas id="graficoStatus"></canvas>
                </div>
            </div>
            <div class="card-relatorio">
                <h3>Top 10 Categorias com Mais Chamados</h3>
                <div class="chart-container">
                    <canvas id="graficoCategoria"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if ($conexao) {
    $conexao->close();
}
?>
</div> <!-- Fechamento da .dashboard-container -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- ===== NOVO: SCRIPT PARA CONTROLAR OS GRÁFICOS INTERATIVOS ===== -->
<script src="js/relatorios_interativos.js?v=<?php echo filemtime('js/relatorios_interativos.js'); ?>"></script>

</body>
</html>