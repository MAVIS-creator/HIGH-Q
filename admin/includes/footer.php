<?php
// admin/includes/footer.php
?>
    </main>
    <footer class="admin-footer">
        <div style="background:var(--hq-yellow);padding:12px 18px;border-radius:8px;display:flex;align-items:center;justify-content:space-between;max-width:1200px;margin:0 auto;">
            <div>
                <div style="font-weight:700;color:#111;">HIGH Q SOLID ACADEMY</div>
                <div style="color:#333;">Always Ahead of Others</div>
            </div>
            <div style="color:#222;font-size:0.95rem;">© <?= date('Y') ?> HIGH Q SOLID ACADEMY LIMITED</div>
        </div>
    </footer>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

</body>

</html>

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
