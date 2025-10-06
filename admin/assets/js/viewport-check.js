// Viewport check for admin panel
function checkViewportMode() {
    const isMobile = window.innerWidth < 768;
    const isDesktopMode = window.visualViewport ? window.visualViewport.scale >= 1 : true;
    
    if (isMobile && !isDesktopMode) {
        Swal.fire({
            title: 'Desktop Mode Recommended',
            text: 'For the best admin experience, please enable Desktop Mode in your mobile browser.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Got it!',
            cancelButtonText: 'Continue anyway',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                localStorage.setItem('viewportChecked', 'true');
            }
        });
    }
}

// Run check on load
document.addEventListener('DOMContentLoaded', () => {
    if (!localStorage.getItem('viewportChecked')) {
        checkViewportMode();
    }
});

// Run check on orientation change
window.addEventListener('orientationchange', () => {
    setTimeout(checkViewportMode, 300);
});