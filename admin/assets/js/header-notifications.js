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
    panel.style.maxHeight = '420px';
    panel.style.overflow = 'auto';

    // Clone button and add to wrapper
    wrap.appendChild(btn.cloneNode(true));
    wrap.appendChild(panel);

    // Replace original button with wrapper
    const orig = btn.parentNode;
    orig.replaceChild(wrap, btn);

    const badge = wrap.querySelector('#notifBadge');

    // Function to load and display notifications
    async function loadNotifications() {
        let res, data;
        try {
            // Build API endpoint
            const adminBase = (typeof window.ADMIN_BASE !== 'undefined' && window.ADMIN_BASE) ? window.ADMIN_BASE : '';
            const notificationsEndpoint = adminBase ? adminBase.replace(/\/$/, '') + '/api/notifications.php' : 'api/notifications.php';

            // Fetch notifications
            res = await (typeof window.hqFetchCompat === 'function'
                ? window.hqFetchCompat(notificationsEndpoint, { credentials: 'same-origin' })
                : fetch(notificationsEndpoint, { credentials: 'same-origin' })
            );

            console.log('Notifications API raw response:', res);

            // Normalize response
            if (res && res._parsed) {
                data = res._parsed;
            } else if (typeof res === 'string') {
                data = JSON.parse(res);
            } else if (res && res.notifications) {
                data = res;
            } else if (res && typeof res.text === 'function') {
                const txt = await res.text();
                data = JSON.parse(txt);
            } else {
                throw new Error('Unexpected notifications response format');
            }

            // Update badge
            const count = data.notifications?.length || 0;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            badge.textContent = count;

            // Update panel content
            panel.innerHTML = '';

            if (count === 0) {
                panel.innerHTML = '<div class="notif-empty">No notifications</div>';
                return;
            }

            // Add header bar
            const headerBar = document.createElement('div');
            headerBar.className = 'notif-header flex justify-between items-center p-2 border-b';
            headerBar.innerHTML = `
                <span class="font-semibold text-sm">Notifications</span>
                <button class="mark-all text-xs text-blue-600 hover:underline">Mark all as read</button>
            `;
            panel.appendChild(headerBar);

            // Render notifications
            data.notifications.forEach(n => {
                const item = document.createElement('div');
                item.className = 'notification-item p-2 border-b cursor-pointer' + (n.is_read ? ' read' : '');
                item.innerHTML = `
                    <div class="notif-title font-medium">${n.title}</div>
                    <div class="notif-msg text-sm text-gray-600">${n.message}</div>
                    <div class="notif-time text-xs text-gray-400">${n.created_at}</div>
                `;
                item.addEventListener('click', async () => {
                    try {
                        // mark as read
                        const fd = new FormData();
                        fd.append('type', n.type);
                        fd.append('id', n.id);

                        const markEndpoint = adminBase ? adminBase.replace(/\/$/, '') + '/api/mark_read.php' : 'api/mark_read.php';
                        await (typeof window.hqFetchCompat === 'function'
                            ? window.hqFetchCompat(markEndpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
                            : fetch(markEndpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
                        );

                        // update badge
                        const curCount = parseInt(badge.textContent) || 0;
                        if (curCount > 0) {
                            badge.textContent = curCount - 1;
                            if (curCount - 1 === 0) badge.style.display = 'none';
                        }

                        // navigate if link available
                        const urlMap = data.urls || {};
                        const targetBase = urlMap[n.type] || '#';
                        if (targetBase && targetBase !== '#') {
                            const targetUrl = targetBase + (n.meta?.post_id || n.meta?.student_id || n.meta?.thread_id || n.id);
                            window.location.href = targetUrl;
                        }
                    } catch (err) {
                        console.error('Failed to mark notification as read:', err);
                    }
                });
                panel.appendChild(item);
            });

            // Handle "Mark all as read"
            const markAllBtn = headerBar.querySelector('.mark-all');
            markAllBtn.addEventListener('click', async (ev) => {
                ev.preventDefault();
                markAllBtn.disabled = true;
                markAllBtn.textContent = 'Marking...';

                try {
                    const markEndpointAll = adminBase ? adminBase.replace(/\/$/, '') + '/api/mark_read.php' : 'api/mark_read.php';
                    const ops = data.notifications.map(n => {
                        const fd = new FormData();
                        fd.append('type', n.type);
                        fd.append('id', n.id);
                        return (typeof window.hqFetchCompat === 'function'
                            ? window.hqFetchCompat(markEndpointAll, { method: 'POST', body: fd, credentials: 'same-origin' })
                            : fetch(markEndpointAll, { method: 'POST', body: fd, credentials: 'same-origin' })
                        );
                    });

                    await Promise.all(ops);
                    panel.querySelectorAll('.notification-item').forEach(i => i.classList.add('read'));
                    badge.style.display = 'none';
                    badge.textContent = '0';
                    markAllBtn.textContent = 'All read';
                    setTimeout(() => {
                        markAllBtn.textContent = 'Mark all as read';
                        markAllBtn.disabled = false;
                    }, 2000);
                } catch (err) {
                    console.error('Failed to mark all as read:', err);
                    markAllBtn.disabled = false;
                    markAllBtn.textContent = 'Mark all as read';
                }
            });

        } catch (e) {
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

    // Initial load and auto-refresh
    loadNotifications();
    setInterval(loadNotifications, 60000);
});
