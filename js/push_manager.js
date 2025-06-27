// js/push_manager.js (VERSÃO DE DEPURAÇÃO)

console.log("Debug: push_manager.js foi carregado.");

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function managePushSubscription() {
    console.log("Debug PASSO 1: Iniciando managePushSubscription().");

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.error('Debug PASSO 2: FALHA - Navegador não suporta as tecnologias de Push.');
        return;
    }
    console.log("Debug PASSO 2: SUCESSO - Navegador compatível.");

    try {
        const swPath = '/chamados_contec/service-worker.js';
        console.log(`Debug PASSO 3: Tentando registrar o Service Worker em: ${swPath}`);
        
        const registration = await navigator.serviceWorker.register(swPath, {
            scope: '/chamados_contec/'
        });
        console.log('Debug PASSO 4: SUCESSO - Service Worker registrado.', registration);

        console.log("Debug PASSO 5: Solicitando permissão de notificação...");
        const permission = await window.Notification.requestPermission();
        console.log("Debug PASSO 6: Resposta da permissão do usuário:", permission);

        if (permission !== 'granted') {
            console.info('Debug PASSO 7: Permissão não concedida pelo usuário. Processo interrompido.');
            return;
        }
        
        console.log("Debug PASSO 8: Permissão concedida! Continuando para obter a inscrição...");
        
        let subscription = await registration.pushManager.getSubscription();
        if (subscription === null) {
            console.log("Debug PASSO 9: Nenhuma inscrição existente. Criando uma nova...");
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(window.vapidPublicKey)
            });
            console.log("Debug PASSO 10: Nova inscrição criada.");
        } else {
            console.log("Debug PASSO 9: Inscrição já existente encontrada.");
        }

        console.log("Debug PASSO 11: Enviando inscrição para o servidor...");
        await fetch('save-subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscription),
        });
        console.log("Debug PASSO 12: Inscrição enviada com sucesso. FIM.");

    } catch (error) {
        console.error("Debug: ERRO CRÍTICO no processo de subscrição:", error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Debug: DOM carregado.");
    if (window.vapidPublicKey) {
        console.log("Debug: Chave VAPID encontrada. Chamando a função principal de subscrição.");
        managePushSubscription();
    } else {
        console.error("Debug: ERRO FATAL - window.vapidPublicKey não foi encontrada. Verifique o arquivo includes/sidebar.php");
    }
});