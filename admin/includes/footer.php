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
</script>
<script>
// Sidebar toggle — ensure elements exist
const menuToggle = document.getElementById('menuToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');

        // Overlay only for mobile
        if (window.innerWidth <= 768) {
            if (sidebarOverlay) sidebarOverlay.classList.toggle('active', document.body.classList.contains('sidebar-collapsed'));
        } else {
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        }
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
        document.body.classList.remove('sidebar-collapsed');
        sidebarOverlay.classList.remove('active');
    });
}
</script>
