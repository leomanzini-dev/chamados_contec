// js/login_animation.js (VERSÃO FINAL COM MÉTODO JAVASCRIPT)
document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DO LABEL FLUTUANTE ---
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach(formGroup => {
        const input = formGroup.querySelector('input');
        if (input) {
            // Verifica no carregamento da página se o campo já tem valor (autocomplete)
            if (input.value.trim() !== '') {
                formGroup.classList.add('is-focused');
            }
            // Adiciona a classe quando o campo ganha foco
            input.addEventListener('focus', () => {
                formGroup.classList.add('is-focused');
            });
            // Remove a classe quando o campo perde o foco, SE ESTIVER VAZIO
            input.addEventListener('blur', () => {
                if (input.value.trim() === '') {
                    formGroup.classList.remove('is-focused');
                }
            });
        }
    });

    // --- INICIALIZAÇÃO DA ANIMAÇÃO DE FUNDO ---
    if (window.VANTA) {
        VANTA.NET({
            el: "#animated-bg", mouseControls: true, touchControls: true, gyroControls: false,
            minHeight: 200.00, minWidth: 200.00, scale: 1.00, scaleMobile: 1.00,
            color: '#3C6E71', backgroundColor: '#121828', points: 12.00,
            maxDistance: 22.00, spacing: 18.00
        });
    }

    // --- LÓGICA DO FORMULÁRIO DE LOGIN ---
    const loginForm = document.getElementById('login-form');
    const formContainer = document.getElementById('form-container');
    const successAnimation = document.getElementById('success-animation');
    const errorMessageContainer = document.getElementById('error-message-container');
    const toggleSenha = document.getElementById('toggle-senha');
    const campoSenha = document.getElementById('senha');

    if(toggleSenha && campoSenha) {
        toggleSenha.addEventListener('click', function () {
            const type = campoSenha.getAttribute('type') === 'password' ? 'text' : 'password';
            campoSenha.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
            this.classList.toggle('fa-eye');
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(loginForm);
            
            fetch('processa_login.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                errorMessageContainer.innerHTML = '';
                if (data.success) {
                    formContainer.classList.add('fade-out');
                    setTimeout(() => {
                        formContainer.style.display = 'none';
                        successAnimation.classList.remove('hidden');
                        successAnimation.classList.add('fade-in');
                    }, 400);
                    setTimeout(() => { window.location.href = 'painel.php'; }, 2000);
                } else {
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