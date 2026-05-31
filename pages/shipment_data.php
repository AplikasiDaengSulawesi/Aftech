<!DOCTYPE html>
<html lang="en">
<?php
include '../includes/header.php';
require_once '../includes/db.php';

$items_query = $pdo->query("
    SELECT i.id, i.name, u.name as unit_name 
    FROM master_items i 
    LEFT JOIN master_units u ON i.unit_id = u.id 
    ORDER BY i.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$hierarchy_data = [];
foreach($items_query as $it) {
    $sizes = $pdo->prepare("SELECT size_value FROM master_sizes WHERE item_id = ? ORDER BY CAST(size_value AS UNSIGNED) ASC");
    $sizes->execute([$it['id']]);
    
    $hierarchy_data[$it['name']] = [
        'unit' => $it['unit_name'],
        'sizes' => $sizes->fetchAll(PDO::FETCH_COLUMN)
    ];
}
?>
<style>
    .pagination-xs .page-link { padding: 5px 10px; font-size: 12px; }
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

    /* ACTION MENU & DETAIL LIST */
    .action-list { padding: 0; margin: 0; }
    .action-item {
        display: flex; align-items: center; padding: 12px 15px; color: #333; font-weight: 600; font-size: 14px;
        cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f1f1f1; width: 100%; background: none; border-left: none; border-right: none; border-top: none;
        text-align: left; text-decoration: none;
    }
    .action-item:last-child { border-bottom: none; }
    .action-item:hover { background: #f8f9ff; color: var(--af-primary); }
    .action-item i { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 12px; font-size: 14px; }
    .icon-view { background: #e8eaf6; color: #1A237E; }
    .icon-delete { background: #ffebee; color: #d32f2f; }
    .icon-append { background: #fff8e1; color: #ffa000; }
    .icon-print { background: #e3f2fd; color: #1976d2; }
    
    .swal2-close-custom {
        position: absolute; top: 12px; right: 12px; background: #f5f5f5; border-radius: 50%; width: 26px; height: 26px;
        display: flex; align-items: center; justify-content: center; cursor: pointer; color: #999; font-size: 12px;
    }

    .card-kpi { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .card-kpi .card-body { padding: 1.5rem; }
    .kpi-title-month { font-size: 11px; opacity: 0.9; font-weight: 500; color: #fff !important; }

    /* TABS STYLE (selaras warehouse_inventory) */
    .sh-tabs { display: flex; gap: 8px; border: none; margin-bottom: 18px; flex-wrap: wrap; }
    .sh-tabs .nav-link {
        background: #fff; color: #1A237E; border: 1px solid #e8eaf6; border-radius: 12px;
        font-weight: 700; padding: 10px 18px; font-size: 13px; transition: 0.2s;
    }
    .sh-tabs .nav-link:hover { color: #1A237E; border-color: #c5cae9; }
    .sh-tabs .nav-link.active { background: #1A237E; color: #fff; border-color: #1A237E; }
    .sh-tabs .nav-link i { margin-right: 6px; }

    /* LABEL CHIP UNTUK MODAL DETAIL RETURN */
    .label-chip-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(56px, 1fr));
        gap: 6px; padding: 12px; background: #f8f9fa; border-radius: 10px;
        max-height: 220px; overflow-y: auto; user-select: none;
    }
    .label-chip {
        height: 34px; display: flex; align-items: center; justify-content: center;
        background: #fff; border: 1px solid #c5cae9; border-radius: 6px;
        font-size: 11px; font-weight: 700; color: #1A237E; cursor: pointer; transition: 0.1s;
    }
    .label-chip:hover { background: #e8eaf6; }
    .label-chip.selected { background: #D50000; color: #fff; border-color: #D50000; transform: scale(0.94); }
    .label-chip.returned { background: #eee; color: #aaa; border-color: #ddd; cursor: not-allowed; }
    .label-chip.returned.rusak { background: #ffebee; color: #c62828; border-color: #ef9a9a; }
    .label-chip.returned.utuh { background: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; }

    /* SWAL CONDITION PICKER (utuh/rusak card-radio) */
    .cond-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 6px; }
    .cond-card {
        border: 2px solid #e0e0e0; border-radius: 12px; padding: 14px 10px; cursor: pointer;
        transition: 0.15s; text-align: center; background: #fff; user-select: none;
    }
    .cond-card i { font-size: 22px; display: block; margin-bottom: 6px; }
    .cond-card .cond-title { font-weight: 800; font-size: 13px; }
    .cond-card .cond-sub   { font-size: 10.5px; color: #888; line-height: 1.3; margin-top: 3px; }
    .cond-card:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.06); }
    .cond-card.cond-utuh.active  { border-color: #2e7d32; background: #e8f5e9; }
    .cond-card.cond-utuh.active i, .cond-card.cond-utuh.active .cond-title { color: #2e7d32; }
    .cond-card.cond-rusak.active { border-color: #c62828; background: #ffebee; }
    .cond-card.cond-rusak.active i, .cond-card.cond-rusak.active .cond-title { color: #c62828; }
    .ret-reason-input {
        width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;
        font-size: 13px; margin-top: 4px;
    }
    .ret-reason-input:focus { outline: none; border-color: #1A237E; box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }

    /* CHIPS LABEL DI TABEL RIWAYAT RETURN */
    .return-label-chips { display: flex; flex-wrap: wrap; gap: 4px; max-width: 260px; }
    .return-label-chip {
        display: inline-block; padding: 2px 7px; border-radius: 4px;
        font-size: 10.5px; font-weight: 700; line-height: 1.5;
        background: #e8eaf6; color: #1A237E; border: 1px solid #c5cae9;
    }
    .return-label-chip.rusak { background: #ffebee; color: #c62828; border-color: #ef9a9a; }
    .return-label-chip.utuh  { background: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; }

    /* MINI KPI STRIP UNTUK TAB RETURN (mengikuti filter) */
    .ret-kpi-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 18px;
    }
    @media (min-width: 576px) { .ret-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 992px) { .ret-kpi-grid { grid-template-columns: repeat(6, 1fr); } }
    .ret-kpi-card {
        background: #fff; border: 1px solid #eef0f7; border-radius: 12px; padding: 12px 14px;
        display: flex; align-items: center; gap: 10px;
        box-shadow: 0 2px 6px rgba(26,35,126,0.04); transition: 0.18s;
    }
    .ret-kpi-card:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(26,35,126,0.08); }
    .ret-kpi-icon {
        width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }
    .ret-kpi-icon.bg-utuh   { background: #e8f5e9; color: #2e7d32; }
    .ret-kpi-icon.bg-rusak  { background: #ffebee; color: #c62828; }
    .ret-kpi-icon.bg-event  { background: #fff3e0; color: #ef6c00; }
    .ret-kpi-icon.bg-nota   { background: #e3f2fd; color: #1565c0; }
    .ret-kpi-icon.bg-batch  { background: #e8eaf6; color: #1A237E; }
    .ret-kpi-icon.bg-cust   { background: #f3e5f5; color: #6a1b9a; }
    .ret-kpi-icon.bg-unit   { background: #ede7f6; color: #4527a0; }
    .ret-kpi-text { display: flex; flex-direction: column; min-width: 0; }
    .ret-kpi-text .ret-kpi-val { font-weight: 800; font-size: 18px; color: #1a1a1a; line-height: 1; }
    .ret-kpi-text .ret-kpi-lbl { font-size: 10.5px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 3px; }
    .ret-kpi-text .ret-kpi-sub { font-size: 10px; color: #aaa; margin-top: 2px; }

    .ret-last-bar {
        display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f8f9fb;
        border: 1px dashed #d9dbe8; border-radius: 10px; font-size: 11.5px; color: #555;
        margin-bottom: 14px;
    }
    .ret-last-bar i { color: #1A237E; }
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
                                    <span class="me-3"><i class="fa fa-truck"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Pengiriman</p>
                                        <h3 class="text-white mb-0" id="kpi-pengiriman">0</h3>
                                        <small class="d-block mt-1">Surat Jalan</small>
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
                                    <span class="me-3"><i class="fa fa-box-open"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Dus Terkirim</p>
                                        <h3 class="text-white mb-0" id="kpi-unit">0</h3>
                                        <small class="d-block mt-1">Dus Produk</small>
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
                                    <span class="me-3"><i class="fa fa-users"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Customer</p>
                                        <h3 class="text-white mb-0" id="kpi-customer">0</h3>
                                        <small class="d-block mt-1">Retailer/Agen</small>
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
                                    <span class="me-3 text-white"><i class="fa fa-sync-alt"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Repeat Order</p>
                                        <h3 class="text-white mb-0" id="kpi-repeat">0</h3>
                                        <small class="d-block mt-1">Loyalitas</small>
                                        <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABS NAV -->
                <ul class="nav sh-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-shipment-btn" data-bs-toggle="tab" data-bs-target="#tabShipment" type="button" role="tab" aria-controls="tabShipment" aria-selected="true">
                            <i class="fa fa-truck"></i>Riwayat Pengiriman
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-return-btn" data-bs-toggle="tab" data-bs-target="#tabReturn" type="button" role="tab" aria-controls="tabReturn" aria-selected="false">
                            <i class="fa fa-undo"></i>Riwayat Return
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                <div class="tab-pane fade show active" id="tabShipment" role="tabpanel" aria-labelledby="tab-shipment-btn">

                <!-- Filter Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div>
                                        <h4 class="text-black mb-0 font-w800">Database Pengiriman Distributor</h4>
                                        <p class="mb-0 small text-muted">Klik baris untuk opsi cetak nota atau pembatalan</p>
                                    </div>
                                    <div class="header-btn-group">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-xs shadow-sm dropdown-toggle font-w600 w-100" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-columns me-1 text-primary"></i> Pilih Kolom
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end column-toggle-dropdown p-3 shadow-lg">
                                                <h6 class="dropdown-header ps-0 mb-2 font-w700 text-black">Tampilkan:</h6>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-time" id="chk-time" checked disabled><label class="form-check-label small font-w600" for="chk-time">Waktu Pengiriman</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-customer" id="chk-customer" checked><label class="form-check-label small font-w600" for="chk-customer">Customer</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-items" id="chk-items" checked><label class="form-check-label small font-w600" for="chk-items">Item & Ukuran</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-total" id="chk-total" checked><label class="form-check-label small font-w600" for="chk-total">Total Dus</label></div>
                                                <div class="form-check"><input class="form-check-input col-checkbox" type="checkbox" value="col-officer" id="chk-officer" checked><label class="form-check-label small font-w600" for="chk-officer">Dikirim Oleh</label></div>
                                            </div>
                                        </div>
                                        <button onclick="resetFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600"><i class="fa fa-undo me-1"></i> Reset Filter</button>
                                    </div>
                                </div>
                                <form id="formFilter" class="row g-2">
                                    <!-- 1. VIEW MODE TOGGLE (FIRST) -->
                                    <div class="col-12 col-md-3">
                                        <div class="btn-group btn-group-sm shadow-sm w-100 h-100" role="group">
                                            <input type="radio" class="btn-check" name="view_mode" id="mode_customer" value="customer" checked onchange="fetchShipments(1)">
                                            <label class="btn btn-outline-primary font-w600 d-flex align-items-center justify-content-center" for="mode_customer"><i class="fa fa-user me-2"></i> Customer</label>
                                            
                                            <input type="radio" class="btn-check" name="view_mode" id="mode_batch" value="batch" onchange="fetchShipments(1)">
                                            <label class="btn btn-outline-primary font-w600 d-flex align-items-center justify-content-center" for="mode_batch"><i class="fa fa-boxes me-2"></i> Batch</label>
                                        </div>
                                    </div>

                                    <!-- 2. SEARCH BAR -->
                                    <div class="col-12 col-md-3">
                                        <input type="text" id="f_search" name="search" class="form-control form-control-sm h-100" placeholder="Cari data...">
                                    </div>

                                    <!-- 3. NATIVE BOOTSTRAP SUPER FILTER ITEM -->
                                    <div class="col-12 col-md-3">
                                        <div class="dropdown w-100 h-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start h-100" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
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

                                    <!-- 4. DATE RANGE -->
                                    <div class="col-12 col-md-3">
                                        <input type="text" id="f_daterange" class="form-control form-control-sm daterange-picker h-100" placeholder="Pilih Tanggal" readonly>
                                        <input type="hidden" name="start_date" id="f_start">
                                        <input type="hidden" name="end_date" id="f_end">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Data Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table shadow-hover mb-0">
                                        <thead class="bg-light" id="tableShipmentHeader">
                                            <!-- Dynamic Header -->
                                        </thead>
                                        <tbody id="tableShipmentBody">
                                            <!-- Dynamic via AJAX -->
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
                </div> <!-- /tabShipment -->

                <!-- ============== TAB: RIWAYAT RETURN ============== -->
                <div class="tab-pane fade" id="tabReturn" role="tabpanel" aria-labelledby="tab-return-btn">
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                                <div class="card-body p-3 p-md-4">
                                    <div class="filter-card-header">
                                        <div>
                                            <h4 class="text-black mb-0 font-w800">Riwayat Return Pengiriman</h4>
                                            <p class="mb-0 small text-muted">Label yang batal dikirim. Status <b>Utuh</b> kembali ke gudang, status <b>Rusak</b> hanya dicatat sebagai return.</p>
                                        </div>
                                        <div class="header-btn-group">
                                            <button onclick="resetReturnFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600">
                                                <i class="fa fa-undo me-1"></i> Reset Filter
                                            </button>
                                        </div>
                                    </div>
                                    <form id="formReturnFilter" class="row g-2">
                                        <div class="col-12 col-md-3">
                                            <input type="text" id="r_search" name="search" class="form-control form-control-sm" placeholder="Cari batch / item / customer / alasan...">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <input type="text" id="r_batch" name="batch" class="form-control form-control-sm" placeholder="Filter Batch">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <select id="r_condition" name="condition" class="form-control form-control-sm">
                                                <option value="">Semua Kondisi</option>
                                                <option value="utuh">Utuh</option>
                                                <option value="rusak">Rusak</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <input type="text" id="r_daterange" class="form-control form-control-sm" placeholder="Rentang Tanggal Return" readonly>
                                            <input type="hidden" name="start_date" id="r_start">
                                            <input type="hidden" name="end_date" id="r_end">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- KPI STRIP (ikut filter) -->
                    <div class="row">
                        <div class="col-12">
                            <div class="ret-kpi-grid" id="returnKpiGrid">
                                <div class="ret-kpi-card">
                                    <div class="ret-kpi-icon bg-event"><i class="fa fa-undo"></i></div>
                                    <div class="ret-kpi-text">
                                        <span class="ret-kpi-val" id="ret-kpi-event">0</span>
                                        <span class="ret-kpi-lbl">Event Return</span>
                                        <span class="ret-kpi-sub" id="ret-kpi-last">Belum ada</span>
                                    </div>
                                </div>
                                <div class="ret-kpi-card">
                                    <div class="ret-kpi-icon bg-utuh"><i class="fa fa-check-circle"></i></div>
                                    <div class="ret-kpi-text">
                                        <span class="ret-kpi-val" id="ret-kpi-utuh">0</span>
                                        <span class="ret-kpi-lbl">Dus Utuh</span>
                                        <span class="ret-kpi-sub" id="ret-kpi-unit-utuh">0 unit</span>
                                    </div>
                                </div>
                                <div class="ret-kpi-card">
                                    <div class="ret-kpi-icon bg-rusak"><i class="fa fa-exclamation-triangle"></i></div>
                                    <div class="ret-kpi-text">
                                        <span class="ret-kpi-val" id="ret-kpi-rusak">0</span>
                                        <span class="ret-kpi-lbl">Dus Rusak</span>
                                        <span class="ret-kpi-sub" id="ret-kpi-unit-rusak">0 unit</span>
                                    </div>
                                </div>
                                <div class="ret-kpi-card">
                                    <div class="ret-kpi-icon bg-unit"><i class="fa fa-cubes"></i></div>
                                    <div class="ret-kpi-text">
                                        <span class="ret-kpi-val" id="ret-kpi-unit-total">0</span>
                                        <span class="ret-kpi-lbl">Total Unit</span>
                                        <span class="ret-kpi-sub" id="ret-kpi-label-total">0 dus</span>
                                    </div>
                                </div>
                                <div class="ret-kpi-card">
                                    <div class="ret-kpi-icon bg-nota"><i class="fa fa-receipt"></i></div>
                                    <div class="ret-kpi-text">
                                        <span class="ret-kpi-val" id="ret-kpi-nota">0</span>
                                        <span class="ret-kpi-lbl">Nota Terdampak</span>
                                        <span class="ret-kpi-sub" id="ret-kpi-batch">0 batch</span>
                                    </div>
                                </div>
                                <div class="ret-kpi-card">
                                    <div class="ret-kpi-icon bg-cust"><i class="fa fa-users"></i></div>
                                    <div class="ret-kpi-text">
                                        <span class="ret-kpi-val" id="ret-kpi-customer">0</span>
                                        <span class="ret-kpi-lbl">Customer</span>
                                        <span class="ret-kpi-sub">terdampak</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ret-last-bar" id="returnFilterSummary" style="display:none;">
                                <i class="fa fa-filter"></i>
                                <span id="returnFilterSummaryText">Statistik di atas dihitung sesuai filter aktif.</span>
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
                                                    <th class="ps-4"><strong>WAKTU RETURN</strong></th>
                                                    <th><strong>CUSTOMER / NOTA</strong></th>
                                                    <th><strong>BATCH</strong></th>
                                                    <th><strong>ITEM / SIZE</strong></th>
                                                    <th class="text-center"><strong>QTY</strong></th>
                                                    <th><strong>LABEL</strong></th>
                                                    <th class="text-center"><strong>KONDISI</strong></th>
                                                    <th><strong>OLEH</strong></th>
                                                    <th><strong>ALASAN</strong></th>
                                                </tr>
                                            </thead>
                                            <tbody id="returnTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer border-0 d-flex flex-column flex-md-row justify-content-between align-items-center p-4 gap-3">
                                    <div id="returnPaginationInfo" class="small text-muted font-w600"></div>
                                    <nav><ul class="pagination pagination-xs mb-0" id="returnPaginationControls"></ul></nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- /tabReturn -->

                </div> <!-- /tab-content -->
            </div>
        </div>

        <!-- MODAL VIEW DETAIL PENGIRIMAN -->
        <!-- data-bs-focus="false": cegah focus-trap Bootstrap mencegat input pada Swal di atas modal -->
        <div class="modal fade" id="modalViewShipment" tabindex="-1" aria-hidden="true" data-bs-focus="false">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                    <div class="modal-header bg-primary text-white border-0" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title text-white font-w700">Rincian Lengkap Pengiriman</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4" id="viewDetailContent">
                        <!-- Content loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.currentPage = 1;
        window.latestData = [];
        window.columnStates = { 'col-time': true, 'col-customer': true, 'col-items': true, 'col-total': true, 'col-officer': true };

        window.downloadSuratJalan = function(id) {
            Swal.fire({ title: 'Menyiapkan PDF...', text: 'Mohon tunggu sebentar', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const iframeId = 'iframe_print_' + id;
            let iframe = document.getElementById(iframeId);
            if(iframe) iframe.remove();
            iframe = document.createElement('iframe');
            iframe.id = iframeId;
            iframe.style.position = 'absolute';
            iframe.style.width = '8.5in';
            iframe.style.height = '5.5in';
            iframe.style.left = '-9999px';
            iframe.style.top = '-9999px';
            iframe.src = 'print_surat_jalan.php?id=' + id + '&auto=1';
            document.body.appendChild(iframe);
        };
        window.addEventListener('message', function(event) {
            if (event.data && event.data.action === 'pdf_downloaded') {
                Swal.close();
                toastr.success('PDF Surat Jalan berhasil diunduh');
                setTimeout(() => { const iframe = document.getElementById('iframe_print_' + event.data.id); if(iframe) iframe.remove(); }, 2000);
            }
        });

        function formatCompactNumber(number) {
            return Number(number || 0).toLocaleString('id-ID');
        }

        window.fetchShipments = async function(page = 1) {
            const formData = new FormData(document.getElementById('formFilter'));
            const viewMode = document.querySelector('input[name="view_mode"]:checked').value;
            const params = new URLSearchParams(formData);
            params.set('page', page); 
            params.set('limit', 10);
            params.set('view_mode', viewMode);

            try {
                const res = await fetch(`../api/get_shipments.php?${params.toString()}&_nocache=${Date.now()}`);
                const result = await res.json();
                
                if (result.stats) {
                    document.getElementById('kpi-pengiriman').innerText = result.stats.total_pengiriman;
                    document.getElementById('kpi-unit').innerText = result.stats.total_unit;
                    document.getElementById('kpi-customer').innerText = result.stats.total_customer;
                    if(result.stats.bulan) document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = result.stats.bulan);
                }

                if(result.data) {
                    window.latestData = result.data;
                    updateTableHeader(viewMode);
                    displayData(result.data, viewMode);
                    setupPagination(result.pages, result.total, page);
                    window.currentPage = page;
                }
            } catch (e) { console.error(e); }
        }

        function updateTableHeader(mode) {
            const header = document.getElementById('tableShipmentHeader');
            if (mode === 'batch') {
                header.innerHTML = `<tr><th class="ps-4"><strong>KODE BATCH</strong></th><th><strong>ITEM & UKURAN</strong></th><th><strong>TOTAL DISTRIBUSI</strong></th><th><strong>TUJUAN PENGIRIMAN (CUSTOMER)</strong></th></tr>`;
            } else {
                header.innerHTML = `<tr><th class="ps-4 col-time"><strong>WAKTU</strong></th><th class="col-customer"><strong>CUSTOMER</strong></th><th class="col-items"><strong>ITEM & UKURAN</strong></th><th class="col-total"><strong>TOTAL</strong></th><th class="col-officer"><strong>PETUGAS</strong></th></tr>`;
            }
        }

        function displayData(data, mode) {
            const tbody = document.getElementById('tableShipmentBody');
            tbody.innerHTML = data.length ? '' : '<tr><td colspan="5" class="text-center py-5 text-muted">Data Kosong.</td></tr>';
            
            data.forEach((row, index) => {
                if (mode === 'batch') {
                    // Split distribution list into clean lines
                    let distributionHTML = '';
                    if (row.distribution_list) {
                        distributionHTML = row.distribution_list.split('|||').map(entry => {
                            // Mencari nama dan jumlah dus secara bersih
                            const parts = entry.split(' (');
                            const name = parts[0];
                            const qty = parts[1] ? `(${parts[1]}` : '';

                            return `<div class="mb-1 d-flex justify-content-between border-bottom pb-1" style="border-bottom-style: dotted !important;">
                                        <span class="text-black font-w600" style="font-size:12px;">${name}</span>
                                        <span class="text-primary font-w800 ms-2" style="font-size:11px;">${qty}</span>
                                    </div>`;
                        }).join('');
                    }

                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td class="ps-4">
                                <span class="text-primary font-w800" style="font-size:13px; letter-spacing:0.5px;">${row.batch}</span>
                            </td>
                            <td>
                                <div class="text-black font-w700" style="font-size:13px; line-height:1.2;">${row.item}</div>
                                <small class="text-muted font-w600" style="font-size:11px;">${row.size} ${row.unit}</small>
                            </td>
                            <td>
                                <div class="badge badge-success text-white font-w800" style="font-size:11px; padding: 4px 10px; border-radius:4px;">${row.total_qty} Dus</div>
                            </td>
                            <td style="max-width: 400px; padding-top: 12px; padding-bottom: 12px;">
                                <div class="distribution-list-container">
                                    ${distributionHTML}
                                </div>
                            </td>
                        </tr>
                    `);
                } else {
                    let itemsHTML = '';
                    if (row.item_summary) {
                        const items = row.item_summary.split(';');
                        const aggregated = {};
                        items.forEach(it => {
                            const p = it.split('|');
                            aggregated[p[0]] = (aggregated[p[0]] || 0) + parseInt(p[1] || 0);
                        });
                        itemsHTML = Object.entries(aggregated).map(([n, c]) => `<div class="mb-2"><div class="text-black font-w700" style="font-size:13px; line-height:1.1;">${n}</div><small class="text-muted font-w600" style="font-size:11px;">${c} Dus</small></div>`).join('');
                    }
                    tbody.insertAdjacentHTML('beforeend', `
                        <tr onclick="showRowActions(${index})">
                            <td class="ps-4 col-time"><span class="text-black font-w600" style="font-size:13px;">${row.shipped_at_formatted}</span><br><small class="text-muted font-w500">${row.shipped_time_formatted}</small></td>
                            <td class="col-customer"><div class="text-primary font-w700" style="font-size:14px;">${row.customer_name}</div><small class="text-muted">${row.customer_contact || '-'}</small></td>
                            <td class="col-items">${itemsHTML || '-'}</td>
                            <td class="col-total">${parseInt(row.total_qty) > 0 ? `<div class="badge badge-success text-white font-w800" style="font-size:12px; padding: 5px 10px; border-radius:6px;">${row.total_qty} Dus</div>` : `<span class="badge badge-danger text-white font-w800" style="font-size:11px;">RETURNED</span>`}</td>
                            <td class="col-officer"><small class="font-w600 text-black">${row.shipped_by}</small></td>
                        </tr>
                    `);
                }
            });
        }

        window.showRowActions = function(index) {
            const row = window.latestData[index];
            if (!row.id) return; // Disable for batch mode rows
            Swal.fire({
                html: `
                    <div class="swal2-close-custom" onclick="Swal.close()"><i class="fa fa-times"></i></div>
                    <div class="text-center mb-3"><small class="text-muted d-block mb-1">Customer</small><strong class="text-black">${row.customer_name}</strong></div>
                    <div class="action-list">
                        <button onclick="Swal.close(); viewShipmentDetail(${index})" class="action-item"><i class="fa fa-eye icon-view"></i> Lihat Rincian Dus</button>
                        <button onclick="Swal.close(); window.location.href='shipment_scan.php?append_id=${row.id}'" class="action-item"><i class="fa fa-plus icon-append"></i> Tambah Dus Susulan</button>
                        <button onclick="Swal.close(); downloadSuratJalan(${row.id})" class="action-item"><i class="fa fa-print icon-print"></i> Cetak Surat Jalan (Dot Matrix)</button>
                        <button onclick="Swal.close(); deleteShipment(${row.id}, '${row.customer_name}')" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Batalkan Pengiriman</button>
                    </div>
                `,
                showConfirmButton: false, width: '320px', borderRadius: '15px'
            });
        };

        window.currentDetailShipmentId = null;
        window.selectedReturnLabels = {}; // { production_id: Set<label_no> }

        window.viewShipmentDetail = async function(index) {
            const row = window.latestData[index];
            const modalEl = document.getElementById('modalViewShipment');
            const contentEl = document.getElementById('viewDetailContent');
            contentEl.innerHTML = '<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></div>';
            new bootstrap.Modal(modalEl).show();
            window.currentDetailShipmentId = row.id;
            window.selectedReturnLabels = {};
            try {
                const res = await fetch(`../api/get_shipment_details.php?id=${row.id}`);
                const r = await res.json();
                if (r.status !== 'success') throw new Error(r.message || 'Gagal memuat');

                const batches = r.data.map(it => {
                    const labels = (it.label_nos || '').split(',').filter(x => x.length).map(x => parseInt(x));
                    const chips = labels.map(n => `<div class="label-chip" data-pid="${it.production_id}" data-no="${n}" onclick="toggleReturnLabel(${it.production_id}, ${n}, this)">#${n}</div>`).join('');
                    return `
                        <div class="border rounded p-3 mb-3" data-pid="${it.production_id}">
                            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                <div>
                                    <div class="font-w800 text-black">${it.item} <span class="text-muted small">${it.size} ${it.unit}</span></div>
                                    <div class="small text-muted">Batch: <b class="text-primary">#${it.batch}</b> &middot; ${labels.length} dus terkirim</div>
                                </div>
                                <button class="btn btn-danger btn-xs font-w700 px-3" id="btn-return-${it.production_id}" onclick="returnSelectedLabels(${it.production_id}, '${it.batch}')" disabled>
                                    <i class="fa fa-undo me-1"></i>Return Dipilih
                                </button>
                            </div>
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-light btn-xxs px-2" onclick="selectAllReturnLabels(${it.production_id})"><i class="fa fa-check-double me-1"></i>Pilih Semua</button>
                                <button type="button" class="btn btn-light btn-xxs px-2" onclick="clearReturnLabels(${it.production_id})"><i class="fa fa-times me-1"></i>Bersihkan</button>
                            </div>
                            <div class="label-chip-grid">${chips || '<div class="text-muted small p-2">Tidak ada label.</div>'}</div>
                        </div>
                    `;
                }).join('');

                contentEl.innerHTML = `
                    <div class="p-3 bg-light rounded mb-3">
                        <div class="text-black font-w800">${row.customer_name}</div>
                        <div class="small text-muted">#${row.no_resi}</div>
                        <div class="small text-muted mt-1"><i class="fa fa-info-circle me-1"></i>Pilih label yang ingin di-return, lalu tentukan kondisi (utuh/rusak).</div>
                    </div>
                    ${batches || '<div class="text-center py-3 text-muted">Tidak ada item.</div>'}
                `;
            } catch (e) { contentEl.innerHTML = `<div class="text-danger text-center py-3">Error: ${e.message || e}</div>`; }
        };

        function refreshReturnButton(pid) {
            const set = window.selectedReturnLabels[pid] || new Set();
            const cntEl = document.getElementById(`cnt-pid-${pid}`);
            if (cntEl) cntEl.innerText = set.size;
            const btn = document.getElementById(`btn-return-${pid}`);
            if (btn) btn.disabled = set.size === 0;
        }

        window.toggleReturnLabel = function(pid, labelNo, el) {
            if (!window.selectedReturnLabels[pid]) window.selectedReturnLabels[pid] = new Set();
            const set = window.selectedReturnLabels[pid];
            if (set.has(labelNo)) { set.delete(labelNo); el.classList.remove('selected'); }
            else { set.add(labelNo); el.classList.add('selected'); }
            refreshReturnButton(pid);
        };

        window.selectAllReturnLabels = function(pid) {
            const chips = document.querySelectorAll(`.label-chip[data-pid="${pid}"]`);
            if (!window.selectedReturnLabels[pid]) window.selectedReturnLabels[pid] = new Set();
            chips.forEach(c => {
                const n = parseInt(c.dataset.no);
                window.selectedReturnLabels[pid].add(n);
                c.classList.add('selected');
            });
            refreshReturnButton(pid);
        };

        window.clearReturnLabels = function(pid) {
            const chips = document.querySelectorAll(`.label-chip[data-pid="${pid}"]`);
            chips.forEach(c => c.classList.remove('selected'));
            window.selectedReturnLabels[pid] = new Set();
            refreshReturnButton(pid);
        };

        window.returnSelectedLabels = async function(pid, batch) {
            const set = window.selectedReturnLabels[pid] || new Set();
            const labels = Array.from(set);
            if (labels.length === 0) {
                toastr.warning('Pilih minimal 1 label untuk di-return.');
                return;
            }
            const sid = window.currentDetailShipmentId;
            const confirm = await Swal.fire({
                title: `Return ${labels.length} Label`,
                html: `
                    <div class="text-start">
                        <div class="small text-muted mb-3">
                            Batch <b class="text-primary">#${batch}</b> &middot; Label: <code>${labels.join(', ')}</code>
                        </div>
                        <label class="form-label small font-w700 mb-1">Kondisi Barang</label>
                        <div class="cond-grid" id="ret-cond-grid">
                            <div class="cond-card cond-utuh active" data-val="utuh">
                                <i class="fa fa-check-circle"></i>
                                <div class="cond-title">UTUH</div>
                                <div class="cond-sub">Kembali ke stok Gudang</div>
                            </div>
                            <div class="cond-card cond-rusak" data-val="rusak">
                                <i class="fa fa-exclamation-triangle"></i>
                                <div class="cond-title">RUSAK</div>
                                <div class="cond-sub">Tidak masuk stok</div>
                            </div>
                        </div>
                        <label class="form-label small font-w700 mt-3 mb-1" for="ret-reason">Alasan <span class="text-muted font-w500">(opsional)</span></label>
                        <input type="text" id="ret-reason" class="ret-reason-input" placeholder="Mis. label tergores, salah kirim, dll." autocomplete="off">
                    </div>
                `,
                icon: undefined,
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-undo me-1"></i> Ya, Return',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                focusConfirm: false,
                heightAuto: false,
                allowOutsideClick: false,
                returnFocus: false,
                didOpen: (popup) => {
                    // Pastikan Swal di atas modal Bootstrap (z-index 1055)
                    popup.style.zIndex = 2000;
                    const container = popup.parentElement;
                    if (container) container.style.zIndex = 2000;

                    const grid = popup.querySelector('#ret-cond-grid');
                    grid.addEventListener('click', (ev) => {
                        const card = ev.target.closest('.cond-card');
                        if (!card) return;
                        grid.querySelectorAll('.cond-card').forEach(c => c.classList.remove('active'));
                        card.classList.add('active');
                    });

                    const reason = popup.querySelector('#ret-reason');
                    if (reason) {
                        // Lawan focus-trap Bootstrap kalau masih nyangkut
                        setTimeout(() => reason.focus(), 100);
                        reason.addEventListener('click', () => reason.focus());
                    }
                },
                preConfirm: () => {
                    const active = document.querySelector('#ret-cond-grid .cond-card.active');
                    return {
                        condition: active ? active.dataset.val : 'utuh',
                        reason: (document.getElementById('ret-reason')?.value || '').trim()
                    };
                }
            });
            if (!confirm.isConfirmed) return;

            const f = new FormData();
            f.append('shipment_id', sid);
            f.append('production_id', pid);
            f.append('labels', JSON.stringify(labels));
            f.append('condition', confirm.value.condition);
            f.append('reason', confirm.value.reason || '');

            const r = await fetch('../api/manage_settings.php?action=return_shipment_labels', { method: 'POST', body: f });
            const j = await r.json();
            if (j.status === 'success') {
                toastr.success(`Berhasil return ${j.returned} label (${j.condition.toUpperCase()})`);
                bootstrap.Modal.getInstance(document.getElementById('modalViewShipment')).hide();
                fetchShipments(window.currentPage);
                if (document.getElementById('tab-return-btn')?.classList.contains('active')) fetchReturns(1);
            } else {
                toastr.error(j.message || 'Gagal return');
            }
        };

        window.deleteShipment = function(id, name) {
            Swal.fire({ title: 'Batalkan Pengiriman?', icon: 'warning', showCancelButton: true }).then(async (res) => {
                if (res.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const r = await fetch(`../api/manage_settings.php?action=delete&type=shipment`, { method: 'POST', body: f });
                    if((await r.json()).status === 'success') { toastr.success('Dibatalkan'); fetchShipments(window.currentPage); }
                }
            });
        };

        function setupPagination(totalP, totalD, current) {
            const controls = document.getElementById('paginationControls');
            document.getElementById('paginationInfo').innerText = `Halaman ${current} dari ${totalP} (${totalD} Data)`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchShipments(${current-1})">Prev</a></li>`);
            for (let i = 1; i <= totalP; i++) {
                if (i == 1 || i == totalP || (i >= current-1 && i <= current+1))
                    controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchShipments(${i})">${i}</a></li>`);
            }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchShipments(${current+1})">Next</a></li>`);
        }

        window.selectSuperFilter = (item, size, label) => {
            document.getElementById('f_item_val').value = item;
            document.getElementById('f_size_val').value = size;
            document.getElementById('sf-label').innerText = label || item || 'Semua Item';
            fetchShipments(1);
        };

        window.resetFilter = () => { document.getElementById('formFilter').reset(); selectSuperFilter('', '', ''); fetchShipments(1); }
        document.getElementById('f_search').oninput = () => { clearTimeout(window.sT); window.sT = setTimeout(() => fetchShipments(1), 500); }

        $('#f_daterange').daterangepicker({ autoUpdateInput: false });
        $('#f_daterange').on('apply.daterangepicker', (e, p) => { $(e.target).val(p.startDate.format('YYYY-MM-DD') + ' - ' + p.endDate.format('YYYY-MM-DD')); $('#f_start').val(p.startDate.format('YYYY-MM-DD')); $('#f_end').val(p.endDate.format('YYYY-MM-DD')); fetchShipments(1); });

        // ====================== TAB: RIWAYAT RETURN ======================
        window.currentReturnPage = 1;

        function fmtNum(n) { return Number(n || 0).toLocaleString('id-ID'); }
        function fmtDateTime(s) {
            if (!s) return null;
            const t = new Date(s.replace(' ', 'T'));
            if (isNaN(t)) return null;
            return t.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) + ' ' +
                   t.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
        }

        function renderReturnStats(stats) {
            stats = stats || {};
            document.getElementById('ret-kpi-event').innerText      = fmtNum(stats.cnt_event);
            document.getElementById('ret-kpi-utuh').innerText       = fmtNum(stats.cnt_utuh);
            document.getElementById('ret-kpi-rusak').innerText      = fmtNum(stats.cnt_rusak);
            document.getElementById('ret-kpi-unit-utuh').innerText  = fmtNum(stats.cnt_unit_utuh)  + ' unit';
            document.getElementById('ret-kpi-unit-rusak').innerText = fmtNum(stats.cnt_unit_rusak) + ' unit';
            document.getElementById('ret-kpi-unit-total').innerText = fmtNum(stats.cnt_unit_total);
            document.getElementById('ret-kpi-label-total').innerText = fmtNum(stats.cnt_label_total) + ' dus';
            document.getElementById('ret-kpi-nota').innerText       = fmtNum(stats.cnt_nota);
            document.getElementById('ret-kpi-batch').innerText      = fmtNum(stats.cnt_batch) + ' batch';
            document.getElementById('ret-kpi-customer').innerText   = fmtNum(stats.cnt_customer);

            const lastTxt = fmtDateTime(stats.last_returned_at);
            document.getElementById('ret-kpi-last').innerText = lastTxt ? `Terakhir: ${lastTxt}` : 'Belum ada';
        }

        function describeReturnFilters(formEl) {
            const fd = new FormData(formEl);
            const tokens = [];
            const search = (fd.get('search') || '').toString().trim();
            const batch  = (fd.get('batch')  || '').toString().trim();
            const cond   = (fd.get('condition') || '').toString().trim();
            const start  = (fd.get('start_date') || '').toString().trim();
            const end    = (fd.get('end_date')   || '').toString().trim();
            if (search) tokens.push(`pencarian "${search}"`);
            if (batch)  tokens.push(`batch "${batch}"`);
            if (cond)   tokens.push(`kondisi ${cond.toUpperCase()}`);
            if (start && end) tokens.push(`tanggal ${start} s.d. ${end}`);
            return tokens;
        }

        window.fetchReturns = async function(page = 1) {
            const formEl = document.getElementById('formReturnFilter');
            const formData = new FormData(formEl);
            const params = new URLSearchParams(formData);
            params.set('page', page);
            params.set('limit', 10);
            try {
                const res = await fetch(`../api/get_shipment_returns.php?${params.toString()}&_nocache=${Date.now()}`);
                const result = await res.json();
                renderReturnStats(result.stats);

                const filters = describeReturnFilters(formEl);
                const bar = document.getElementById('returnFilterSummary');
                const txt = document.getElementById('returnFilterSummaryText');
                if (filters.length) {
                    bar.style.display = 'flex';
                    txt.innerHTML = `Statistik mengikuti filter aktif: <b>${filters.join(' &middot; ')}</b>`;
                } else {
                    bar.style.display = 'none';
                }

                renderReturns(result.data || []);
                setupReturnPagination(result.pages || 1, result.total || 0, page);
                window.currentReturnPage = page;
            } catch (e) { console.error(e); }
        };

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function renderReturns(data) {
            const tbody = document.getElementById('returnTableBody');
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5 text-muted">Belum ada riwayat return.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(row => {
                const cond = row.condition_status || 'utuh';
                const condBadge = cond === 'rusak'
                    ? '<span class="badge bg-danger text-white font-w700">RUSAK</span>'
                    : '<span class="badge bg-success text-white font-w700">UTUH</span>';

                const labelArr = (row.label_nos || '').split(',').filter(x => x.length);
                const chips = labelArr.length
                    ? `<div class="return-label-chips">${labelArr.map(n => `<span class="return-label-chip ${cond}">#${escapeHtml(n)}</span>`).join('')}</div>`
                    : '<small class="text-muted">-</small>';

                return `
                    <tr>
                        <td class="ps-4">
                            <div class="text-black font-w600 small">${row.returned_date}</div>
                            <small class="text-muted">${row.returned_time} WITA</small>
                        </td>
                        <td>
                            <div class="text-primary font-w700 small">${escapeHtml(row.customer_name || '-')}</div>
                            <small class="text-muted">Nota #${row.shipment_id}</small>
                        </td>
                        <td><span class="text-primary font-w800 small">#${escapeHtml(row.batch)}</span></td>
                        <td>
                            <div class="text-black font-w700 small">${escapeHtml(row.item)}</div>
                            <small class="text-muted">${escapeHtml(row.size)} ${escapeHtml(row.unit)}</small>
                        </td>
                        <td class="text-center">
                            <div class="badge badge-${cond === 'rusak' ? 'danger' : 'success'} text-white font-w800" style="font-size:11px; padding:4px 10px; border-radius:6px;">${row.label_qty} Dus</div>
                        </td>
                        <td>${chips}</td>
                        <td class="text-center">${condBadge}</td>
                        <td><small class="text-black font-w600">${escapeHtml(row.returned_by || '-')}</small></td>
                        <td><small class="text-muted">${escapeHtml(row.reason || '-')}</small></td>
                    </tr>
                `;
            }).join('');
        }

        function setupReturnPagination(totalP, totalD, current) {
            const controls = document.getElementById('returnPaginationControls');
            document.getElementById('returnPaginationInfo').innerText = `Halaman ${current} dari ${totalP} (${totalD} Data)`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchReturns(${current-1})">Prev</a></li>`);
            for (let i = 1; i <= totalP; i++) {
                if (i == 1 || i == totalP || (i >= current-1 && i <= current+1))
                    controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchReturns(${i})">${i}</a></li>`);
            }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchReturns(${current+1})">Next</a></li>`);
        }

        window.resetReturnFilter = () => { document.getElementById('formReturnFilter').reset(); $('#r_daterange').val(''); fetchReturns(1); };
        document.getElementById('r_search').oninput = () => { clearTimeout(window.rT); window.rT = setTimeout(() => fetchReturns(1), 500); };
        document.getElementById('r_batch').oninput  = () => { clearTimeout(window.rT2); window.rT2 = setTimeout(() => fetchReturns(1), 500); };
        document.getElementById('r_condition').onchange = () => fetchReturns(1);

        $('#r_daterange').daterangepicker({ autoUpdateInput: false });
        $('#r_daterange').on('apply.daterangepicker', (e, p) => { $(e.target).val(p.startDate.format('YYYY-MM-DD') + ' - ' + p.endDate.format('YYYY-MM-DD')); $('#r_start').val(p.startDate.format('YYYY-MM-DD')); $('#r_end').val(p.endDate.format('YYYY-MM-DD')); fetchReturns(1); });

        // Lazy-load saat tab return diaktifkan pertama kali
        document.getElementById('tab-return-btn').addEventListener('shown.bs.tab', () => {
            if (!window._returnLoadedOnce) { window._returnLoadedOnce = true; fetchReturns(1); }
        });

        fetchShipments();
    </script>
</body>
</html>