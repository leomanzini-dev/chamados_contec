// service-worker.js - Versão Final

// Este evento é acionado quando uma notificação push é recebida
self.addEventListener('push', function(event) {
    let payload = {
        title: 'Nova Notificação',
        body: 'Você tem uma nova atualização no sistema.',
        icon: '/chamados_contec/img/logo_contec.png',
        data: { url: '/chamados_contec/painel.php' },
        tag: 'geral'
    };

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            console.error('Erro ao ler o payload da notificação:', e);
        }
    }

    const title = payload.title;
    const options = {
        body: payload.body,
        icon: payload.icon,
        badge: payload.icon,
        tag: payload.tag,
        data: payload.data,
        actions: payload.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Este evento é acionado quando o utilizador clica na notificação
self.addEventListener('notificationclick', function(event) {
    const urlToOpen = event.notification.data.url;
    event.notification.close();

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clientList => {
            for (let client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
