<?php
// adicionar_usuario.php
$titulo_pagina = "Adicionar Novo Usuário";
$css_pagina = "formularios.css"; // Carrega nosso CSS de formulários
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas usuários 'ti' podem acessar esta página
if ($tipo_usuario != 'ti') {
    $_SESSION['form_message'] = "Acesso negado.";
    $_SESSION['form_message_type'] = 'error';
    header("Location: painel.php");
    exit();
}

// Lista de departamentos vinda da imagem
$departamentos = [
    'Administrativo', 'Comercial', 'Contábil', 'Diretoria', 'Financeiro', 
    'Fiscal', 'MAP I.A', 'Marketing', 'Pessoal', 'Recepção', 
    'RFpastrello', 'RH', 'TI', 'Metos Holding'
];
sort($departamentos); // Opcional: ordena a lista em ordem alfabética

?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
    </div>

    <div class="content-body">
        
        <form action="processa_adicionar_usuario.php" method="POST">
            <div class="form-group">
                <label for="nome_completo">Nome Completo</label>
                <input type="text" id="nome_completo" name="nome_completo" required>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento">
                        <option value="">-- Selecione um departamento --</option>
                        <?php foreach($departamentos as $depto): ?>
                            <option value="<?php echo htmlspecialchars($depto); ?>">
                                <?php echo htmlspecialchars($depto); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <div class="form-group">
                    <label for="tipo_usuario">Tipo de Usuário</label>
                    <select id="tipo_usuario" name="tipo_usuario" required>
                        <option value="colaborador">Colaborador</option>
                        <option value="ti">TI</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <div>
                    <input type="radio" id="status_ativo" name="ativo" value="1" checked>
                    <label for="status_ativo" style="display: inline; font-weight: normal;">Ativo</label>
                </div>
                <div>
                    <input type="radio" id="status_inativo" name="ativo" value="0">
                    <label for="status_inativo" style="display: inline; font-weight: normal;">Inativo</label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Salvar Usuário</button>
                <a href="admin_usuarios.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>

    </div>
</div>

<?php
$conexao->close();
?>
    </div> </body>
</html>