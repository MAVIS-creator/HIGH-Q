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
menuToggle.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-collapsed');

    // Overlay only for mobile
    if (window.innerWidth <= 768) {
        overlay.classList.toggle('active', document.body.classList.contains('sidebar-collapsed'));
    } else {
        overlay.classList.remove('active');
    }
});

</script>
