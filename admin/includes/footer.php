<?php
// admin/includes/footer.php
?>
    </main>
    <footer class="relative ml-[var(--sidebar-width)] w-[calc(100%-var(--sidebar-width))] bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 shadow-[0_-2px_10px_rgba(0,0,0,0.06)] z-50 transition-all duration-300 ease-in-out">
        <div class="flex items-center gap-5 px-6 py-4 max-w-full">
            <div class="flex-shrink-0">
                <span class="text-2xl text-amber-800"><i class='bx bx-award'></i></span>
            </div>
            <div class="flex-grow">
                <div class="font-semibold text-slate-900">HIGH Q SOLID ACADEMY</div>
                <div class="text-sm text-slate-700">Always Ahead of Others</div>
                <div class="text-xs text-slate-600">Empowering students since 2018 with quality education and excellent results</div>
            </div>
            <div class="flex-shrink-0 text-sm text-slate-700">© <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED</div>
        </div>
    </footer>

    <style>
        /* Ensure footer responds to sidebar collapse */
        body.sidebar-collapsed footer {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        @media (max-width: 768px) {
            footer {
                margin-left: 0 !important;
                width: 100% !important;
            }
            footer > div {
                flex-direction: column;
                text-align: center;
                gap: 12px;
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
