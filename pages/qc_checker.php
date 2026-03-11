<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
require_once '../includes/auth_check.php';
protect_page('qc_checker');
?>
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
    .scanner-container { position: sticky; top: 80px; z-index: 0; }
    #reader { width: 100%; border-radius: 15px; overflow: hidden; border: 3px solid #1A237E !important; background: #000; min-height: 250px; }
    #reader video { width: 100% !important; height: auto !important; object-fit: cover !important; border-radius: 12px; }

    .cinema-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(32px, 1fr));
        gap: 6px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #eee;
    }
    .seat {
        height: 32px; display: flex; align-items: center; justify-content: center;
        background: #e9ecef; border-radius: 4px; font-weight: bold; color: #adb5bd;
        font-size: 10px; transition: all 0.2s ease; border-bottom: 2px solid #dee2e6;
    }
    .seat.occupied { background: #00C853; color: #fff; border-color: #009624; }
    .seat.last-hit {
        background: #FFC107; color: #000; border-color: #FFA000;
        animation: pulse-yellow 0.8s infinite;
    }

    @keyframes pulse-yellow {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .status-panel {
        min-height: 70px; padding: 12px 15px; border-radius: 10px; margin-top: 15px;
        display: flex; align-items: center; gap: 12px; transition: all 0.3s ease;
        border: 1px solid #eee; background: #fff;
    }
    .status-panel.bg-success-light { background: #E8F5E9; border-color: #00C853; }
    .status-panel.bg-danger-light { background: #FFEBEE; border-color: #D50000; }
    .status-panel.bg-warning-light { background: #FFF8E1; border-color: #FFC107; }

    .cam-controls { display: flex; gap: 5px; margin-top: 10px; }
    #camera-select { font-size: 12px; height: 35px; border-radius: 8px; border: 1px solid #1A237E; }

    /* RESPONSIVE MOBILE/TABLET ADJUSTMENTS */
    @media (max-width: 991px) {
        .scanner-container { position: static; margin-bottom: 20px; z-index: 0; }
        .cinema-grid { grid-template-columns: repeat(auto-fill, minmax(26px, 1fr)); padding: 10px; gap: 4px; }
        .seat { height: 28px; font-size: 9px; }
        .card-header h4 { font-size: 16px; }
    }
    .batch-card { border: 1px solid #e0e0e0; border-radius: 15px; padding: 15px; margin-bottom: 15px; }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>    
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row">
                    <!-- SCANNER SIDE -->
                    <div class="col-xl-4 col-lg-5 col-md-12 mb-4 mb-lg-0">
                        <div class="scanner-container">
                            <div class="card shadow-sm border-0">
                                <div class="card-header border-0 pb-0">
                                    <h4 class="card-title text-black">QC Scanner</h4>
                                </div>                                <div class="card-body">
                                    <div class="alert alert-info alert-dismissible fade show small py-2 px-3 mb-3 d-flex align-items-center" role="alert">
                                        <i class="fa fa-info-circle me-2 fs-5"></i>
                                        <div><strong>Tips:</strong> Untuk kenyamanan, sangat disarankan untuk mengunci rotasi layar HP/Tablet Anda.</div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.75rem 1rem;"></button>
                                    </div>
                                    <div id="reader"></div>
                                    <div class="cam-controls">
                                        <select id="camera-select" class="form-control" onchange="switchCamera(this.value)">
                                            <option value="">Mencari Kamera...</option>
                                        </select>
                                        <button id="torch-btn" class="btn btn-warning btn-xs shadow-sm" onclick="toggleTorch()" style="display:none;"><i class="fa fa-bolt"></i></button>
                                    </div>
                                    <div class="text-center mt-3" id="start-btn-area">
                                        <button class="btn btn-primary btn-sm w-100" onclick="initCamera()"><i class="fa fa-camera me-2"></i> Aktifkan Kamera</button>
                                    </div>
                                    <div id="status-display" class="status-panel mt-3">
                                        <div id="status-icon"><i class="fa fa-qrcode fa-2x text-muted"></i></div>
                                        <div id="status-text">
                                            <div class="font-w600 text-black">Siap Scan</div>
                                            <small class="text-muted">Arahkan kamera ke QR Dus</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm w-100 mt-2" onclick="resumeScanner()">Resume Scanner</button>
                                    <button class="btn btn-light btn-xs w-100 mt-3" onclick="clearTracking()">Bersihkan Pantauan Grid</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MULTI TRACKING MAPS SIDE -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header border-bottom flex-column align-items-start">
                                <div class="d-flex justify-content-between w-100 mb-3">
                                    <h4 class="card-title text-black">Pantauan Real-Time QC</h4>
                                    <span class="badge border border-primary text-primary bg-light shadow-sm" style="font-size: 14px;" id="global-total-verified">Total Scan Dus: 0</span>
                                </div>
                            </div>
                            <div class="card-body" id="tracking-maps-container">
                                <div id="empty-state" class="text-center py-5">
                                    <i class="fa fa-th fa-4x text-light mb-3"></i>
                                    <h5 class="text-muted">Belum ada batch yang dipantau.<br>Scan dus untuk memulai tracking.</h5>
                                </div>
                                <!-- Grid Cards will be appended here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php' ?>
    <script>
        let html5QrCode;
        let isTorchOn = false;
        let isProcessing = false;
        let trackedBatchIds = []; // Menyimpan daftar prod_id yang sedang dipantau
        let globalScannedCounts = {}; // { prod_id: scanned_count }

        const statusPanel = document.getElementById('status-display');
        const cameraSelect = document.getElementById('camera-select');
        const mapsContainer = document.getElementById('tracking-maps-container');

        function updateGlobalVerifiedCount() {
            let total = 0;
            for (const pid in globalScannedCounts) {
                total += globalScannedCounts[pid];
            }
            document.getElementById('global-total-verified').innerText = `Total Scan Dus: ${total}`;
        }

        // Fungsi Global untuk Muat Progress QC (Full AJAX Multi-Batch)
        window.fetchQCProgress = async function() {
            if (trackedBatchIds.length === 0) return;

            for (const prodId of trackedBatchIds) {
                try {
                    const res = await fetch(`../api/get_production.php?id=${prodId}&_nocache=${Date.now()}`);
                    const result = await res.json();

                    if (result.data && result.data.length > 0) {
                        const row = result.data[0];
                        const detRes = await fetch(`../api/get_qc_details.php?prod_id=${prodId}&_nocache=${Date.now()}`);
                        const scannedList = await detRes.json();

                        const data = {
                            production_id: prodId,
                            item: row.item,
                            batch: row.batch,
                            copies: parseInt(row.copies), 
                            scanned_list: scannedList,
                            progress: `${scannedList.length}/${parseInt(row.copies)}`,
                            last_no: scannedList[scannedList.length - 1]
                        };

                        globalScannedCounts[prodId] = scannedList.length;
                        updateGlobalVerifiedCount();
                        updateOrRenderGrid(data);
                    }
                } catch (e) { console.error("Sync Error for ID " + prodId, e); }
            }
        };

        // Polling Progress setiap 2 detik
        setInterval(() => window.fetchQCProgress(), 2000);
        function updateOrRenderGrid(data) {
            const emptyState = document.getElementById('empty-state');
            if (emptyState) emptyState.style.display = 'none';

            let card = document.getElementById(`batch-card-${data.production_id}`);

            // Jika kartu belum ada, buat baru dan letakkan di PALING BAWAH (beforeend) agar bersusun ke bawah
            if (!card) {
                const cardHTML = `
                    <div class="batch-card" id="batch-card-${data.production_id}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="text-primary mb-0">#${data.batch}</h5>
                                <small class="text-black font-w600">${data.item}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="position: relative; z-index: 10;">
                                <div class="badge border border-success text-success bg-white shadow-sm" style="font-size: 12px; padding: 6px 12px;">
                                    <span id="progress-text-${data.production_id}" class="font-w800 text-success" style="font-size: 14px;">${data.progress}</span> <span class="ms-1 font-w600">Terverifikasi</span>
                                </div>
                            </div>
                        </div>
                        <div class="cinema-grid" id="grid-${data.production_id}"></div>
                    </div>
                `;
                mapsContainer.insertAdjacentHTML('beforeend', cardHTML);
                card = document.getElementById(`batch-card-${data.production_id}`);
            }

            // Update Progress Text
            document.getElementById(`progress-text-${data.production_id}`).innerText = data.progress;

            // Update Grid Seats
            const grid = document.getElementById(`grid-${data.production_id}`);
            grid.innerHTML = '';
            for (let i = 1; i <= data.copies; i++) {
                const isScanned = data.scanned_list.includes(i);
                const isLast = (i === data.last_no);
                const seat = document.createElement('div');
                seat.className = `seat ${isScanned ? 'occupied' : ''} ${isLast ? 'last-hit' : ''}`;
                seat.innerText = i;
                grid.appendChild(seat);
            }
        }
        function clearTracking() {
            trackedBatchIds = [];
            globalScannedCounts = {};
            updateGlobalVerifiedCount();
            mapsContainer.querySelectorAll('.card:not(#empty-state), .batch-card').forEach(el => el.remove());
            document.getElementById('empty-state').style.display = 'block';
            toastr.info("Daftar pantauan dibersihkan.");
        }

        async function onScanSuccess(decodedText) {
            if (isProcessing) return;
            if (html5QrCode && html5QrCode.isScanning) html5QrCode.pause(true);

            // 1. LOKAL PRE-CHECK: Cegah scan dus ganda sebelum hit API
            const dashIndex = decodedText.indexOf('-');
            if (dashIndex > 0) {
                const labelNo = parseInt(decodedText.substring(0, dashIndex));
                const batchStr = decodedText.substring(dashIndex + 1);
                
                // Cek apakah dus ini sudah ada di dalam DOM Grid (sudah hijau)
                let isAlreadyScannedLocally = false;
                for (const pid of trackedBatchIds) {
                    const batchHeader = document.querySelector(`#batch-card-${pid} h5`);
                    if (batchHeader && batchHeader.innerText.includes(batchStr)) {
                        // Batch cocok, cek apakah kursi (labelNo) sudah ada kelas 'occupied'
                        const seats = document.querySelectorAll(`#grid-${pid} .seat.occupied`);
                        for (let seat of seats) {
                            if (parseInt(seat.innerText) === labelNo) {
                                isAlreadyScannedLocally = true;
                                break;
                            }
                        }
                    }
                    if (isAlreadyScannedLocally) break;
                }

                if (isAlreadyScannedLocally) {
                    new Audio('../assets/sounds/alert.wav').play().catch(e => console.log(e));
                    updateStatus('warning', 'SUDAH DI-SCAN', `Dus #${labelNo} untuk Batch ${batchStr} sudah diverifikasi.`);
                    setTimeout(() => resumeScanner(), 2000);
                    return;
                }
            }

            isProcessing = true;

            try {
                const res = await fetch(`../api/process_scan.php?batch=${encodeURIComponent(decodedText)}&_nocache=${Date.now()}`);
                const data = await res.json();

                if(data.status === 'success') {
                    new Audio('../assets/sounds/success.wav').play().catch(e => {});
                    updateStatus('success', '✓ TERVERIFIKASI', data.message);

                    const prodId = data.data.production_id;
                    if (!trackedBatchIds.includes(prodId)) {
                        trackedBatchIds.push(prodId);
                    }

                    // Update global counts right away from API response
                    if (data.data.scanned_list) {
                        globalScannedCounts[prodId] = data.data.scanned_list.length;
                        updateGlobalVerifiedCount();
                    }

                    updateOrRenderGrid(data.data);
                    setTimeout(() => resumeScanner(), 800);
                } else {
                    new Audio('../assets/sounds/reject.wav').play().catch(e => {});
                    updateStatus('error', '⚠ PERHATIAN', data.message);
                    setTimeout(() => resumeScanner(), 2000);
                }
            } catch (e) {
                updateStatus('error', 'SERVER ERROR', 'Koneksi database terputus');
                setTimeout(() => resumeScanner(), 2000);
            }
        }

        async function initCamera() {
            try {
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    cameraSelect.innerHTML = '';
                    devices.forEach(device => {
                        const opt = document.createElement('option');
                        opt.value = device.id; opt.text = device.label;
                        cameraSelect.appendChild(opt);
                    });
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear'));
                    const targetId = backCam ? backCam.id : devices[0].id;
                    cameraSelect.value = targetId;
                    startScanner(targetId);
                }
            } catch (e) { updateStatus('error', 'Izin Kamera', 'Gagal mengakses list kamera.'); }
        }

        function startScanner(deviceId) {
            if(html5QrCode) html5QrCode.stop().then(() => doStart(deviceId));
            else doStart(deviceId);
        }

        function doStart(deviceId) {
            html5QrCode = new Html5Qrcode("reader");
            
            // Calculate a responsive qrbox size based on window width
            let boxSize = window.innerWidth < 600 ? 200 : 250;
            
            html5QrCode.start(deviceId, { 
                fps: 15, // Optmized frame rate for speed without overheating mobile devices
                qrbox: boxSize,
                aspectRatio: 1.0, // Force a square aspect ratio to prevent severe stretching on rotate
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ] // Restrict to QR only for faster processing
            }, onScanSuccess)
            .then(() => {
                document.getElementById('start-btn-area').style.display = 'none';
                updateStatus('info', 'Scanner Ready', 'Kamera berhasil diaktifkan');
                const settings = html5QrCode.getRunningTrackSettings();
                if (settings.torch) document.getElementById('torch-btn').style.display = 'block';
            });
        }

        function switchCamera(id) { if(id) startScanner(id); }
        function resumeScanner() {
            isProcessing = false;
            if (html5QrCode && html5QrCode.getState() === 3) html5QrCode.resume();
            updateStatus('info', 'Scanner Ready', 'Arahkan kamera ke QR Barang');
        }

        async function toggleTorch() {
            if (!html5QrCode) return;
            isTorchOn = !isTorchOn;
            try { await html5QrCode.applyVideoConstraints({ focusMode: "continuous", advanced: [{ torch: isTorchOn }] }); }
            catch (e) { console.error("Torch error", e); }
        }

        function updateStatus(type, title, msg) {
            statusPanel.classList.remove('bg-success-light', 'bg-danger-light', 'bg-warning-light');
            const icons = {
                success: '<i class="fa fa-check-circle fa-2x text-success"></i>',
                error: '<i class="fa fa-exclamation-triangle fa-2x text-danger"></i>',
                warning: '<i class="fa fa-exclamation-circle fa-2x text-warning"></i>',
                info: '<i class="fa fa-info-circle fa-2x text-primary"></i>'
            };
            statusPanel.className = `status-panel mt-3 bg-${type}-light`;
            document.getElementById('status-icon').innerHTML = icons[type] || icons.info;
            document.getElementById('status-text').innerHTML = `<div class="font-w700 text-${type}">${title}</div><small class="text-dark">${msg}</small>`;
        }

        document.querySelector('a[href="qc_checker.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>