// Function to check for new notifications
function checkNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                showNotifications(data.notifications);
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

// Function to show notifications
function showNotifications(notifications) {
    const notificationContainer = document.getElementById('notification-container');
    if (!notificationContainer) return;

    // Clear existing notifications
    notificationContainer.innerHTML = '';
            
            notifications.forEach(notification => {
        const notificationElement = document.createElement('div');
        notificationElement.className = 'notification-item';
        notificationElement.innerHTML = `
            <div class="notification-content">
                <p>${notification.message}</p>
                <small>${new Date(notification.created_at).toLocaleString()}</small>
                    </div>
            <button class="btn-close" onclick="markAsRead(${notification.id})">
                <i class="fas fa-times"></i>
            </button>
        `;
        notificationContainer.appendChild(notificationElement);
    });

    // Show notification badge if there are unread notifications
    const badge = document.getElementById('notification-badge');
    if (badge) {
        badge.textContent = notifications.length;
        badge.style.display = notifications.length > 0 ? 'block' : 'none';
    }
}

// Function to mark notification as read
function markAsRead(notificationId) {
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkNotifications(); // Refresh notifications
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

// Function to toggle notifications dropdown
function toggleNotifications() {
    const container = document.getElementById('notification-container');
    if (container) {
        container.style.display = container.style.display === 'none' ? 'block' : 'none';
        }
}

// Add click event listener to the bell icon
document.addEventListener('DOMContentLoaded', function() {
    const bellIcon = document.querySelector('.notification-icon');
    if (bellIcon) {
        bellIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleNotifications();
        });
}

    // Close notifications when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.getElementById('notification-container');
        const bellIcon = document.querySelector('.notification-icon');
        
        if (container && !container.contains(e.target) && !bellIcon.contains(e.target)) {
            container.style.display = 'none';
        }
    });
});

// Check for notifications every 30 seconds
setInterval(checkNotifications, 30000);

// Initial check when page loads
document.addEventListener('DOMContentLoaded', checkNotifications);
