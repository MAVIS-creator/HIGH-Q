<?php
// admin/includes/footer.php
?>
</main>
<footer class="admin-footer">
    © <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED - Admin Panel
</footer>
</body>
</html>
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const closeBtn = document.getElementById('closeSidebar');

// Function to open sidebar
function openSidebar() {
    sidebar.classList.add('active');
    overlay.classList.add('active');
}

// Function to close sidebar
function closeSidebar() {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
}

// Close when clicking outside
overlay.addEventListener('click', closeSidebar);
closeBtn.addEventListener('click', closeSidebar);

// Optional: open sidebar from a menu button in header
document.getElementById('menuToggle')?.addEventListener('click', openSidebar);
const menuToggle = document.getElementById('menuToggle');

menuToggle.addEventListener('click', () => {
    sidebar.classList.add('active');
    overlay.classList.add('active');
});
</script>
