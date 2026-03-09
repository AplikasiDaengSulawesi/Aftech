<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
$role = $_SESSION['role'] ?? 'gudang';
if($role !== 'admin') { header("Location: index.php"); exit; }
require_once '../includes/db.php';

$m_items = $pdo->query("SELECT name FROM master_items ORDER BY name ASC")->fetchAll();
$m_machines = $pdo->query("SELECT name FROM master_machines ORDER BY name ASC")->fetchAll();
$m_shifts = $pdo->query("SELECT name FROM master_shifts ORDER BY name ASC")->fetchAll();
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
    .batch-badge { display: inline-block; white-space: nowrap; padding: 5px 10px; border-radius: 6px; font-size: 11px !important; font-weight: 800; }
    @media (max-width: 767px) { .batch-badge { white-space: normal; word-break: break-all; max-width: 140px; } }

    /* ACTION MENU & DETAIL LIST */
    .action-list { padding: 0; margin: 0; }
    .action-item {
        display: flex; align-items: center; padding: 12px 15px; color: #333; font-weight: 600; font-size: 14px;
        cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f1f1f1; width: 100%; background: none; border-left: none; border-right: none; border-top: none;
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
            
                <!-- KPI WIDGETS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card bg-primary shadow-sm card-kpi">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="fa fa-boxes"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Produksi</p>
                                        <h3 class="text-white mb-0"><span id="kpi-batch">0</span></h3>
                                        <small class="d-block mt-1">Jumlah Batch</small>
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
                                        <p class="mb-1 text-white font-w600">Total Qty</p>
                                        <h3 class="text-white mb-0" id="kpi-qty">0</h3>
                                        <small class="d-block mb-1">Unit Produk</small>
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
                                        <p class="mb-1 text-white font-w600">Belum di Scan</p>
                                        <h3 class="text-white mb-0" id="kpi-belum-scan">0</h3>
                                        <small class="d-block mb-1">Produksi Menunggu QC</small>
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
                                        <p class="mb-1 text-white font-w600">Stok di Gudang</p>
                                        <h3 class="text-white mb-0" id="kpi-gudang">0</h3>
                                        <small class="d-block mb-1">Lolos QC & Tersedia</small>
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
                                        <h4 class="text-black mb-0 font-w800">Database Produksi</h4>
                                        <p class="mb-0 small text-muted">Klik baris untuk opsi detail, edit, atau hapus</p>
                                    </div>
                                    <div class="header-btn-group">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-xs shadow-sm dropdown-toggle font-w600 w-100" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-columns me-1 text-primary"></i> Pilih Kolom
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end column-toggle-dropdown p-3">
                                                <h6 class="dropdown-header ps-0 mb-2 font-w700 text-black">Tampilkan:</h6>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-batch" id="chk-batch" checked disabled><label class="form-check-label" for="chk-batch">Batch / Waktu</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-item" id="chk-item" checked><label class="form-check-label" for="chk-item">Item / Size</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-qty" id="chk-qty" checked><label class="form-check-label" for="chk-qty">Qty</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-mesin" id="chk-mesin" checked><label class="form-check-label" for="chk-mesin">Mesin / Shift</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-progres" id="chk-progres" checked><label class="form-check-label" for="chk-progres">Progres QC</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-tim" id="chk-tim" checked><label class="form-check-label" for="chk-tim">QC Operator</label></div>
                                                <div class="form-check"><input class="form-check-input col-checkbox" type="checkbox" value="col-device" id="chk-device"><label class="form-check-label" for="chk-device">Device</label></div>
                                            </div>
                                        </div>
                                        <button onclick="resetFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600">
                                            <i class="fa fa-undo me-1"></i> Reset Filter
                                        </button>
                                    </div>
                                </div>
                                <form id="formFilter" class="row g-2">
                                    <div class="col-12 col-md-3"><input type="text" id="f_search" name="search" class="form-control form-control-sm" placeholder="Cari data..."></div>
                                    <div class="col-6 col-md-2"><select name="item" id="f_item" class="form-control form-control-sm default-select auto-filter"><option value="">Semua Item</option><?php foreach($m_items as $i) echo "<option value='{$i['name']}'>{$i['name']}</option>"; ?></select></div>
                                    <div class="col-6 col-md-2"><select name="machine" id="f_machine" class="form-control form-control-sm default-select auto-filter"><option value="">Semua Mesin</option><?php foreach($m_machines as $m) echo "<option value='{$m['name']}'>{$m['name']}</option>"; ?></select></div>
                                    <div class="col-12 col-md-5"><input type="text" id="f_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Tanggal" readonly><input type="hidden" name="start_date" id="f_start"><input type="hidden" name="end_date" id="f_end"></div>
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
                                                <th class="ps-4 text-center col-batch"><strong>BATCH / WAKTU</strong></th>
                                                <th class="col-item"><strong>ITEM / SIZE</strong></th>
                                                <th class="col-qty text-center"><strong>QTY</strong></th>
                                                <th class="col-mesin"><strong>MESIN</strong></th>
                                                <th class="col-progres"><strong>PROGRES</strong></th>
                                                <th class="col-tim"><strong>QC OPERATOR</strong></th>
                                                <th class="col-device col-hidden"><strong>DEVICE</strong></th>
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
        <div class="modal fade" id="modalViewProduction" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg" style="border-radius: 20px;"><div class="modal-header bg-primary text-white border-0" style="border-radius: 20px 20px 0 0;"><h5 class="modal-title text-white font-w700">Informasi Lengkap Produksi</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4" id="viewDetailContent"></div></div></div></div>
        <div class="modal fade" id="modalEditProduction" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow-lg" style="border-radius: 20px;"><form id="formEditProduction"><div class="modal-header border-0 pb-0"><h5 class="text-black font-w800">Koreksi Data Produksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="edit_id"><div class="row g-3"><div class="col-md-6"><label class="small font-w600">Item</label><select name="item" id="edit_item" class="form-control default-select"><?php foreach($m_items as $i) echo "<option value='{$i['name']}'>{$i['name']}</option>"; ?></select></div><div class="col-md-3"><label class="small font-w600">Size</label><input type="text" name="size" id="edit_size" class="form-control" required></div><div class="col-md-3"><label class="small font-w600">Unit</label><input type="text" name="unit" id="edit_unit" class="form-control" required></div><div class="col-md-6"><label class="small font-w600">Batch No</label><input type="text" name="batch" id="edit_batch" class="form-control bg-light" readonly></div><div class="col-md-3"><label class="small font-w600">Qty</label><input type="text" name="quantity" id="edit_quantity" class="form-control" required></div><div class="col-md-3"><label class="small font-w600">Labels</label><input type="number" name="copies" id="edit_copies" class="form-control" required></div><div class="col-md-6"><label class="small font-w600">Mesin</label><select name="machine" id="edit_machine" class="form-control default-select"><?php foreach($m_machines as $m) echo "<option value='{$m['name']}'>{$m['name']}</option>"; ?></select></div><div class="col-md-6"><label class="small font-w600">Shift</label><select name="shift" id="edit_shift" class="form-control default-select"><?php foreach($m_shifts as $s) echo "<option value='{$s['name']}'>{$s['name']}</option>"; ?></select></div><div class="col-md-6"><label class="small font-w600">Operator</label><input type="text" name="operator" id="edit_operator" class="form-control" required></div><div class="col-md-6"><label class="small font-w600">QC</label><input type="text" name="qc" id="edit_qc" class="form-control" required></div><div class="col-md-6"><label class="small font-w600">Tanggal</label><input type="date" name="production_date" id="edit_p_date" class="form-control" required></div><div class="col-md-6"><label class="small font-w600">Waktu</label><input type="time" name="production_time" id="edit_p_time" class="form-control" step="1" required></div></div></div><div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button></div></form></div></div></div>
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.currentPage = 1; window.latestData = [];
        window.columnStates = { 'col-batch': true, 'col-item': true, 'col-qty': true, 'col-mesin': true, 'col-progres': true, 'col-tim': true, 'col-device': false };

        $('#f_daterange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' } });
        $('#f_daterange').on('apply.daterangepicker', function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD')); $('#f_start').val(picker.startDate.format('YYYY-MM-DD')); $('#f_end').val(picker.endDate.format('YYYY-MM-DD')); fetchProduction(1); });
        $('#f_daterange').on('cancel.daterangepicker', function(ev, picker) { $(this).val(''); $('#f_start').val(''); $('#f_end').val(''); fetchProduction(1); });

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

        window.fetchProduction = async function(page = 1) {
            const params = new URLSearchParams(new FormData(document.getElementById('formFilter')));
            params.set('page', page); params.set('limit', 10);
            try {
                const res = await fetch(`../api/get_production.php?${params.toString()}&_nocache=${Date.now()}`);
                const result = await res.json();
                
                // Update KPI Stats if available
                if (result.stats) {
                    document.getElementById('kpi-batch').innerText = formatCompactNumber(result.stats.total_batch);
                    document.getElementById('kpi-batch').title = result.stats.total_batch.toLocaleString();
                    
                    document.getElementById('kpi-qty').innerText = formatCompactNumber(result.stats.total_qty);
                    document.getElementById('kpi-qty').title = result.stats.total_qty.toLocaleString();
                    
                    document.getElementById('kpi-belum-scan').innerText = formatCompactNumber(result.stats.belum_scan);
                    document.getElementById('kpi-belum-scan').title = result.stats.belum_scan.toLocaleString();
                    
                    document.getElementById('kpi-gudang').innerText = formatCompactNumber(result.stats.in_warehouse);
                    document.getElementById('kpi-gudang').title = result.stats.in_warehouse.toLocaleString();
                    
                    // Update bulan title dynamically if provided by backend
                    if(result.stats.bulan) {
                        document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = `(${result.stats.bulan})`);
                    }
                }
                
                if (result.data) { window.latestData = result.data; displayData(result.data); setupPagination(result.pages, result.total, page); window.currentPage = page; }
            } catch (e) { console.error(e); }
        }

        setInterval(() => { if (!document.hidden) fetchProduction(window.currentPage); }, 3000);

        function displayData(data) {
            const tbody = document.getElementById('tableProductionBody');
            tbody.innerHTML = data.length ? '' : '<tr><td colspan="7" class="text-center py-5 text-muted">Data Kosong.</td></tr>';
            const monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

            data.forEach((row, index) => {
                const pct = Math.round((parseInt(row.scanned)/parseInt(row.copies))*100) || 0;
                let dateFormatted = '-- ---';
                if(row.production_date) { const d = new Date(row.production_date); dateFormatted = `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`; }

                tbody.insertAdjacentHTML('beforeend', `
                    <tr onclick="showRowActions(${index})">
                        <td class="ps-4 text-center col-batch ${window.columnStates['col-batch'] ? '' : 'col-hidden'}">
                            <span class="badge bg-primary text-white batch-badge mb-1">${row.batch}</span>
                            <div class="text-black small" style="font-weight:400;">${dateFormatted} | ${row.production_time || '--:--'} WITA</div>
                        </td>
                        <td class="col-item ${window.columnStates['col-item'] ? '' : 'col-hidden'}"><div class="text-black font-w700">${row.item}</div><small class="text-black">${row.size} ${row.unit}</small></td>
                        <td class="col-qty text-center ${window.columnStates['col-qty'] ? '' : 'col-hidden'}"><span class="badge badge-light border text-black shadow-sm font-w700" style="font-size:12px;">${row.quantity} Unit</span></td>
                        <td class="col-mesin ${window.columnStates['col-mesin'] ? '' : 'col-hidden'}"><span class="text-black font-w600">${row.machine}</span><br><small>${row.shift}</small></td>
                        <td class="col-progres ${window.columnStates['col-progres'] ? '' : 'col-hidden'}">
                            <div style="min-width:120px;">
                                <div class="d-flex justify-content-between align-items-center mb-1"><span class="text-black font-w800 small">${row.scanned} / ${row.copies}</span><small class="text-muted font-w700">${pct}%</small></div>
                                <div class="progress" style="height:5px; background:#eee;"><div class="progress-bar ${pct === 100 ? 'bg-success' : 'bg-primary'}" style="width: ${pct}%;"></div></div>
                            </div>
                        </td>
                        <td class="col-tim ${window.columnStates['col-tim'] ? '' : 'col-hidden'}"><div class="small"><strong>OP:</strong> ${row.operator}</div><div class="small"><strong>QC:</strong> ${row.qc}</div></td>
                        <td class="col-device ${window.columnStates['col-device'] ? '' : 'col-hidden'}"><span class="badge-device">${row.device_model || 'Unknown'}</span></td>
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
                        <button onclick="Swal.close(); viewProduction(${index})" class="action-item"><i class="fa fa-eye icon-view"></i> Lihat Detail</button>
                        <button onclick="Swal.close(); openEditModal(window.latestData[${index}])" class="action-item"><i class="fa fa-edit icon-edit"></i> Koreksi Data</button>
                        <button onclick="Swal.close(); deleteProduction(${row.id})" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Hapus Permanen</button>
                    </div>
                `,
                showConfirmButton: false, padding: '1.2rem', width: '320px', borderRadius: '15px'
            });
        };

        window.viewProduction = function(index) {
            const row = window.latestData[index];
            const pct = Math.round((parseInt(row.scanned)/parseInt(row.copies))*100) || 0;
            const statusGudang = (parseInt(row.scanned) >= parseInt(row.copies)) ? '<span class="badge badge-success">Lengkap</span>' : '<span class="badge badge-warning">Parsial</span>';
            
            const content = `
                <div class="text-center mb-3"><span class="badge bg-primary text-white px-3 py-2" style="font-size:13px;">${row.batch}</span></div>
                <div class="detail-list">
                    <div class="detail-list-item"><span class="detail-label">Item / Ukuran</span><span class="detail-value">${row.item} (${row.size} ${row.unit})</span></div>
                    <div class="detail-list-item"><span class="detail-label">Mesin / Shift</span><span class="detail-value">${row.machine} / ${row.shift}</span></div>
                    <div class="detail-list-item"><span class="detail-label">Operator</span><span class="detail-value">${row.operator}</span></div>
                    <div class="detail-list-item"><span class="detail-label">QC Personnel</span><span class="detail-value">${row.qc}</span></div>
                    <div class="detail-list-item"><span class="detail-label">Waktu Cetak</span><span class="detail-value">${row.production_date} | ${row.production_time}</span></div>
                    <div class="detail-list-item"><span class="detail-label">Perangkat Input</span><span class="detail-value text-primary">${row.device_model || 'System'}</span></div>
                    <div class="detail-list-item"><span class="detail-label">Masuk Gudang</span><span class="detail-value">${row.scanned} / ${row.copies} Label</span></div>
                    <div class="detail-list-item"><span class="detail-label">Status</span><span class="detail-value">${statusGudang}</span></div>
                </div>
                <div class="mt-3 text-center small text-muted italic">ID Produksi: #${row.id}</div>
            `;
            document.getElementById('viewDetailContent').innerHTML = content;
            new bootstrap.Modal(document.getElementById('modalViewProduction')).show();
        };

        $(document).on('change', '.col-checkbox', function() {
            const colClass = $(this).val();
            window.columnStates[colClass] = $(this).is(':checked');
            $(`th.${colClass}, td.${colClass}`).toggleClass('col-hidden', !$(this).is(':checked'));
        });

        window.openEditModal = function(row) {
            document.getElementById('formEditProduction').reset();
            document.getElementById('edit_id').value = row.id;
            document.getElementById('edit_item').value = row.item;
            document.getElementById('edit_size').value = row.size;
            document.getElementById('edit_unit').value = row.unit;
            document.getElementById('edit_batch').value = row.batch;
            document.getElementById('edit_quantity').value = row.quantity;
            document.getElementById('edit_copies').value = row.copies;
            document.getElementById('edit_machine').value = row.machine;
            document.getElementById('edit_shift').value = row.shift;
            document.getElementById('edit_operator').value = row.operator;
            document.getElementById('edit_qc').value = row.qc;
            document.getElementById('edit_p_date').value = row.production_date;
            document.getElementById('edit_p_time').value = row.production_time;
            $('.default-select').selectpicker('refresh');
            new bootstrap.Modal(document.getElementById('modalEditProduction')).show();
        };

        document.getElementById('formEditProduction').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch(`../api/admin_manage.php?action=save&type=production`, { method: 'POST', body: new FormData(e.target) });
            if((await res.json()).status === 'success') { bootstrap.Modal.getInstance(document.getElementById('modalEditProduction')).hide(); toastr.success('Data Berhasil Diperbarui'); fetchProduction(window.currentPage); }
        };

        window.deleteProduction = function(id) {
            Swal.fire({ title: 'Hapus?', text: "Data ini akan dihapus permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#D50000', confirmButtonText: 'Ya, Hapus' }).then(async (result) => {
                if (result.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const res = await fetch(`../api/admin_manage.php?action=delete&type=production`, { method: 'POST', body: f });
                    if((await res.json()).status === 'success') { toastr.success('Data produksi berhasil dihapus.'); fetchProduction(window.currentPage); }
                }
            });
        };

        function setupPagination(totalP, totalD, current) {
            const controls = document.getElementById('paginationControls');
            document.getElementById('paginationInfo').innerText = `Data ${(current-1)*10 + 1}-${Math.min(current*10, totalD)} dari ${totalD}`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchProduction(${current-1})"><i class="fas fa-chevron-left"></i></a></li>`);
            for (let i = 1; i <= totalP; i++) { if (i == 1 || i == totalP || (i >= current-1 && i <= current+1)) controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchProduction(${i})">${i}</a></li>`); else if (i == current - 2 || i == current + 2) controls.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><a class="page-link">...</a></li>`); }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchProduction(${current+1})"><i class="fas fa-chevron-right"></i></a></li>`);
        }

        window.resetFilter = () => { document.getElementById('formFilter').reset(); $('#f_daterange').val(''); $('#f_start').val(''); $('#f_end').val(''); fetchProduction(1); }
        document.getElementById('f_search').oninput = () => { clearTimeout(window.sT); window.sT = setTimeout(() => { fetchProduction(1); }, 500); }
        $(document).on('change', '.auto-filter', () => { fetchProduction(1); });

        fetchProduction(1);
        document.querySelector('a[href="data_produksi.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>