// js/login_animation.js
document.addEventListener('DOMContentLoaded', function() {
    
    const loginForm = document.getElementById('login-form');
    const formContainer = document.getElementById('form-container');
    const successAnimation = document.getElementById('success-animation');
    const errorMessageContainer = document.getElementById('error-message-container');
    const toggleSenha = document.getElementById('toggle-senha');
    const campoSenha = document.getElementById('senha');

    // Lógica para mostrar/ocultar senha
    if(toggleSenha && campoSenha) {
        toggleSenha.addEventListener('click', function () {
            const type = campoSenha.getAttribute('type') === 'password' ? 'text' : 'password';
            campoSenha.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    }

    // Lógica para envio do formulário e animação
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o envio padrão

            const formData = new FormData(loginForm);
            
            fetch('processa_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animação de sucesso
                    formContainer.classList.add('fade-out');
                    setTimeout(() => {
                        formContainer.style.display = 'none';
                        successAnimation.classList.remove('hidden');
                        successAnimation.classList.add('fade-in');
                    }, 500); // Espera a animação de fade-out terminar

                    // Redireciona para o painel após a animação
                    setTimeout(() => {
                        window.location.href = 'painel.php';
                    }, 2500); // Tempo total antes do redirecionamento
                } else {
                    // Mostra a mensagem de erro vinda do PHP
                    errorMessageContainer.innerHTML = `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                errorMessageContainer.innerHTML = `<div class="error-message">Ocorreu um erro de comunicação.</div>`;
            });
        });
    }
});
