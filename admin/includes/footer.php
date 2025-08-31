<?php
// admin/includes/footer.php
?>
</main>

<footer class="admin-footer">
    © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
</footer>

<!-- Sidebar overlay -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

</body>
</html>

<script>
// Sidebar toggle
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

// Toggle sidebar
menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
});

// Close when clicking overlay
overlay.addEventListener('click', closeSidebar);
</script>
