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
                <a href="https://github.com/MAVIS-creator" target="_blank" rel="noopener noreferrer" class="credit-mavis">Made by MAVIS</a>
                <span class="footer-divider">•</span>
                <a href="https://github.com/gamerdave-web" target="_blank" rel="noopener noreferrer" class="credit-gamerdave">Exam portal made by gamerdave</a>
            </div>
        </div>
    </footer>
    
    <style>
    /* Sticky Footer Styles - ensures footer stays at bottom but doesn't cover content */
    html, body {
        min-height: 100vh;
    }
    
    body {
        display: flex;
        flex-direction: column;
    }
    
    .admin-main {
        flex: 1 0 auto;
        padding-bottom: 80px !important; /* Space for footer */
    }
    
    .admin-footer {
        flex-shrink: 0;
        position: relative;
        margin-left: var(--sidebar-width, 260px);
        background: linear-gradient(90deg, #ffd54f, #ffb300);
        z-index: 50;
        box-shadow: 0 -2px 12px rgba(0,0,0,0.1);
        margin-top: auto;
    }
    
    .admin-footer .footer-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 2rem;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    
    .admin-footer .footer-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .admin-footer .footer-logo {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.1);
        border-radius: 12px;
        font-size: 1.5rem;
        color: #111;
    }
    
    .admin-footer .footer-title {
        font-weight: 800;
        font-size: 0.95rem;
    }
    
    .admin-footer .footer-tagline {
        font-size: 0.8rem;
        color: #374151;
        font-weight: 500;
    }
    
    .admin-footer .footer-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.8rem;
        color: #374151;
        flex-wrap: wrap;
    }
    
    .admin-footer .footer-divider {
        opacity: 0.5;
    }
    
    .admin-footer .credit-mavis {
        color: #1e3a8a;
        font-weight: 800;
        font-size: 0.85rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .admin-footer .credit-mavis:hover {
        color: #3b82f6;
    }
    
    .admin-footer .credit-gamerdave {
        color: #1e293b;
        font-weight: 700;
        font-size: 0.85rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .admin-footer .credit-gamerdave:hover {
        color: #3b82f6;
    }
    
    /* Sidebar collapsed state */
    body.sidebar-collapsed .admin-footer {
        margin-left: 0;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .admin-footer {
            margin-left: 0;
        }
        
        .admin-footer .footer-inner {
            flex-direction: column;
            text-align: center;
            padding: 1rem;
        }
        
        .admin-footer .footer-meta {
            justify-content: center;
        }
    }
    </style>

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