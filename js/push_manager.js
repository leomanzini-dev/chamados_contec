// js/push_manager.js

// Função para converter a chave pública para o formato correto
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Função principal para gerir a subscrição
async function managePushSubscription() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.warn('Push-Benachrichtigungen werden von diesem Browser nicht unterstützt.');
        return;
    }

    // Regista o Service Worker
    const registration = await navigator.serviceWorker.register('/chamados_contec/service-worker.js', {
        scope: '/chamados_contec/'
    });

    // Pede permissão para notificações
    const permission = await window.Notification.requestPermission();
    if (permission !== 'granted') {
        console.info('Permissão para notificações não concedida.');
        return;
    }

    // Obtém a subscrição existente ou cria uma nova
    let subscription = await registration.pushManager.getSubscription();
    if (subscription === null) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(window.vapidPublicKey)
        });
    }

    // Envia a subscrição para o nosso servidor
    await fetch('save-subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(subscription),
    });
}

// Inicia o processo quando a página carrega
document.addEventListener('DOMContentLoaded', () => {
    // Só tenta subscrever se a chave pública estiver disponível
    if (window.vapidPublicKey) {
        managePushSubscription().catch(err => console.error(err));
    }
});
