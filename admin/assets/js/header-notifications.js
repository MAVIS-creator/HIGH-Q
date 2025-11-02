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
        try {
            // include credentials so session cookie is sent and the API can authenticate the admin
            const res = await fetch((window.HQ_ADMIN_BASE || '') + '/api/notifications.php', { credentials: 'same-origin' });
            if (!res.ok) {
                console.warn('Notifications endpoint returned HTTP', res.status);
                return;
            }

            // Read as text first so we can show raw HTML errors (PHP notices) in console instead of causing an unhandled JSON parse exception
            const txt = await res.text();
            let data;
            try {
                data = JSON.parse(txt);
            } catch (err) {
                console.error('Notifications API returned non-JSON response:', txt);
                panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
                return;
            }
            
            // Update badge
            const count = data.notifications?.length || 0;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            badge.textContent = count;

            // Update panel content
            panel.innerHTML = '';

            // add header with Mark all as read control
            const headerBar = document.createElement('div');
            headerBar.className = 'notif-panel-header';
            headerBar.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-bottom:1px solid #f1f1f1;background:#fafafa"><strong style="font-size:13px">Notifications</strong><div><button class="mark-all" style="background:none;border:none;color:#007bff;cursor:pointer;padding:4px 8px">Mark all as read</button></div></div>';
            panel.appendChild(headerBar);
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
                const u = (data.urls[n.type] || '').replace(/^\//, '');
                item.href = (window.HQ_ADMIN_BASE || '') + '/' + u + n.id;
                
                const title = document.createElement('div');
                title.className = 'notification-title';
                title.textContent = n.title || '';

                const message = document.createElement('div');
                message.className = 'notification-message';
                message.textContent = n.message || '';

                const time = document.createElement('div');
                time.className = 'notification-time';
                // Format the timestamp nicely
                const date = new Date(n.created_at);
                time.textContent = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

                item.appendChild(title);
                item.appendChild(message);
                item.appendChild(time);
                panel.appendChild(item);

                // Add click handler
                item.addEventListener('click', async function(e) {
                    e.preventDefault();
                    // Mark as read
                    const formData = new FormData();
                    formData.append('type', n.type);
                    formData.append('id', n.id);
                    try {
                        await fetch((window.HQ_ADMIN_BASE || '') + '/api/mark_read.php', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        });
                        // Add read class for visual feedback
                        item.classList.add('read');
                        // Update the badge count
                        const curCount = parseInt(badge.textContent) || 0;
                        if (curCount > 0) {
                            badge.textContent = curCount - 1;
                            if (curCount - 1 === 0) {
                                badge.style.display = 'none';
                            }
                        }
                        // Navigate to the detail page
                        window.location.href = item.href;
                    } catch (err) {
                        console.error('Failed to mark notification as read:', err);
                    }
                });
            });

            // wire up Mark all button
            const markAllBtn = panel.querySelector('.mark-all');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', async function(ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    markAllBtn.disabled = true; 
                    markAllBtn.textContent = 'Marking...';
                    try {
                        // Send mark_read for each notification
                        const ops = data.notifications.map(n => {
                            const fd = new FormData(); 
                            fd.append('type', n.type); 
                            fd.append('id', n.id);
                            return fetch((window.HQ_ADMIN_BASE || '') + '/api/mark_read.php', {
                                method: 'POST', 
                                body: fd, 
                                credentials: 'same-origin'
                            });
                        });
                        await Promise.all(ops);
                        
                        // Update UI to show all as read
                        panel.querySelectorAll('.notification-item').forEach(item => {
                            item.classList.add('read');
                        });
                        badge.style.display = 'none';
                        badge.textContent = '0';
                        
                        markAllBtn.textContent = 'All read';
                        setTimeout(() => {
                            markAllBtn.textContent = 'Mark all as read';
                            markAllBtn.disabled = false;
                        }, 2000);
                    } catch (e) {
                        console.error('Failed to mark all as read', e);
                        markAllBtn.disabled = false; 
                        markAllBtn.textContent = 'Mark all as read';
                    }
                });
            }
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