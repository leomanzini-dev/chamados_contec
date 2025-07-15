<?php
// editar_usuario.php (VERSÃO FINAL E CORRIGIDA)
$titulo_pagina = "Editar Usuário";
$css_pagina = "formularios.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if ($tipo_usuario != 'ti') {
    $_SESSION['mensagem_erro'] = "Acesso negado.";
    header("Location: painel.php");
    exit();
}

$id_usuario_editar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_usuario_editar) {
    $_SESSION['mensagem_erro'] = "ID de usuário inválido.";
    header("Location: admin_usuarios.php");
    exit();
}

$sql = "SELECT nome_completo, email, departamento, tipo_usuario, ativo FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_usuario_editar);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0) {
    $_SESSION['mensagem_erro'] = "Usuário não encontrado.";
    header("Location: admin_usuarios.php");
    exit();
}
$usuario_editar = $resultado->fetch_assoc();
$stmt->close();

$departamentos = ['Administrativo', 'Comercial', 'Contábil', 'Diretoria', 'Financeiro', 'Fiscal', 'MAP I.A', 'Marketing', 'Pessoal', 'Recepção', 'RFpastrello', 'RH', 'TI', 'Metos Holding'];
sort($departamentos);
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?>: <?php echo htmlspecialchars($usuario_editar['nome_completo']); ?></h1>
    </div>

    <div class="content-body">
        <div class="form-card">
            <form action="processa_editar_usuario.php" method="POST">
                <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($id_usuario_editar); ?>">

                <div class="form-group">
                    <label for="nome_completo">Nome Completo</label>
                    <input type="text" id="nome_completo" name="nome_completo" value="<?php echo htmlspecialchars($usuario_editar['nome_completo']); ?>" required>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario_editar['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="departamento">Departamento</label>
                        <select id="departamento" name="departamento">
                            <option value="">-- Selecione um departamento --</option>
                            <?php foreach($departamentos as $depto): ?>
                                <option value="<?php echo htmlspecialchars($depto); ?>" <?php echo ($usuario_editar['departamento'] == $depto) ? 'selected' : ''; ?>><?php echo htmlspecialchars($depto); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                     <div class="form-group">
                        <label for="tipo_usuario">Tipo de Usuário</label>
                        <select id="tipo_usuario" name="tipo_usuario" required>
                            <option value="colaborador" <?php echo ($usuario_editar['tipo_usuario'] == 'colaborador') ? 'selected' : ''; ?>>Colaborador</option>
                            <option value="ti" <?php echo ($usuario_editar['tipo_usuario'] == 'ti') ? 'selected' : ''; ?>>TI</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div class="radio-group">
                            <input type="radio" id="status_ativo" name="ativo" value="1" <?php echo ($usuario_editar['ativo'] == 1) ? 'checked' : ''; ?>>
                            <label for="status_ativo">Ativo</label>
                            <input type="radio" id="status_inativo" name="ativo" value="0" <?php echo ($usuario_editar['ativo'] == 0) ? 'checked' : ''; ?>>
                            <label for="status_inativo">Inativo</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="senha">Nova Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Deixe em branco para não alterar">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar Alterações</button>
                    <a href="admin_usuarios.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($conexao) { $conexao->close(); } ?>
</div>
</body>
</html>