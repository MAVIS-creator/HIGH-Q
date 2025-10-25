// Notification click handler
document.addEventListener('DOMContentLoaded', function() {
    const notificationList = document.querySelector('.notification-list');
    if (!notificationList) return;

    notificationList.addEventListener('click', async function(e) {
        const notificationItem = e.target.closest('[data-notification-id]');
        if (!notificationItem) return;

        e.preventDefault();

        const id = notificationItem.dataset.notificationId;
        const type = notificationItem.dataset.notificationType;
        const url = notificationItem.href;

        // Mark as read first
        try {
            const formData = new FormData();
            formData.append('type', type);
            formData.append('id', id);

            const response = await fetch('../api/mark_read.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                // Visual feedback
                notificationItem.classList.add('read');
                
                // Navigate to the target page
                if (url) {
                    window.location.href = url;
                }
            } else {
                console.error('Error marking notification as read');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });
});