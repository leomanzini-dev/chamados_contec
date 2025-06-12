<?php
// meus_chamados.php
$titulo_pagina = "Meus Chamados";
$css_pagina = "tabelas.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$chamados = [];
$sql = "SELECT t.id, t.motivo_chamado, t.data_criacao, c.nome AS nome_categoria, s.nome AS nome_status
        FROM tickets AS t
        JOIN categorias AS c ON t.id_categoria = c.id
        JOIN status_tickets AS s ON t.id_status = s.id
        WHERE t.id_solicitante = ?
        ORDER BY t.data_criacao DESC";

if ($stmt = $conexao->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario_logado);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $chamados = $resultado->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <div class="user-menu">
            <div class="notificacao-sino">
                <i class="fa-solid fa-bell"></i>
                <span class="contador" id="contador-notificacoes" style="<?php echo (!isset($total_nao_lidas) || $total_nao_lidas == 0) ? 'display: none;' : ''; ?>"><?php echo $total_nao_lidas ?? 0; ?></span>
                <div class="notificacoes-dropdown">
                    <!-- Conteúdo das notificações -->
                </div>
            </div>
            <span>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>
    </div>

    <div class="content-body">
        <?php if (empty($chamados)): ?>
            <p class="nenhum-chamado">Você ainda não abriu nenhum chamado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nº Chamado</th>
                        <th>Assunto</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Data de Abertura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <!-- << NOVO >> Adicionado ID ao corpo da tabela -->
                <tbody id="tabela-chamados-corpo">
                    <?php foreach ($chamados as $chamado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($chamado['id']); ?></td>
                            <td><?php echo htmlspecialchars($chamado['motivo_chamado']); ?></td>
                            <td><?php echo htmlspecialchars($chamado['nome_categoria']); ?></td>
                            <td>
                                <span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>">
                                    <?php echo htmlspecialchars($chamado['nome_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_criacao']))); ?></td>
                            <td>
                                <a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>" class="btn-acao">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if($conexao) { $conexao->close(); } ?>
    </div>
</body>

<!-- << NOVO >> Script de atualização automática para esta página -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoUsuario = "<?php echo $tipo_usuario; ?>";

    // Só executa se for um colaborador nesta página
    if (tipoUsuario !== 'colaborador') return;

    function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }
    
    function flashElement(element) {
        if (!element) return;
        element.style.transition = 'background-color 0.2s';
        element.style.backgroundColor = '#fffacd';
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 1000);
    }

    function atualizarTabelaChamados(chamados) {
        const tabelaCorpo = document.getElementById('tabela-chamados-corpo');
        if (!tabelaCorpo) return;

        const htmlAtual = tabelaCorpo.innerHTML;
        let novoHtml = '';

        if (chamados.length > 0) {
            chamados.forEach(chamado => {
                const statusClass = 'status-' + chamado.nome_status.toLowerCase().replace(/ /g, '-');
                const dataFormatada = new Date(chamado.data_criacao).toLocaleString('pt-BR', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                novoHtml += `
                    <tr>
                        <td>${escapeHTML(chamado.id)}</td>
                        <td>${escapeHTML(chamado.motivo_chamado)}</td>
                        <td>${escapeHTML(chamado.nome_categoria)}</td>
                        <td><span class="status ${statusClass}">${escapeHTML(chamado.nome_status)}</span></td>
                        <td>${dataFormatada}</td>
                        <td><a href="detalhes_chamado.php?id=${chamado.id}" class="btn-acao">Ver Detalhes</a></td>
                    </tr>
                `;
            });
        }
        
        // Compara o HTML para evitar redesenhar sem necessidade
        if (tabelaCorpo.innerHTML.replace(/\s/g, '') !== novoHtml.replace(/\s/g, '')) {
            tabelaCorpo.innerHTML = novoHtml;
            flashElement(tabelaCorpo);
        }
    }
    
    function atualizarNotificacoes(contagem) {
        const contador = document.getElementById('contador-notificacoes');
        if (!contador) return;
        
        const contagemAtual = parseInt(contador.innerText) || 0;

        if (contagem > 0) {
            contador.innerText = contagem;
            contador.style.display = 'inline-block';
            if (contagem > contagemAtual) {
                flashElement(contador.closest('.notificacao-sino'));
            }
        } else {
            contador.style.display = 'none';
        }
    }

    async function verificarAtualizacoes() {
        try {
            const cacheBuster = new Date().getTime();
            const url = `/chamados_contec/verificar_updates.php?t=${cacheBuster}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.error) { return; }
            
            if (data.tipo_usuario === 'colaborador') {
                if (data.todos_meus_chamados) {
                    atualizarTabelaChamados(data.todos_meus_chamados);
                }
            }
            
            if (typeof data.notificacoes_nao_lidas !== 'undefined') {
                atualizarNotificacoes(data.notificacoes_nao_lidas);
            }

        } catch (error) {
            console.error("Erro na verificação:", error);
        }
    }
    
    setInterval(verificarAtualizacoes, 5000);
});
</script>
</html>
