<?php
// admin/includes/footer.php
?>
    </main>
    <footer class="admin-footer">
        <div class="footer-card footer-card--highlight">
            <div class="footer-card-left">
                <span class="footer-icon"><i class='bx bx-award'></i></span>
            </div>
            <div class="footer-card-body">
                <div class="footer-title">HIGH Q SOLID ACADEMY</div>
                <div class="footer-sub">Always Ahead of Others</div>
                <div class="footer-desc muted">Empowering students since 2018 with quality education and excellent results</div>
            </div>
            <div class="footer-card-right">© <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED</div>
        </div>
    </footer>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

</body>

</html>

<script>
// Robust sidebar toggle initialization
(function(){
    function initSidebarToggle(){
        try{
            const menuToggle = document.getElementById('menuToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if(!menuToggle) return;

            menuToggle.addEventListener('click', () => {
                try{
                    document.body.classList.toggle('sidebar-collapsed');
                    // For small screens, use overlay active when sidebar is open
                    if (window.innerWidth <= 768) {
                        if (sidebarOverlay) sidebarOverlay.classList.toggle('active', !document.body.classList.contains('sidebar-collapsed'));
                    } else {
                        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                    }
                }catch(e){ console.error('toggle error', e); }
            });

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', () => {
                    document.body.classList.remove('sidebar-collapsed');
                    sidebarOverlay.classList.remove('active');
                });
            }
        }catch(e){ console.error('initSidebarToggle failed', e); }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSidebarToggle);
    else initSidebarToggle();
})();
</script>

<!-- Global AJAX auth helper: parse JSON error responses and redirect to login with return_to -->
<script>
    window.hqAjaxAuthHandler = {
        handleJsonAuthError: function(json) {
            if (!json) return false;
            var err = json.error || json.message || '';
            if (json.error === 'Unauthenticated' || err.indexOf('Access denied') === 0) {
                return true;
            }
            return false;
        },
        redirectToLoginWithReturn: function() {
            var cur = window.location.pathname + window.location.search + window.location.hash;
            var target = '/admin/login.php?return_to=' + encodeURIComponent(cur);
            if (window.Swal) {
                Swal.fire({
                    title: 'Session expired',
                    text: 'You need to sign in to continue — redirecting to login...',
                    icon: 'warning',
                    timer: 1800,
                    showConfirmButton: false
                }).then(function() { window.location.href = target; });
                setTimeout(function(){ window.location.href = target; }, 2000);
            } else {
                window.location.href = target;
            }
        }
    };

    // Optional: a global fetch wrapper that handles JSON auth errors automatically.
    // Use like: hqFetch('/admin/api/xyz', {method:'POST', body:...}).then(...)
    window.hqFetch = function(url, opts) {
        opts = opts || {};
        opts.credentials = opts.credentials || 'same-origin';
        return fetch(url, opts).then(function(resp) {
            var ct = '';
            try {
                if (resp && resp.headers && typeof resp.headers.get === 'function') ct = resp.headers.get('Content-Type') || '';
                else if (resp && resp._parsed) ct = 'application/json';
                else if (typeof resp === 'string') ct = 'text/html';
            } catch(e) { ct = ''; }
            if (resp.status === 401 || resp.status === 403) {
                return resp.json().catch(function(){ return null; }).then(function(j){
                    if (window.hqAjaxAuthHandler.handleJsonAuthError(j)) { window.hqAjaxAuthHandler.redirectToLoginWithReturn(); throw new Error('auth'); }
                    throw new Error('http-'+resp.status);
                });
            }
            if (ct.indexOf('application/json') !== -1) return resp.json();
            return resp.text().then(function(txt){
                if (txt && txt.trim().indexOf('<!DOCTYPE') === 0 && txt.indexOf('<form') !== -1 && txt.indexOf('name="login"') !== -1) {
                    window.hqAjaxAuthHandler.redirectToLoginWithReturn();
                    throw new Error('auth-html');
                }
                return txt;
            });
        });
    };

    // Compatibility helper: unified entrypoint for codebases that expect either
    // the existing `hqFetch()` (which returns parsed JSON/text) or the native
    // `fetch()` Response object. Call `hqFetchCompat(url, opts)` in new code.
    // It will prefer `hqFetch` when available and wrap its parsed result into
    // a Response-like object so existing `fetch(...).then(r=>r.text()/r.json())`
    // chains continue to work. When `hqFetch` is not present it falls back to
    // native `fetch` unchanged.
    window.hqFetchCompat = function(url, opts) {
        opts = opts || {};
        // If native fetch is to be used (no hqFetch defined), return its promise
        if (typeof window.hqFetch !== 'function') {
            return fetch(url, opts);
        }

        // Otherwise use hqFetch which returns parsed JSON or text (or may throw
        // on auth). Wrap parsed value into a Response-like object so callers
        // using .json()/.text() still work.
        return Promise.resolve().then(function(){
            return window.hqFetch(url, opts);
        }).then(function(parsed){
            // Build a minimal Response-like object
            var isObj = parsed !== null && typeof parsed === 'object';
            var isStr = typeof parsed === 'string';
            var wrapper = {
                ok: true,
                status: 200,
                // minimal headers-like object for code that expects resp.headers.get(...)
                headers: {
                    get: function(name) {
                        if (!name) return null;
                        var n = String(name).toLowerCase();
                        if (n === 'content-type') return isObj ? 'application/json' : 'text/html';
                        return null;
                    }
                },
                // return parsed for json(); if parsed is string, attempt parse
                json: function() {
                    return new Promise(function(resolve, reject){
                        if (isObj) return resolve(parsed);
                        if (isStr) {
                            try { resolve(JSON.parse(parsed)); } catch(e){ reject(e); }
                        } else {
                            // other types
                            resolve(parsed);
                        }
                    });
                },
                // return string representation for text()
                text: function() {
                    return new Promise(function(resolve){
                        if (isStr) return resolve(parsed);
                        try { resolve(JSON.stringify(parsed)); } catch(e){ resolve(String(parsed)); }
                    });
                },
                // convenience: return the parsed value directly
                _parsed: parsed
            };
            return wrapper;
        });
    };
</script>

<script>
// Polyfill: intercept global fetch and XHR responses so we can handle auth HTML/JSON centrally.
(function(){
    // helper to decide if a JSON payload is an auth error
    function isAuthJson(j){
        if (!j) return false; if (j.code && (j.code === 'unauthenticated' || j.code === 'no_users' || j.code === 'access_denied')) return true; if (j.error && (j.error.toLowerCase().indexOf('unauth') !== -1 || j.error.toLowerCase().indexOf('access denied') !== -1)) return true; return false;
    }

    // Save original fetch if present
    var _fetch = window.fetch;
    if (_fetch) {
        window.fetch = function(input, init){
            return _fetch(input, init).then(function(resp){
                var ct = '';
                try {
                    if (resp && resp.headers && typeof resp.headers.get === 'function') ct = resp.headers.get('Content-Type') || '';
                    else if (resp && resp._parsed) ct = 'application/json';
                    else if (typeof resp === 'string') ct = 'text/html';
                } catch(e) { ct = ''; }
                if (resp.status === 401 || resp.status === 403) {
                    // try parse JSON, else redirect
                    return resp.clone().text().then(function(txt){
                        try { var j = JSON.parse(txt); if (isAuthJson(j)) { window.hqAjaxAuthHandler.redirectToLoginWithReturn(); throw new Error('auth'); } } catch(e) {}
                        // if non-json or not auth json, still redirect on 401/403
                        window.hqAjaxAuthHandler.redirectToLoginWithReturn(); throw new Error('auth');
                    });
                }
                if (ct.indexOf('application/json') !== -1) return resp.json();
                return resp.text().then(function(txt){
                    var trimmed = txt.trim();
                    if (trimmed && trimmed.indexOf('<!DOCTYPE') === 0 && trimmed.indexOf('<form') !== -1 && trimmed.toLowerCase().indexOf('name="login"') !== -1) {
                        window.hqAjaxAuthHandler.redirectToLoginWithReturn(); throw new Error('auth-html');
                    }
                    return txt;
                });
            });
        };
    }

    // Patch XMLHttpRequest send to detect auth HTML responses
    try {
        var XHR = window.XMLHttpRequest;
        var origSend = XHR.prototype.send;
        XHR.prototype.send = function(body){
            this.addEventListener('load', function(){
                try {
                    var ct = (this.getResponseHeader('Content-Type') || '').toLowerCase();
                    if (this.status === 401 || this.status === 403) {
                        var txt = this.responseText || '';
                        try { var j = JSON.parse(txt); if (isAuthJson(j)) { window.hqAjaxAuthHandler.redirectToLoginWithReturn(); return; } } catch(e) {}
                        window.hqAjaxAuthHandler.redirectToLoginWithReturn(); return;
                    }
                    if (ct.indexOf('application/json') === -1) {
                        var txt = (this.responseText || '').trim();
                        if (txt && txt.indexOf('<!DOCTYPE') === 0 && txt.indexOf('<form') !== -1 && txt.toLowerCase().indexOf('name="login"') !== -1) {
                            window.hqAjaxAuthHandler.redirectToLoginWithReturn(); return;
                        }
                    }
                } catch(e) { /* swallow */ }
            });
            return origSend.call(this, body);
        };
    } catch(e) { /* environment may disallow */ }
})();
</script>
