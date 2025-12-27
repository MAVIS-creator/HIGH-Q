<?php
// admin/includes/footer.php
// Check if main tag needs closing (some pages handle it themselves)
if (!isset($skipMainClose) || !$skipMainClose) {
    echo '</main>';
}
?>
    <footer class="admin-footer" style="position:fixed !important;bottom:0 !important;left:260px;right:0;background:linear-gradient(90deg,#ffd54f,#ffb300);z-index:50;box-shadow:0 -2px 12px rgba(0,0,0,0.1);">
        <div class="footer-inner" style="display:flex;align-items:center;justify-content:space-between;padding:1rem 2rem;gap:1.5rem;flex-wrap:wrap;">
            <div class="footer-brand" style="display:flex;align-items:center;gap:1rem;">
                <div class="footer-logo" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.1);border-radius:12px;font-size:1.5rem;color:#111;">
                    <i class='bx bx-award'></i>
                </div>
                <div class="footer-info">
                    <div class="footer-title" style="font-weight:800;font-size:0.95rem;">HIGH Q SOLID ACADEMY</div>
                    <div class="footer-tagline" style="font-size:0.8rem;color:#374151;font-weight:500;">Always Ahead of Others</div>
                </div>
            </div>
            <div class="footer-meta" style="display:flex;align-items:center;gap:0.75rem;font-size:0.8rem;color:#374151;flex-wrap:wrap;">
                <span class="footer-desc" style="font-weight:500;">Empowering students since 2018</span>
                <span class="footer-divider" style="opacity:0.5;">•</span>
                <span class="footer-copyright" style="font-weight:600;">© <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED</span>
                <span class="footer-divider" style="opacity:0.5;">•</span>
                <a href="https://github.com/MAVIS-creator" target="_blank" rel="noopener noreferrer" style="color:#1e3a8a;font-weight:800;font-size:0.85rem;text-decoration:none;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#1e3a8a'">Made by MAVIS</a>
                <span class="footer-divider" style="opacity:0.5;">•</span>
                <a href="https://github.com/gamerdave-web" target="_blank" rel="noopener noreferrer" style="color:#1e293b;font-weight:700;font-size:0.85rem;text-decoration:none;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#1e293b'">Exam portal made by gamerdave</a>
            </div>
        </div>
    </footer>
    <script>
    // Adjust footer left position based on sidebar state
    (function(){
        function adjustFooter(){
            var footer = document.querySelector('.admin-footer');
            if(!footer) return;
            var collapsed = document.body.classList.contains('sidebar-collapsed');
            footer.style.left = collapsed ? '0' : '260px';
        }
        adjustFooter();
        // Watch for sidebar toggle
        var observer = new MutationObserver(adjustFooter);
        observer.observe(document.body, {attributes: true, attributeFilter: ['class']});
    })();
    </script>

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

// Profile dropdown toggle
(function(){
    function initProfileDropdown(){
        try{
            const avatarBtn = document.getElementById('avatarDropdownBtn');
            const wrapper = avatarBtn?.closest('.header-avatar-wrapper');
            if(!avatarBtn || !wrapper) return;

            avatarBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                wrapper.classList.toggle('open');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if(!wrapper.contains(e.target)){
                    wrapper.classList.remove('open');
                }
            });

            // Close dropdown when clicking a menu item (except logout which navigates away)
            const dropdownItems = wrapper.querySelectorAll('.profile-dropdown-item:not(.profile-dropdown-item--logout)');
            dropdownItems.forEach(item => {
                item.addEventListener('click', () => {
                    wrapper.classList.remove('open');
                });
            });
        }catch(e){ console.error('initProfileDropdown failed', e); }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initProfileDropdown);
    else initProfileDropdown();
})();
</script>