<!--**********************************
            Sidebar start
***********************************-->
<?php
$role = $_SESSION['role'] ?? 'gudang'; // Default ke gudang jika tidak set
?>
<div class="deznav">
    <div class="deznav-scroll">
        <ul class="metismenu" id="menu">
            <li><a href="index.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-home-2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- MENU KHUSUS ADMIN -->
            <?php if($role == 'admin'): ?>
            <li><a href="data_produksi.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-notepad"></i>
                    <span class="nav-text">Data Produksi</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- MENU QC & ADMIN -->
            <?php if($role == 'admin' || $role == 'qc'): ?>
            <li><a href="qc_checker.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-search-3"></i>
                    <span class="nav-text">QC Checker</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- MENU SEMUA ROLE (ADMIN, QC, GUDANG) -->
            <li><a href="warehouse_inventory.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-layer-1"></i>
                    <span class="nav-text">Gudang & Stok</span>
                </a>
            </li>

            <!-- MENU GUDANG & ADMIN -->
            <?php if($role == 'admin' || $role == 'gudang'): ?>
            <li><a href="distributor_scan.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-search-2"></i>
                    <span class="nav-text">Scan Pengiriman</span>
                </a>
            </li>
            <li><a href="data_distributor.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-send-2"></i>
                    <span class="nav-text">Data Pengiriman</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- MENU LAPORAN & LOG (ADMIN ONLY) -->
            <?php if($role == 'admin'): ?>
            <li><a href="log_aktivitas.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-clock"></i>
                    <span class="nav-text">Log Aktivitas</span>
                </a>
            </li>
            <li><a href="laporan.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-file"></i>
                    <span class="nav-text">Laporan</span>
                </a>
            </li>
            <li><a href="pengaturan.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-settings-2"></i>
                    <span class="nav-text">Pengaturan</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<!--**********************************
            Sidebar end
***********************************-->