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
    const overlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');

    if (!menuToggle || !overlay) return;

    menuToggle.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');

        // Only show overlay on mobile
        if (window.innerWidth <= 768) {
            if (document.body.classList.contains('sidebar-collapsed')) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        } else {
            overlay.classList.remove('active');
        }
    });

    overlay.addEventListener('click', () => {
        document.body.classList.remove('sidebar-collapsed');
        overlay.classList.remove('active');
    });
});

</script>
