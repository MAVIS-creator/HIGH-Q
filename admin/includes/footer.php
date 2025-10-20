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
            var ct = resp.headers.get('Content-Type') || '';
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
</script>
