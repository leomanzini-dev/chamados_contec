<?php
// includes/sidebar.php
// As variáveis $pagina_atual e $tipo_usuario vêm do header.php
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="painel.php">
            <img src="img/logo_contec.png" alt="Logo Contec" class="logo">
        </a>
        <span class="site-title">Chamados Contec</span>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>">
                <a href="painel.php" title="Painel Principal"><i class="fa-solid fa-house"></i><span class="link-text">Painel Principal</span></a>
            </li>

            <?php if ($tipo_usuario == 'colaborador'): ?>
                <li class="<?php echo ($pagina_atual == 'abrir_chamado.php') ? 'active' : ''; ?>">
                    <a href="abrir_chamado.php" title="Abrir Chamado"><i class="fa-solid fa-plus"></i><span class="link-text">Abrir Chamado</span></a>
                </li>
                <li class="<?php echo ($pagina_atual == 'meus_chamados.php') ? 'active' : ''; ?>">
                    <a href="meus_chamados.php" title="Meus Chamados"><i class="fa-solid fa-ticket"></i><span class="link-text">Meus Chamados</span></a>
                </li>
                <!-- Link da Base de Conhecimento para Colaboradores -->
                <li class="<?php echo ($pagina_atual == 'kb.php') ? 'active' : ''; ?>">
                    <a href="kb.php" title="Base de Conhecimento"><i class="fa-solid fa-book-open"></i><span class="link-text">Base de Conhecimento</span></a>
                </li>
            <?php endif; ?>

            <?php if ($tipo_usuario == 'ti'): ?>
                <li class="<?php echo ($pagina_atual == 'gerenciar_chamados.php') ? 'active' : ''; ?>">
                    <a href="gerenciar_chamados.php" title="Gerenciar Chamados"><i class="fa-solid fa-list-check"></i><span class="link-text">Gerenciar Chamados</span></a>
                </li>
                <li class="<?php echo ($pagina_atual == 'admin_kb.php') ? 'active' : ''; ?>">
                    <a href="admin_kb.php" title="Base de Conhecimento"><i class="fa-solid fa-book"></i><span class="link-text">Base de Conhecimento</span></a>
                </li>
                <li class="<?php echo ($pagina_atual == 'admin_usuarios.php') ? 'active' : ''; ?>">
                    <a href="admin_usuarios.php" title="Administrar Usuários"><i class="fa-solid fa-users-gear"></i><span class="link-text">Administrar Usuários</span></a>
                </li>
                <li class="<?php echo ($pagina_atual == 'relatorios.php') ? 'active' : ''; ?>">
                    <a href="relatorios.php" title="Relatórios"><i class="fa-solid fa-chart-pie"></i><span class="link-text">Relatórios</span></a>
                </li>
            <?php endif; ?>

        </ul>
    </nav>

    <!-- Botão de Toggle no final da sidebar -->
    <div class="sidebar-toggle-wrapper">
        <button id="sidebar-toggle" title="Encolher menu">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>
</aside>
