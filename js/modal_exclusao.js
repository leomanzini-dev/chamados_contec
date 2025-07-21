// js/modal_exclusao.js (VERSÃO ATUALIZADA COM CONFIRMAÇÃO DE TEXTO)
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-confirmacao');
    if (!modal) return;

    // Novos elementos do formulário no modal
    const formExcluir = document.getElementById('form-excluir-generico');
    const inputHiddenId = document.getElementById('id-excluir-hidden');
    const inputConfirmacao = document.getElementById('input-confirmacao-generico');
    const btnSubmit = document.getElementById('modal-confirmar-submit');
    
    const btnCancelar = document.getElementById('modal-cancelar');
    const btnFechar = document.getElementById('modal-fechar');
    const modalMensagem = document.getElementById('modal-mensagem');

    // Abre o modal quando qualquer botão .btn-excluir for clicado
    document.querySelectorAll('.btn-excluir').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const nome = this.dataset.nome;
            const tipo = this.dataset.tipo;
            
            // Configura o formulário do modal
            modalMensagem.innerHTML = `Você tem certeza que deseja excluir o ${tipo} <strong>"${nome}"</strong>? Esta ação é irreversível.`;
            formExcluir.action = `excluir_${tipo}.php`;
            inputHiddenId.value = id;
            
            // Reseta o campo de confirmação e o botão
            inputConfirmacao.value = '';
            btnSubmit.disabled = true;
            
            modal.style.display = 'flex';
            inputConfirmacao.focus(); // Foca no campo de texto
        });
    });
    
    // Habilita/desabilita o botão de exclusão conforme o usuário digita
    inputConfirmacao.addEventListener('keyup', function() {
        btnSubmit.disabled = inputConfirmacao.value.trim().toUpperCase() !== 'EXCLUIR';
    });

    // Função para fechar o modal
    function fecharModal() {
        modal.style.display = 'none';
    }

    btnCancelar.addEventListener('click', fecharModal);
    btnFechar.addEventListener('click', fecharModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            fecharModal();
        }
    });
});