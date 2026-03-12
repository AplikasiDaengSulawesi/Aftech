<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
require_once '../includes/auth_check.php';
protect_page('shipment_reports');
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

                <!-- Filter Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div>
                                        <h4 class="text-black mb-0 font-w800">Database Pengiriman Distributor</h4>
                                        <p class="mb-0 small text-muted">Klik baris untuk opsi cetak nota, tambah unit susulan atau pembatalan</p>
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
                                    <div class="col-12 col-md-4">
                                        <input type="text" id="f_search" name="search" class="form-control form-control-sm" placeholder="Cari data...">
                                    </div>
                                    <!-- NATIVE BOOTSTRAP SUPER FILTER ITEM -->
                                    <div class="col-12 col-md-3">
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
                                    <div class="col-12 col-md-5">
                                        <input type="text" id="f_daterange" class="form-control form-control-sm daterange-picker" placeholder="Pilih Tanggal Pengiriman" readonly>
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
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 col-time"><strong>WAKTU PENGIRIMAN</strong></th>
                                                <th class="col-customer"><strong>CUSTOMER</strong></th>
                                                <th class="col-items"><strong>ITEM & UKURAN</strong></th>
                                                <th class="col-total"><strong>TOTAL DUS</strong></th>
                                                <th class="col-officer"><strong>DIKIRIM OLEH</strong></th>
                                            </tr>
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
            </div>
        </div>

        <!-- MODAL VIEW DETAIL PENGIRIMAN -->
        <div class="modal fade" id="modalViewShipment" tabindex="-1" aria-hidden="true">
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

        function renderSkeleton() {
            const tbody = document.getElementById('tableShipmentBody');
            tbody.innerHTML = '';
            for(let i=0; i<5; i++) {
                tbody.insertAdjacentHTML('beforeend', `<tr><td class="ps-4"><div class="skeleton" style="height:20px; width:100px;"></div></td><td><div class="skeleton skeleton-text"></div></td><td><div class="skeleton skeleton-text"></div></td><td><div class="skeleton skeleton-badge"></div></td><td><div class="skeleton skeleton-text"></div></td></tr>`);
            }
        }

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

        window.fetchShipments = async function(page = 1) {
            const params = new URLSearchParams(new FormData(document.getElementById('formFilter')));
            params.set('page', page); params.set('limit', 10);

            try {
                const res = await fetch(`../api/get_shipments.php?${params.toString()}&_nocache=${Date.now()}`, {
                    cache: 'no-store',
                    headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
                });
                const result = await res.json();
                
                // Update KPI Stats
                if (result.stats) {
                    document.getElementById('kpi-pengiriman').innerText = formatCompactNumber(result.stats.total_pengiriman);
                    document.getElementById('kpi-pengiriman').title = result.stats.total_pengiriman.toLocaleString('id-ID');
                    
                    document.getElementById('kpi-unit').innerText = formatCompactNumber(result.stats.total_unit);
                    document.getElementById('kpi-unit').title = result.stats.total_unit.toLocaleString('id-ID');
                    
                    document.getElementById('kpi-customer').innerText = formatCompactNumber(result.stats.total_customer);
                    document.getElementById('kpi-customer').title = result.stats.total_customer.toLocaleString('id-ID');

                    document.getElementById('kpi-repeat').innerText = formatCompactNumber(result.stats.total_repeat);
                    document.getElementById('kpi-repeat').title = result.stats.total_repeat.toLocaleString('id-ID');

                    if(result.stats.bulan) {
                        document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = result.stats.bulan);
                    }
                }

                if(result.data) {
                    window.latestData = result.data;
                    displayData(result.data);
                    setupPagination(result.pages, result.total, page);
                    window.currentPage = page;
                    applyColumnVisibility();
                }
            } catch (e) { console.error("Shipment AJAX Error:", e); }
        }

        // Auto-Polling
        setInterval(() => {
            if (!document.hidden) fetchShipments(window.currentPage);
        }, 3000);

        function displayData(data) {
            const tbody = document.getElementById('tableShipmentBody');
            tbody.innerHTML = data.length ? '' : '<tr><td colspan="5" class="text-center py-5 text-muted">Data Kosong.</td></tr>';
            
            data.forEach((row, index) => {
                // Item Aggregation Logic: Menggabungkan item yang sama namanya & ukurannya
                let itemsHTML = '';
                if (row.item_summary) {
                    const items = row.item_summary.split(';');
                    const aggregated = {};

                    items.forEach(it => {
                        const parts = it.split('|');
                        const nameSize = parts[0];
                        const count = parseInt(parts[1] || '0');
                        
                        if (aggregated[nameSize]) {
                            aggregated[nameSize] += count;
                        } else {
                            aggregated[nameSize] = count;
                        }
                    });

                    itemsHTML = Object.entries(aggregated).map(([nameSize, totalCount]) => {
                        return `<div class="mb-2"><div class="text-black font-w700" style="font-size:13px; line-height:1.1;">${nameSize}</div><small class="text-muted font-w600" style="font-size:11px;">${totalCount} Dus</small></div>`;
                    }).join('');
                }

                tbody.insertAdjacentHTML('beforeend', `
                    <tr onclick="showRowActions(${index})">
                        <td class="ps-4 col-time ${window.columnStates['col-time'] ? '' : 'col-hidden'}">
                            <span class="text-black font-w600" style="font-size:13px;">${row.shipped_at_formatted}</span><br>
                            <small class="text-muted font-w500">${row.shipped_time_formatted}</small>
                        </td>
                        <td class="col-customer ${window.columnStates['col-customer'] ? '' : 'col-hidden'}">
                            <div class="text-primary font-w700" style="font-size:14px;">${row.customer_name}</div>
                            <small class="text-muted"><i class="fa fa-phone-alt me-1" style="font-size:10px;"></i>${row.customer_contact || '-'}</small>
                        </td>
                        <td class="col-items ${window.columnStates['col-items'] ? '' : 'col-hidden'}">${itemsHTML || '-'}</td>
                        <td class="col-total ${window.columnStates['col-total'] ? '' : 'col-hidden'}">
                            ${parseInt(row.total_qty || 0) > 0 ? `<div class="badge badge-success text-white font-w800" style="font-size:12px; padding: 5px 10px; border-radius:6px;">${row.total_qty} Dus</div>` : `<span class="badge badge-danger text-white font-w800" style="font-size:11px; padding: 5px 10px; border-radius:6px;">RETURNED</span>`}
                        </td>
                        <td class="col-officer ${window.columnStates['col-officer'] ? '' : 'col-hidden'}"><small class="font-w600 text-black">${row.shipped_by}</small></td>
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
                        <small class="text-muted d-block mb-1">Customer</small>
                        <strong class="text-black" style="word-break:break-all;">${row.customer_name}</strong>
                    </div>
                    <div class="action-list">
                        <button onclick="Swal.close(); viewShipmentDetail(${index})" class="action-item"><i class="fa fa-eye icon-view"></i> Lihat Rincian Dus</button>
                        <button onclick="Swal.close(); window.location.href='shipment_scan.php?append_id=${row.id}'" class="action-item"><i class="fa fa-plus icon-append"></i> Tambah Dus Susulan</button>
                        <button onclick="Swal.close(); window.open('print_invoice.php?id=${row.id}', '_blank')" class="action-item"><i class="fa fa-print icon-print"></i> Cetak Surat Jalan</button>
                        <button onclick="Swal.close(); deleteShipment(${row.id}, '${row.customer_name}')" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Batalkan Pengiriman</button>
                    </div>
                `,
                showConfirmButton: false, padding: '1.2rem', width: '320px', borderRadius: '15px'
            });
        };

        window.viewShipmentDetail = async function(index) {
            const row = window.latestData[index];
            const modalEl = document.getElementById('modalViewShipment');
            const contentEl = document.getElementById('viewDetailContent');
            contentEl.innerHTML = '<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Memuat rincian...</p></div>';
            new bootstrap.Modal(modalEl).show();

            try {
                const res = await fetch(`../api/get_shipment_details.php?id=${row.id}`);
                const result = await res.json();

                if (result.status === 'success') {
                    let tableRows = '';
                    let totalLabel = 0;
                    let totalUnit = 0;

                    result.data.forEach((item, i) => {
                        totalLabel += parseInt(item.label_qty);
                        tableRows += `
                            <tr>
                                <td class="text-muted align-middle text-center" style="font-size: 12px;">${i + 1}</td>
                                <td class="align-middle">
                                    <div class="text-black font-w700" style="font-size: 13px;">${item.item}</div>
                                    <div class="text-muted" style="font-size: 11px;">${item.size} ${item.unit} &bull; <span class="text-primary font-w600">#${item.batch}</span></div>
                                </td>
                                <td class="text-center align-middle font-w600 text-black" style="font-size: 13px;">
                                    ${item.label_qty} Dus
                                </td>
                                <td class="text-end align-middle">
                                    <button onclick="returnShipmentItem(${item.shipment_id}, ${item.production_id}, '${item.batch}', ${item.label_qty})" class="btn btn-danger btn-xxs shadow-sm text-white font-w600 px-2" style="font-size:10px; border-radius:4px;">
                                        <i class="fa fa-undo me-1"></i> Return
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    contentEl.innerHTML = `
                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded" style="height: 100%;">
                                    <small class="text-muted text-uppercase d-block mb-2" style="font-size:10px; font-weight:700; letter-spacing:1px;">Dikirim Kepada</small>
                                    <div class="text-black font-w800 mb-2" style="font-size: 16px;">${row.customer_name}</div>
                                    <div class="d-flex align-items-center text-muted mb-1" style="font-size: 12px;">
                                        <i class="fa fa-phone-alt me-2 text-primary" style="width: 14px; text-align: center;"></i> ${row.customer_contact || '-'}
                                    </div>
                                    <div class="d-flex align-items-start text-muted" style="font-size: 12px; line-height: 1.4;">
                                        <i class="fa fa-map-marker-alt me-2 mt-1 text-primary" style="width: 14px; text-align: center;"></i> 
                                        <span>${row.customer_address || '-'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded" style="height: 100%;">
                                    <small class="text-muted text-uppercase d-block mb-2" style="font-size:10px; font-weight:700; letter-spacing:1px;">Detail Dokumen</small>
                                    <table class="w-100" style="font-size: 12px;">
                                        <tr><td class="text-muted pb-1" style="width: 80px;">No. Resi</td><td class="pb-1 text-black font-w700">#${row.no_resi}</td></tr>
                                        <tr><td class="text-muted pb-1">Status</td><td class="pb-1"><span class="badge ${parseInt(row.total_qty) === 0 ? 'badge-danger' : 'badge-success'} light" style="font-size: 10px;">${parseInt(row.total_qty) === 0 ? 'DI-RETURN' : 'TERKIRIM'}</span></td></tr>
                                        <tr><td class="text-muted pb-1">Waktu</td><td class="text-black font-w600 pb-1">${row.shipped_at_formatted} | <span class="text-primary">${row.shipped_time_formatted}</span></td></tr>
                                        <tr><td class="text-muted">Petugas</td><td class="text-black font-w600">${row.shipped_by}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-black font-w800 mb-0" style="font-size: 14px;">Rincian Barang & Kendali Return</h6>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover mb-0" style="table-layout: fixed; width: 100%;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 10%;">NO</th>
                                        <th class="text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 45%;">ITEM & BATCH</th>
                                        <th class="text-center text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 20%;">JUMLAH</th>
                                        <th class="text-end text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 25%;">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows || '<tr><td colspan="4" class="text-center py-5 text-muted">Seluruh item telah di-return.</td></tr>'}
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="2" class="text-end py-3 text-black font-w800" style="font-size: 12px; border-bottom: 0;">TOTAL BERSIH</td>
                                        <td class="text-center py-3 text-black font-w800" style="font-size: 14px; border-bottom: 0;">${totalLabel}</td>
                                        <td class="text-end py-3 ${totalLabel === 0 ? 'text-danger' : 'text-primary'} font-w800" style="font-size: 16px; border-bottom: 0;">${totalLabel} Dus</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                } else {
                    contentEl.innerHTML = `<div class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2"></i><br>${result.message}</div>`;
                }
            } catch (e) {
                contentEl.innerHTML = '<div class="text-center text-danger py-4"><i class="fa fa-wifi fa-2x mb-2"></i><br>Gagal terhubung ke server.</div>';
            }
        };

        window.returnShipmentItem = function(shipmentId, prodId, batchCode, qty) {
            Swal.fire({
                title: 'Return ke Gudang?',
                text: `Anda akan mengembalikan ${qty} dus dari batch #${batchCode} kembali ke stok gudang. Lanjutkan?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FFA000',
                confirmButtonText: 'Ya, Return Barang',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const f = new FormData();
                    f.append('shipment_id', shipmentId);
                    f.append('production_id', prodId);
                    const res = await fetch(`../api/manage_settings.php?action=delete&type=shipment_item`, { method: 'POST', body: f });
                    const data = await res.json();
                    if(data.status === 'success') {
                        toastr.success(`Batch #${batchCode} berhasil di-return ke gudang`);
                        // Refresh modal rincian tanpa menutupnya
                        const modalEl = document.getElementById('modalViewShipment');
                        const resiIndex = window.latestData.findIndex(r => r.id == shipmentId);
                        if (resiIndex !== -1) {
                            viewShipmentDetail(resiIndex);
                        }
                        fetchShipments(window.currentPage);
                    } else {
                        toastr.error(data.message);
                    }
                }
            });
        };

        window.deleteShipment = function(id, name) {
            Swal.fire({ 
                title: 'Batalkan Pengiriman?', 
                text: "Barang akan dikembalikan ke stok gudang.", 
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#D50000', confirmButtonText: 'Ya, Batalkan' 
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const res = await fetch(`../api/manage_settings.php?action=delete&type=shipment`, { method: 'POST', body: f });
                    const data = await res.json();
                    if(data.status === 'success') { 
                        toastr.success('Data Berhasil Dibatalkan'); 
                        fetchShipments(window.currentPage); 
                    } else {
                        toastr.error(data.message);
                    }
                }
            });
        };

        function setupPagination(totalP, totalD, current) {
            const controls = document.getElementById('paginationControls');
            document.getElementById('paginationInfo').innerText = `Data ${(current-1)*10 + 1}-${Math.min(current*10, totalD)} dari ${totalD}`;
            controls.innerHTML = '';
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == 1 ? 'disabled' : ''}"><a class="page-link" onclick="fetchShipments(${current-1})"><i class="fas fa-chevron-left"></i></a></li>`);
            for (let i = 1; i <= totalP; i++) {
                if (i == 1 || i == totalP || (i >= current-1 && i <= current+1))
                    controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == i ? 'active' : ''}"><a class="page-link" onclick="fetchShipments(${i})">${i}</a></li>`);
                else if (i == current - 2 || i == current + 2)
                    controls.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><a class="page-link">...</a></li>`);
            }
            controls.insertAdjacentHTML('beforeend', `<li class="page-item ${current == totalP ? 'disabled' : ''}"><a class="page-link" onclick="fetchShipments(${current+1})"><i class="fas fa-chevron-right"></i></a></li>`);
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
            if(dropdownEl && dropdownEl.classList.contains('show')) {
                dropdownEl.querySelector('[data-bs-toggle="dropdown"]').click();
            }
            fetchShipments(1);
        };

        window.resetFilter = () => { document.getElementById('formFilter').reset(); $('#f_daterange').val(''); $('#f_start').val(''); $('#f_end').val(''); selectSuperFilter('', '', ''); }
        document.getElementById('f_search').oninput = () => { clearTimeout(window.sT); window.sT = setTimeout(() => { fetchShipments(1); }, 500); }
        $(document).on('change', '.auto-filter', () => { fetchShipments(1); });

        $('#f_daterange').daterangepicker({ autoUpdateInput: false, locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' } });
        $('#f_daterange').on('apply.daterangepicker', function(ev, picker) { $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD')); $('#f_start').val(picker.startDate.format('YYYY-MM-DD')); $('#f_end').val(picker.endDate.format('YYYY-MM-DD')); fetchShipments(1); });
        $('#f_daterange').on('cancel.daterangepicker', function(ev, picker) { $(this).val(''); $('#f_start').val(''); $('#f_end').val(''); fetchShipments(1); });

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

        fetchShipments();
        document.querySelector('a[href="shipment_data.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>