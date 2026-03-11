<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
require_once '../includes/auth_check.php';
protect_page('warehouse');
require_once '../includes/db.php';

// Fetch master data for filters
$m_machines = $pdo->query("SELECT name FROM master_machines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$m_shifts = $pdo->query("SELECT name FROM master_shifts ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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

    .card-kpi { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .card-kpi .card-body { padding: 1.5rem; }
    .kpi-title-month { font-size: 11px; opacity: 0.9; font-weight: 500; color: #fff !important; }
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
                                    <div class="col-12 col-md-5"><input type="text" id="f_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Tanggal Masuk Gudang" readonly><input type="hidden" name="start_date" id="f_start"><input type="hidden" name="end_date" id="f_end"></div>
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
        window.columnStates = { 'col-batch': true, 'col-item': true, 'col-qty': true, 'col-mesin': true, 'col-progres': true, 'col-shipped': true, 'col-pending': true, 'col-qc': true };

        function renderSkeleton() {
            const tbody = document.getElementById('warehouseTableBody');
            tbody.innerHTML = '';
            for(let i=0; i<5; i++) {
                tbody.insertAdjacentHTML('beforeend', `<tr><td class="ps-4"><div class="skeleton" style="height:20px; width:80px;"></div></td><td><div class="skeleton skeleton-text"></div></td><td><div class="skeleton skeleton-badge"></div></td><td><div class="skeleton" style="height:15px; width:80%;"></div></td><td><div class="skeleton skeleton-text"></div></td><td><div class="skeleton skeleton-text"></div></td></tr>`);
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

        window.resetFilter = () => { document.getElementById('formFilter').reset(); $('#f_daterange').val(''); $('#f_start').val(''); $('#f_end').val(''); selectSuperFilter('', '', ''); selectMesinFilter('', '', ''); }
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
                        <td colspan="5" class="text-center py-5">
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
            Swal.fire({ title: 'Hapus dari Gudang?', text: `Kosongkan seluruh unit batch #${batch} dari gudang?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#D50000', confirmButtonText: 'Ya, Hapus' }).then(async (res) => {
                if(res.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const r = await fetch(`../api/manage_settings.php?action=delete&type=warehouse_batch`, { method: 'POST', body: f });
                    if((await r.json()).status === 'success') { toastr.success('Dihapus'); fetchWarehouseStock(window.currentPage); }
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
    </script>
</body>
</html>