<!--**********************************
    Nav header start
***********************************-->
<div class="nav-header">
    <a href="index.php" class="brand-logo">
        <img class="logo-abbr" src="../assets/images/logo.png" alt="Logo" style="width: 45px; border-radius: 4px;">
        <div class="brand-title">
            <img src="../assets/images/logo.png" alt="Logo" style="width: 35px; border-radius: 4px; vertical-align: middle;">
            <span class="brand-text" style="font-size: 16px; font-weight: 900; color: var(--af-primary); letter-spacing: 1px; margin-left: 10px; vertical-align: middle;">AFTECH</span>
        </div>
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
    .nav-header {
        background: #ffffff !important;
        border-bottom: 1px solid #f0f0f0;
    }
    .nav-header .brand-logo {
        display: flex;
        align-items: center;
        padding-left: 20px;
        width: 100%;
        height: 100%;
    }
    .hamburger .line {
        background: var(--af-primary) !important;
    }
    
    /* Ensure brand-title shows its contents flex-ly */
    .brand-title {
        display: flex;
        align-items: center;
    }

    /* Template-specific visibility fixes */
    /* When sidebar is full */
    [data-sidebar-style="full"] .logo-abbr { display: none; }
    [data-sidebar-style="full"] .brand-title { display: flex; }

    /* When sidebar is mini/compact/overlay */
    [data-sidebar-style="mini"] .logo-abbr,
    [data-sidebar-style="compact"] .logo-abbr,
    [data-sidebar-style="overlay"] .logo-abbr { 
        display: block !important; 
        margin: 0 auto;
    }
    
    [data-sidebar-style="mini"] .brand-title,
    [data-sidebar-style="compact"] .brand-title,
    [data-sidebar-style="overlay"] .brand-title { 
        display: none !important; 
    }

    @media (max-width: 768px) {
        .nav-header .brand-logo {
            padding-left: 15px;
            justify-content: center;
        }
        .logo-abbr {
            display: block !important;
            width: 40px !important;
        }
        .brand-title {
            display: none !important;
        }
    }
</style>