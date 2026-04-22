<!DOCTYPE html>
<html lang="en">
<?php
include '../includes/header.php';
require_once '../includes/db.php';

$append_id = isset($_GET['append_id']) ? (int)$_GET['append_id'] : 0;
$append_data = null;
if ($append_id > 0) {
    $stmt = $pdo->prepare("SELECT customer_name, customer_contact, customer_address, shipment_date FROM outbound_shipments WHERE id = ?");
    $stmt->execute([$append_id]);
    $append_data = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
    .shipment-top-row { align-items: stretch; }
    .shipment-panel-col { display: flex; }
    .scanner-container { position: sticky; top: 90px; z-index: 1; width: 100%; height: 100%; display: flex; }
    .scanner-container > .card { width: 100%; height: 100%; }
    #reader { width: 100%; border-radius: 15px; overflow: hidden; border: 3px solid #1A237E !important; background: #000; min-height: 250px; }
    #reader video { width: 100% !important; height: auto !important; object-fit: cover !important; border-radius: 12px; }
    
    .form-shipment-card { position: relative; z-index: 1; width: 100%; display: flex; flex-direction: column; }
    .form-shipment-card .card-body { display: flex; flex: 1 1 auto; flex-direction: column; }
    .form-shipment-card #shipmentForm { display: flex; flex: 1 1 auto; flex-direction: column; }
    .shipment-form-actions { margin-top: auto; }
    
    .status-panel { 
        min-height: 80px; padding: 15px; border-radius: 10px; margin-top: 15px; 
        display: flex; align-items: center; gap: 12px; transition: all 0.3s ease;
        border: 1px solid #eee; background: #fff;
    }
    .status-panel.bg-success-light { background: #E8F5E9; border-color: #00C853; }
    .status-panel.bg-danger-light { background: #FFEBEE; border-color: #D50000; }
    .status-panel.bg-warning-light { background: #FFF8E1; border-color: #FFC107; }
    
    .cam-controls { display: flex; gap: 5px; margin-top: 10px; }
    #camera-select { font-size: 12px; height: 35px; border-radius: 8px; border: 1px solid #1A237E; }
    
    /* Grid Styles */
    .cinema-grid-bulk { 
        display: grid; grid-template-columns: repeat(auto-fill, minmax(32px, 1fr)); 
        gap: 5px; max-height: 300px; overflow-y: auto; padding: 15px;
        background: #f8f9fa; border-radius: 10px; user-select: none;
    }
    .seat-bulk { 
        height: 32px; display: flex; align-items: center; justify-content: center; 
        background: #eee; border-radius: 4px; font-size: 10px; font-weight: bold; 
        color: #aaa; cursor: not-allowed; border: 1px solid #ddd; transition: 0.1s;
    }
    .seat-bulk.available { background: #fff; color: #00C853; border-color: #00C853; cursor: pointer; }
    .seat-bulk.selected { background: #1A237E !important; color: #fff !important; border-color: #1A237E !important; transform: scale(0.92); }
    .seat-bulk.shipped { background: #D50000; color: #fff; border-color: #b71c1c; opacity: 0.5; }
    
    .batch-card { border: 1px solid #e0e0e0; border-radius: 15px; padding: 15px; margin-bottom: 15px; }

    .card-kpi { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .card-kpi .card-body { padding: 1.5rem; }
    .kpi-title-month { font-size: 11px; opacity: 0.9; font-weight: 500; color: #fff !important; }

    /* Tombol Toggle Custom */
    .btn-toggle-select { color: var(--af-primary) !important; border-color: var(--af-primary) !important; background-color: transparent !important; }
    .btn-toggle-select i, .btn-toggle-select span { color: var(--af-primary) !important; }
    .btn-toggle-select:hover { background-color: var(--af-primary) !important; color: #fff !important; }
    .btn-toggle-select:hover i, .btn-toggle-select:hover span { color: #fff !important; }
    
    .btn-toggle-deselect { color: var(--af-danger) !important; border-color: var(--af-danger) !important; background-color: transparent !important; }
    .btn-toggle-deselect i, .btn-toggle-deselect span { color: var(--af-danger) !important; }
    .btn-toggle-deselect:hover { background-color: var(--af-danger) !important; color: #fff !important; }
    .btn-toggle-deselect:hover i, .btn-toggle-deselect:hover span { color: #fff !important; }

    /* Tabs Styling */
    .cart-tabs-wrapper { display: flex; overflow-x: auto; gap: 8px; padding-bottom: 10px; }
    .cart-tabs-wrapper::-webkit-scrollbar { height: 4px; }
    .cart-tabs-wrapper::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px; }
    .cart-tab { white-space: nowrap; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid var(--af-primary); }
    .cart-tab.active, .cart-tab.active span, .cart-tab.active i { background-color: var(--af-primary); color: #fff !important; }
    .cart-tab.inactive { background-color: #fff; color: var(--af-primary); }
    .cart-tab-add { background-color: #f8f9fa; color: var(--af-primary); border: 1px dashed var(--af-primary); }
    .cart-tab i.fa-times { margin-left: 8px; cursor: pointer; opacity: 0.7; }
    .cart-tab i.fa-times:hover { opacity: 1; color: #ffcccc; }

    /* ACTION MENU */
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

    /* SHIPMENT CARD COLLAPSE (per baris pengiriman) */
    .shipment-col-header { font-size: 11px; color: #666; letter-spacing: 0.5px; text-transform: uppercase; }
    .sh-col { padding: 0 8px; }
    .sh-col.col-time { flex: 0 0 18%; }
    .sh-col.col-customer { flex: 1 1 auto; }
    .sh-col.col-method { flex: 0 0 14%; }
    .sh-col.col-total { flex: 0 0 12%; }
    .sh-col.col-by { flex: 0 0 14%; }
    .sh-col.col-chev { flex: 0 0 40px; text-align: center; }

    .shipment-card { border: 1px solid #e0e4ec; border-radius: 10px; overflow: hidden; background: #fff; margin-bottom: 8px; transition: box-shadow 0.2s; }
    .shipment-card:hover { box-shadow: 0 2px 8px rgba(26, 35, 126, 0.08); }
    .shipment-card.open { border-color: #1A237E; box-shadow: 0 4px 16px rgba(26, 35, 126, 0.12); }
    .shipment-card-header { padding: 14px 12px; cursor: pointer; display: flex; align-items: center; transition: background 0.15s; }
    .shipment-card-header:hover { background: #f8f9ff; }
    .shipment-card.open .shipment-card-header { background: #eef2ff; border-bottom: 1px solid #e0e4ec; }
    .shipment-card-body { padding: 20px; background: #fafbfc; }
    .shipment-card-body.d-none { display: none !important; }
    .shipment-method-badge {
        display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 999px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.2px; border: 1px solid transparent;
    }
    .shipment-method-badge.scan { background: #e8f5e9; color: #1b5e20; border-color: #c8e6c9; }
    .shipment-method-badge.manual { background: #fff8e1; color: #ef6c00; border-color: #ffe0b2; }
    .shipment-method-badge.campuran { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
    .shipment-method-badge.legacy { background: #f5f5f5; color: #616161; border-color: #e0e0e0; }

    .ship-chev { transition: transform 0.25s ease; color: #1A237E; font-size: 13px; display: inline-block; }
    .ship-chev.rotated { transform: rotate(180deg); }

    .batch-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 8px; }
    .batch-detail-row { padding: 10px 12px; border: 1px solid #e0e4ec; border-radius: 8px; background: #fff; min-width: 0; }
    .batch-meta-line { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
    .batch-meta-main { min-width: 0; }
    .batch-meta-info { font-size: 11px; color: #666; line-height: 1.35; }
    .batch-chip {
        font-family: 'Courier New', monospace; font-size: 9px; background: #eef2ff;
        color: #1A237E; padding: 2px 6px; border-radius: 999px; display: inline-block;
        margin: 1px; font-weight: 600; border: 1px solid #d6dcff;
    }
    .batch-chips { max-height: 44px; overflow: hidden; transition: max-height 0.25s ease; }
    .batch-chips.expanded { max-height: 220px; overflow-y: auto; }

    @media (max-width: 991px) {
        .shipment-panel-col { display: block; }
        .shipment-card-header { flex-wrap: wrap; }
        .sh-col.col-time, .sh-col.col-customer, .sh-col.col-method,
        .sh-col.col-total, .sh-col.col-by { flex: 0 0 50%; padding: 4px 8px; }
        .sh-col.col-chev { flex: 0 0 100%; text-align: center; padding-top: 4px; }
        .batch-meta-line { align-items: flex-start; }
        .batch-detail-grid { grid-template-columns: 1fr; }
    }

    /* RESPONSIVE MOBILE/TABLET ADJUSTMENTS */
    @media (max-width: 991px) {
        .scanner-container { position: static; margin-bottom: 20px; z-index: 0; }
        .cinema-grid-bulk { grid-template-columns: repeat(auto-fill, minmax(26px, 1fr)); padding: 10px; gap: 4px; }
        .seat-bulk { height: 28px; font-size: 9px; }
        .card-header h4 { font-size: 16px; }
    }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        <div class="content-body">
            <div class="container-fluid">
            
                <div class="row shipment-top-row">
                    <!-- SCANNER -->
                    <div class="col-xl-4 col-lg-5 col-md-12 mb-4 mb-lg-0 shipment-panel-col">
                        <div class="scanner-container">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header border-0 pb-0">
                                    <h4 class="card-title text-black">Quick Scan Unit</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info alert-dismissible fade show small py-2 px-3 mb-3 d-flex align-items-center" role="alert">
                                        <i class="fa fa-info-circle me-2 fs-5"></i>
                                        <div style="padding-right: 20px;"><strong>Tips:</strong> Untuk kenyamanan, sangat disarankan untuk mengunci rotasi layar HP/Tablet Anda.</div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.75rem 1rem;"></button>
                                    </div>
                                    <div id="reader"></div>
                                    
                                    <div class="cam-controls">
                                        <select id="camera-select" class="form-control" onchange="switchCamera(this.value)">
                                            <option value="">Mencari Kamera...</option>
                                        </select>
                                    </div>

                                    <div class="text-center mt-3" id="start-btn-area">
                                        <button class="btn btn-primary btn-sm w-100" onclick="initCamera()">
                                            <i class="fa fa-camera me-2"></i> Aktifkan Kamera
                                        </button>
                                    </div>

                                    <div id="status-display" class="status-panel mt-3">
                                        <div id="status-icon"><i class="fa fa-qrcode fa-2x text-muted"></i></div>
                                        <div id="status-text">
                                            <div class="font-w600 text-black">Siap Scan</div>
                                            <small class="text-muted">Arahkan ke QR Code Unit</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm w-100 mt-2" onclick="resumeScanner()">Resume Scanner</button>

                                    <div class="d-flex align-items-center my-3">
                                        <hr class="flex-grow-1 my-0">
                                        <small class="text-muted px-2">atau</small>
                                        <hr class="flex-grow-1 my-0">
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="openManualModal()">
                                        <i class="fa fa-keyboard me-2"></i> Input Manual (Tanpa Barcode)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FORM PENGIRIMAN BULK -->
                    <div class="col-xl-8 col-lg-7 mb-4 mb-lg-0 shipment-panel-col">
                        <div class="card shadow-sm border-0 h-100 form-shipment-card">
                            <div class="card-header border-bottom flex-column align-items-start">
                                <div class="d-flex justify-content-between w-100 mb-3">
                                    <h4 class="card-title text-black">
                                        <?php if($append_id > 0): ?>
                                            Susulan Nota #<?php echo $append_id; ?>
                                        <?php else: ?>
                                            Keranjang Pengiriman
                                        <?php endif; ?>
                                    </h4>
                                    <span class="badge badge-primary" id="total-badge" style="font-size: 14px;">Total Keluar: 0</span>
                                </div>
                                <?php if($append_id == 0): ?>
                                <!-- TABS MULTI-CART -->
                                <div class="cart-tabs-wrapper w-100" id="cart-tabs-container">
                                    <!-- Rendered dynamically -->
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if($append_id > 0): ?>
                                <div class="alert alert-info py-2 px-3 mb-3 border-0" style="font-size: 13px;">
                                    <i class="fa fa-info-circle me-2"></i> Mode Tambah Susulan aktif. Scan barang baru untuk ditambahkan ke pengiriman <strong><?php echo htmlspecialchars($append_data['customer_name']); ?></strong>.
                                </div>
                                <?php endif; ?>
                                <form id="shipmentForm" onsubmit="submitBulkShipment(event)">
                                    <div class="row mb-2">
                                        <div class="col-md-5 mb-2 position-relative">
                                            <label class="form-label font-w600 text-black" style="font-size: 12px;">Nama Customer <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-sm border-primary" id="customer_name" required autocomplete="off" onkeyup="searchCustomer(this.value)" onfocus="searchCustomer(this.value)" <?php echo $append_id > 0 ? 'readonly value="'.htmlspecialchars($append_data['customer_name']).'"' : ''; ?>>
                                            <!-- Dropdown Sugesti -->
                                            <div id="customer-suggestions" class="list-group position-absolute w-100 shadow-lg" style="display:none; z-index: 9999; max-height: 250px; overflow-y: auto; background: #fff; border: 1px solid #1A237E; border-radius: 8px; margin-top: 4px;"></div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label font-w600 text-black" style="font-size: 12px;">No. HP</label>
                                            <input type="text" class="form-control form-control-sm" id="customer_contact" autocomplete="off" <?php echo $append_id > 0 ? 'readonly value="'.htmlspecialchars($append_data['customer_contact']).'"' : ''; ?>>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label font-w600 text-black" style="font-size: 12px;">Tanggal <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control form-control-sm border-primary" id="shipment_date" required <?php echo $append_id > 0 ? 'readonly value="'.htmlspecialchars($append_data['shipment_date']).'"' : ''; ?>>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <label class="form-label font-w600 text-black" style="font-size: 12px;">Alamat</label>
                                            <input type="text" class="form-control form-control-sm" id="customer_address" autocomplete="off" <?php echo $append_id > 0 ? 'readonly value="'.htmlspecialchars($append_data['customer_address']).'"' : ''; ?>>
                                        </div>
                                    </div>
                                    
                                    <hr class="mt-1 mb-3">
                                    <h5 class="text-black mb-3"><i class="fa fa-boxes me-2 text-primary"></i>Daftar Dus Keluar</h5>
                                    
                                    <div id="empty-cart" class="text-center py-4">
                                        <i class="fa fa-box-open fa-3x text-light mb-3"></i>
                                        <p class="text-muted">Scan barcode paket unit untuk memasukkannya ke dalam keranjang aktif.</p>
                                    </div>

                                    <div id="cart-container">
                                        <!-- Grid visual per batch akan dirender di sini -->
                                    </div>

                                    <div class="mt-4 pt-3 border-top shipment-form-actions">
                                        <div class="row g-2">
                                            <div class="col-12 col-sm-8">
                                                <button type="submit" class="btn btn-primary shadow-sm w-100" id="btn-submit" disabled style="height: 42px; font-weight: 600; font-size: 12px; border-radius: 8px;">
                                                    Proses Pengiriman
                                                </button>
                                            </div>
                                            <div class="col-12 col-sm-4">
                                                <?php if($append_id > 0): ?>
                                                <button type="button" class="btn btn-danger w-100 shadow-sm" onclick="window.location.href='shipment_data.php'" style="height: 42px; font-weight: 600; font-size: 12px; border-radius: 8px;">
                                                    Batalkan
                                                </button>
                                                <?php else: ?>
                                                <button type="button" class="btn btn-light w-100 border" onclick="clearCart()" style="height: 42px; font-weight: 600; font-size: 12px; border-radius: 8px; background: #fdfdfd; color: #d32f2f;">
                                                    Kosongkan
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIST HISTORI PENGIRIMAN (CARD COLLAPSE) -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-3">
                                <div class="shipment-col-header d-none d-lg-flex px-3 py-2 bg-light rounded mb-2">
                                    <div class="sh-col col-time"><strong>WAKTU PENGIRIMAN</strong></div>
                                    <div class="sh-col col-customer"><strong>CUSTOMER</strong></div>
                                    <div class="sh-col col-method"><strong>INPUT</strong></div>
                                    <div class="sh-col col-total"><strong>TOTAL DUS</strong></div>
                                    <div class="sh-col col-by"><strong>DIKIRIM OLEH</strong></div>
                                    <div class="sh-col col-chev"></div>
                                </div>
                                <div id="historyListContainer">
                                    <div class="text-center py-5 text-muted">Memuat data histori...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL INPUT MANUAL (TANPA BARCODE) -->
        <div class="modal fade" id="modalManualAdd" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                    <div class="modal-header bg-primary text-white border-0" style="border-radius: 20px 20px 0 0;">
                        <h5 class="modal-title text-white font-w700"><i class="fa fa-keyboard me-2"></i>Input Manual (Tanpa Barcode)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 px-3 mb-3 border-0" style="font-size: 13px;">
                            <i class="fa fa-info-circle me-2"></i> Pilih batch yang akan ditambahkan ke keranjang, lalu pilih nomor dus di grid.
                        </div>
                        <label class="form-label font-w600 text-black" style="font-size: 12px;">Cari Batch / Item / Ukuran</label>
                        <div class="position-relative">
                            <input type="text" class="form-control border-primary" id="manualSearchInput" placeholder="Ketik batch, nama item, atau ukuran..." autocomplete="off">
                            <i class="fa fa-search position-absolute" style="right: 12px; top: 12px; color: #1A237E;"></i>
                        </div>
                        <div class="mt-3" id="manualSearchResults" style="max-height: 400px; overflow-y: auto;">
                            <!-- Results rendered here -->
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
        let html5QrCode;
        let isProcessing = false;
        let isDragging = false;
        const statusPanel = document.getElementById('status-display');
        const cameraSelect = document.getElementById('camera-select');
        const cartContainer = document.getElementById('cart-container');
        const tabsContainer = document.getElementById('cart-tabs-container');
        
        // --- MULTI-CART STATE ---
        let cartCounter = 1;
        let activeCartId = 1;
        let carts = {
            1: {
                name: 'Keranjang 1',
                customer_name: '<?php echo ($append_id > 0 && $append_data) ? addslashes($append_data['customer_name']) : ''; ?>',
                customer_contact: '<?php echo ($append_id > 0 && $append_data) ? addslashes($append_data['customer_contact']) : ''; ?>',
                customer_address: '<?php echo ($append_id > 0 && $append_data) ? addslashes($append_data['customer_address']) : ''; ?>',
                shipment_date: '<?php echo ($append_id > 0 && $append_data) ? addslashes($append_data['shipment_date']) : date("Y-m-d"); ?>',
                items: {}
            }
        };

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

        // Fungsi Global untuk Muat Histori (FULL LIVE AJAX)
        window.loadHistory = async function() {
            const container = document.getElementById('historyListContainer');
            if (!container) return;

            try {
                const uniqueUrl = `../api/process_shipment.php?action=history&_nocache=${Date.now()}`;
                const res = await fetch(uniqueUrl, { 
                    method: 'GET', 
                    cache: 'no-store',
                    headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
                });
                
                if (!res.ok) throw new Error("HTTP Error " + res.status);
                const response = await res.json();
                
                if (response.stats) {
                    if(response.stats.bulan) {
                        document.querySelectorAll('.kpi-title-month').forEach(el => el.innerText = `(${response.stats.bulan})`);
                    }
                }

                renderHistoryRows(response.data || response);
            } catch (e) {
                container.innerHTML = '<div class="text-center py-5 text-danger">Gagal memuat data histori.</div>';
                console.error("AJAX Fetch failed:", e);
            }
        };

        // --- SHIPMENT CARD COLLAPSE (per baris pengiriman) ---
        const shipmentDetailsCache = {}; // shipmentId -> { header, data } | Promise
        const openShipmentCards = new Set(); // shipmentId, preserved across auto-refresh

        function compactLabelRanges(nosStr) {
            if (!nosStr) return '-';
            const nos = nosStr.split(',').map(n => parseInt(n)).filter(n => !isNaN(n)).sort((a, b) => a - b);
            if (nos.length === 0) return '-';
            const ranges = [];
            let start = nos[0], prev = nos[0];
            for (let i = 1; i < nos.length; i++) {
                if (nos[i] === prev + 1) { prev = nos[i]; }
                else {
                    ranges.push(start === prev ? `${start}` : `${start}-${prev}`);
                    start = nos[i]; prev = nos[i];
                }
            }
            ranges.push(start === prev ? `${start}` : `${start}-${prev}`);
            return ranges.join(', ');
        }

        function getShipmentMethodMeta(inputMethod) {
            const normalized = String(inputMethod || '').toLowerCase();
            if (normalized === 'manual') {
                return { key: 'manual', icon: 'fa-keyboard', label: 'Input Manual' };
            }
            if (normalized === 'campuran') {
                return { key: 'campuran', icon: 'fa-exchange-alt', label: 'Campuran' };
            }
            if (normalized === 'scan') {
                return { key: 'scan', icon: 'fa-qrcode', label: 'Scan QR' };
            }
            return { key: 'legacy', icon: 'fa-clock', label: 'Tanpa Data' };
        }

        function normalizeItemInputMethod(inputMethod, scannedLabel) {
            if (inputMethod === 'manual' || inputMethod === 'scan' || inputMethod === 'campuran') {
                return inputMethod;
            }
            if (scannedLabel === null) {
                return 'manual';
            }
            if (scannedLabel !== undefined) {
                return 'scan';
            }
            return 'scan';
        }

        function mergeItemInputMethods(existingMethod, incomingMethod) {
            const existing = normalizeItemInputMethod(existingMethod);
            const incoming = normalizeItemInputMethod(incomingMethod);
            if (existing === incoming) return existing;
            return 'campuran';
        }

        async function fetchShipmentDetails(shipmentId) {
            const existing = shipmentDetailsCache[shipmentId];
            if (existing && !(existing.then)) return existing;
            if (existing && typeof existing.then === 'function') return existing;

            const promise = (async () => {
                const res = await fetch(`../api/get_shipment_details.php?id=${shipmentId}`);
                const result = await res.json();
                if (result.status !== 'success') throw new Error(result.message || 'Gagal');
                return { header: result.header || {}, data: result.data || [] };
            })();
            shipmentDetailsCache[shipmentId] = promise;
            try {
                const data = await promise;
                shipmentDetailsCache[shipmentId] = data;
                return data;
            } catch (e) {
                delete shipmentDetailsCache[shipmentId];
                throw e;
            }
        }

        function renderShipmentCardBody(shipmentId, header, details) {
            if (!header) header = {};
            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            let shipDateFmt = '-';
            if (header.shipment_date) {
                const d = new Date(header.shipment_date);
                shipDateFmt = `${d.getDate().toString().padStart(2,'0')} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
            }
            let shipTime = '-';
            if (header.shipped_at) {
                const parts = header.shipped_at.split(' ');
                shipTime = parts.length > 1 ? parts[1] : parts[0];
            }
            const methodMeta = getShipmentMethodMeta(header.input_method);

            let totalLabel = 0;
            let batchesHTML = '';
            if (!details || details.length === 0) {
                batchesHTML = '<div class="text-center text-muted py-3"><small>Tidak ada rincian batch.</small></div>';
            } else {
                batchesHTML = details.map(d => {
                    totalLabel += parseInt(d.label_qty || 0);
                    const nosArr = (d.label_nos || '').split(',').filter(x => x !== '');
                    const compact = compactLabelRanges(d.label_nos);
                    const chipsHTML = nosArr.map(n => `<span class="batch-chip">${n}</span>`).join('');
                    const needToggle = nosArr.length > 15;
                    const chipId = `chips-${shipmentId}-${d.production_id}`;
                    return `
                        <div class="batch-detail-row">
                            <div class="batch-meta-line">
                                <div class="batch-meta-main">
                                    <div class="text-primary font-w800" style="font-size:12px; line-height:1.2;">#${d.batch} <span class="text-black font-w700">${d.item}</span></div>
                                    <div class="batch-meta-info">${d.size} ${d.unit} &bull; ${d.machine || '-'} / Shift ${d.shift || '-'}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge badge-primary text-white font-w700" style="font-size:10px; padding:4px 8px;">${d.label_qty} Dus</span>
                                </div>
                            </div>
                            <div class="mb-1">
                                <small class="text-muted" style="font-size:10px;">No. Label: <strong class="text-black">${compact}</strong></small>
                            </div>
                            <div class="batch-chips" id="${chipId}">
                                ${chipsHTML}
                            </div>
                            ${needToggle ? `<button type="button" class="btn btn-link btn-sm p-0 mt-1" style="font-size:11px;" onclick="event.stopPropagation(); toggleChipsExpand('${chipId}', this)">Lihat semua ${nosArr.length} nomor</button>` : ''}
                        </div>
                    `;
                }).join('');
            }

            const custName = (header.customer_name || '').replace(/"/g, '&quot;');

            return `
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <div class="p-3 border rounded bg-white h-100">
                            <small class="text-muted text-uppercase d-block mb-2" style="font-size:10px; letter-spacing:1px; font-weight:700;">Customer</small>
                            <div class="text-black font-w800 mb-1" style="font-size:15px;">${custName}</div>
                            <div class="small text-muted"><i class="fa fa-phone-alt me-2" style="width:14px;"></i>${header.customer_contact || '-'}</div>
                            <div class="small text-muted"><i class="fa fa-map-marker-alt me-2" style="width:14px;"></i>${header.customer_address || '-'}</div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="p-3 border rounded bg-white h-100">
                            <small class="text-muted text-uppercase d-block mb-2" style="font-size:10px; letter-spacing:1px; font-weight:700;">Pengiriman</small>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size:10px;">Tanggal</small>
                                    <div class="text-black font-w700" style="font-size:12px;">${shipDateFmt}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block" style="font-size:10px;">Jam Input</small>
                                    <div class="text-black font-w700" style="font-size:12px;">${shipTime} WITA</div>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block" style="font-size:10px;">Dikirim Oleh</small>
                                    <div class="text-black font-w700" style="font-size:12px;">${header.shipped_by || '-'}</div>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block" style="font-size:10px;">Total Dus</small>
                                    <div class="text-success font-w800" style="font-size:13px;">${totalLabel} Dus</div>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block mb-1" style="font-size:10px;">Metode Input</small>
                                    <span class="shipment-method-badge ${methodMeta.key}"><i class="fa ${methodMeta.icon}"></i>${methodMeta.label}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <h6 class="text-black font-w800 mb-0" style="font-size:13px;"><i class="fa fa-boxes me-2 text-primary"></i>Rincian Batch & Nomor Label</h6>
                </div>

                <div class="batch-detail-grid">${batchesHTML}</div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); window.open('print_invoice.php?id=${shipmentId}', '_blank')"><i class="fa fa-print me-1"></i> Cetak Ulang Surat Jalan</button>
                </div>
            `;
        }

        window.toggleShipmentCard = async function(shipmentId) {
            const shipmentKey = String(shipmentId);
            const card = document.querySelector(`.shipment-card[data-id="${shipmentId}"]`);
            const body = document.getElementById(`ship-body-${shipmentId}`);
            const chev = document.querySelector(`.ship-chev-${shipmentId}`);
            if (!card || !body) return;

            if (!body.classList.contains('d-none')) {
                body.classList.add('d-none');
                card.classList.remove('open');
                chev?.classList.remove('rotated');
                openShipmentCards.delete(shipmentKey);
                return;
            }

            body.classList.remove('d-none');
            card.classList.add('open');
            chev?.classList.add('rotated');
            openShipmentCards.add(shipmentKey);

            if (body.dataset.loaded !== 'true') {
                try {
                    const { header, data } = await fetchShipmentDetails(shipmentId);
                    body.innerHTML = renderShipmentCardBody(shipmentId, header, data);
                    body.dataset.loaded = 'true';
                } catch (e) {
                    body.innerHTML = '<div class="text-center text-danger py-3"><i class="fa fa-exclamation-triangle me-2"></i>Gagal memuat detail</div>';
                }
            }
        };

        window.toggleChipsExpand = function(chipId, btn) {
            const el = document.getElementById(chipId);
            if (!el) return;
            const expanded = el.classList.toggle('expanded');
            const total = el.children.length;
            btn.innerText = expanded ? 'Sembunyikan' : `Lihat semua ${total} nomor`;
        };

        function renderHistoryRows(data) {
            const container = document.getElementById('historyListContainer');
            if (!container) return;
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="text-center py-5 text-muted">Belum ada data pengiriman hari ini.</div>';
                return;
            }

            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

            let html = '';
            data.forEach(row => {
                const shipmentId = String(row.id);
                const methodMeta = getShipmentMethodMeta(row.input_method);
                const shippedAtRaw = row.shipped_at || '';
                const shipmentDateRaw = row.shipment_date || '';
                let formattedDate = '-';
                let time = '-';

                if (shippedAtRaw) {
                    const [datePart, timePart = ''] = shippedAtRaw.split(' ');
                    if (datePart) {
                        const dateObj = new Date(`${datePart}T00:00:00`);
                        if (!Number.isNaN(dateObj.getTime())) {
                            formattedDate = `${dateObj.getDate().toString().padStart(2, '0')} ${monthNames[dateObj.getMonth()]} ${dateObj.getFullYear()}`;
                        }
                    }
                    time = timePart || '-';
                } else if (shipmentDateRaw) {
                    const dateObj = new Date(`${shipmentDateRaw}T00:00:00`);
                    if (!Number.isNaN(dateObj.getTime())) {
                        formattedDate = `${dateObj.getDate().toString().padStart(2, '0')} ${monthNames[dateObj.getMonth()]} ${dateObj.getFullYear()}`;
                    }
                }

                // Agregasi item summary per (item+size)
                const aggregated = {};
                if (row.item_summary) {
                    row.item_summary.split(';').forEach(it => {
                        const parts = it.split('|');
                        const nameSize = parts[0];
                        const count = parseInt(parts[1] || '0');
                        aggregated[nameSize] = (aggregated[nameSize] || 0) + count;
                    });
                }
                const isOpen = openShipmentCards.has(shipmentId);
                const openClass = isOpen ? 'open' : '';
                const bodyHiddenClass = isOpen ? '' : 'd-none';
                const chevRotated = isOpen ? 'rotated' : '';
                const cached = shipmentDetailsCache[shipmentId];
                const hasCache = cached && !cached.then;
                let bodyContent = '<div class="text-center py-3"><i class="fa fa-spinner fa-spin text-primary me-2"></i>Memuat rincian...</div>';
                let loadedAttr = '';
                if (isOpen && hasCache) {
                    bodyContent = renderShipmentCardBody(shipmentId, cached.header, cached.data);
                    loadedAttr = 'data-loaded="true"';
                }

                html += `
                    <div class="shipment-card ${openClass}" data-id="${shipmentId}">
                        <div class="shipment-card-header" onclick="toggleShipmentCard('${shipmentId}')">
                            <div class="sh-col col-time">
                                <span class="text-black font-w600" style="font-size:13px;">${formattedDate}</span><br>
                                <small class="text-muted font-w500">${time} WITA</small>
                            </div>
                            <div class="sh-col col-customer">
                                <div class="text-primary font-w700" style="font-size:14px;">${row.customer_name}</div>
                                <small class="text-muted"><i class="fa fa-phone-alt me-1" style="font-size:10px;"></i>${row.customer_contact || '-'}</small>
                            </div>
                            <div class="sh-col col-method">
                                <span class="shipment-method-badge ${methodMeta.key}"><i class="fa ${methodMeta.icon}"></i>${methodMeta.label}</span>
                            </div>
                            <div class="sh-col col-total">
                                <div class="badge badge-success text-white font-w800" style="font-size:12px; padding: 5px 10px; border-radius:6px;">${row.total_qty || 0} Dus</div>
                            </div>
                            <div class="sh-col col-by"><small class="font-w600 text-black">${row.shipped_by}</small></div>
                            <div class="sh-col col-chev">
                                <i class="fa fa-chevron-down ship-chev ship-chev-${shipmentId} ${chevRotated}"></i>
                            </div>
                        </div>
                        <div class="shipment-card-body ${bodyHiddenClass}" id="ship-body-${shipmentId}" ${loadedAttr}>
                            ${bodyContent}
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        window.showRowActions = function(id, name) {
            Swal.fire({
                html: `
                    <div class="swal2-close-custom" onclick="Swal.close()"><i class="fa fa-times"></i></div>
                    <div class="text-center mb-3">
                        <small class="text-muted d-block mb-1">Customer</small>
                        <strong class="text-black" style="word-break:break-all;">${name}</strong>
                    </div>
                    <div class="action-list">
                        <button onclick="Swal.close(); viewShipmentDetail(${id}, '${name.replace(/'/g, "\\'")}')" class="action-item"><i class="fa fa-eye icon-view"></i> Lihat Rincian Dus</button>
                        <button onclick="Swal.close(); window.location.href='shipment_scan.php?append_id=${id}'" class="action-item"><i class="fa fa-plus icon-append"></i> Tambah Dus Susulan</button>
                        <button onclick="Swal.close(); window.open('print_invoice.php?id=${id}', '_blank')" class="action-item"><i class="fa fa-print icon-print"></i> Cetak Surat Jalan</button>
                        <button onclick="Swal.close(); deleteShipment(${id})" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Batalkan Pengiriman</button>
                    </div>
                `,
                showConfirmButton: false, padding: '1.2rem', width: '320px', borderRadius: '15px'
            });
        };

        window.viewShipmentDetail = async function(id, name) {
            const modalEl = document.getElementById('modalViewShipment');
            const contentEl = document.getElementById('viewDetailContent');
            contentEl.innerHTML = '<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Memuat rincian...</p></div>';
            new bootstrap.Modal(modalEl).show();

            try {
                const res = await fetch(`../api/get_shipment_details.php?id=${id}`);
                const result = await res.json();

                if (result.status === 'success') {
                    let tableRows = '';
                    let totalLabel = 0;

                    result.data.forEach((item, i) => {
                        totalLabel += parseInt(item.label_qty);
                        tableRows += `
                            <tr>
                                <td class="text-muted align-middle" style="font-size: 12px;">${i + 1}</td>
                                <td class="align-middle">
                                    <div class="text-black font-w700" style="font-size: 13px;">${item.item}</div>
                                    <div class="text-muted" style="font-size: 11px;">${item.size} ${item.unit} &bull; <span class="text-primary font-w600">#${item.batch}</span></div>
                                </td>
                                <td class="text-center align-middle font-w600 text-black" style="font-size: 13px;">
                                    ${item.label_qty} Dus
                                </td>
                                <td class="text-end align-middle font-w800 text-black" style="font-size: 14px;">${item.label_qty} Dus</td>
                            </tr>
                        `;
                    });

                    contentEl.innerHTML = `
                        <div class="row mb-4 g-3">
                            <div class="col-12">
                                <div class="p-3 bg-light rounded" style="height: 100%;">
                                    <small class="text-muted text-uppercase d-block mb-2" style="font-size:10px; font-weight:700; letter-spacing:1px;">Customer</small>
                                    <div class="text-black font-w800" style="font-size: 16px;">${name}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-black font-w800 mb-0" style="font-size: 14px;">Rincian Dus</h6>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover mb-0" style="table-layout: fixed; width: 100%;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 10%;">NO</th>
                                        <th class="text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 45%;">ITEM & BATCH</th>
                                        <th class="text-center text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 20%;">JUMLAH</th>
                                        <th class="text-end text-muted font-w700 border-bottom-0 py-2" style="font-size:10px; width: 25%;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows || '<tr><td colspan="4" class="text-center py-5 text-muted">Tidak ada data rincian label.</td></tr>'}
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="2" class="text-end py-3 text-black font-w800" style="font-size: 12px; border-bottom: 0;">TOTAL KESELURUHAN</td>
                                        <td class="text-center py-3 text-black font-w800" style="font-size: 14px; border-bottom: 0;">${totalLabel}</td>
                                        <td class="text-end py-3 text-danger font-w800" style="font-size: 16px; border-bottom: 0;">${totalLabel} Dus</td>
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

        window.deleteShipment = function(id) {
            Swal.fire({
                title: 'Batalkan Pengiriman?',
                text: "Barang akan dikembalikan ke stok gudang.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D50000',
                confirmButtonText: 'Ya, Batalkan'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const res = await fetch(`../api/manage_settings.php?action=delete&type=shipment`, { method: 'POST', body: f });
                    const data = await res.json();
                    if(data.status === 'success') {
                        toastr.success('Data Berhasil Dibatalkan');
                        window.loadHistory();
                    } else {
                        toastr.error(data.message || 'Gagal membatalkan pengiriman');
                    }
                }
            });
        }

        setInterval(() => window.loadHistory(), 2000);

        document.getElementById('customer_name').addEventListener('input', (e) => carts[activeCartId].customer_name = e.target.value);
        document.getElementById('customer_contact').addEventListener('input', (e) => carts[activeCartId].customer_contact = e.target.value);
        document.getElementById('customer_address').addEventListener('input', (e) => carts[activeCartId].customer_address = e.target.value);
        document.getElementById('shipment_date').addEventListener('change', (e) => carts[activeCartId].shipment_date = e.target.value);

        function renderTabs() {
            tabsContainer.innerHTML = '';
            for (const id in carts) {
                const c = carts[id];
                const btn = document.createElement('div');
                btn.className = `cart-tab ${id == activeCartId ? 'active' : 'inactive'}`;
                const textSpan = document.createElement('span');
                textSpan.innerText = c.name;
                textSpan.onclick = () => switchCart(id);
                btn.appendChild(textSpan);
                if (Object.keys(carts).length > 1) {
                    const delIcon = document.createElement('i');
                    delIcon.className = 'fa fa-times';
                    delIcon.onclick = (e) => deleteCart(id, e);
                    btn.appendChild(delIcon);
                }
                tabsContainer.appendChild(btn);
            }
            const addBtn = document.createElement('div');
            addBtn.className = 'cart-tab cart-tab-add';
            addBtn.innerHTML = '<i class="fa fa-plus me-1"></i> Tambah';
            addBtn.onclick = addNewCart;
            tabsContainer.appendChild(addBtn);
        }

        function addNewCart() {
            cartCounter++;
            carts[cartCounter] = { 
                name: `Keranjang ${cartCounter}`, 
                customer_name: '', customer_contact: '', customer_address: '', 
                shipment_date: '<?php echo date("Y-m-d"); ?>', 
                items: {} 
            };
            switchCart(cartCounter);
        }

        function deleteCart(id, e) {
            e.stopPropagation();
            if (Object.keys(carts).length <= 1) return;
            delete carts[id];
            if (activeCartId == id) activeCartId = Object.keys(carts)[0];
            switchCart(activeCartId);
        }

        function switchCart(id) {
            activeCartId = id;
            const c = carts[id];
            document.getElementById('customer_name').value = c.customer_name;
            document.getElementById('customer_contact').value = c.customer_contact;
            document.getElementById('customer_address').value = c.customer_address;
            document.getElementById('shipment_date').value = c.shipment_date;
            renderTabs();
            renderActiveCart();
        }

        function renderActiveCart() {
            cartContainer.innerHTML = '';
            const cItems = carts[activeCartId].items;
            if (Object.keys(cItems).length === 0) {
                document.getElementById('empty-cart').style.display = 'block';
            } else {
                document.getElementById('empty-cart').style.display = 'none';
                for (const pid in cItems) renderBatchGridHTML(pid, cItems[pid]);
            }
            updateTotal();
        }

        async function searchCustomer(q) {
            const suggBox = document.getElementById('customer-suggestions');
            if(q.length < 2) { suggBox.style.display = 'none'; return; }
            try {
                const res = await fetch(`../api/get_customers.php?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                suggBox.innerHTML = '';
                if(data.length > 0) {
                    data.forEach(c => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action py-2';
                        a.href = '#';
                        a.innerHTML = `<div class="d-flex justify-content-between align-items-center">
                                        <div><strong class="text-primary">${c.name}</strong><small class="text-muted d-block">${c.contact || '-'}</small></div>
                                        <div class="text-end"><span class="badge badge-light text-dark border shadow-sm" style="font-size:10px;">${c.total_orders || 0} Order</span></div>
                                       </div>
                                       <small class="text-muted d-block text-truncate mt-1">${c.address || ''}</small>`;
                        a.onclick = (e) => { e.preventDefault(); selectCustomer(c.name, c.contact, c.address); };
                        suggBox.appendChild(a);
                    });
                    suggBox.style.display = 'block';
                } else suggBox.style.display = 'none';
            } catch(e) {}
        }

        function selectCustomer(name, contact, address) {
            document.getElementById('customer_name').value = name;
            document.getElementById('customer_contact').value = contact || '';
            document.getElementById('customer_address').value = address || '';
            document.getElementById('customer-suggestions').style.display = 'none';
            carts[activeCartId].customer_name = name;
            carts[activeCartId].customer_contact = contact || '';
            carts[activeCartId].customer_address = address || '';
        }

        document.addEventListener('click', (e) => {
            if(!document.getElementById('customer_name').contains(e.target)) document.getElementById('customer-suggestions').style.display = 'none';
        });

        function updateStatus(type, title, msg) {
            statusPanel.classList.remove('bg-success-light', 'bg-danger-light', 'bg-warning-light');
            const icons = { success: '<i class="fa fa-check-circle fa-2x text-success"></i>', error: '<i class="fa fa-times-circle fa-2x text-danger"></i>', warning: '<i class="fa fa-exclamation-triangle fa-2x text-warning"></i>', info: '<i class="fa fa-info-circle fa-2x text-primary"></i>' };
            statusPanel.className = `status-panel mt-3 bg-${type}-light`;
            document.getElementById('status-icon').innerHTML = icons[type] || icons.info;
            document.getElementById('status-text').innerHTML = `<div class="font-w700 text-${type}">${title}</div><small class="text-dark">${msg}</small>`;
        }

        async function initCamera() {
            try {
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    cameraSelect.innerHTML = '';
                    devices.forEach(device => { cameraSelect.appendChild(new Option(device.label, device.id)); });
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear'));
                    cameraSelect.value = backCam ? backCam.id : devices[0].id;
                    startScanner(cameraSelect.value);
                }
            } catch (e) { updateStatus('error', 'Izin Kamera', 'Gagal mengakses list kamera.'); }
        }

        function startScanner(deviceId) {
            if(html5QrCode) html5QrCode.stop().then(() => doStart(deviceId));
            else doStart(deviceId);
        }

        function doStart(deviceId) {
            html5QrCode = new Html5Qrcode("reader");
            let boxSize = window.innerWidth < 600 ? 200 : 250;
            html5QrCode.start(deviceId, { fps: 15, qrbox: boxSize, aspectRatio: 1.0 }, onScanSuccess)
            .then(() => { document.getElementById('start-btn-area').style.display = 'none'; updateStatus('info', 'Scanner Ready', 'Arahkan kamera ke QR Barang'); });
        }

        function switchCamera(id) { if(id) startScanner(id); }
        function resumeScanner() { isProcessing = false; if (html5QrCode && html5QrCode.getState() === 3) html5QrCode.resume(); updateStatus('info', 'Scanner Ready', `Arahkan kamera ke QR (Mengisi ${carts[activeCartId].name})`); }

        async function onScanSuccess(decodedText) {
            if (isProcessing) return; 
            if (html5QrCode && html5QrCode.isScanning) html5QrCode.pause(true);
            const dashIndex = decodedText.indexOf('-');
            if (dashIndex > 0) {
                const labelNo = parseInt(decodedText.substring(0, dashIndex));
                const batchStr = decodedText.substring(dashIndex + 1);
                let foundInCartName = null;
                for (const cid in carts) {
                    for (const pid in carts[cid].items) {
                        if (carts[cid].items[pid].batch === batchStr && carts[cid].items[pid].selected.has(labelNo)) { foundInCartName = carts[cid].name; break; }
                    }
                    if (foundInCartName) break;
                }
                if (foundInCartName) {
                    new Audio('../assets/sounds/alert.wav').play().catch(e => {});
                    updateStatus('warning', 'SUDAH TERPILIH', `Barang ini sedang berada di ${foundInCartName}!`);
                    setTimeout(resumeScanner, 2000);
                    return;
                }
            }
            isProcessing = true;
            updateStatus('info', 'Memeriksa...', 'Mencari data unit...');
            try {
                const res = await fetch(`../api/process_shipment.php?action=get_batch_data&qr=${encodeURIComponent(decodedText)}`);
                const result = await res.json();
                if(result.status === 'success') {
                    new Audio('../assets/sounds/success.wav').play().catch(e => {});
                    updateStatus('success', 'UNIT DITAMBAHKAN', `Paket ditambahkan ke ${carts[activeCartId].name}`);
                    addToCart(result.data);
                    setTimeout(resumeScanner, 800);
                } else {
                    new Audio('../assets/sounds/reject.wav').play().catch(e => {});
                    updateStatus('error', 'DITOLAK', result.message);
                    setTimeout(resumeScanner, 2000);
                }
            } catch (e) { new Audio('../assets/sounds/alert.wav').play().catch(e => {}); updateStatus('error', 'SERVER ERROR', 'Koneksi terputus'); setTimeout(resumeScanner, 2000); }
        }

        function addToCart(data) {
            document.getElementById('empty-cart').style.display = 'none';
            const pid = data.production_id;
            const labelScanned = data.scanned_label;
            const incomingInputMethod = normalizeItemInputMethod(data.input_method, labelScanned);
            if (!carts[activeCartId].items[pid]) {
                carts[activeCartId].items[pid] = { batch: data.batch, item: data.item, size: data.size, copies: data.copies, input_method: incomingInputMethod, in_warehouse: data.in_warehouse, already_shipped: data.already_shipped, selected: new Set() };
                renderBatchGridHTML(pid, carts[activeCartId].items[pid]);
            } else {
                carts[activeCartId].items[pid].input_method = mergeItemInputMethods(carts[activeCartId].items[pid].input_method, incomingInputMethod);
            }
            if (carts[activeCartId].items[pid].in_warehouse.includes(labelScanned) && !carts[activeCartId].items[pid].already_shipped.includes(labelScanned)) {
                if (!carts[activeCartId].items[pid].selected.has(labelScanned)) {
                    carts[activeCartId].items[pid].selected.add(labelScanned);
                    updateUIAfterSelection(pid, labelScanned);
                }
            }
            updateTotal();
        }

        function renderBatchGridHTML(pid, batchData) {
            const div = document.createElement('div');
            div.className = 'batch-card'; div.id = `batch-card-${pid}`;
            const availableCount = batchData.in_warehouse.filter(x => !batchData.already_shipped.includes(x)).length;
            div.innerHTML = `<div class="d-flex justify-content-between align-items-center mb-2"><div><h5 class="text-primary mb-0">#${batchData.batch}</h5><small class="text-black font-w600">${batchData.item} (${batchData.size})</small></div><div class="d-flex align-items-center gap-2"><button type="button" class="btn btn-outline-primary btn-sm" id="btn-selectall-${pid}" onclick="selectAllInBatch(${pid})"><i class="fa fa-check-double me-1"></i> Pilih Semua (${availableCount})</button><div class="text-primary font-w800" style="font-size: 14px;"><span id="count-${pid}">${batchData.selected.size}</span> Dipilih</div></div></div><div class="cinema-grid-bulk" id="grid-${pid}"></div>`;
            cartContainer.appendChild(div);
            const gridEl = document.getElementById(`grid-${pid}`);
            for (let i = 1; i <= batchData.copies; i++) {
                const seat = document.createElement('div');
                seat.innerText = i; seat.id = `seat-${pid}-${i}`; seat.className = 'seat-bulk';
                if (batchData.already_shipped.includes(i)) { seat.classList.add('shipped'); seat.title = 'Sudah Dikirim'; }
                else if (batchData.in_warehouse.includes(i)) {
                    seat.classList.add('available'); if (batchData.selected.has(i)) seat.classList.add('selected');
                    const start = () => { isDragging = true; toggleSeat(pid, i, seat); };
                    const move = () => { if(isDragging) toggleSeat(pid, i, seat); };
                    seat.addEventListener('mousedown', start); seat.addEventListener('mouseenter', move);
                    seat.addEventListener('touchstart', (e) => { e.preventDefault(); start(); });
                } else seat.title = 'Belum di Gudang';
                gridEl.appendChild(seat);
            }
            window.addEventListener('mouseup', () => isDragging = false); window.addEventListener('touchend', () => isDragging = false);
            updateSelectAllButtonUI(pid);
        }

        function updateSelectAllButtonUI(pid) {
            const batchData = carts[activeCartId].items[pid];
            const availableCount = batchData.in_warehouse.filter(x => !batchData.already_shipped.includes(x)).length;
            const btn = document.getElementById(`btn-selectall-${pid}`); if(!btn) return;
            if (batchData.selected.size === availableCount && availableCount > 0) { btn.innerHTML = `<i class="fa fa-times me-1"></i> <span>Batal Pilih</span>`; btn.className = "btn btn-toggle-deselect btn-sm"; }
            else { btn.innerHTML = `<i class="fa fa-check-double me-1"></i> <span>Pilih Semua (${availableCount})</span>`; btn.className = "btn btn-toggle-select btn-sm"; }
        }

        function selectAllInBatch(pid) {
            const batchData = carts[activeCartId].items[pid];
            const availableCount = batchData.in_warehouse.filter(x => !batchData.already_shipped.includes(x)).length;
            if (availableCount === 0) return toastr.info("Kosong.");
            if (batchData.selected.size === availableCount) {
                batchData.selected.clear();
                for (let i = 1; i <= batchData.copies; i++) { const el = document.getElementById(`seat-${pid}-${i}`); if (el) el.classList.remove('selected'); }
            } else {
                Swal.fire({ 
                    title: 'Pilih Semua?', 
                    text: `Tindakan ini berisiko karena Anda memilih ${availableCount} unit tanpa melakukan scan fisik satu per satu. Lanjutkan?`, 
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#D50000', confirmButtonText: 'Ya, Saya Paham' 
                }).then((res) => {
                    if (res.isConfirmed) {
                        for (let i = 1; i <= batchData.copies; i++) { if (batchData.in_warehouse.includes(i) && !batchData.already_shipped.includes(i)) { batchData.selected.add(i); const el = document.getElementById(`seat-${pid}-${i}`); if (el) el.classList.add('selected'); } }
                        updateTotal(); updateSelectAllButtonUI(pid); document.getElementById(`count-${pid}`).innerText = batchData.selected.size;
                    }
                });
            }
            updateTotal(); updateSelectAllButtonUI(pid); document.getElementById(`count-${pid}`).innerText = batchData.selected.size;
        }

        function toggleSeat(pid, l, el) {
            if (carts[activeCartId].items[pid].selected.has(l)) { carts[activeCartId].items[pid].selected.delete(l); el.classList.remove('selected'); }
            else { carts[activeCartId].items[pid].selected.add(l); el.classList.add('selected'); }
            document.getElementById(`count-${pid}`).innerText = carts[activeCartId].items[pid].selected.size;
            updateTotal(); updateSelectAllButtonUI(pid);
        }

        function updateUIAfterSelection(p, l) {
            const el = document.getElementById(`seat-${p}-${l}`); if (el) el.classList.add('selected');
            document.getElementById(`count-${p}`).innerText = carts[activeCartId].items[p].selected.size;
            updateSelectAllButtonUI(p);
        }

        function updateTotal() {
            let t = 0; for (const p in carts[activeCartId].items) t += carts[activeCartId].items[p].selected.size;
            document.getElementById('total-badge').innerText = `Total Keluar: ${t}`;
            document.getElementById('btn-submit').disabled = (t === 0);
        }

        function clearCart() { carts[activeCartId].items = {}; carts[activeCartId].customer_name = ''; carts[activeCartId].customer_contact = ''; carts[activeCartId].customer_address = ''; carts[activeCartId].shipment_date = '<?php echo date("Y-m-d"); ?>'; switchCart(activeCartId); resumeScanner(); }

        async function submitBulkShipment(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit');
            let finalCart = {}; for (const p in carts[activeCartId].items) { if (carts[activeCartId].items[p].selected.size > 0) finalCart[p] = Array.from(carts[activeCartId].items[p].selected); }
            if (Object.keys(finalCart).length === 0) return toastr.warning("Kosong!");
            let shipmentInputMethod = '';
            for (const p in finalCart) {
                const itemMethod = normalizeItemInputMethod(carts[activeCartId].items[p].input_method);
                shipmentInputMethod = shipmentInputMethod ? mergeItemInputMethods(shipmentInputMethod, itemMethod) : itemMethod;
            }
            shipmentInputMethod = shipmentInputMethod || 'scan';
            btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>...';
            try {
                const f = new FormData(); 
                f.append('customer_name', document.getElementById('customer_name').value); 
                f.append('customer_contact', document.getElementById('customer_contact').value); 
                f.append('customer_address', document.getElementById('customer_address').value); 
                f.append('shipment_date', document.getElementById('shipment_date').value); 
                f.append('cart', JSON.stringify(finalCart));
                f.append('input_method', shipmentInputMethod);
                f.append('append_to', <?php echo $append_id; ?>);
                
                const res = await fetch('../api/process_shipment.php?action=submit_bulk', { method: 'POST', body: f });
                const d = await res.json();
                if (d.status === 'success') {
                    toastr.success(d.message);
                    <?php if($append_id > 0): ?>
                    Swal.fire({ title: 'Berhasil Susulan', text: "Kembali ke data histori?", icon: 'success', showCancelButton: true, confirmButtonColor: '#1A237E', confirmButtonText: 'Ya' }).then((r) => { if (r.isConfirmed) window.location.href = 'shipment_data.php'; else clearCart(); });
                    <?php else: ?>
                    Swal.fire({ title: 'Pengiriman Berhasil', text: "Apakah Anda ingin mencetak Surat Jalan sekarang?", icon: 'success', showCancelButton: true, confirmButtonColor: '#1A237E', confirmButtonText: 'Cetak Surat Jalan' }).then((r) => { if (r.isConfirmed) window.open(`print_invoice.php?id=${d.shipment_id}`, '_blank'); });
                    <?php endif; ?>
                    if (Object.keys(carts).length > 1) { delete carts[activeCartId]; activeCartId = Object.keys(carts)[0]; switchCart(activeCartId); } else clearCart();
                    window.loadHistory();
                } else toastr.error(d.message);
            } catch (err) { toastr.error('Error.'); } finally { btn.disabled = false; btn.innerHTML = '<i class="fa fa-paper-plane me-2"></i> Proses Pengiriman'; }
        }

        // --- MANUAL INPUT (TANPA BARCODE) ---
        let manualSearchTimer = null;

        window.openManualModal = function() {
            document.getElementById('manualSearchInput').value = '';
            new bootstrap.Modal(document.getElementById('modalManualAdd')).show();
            setTimeout(() => document.getElementById('manualSearchInput').focus(), 300);
            searchManualBatches('');
        };

        async function searchManualBatches(q) {
            const container = document.getElementById('manualSearchResults');
            container.innerHTML = '<div class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin me-1"></i> Memuat...</div>';
            try {
                const res = await fetch(`../api/process_shipment.php?action=search_batches&q=${encodeURIComponent(q)}`);
                const result = await res.json();
                if (result.status !== 'success' || !result.data || result.data.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-4"><i class="fa fa-box-open fa-2x mb-2"></i><br>Tidak ada batch dengan stok tersedia.</div>';
                    return;
                }
                let html = '<div class="list-group">';
                result.data.forEach(b => {
                    const inCart = !!carts[activeCartId].items[b.id];
                    html += `
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" onclick="pickManualBatch(${b.id})" ${inCart ? 'disabled' : ''}>
                            <div class="text-start">
                                <div class="text-primary font-w700" style="font-size: 14px;">#${b.batch}</div>
                                <small class="text-black font-w600">${b.item} (${b.size} ${b.unit})</small>
                            </div>
                            <div class="text-end">
                                ${inCart ? '<span class="badge badge-secondary text-white" style="font-size: 11px;">Sudah di Keranjang</span>' : `<span class="badge badge-success text-white" style="font-size: 12px;">${b.available} Tersedia</span>`}
                            </div>
                        </button>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } catch (e) {
                container.innerHTML = '<div class="text-center text-danger py-4"><i class="fa fa-wifi fa-2x mb-2"></i><br>Gagal memuat data.</div>';
            }
        }

        document.getElementById('manualSearchInput').addEventListener('input', (e) => {
            clearTimeout(manualSearchTimer);
            manualSearchTimer = setTimeout(() => searchManualBatches(e.target.value), 250);
        });

        window.pickManualBatch = async function(prodId) {
            try {
                const res = await fetch(`../api/process_shipment.php?action=get_batch_manual&production_id=${prodId}`);
                const result = await res.json();
                if (result.status !== 'success') {
                    toastr.error(result.message || 'Gagal memuat batch');
                    return;
                }
                addToCart(result.data);
                bootstrap.Modal.getInstance(document.getElementById('modalManualAdd')).hide();
                toastr.success(`Batch #${result.data.batch} ditambahkan. Pilih nomor dus di grid.`);
                const card = document.getElementById(`batch-card-${result.data.production_id}`);
                if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch (e) {
                toastr.error('Gagal memuat batch');
            }
        };

        switchCart(1);
        window.loadHistory();
        document.querySelector('a[href="shipment_scan.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>
