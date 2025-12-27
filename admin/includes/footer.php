<?php
// admin/includes/footer.php
// Check if main tag needs closing (some pages handle it themselves)
if (!isset($skipMainClose) || !$skipMainClose) {
    echo '</main>';
}
?>
    <footer class="admin-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="footer-logo">
                    <i class='bx bx-award'></i>
                </div>
                <div class="footer-info">
                    <div class="footer-title">HIGH Q SOLID ACADEMY</div>
                    <div class="footer-tagline">Always Ahead of Others</div>
                </div>
            </div>
            <div class="footer-meta">
                <span class="footer-desc">Empowering students since 2018</span>
                <span class="footer-divider">•</span>
                <span class="footer-copyright">© <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED</span>
                <span class="footer-divider">•</span>
                <a href="https://github.com/MAVIS-creator" target="_blank" rel="noopener noreferrer" class="footer-credit-mavis" style="color:#1e40af;font-weight:800;text-decoration:none;transition:color 0.2s;">Made by MAVIS</a>
                <span class="footer-divider">•</span>
                <a href="https://github.com/gamerdave-web" target="_blank" rel="noopener noreferrer" class="footer-credit-gamer" style="color:#0a0a0a;font-weight:700;text-decoration:none;transition:color 0.2s;">Exam portal made by gamerdave</a>
            </div>
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