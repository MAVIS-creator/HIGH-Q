<?php
// admin/includes/footer.php
?>
</main>
<footer class="admin-footer">
    © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
</footer>

<div id="sidebarOverlay" class="sidebar-overlay"></div>

</body>

</html>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');

    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
    }
    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }

    if (sidebar && menuToggle && overlay) {
        menuToggle.addEventListener('click', () => {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        overlay.addEventListener('click', closeSidebar);
    }

    // Optional: close sidebar on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });
});
</script>
