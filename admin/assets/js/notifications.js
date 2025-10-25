// Notification click handler
document.addEventListener('DOMContentLoaded', function() {
    const notificationList = document.querySelector('.notification-list');
    if (!notificationList) return;

    // Compute admin API base (use injected helper when available)
    var adminApiBase = null;
    try {
        if (typeof window.adminApi === 'function') adminApiBase = null;
        else if (window.HQ_BASE_URL && window.HQ_BASE_URL.length) adminApiBase = window.HQ_BASE_URL.replace(/\/$/, '') + '/admin';
        else if (location.pathname.indexOf('/admin') !== -1) adminApiBase = location.protocol + '//' + location.host + location.pathname.split('/admin')[0] + '/admin';
        else adminApiBase = '/admin';
    } catch(e) { adminApiBase = '/admin'; }

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

            var markUrl = adminApiBase + '/api/mark_read.php';
            const response = await (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat(markUrl, { method: 'POST', body: formData, credentials: 'same-origin' }) : fetch(markUrl, { method: 'POST', body: formData, credentials: 'same-origin' }));

            // Normalize: hqFetchCompat may return parsed; native fetch returns Response
            const ok = (response && response._parsed) ? true : (response && response.ok);
            if (ok) {
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