<!DOCTYPE html>
<html lang="en">
<?php
include '../includes/header.php';
require_once '../includes/db.php';

// Fetch master data for filters
$m_machines = $pdo->query("SELECT name FROM master_machines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$m_shifts = $pdo->query("SELECT name FROM master_shifts ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$items_query = $pdo->query("
    SELECT i.id, i.name, u.name as unit_name, m.name as default_machine
    FROM master_items i
    LEFT JOIN master_units u ON i.unit_id = u.id
    LEFT JOIN master_machines m ON i.default_machine_id = m.id
    ORDER BY i.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$hierarchy_data = [];
foreach($items_query as $it) {
    $sizes = $pdo->prepare("SELECT size_value FROM master_sizes WHERE item_id = ? ORDER BY CAST(size_value AS UNSIGNED) ASC");
    $sizes->execute([$it['id']]);

    $hierarchy_data[$it['name']] = [
        'unit'            => $it['unit_name'],
        'default_machine' => $it['default_machine'],
        'sizes'           => $sizes->fetchAll(PDO::FETCH_COLUMN)
    ];
}
?>
<style>
    .cinema-grid-warehouse { 
        display: grid; grid-template-columns: repeat(auto-fill, minmax(30px, 1fr)); 
        gap: 5px; max-height: 400px; overflow-y: auto; padding: 15px;
        background: #f8f9fa; border-radius: 10px; user-select: none;
    }
    .seat-warehouse { 
        height: 30px; display: flex; align-items: center; justify-content: center; 
        background: #eee; border-radius: 4px; font-size: 10px; font-weight: bold; color: #aaa;
        border: 1px solid #ddd; transition: 0.1s;
    }
    .seat-warehouse.in-stock { background: #fff; color: #1A237E; border-color: #1A237E; cursor: pointer; }
    .seat-warehouse.selected { background: #D50000 !important; color: #fff !important; border-color: #D50000 !important; transform: scale(0.92); }

    .column-toggle-dropdown { padding: 15px; min-width: 220px; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .col-hidden { display: none !important; }

    /* COMPACT HEADER & MOBILE BUTTONS */
    .filter-card-header { 
        margin-bottom: 15px; padding-top: 5px; padding-bottom: 5px;
        display: flex; flex-direction: column; gap: 12px;
    }
    .header-btn-group { display: flex; gap: 8px; width: 100%; }
    
    @media (min-width: 768px) { 
        .filter-card-header { flex-direction: row; justify-content: space-between; align-items: center; } 
        .header-btn-group { width: auto; }
    }
    @media (max-width: 767px) {
        .header-btn-group .btn, .header-btn-group .dropdown { flex: 1; }
        .header-btn-group .btn { width: 100%; justify-content: center; }
    }

    /* TABLE STYLE */
    .table-responsive-md .shadow-hover tbody tr { cursor: pointer; transition: 0.2s; }
    .table-responsive-md .shadow-hover tbody tr:hover { background-color: #f8f9ff !important; }

    /* ACTION MENU */
    .action-list { padding: 0; margin: 0; }
    .action-item {
        display: flex; align-items: center; padding: 12px 15px; color: #333; font-weight: 600; font-size: 14px;
        cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f1f1f1; width: 100%; background: none; border-left: none; border-right: none; border-top: none;
    }
    .action-item:last-child { border-bottom: none; }
    .action-item:hover { background: #f8f9ff; color: var(--af-primary); }
    .action-item i { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 12px; font-size: 14px; }
    .icon-view { background: #e8eaf6; color: #1A237E; }
    .icon-delete { background: #ffebee; color: #d32f2f; }
    
    /* PULSE FOR RECENT ITEM */
    .pulse-amber {
        animation: pulse-amber-shadow 2s infinite;
        border-left: 4px solid #FFC107 !important;
    }
    @keyframes pulse-amber-shadow {
        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
    }
    
    .legend-item { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 600; color: #666; }
    .legend-box { width: 12px; height: 12px; border-radius: 3px; }

    .swal2-close-custom {
        position: absolute; top: 12px; right: 12px; background: #f5f5f5; border-radius: 50%; width: 26px; height: 26px;
        display: flex; align-items: center; justify-content: center; cursor: pointer; color: #999; font-size: 12px;
    }
    
    .batch-badge { display: inline-block; white-space: nowrap; padding: 5px 10px; border-radius: 6px; font-size: 11px !important; font-weight: 800; }
    @media (max-width: 767px) { .batch-badge { white-space: normal; word-break: break-all; max-width: 140px; } }
    .warehouse-method-badge {
        display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 999px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.2px; border: 1px solid transparent;
    }
    .warehouse-method-badge.scan { background: #e8f5e9; color: #1b5e20; border-color: #c8e6c9; }
    .warehouse-method-badge.manual { background: #fff8e1; color: #ef6c00; border-color: #ffe0b2; }
    .warehouse-method-badge.hybrid { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }

    .card-kpi { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .card-kpi .card-body { padding: 1.5rem; }
    .kpi-title-month { font-size: 11px; opacity: 0.9; font-weight: 500; color: #fff !important; }

    /* TAB WAREHOUSE */
    .wh-tabs { border: 0; gap: 8px; margin-bottom: 16px; }
    .wh-tabs .nav-link {
        background: #fff; color: #555; border: 1px solid #e3e6f0; border-radius: 999px;
        padding: 8px 18px; font-weight: 700; font-size: 13px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .wh-tabs .nav-link:hover { color: #1A237E; border-color: #c5cae9; }
    .wh-tabs .nav-link.active { background: #1A237E; color: #fff; border-color: #1A237E; }
    .wh-tabs .nav-link i { margin-right: 6px; }

    .cancel-category-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.2px; border: 1px solid transparent;
    }
    .cancel-category-badge.production { background: #fff8e1; color: #ef6c00; border-color: #ffe0b2; }
    .cancel-category-badge.warehouse  { background: #ffebee; color: #c62828; border-color: #ffcdd2; }
    .device-chip {
        display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
        background: #e8eaf6; color: #1A237E; border: 1px solid #c5cae9; max-width: 180px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle;
    }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        <div class="content-body">
            <div class="container-fluid">
            
                <!-- KPI WIDGETS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card bg-primary shadow-sm card-kpi">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="fa fa-boxes"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Batch</p>
                                        <h3 class="text-white mb-0" id="kpi-batch">0</h3>
                                        <small class="d-block mb-1">Batch di Gudang</small>
                                        <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card bg-info shadow-sm card-kpi">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="fa fa-layer-group"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Dus Masuk</p>
                                        <h3 class="text-white mb-0" id="kpi-qty">0</h3>
                                        <small class="d-block mb-1">Diterima di Gudang</small>
                                        <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card bg-warning shadow-sm card-kpi">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="fa fa-clock"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Telah Terkirim</p>
                                        <h3 class="text-white mb-0" id="kpi-shipped">0</h3>
                                        <small class="d-block mb-1">Dus Keluar</small>
                                        <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card bg-success shadow-sm card-kpi">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="fa fa-check-circle"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Sisa di Gudang</p>
                                        <h3 class="text-white mb-0" id="kpi-verified">0</h3>
                                        <small class="d-block mb-1">Total Dus Tersedia</small>
                                        <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav wh-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-stock-btn" data-bs-toggle="tab" data-bs-target="#tabStock" type="button" role="tab" aria-controls="tabStock" aria-selected="true">
                            <i class="fa fa-boxes"></i>Stock Gudang
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-history-btn" data-bs-toggle="tab" data-bs-target="#tabHistory" type="button" role="tab" aria-controls="tabHistory" aria-selected="false">
                            <i class="fa fa-ban"></i>Riwayat Pembatalan Label
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                <div class="tab-pane fade show active" id="tabStock" role="tabpanel" aria-labelledby="tab-stock-btn">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div>
                                        <h4 class="text-black mb-0 font-w800">Persediaan Gudang (Warehouse Inventory)</h4>
                                        <p class="mb-0 small text-muted">Klik baris untuk melihat peta dus atau mengosongkan batch</p>
                                    </div>
                                    <div class="header-btn-group">
                                        <button type="button" class="btn btn-primary btn-xs shadow-sm font-w600" data-bs-toggle="modal" data-bs-target="#modalAddStock">
                                            <i class="fas fa-plus-circle me-1"></i> Tambah Stok
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-xs shadow-sm dropdown-toggle font-w600 w-100" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-columns me-1 text-primary"></i> Pilih Kolom
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end column-toggle-dropdown p-3 shadow-lg">
                                                <h6 class="dropdown-header ps-0 mb-2 font-w700 text-black">Tampilkan:</h6>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-batch" id="chk-batch" checked disabled><label class="form-check-label small font-w600" for="chk-batch">Batch / Terima</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-item" id="chk-item" checked><label class="form-check-label small font-w600" for="chk-item">Item / Size</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-qty" id="chk-qty" checked><label class="form-check-label small font-w600" for="chk-qty">Total Dus</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-mesin" id="chk-mesin" checked><label class="form-check-label small font-w600" for="chk-mesin">Mesin / Shift</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-method" id="chk-method" checked><label class="form-check-label small font-w600" for="chk-method">Metode Input</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-progres" id="chk-progres" checked><label class="form-check-label small font-w600" for="chk-progres">Verified</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-shipped" id="chk-shipped" checked><label class="form-check-label small font-w600" for="chk-shipped">Terkirim</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-pending" id="chk-pending"><label class="form-check-label small font-w600" for="chk-pending">Sisa Stok</label></div>
                                                <div class="form-check"><input class="form-check-input col-checkbox" type="checkbox" value="col-qc" id="chk-qc" checked><label class="form-check-label small font-w600" for="chk-qc">Pengirim (QC)</label></div>
                                            </div>
                                        </div>
                                        <button onclick="resetFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600">
                                            <i class="fa fa-undo me-1"></i> Reset Filter
                                        </button>
                                    </div>
                                </div>
                                <form id="formFilter" class="row g-2">
                                    <div class="col-12 col-md-3"><input type="text" id="f_search" name="search" class="form-control form-control-sm" placeholder="Cari data..."></div>
                                    <!-- NATIVE BOOTSTRAP SUPER FILTER ITEM -->
                                    <div class="col-6 col-md-2">
                                        <div class="dropdown w-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                                                <span id="sf-label" class="text-truncate text-muted">Semua Item</span>
                                                <i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu shadow-lg border-0 mt-1" style="max-height: 350px; overflow-y: auto; border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 13px; min-width: 100%;">
                                                <li><a class="dropdown-item font-w600 text-black" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectSuperFilter('', '')">Semua Item</a></li>
                                                <li><hr class="dropdown-divider m-0"></li>
                                                <?php foreach($hierarchy_data as $item => $data): ?>
                                                    <li>
                                                        <div class="d-flex justify-content-between align-items-center dropdown-item" style="cursor: default; padding: 5px 15px;">
                                                            <a class="text-black font-w600 text-decoration-none flex-grow-1" style="opacity: 0.7;" href="javascript:void(0)" onclick="selectSuperFilter('<?= $item ?>', '', '')"><?= $item ?></a>
                                                            <?php if(!empty($data['sizes'])): ?>
                                                            <a class="text-primary" style="font-size: 16px; margin-left: 10px;" data-bs-toggle="collapse" href="#collapse-<?= md5($item) ?>" onclick="event.stopPropagation();"><i class="fa fa-plus-circle" style="color: #1A237E;"></i></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                    <?php if(!empty($data['sizes'])): ?>
                                                    <div class="collapse bg-white" id="collapse-<?= md5($item) ?>">
                                                        <?php foreach($data['sizes'] as $sz): ?>
                                                        <?php $displayLabel = $sz . ' ' . $data['unit']; ?>
                                                        <li><a class="dropdown-item text-muted" style="padding: 8px 15px 8px 25px;" href="javascript:void(0)" onclick="selectSuperFilter('<?= $item ?>', '<?= $sz ?>', '<?= $displayLabel ?>')"><?= $displayLabel ?></a></li>
                                                        <?php endforeach; ?>
                                                        <li><a class="dropdown-item text-primary font-w600" style="padding: 8px 15px 8px 25px;" href="javascript:void(0)" onclick="selectSuperFilter('<?= $item ?>', 'Custom', 'Ukuran Lainnya')"><i class="fa fa-plus me-2"></i>Ukuran Lainnya</a></li>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                            <input type="hidden" name="item" id="f_item_val">
                                            <input type="hidden" name="size" id="f_size_val">
                                        </div>
                                    </div>

                                    <!-- NATIVE BOOTSTRAP SUPER FILTER MESIN -->
                                    <div class="col-6 col-md-2">
                                        <div class="dropdown w-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                                                <span id="sf-mesin-label" class="text-truncate text-muted">Semua Mesin</span>
                                                <i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu shadow-lg border-0 mt-1" style="max-height: 350px; overflow-y: auto; border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 13px; min-width: 100%;">
                                                <li><a class="dropdown-item font-w600 text-black" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectMesinFilter('', '', '')">Semua Mesin</a></li>
                                                <li><hr class="dropdown-divider m-0"></li>
                                                <?php foreach($m_machines as $m): ?>
                                                    <li>
                                                        <div class="d-flex justify-content-between align-items-center dropdown-item" style="cursor: default; padding: 5px 15px;">
                                                            <a class="text-black font-w600 text-decoration-none flex-grow-1" style="opacity: 0.7;" href="javascript:void(0)" onclick="selectMesinFilter('<?= $m['name'] ?>', '', '')"><?= $m['name'] ?></a>
                                                            <?php if(!empty($m_shifts)): ?>
                                                            <a class="text-primary" style="font-size: 16px; margin-left: 10px;" data-bs-toggle="collapse" href="#collapse-mesin-<?= md5($m['name']) ?>" onclick="event.stopPropagation();"><i class="fa fa-plus-circle" style="color: #1A237E;"></i></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                    <?php if(!empty($m_shifts)): ?>
                                                    <div class="collapse bg-white" id="collapse-mesin-<?= md5($m['name']) ?>">
                                                        <?php foreach($m_shifts as $sh): ?>
                                                        <?php $displayLabel = $sh['name']; ?>
                                                        <li><a class="dropdown-item text-muted" style="padding: 8px 15px 8px 25px;" href="javascript:void(0)" onclick="selectMesinFilter('<?= $m['name'] ?>', '<?= $sh['name'] ?>', '<?= $displayLabel ?>')"><?= $displayLabel ?></a></li>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                            <input type="hidden" name="machine" id="f_machine_val">
                                            <input type="hidden" name="shift" id="f_shift_val">
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <div class="dropdown w-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                                                <span id="sf-input-label" class="text-truncate text-muted">Semua Metode</span>
                                                <i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu shadow-lg border-0 mt-1" style="border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 13px; min-width: 100%;">
                                                <li><a class="dropdown-item font-w600 text-black" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectInputMethodFilter('', 'Semua Metode')">Semua Metode</a></li>
                                                <li><hr class="dropdown-divider m-0"></li>
                                                <li><a class="dropdown-item" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectInputMethodFilter('scan', 'Scan QR')">Scan QR</a></li>
                                                <li><a class="dropdown-item" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectInputMethodFilter('manual', 'Input Manual')">Input Manual</a></li>
                                                <li><a class="dropdown-item" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectInputMethodFilter('hybrid', 'Hybrid')">Hybrid</a></li>
                                            </ul>
                                            <input type="hidden" name="input_method" id="f_input_method">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3"><input type="text" id="f_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Tanggal Masuk Gudang" readonly><input type="hidden" name="start_date" id="f_start"><input type="hidden" name="end_date" id="f_end"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table shadow-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 text-center col-batch"><strong>BATCH / TERIMA</strong></th>
                                                <th class="col-item"><strong>ITEM / SIZE</strong></th>
                                                <th class="col-qty text-center"><strong>TOTAL DUS</strong></th>
                                                <th class="col-mesin"><strong>MESIN</strong></th>
                                                <th class="col-method text-center"><strong>INPUT</strong></th>
                                                <th class="col-progres text-center"><strong>VERIFIED</strong></th>
                                                <th class="col-shipped text-center"><strong>TERKIRIM</strong></th>
                                                <th class="col-pending text-center"><strong>SISA STOK</strong></th>
                                                <th class="col-qc"><strong>QC CHECK</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody id="warehouseTableBody">
                                            <!-- Data via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer border-0 d-flex flex-column flex-md-row justify-content-between align-items-center p-4 gap-3">
                                <div id="paginationInfo" class="small text-muted font-w600"></div>
                                <nav><ul class="pagination pagination-xs mb-0" id="paginationControls"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
                </div> <!-- /tabStock -->

                <!-- ============== TAB: RIWAYAT PEMBATALAN LABEL ============== -->
                <div class="tab-pane fade" id="tabHistory" role="tabpanel" aria-labelledby="tab-history-btn">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                                <div class="card-body p-3 p-md-4">
                                    <div class="filter-card-header">
                                        <div>
                                            <h4 class="text-black mb-0 font-w800">Riwayat Pembatalan Label</h4>
                                            <p class="mb-0 small text-muted">Seluruh pembatalan label (kategori <b>production</b> &amp; <b>warehouse</b>). Filter termasuk <b>device_id</b>.</p>
                                        </div>
                                        <div class="header-btn-group">
                                            <span class="badge bg-light text-dark border shadow-sm font-w700 d-flex align-items-center" style="font-size:11px;">
                                                <i class="fa fa-layer-group me-1 text-warning"></i>Production: <span id="hist-cnt-prod" class="ms-1">0</span>
                                            </span>
                                            <span class="badge bg-light text-dark border shadow-sm font-w700 d-flex align-items-center" style="font-size:11px;">
                                                <i class="fa fa-warehouse me-1 text-danger"></i>Warehouse: <span id="hist-cnt-wh" class="ms-1">0</span>
                                            </span>
                                            <span class="badge bg-light text-dark border shadow-sm font-w700 d-flex align-items-center" style="font-size:11px;">
                                                <i class="fa fa-mobile-alt me-1 text-primary"></i>Device: <span id="hist-cnt-dev" class="ms-1">0</span>
                                            </span>
                                            <button onclick="resetHistoryFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600">
                                                <i class="fa fa-undo me-1"></i> Reset Filter
                                            </button>
                                        </div>
                                    </div>
                                    <form id="formHistoryFilter" class="row g-2">
                                        <div class="col-12 col-md-3">
                                            <input type="text" id="h_search" name="search" class="form-control form-control-sm" placeholder="Cari batch / item / alasan / nomor label...">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <div class="dropdown w-100">
                                                <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                                                    <span id="hf-item-label" class="text-truncate text-muted">Semua Item</span>
                                                    <i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i>
                                                </button>
                                                <ul class="dropdown-menu shadow-lg border-0 mt-1" style="max-height: 350px; overflow-y: auto; border-radius: 12px; font-family: 'Poppins', sans-serif; font-size: 13px; min-width: 100%;">
                                                    <li><a class="dropdown-item font-w600 text-black" style="padding: 10px 15px;" href="javascript:void(0)" onclick="selectHistoryItemFilter('', '', '')">Semua Item</a></li>
                                                    <li><hr class="dropdown-divider m-0"></li>
                                                    <?php foreach($hierarchy_data as $item => $data): ?>
                                                        <li>
                                                            <div class="d-flex justify-content-between align-items-center dropdown-item" style="cursor: default; padding: 5px 15px;">
                                                                <a class="text-black font-w600 text-decoration-none flex-grow-1" style="opacity: 0.7;" href="javascript:void(0)" onclick="selectHistoryItemFilter('<?= $item ?>', '', '')"><?= $item ?></a>
                                                                <?php if(!empty($data['sizes'])): ?>
                                                                <a class="text-primary" style="font-size: 16px; margin-left: 10px;" data-bs-toggle="collapse" href="#hcollapse-<?= md5($item) ?>" onclick="event.stopPropagation();"><i class="fa fa-plus-circle" style="color: #1A237E;"></i></a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </li>
                                                        <?php if(!empty($data['sizes'])): ?>
                                                        <div class="collapse bg-white" id="hcollapse-<?= md5($item) ?>">
                                                            <?php foreach($data['sizes'] as $sz): ?>
                                                            <?php $displayLabel = $sz . ' ' . $data['unit']; ?>
                                                            <li><a class="dropdown-item text-muted" style="padding: 8px 15px 8px 25px;" href="javascript:void(0)" onclick="selectHistoryItemFilter('<?= $item ?>', '<?= $sz ?>', '<?= $displayLabel ?>')"><?= $displayLabel ?></a></li>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <input type="hidden" name="item" id="h_item_val">
                                                <input type="hidden" name="size" id="h_size_val">
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <input type="text" id="h_batch" name="batch" class="form-control form-control-sm" placeholder="Filter Batch">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <input type="text" id="h_device" name="device_id" class="form-control form-control-sm" placeholder="Filter Device ID / UUID">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <select id="h_category" name="category" class="form-control form-control-sm">
                                                <option value="">Semua Kategori</option>
                                                <option value="production">Production</option>
                                                <option value="warehouse">Warehouse</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <input type="text" id="h_daterange" class="form-control form-control-sm" placeholder="Rentang Tanggal Pembatalan" readonly>
                                            <input type="hidden" name="start_date" id="h_start">
                                            <input type="hidden" name="end_date" id="h_end">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table shadow-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4"><strong>WAKTU BATAL</strong></th>
                                                    <th><strong>BATCH</strong></th>
                                                    <th><strong>ITEM / SIZE</strong></th>
                                                    <th class="text-center"><strong>LABEL NO</strong></th>
                                                    <th class="text-center"><strong>KATEGORI</strong></th>
                                                    <th><strong>DEVICE</strong></th>
                                                    <th><strong>DIBATALKAN OLEH</strong></th>
                                                    <th><strong>ALASAN</strong></th>
                                                </tr>
                                            </thead>
                                            <tbody id="historyTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer border-0 d-flex flex-column flex-md-row justify-content-between align-items-center p-4 gap-3">
                                    <div id="historyPaginationInfo" class="small text-muted font-w600"></div>
                                    <nav><ul class="pagination pagination-xs mb-0" id="historyPaginationControls"></ul></nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- /tabHistory -->

                </div> <!-- /tab-content -->
            </div>
        </div>

        <!-- MODAL TAMBAH STOK GUDANG -->
        <div class="modal fade" id="modalAddStock" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                    <div class="modal-header bg-primary text-white border-0" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title text-white font-w700"><i class="fa fa-plus-circle me-2"></i>Tambah Stok ke Gudang</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="formAddStock">
                        <div class="modal-body p-4">
                            <div class="alert alert-info border-0 small mb-3" style="border-radius: 10px;">
                                <i class="fa fa-info-circle me-1"></i> Stok yang ditambahkan langsung masuk ke gudang. Jika <b>Kode Batch</b> sudah ada, <code>copies</code> akan diakumulasi (append mode).
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small font-w700">Item <span class="text-danger">*</span></label>
                                    <select class="form-control" id="as_item" required>
                                        <option value="">-- Pilih Item --</option>
                                        <?php foreach($hierarchy_data as $itm => $dat): ?>
                                            <option value="<?= htmlspecialchars($itm) ?>" data-unit="<?= htmlspecialchars($dat['unit']) ?>"><?= htmlspecialchars($itm) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small font-w700">Size <span class="text-danger">*</span></label>
                                    <select class="form-control" id="as_size" required>
                                        <option value="">-- Pilih Size --</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small font-w700">Unit</label>
                                    <input type="text" class="form-control" id="as_unit" readonly placeholder="auto">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small font-w700">Mesin <span class="text-danger">*</span></label>
                                    <select class="form-control" id="as_machine" required>
                                        <option value="">-- Pilih Mesin --</option>
                                        <?php foreach($m_machines as $mc): ?>
                                            <option value="<?= htmlspecialchars($mc['name']) ?>"><?= htmlspecialchars($mc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-w700">Shift <span class="text-danger">*</span></label>
                                    <select class="form-control" id="as_shift" required>
                                        <option value="">-- Pilih Shift --</option>
                                        <?php foreach($m_shifts as $sh): ?>
                                            <option value="<?= htmlspecialchars($sh['name']) ?>"><?= htmlspecialchars($sh['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small font-w700">Operator</label>
                                    <input type="text" class="form-control" id="as_operator" placeholder="Nama operator">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-w700">QC</label>
                                    <input type="text" class="form-control" id="as_qc" placeholder="Nama QC">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small font-w700">Tanggal Produksi</label>
                                    <input type="date" class="form-control" id="as_date" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small font-w700">Jam Produksi</label>
                                    <input type="time" class="form-control" id="as_time" value="<?= date('H:i') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small font-w700">Qty per Dus <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="as_quantity" placeholder="Contoh: 1000" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small font-w700">Jumlah Dus (Copies) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="as_copies" min="1" value="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small font-w700">Kode Batch <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="as_batch" placeholder="Mis. 180426-01A-SED-1000-ADMIN-100PCS" required>
                                        <button type="button" class="btn btn-light border" onclick="autoGenerateBatch()" title="Auto-generate dari field di atas"><i class="fa fa-magic"></i></button>
                                    </div>
                                    <small class="text-muted">Format: <code>ddmmyy-mesinShift-item-qty-operator-sizeUnit</code></small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm shadow" id="btnSubmitAddStock">
                                <i class="fa fa-save me-1"></i> Simpan & Masuk Gudang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL PETA UNIT GUDANG (VIEW ONLY) -->
        <div class="modal fade" id="modalWarehouseMap" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                    <div class="modal-header bg-primary text-white border-0" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title text-white font-w700"><i class="fa fa-th-large me-2"></i>Peta Dus Gudang</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-between mb-2">
                            <div><h5 class="text-black mb-0" id="v-item"></h5><p class="small text-primary mb-0" id="v-batch"></p></div>
                        </div>
                        <p class="text-muted small mb-3">Peta visual unit dus yang sudah masuk ke gudang (Warna Indigo).</p>
                        <div class="cinema-grid-warehouse" id="v-grid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.currentPage = 1;
        window.latestData = [];
        window.columnStates = { 'col-batch': true, 'col-item': true, 'col-qty': true, 'col-mesin': true, 'col-method': true, 'col-progres': true, 'col-shipped': true, 'col-pending': true, 'col-qc': true };

        function getWarehouseInputMeta(inputMethod) {
            const normalized = String(inputMethod || '').toLowerCase();
            if (normalized === 'manual') return { key: 'manual', icon: 'fa-keyboard', label: 'Input Manual' };
            if (normalized === 'hybrid') return { key: 'hybrid', icon: 'fa-exchange-alt', label: 'Hybrid' };
            return { key: 'scan', icon: 'fa-qrcode', label: 'Scan QR' };
        }

        function renderSkeleton() {
            const tbody = document.getElementById('warehouseTableBody');
            tbody.innerHTML = '';
            for(let i=0; i<5; i++) {
                tbody.insertAdjacentHTML('beforeend', `<tr><td class="ps-4"><div class="skeleton" style="height:20px; width:80px;"></div></td><td><div class="skeleton skeleton-text"></div></td><td><div class="skeleton skeleton-badge"></div></td><td><div class="skeleton" style="height:15px; width:80%;"></div></td><td><div class="skeleton skeleton-badge"></div></td><td><div class="skeleton skeleton-text"></div></td><td><div class="skeleton skeleton-text"></div></td></tr>`);
            }
        }

        $('#f_daterange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' } });
        $('#f_daterange').on('apply.daterangepicker', function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD')); $('#f_start').val(picker.startDate.format('YYYY-MM-DD')); $('#f_end').val(picker.endDate.format('YYYY-MM-DD')); fetchWarehouseStock(); });
        $('#f_daterange').on('cancel.daterangepicker', function(ev, picker) { $(this).val(''); $('#f_start').val(''); $('#f_end').val(''); fetchWarehouseStock(); });

        function formatCompactNumber(number) {
            if (number < 1000) {
                return number.toLocaleString('id-ID');
            } else if (number >= 1000 && number < 1000000) {
                return (number / 1000).toFixed(1).replace(/\.0$/, '') + ' Ribu';
            } else if (number >= 1000000 && number < 1000000000) {
                return (number / 1000000).toFixed(1).replace(/\.0$/, '') + ' Juta';
            } else if (number >= 1000000000 && number < 1000000000000) {
                return (number / 1000000000).toFixed(1).replace(/\.0$/, '') + ' Milyar';
            } else if (number >= 1000000000000) {
                return (number / 1000000000000).toFixed(1).replace(/\.0$/, '') + ' Triliun';
            }
            return number.toLocaleString('id-ID');
        }

        window.fetchWarehouseStock = async function(page = 1) {
            const tbody = document.getElementById('warehouseTableBody');
            if(!tbody) return;

            const params = new URLSearchParams(new FormData(document.getElementById('formFilter')));
            params.set('page', page); 
            params.set('limit', 10);

            try {
                const res = await fetch(`../api/get_warehouse_stock_summary.php?${params.toString()}&_nocache=${Date.now()}`, {
                    cache: 'no-store',
                    headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
                });
                const response = await res.json();

                // Update KPI Stats if available
                                                if (response.stats) {
                                                    document.getElementById('kpi-batch').innerText = formatCompactNumber(response.stats.total_batch);
                                                    document.getElementById('kpi-batch').title = response.stats.total_batch.toLocaleString('id-ID');

                                                    document.getElementById('kpi-qty').innerText = formatCompactNumber(response.stats.total_verified);
                                                    document.getElementById('kpi-qty').title = response.stats.total_verified.toLocaleString('id-ID');

                                                    document.getElementById('kpi-shipped').innerText = formatCompactNumber(response.stats.total_shipped);
                                                    document.getElementById('kpi-shipped').title = response.stats.total_shipped.toLocaleString('id-ID');

                                                    document.getElementById('kpi-verified').innerText = formatCompactNumber(response.stats.total_stok);
                                                    document.getElementById('kpi-verified').title = response.stats.total_stok.toLocaleString('id-ID');

                                                    if(response.stats.bulan) {
                                                        document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = response.stats.bulan);
                                                    }
                                                }

                if (response.data) {
                    window.latestData = response.data;
                    displayWarehouseData(response.data);
                    setupPagination(response.pages, response.total, page);
                    window.currentPage = page;
                    applyColumnVisibility();
                }
            } catch (e) { console.error("WH AJAX Error:", e); }
        }
        window.selectSuperFilter = (item, size, displayLabel) => {
            document.getElementById('f_item_val').value = item;
            document.getElementById('f_size_val').value = size;
            let label = item || 'Semua Item';
            if(size) label = `${item} (${displayLabel})`;
            const labelEl = document.getElementById('sf-label');
            labelEl.innerText = label;
            if (item) labelEl.classList.remove('text-muted');
            else labelEl.classList.add('text-muted');
            
            const dropdownEl = labelEl.closest('.dropdown');
            if(dropdownEl && dropdownEl.classList.contains('show')) dropdownEl.querySelector('[data-bs-toggle="dropdown"]').click();
            fetchWarehouseStock(1);
        };

        window.selectMesinFilter = (machine, shift, displayLabel) => {
            document.getElementById('f_machine_val').value = machine;
            document.getElementById('f_shift_val').value = shift || '';
            let label = machine || 'Semua Mesin';
            if(shift) label = `${machine} (${displayLabel})`;
            const labelEl = document.getElementById('sf-mesin-label');
            labelEl.innerText = label;
            if (machine) labelEl.classList.remove('text-muted');
            else labelEl.classList.add('text-muted');
            
            const dropdownEl = labelEl.closest('.dropdown');
            if(dropdownEl && dropdownEl.classList.contains('show')) dropdownEl.querySelector('[data-bs-toggle="dropdown"]').click();
            fetchWarehouseStock(1);
        };

        window.selectInputMethodFilter = (inputMethod, label) => {
            document.getElementById('f_input_method').value = inputMethod;
            const labelEl = document.getElementById('sf-input-label');
            labelEl.innerText = label || 'Semua Metode';
            if (inputMethod) labelEl.classList.remove('text-muted');
            else labelEl.classList.add('text-muted');

            const dropdownEl = labelEl.closest('.dropdown');
            if (dropdownEl && dropdownEl.classList.contains('show')) dropdownEl.querySelector('[data-bs-toggle="dropdown"]').click();
            fetchWarehouseStock(1);
        };

        window.resetFilter = () => { document.getElementById('formFilter').reset(); $('#f_daterange').val(''); $('#f_start').val(''); $('#f_end').val(''); selectSuperFilter('', '', ''); selectMesinFilter('', '', ''); selectInputMethodFilter('', 'Semua Metode'); }
        document.getElementById('f_search').oninput = () => { clearTimeout(window.sT); window.sT = setTimeout(() => { fetchWarehouseStock(1); }, 500); }
        $(document).on('change', '.auto-filter', () => { fetchWarehouseStock(1); });

        // Column Toggle Logic
        $(document).on('change', '.col-checkbox', function() {
            const colClass = $(this).val();
            window.columnStates[colClass] = $(this).is(':checked');
            applyColumnVisibility();
        });

        function applyColumnVisibility() {
            for (const [colClass, show] of Object.entries(window.columnStates)) {
                $(`th.${colClass}, td.${colClass}`).toggleClass('col-hidden', !show);
            }
        }

        // Auto-Polling: Refresh Live setiap 3 detik (Full AJAX)
        setInterval(() => {
            if (!document.hidden) fetchWarehouseStock(window.currentPage);
        }, 3000);

        function displayWarehouseData(data) {
            const tbody = document.getElementById('warehouseTableBody');
            if (!data.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="py-4">
                                <i class="fa fa-boxes fa-4x text-light mb-3"></i>
                                <h5 class="text-muted">Gudang Kosong atau Tidak Ada Data.</h5>
                                <p class="small text-muted">Gunakan menu QC Checker untuk mengirim barang ke gudang.</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }
            tbody.innerHTML = '';
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            
            data.forEach((row, index) => {
                const verified = parseInt(row.total_in_warehouse || 0);
                const shipped = parseInt(row.total_shipped_labels || 0);
                const stock = verified - shipped;
                const limit = parseInt(row.copies || 0);
                const inputMeta = getWarehouseInputMeta(row.batch_input_method);
                
                let dateFormatted = '-- ---';
                if(row.last_entry) { 
                    const d = new Date(row.last_entry); 
                    dateFormatted = `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`; 
                }

                tbody.insertAdjacentHTML('beforeend', `
                    <tr onclick="showRowActions(${index})">
                        <td class="ps-4 text-center col-batch ${window.columnStates['col-batch'] ? '' : 'col-hidden'}">
                            <span class="badge bg-primary text-white batch-badge mb-1">${row.batch}</span>
                            <div class="text-black small" style="font-weight:400;">${dateFormatted} | ${row.last_entry_time} WITA</div>
                        </td>
                        <td class="col-item ${window.columnStates['col-item'] ? '' : 'col-hidden'}"><div class="text-black font-w700">${row.item}</div><small class="text-black">${row.size} ${row.unit}</small></td>
                        <td class="col-qty text-center ${window.columnStates['col-qty'] ? '' : 'col-hidden'}">
                            ${parseInt(row.copies) > 0 ? `<span class="badge badge-light border text-black shadow-sm font-w700" style="font-size:12px;">${row.copies} Dus</span>` : `<span class="badge badge-danger light font-w800" style="font-size:10px;">BELUM ADA</span>`}
                        </td>
                        <td class="col-mesin ${window.columnStates['col-mesin'] ? '' : 'col-hidden'}"><span class="text-black font-w600">${row.machine || '-'}</span><br><small>${row.shift || '-'}</small></td>
                        <td class="col-method text-center ${window.columnStates['col-method'] ? '' : 'col-hidden'}">
                            <span class="warehouse-method-badge ${inputMeta.key}"><i class="fa ${inputMeta.icon}"></i>${inputMeta.label}</span>
                        </td>
                        <td class="col-progres text-center ${window.columnStates['col-progres'] ? '' : 'col-hidden'}">
                            <span class="text-black font-w800">${verified}</span>
                        </td>
                        <td class="col-shipped text-center ${window.columnStates['col-shipped'] ? '' : 'col-hidden'}">
                            <span class="text-success font-w800">${shipped}</span>
                        </td>
                        <td class="col-pending text-center ${window.columnStates['col-pending'] ? '' : 'col-hidden'}">
                            ${stock > 0 ? `<span class="text-primary font-w800">${stock}</span> <small class="text-muted">Dus</small>` : `<span class="badge badge-danger text-white" style="font-size:11px; font-weight:800; padding: 6px 12px; border-radius: 6px;">KOSONG</span>`}
                        </td>
                        <td class="col-qc ${window.columnStates['col-qc'] ? '' : 'col-hidden'}"><div class="small text-black font-w600">${row.pengirim || '-'}</div></td>
                    </tr>
                `);
            });
        }

        window.showRowActions = function(index) {
            const row = window.latestData[index];
            Swal.fire({
                html: `
                    <div class="swal2-close-custom" onclick="Swal.close()"><i class="fa fa-times"></i></div>
                    <div class="text-center mb-3">
                        <small class="text-muted d-block mb-1">Batch ID</small>
                        <strong class="text-black" style="word-break:break-all;">${row.batch}</strong>
                    </div>
                    <div class="action-list">
                        <button onclick="Swal.close(); viewStockMap(window.latestData[${index}])" class="action-item"><i class="fa fa-th icon-view"></i> Lihat Peta Unit</button>
                        <button onclick="Swal.close(); clearBatch(${row.production_id}, '${row.batch}')" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Kosongkan Batch</button>
                    </div>
                `,
                showConfirmButton: false, padding: '1.2rem', width: '320px', borderRadius: '15px'
            });
        };

        window.viewStockMap = async function(row) {
            const modalEl = document.getElementById('modalWarehouseMap');
            const old = bootstrap.Modal.getInstance(modalEl); if(old) old.dispose();
            document.getElementById('v-item').innerText = row.item;
            document.getElementById('v-batch').innerText = "Batch ID: " + row.batch;
            const grid = document.getElementById('v-grid');
            grid.innerHTML = '<div class="text-center w-100 py-4">Memuat peta...</div>';
            new bootstrap.Modal(modalEl).show();
            try {
                const res = await fetch(`../api/get_warehouse_details.php?prod_id=${row.production_id}`);
                const list = await res.json();
                grid.innerHTML = '';
                for (let i = 1; i <= row.copies; i++) {
                    const seat = document.createElement('div');
                    seat.innerText = i; seat.className = 'seat-warehouse';
                    if (list.includes(i)) seat.classList.add('in-stock');
                    // Tidak ada event listener klik/hover, sehingga murni View-Only
                    grid.appendChild(seat);
                }
            } catch (e) { grid.innerHTML = '<div class="text-danger w-100 text-center">Gagal memuat peta.</div>'; }
        }

        window.clearBatch = function(id, batch) {
            const row = window.latestData.find(r => r.production_id == id);
            const shippedCount = row ? parseInt(row.total_shipped_labels || 0) : 0;
            const hasShipped = shippedCount > 0;

            const config = {
                title: hasShipped ? '⚠️ Perhatian: Stok Terkirim!' : 'Kosongkan Batch?',
                text: hasShipped 
                    ? `Batch #${batch} memiliki ${shippedCount} dus yang SUDAH TERKIRIM. Mengosongkan batch ini akan menghapus riwayat stok di gudang namun TETAP meninggalkan data pengiriman yang mungkin menjadi tidak sinkron. Lanjutkan?` 
                    : `Hapus seluruh unit batch #${batch} dari stok gudang?`,
                icon: hasShipped ? 'error' : 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D50000',
                confirmButtonText: hasShipped ? 'Ya, Tetap Kosongkan' : 'Ya, Hapus',
                cancelButtonText: 'Batal'
            };

            Swal.fire(config).then(async (res) => {
                if(res.isConfirmed) {
                    if (hasShipped) {
                        const { value: confirm } = await Swal.fire({
                            title: 'Konfirmasi Keamanan',
                            text: 'Ketik "HAPUS" untuk mengosongkan stok batch yang sudah ada pengirimannya:',
                            input: 'text',
                            inputPlaceholder: 'Ketik HAPUS...',
                            showCancelButton: true,
                            confirmButtonColor: '#D50000',
                            inputValidator: (value) => {
                                if (!value || value.toUpperCase() !== 'HAPUS') return 'Anda harus mengetik HAPUS untuk melanjutkan!';
                            }
                        });
                        if (!confirm) return;
                    }

                    const f = new FormData(); f.append('id', id);
                    const r = await fetch(`../api/manage_settings.php?action=delete&type=warehouse_batch`, { method: 'POST', body: f });
                    const response = await r.json();
                    if(response.status === 'success') { 
                        toastr.success('Stok Batch Berhasil Dikosongkan'); 
                        fetchWarehouseStock(window.currentPage); 
                    } else {
                        toastr.error(response.message || 'Gagal menghapus data');
                    }
                }
            });
        }

        function setupPagination(totalP, totalD, current) {
            const controls = document.getElementById('paginationControls');
            document.getElementById('paginationInfo').innerText = `Data ${(current-1)*10 + 1}-${Math.min(current*10, totalD)} dari ${totalD}`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchWarehouseStock(${current-1})"><i class="fas fa-chevron-left"></i></a></li>`);
            for (let i = 1; i <= totalP; i++) { if (i == 1 || i == totalP || (i >= current-1 && i <= current+1)) controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchWarehouseStock(${i})">${i}</a></li>`); else if (i == current - 2 || i == current + 2) controls.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><a class="page-link">...</a></li>`); }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchWarehouseStock(${current+1})"><i class="fas fa-chevron-right"></i></a></li>`);
        }

        fetchWarehouseStock(1);
        document.querySelector('a[href="warehouse_inventory.php"]')?.closest('li')?.classList.add('mm-active');

        // ================= TAMBAH STOK GUDANG =================
        const hierarchyData = <?= json_encode($hierarchy_data) ?>;

        document.getElementById('as_item').addEventListener('change', function() {
            const item       = this.value;
            const sizeSel    = document.getElementById('as_size');
            const unitInp    = document.getElementById('as_unit');
            const machineSel = document.getElementById('as_machine');
            sizeSel.innerHTML = '<option value="">-- Pilih Size --</option>';
            unitInp.value = '';
            if (item && hierarchyData[item]) {
                unitInp.value = hierarchyData[item].unit || '';
                (hierarchyData[item].sizes || []).forEach(s => {
                    sizeSel.insertAdjacentHTML('beforeend', `<option value="${s}">${s}</option>`);
                });

                // Auto-select mesin default (masih bisa di-override manual)
                const defMachine = hierarchyData[item].default_machine;
                if (defMachine) {
                    const opt = Array.from(machineSel.options).find(o => o.value === defMachine);
                    if (opt) {
                        machineSel.value = defMachine;
                        machineSel.classList.add('is-valid');
                        setTimeout(() => machineSel.classList.remove('is-valid'), 1500);
                    }
                }
            }
        });

        window.autoGenerateBatch = function() {
            const item     = document.getElementById('as_item').value;
            const size     = document.getElementById('as_size').value;
            const unit     = document.getElementById('as_unit').value;
            const machine  = document.getElementById('as_machine').value;
            const shift    = document.getElementById('as_shift').value;
            const operator = document.getElementById('as_operator').value || 'ADMIN';
            const quantity = document.getElementById('as_quantity').value;
            const dateVal  = document.getElementById('as_date').value;
            if (!item || !size || !machine || !shift || !quantity || !dateVal) {
                toastr.warning('Lengkapi item, size, mesin, shift, qty, tanggal dulu');
                return;
            }
            const d = new Date(dateVal);
            const dd = String(d.getDate()).padStart(2,'0');
            const mm = String(d.getMonth()+1).padStart(2,'0');
            const yy = String(d.getFullYear()).slice(-2);
            const shiftCode = (machine.match(/\d+/) || ['01'])[0] + (shift.match(/[A-Z]$/i) || ['A'])[0].toUpperCase();
            const itemPrefix = item.substring(0,3).toUpperCase();
            const opCode = operator.substring(0, 4).toUpperCase().replace(/\s+/g,'');
            const batch = `${dd}${mm}${yy}-${shiftCode}-${itemPrefix}-${quantity}-${opCode}-${size}${unit}`;
            document.getElementById('as_batch').value = batch;
        };

        document.getElementById('formAddStock').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitAddStock');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Menyimpan...';

            const fd = new FormData();
            fd.append('batch',           document.getElementById('as_batch').value.trim());
            fd.append('item',            document.getElementById('as_item').value);
            fd.append('size',            document.getElementById('as_size').value);
            fd.append('unit',            document.getElementById('as_unit').value);
            fd.append('machine',         document.getElementById('as_machine').value);
            fd.append('shift',           document.getElementById('as_shift').value);
            fd.append('quantity',        document.getElementById('as_quantity').value);
            fd.append('operator',        document.getElementById('as_operator').value);
            fd.append('qc',              document.getElementById('as_qc').value);
            fd.append('production_date', document.getElementById('as_date').value);
            fd.append('production_time', document.getElementById('as_time').value);
            fd.append('copies',          document.getElementById('as_copies').value);

            try {
                const r = await fetch('../api/manage_settings.php?action=add_warehouse_stock', { method: 'POST', body: fd });
                const res = await r.json();
                if (res.status === 'success') {
                    toastr.success(`Stok masuk gudang: ${res.copies} dus (label #${res.first_label_no}–${res.last_label_no})`);
                    bootstrap.Modal.getInstance(document.getElementById('modalAddStock')).hide();
                    document.getElementById('formAddStock').reset();
                    document.getElementById('as_date').value = new Date().toISOString().slice(0,10);
                    fetchWarehouseStock(1);
                } else {
                    toastr.error(res.message || 'Gagal menyimpan');
                }
            } catch (err) {
                toastr.error('Koneksi error: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save me-1"></i> Simpan & Masuk Gudang';
            }
        });

        // ================= RIWAYAT PEMBATALAN LABEL =================
        window.historyState = { page: 1, initialized: false };

        $('#h_daterange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' } });
        $('#h_daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            $('#h_start').val(picker.startDate.format('YYYY-MM-DD'));
            $('#h_end').val(picker.endDate.format('YYYY-MM-DD'));
            fetchCancelledHistory(1);
        });
        $('#h_daterange').on('cancel.daterangepicker', function() {
            $(this).val(''); $('#h_start').val(''); $('#h_end').val(''); fetchCancelledHistory(1);
        });

        window.selectHistoryItemFilter = (item, size, displayLabel) => {
            document.getElementById('h_item_val').value = item;
            document.getElementById('h_size_val').value = size;
            const labelEl = document.getElementById('hf-item-label');
            labelEl.innerText = item ? (size ? `${item} (${displayLabel})` : item) : 'Semua Item';
            labelEl.classList.toggle('text-muted', !item);
            const dropdownEl = labelEl.closest('.dropdown');
            if (dropdownEl && dropdownEl.classList.contains('show')) dropdownEl.querySelector('[data-bs-toggle="dropdown"]').click();
            fetchCancelledHistory(1);
        };

        window.resetHistoryFilter = () => {
            document.getElementById('formHistoryFilter').reset();
            $('#h_daterange').val(''); $('#h_start').val(''); $('#h_end').val('');
            selectHistoryItemFilter('', '', '');
        };

        function debounceHistory(fn, delay = 450) {
            return () => { clearTimeout(window.hT); window.hT = setTimeout(fn, delay); };
        }
        ['h_search', 'h_batch', 'h_device'].forEach(id => {
            document.getElementById(id).addEventListener('input', debounceHistory(() => fetchCancelledHistory(1)));
        });
        document.getElementById('h_category').addEventListener('change', () => fetchCancelledHistory(1));

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        window.fetchCancelledHistory = async function(page = 1) {
            const tbody = document.getElementById('historyTableBody');
            if (!tbody) return;
            const params = new URLSearchParams(new FormData(document.getElementById('formHistoryFilter')));
            params.set('page', page);
            params.set('limit', 10);
            try {
                const res = await fetch(`../api/get_cancelled_labels_history.php?${params.toString()}&_nocache=${Date.now()}`, {
                    cache: 'no-store',
                    headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
                });
                const r = await res.json();
                document.getElementById('hist-cnt-prod').innerText = (r.stats?.cnt_production ?? 0).toLocaleString('id-ID');
                document.getElementById('hist-cnt-wh').innerText   = (r.stats?.cnt_warehouse ?? 0).toLocaleString('id-ID');
                document.getElementById('hist-cnt-dev').innerText  = (r.stats?.cnt_devices ?? 0).toLocaleString('id-ID');
                renderHistoryRows(r.data || []);
                setupHistoryPagination(r.pages || 0, r.total || 0, page);
                window.historyState.page = page;
            } catch (e) { console.error('History AJAX Error:', e); }
        };

        function renderHistoryRows(data) {
            const tbody = document.getElementById('historyTableBody');
            if (!data.length) {
                tbody.innerHTML = `
                    <tr><td colspan="8" class="text-center py-5">
                        <div class="py-4">
                            <i class="fa fa-ban fa-3x text-light mb-3"></i>
                            <h5 class="text-muted">Belum ada riwayat pembatalan label.</h5>
                        </div>
                    </td></tr>`;
                return;
            }
            const monthNames = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
            tbody.innerHTML = '';
            data.forEach(row => {
                const d = row.cancelled_at ? new Date(row.cancelled_at.replace(' ', 'T')) : null;
                const dateFmt = d ? `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}` : '-';
                const cat = (row.category || '').toLowerCase();
                const deviceHtml = row.device_id
                    ? `<span class="device-chip" title="${escapeHtml(row.device_id)}">${escapeHtml(row.device_name || row.device_id)}</span>`
                    : '<span class="text-muted small">—</span>';
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td class="ps-4">
                            <div class="text-black font-w700 small">${dateFmt}</div>
                            <small class="text-muted">${row.cancelled_time || ''} WITA</small>
                        </td>
                        <td><span class="badge bg-primary text-white batch-badge">${escapeHtml(row.batch || '-')}</span></td>
                        <td>
                            <div class="text-black font-w700">${escapeHtml(row.item || '-')}</div>
                            <small class="text-black">${escapeHtml(row.size || '')} ${escapeHtml(row.unit || '')}</small>
                        </td>
                        <td class="text-center"><span class="badge badge-light border text-black font-w700">#${row.label_no}</span></td>
                        <td class="text-center"><span class="cancel-category-badge ${cat}">${escapeHtml(row.category || '-')}</span></td>
                        <td>${deviceHtml}</td>
                        <td><div class="small text-black font-w600">${escapeHtml(row.cancelled_by || '-')}</div></td>
                        <td><div class="small text-muted">${escapeHtml(row.reason || '-')}</div></td>
                    </tr>
                `);
            });
        }

        function setupHistoryPagination(totalP, totalD, current) {
            const controls = document.getElementById('historyPaginationControls');
            document.getElementById('historyPaginationInfo').innerText =
                totalD ? `Data ${(current-1)*10 + 1}-${Math.min(current*10, totalD)} dari ${totalD}` : '';
            controls.innerHTML = '';
            if (!totalP) return;
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchCancelledHistory(${current-1})"><i class="fas fa-chevron-left"></i></a></li>`);
            for (let i = 1; i <= totalP; i++) {
                if (i == 1 || i == totalP || (i >= current-1 && i <= current+1)) {
                    controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchCancelledHistory(${i})">${i}</a></li>`);
                } else if (i == current - 2 || i == current + 2) {
                    controls.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><a class="page-link">...</a></li>`);
                }
            }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchCancelledHistory(${current+1})"><i class="fas fa-chevron-right"></i></a></li>`);
        }

        document.getElementById('tab-history-btn').addEventListener('shown.bs.tab', () => {
            if (!window.historyState.initialized) {
                window.historyState.initialized = true;
                fetchCancelledHistory(1);
            }
        });
    </script>
</body>
</html>
