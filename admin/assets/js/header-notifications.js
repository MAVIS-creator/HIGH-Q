// Initialize notifications
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('notifBtn');
    if (!btn) return;

    // Create dropdown container
    const wrap = document.createElement('div');
    wrap.className = 'notif-dropdown';
    
    const panel = document.createElement('div');
    panel.className = 'notif-panel notification-list';
    panel.id = 'notifPanel';
    
    // Clone button and add to wrapper
    wrap.appendChild(btn.cloneNode(true));
    wrap.appendChild(panel);
    
    // Replace original button with wrapper
    const orig = btn.parentNode;
    orig.replaceChild(wrap, btn);

    const badge = wrap.querySelector('#notifBadge');

    // Function to load and display notifications
    async function loadNotifications() {
        try {
            const res = await fetch('/HIGH-Q/admin/api/notifications.php');
            if (!res.ok) return;
            const data = await res.json();
            
            // Update badge
            const count = data.notifications?.length || 0;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            badge.textContent = count;

            // Update panel content
            panel.innerHTML = '';
            if (!data.notifications || data.notifications.length === 0) {
                panel.innerHTML = '<div class="notif-empty">No notifications</div>';
                return;
            }

            // Create notification items
            data.notifications.forEach(n => {
                const item = document.createElement('a');
                item.className = 'notification-item ' + (n.is_read ? 'read' : '');
                item.setAttribute('data-notification-id', n.id);
                item.setAttribute('data-notification-type', n.type);
                item.href = '#';

                const title = document.createElement('div');
                title.className = 'notification-title';
                title.textContent = n.title || '';

                const message = document.createElement('div');
                message.className = 'notification-message';
                message.textContent = n.message || '';

                const time = document.createElement('div');
                time.className = 'notification-time';
                time.textContent = n.created_at || '';

                item.appendChild(title);
                item.appendChild(message);
                item.appendChild(time);
                panel.appendChild(item);
            });
        } catch(e) {
            console.error('Error loading notifications:', e);
            panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
        }
    }

    // Toggle panel and load notifications
    wrap.querySelector('button').addEventListener('click', function(e) {
        e.stopPropagation();
        const isVisible = panel.style.display === 'block';
        panel.style.display = isVisible ? 'none' : 'block';
        if (!isVisible) loadNotifications();
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) {
            panel.style.display = 'none';
        }
    });

    // Initial load and polling
    loadNotifications();
    setInterval(loadNotifications, 60000); // Poll every minute
});