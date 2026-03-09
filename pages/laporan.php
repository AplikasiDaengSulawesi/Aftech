<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
$role = $_SESSION['role'] ?? 'gudang';
if($role !== 'admin') { header("Location: index.php"); exit; }
require_once '../includes/db.php';

$m_items = $pdo->query("SELECT name FROM master_items ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
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
                
                <!-- PRINT ONLY HEADER -->
                <div class="print-header">
                    <h2 style="margin:0; font-weight:900;">PT AFTECH MAKASSAR INDONESIA</h2>
                    <h4 style="margin:5px 0; color:#1A237E;">LAPORAN REKAPITULASI PRODUKSI & GUDANG</h4>
                    <p style="margin:0; font-size:12px; color:#666;">Dicetak pada: <?php echo date('d/m/Y H:i'); ?></p>
                </div>

                <!-- KPI WIDGETS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card bg-primary shadow-sm card-kpi">
                            <div class="card-body p-4">
                                <div class="media">
                                    <span class="me-3"><i class="fa fa-boxes"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Produksi</p>
                                        <h3 class="text-white mb-0" id="stat-produced">0</h3>
                                        <small class="d-block mt-1">Unit Terdaftar</small>
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
                                    <span class="me-3"><i class="fa fa-check-circle"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Verifikasi</p>
                                        <h3 class="text-white mb-0" id="stat-verified">0</h3>
                                        <small class="d-block mt-1">Lolos QC (Gudang)</small>
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
                                    <span class="me-3"><i class="fa fa-truck"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Total Terkirim</p>
                                        <h3 class="text-white mb-0" id="stat-shipped">0</h3>
                                        <small class="d-block mt-1">Keluar ke Distributor</small>
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
                                    <span class="me-3"><i class="fa fa-warehouse"></i></span>
                                    <div class="media-body text-white text-end">
                                        <p class="mb-1 text-white font-w600">Sisa Stok</p>
                                        <h3 class="text-white mb-0" id="stat-stock">0</h3>
                                        <small class="d-block mt-1">Tersedia di Gudang</small>
                                        <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FILTER SECTION -->
                <div class="row btn-print-area">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div>
                                        <h4 class="text-black mb-0 font-w800">Laporan Rekapitulasi</h4>
                                        <p class="mb-0 small text-muted">Ringkasan produksi, verifikasi, dan pengiriman barang.</p>
                                    </div>
                                    <div class="header-btn-group">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-xs shadow-sm dropdown-toggle font-w600 w-100" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-columns me-1 text-primary"></i> Pilih Kolom
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end column-toggle-dropdown p-3 shadow-lg">
                                                <h6 class="dropdown-header ps-0 mb-2 font-w700 text-black">Tampilkan:</h6>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-date" id="chk-date" checked><label class="form-check-label small font-w600" for="chk-date">Tanggal</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-item" id="chk-item-col" checked><label class="form-check-label small font-w600" for="chk-item-col">Item & Ukuran</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-prod" id="chk-produced" checked><label class="form-check-label small font-w600" for="chk-produced">Produksi</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-ver" id="chk-verified" checked><label class="form-check-label small font-w600" for="chk-verified">Verified</label></div>
                                                <div class="form-check mb-2"><input class="form-check-input col-checkbox" type="checkbox" value="col-ship" id="chk-shipped" checked><label class="form-check-label small font-w600" for="chk-shipped">Terkirim</label></div>
                                                <div class="form-check"><input class="form-check-input col-checkbox" type="checkbox" value="col-stock" id="chk-stock" checked><label class="form-check-label small font-w600" for="chk-stock">Stok Akhir</label></div>
                                            </div>
                                        </div>
                                        <button onclick="resetReportFilter()" class="btn btn-light btn-xs shadow-sm text-danger font-w600">
                                            <i class="fa fa-undo me-1"></i> Reset Filter
                                        </button>
                                    </div>
                                </div>
                                <form id="formFilterReport" class="row g-2 align-items-center">
                                    <div class="col-12 col-md-3">
                                        <input type="text" id="report_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Periode" readonly>
                                        <input type="hidden" name="start_date" id="f_start" value="<?php echo date('Y-m-01'); ?>">
                                        <input type="hidden" name="end_date" id="f_end" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <select name="item" id="f_item" class="form-control form-control-sm default-select auto-filter">
                                            <option value="">Semua Item</option>
                                            <?php foreach($m_items as $i) echo "<option value='".htmlspecialchars($i['name'])."'>".htmlspecialchars($i['name'])."</option>"; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button type="button" onclick="printFormalReport()" class="btn btn-primary btn-sm w-100 font-w600">
                                            <i class="fa fa-print me-1"></i> Cetak Laporan
                                        </button>
                                    </div>
                                    <div class="col-12 col-md-3 d-flex align-items-center">
                                        <div class="form-check custom-checkbox">
                                            <input type="checkbox" class="form-check-input auto-filter" id="f_all_time" name="show_all" value="true">
                                            <label class="form-check-label small font-w700 text-black" for="f_all_time">Semua Data</label>
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
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 text-center col-no" style="width: 50px;"><strong>No</strong></th>
                                                <th class="col-date"><strong>TANGGAL / BATCH</strong></th>
                                                <th class="col-item"><strong>NAMA ITEM & UKURAN</strong></th>
                                                <th class="text-center col-prod"><strong>PRODUKSI (UNIT)</strong></th>
                                                <th class="text-center col-ver"><strong>VERIFIED (UNIT)</strong></th>
                                                <th class="text-center col-ship"><strong>TERKIRIM (UNIT)</strong></th>
                                                <th class="text-end pe-4 col-stock"><strong>STOK GUDANG</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <tr><td colspan="7" class="text-center py-5">Memuat data laporan...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.columnStates = { 'col-no': true, 'col-date': true, 'col-item': true, 'col-prod': true, 'col-ver': true, 'col-ship': true, 'col-stock': true };

        // Setup DateRangePicker
        $('#report_daterange').daterangepicker({
            startDate: moment().startOf('month'),
            endDate: moment(),
            locale: { format: 'DD/MM/YYYY' }
        }, function(start, end) {
            $('#f_start').val(start.format('YYYY-MM-DD'));
            $('#f_end').val(end.format('YYYY-MM-DD'));
            document.getElementById('f_all_time').checked = false; // Uncheck "Show All" if date is picked
            fetchReport();
        });

        function formatNumber(num) {
            return parseInt(num).toLocaleString('id-ID');
        }

        async function fetchReport() {
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i></td></tr>';
            
            const params = new URLSearchParams(new FormData(document.getElementById('formFilterReport')));
            if (document.getElementById('f_all_time').checked) {
                params.set('show_all', 'true');
            }
            
            try {
                const res = await fetch(`../api/get_reports.php?${params.toString()}`);
                const result = await res.json();
                
                if (result.status === 'success') {
                    // Update Summary
                    document.getElementById('stat-produced').innerText = formatNumber(result.summary.produced);
                    document.getElementById('stat-verified').innerText = formatNumber(result.summary.verified);
                    document.getElementById('stat-shipped').innerText = formatNumber(result.summary.shipped);
                    document.getElementById('stat-stock').innerText = formatNumber(result.summary.stock);

                    if(result.summary.bulan) {
                        const label = result.summary.bulan.includes('Filter') || result.summary.bulan.includes('Semua') ? result.summary.bulan : `(${result.summary.bulan})`;
                        document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = label);
                    }
                    
                    // Render Table
                    tbody.innerHTML = '';
                    if (result.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data untuk periode ini.</td></tr>';
                        return;
                    }
                    
                    const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
                    
                    result.data.forEach((row, i) => {
                        const d = new Date(row.production_date);
                        const formattedDate = `${d.getDate()} ${monthNamesShort[d.getMonth()]} ${d.getFullYear()}`;

                        const shippedDisplay = (parseInt(row.shipped_qty) > 0) 
                            ? `<strong>${formatNumber(row.shipped_qty)}</strong>` 
                            : `<small class="text-muted italic" style="font-size:10px;">Belum Terkirim</small>`;
                        
                        const stockDisplay = (parseInt(row.stock_qty) > 0)
                            ? `<span class="badge badge-primary light font-w800" style="font-size:13px;">${formatNumber(row.stock_qty)}</span>`
                            : `<span class="badge badge-light text-danger font-w800" style="font-size:11px;">Kosong</span>`;

                        tbody.insertAdjacentHTML('beforeend', `
                            <tr>
                                <td class="text-center ps-4 col-no ${window.columnStates['col-no'] ? '' : 'col-hidden'}">${i + 1}</td>
                                <td class="col-date ${window.columnStates['col-date'] ? '' : 'col-hidden'}">
                                    <span class="font-w600 text-black">${formattedDate}</span><br>
                                    <span class="badge badge-light border text-primary" style="font-size:9px; padding:2px 5px;">#${row.batch}</span>
                                </td>
                                <td class="col-item ${window.columnStates['col-item'] ? '' : 'col-hidden'}">
                                    <div class="text-black font-w700">${row.item}</div>
                                    <small class="text-muted">${row.size} ${row.unit}</small>
                                </td>
                                <td class="text-center col-prod ${window.columnStates['col-prod'] ? '' : 'col-hidden'}"><strong>${formatNumber(row.produced_qty)}</strong></td>
                                <td class="text-center col-ver ${window.columnStates['col-ver'] ? '' : 'col-hidden'}"><span class="text-primary font-w700">${formatNumber(row.verified_qty)}</span></td>
                                <td class="text-center col-ship ${window.columnStates['col-ship'] ? '' : 'col-hidden'}"><span class="text-success">${shippedDisplay}</span></td>
                                <td class="text-end pe-4 col-stock ${window.columnStates['col-stock'] ? '' : 'col-hidden'}">${stockDisplay}</td>
                            </tr>
                        `);
                    });
                    applyColumnVisibility();
                }
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Gagal mengambil data laporan.</td></tr>';
            }
        }

        function resetReportFilter() {
            document.getElementById('formFilterReport').reset();
            $('#report_daterange').data('daterangepicker').setStartDate(moment().startOf('month'));
            $('#report_daterange').data('daterangepicker').setEndDate(moment());
            $('#f_start').val(moment().startOf('month').format('YYYY-MM-DD'));
            $('#f_end').val(moment().format('YYYY-MM-DD'));
            fetchReport();
        }

        function printFormalReport() {
            const params = new URLSearchParams(new FormData(document.getElementById('formFilterReport')));
            if (document.getElementById('f_all_time').checked) {
                params.set('show_all', 'true');
            }
            window.open(`print_report.php?${params.toString()}`, '_blank');
        }

        $(document).on('change', '.auto-filter', () => { fetchReport(); });

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

        // Initial fetch
        fetchReport();
        document.querySelector('a[href="laporan.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>