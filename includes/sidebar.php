<?php
// includes/sidebar.php (VERSÃO FINAL COM LINKS ABSOLUTOS E SCRIPT COMPLETO)

// As variáveis necessárias vêm do header.php
$partes_nome = explode(' ', trim($nome_usuario));
$primeiro_nome = $partes_nome[0];
$ultimo_nome = count($partes_nome) > 1 ? end($partes_nome) : '';
$nome_exibicao = $primeiro_nome . ($ultimo_nome ? ' ' . $ultimo_nome : '');
$iniciais = mb_substr($primeiro_nome, 0, 1) . ($ultimo_nome ? mb_substr($ultimo_nome, 0, 1) : '');

// Lógica para definir o nome do cargo/função para exibição
if ($tipo_usuario == 'ti') {
    $nome_tipo_usuario = 'Administrador';
} elseif (isset($departamento_usuario) && $departamento_usuario == 'Pessoal') {
    $nome_tipo_usuario = 'Dep. Pessoal';
} else {
    $nome_tipo_usuario = 'Colaborador';
}

// Define o caminho base do projeto para ser usado nos links.
// Isso assume que seu projeto está em http://localhost/chamados_contec/
$base_path = '/chamados_contec';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo $base_path; ?>/painel.php" class="user-profile-link" title="Painel Principal">
            <div class="user-avatar">
                <span><?php echo strtoupper($iniciais); ?></span>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($nome_exibicao); ?></span>
                <span class="user-role"><?php echo $nome_tipo_usuario; ?></span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($pagina_atual == 'painel.php') ? 'active' : ''; ?>">
                <a href="<?php echo $base_path; ?>/painel.php" title="Painel Principal"><i class="fa-solid fa-house-chimney"></i><span class="link-text">Painel Principal</span></a>
            </li>

            <?php if ($tipo_usuario == 'ti'): ?>
                <li class="<?php echo ($pagina_atual == 'gerenciar_chamados.php') ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>/gerenciar_chamados.php" title="Gerenciar Chamados"><i class="fa-solid fa-list-check"></i><span class="link-text">Gerenciar Chamados</span></a></li>
                
                <?php
                if (isset($departamento_usuario) && $departamento_usuario == 'Pessoal') {
                    echo '<li class="' . ($pagina_atual == 'base_conhecimento_dp.php' ? 'active' : '') . '"><a href="' . $base_path . '/faq_dp/base_conhecimento_dp.php" title="Base de Conhecimento DP"><i class="fa-solid fa-book-medical"></i><span class="link-text">Base de Conhecimento DP</span></a></li>';
                } else {
                    echo '<li class="' . ($pagina_atual == 'admin_kb.php' ? 'active' : '') . '"><a href="' . $base_path . '/admin_kb.php" title="Base de Conhecimento"><i class="fa-solid fa-book"></i><span class="link-text">Base de Conhecimento</span></a></li>';
                }
                ?>
                
                <li class="<?php echo ($pagina_atual == 'admin_usuarios.php') ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>/admin_usuarios.php" title="Administrar Usuários"><i class="fa-solid fa-users-gear"></i><span class="link-text">Administrar Usuários</span></a></li>
                <li class="<?php echo ($pagina_atual == 'relatorios.php') ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>/relatorios.php" title="Relatórios"><i class="fa-solid fa-chart-pie"></i><span class="link-text">Relatórios</span></a></li>

            <?php else: // Para 'colaborador' e outros tipos de usuário ?>
                <li class="<?php echo ($pagina_atual == 'abrir_chamado.php') ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>/abrir_chamado.php" title="Abrir Chamado"><i class="fa-solid fa-plus"></i><span class="link-text">Abrir Chamado</span></a></li>
                <li class="<?php echo ($pagina_atual == 'meus_chamados.php') ? 'active' : ''; ?>"><a href="<?php echo $base_path; ?>/meus_chamados.php" title="Meus Chamados"><i class="fa-solid fa-ticket"></i><span class="link-text">Meus Chamados</span></a></li>
                
                <?php
                if (isset($departamento_usuario) && $departamento_usuario == 'Pessoal') {
                    echo '<li class="' . ($pagina_atual == 'base_conhecimento_dp.php' ? 'active' : '') . '"><a href="' . $base_path . '/faq_dp/base_conhecimento_dp.php" title="Base de Conhecimento DP"><i class="fa-solid fa-book-medical"></i><span class="link-text">Base de Conhecimento DP</span></a></li>';
                } else {
                    echo '<li class="' . ($pagina_atual == 'kb.php' ? 'active' : '') . '"><a href="' . $base_path . '/kb.php" title="Base de Conhecimento"><i class="fa-solid fa-book-open"></i><span class="link-text">Base de Conhecimento</span></a></li>';
                }
                ?>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-toggle-wrapper">
        <button id="sidebar-toggle" title="Recolher/Expandir Menu">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const profileLink = document.querySelector('.user-profile-link');

    function ajustarAlinhamentoAvatar() {
        if (!sidebar || !profileLink) return;
        if (sidebar.classList.contains('recolhida')) {
            profileLink.style.justifyContent = 'center';
        } else {
            profileLink.style.justifyContent = 'flex-start';
        }
    }

    if(sidebar && toggleBtn && profileLink) {
        if (localStorage.getItem('sidebarRecolhida') === 'true') {
            sidebar.classList.add('recolhida');
        }
        ajustarAlinhamentoAvatar();

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('recolhida');
            localStorage.setItem('sidebarRecolhida', sidebar.classList.contains('recolhida'));
            ajustarAlinhamentoAvatar();
        });
    }
});
</script>

<div id="toast-container"></div>