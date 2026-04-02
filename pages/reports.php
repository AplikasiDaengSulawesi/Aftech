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

$stmt_qc = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='qc_checker_enabled'");
$qc_checker_enabled = ($stmt_qc->fetchColumn() === '1');
?>
<style>
    .pagination-xs .page-link { padding: 5px 10px; font-size: 12px; }
    .column-toggle-dropdown { padding: 15px; min-width: 220px; border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .col-hidden { display: none !important; }
    
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

    .table-responsive-md .shadow-hover tbody tr { cursor: pointer; transition: 0.2s; }
    .table-responsive-md .shadow-hover tbody tr:hover { background-color: #f8f9ff !important; }
    .batch-badge { display: inline-block; white-space: nowrap; padding: 5px 10px; border-radius: 6px; font-size: 11px !important; font-weight: 800; }

    .card-kpi { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .card-kpi .card-body { padding: 1.5rem; }
    .kpi-title-month { font-size: 11px; opacity: 0.9; font-weight: 500; color: #fff !important; }
    
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        .deznav, .header, .footer, .filter-card, .btn-print-area, .header-btn-group, .nav-header { display: none !important; }
        .content-body { margin-left: 0 !important; padding-top: 0 !important; width: 100% !important; }
        .container-fluid { padding: 0 !important; width: 100% !important; }
        .card { box-shadow: none !important; border: 1px solid #eee !important; }
        .table { width: 100% !important; }
        .table th { background: #f8f9fa !important; color: #1A237E !important; -webkit-print-color-adjust: exact; }
        .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1A237E; padding-bottom: 10px; }
    }
    .print-header { display: none; }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        
        <div class="content-body">
            <div class="container-fluid">
                
                <div class="print-header">
                    <h2 style="margin:0; font-weight:900;">PT AFTECH MAKASSAR INDONESIA</h2>
                    <h4 style="margin:5px 0; color:#1A237E;" id="print-title-dynamic">LAPORAN MANAJEMEN BARANG</h4>
                    <p style="margin:0; font-size:12px; color:#666;">Dicetak pada: <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <!-- KPI WIDGETS -->
                <div class="row mb-4">
                    <?php $col_class = $qc_checker_enabled ? 'col-xl-3' : 'col-xl-4'; ?>
                    <div class="col-sm-6 col-lg-6 <?php echo $col_class; ?>">
                        <div class="widget-stat card bg-primary shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-boxes"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Total Produksi</p><h3 class="text-white mb-0" id="stat-produced">0</h3><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <?php if ($qc_checker_enabled): ?>
                    <div class="col-sm-6 col-lg-6 <?php echo $col_class; ?>">
                        <div class="widget-stat card bg-info shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-check-circle"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Total Verified</p><h3 class="text-white mb-0" id="stat-verified">0</h3><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-6 col-lg-6 <?php echo $col_class; ?>">
                        <div class="widget-stat card bg-success shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-truck"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Total Terkirim</p><h3 class="text-white mb-0" id="stat-shipped">0</h3><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6 <?php echo $col_class; ?>">
                        <div class="widget-stat card bg-warning shadow-sm card-kpi">
                            <div class="card-body p-4"><div class="media"><span class="me-3"><i class="fa fa-warehouse"></i></span><div class="media-body text-white text-end"><p class="mb-1 text-white font-w600">Sisa Stok</p><h3 class="text-white mb-0" id="stat-stock">0</h3><span class="kpi-title-month">Bulan Ini</span></div></div></div>
                        </div>
                    </div>
                </div>

                <!-- FILTER SECTION -->
                <div class="row btn-print-area">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div><h4 class="text-black mb-0 font-w800">Manajemen Laporan</h4><p class="mb-0 small text-muted">Filter data berdasarkan jenis dan periode waktu.</p></div>
                                    <div class="header-btn-group"><button onclick="resetReportFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600"><i class="fa fa-undo me-1"></i> Reset Filter</button></div>
                                </div>
                                <form id="formFilterReport" class="row g-2 align-items-end">
                                    <div class="col-12 col-md-2">
                                        <label class="small font-w600 mb-1">Jenis Laporan</label>
                                        <select name="report_type" id="f_report_type" class="form-control form-control-sm default-select auto-filter">
                                            <option value="rekap">Laporan Rekapitulasi</option>
                                            <option value="produksi">Laporan Bagian Produksi</option>
                                            <option value="gudang">Laporan Inventori Gudang</option>
                                            <option value="pengiriman">Laporan Pengiriman (Customer)</option>
                                            <option value="pengiriman_batch">Laporan Pengiriman (Batch)</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-2">
                                        <label class="small font-w600 mb-1">Periode</label>
                                        <input type="text" id="report_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Periode" readonly>
                                        <input type="hidden" name="start_date" id="f_start" value="<?php echo date('Y-m-01'); ?>"><input type="hidden" name="end_date" id="f_end" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="small font-w600 mb-1">Item Produk</label>
                                        <div class="dropdown w-100">
                                            <button class="form-control form-control-sm d-flex justify-content-between align-items-center text-start" type="button" data-bs-toggle="dropdown"><span id="sf-label" class="text-truncate text-muted">Semua Item</span><i class="fa fa-caret-down opacity-50 ms-2 text-muted"></i></button>
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
                                                    <?php if(!empty($data['sizes'])): ?><div class="collapse bg-white" id="collapse-<?= md5($item) ?>"><?php foreach($data['sizes'] as $sz): ?><li><a class="dropdown-item text-muted ps-4" href="javascript:void(0)" onclick="selectSuperFilter('<?= $item ?>', '<?= $sz ?>', '<?= $sz ?> <?= $data['unit'] ?>')"><?= $sz ?> <?= $data['unit'] ?></a></li><?php endforeach; ?></div><?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                            <input type="hidden" name="item" id="f_item_val"><input type="hidden" name="size" id="f_size_val">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button type="button" onclick="printFormalReport()" class="btn btn-primary btn-sm w-100 font-w600 d-flex align-items-center justify-content-center" style="height:35px; background-color: #1A237E !important; border:none; border-radius: 8px;">
                                            <i class="fa fa-print me-2"></i> Cetak Laporan
                                        </button>
                                    </div>
                                    <div class="col-12 col-md-2 d-flex align-items-center pb-1">
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input auto-filter" id="f_all_time" name="show_all" value="true">
                                            <label class="form-check-label small font-w800 text-black mb-0 ms-1" for="f_all_time" style="cursor: pointer;">Semua</label>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- REPORT TABLE -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table shadow-hover mb-0">
                                        <thead class="bg-light" id="reportTableHeader"></thead>
                                        <tbody id="reportTableBody"></tbody>
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
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.qc_checker_enabled = <?= $qc_checker_enabled ? 'true' : 'false' ?>;
        window.reportCurrentPage = 1;

        function formatCompactNumber(number) {
            number = parseInt(number) || 0;
            if (number < 1000) return number.toLocaleString('id-ID');
            else if (number >= 1000 && number < 1000000) return (number / 1000).toFixed(1).replace(/\.0$/, '') + ' Rb';
            else if (number >= 1000000) return (number / 1000000).toFixed(1).replace(/\.0$/, '') + ' Jt';
            return number.toLocaleString('id-ID');
        }

        $('#report_daterange').daterangepicker({
            startDate: moment().startOf('month'), endDate: moment(), locale: { format: 'DD/MM/YYYY' }
        }, function(start, end) {
            $('#f_start').val(start.format('YYYY-MM-DD')); $('#f_end').val(end.format('YYYY-MM-DD'));
            document.getElementById('f_all_time').checked = false; fetchReport(1);
        });

        async function fetchReport(page = 1) {
            const tbody = document.getElementById('reportTableBody');
            const thead = document.getElementById('reportTableHeader');
            const reportType = document.getElementById('f_report_type').value;
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></td></tr>';
            
            // Render Headers based on reference pages
            let headerHtml = '<tr><th class="ps-4 text-center" style="width: 50px;"><strong>No</strong></th>';
            if (reportType === 'produksi') {
                headerHtml += window.qc_checker_enabled
                    ? '<th class="text-center"><strong>BATCH</strong></th><th><strong>TANGGAL | WAKTU</strong></th><th><strong>ITEM / SIZE</strong></th><th class="text-center"><strong>TOTAL DUS</strong></th><th><strong>MESIN</strong></th><th><strong>SHIFT</strong></th><th class="text-center"><strong>PROGRES QC</strong></th><th class="text-end pe-4"><strong>OP | QC</strong></th></tr>'
                    : '<th class="text-center"><strong>BATCH</strong></th><th><strong>TANGGAL | WAKTU</strong></th><th><strong>ITEM / SIZE</strong></th><th class="text-center"><strong>TOTAL DUS</strong></th><th><strong>MESIN</strong></th><th><strong>SHIFT</strong></th><th class="text-end pe-4"><strong>OP | QC</strong></th></tr>';
            } else if (reportType === 'gudang') {
                headerHtml += '<th class="text-center"><strong>BATCH</strong></th><th><strong>TANGGAL TERIMA</strong></th><th><strong>ITEM / SIZE</strong></th><th class="text-center"><strong>DUS MASUK</strong></th><th class="text-center"><strong>DUS TERKIRIM</strong></th><th class="text-end pe-4"><strong>SISA STOK</strong></th></tr>';
            } else if (reportType === 'pengiriman') {
                headerHtml += '<th style="width:150px;"><strong>WAKTU PENGIRIMAN</strong></th><th style="width:250px;"><strong>CUSTOMER | NO. RESI</strong></th><th><strong>RINCIAN ITEM (ITEM | JUMLAH | BATCH)</strong></th><th class="text-center" style="width:100px;"><strong>TOTAL</strong></th><th class="text-end pe-4" style="width:150px;"><strong>PETUGAS</strong></th></tr>';
            } else if (reportType === 'pengiriman_batch') {
                headerHtml += '<th class="text-center"><strong>KODE BATCH</strong></th><th><strong>ITEM & UKURAN</strong></th><th class="text-center"><strong>TOTAL TERKIRIM</strong></th><th><strong>DISTRIBUSI CUSTOMER</strong></th></tr>';
            } else { // rekap
                headerHtml += window.qc_checker_enabled 
                    ? '<th class="text-center"><strong>BATCH</strong></th><th><strong>TANGGAL</strong></th><th><strong>ITEM & UKURAN</strong></th><th class="text-center"><strong>PRODUKSI</strong></th><th class="text-center"><strong>VERIFIED</strong></th><th class="text-center"><strong>TERKIRIM</strong></th><th class="text-end pe-4"><strong>STOK</strong></th></tr>'
                    : '<th class="text-center"><strong>BATCH</strong></th><th><strong>TANGGAL</strong></th><th><strong>ITEM & UKURAN</strong></th><th class="text-center"><strong>PRODUKSI</strong></th><th class="text-center"><strong>TERKIRIM</strong></th><th class="text-end pe-4"><strong>STOK</strong></th></tr>';
            }
            thead.innerHTML = headerHtml;

            const params = new URLSearchParams(new FormData(document.getElementById('formFilterReport')));
            params.set('page', page);
            params.set('limit', 10);

            try {
                const res = await fetch(`../api/get_reports.php?${params.toString()}`);
                const result = await res.json();
                if (result.status === 'success') {
                    const stats = result.summary;
                    document.getElementById('stat-produced').innerText = formatCompactNumber(stats.produced);
                    if (window.qc_checker_enabled && document.getElementById('stat-verified')) {
                        document.getElementById('stat-verified').innerText = formatCompactNumber(stats.verified);
                    }
                    document.getElementById('stat-shipped').innerText = formatCompactNumber(stats.shipped);
                    document.getElementById('stat-stock').innerText = formatCompactNumber(stats.stock);
                    if(result.summary.bulan) document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = result.summary.bulan);
                    
                    tbody.innerHTML = '';
                    if (result.data.length === 0) { tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted">Tidak ada data.</td></tr>'; return; }
                    const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
                    
                    result.data.forEach((row, i) => {
                        let rowHtml = `<tr><td class="text-center ps-4 text-muted">${(page-1)*10 + i + 1}</td>`;
                        const d = new Date(row.production_date || row.shipment_date || row.latest_shipment);
                        const formattedDate = `${d.getDate()} ${monthNamesShort[d.getMonth()]} ${d.getFullYear()}`;

                        if (reportType === 'produksi') {
                            const pct = Math.round((parseInt(row.scanned)/parseInt(row.produced_qty))*100) || 0;
                            rowHtml += `<td class="text-center"><span class="badge bg-primary text-white batch-badge">${row.batch}</span></td>
                                        <td><small class="text-black font-w600">${formattedDate} <span class="text-muted mx-1">|</span> ${row.production_time}</small></td>
                                        <td><div class="text-black font-w700">${row.item}</div><small class="text-muted font-w600">${row.size} ${row.unit}</small></td>
                                        <td class="text-center"><span class="badge badge-light border text-black font-w700">${row.produced_qty} Dus</span></td>
                                        <td><div class="text-black font-w600">${row.machine}</div></td>
                                        <td><div class="text-muted font-w600">${row.shift}</div></td>`;
                            if (window.qc_checker_enabled) {
                                rowHtml += `<td>
                                            <div style="min-width:100px;">
                                                <div class="text-center mb-1"><span class="font-w900 text-primary" style="font-size:12px;">${row.scanned} / ${row.produced_qty}</span> <small class="text-muted">Verified</small></div>
                                                <div class="progress" style="height:6px; border-radius:10px;"><div class="progress-bar ${pct === 100 ? 'bg-success' : 'bg-primary'}" style="width: ${pct}%;"></div></div>
                                            </div>
                                        </td>`;
                            }
                            rowHtml += `<td class="text-end pe-4"><div class="small"><strong>OP:</strong> ${row.operator} <span class="text-muted mx-1">|</span> <strong>QC:</strong> ${row.qc}</div></td>`;
                        } else if (reportType === 'gudang') {
                            rowHtml += `<td class="text-center"><span class="badge bg-primary text-white batch-badge">${row.batch}</span></td>
                                        <td><small class="text-black font-w600">${formattedDate}</small></td>
                                        <td><div class="text-black font-w700">${row.item}</div><small class="text-muted font-w600">${row.size} ${row.unit}</small></td>
                                        <td class="text-center text-black font-w800">${row.verified_qty} Dus</td>
                                        <td class="text-center text-success font-w800">${row.shipped_qty} Dus</td>
                                        <td class="text-end pe-4"><span class="text-primary font-w800">${row.stock_qty} Dus</span></td>`;
                        } else if (reportType === 'pengiriman') {
                            const sa = new Date(row.shipped_at);
                            let itemsHTML = '';
                            if (row.item_summary) {
                                const items = row.item_summary.split(';');
                                itemsHTML = `<table class="table table-sm table-bordered mb-0" style="font-size:11px; background: #fff;">
                                    <thead class="bg-light"><tr><th class="py-1">Item & Ukuran</th><th class="py-1 text-center" style="width:60px;">Jumlah</th><th class="py-1 text-center" style="width:80px;">Batch</th></tr></thead>
                                    <tbody>`;
                                itemsHTML += items.map(it => {
                                    const parts = it.split('|');
                                    return `<tr>
                                        <td class="py-1 text-black font-w600">${parts[0]}</td>
                                        <td class="py-1 text-center text-primary font-w800">${parts[1]} Dus</td>
                                        <td class="py-1 text-center text-muted">${parts[2]}</td>
                                    </tr>`;
                                }).join('');
                                itemsHTML += `</tbody></table>`;
                            }
                            rowHtml += `<td><span class="text-black font-w600">${formattedDate}</span> <span class="text-muted mx-1">|</span> <small class="font-w700 text-primary">${sa.getHours().toString().padStart(2,'0')}:${sa.getMinutes().toString().padStart(2,'0')}</small></td>
                                        <td><div class="text-primary font-w700">${row.customer_name}</div><small class="text-muted font-w800">RESI: #${row.no_resi}</small></td>
                                        <td class="p-1">${itemsHTML || '-'}</td>
                                        <td class="text-center"><div class="badge badge-success text-white font-w800" style="font-size:13px;">${row.total_shipped_qty} Dus</div></td>
                                        <td class="text-end pe-4"><small class="font-w600 text-black">${row.shipped_by}</small></td>`;
                        } else if (reportType === 'pengiriman_batch') {
                            let distHTML = '';
                            if (row.distribution_list) {
                                distHTML = row.distribution_list.split('|||').map(entry => {
                                    const parts = entry.split(' (');
                                    return `<div class="mb-1 d-flex justify-content-between border-bottom pb-1" style="border-bottom-style: dotted !important;">
                                                <span class="text-black font-w600" style="font-size:11px;">${parts[0]}</span>
                                                <span class="text-primary font-w800 ms-2" style="font-size:10px;">(${parts[1] || ''}</span>
                                            </div>`;
                                }).join('');
                            }
                            rowHtml += `<td class="text-center"><span class="text-primary font-w800" style="font-size:12px;">${row.batch}</span></td>
                                        <td><div class="text-black font-w700" style="font-size:13px;">${row.item}</div><small class="text-muted font-w600">${row.size} ${row.unit}</small></td>
                                        <td class="text-center"><div class="badge badge-success text-white font-w800" style="font-size:11px; padding: 4px 8px;">${row.total_qty} Dus</div></td>
                                        <td style="min-width: 250px; padding-top: 10px; padding-bottom: 10px;">${distHTML}</td>`;
                        } else { // rekap
                            rowHtml += `<td class="text-center"><span class="badge bg-primary text-white batch-badge">${row.batch}</span></td>
                                        <td><small class="text-black font-w600">${formattedDate}</small></td>
                                        <td><div class="text-black font-w700">${row.item}</div><small class="text-muted font-w600">${row.size} ${row.unit}</small></td>
                                        <td class="text-center"><strong>${row.produced_qty}</strong></td>`;
                            if (window.qc_checker_enabled) {
                                rowHtml += `<td class="text-center text-primary font-w700">${row.verified_qty}</td>`;
                            }
                            rowHtml += `<td class="text-center text-success font-w700">${row.shipped_qty}</td>
                                        <td class="text-end pe-4"><strong>${row.stock_qty}</strong></td>`;
                        }
                        rowHtml += `</tr>`; tbody.insertAdjacentHTML('beforeend', rowHtml);
                    });
                    setupPagination(result.pages, result.total, page);
                    window.reportCurrentPage = page;
                }
            } catch (e) { console.error(e); tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-danger">Gagal memuat.</td></tr>'; }
        }

        function setupPagination(totalP, totalD, current) {
            const controls = document.getElementById('paginationControls');
            document.getElementById('paginationInfo').innerText = `Data ${(current-1)*10 + 1}-${Math.min(current*10, totalD)} dari ${totalD}`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchReport(${current-1})"><i class="fas fa-chevron-left"></i></a></li>`);
            for (let i = 1; i <= totalP; i++) { if (i == 1 || i == totalP || (i >= current-1 && i <= current+1)) controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchReport(${i})">${i}</a></li>`); else if (i == current-2 || i == current+2) controls.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><a class="page-link">...</a></li>`); }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchReport(${current+1})"><i class="fas fa-chevron-right"></i></a></li>`);
        }

        function resetReportFilter() {
            document.getElementById('formFilterReport').reset();
            $('#report_daterange').data('daterangepicker').setStartDate(moment().startOf('month'));
            $('#report_daterange').data('daterangepicker').setEndDate(moment());
            $('#f_start').val(moment().startOf('month').format('YYYY-MM-DD'));
            $('#f_end').val(moment().format('YYYY-MM-DD'));
            selectSuperFilter('', '', '');
        }

        window.selectSuperFilter = (item, size, displayLabel) => {
            document.getElementById('f_item_val').value = item; document.getElementById('f_size_val').value = size;
            let label = item || 'Semua Item'; if(size) label = `${item} (${displayLabel})`;
            const labelEl = document.getElementById('sf-label'); labelEl.innerText = label;
            if (item) labelEl.classList.remove('text-muted'); else labelEl.classList.add('text-muted');
            fetchReport(1);
        };

        function printFormalReport() {
            const params = new URLSearchParams(new FormData(document.getElementById('formFilterReport')));
            window.open(`print_report.php?${params.toString()}`, '_blank');
        }

        $(document).on('change', '.auto-filter', () => { fetchReport(1); });
        fetchReport(1);
        document.querySelector('a[href="reports.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>