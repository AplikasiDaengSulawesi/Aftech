/**
 * AFTECH Navigation Engine - Standard Version
 * SPA Disabled for Maximum Stability.
 */

// Navigasi standar browser (Reload penuh)
document.addEventListener('DOMContentLoaded', function() {
    // Tidak ada e.preventDefault(), biarkan browser memuat halaman secara normal
    
    // Logika Auto-Close Sidebar untuk Mobile tetap dipertahankan
    const sidebarMenu = document.querySelector('.metismenu');
    if (sidebarMenu) {
        sidebarMenu.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                const mainWrapper = document.getElementById('main-wrapper');
                if (mainWrapper) {
                    mainWrapper.classList.add('menu-toggle');
                    const hamburger = document.querySelector('.hamburger');
                    if (hamburger) hamburger.classList.remove('is-active');
                }
            }
        });
    }
});

function toggleFullScreen() {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen();
    else if (document.exitFullscreen) document.exitFullscreen();
}
