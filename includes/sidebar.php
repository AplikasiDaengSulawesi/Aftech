<!--**********************************
            Sidebar start
***********************************-->
<?php
require_once 'auth_check.php';
$role = $_SESSION['role'] ?? 'gudang'; // Default ke gudang jika tidak set
?>
<div class="deznav">
    <div class="deznav-scroll">
        <ul class="metismenu" id="menu">
            <?php if(can_access('dashboard')): ?>
            <li><a href="index.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-home-2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('production_data')): ?>
            <li><a href="production_data.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-notepad"></i>
                    <span class="nav-text">Data Produksi</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('qc_checker')): ?>
            <li><a href="qc_checker.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-search-3"></i>
                    <span class="nav-text">QC Checker</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('warehouse')): ?>
            <li><a href="warehouse_inventory.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-layer-1"></i>
                    <span class="nav-text">Gudang & Stok</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('shipment_reports')): ?>
            <li><a href="shipment_scan.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-search-2"></i>
                    <span class="nav-text">Scan Pengiriman</span>
                </a>
            </li>
            <li><a href="shipment_data.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-send-2"></i>
                    <span class="nav-text">Data Pengiriman</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('reports')): ?>
            <li><a href="reports.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-print"></i>
                    <span class="nav-text">Laporan Rekap</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('activity_logs')): ?>
            <li><a href="activity_logs.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-clock"></i>
                    <span class="nav-text">Log Aktivitas</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if($role === 'admin'): ?>
            
            <?php if(can_access('settings')): ?>
            <li><a href="settings.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-settings-2"></i>
                    <span class="nav-text">Pengaturan</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(can_access('access_control')): ?>
            <li><a href="access_control.php" class="ai-icon" aria-expanded="false">
                    <i class="flaticon-381-lock"></i>
                    <span class="nav-text">Hak Akses</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>
<!--**********************************
            Sidebar end
***********************************-->