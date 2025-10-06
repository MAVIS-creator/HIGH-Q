// Viewport check for admin panel
function checkViewportMode() {
    const isMobile = window.innerWidth < 768;
    const isDesktopMode = window.visualViewport ? window.visualViewport.scale >= 1 : true;
    
    if (isMobile && !isDesktopMode) {
        Swal.fire({
            title: 'Desktop Mode Required',
            text: 'You must enable Desktop Mode in your mobile browser to access the admin panel.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Enable Desktop Mode',
            cancelButtonText: 'Exit Admin Panel',
            allowOutsideClick: false
        }).then((result) => {
            if (!result.isConfirmed) {
                // Redirect to logout if they don't want to enable desktop mode
                window.location.href = '../admin/logout.php';
            }
        });
        return false;
    }
    return true;
}

// Run check on load
document.addEventListener('DOMContentLoaded', () => {
    checkViewportMode();
});

// Run check on orientation change
window.addEventListener('orientationchange', () => {
    setTimeout(() => {
        if (!checkViewportMode()) {
            // If viewport check fails after orientation change, redirect to logout
            window.location.href = '../admin/logout.php';
        }
    }, 300);
});

// Add periodic check every 5 seconds
setInterval(() => {
    if (!checkViewportMode()) {
        // If viewport check fails during usage, redirect to logout
        window.location.href = '../admin/logout.php';
    }
}, 5000);