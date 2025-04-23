function loadNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(notifications => {
            const container = document.querySelector('.notifications-list');
            container.innerHTML = '';
            
            notifications.forEach(notification => {
                container.innerHTML += `
                    <div class="notification-item ${notification.read_at ? 'read' : 'unread'}">
                        <i class="fas ${notification.type === 'aprovado' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                        <span>${notification.message}</span>
                        <small>${new Date(notification.created_at).toLocaleDateString()}</small>
                    </div>
                `;
            });

            // Update notification count
            const unreadCount = notifications.filter(n => !n.read_at).length;
            document.querySelector('.notification-count').textContent = unreadCount;
        });
}

function markAllAsRead() {
    fetch('api/notifications.php', {
        method: 'PUT'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

// Load notifications every 30 seconds
setInterval(loadNotifications, 30000);
loadNotifications();
