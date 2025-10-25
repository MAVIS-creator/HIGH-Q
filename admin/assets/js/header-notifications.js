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
            // Build API endpoint using ADMIN_BASE when available to avoid incorrect relative resolution
            const adminBase = (typeof window.ADMIN_BASE !== 'undefined' && window.ADMIN_BASE) ? window.ADMIN_BASE : '';
            const notificationsEndpoint = adminBase ? adminBase.replace(/\/$/, '') + '/api/notifications.php' : 'api/notifications.php';
            // include credentials so session cookie is sent and the API can authenticate the admin
            const res = await (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat(notificationsEndpoint, { credentials: 'same-origin' }) : fetch(notificationsEndpoint, { credentials: 'same-origin' }));

            // Debug: show raw response shape (helps determine if wrapper returns parsed JSON/string/Response)
            console.log('Notifications API raw response:', res);

            // Normalize response: support multiple shapes returned by different fetch wrappers
            // - hqFetchCompat returns a Response-like wrapper with _parsed
            // - some polyfills override fetch to return parsed JSON/string directly
            // - native fetch returns a Response with text()/json()
            let data = null;
            try {
                if (res && res._parsed) {
                    data = res._parsed;
                } else if (typeof res === 'string') {
                    try {
                        data = JSON.parse(res);
                    } catch (err) {
                        console.error('Notifications API returned invalid string JSON:', res);
                        panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
                        return;
                    }
                } else if (res && typeof res === 'object' && !res.text) {
                    // already-parsed JSON object from a polyfill/wrapper
                    data = res;
                } else if (res && typeof res.text === 'function' && typeof res.ok === 'boolean') {
                    // native Response-like
                    if (!res.ok) {
                        console.warn('Notifications endpoint returned HTTP', res.status);
                        return;
                    }
                    const txt = await res.text();
                    try {
                        data = JSON.parse(txt);
                    } catch (err) {
                        console.error('Notifications API returned non-JSON response:', txt);
                        panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
                        return;
                    }
                } else {
                    console.error('Unexpected notifications response', res);
                    panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
                    return;
                }
            } catch (e) {
                console.error('Error normalizing notifications response', e, res);
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
                    // include credentials so session cookie is sent and the API can authenticate the admin
                    const res = await (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat(notificationsEndpoint, { credentials: 'same-origin' }) : (fetch(notificationsEndpoint, { credentials: 'same-origin' }).catch(err => { console.error('Notifications fetch failed', err); return null; })));

                    // Quick normalization: handle parsed JSON returned directly, our compat wrapper, or a native Response
                    if (!res) {
                        panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
                        return;
                    }

                    // If hqFetchCompat (or a polyfill) returned parsed JSON directly
                    let data = null;
                    if (res.notifications) {
                        data = res;
                    } else if (res._parsed) {
                        data = res._parsed;
                    } else {
                        try {
                            // assume native Response
                            const txt = await res.text();
                            data = JSON.parse(txt);
                        } catch (err) {
                            console.error('Failed to parse notifications response', err, res);
                            panel.innerHTML = '<div class="notif-empty">Error loading notifications</div>';
                            return;
                        }
                    }
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
                            const markEndpointAll = adminBase ? adminBase.replace(/\/$/, '') + '/api/mark_read.php' : 'api/mark_read.php';
                            return (typeof window.hqFetchCompat === 'function' ? window.hqFetchCompat(markEndpointAll, { method: 'POST', body: fd, credentials: 'same-origin' }) : fetch(markEndpointAll, { method: 'POST', body: fd, credentials: 'same-origin' }));
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