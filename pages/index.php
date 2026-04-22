<!DOCTYPE html>
<html lang="en">
<?php include '../includes/header.php'; ?>
<style>
    /* BUKA KUNCI SCROLL TOTAL */
    html, body { height: auto !important; overflow: auto !important; background: #f4f7fe !important; }
    #main-wrapper { height: auto !important; display: block !important; }
    .content-body { height: auto !important; min-height: 100vh !important; overflow: visible !important; }
    
    /* WIDGET STYLE - EXACT MATCHING DATA_PRODUKSI.PHP but NOT BOLD */
    .widget-stat {
        border-radius: 15px !important;
        border: none !important;
        margin-bottom: 20px !important;
        transition: 0.3s;
    }
    .widget-stat .card-body { padding: 1.5rem !important; }
    .widget-stat .media-body p { 
        color: #ffffff !important; 
        font-weight: 500 !important; /* Lighter */
        font-size: 13px !important; 
        margin-bottom: 2px !important; 
        text-transform: none;
        opacity: 0.95;
    }
    .widget-stat .media-body h3 { 
        color: #ffffff !important; 
        font-weight: 600 !important; /* Lighter than 800 */
        font-size: 28px !important; 
        margin: 0 !important; 
    }
    .widget-stat .media-body small { 
        color: #ffffff !important; 
        font-size: 11px !important;
        opacity: 0.8;
        font-weight: 400; /* Regular */
    }
    .kpi-title-month { 
        font-size: 10px; 
        opacity: 0.7; 
        font-weight: 400; 
        color: #fff !important; 
        display: block;
        margin-top: 5px;
    }
    .widget-stat .me-3 i { 
        font-size: 35px !important; 
        color: #ffffff !important; 
        opacity: 0.4;
    }

    /* CARD & TEMPLATE STYLE */
    .card { border-radius: 15px !important; border: none !important; box-shadow: 0 4px 15px rgba(0,0,0,0.02) !important; margin-bottom: 20px; }
    .card-header { padding: 15px 20px !important; border-bottom: none !important; }
    .card-title { font-weight: 800 !important; color: #000; font-size: 16px !important; }
    
    .batch-item { padding: 12px 0; border-bottom: 1px solid #f1f1f1; display: flex; align-items: center; justify-content: space-between; }
    .batch-item:last-child { border-bottom: none; }

    /* TOOLTIP FONT COLORS */
    #donutChart .apexcharts-tooltip * { 
        color: #ffffff !important; 
    }

    /* OWL CAROUSEL STYLE - CLEAN & NO NAV */
    .card-header .card-title {
        width: 100%;
    }

    /* SKELETON / EMPTY STATE */
    @keyframes shimmer { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
    .sk-line, .sk-block {
        background: linear-gradient(90deg, #eef1f6 8%, #f7f9fc 18%, #eef1f6 33%);
        background-size: 800px 100%;
        animation: shimmer 1.2s linear infinite;
        border-radius: 8px;
    }
    .sk-line { height: 12px; margin-bottom: 10px; }
    .sk-block { height: 180px; margin: 10px 0; }
    .empty-state {
        text-align: center;
        padding: 30px 10px;
        color: #94a3b8;
        font-size: 13px;
    }
    .empty-state i { font-size: 28px; margin-bottom: 8px; opacity: 0.5; display: block; }
    .kpi-loading { opacity: 0.55; letter-spacing: 2px; }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        
        <div class="content-body">
            <div class="container-fluid" style="padding-top: 25px;">
                <div class="row">
                    
                    <!-- COLUMN LEFT (xl-9) - Main Panel (SWAPPED TO LEFT) -->
                    <div class="col-xl-9 col-xxl-8">
                        
                        <!-- ROW 1: WIDGETS (IDENTIC STYLE) -->
                        <div class="row">
                            <?php 
                                $stmt_qc = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key='qc_checker_enabled'");
                                $qc_checker_enabled = ($stmt_qc->fetchColumn() === '1');
                                $col_class = $qc_checker_enabled ? 'col-xl-3' : 'col-xl-4';
                            ?>
                            <div class="col-sm-6 <?php echo $col_class; ?>">
                                <div class="widget-stat card bg-primary shadow-sm card-kpi">
                                    <div class="card-body p-4">
                                        <div class="media">
                                            <span class="me-3"><i class="fa fa-boxes"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white font-w600">Total Produksi</p>
                                                <h3 class="text-white mb-0" id="k-prod">—</h3>
                                                <small class="kpi-title-month">Batch produksi bulan ini</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($qc_checker_enabled): ?>
                            <div class="col-sm-6 <?php echo $col_class; ?>">
                                <div class="widget-stat card bg-danger shadow-sm card-kpi">
                                    <div class="card-body p-4">
                                        <div class="media">
                                            <span class="me-3"><i class="fa fa-clock"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white font-w600">Antrian QC</p>
                                                <h3 class="text-white mb-0" id="k-pending-2">—</h3>
                                                <small class="kpi-title-month">Belum diverifikasi scan</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-sm-6 <?php echo $col_class; ?>">
                                <div class="widget-stat card bg-warning shadow-sm card-kpi">
                                    <div class="card-body p-4">
                                        <div class="media">
                                            <span class="me-3"><i class="fa fa-warehouse"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white font-w600">Stok Gudang</p>
                                                <h3 class="text-white mb-0" id="k-stok-2">—</h3>
                                                <small class="kpi-title-month">Fisik tersedia saat ini</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 <?php echo $col_class; ?>">
                                <div class="widget-stat card bg-success shadow-sm card-kpi">
                                    <div class="card-body p-4">
                                        <div class="media">
                                            <span class="me-3"><i class="fa fa-truck"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white font-w600">Pengiriman</p>
                                                <h3 class="text-white mb-0" id="k-ship">—</h3>
                                                <small class="kpi-title-month">Fisik keluar dari gudang</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: MAIN BAR CHART -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header border-0 pb-0 d-sm-flex d-block">
                                        <div>
                                            <h4 class="card-title">Tren Aktivitas Produksi</h4>
                                        </div>
                                        <div class="card-action card-tabs mt-3 mt-sm-0">
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" href="javascript:void(0)" onclick="setTrendRange('week')" role="tab">Minggu</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="javascript:void(0)" onclick="setTrendRange('month')" role="tab">Bulan</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="javascript:void(0)" onclick="setTrendRange('year')" role="tab">Tahun</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="bar-skeleton" style="height: 350px; display:flex; align-items:flex-end; gap:12px; padding: 30px 10px 10px;">
                                            <div class="sk-block" style="flex:1; height: 60%;"></div>
                                            <div class="sk-block" style="flex:1; height: 85%;"></div>
                                            <div class="sk-block" style="flex:1; height: 40%;"></div>
                                            <div class="sk-block" style="flex:1; height: 72%;"></div>
                                            <div class="sk-block" style="flex:1; height: 55%;"></div>
                                            <div class="sk-block" style="flex:1; height: 90%;"></div>
                                            <div class="sk-block" style="flex:1; height: 48%;"></div>
                                        </div>
                                        <div id="barTrendChart" style="height: 350px; display:none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 3: RECENT LOGS CAROUSEL -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header border-0">
                                        <h4 class="card-title">Log Aktivitas Sistem</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="log-skeleton" class="row g-3">
                                            <div class="col-md-4"><div class="sk-block"></div></div>
                                            <div class="col-md-4 d-none d-md-block"><div class="sk-block"></div></div>
                                            <div class="col-md-4 d-none d-md-block"><div class="sk-block"></div></div>
                                        </div>
                                        <div class="event-bx owl-carousel" id="log-carousel" style="display:none;">
                                            <!-- Logs -->
                                        </div>
                                        <div id="log-empty" class="empty-state" style="display:none;">
                                            <i class="fa fa-inbox"></i>
                                            Belum ada aktivitas
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLUMN RIGHT (xl-3) - Sidebar (SWAPPED TO RIGHT) -->
                    <div class="col-xl-3 col-xxl-4">
                        <div class="row">
                            <div class="col-xl-12 col-md-6">
                                <div class="card">
                                    <div class="card-header pb-0">
                                        <h4 class="card-title">Komposisi Stok</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="donut-skeleton" style="height: 200px; display:flex; align-items:center; justify-content:center;">
                                            <div class="sk-block" style="width:140px; height:140px; border-radius:50%;"></div>
                                        </div>
                                        <div id="donutChart" style="height: 200px; display:none;"></div>
                                        <div class="mt-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-black font-w600 fs-14"><i class="fa fa-circle text-primary me-2"></i>Tersedia</span>
                                                <span id="k-stok">0</span>
                                            </div>
                                            <?php if ($qc_checker_enabled): ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-black font-w600 fs-14"><i class="fa fa-circle text-warning me-2"></i>Menunggu</span>
                                                <span id="k-pending">0</span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-black font-w600 fs-14"><i class="fa fa-circle text-success me-2"></i>Terkirim</span>
                                                <span id="k-shipped-legend">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12 col-md-6">
                                <div class="card">
                                    <div class="card-header pb-0">
                                        <h4 class="card-title">Batch Terbaru</h4>
                                    </div>
                                    <div class="card-body dz-scroll" id="batch-list-side" style="height: 520px; overflow-y: auto; padding-bottom: 25px;">
                                        <div id="batch-skeleton">
                                            <div class="sk-line" style="width:85%"></div>
                                            <div class="sk-line" style="width:60%; margin-bottom:20px"></div>
                                            <div class="sk-line" style="width:90%"></div>
                                            <div class="sk-line" style="width:55%; margin-bottom:20px"></div>
                                            <div class="sk-line" style="width:80%"></div>
                                            <div class="sk-line" style="width:65%"></div>
                                        </div>
                                    </div>
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
        window.qc_checker_enabled = <?= $qc_checker_enabled ? 'true' : 'false' ?>;
        
        let barChart = null;
        let donutChart = null;
        let currentRange = 'week';

        function setTrendRange(range) {
            currentRange = range;
            updateDashboard();
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

        async function updateDashboard() {
            try {
                const res = await fetch(`../api/get_dashboard_stats.php?range=${currentRange}&_nocache=${Date.now()}`);
                const data = await res.json();

                // 2. TAMPILKAN KPI (EXACT NUMBERS)
                const waitUnit = data.total_production - data.total_verified;
                const waitLabel = data.total_kapasitas_labels - data.total_stok_labels;
                const ready = window.qc_checker_enabled ? (data.total_verified - data.total_shipped) : (data.total_production - data.total_shipped);

                // Production
                document.getElementById('k-prod').innerText = formatCompactNumber(data.total_production);
                document.getElementById('k-prod').title = data.total_production.toLocaleString('id-ID');
                
                // Widget Antrian QC (Dus)
                const pending2 = document.getElementById('k-pending-2');
                if (pending2) {
                    pending2.innerText = formatCompactNumber(waitLabel);
                    pending2.title = waitLabel.toLocaleString('id-ID');
                }

                // Stock & Pending (Dus)
                const pendingElement = document.getElementById('k-pending');
                if (pendingElement) {
                    pendingElement.innerText = formatCompactNumber(waitUnit);
                    pendingElement.title = waitUnit.toLocaleString('id-ID');
                }
                
                document.getElementById('k-stok').innerText = formatCompactNumber(ready);
                document.getElementById('k-stok').title = ready.toLocaleString('id-ID');
                
                document.getElementById('k-stok-2').innerText = formatCompactNumber(ready);
                document.getElementById('k-stok-2').title = ready.toLocaleString('id-ID');
                
                // Shipment
                document.getElementById('k-ship').innerText = formatCompactNumber(data.total_shipped);
                document.getElementById('k-ship').title = data.total_shipped.toLocaleString('id-ID');
                
                // Legend for Donut (Terkirim)
                document.getElementById('k-shipped-legend').innerText = formatCompactNumber(data.total_shipped);
                document.getElementById('k-shipped-legend').title = data.total_shipped.toLocaleString('id-ID');

                // 2. DONUT (Using Dus)
                const donutSeries = window.qc_checker_enabled ? [ready, waitUnit, data.total_shipped] : [ready, data.total_shipped];
                const donutLabels = window.qc_checker_enabled ? ['Ready', 'Pending', 'Shipped'] : ['Ready', 'Shipped'];
                const donutColors = window.qc_checker_enabled ? ['#1A237E', '#FFC107', '#00C853'] : ['#1A237E', '#00C853'];

                if(!donutChart) {
                    donutChart = new ApexCharts(document.querySelector("#donutChart"), {
                        series: donutSeries,
                        chart: { type: 'donut', height: 200 },
                        labels: donutLabels,
                        colors: donutColors,
                        legend: { show: false },
                        dataLabels: { enabled: false },
                        tooltip: {
                            enabled: true,
                            theme: 'dark',
                            style: { fontSize: '12px' }
                        },
                        plotOptions: { pie: { donut: { size: '75%' } } }
                    });
                    donutChart.render();
                } else { donutChart.updateSeries(donutSeries); }

                // 3. BAR CHART (3 Berdampingan)
                if(data.trend) {
                    const barSeries = window.qc_checker_enabled 
                        ? [ { name: 'Produksi', data: data.trend.produced }, { name: 'Verified', data: data.trend.verified }, { name: 'Kirim', data: data.trend.shipped } ]
                        : [ { name: 'Produksi', data: data.trend.produced }, { name: 'Kirim', data: data.trend.shipped } ];
                    const barColors = window.qc_checker_enabled ? ['#1A237E', '#FFC107', '#00C853'] : ['#1A237E', '#00C853'];

                    const options = {
                        series: barSeries,
                        chart: { type: 'bar', height: 350, toolbar: { show: false } },
                        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
                        colors: barColors,
                        dataLabels: { enabled: false },
                        xaxis: { categories: data.trend.labels },
                        legend: { position: 'top', horizontalAlign: 'right' },
                        tooltip: { enabled: true, theme: 'light' }
                    };
                    if(!barChart) {
                        barChart = new ApexCharts(document.querySelector("#barTrendChart"), options);
                        barChart.render();
                    } else { barChart.updateOptions(options); }
                }

                // Hilangkan skeleton chart
                document.getElementById('bar-skeleton')?.remove();
                document.getElementById('donut-skeleton')?.remove();
                document.getElementById('barTrendChart').style.display = '';
                document.getElementById('donutChart').style.display = '';

                // 4. BATCH LIST (SIDE)
                const list = document.getElementById('batch-list-side');
                list.innerHTML = '';
                if (!data.recent_batches || data.recent_batches.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fa fa-box-open"></i>
                            Belum ada batch produksi
                        </div>`;
                } else {
                    data.recent_batches.forEach(b => {
                        list.insertAdjacentHTML('beforeend', `
                            <div class="batch-item">
                                <div><p class="font-w700 text-black mb-0" style="font-size:13px;">${b.item}</p><span class="text-primary" style="font-size:11px;">#${b.batch}</span></div>
                                <span class="badge badge-sm light badge-primary">${parseInt(b.total_qty).toLocaleString('id-ID')} Dus</span>
                            </div>
                        `);
                    });
                }

                // 5. LOG CAROUSEL
                const skel = document.getElementById('log-skeleton');
                const carousel = $('#log-carousel');
                const emptyBox = document.getElementById('log-empty');
                const logsToShow = (data.recent_logs || []).slice(0, 6);

                if (skel) skel.style.display = 'none';

                if (logsToShow.length === 0) {
                    carousel.hide();
                    if (emptyBox) emptyBox.style.display = '';
                } else {
                    if (emptyBox) emptyBox.style.display = 'none';
                    carousel.show();

                    let html = '';
                    logsToShow.forEach(l => {
                        html += `
                            <div class="items">
                                <div class="p-4 bg-white" style="border-radius: 20px; border: 1px solid #f0f0f0; min-height: 160px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                                    <span class="badge badge-primary mb-3" style="padding: 6px 15px; border-radius: 10px; text-transform: uppercase; font-weight: 800;">${l.action}</span>
                                    <h4 class="fs-13 font-w700 text-black mb-3" style="line-height: 1.5; height: 40px; overflow: hidden;">${l.details}</h4>
                                    <p class="fs-11 text-muted mb-0"><i class="fa fa-clock me-1"></i>${l.time}</p>
                                </div>
                            </div>
                        `;
                    });

                    if (carousel.hasClass('owl-loaded')) {
                        carousel.trigger('destroy.owl.carousel');
                        carousel.removeClass('owl-loaded').empty();
                    }
                    carousel.html(html);
                    carousel.owlCarousel({
                        loop:true, margin:20, nav:false, dots:false,
                        responsive:{ 0:{ items:1 }, 768:{ items:2 }, 1200:{ items:3 } }
                    });
                }

            } catch (e) {
                console.error(e);
                // Tampilkan empty state kalau fetch gagal total
                const skel = document.getElementById('log-skeleton');
                if (skel) skel.style.display = 'none';
                const emptyBox = document.getElementById('log-empty');
                if (emptyBox) {
                    emptyBox.innerHTML = '<i class="fa fa-exclamation-triangle"></i>Gagal memuat data, coba refresh halaman';
                    emptyBox.style.display = '';
                }
                const list = document.getElementById('batch-list-side');
                if (list && list.querySelector('#batch-skeleton')) {
                    list.innerHTML = '<div class="empty-state"><i class="fa fa-exclamation-triangle"></i>Gagal memuat data</div>';
                }
            }
        }

        // Force-hide preloader segera setelah DOM siap, tidak tunggu semua asset
        document.addEventListener('DOMContentLoaded', () => {
            const pre = document.getElementById('preloader');
            if (pre) {
                pre.style.transition = 'opacity .3s';
                pre.style.opacity = '0';
                setTimeout(() => pre.style.display = 'none', 300);
            }
            document.getElementById('main-wrapper')?.classList.add('show');
        });

        // ---- Polling terkontrol ----
        let dashboardPollId = null;
        const DASHBOARD_INTERVAL_MS = 60000; // 60 detik (sebelumnya 15s)

        function startDashboardPolling() {
            if (dashboardPollId) return;
            dashboardPollId = setInterval(updateDashboard, DASHBOARD_INTERVAL_MS);
        }
        function stopDashboardPolling() {
            if (!dashboardPollId) return;
            clearInterval(dashboardPollId);
            dashboardPollId = null;
        }

        // Pause saat tab tidak aktif, resume saat kembali (fetch sekali saat resume)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopDashboardPolling();
            } else {
                updateDashboard();
                startDashboardPolling();
            }
        });

        // Initial load: defer fetch sampai browser idle agar paint duluan
        function kickOffDashboard() {
            updateDashboard();
            startDashboardPolling();
        }
        if ('requestIdleCallback' in window) {
            requestIdleCallback(kickOffDashboard, { timeout: 1500 });
        } else {
            setTimeout(kickOffDashboard, 300);
        }

        document.querySelector('a[href="index.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>