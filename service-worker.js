// service-worker.js

// Evento acionado quando uma notificação push é recebida
self.addEventListener('push', function(event) {
    let payload;

    // Verifica se a notificação tem dados
    if (event.data) {
        try {
            // Tenta ler os dados como JSON (o que nosso backend PHP envia)
            payload = event.data.json();
        } catch (e) {
            // Se falhar (como no caso do botão de teste), trata os dados como texto simples
            console.log("Payload não era JSON, tratando como texto.");
            payload = {
                title: 'Notificação de Teste',
                body: event.data.text(),
                icon: '/chamados_contec/img/logo_contec.png',
                data: { url: '/chamados_contec/painel.php' },
                tag: 'teste-geral'
            };
        }
    } else {
        // Se a notificação chegar sem dados nenhuns, usa um padrão
        payload = {
            title: 'Nova Notificação',
            body: 'Você tem uma nova atualização no sistema.',
            icon: '/chamados_contec/img/logo_contec.png',
            data: { url: '/chamados_contec/painel.php' },
            tag: 'geral'
        };
    }

    const title = payload.title;
    const options = {
        body: payload.body,
        icon: payload.icon,
        badge: payload.icon, // Ícone para Android
        tag: payload.tag,    // Agrupa notificações
        data: payload.data   // Dados extras, como o link para abrir
    };

    // Pede ao navegador para exibir a notificação
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Evento acionado quando o usuário clica na notificação
self.addEventListener('notificationclick', function(event) {
    const urlToOpen = event.notification.data.url;
    event.notification.close();

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clientList => {
            // Tenta focar numa aba já aberta em vez de abrir uma nova
            for (let client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // Se não encontrar, abre uma nova aba
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});