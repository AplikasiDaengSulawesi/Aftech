<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
require_once '../includes/auth_check.php';
protect_page('production_data');
require_once '../includes/db.php';

$m_machines = $pdo->query("SELECT name FROM master_machines ORDER BY name ASC")->fetchAll();
$m_shifts = $pdo->query("SELECT name FROM master_shifts ORDER BY name ASC")->fetchAll();
$m_items = $pdo->query("SELECT * FROM master_items ORDER BY name ASC")->fetchAll();

// Ambil Hierarki Item & Size untuk Super Filter (Mengikuti gaya Warehouse)
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
    
    /* COMPACT HEADER & MOBILE BUTTONS (Gaya Warehouse) */
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
    .batch-badge { display: inline-block; white-space: nowrap; padding: 5px 10px; border-radius: 6px; font-size: 11px !important; font-weight: 800; }

    /* ACTION MENU & DETAIL LIST */
    .action-list { padding: 0; margin: 0; }
    .action-item {
        display: flex; align-items: center; padding: 12px 15px; color: #333; font-weight: 600; font-size: 14px;
        cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f1f1f1; width: 100%; background: none; border-left: none; border-right: none; border-top: none;
        text-align: left;
    }
    .action-item:last-child { border-bottom: none; }
    .action-item:hover { background: #f8f9ff; color: var(--af-primary); }
    .action-item i { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 12px; font-size: 14px; }
    .icon-view { background: #e8eaf6; color: #1A237E; }
    .icon-edit { background: #fff8e1; color: #ffa000; }
    .icon-delete { background: #ffebee; color: #d32f2f; }
    
    .swal2-close-custom {
        position: absolute; top: 12px; right: 12px; background: #f5f5f5; border-radius: 50%; width: 26px; height: 26px;
        display: flex; align-items: center; justify-content: center; cursor: pointer; color: #999; font-size: 12px;
    }

    .detail-list-item { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; font-size: 13px; }
    .detail-list-item:last-child { border-bottom: none; }
    .detail-label { color: #666; font-weight: 500; }
    .detail-value { color: #000; font-weight: 700; text-align: right; }
    
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
            
                <!-- KPI WIDGETS (5 Widgets) -->
                <div class="row mb-4">
                    <div class="col-xl col-lg-6 col-sm-6 mb-3 mb-xl-0">
                        <div class="widget-stat card bg-primary shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-boxes"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Total Batch</p><h3 class="text-white mb-0" id="kpi-batch">0</h3><small class="d-block mb-1">Batch Produksi</small><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-6 col-sm-6 mb-3 mb-xl-0">
                        <div class="widget-stat card bg-info shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-layer-group"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Total Dus</p><h3 class="text-white mb-0" id="kpi-qty">0</h3><small class="d-block mb-1">Label Dicetak</small><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-6 col-sm-6 mb-3 mb-xl-0">
                        <div class="widget-stat card bg-warning shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-clock"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Antrian QC</p><h3 class="text-white mb-0" id="kpi-belum-scan">0</h3><small class="d-block mb-1">Belum Verifikasi</small><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-6 col-sm-6 mb-3 mb-xl-0">
                        <div class="widget-stat card bg-danger shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-truck-loading"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Terkirim</p><h3 class="text-white mb-0" id="kpi-shipped">0</h3><small class="d-block mb-1">Keluar Gudang</small><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <div class="col-xl col-lg-6 col-sm-6 mb-3 mb-xl-0">
                        <div class="widget-stat card bg-success shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-check-circle"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Stok Gudang</p><h3 class="text-white mb-0" id="kpi-gudang">0</h3><small class="d-block mb-1">Total Dus Tersedia</small><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                </div>

                <!-- FILTER AREA (Format Warehouse) -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div>
                                        <h4 class="text-black mb-0 font-w800">Database Produksi</h4>
                                        <p class="mb-0 small text-muted">Klik baris untuk opsi detail, koreksi, atau hapus</p>
                                    </div>
                                    <div class="header-btn-group">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-xs shadow-sm dropdown-toggle font-w600 w-100" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-columns me-1 text-primary"></i> Pilih Kolom
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end column-toggle-dropdown p-3 shadow-lg">
                                                <h6 class="dropdown-header ps-0 mb-2 font-w700 text-black">Tampilkan:</h6>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-batch" id="chk-batch" checked disabled><label class="form-check-label small font-w600" for="chk-batch">Batch / Waktu</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-item" id="chk-item" checked><label class="form-check-label small font-w600" for="chk-item">Item / Size</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-qty" id="chk-qty" checked><label class="form-check-label small font-w600" for="chk-qty">Total Dus</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-mesin" id="chk-mesin" checked><label class="form-check-label small font-w600" for="chk-mesin">Mesin / Shift</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-progres" id="chk-progres" checked><label class="form-check-label small font-w600" for="chk-progres">Progres QC</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-tim" id="chk-tim" checked><label class="form-check-label small font-w600" for="chk-tim">QC Operator</label></div>
                                                <div class="form-check"><input class="form-check-input col-checkbox" type="checkbox" value="col-device" id="chk-device"><label class="form-check-label small font-w600" for="chk-device">Device</label></div>
                                            </div>
                                        </div>
                                        <button onclick="resetFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600">
                                            <i class="fa fa-undo me-1"></i> Reset Filter
                                        </button>
                                    </div>
                                </div>
                                <form id="formFilter" class="row g-2">
                                    <div class="col-12 col-md-3"><input type="text" id="f_search" name="search" class="form-control form-control-sm" placeholder="Cari data..."></div>
                                    
                                    <!-- SUPER FILTER ITEM -->
                                    <div class="col-6 col-md-2">
                                        <div class="dropdown w-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown">
                                                <span id="sf-label" class="text-truncate text-muted">Semua Item</span>
                                                <i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu shadow-lg border-0 mt-1" style="max-height: 350px; overflow-y: auto; border-radius: 12px; font-size: 13px; min-width: 100%;">
                                                <li><a class="dropdown-item font-w600 text-black" href="javascript:void(0)" onclick="selectSuperFilter('', '')">Semua Item</a></li>
                                                <li><hr class="dropdown-divider m-0"></li>
                                                <?php foreach($hierarchy_data as $item => $data): ?>
                                                    <li>
                                                        <div class="d-flex justify-content-between align-items-center dropdown-item" style="cursor: default;">
                                                            <a class="text-black font-w600 text-decoration-none flex-grow-1" href="javascript:void(0)" onclick="selectSuperFilter('<?= $item ?>', '')"><?= $item ?></a>
                                                            <?php if(!empty($data['sizes'])): ?>
                                                            <a class="text-primary" data-bs-toggle="collapse" href="#collapse-<?= md5($item) ?>" onclick="event.stopPropagation();"><i class="fa fa-plus-circle"></i></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </li>
                                                    <?php if(!empty($data['sizes'])): ?>
                                                    <div class="collapse bg-white" id="collapse-<?= md5($item) ?>">
                                                        <?php foreach($data['sizes'] as $sz): ?>
                                                        <li><a class="dropdown-item text-muted ps-4" href="javascript:void(0)" onclick="selectSuperFilter('<?= $item ?>', '<?= $sz ?>', '<?= $sz ?> <?= $data['unit'] ?>')"><?= $sz ?> <?= $data['unit'] ?></a></li>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                            <input type="hidden" name="item" id="f_item_val"><input type="hidden" name="size" id="f_size_val">
                                        </div>
                                    </div>

                                    <!-- SUPER FILTER MESIN -->
                                    <div class="col-6 col-md-2">
                                        <div class="dropdown w-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown">
                                                <span id="sf-mesin-label" class="text-truncate text-muted">Semua Mesin</span>
                                                <i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu shadow-lg border-0 mt-1" style="max-height: 350px; overflow-y: auto; border-radius: 12px; font-size: 13px; min-width: 100%;">
                                                <li><a class="dropdown-item font-w600 text-black" href="javascript:void(0)" onclick="selectMesinFilter('', '')">Semua Mesin</a></li>
                                                <li><hr class="dropdown-divider m-0"></li>
                                                <?php foreach($m_machines as $m): ?>
                                                    <li>
                                                        <div class="d-flex justify-content-between align-items-center dropdown-item" style="cursor: default;">
                                                            <a class="text-black font-w600 text-decoration-none flex-grow-1" href="javascript:void(0)" onclick="selectMesinFilter('<?= $m['name'] ?>', '')"><?= $m['name'] ?></a>
                                                            <a class="text-primary" data-bs-toggle="collapse" href="#collapse-m-<?= md5($m['name']) ?>" onclick="event.stopPropagation();"><i class="fa fa-plus-circle"></i></a>
                                                        </div>
                                                    </li>
                                                    <div class="collapse bg-white" id="collapse-m-<?= md5($m['name']) ?>">
                                                        <?php foreach($m_shifts as $sh): ?>
                                                        <li><a class="dropdown-item text-muted ps-4" href="javascript:void(0)" onclick="selectMesinFilter('<?= $m['name'] ?>', '<?= $sh['name'] ?>')"><?= $sh['name'] ?></a></li>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </ul>
                                            <input type="hidden" name="machine" id="f_machine_val"><input type="hidden" name="shift" id="f_shift_val">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5"><input type="text" id="f_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Tanggal Produksi" readonly><input type="hidden" name="start_date" id="f_start"><input type="hidden" name="end_date" id="f_end"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLE AREA -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table shadow-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 text-center col-batch"><strong>BATCH / WAKTU</strong></th>
                                                <th class="col-item"><strong>ITEM / SIZE</strong></th>
                                                <th class="col-qty text-center"><strong>TOTAL DUS</strong></th>
                                                <th class="col-mesin"><strong>MESIN / SHIFT</strong></th>
                                                <th class="col-progres"><strong>PROGRES QC</strong></th>
                                                <th class="col-tim"><strong>QC OPERATOR</strong></th>
                                                <th class="col-device col-hidden text-center"><strong>DEVICE</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableProductionBody"></tbody>
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

        <!-- MODALS -->
        <div class="modal fade" id="modalViewProduction" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg" style="border-radius: 20px;"><div class="modal-header bg-primary text-white border-0"><h5 class="modal-title text-white font-w700">Rincian Produksi</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4" id="viewDetailContent"></div></div></div></div>
        
        <div class="modal fade" id="modalEditProduction" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow-lg" style="border-radius: 20px;"><form id="formEditProduction"><div class="modal-header border-0 pb-0"><h5 class="text-black font-w800">Koreksi Data Produksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="edit_id"><div class="row g-3">
            <div class="col-md-6"><label class="small font-w600">Item</label><select name="item" id="edit_item" class="form-control default-select"><?php foreach($m_items as $i) echo "<option value='{$i['name']}'>{$i['name']}</option>"; ?></select></div>
            <div class="col-md-3"><label class="small font-w600">Size</label><input type="text" name="size" id="edit_size" class="form-control" required></div>
            <div class="col-md-3"><label class="small font-w600">Unit</label><input type="text" name="unit" id="edit_unit" class="form-control" required></div>
            <div class="col-md-6"><label class="small font-w600">Batch No</label><input type="text" name="batch" id="edit_batch" class="form-control bg-light" readonly></div>
            <div class="col-md-3"><label class="small font-w600">Qty (Unit/Dus)</label><input type="text" name="quantity" id="edit_quantity" class="form-control" required></div>
            <div class="col-md-3"><label class="small font-w600">Total Dus</label><input type="number" name="copies" id="edit_copies" class="form-control" required></div>
            <div class="col-md-6"><label class="small font-w600">Mesin</label><select name="machine" id="edit_machine" class="form-control default-select"><?php foreach($m_machines as $m) echo "<option value='{$m['name']}'>{$m['name']}</option>"; ?></select></div>
            <div class="col-md-6"><label class="small font-w600">Shift</label><select name="shift" id="edit_shift" class="form-control default-select"><?php foreach($m_shifts as $s) echo "<option value='{$s['name']}'>{$s['name']}</option>"; ?></select></div>
            <div class="col-md-6"><label class="small font-w600">Operator</label><input type="text" name="operator" id="edit_operator" class="form-control" required></div>
            <div class="col-md-6"><label class="small font-w600">QC Personnel</label><input type="text" name="qc" id="edit_qc" class="form-control" required></div>
            <div class="col-md-6"><label class="small font-w600">Tanggal Produksi</label><input type="date" name="production_date" id="edit_p_date" class="form-control" required></div>
            <div class="col-md-6"><label class="small font-w600">Waktu Produksi</label><input type="time" name="production_time" id="edit_p_time" class="form-control" step="1" required></div>
        </div></div><div class="modal-footer border-0 pt-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100" style="height:45px; border-radius:10px;">Simpan Perubahan Data</button></div></form></div></div></div>
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.currentPage = 1; window.latestData = [];
        window.columnStates = { 'col-batch': true, 'col-item': true, 'col-qty': true, 'col-mesin': true, 'col-progres': true, 'col-tim': true, 'col-device': false };

        $('#f_daterange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' } });
        $('#f_daterange').on('apply.daterangepicker', function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD')); $('#f_start').val(picker.startDate.format('YYYY-MM-DD')); $('#f_end').val(picker.endDate.format('YYYY-MM-DD')); fetchProduction(1); });
        $('#f_daterange').on('cancel.daterangepicker', function(ev, picker) { $(this).val(''); $('#f_start').val(''); $('#f_end').val(''); fetchProduction(1); });

        function formatCompactNumber(number) {
            if (number < 1000) return number.toLocaleString('id-ID');
            else if (number >= 1000 && number < 1000000) return (number / 1000).toFixed(1).replace(/\.0$/, '') + ' Rb';
            else if (number >= 1000000) return (number / 1000000).toFixed(1).replace(/\.0$/, '') + ' Jt';
            return number.toLocaleString('id-ID');
        }

        window.fetchProduction = async function(page = 1) {
            const params = new URLSearchParams(new FormData(document.getElementById('formFilter')));
            params.set('page', page); params.set('limit', 10);
            try {
                const res = await fetch(`../api/get_production.php?${params.toString()}&_nocache=${Date.now()}`);
                const result = await res.json();
                if (result.stats) {
                    document.getElementById('kpi-batch').innerText = formatCompactNumber(result.stats.total_batch);
                    document.getElementById('kpi-qty').innerText = formatCompactNumber(result.stats.total_copies);
                    document.getElementById('kpi-belum-scan').innerText = formatCompactNumber(result.stats.belum_scan);
                    document.getElementById('kpi-shipped').innerText = formatCompactNumber(result.stats.total_shipped);
                    document.getElementById('kpi-gudang').innerText = formatCompactNumber(result.stats.total_net_stock);
                    
                    if(result.stats.bulan) {
                        document.querySelectorAll('.kpi-title-month').forEach(el => el.innerHTML = `${result.stats.bulan}`);
                    }
                }
                if (result.data) { window.latestData = result.data; displayData(result.data); setupPagination(result.pages, result.total, page); window.currentPage = page; applyColumnVisibility(); }
            } catch (e) { console.error(e); }
        }

        setInterval(() => { if (!document.hidden) fetchProduction(window.currentPage); }, 5000);

        function displayData(data) {
            const tbody = document.getElementById('tableProductionBody');
            tbody.innerHTML = data.length ? '' : '<tr><td colspan="8" class="text-center py-5 text-muted">Data Kosong.</td></tr>';
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

            data.forEach((row, index) => {
                const pct = Math.round((parseInt(row.scanned)/parseInt(row.copies))*100) || 0;
                let dateFormatted = '-- --- ----';
                if(row.production_date) { 
                    const d = new Date(row.production_date); 
                    dateFormatted = `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`; 
                }
                const timeStr = row.production_time || '00:00:00';
                const fullDateTime = `${dateFormatted} | ${timeStr} WITA`;

                tbody.insertAdjacentHTML('beforeend', `
                    <tr onclick="showRowActions(${index})">
                        <td class="ps-4 text-center col-batch"><span class="badge bg-primary text-white batch-badge mb-1">${row.batch}</span><br><small class="text-black">${fullDateTime}</small></td>
                        <td class="col-item"><div class="text-black font-w700">${row.item}</div><small class="text-black">${row.size} ${row.unit}</small></td>
                        <td class="text-center col-qty"><span class="badge badge-light border text-black font-w700">${row.copies} Dus</span></td>
                        <td class="col-mesin"><div class="text-black font-w600">${row.machine}</div><small>${row.shift}</small></td>
                        <td class="col-progres">
                            <div style="min-width:120px;">
                                <div class="d-flex justify-content-between align-items-center mb-1"><span class="font-w800 small">${row.scanned}/${row.copies}</span><small>${pct}%</small></div>
                                <div class="progress" style="height:5px; background:#eee;"><div class="progress-bar ${pct === 100 ? 'bg-success' : 'bg-primary'}" style="width: ${pct}%;"></div></div>
                            </div>
                        </td>
                        <td class="col-tim"><div class="small"><strong>OP:</strong> ${row.operator}</div><div class="small"><strong>QC:</strong> ${row.qc}</div></td>
                        <td class="col-device col-hidden text-center"><span class="badge badge-light">${row.device_model || '-'}</span></td>
                    </tr>
                `);
            });
        }

        window.showRowActions = function(index) {
            const row = window.latestData[index];
            Swal.fire({
                html: `<div class="swal2-close-custom" onclick="Swal.close()"><i class="fa fa-times"></i></div><div class="text-center mb-3"><small class="text-muted d-block mb-1">Batch ID</small><strong class="text-black" style="word-break:break-all;">${row.batch}</strong></div><div class="action-list"><button onclick="Swal.close(); viewProduction(${index})" class="action-item"><i class="fa fa-eye icon-view"></i> Lihat Detail</button><button onclick="Swal.close(); openEditModal(${index})" class="action-item"><i class="fa fa-edit icon-edit"></i> Koreksi Data</button><button onclick="Swal.close(); deleteProduction(${row.id})" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Hapus Permanen</button></div>`,
                showConfirmButton: false, padding: '1.2rem', width: '320px', borderRadius: '15px'
            });
        };

        window.viewProduction = (index) => {
            const row = window.latestData[index];
            document.getElementById('viewDetailContent').innerHTML = `<div class="detail-list">
                <div class="detail-list-item"><span class="detail-label">Batch ID</span><span class="detail-value text-primary font-w800">${row.batch}</span></div>
                <div class="detail-list-item"><span class="detail-label">Item / Ukuran</span><span class="detail-value">${row.item} (${row.size} ${row.unit})</span></div>
                <div class="detail-list-item"><span class="detail-label">Mesin / Shift</span><span class="detail-value">${row.machine} / ${row.shift}</span></div>
                <div class="detail-list-item"><span class="detail-label">Operator / QC</span><span class="detail-value">${row.operator} / ${row.qc}</span></div>
                <div class="detail-list-item"><span class="detail-label">Waktu</span><span class="detail-value">${row.production_date} ${row.production_time}</span></div>
                <div class="detail-list-item"><span class="detail-label">Perangkat</span><span class="detail-value text-primary">${row.device_model || 'System'}</span></div>
                <div class="detail-list-item"><span class="detail-label">Gudang</span><span class="detail-value">${row.scanned} / ${row.copies} Dus</span></div>
            </div>`;
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewProduction'));
            modal.show();
        };

        window.openEditModal = (index) => {
            const row = window.latestData[index];
            if(!row) return;
            
            document.getElementById('formEditProduction').reset();
            document.getElementById('edit_id').value = row.id;
            
            // Set values dan refresh selectpicker
            $('#edit_item').val(row.item).selectpicker('refresh');
            $('#edit_machine').val(row.machine).selectpicker('refresh');
            $('#edit_shift').val(row.shift).selectpicker('refresh');
            
            document.getElementById('edit_size').value = row.size;
            document.getElementById('edit_unit').value = row.unit;
            document.getElementById('edit_batch').value = row.batch;
            document.getElementById('edit_quantity').value = row.quantity;
            document.getElementById('edit_copies').value = row.copies;
            document.getElementById('edit_operator').value = row.operator;
            document.getElementById('edit_qc').value = row.qc;
            
            let dateVal = row.production_date;
            if (dateVal && dateVal.includes('-')) {
                const p = dateVal.split('-');
                if (p[0].length === 2) dateVal = `${p[2]}-${p[1]}-${p[0]}`;
            }
            document.getElementById('edit_p_date').value = dateVal;
            
            let timeVal = row.production_time || "00:00:00";
            document.getElementById('edit_p_time').value = timeVal;

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditProduction'));
            modal.show();
        };

        document.getElementById('formEditProduction').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch(`../api/manage_settings.php?action=save&type=production`, { method: 'POST', body: new FormData(e.target) });
            const result = await res.json();
            if(result.status === 'success') { 
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditProduction')).hide(); 
                toastr.success('Data Diperbarui'); 
                fetchProduction(window.currentPage); 
            }
        };

        window.deleteProduction = (id) => {
            const row = window.latestData.find(r => r.id == id);
            const hasShipped = row && parseInt(row.shipped) > 0;
            
            const config = {
                title: hasShipped ? '⚠️ Perhatian: Data Terkirim!' : 'Hapus Data Produksi?',
                text: hasShipped 
                    ? `Batch ini memiliki ${row.shipped} dus yang SUDAH TERKIRIM ke customer. Jika dihapus, riwayat stok dan pengiriman terkait batch ini akan ikut terhapus secara permanen. Tetap hapus?` 
                    : "Data akan dihapus permanen dari sistem dan gudang!",
                icon: hasShipped ? 'error' : 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D50000',
                confirmButtonText: hasShipped ? 'Ya, Tetap Hapus Semuanya' : 'Ya, Hapus',
                cancelButtonText: 'Batal'
            };

            Swal.fire(config).then(async (result) => {
                if (result.isConfirmed) {
                    // Double check if shipped to prevent accidental deletion of critical data
                    if (hasShipped) {
                        const { value: confirm } = await Swal.fire({
                            title: 'Konfirmasi Akhir',
                            text: 'Ketik "HAPUS" untuk mengkonfirmasi penghapusan batch yang sudah terkirim ini:',
                            input: 'text',
                            inputPlaceholder: 'Ketik HAPUS di sini...',
                            showCancelButton: true,
                            confirmButtonColor: '#D50000',
                            inputValidator: (value) => {
                                if (!value || value.toUpperCase() !== 'HAPUS') return 'Anda harus mengetik HAPUS untuk melanjutkan!';
                            }
                        });
                        if (!confirm) return;
                    }

                    const f = new FormData(); f.append('id', id);
                    const res = await fetch(`../api/manage_settings.php?action=delete&type=production`, { method: 'POST', body: f });
                    const response = await res.json();
                    if(response.status === 'success') { 
                        toastr.success('Data Berhasil Dihapus'); 
                        fetchProduction(window.currentPage); 
                    } else {
                        toastr.error(response.message || 'Gagal menghapus data');
                    }
                }
            });
        };

        window.selectSuperFilter = (item, size, displayLabel) => {
            document.getElementById('f_item_val').value = item;
            document.getElementById('f_size_val').value = size;
            let label = item || 'Semua Item';
            if(displayLabel) label = `${item} (${displayLabel})`;
            const labelEl = document.getElementById('sf-label');
            labelEl.innerText = label;
            if (item) labelEl.classList.remove('text-muted'); else labelEl.classList.add('text-muted');
            fetchProduction(1);
        };

        window.selectMesinFilter = (machine, shift) => {
            document.getElementById('f_machine_val').value = machine;
            document.getElementById('f_shift_val').value = shift;
            let label = machine || 'Semua Mesin';
            if(shift) label = `${machine} (${shift})`;
            const labelEl = document.getElementById('sf-mesin-label');
            labelEl.innerText = label;
            if (machine) labelEl.classList.remove('text-muted'); else labelEl.classList.add('text-muted');
            fetchProduction(1);
        };

        function setupPagination(totalP, totalD, current) {
            const controls = document.getElementById('paginationControls');
            document.getElementById('paginationInfo').innerText = `Data ${(current-1)*10 + 1}-${Math.min(current*10, totalD)} dari ${totalD}`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchProduction(${current-1})"><i class="fas fa-chevron-left"></i></a></li>`);
            for (let i = 1; i <= totalP; i++) { if (i == 1 || i == totalP || (i >= current-1 && i <= current+1)) controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchProduction(${i})">${i}</a></li>`); else if (i == current-2 || i == current+2) controls.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><a class="page-link">...</a></li>`); }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchProduction(${current+1})"><i class="fas fa-chevron-right"></i></a></li>`);
        }

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

        window.resetFilter = () => { 
            document.getElementById('formFilter').reset(); 
            selectSuperFilter('', ''); selectMesinFilter('', '');
            $('#f_daterange').val(''); $('#f_start').val(''); $('#f_end').val(''); 
            fetchProduction(1); 
        }
        document.getElementById('f_search').oninput = () => { clearTimeout(window.sT); window.sT = setTimeout(() => { fetchProduction(1); }, 500); }

        fetchProduction(1);
        document.querySelector('a[href="production_data.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>