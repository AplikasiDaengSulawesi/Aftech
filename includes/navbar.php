<!--**********************************
    Nav header start
***********************************-->
<div class="nav-header">
    <a href="index.php" class="brand-logo">
        <span class="logo-abbr" style="font-size: 24px; font-weight: 900; color: var(--af-primary);">A</span>
        <span class="logo-compact" style="font-size: 24px; font-weight: 900; color: var(--af-primary);">AFTECH</span>
        <span class="brand-title" style="font-size: 24px; font-weight: 900; color: var(--af-primary); letter-spacing: 2px;">AFTECH</span>
    </a>

    <div class="nav-control">
        <div class="hamburger">
            <span class="line"></span><span class="line"></span><span class="line"></span>
        </div>
    </div>
</div>
<!--**********************************
    Nav header end
***********************************-->
<style>
    /* Mengikuti format template asli untuk layout, tapi warna tetap Indigo & Putih sesuai permintaan */
    .nav-header {
        background: #ffffff !important;
        border-bottom: 1px solid #f0f0f0;
    }
    .nav-header .brand-logo {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .hamburger .line {
        background: var(--af-primary) !important;
    }
    [data-sidebar-style="mini"] .logo-abbr {
        display: block !important;
    }
    [data-sidebar-style="mini"] .brand-title, 
    [data-sidebar-style="mini"] .logo-compact {
        display: none !important;
    }
</style>