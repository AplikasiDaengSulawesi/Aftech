<!DOCTYPE html>
<html lang="en">
<?php include '../includes/header.php' ?>
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
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        
        <div class="content-body">
            <div class="container-fluid" style="padding-top: 25px;">
                <div class="row">
                    
                    <!-- COLUMN LEFT (xl-3) - Like Template Index -->
                    <div class="col-xl-3 col-xxl-4">
                        <div class="row">
                            <div class="col-xl-12 col-md-6">
                                <div class="card">
                                    <div class="card-header pb-0">
                                        <h4 class="card-title">Komposisi Stok</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="donutChart" style="height: 200px;"></div>
                                        <div class="mt-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-black font-w600 fs-14"><i class="fa fa-circle text-primary me-2"></i>Tersedia</span>
                                                <span id="k-stok">0</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-black font-w600 fs-14"><i class="fa fa-circle text-warning me-2"></i>Menunggu</span>
                                                <span id="k-pending">0</span>
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
                                    <div class="card-body pb-0 dz-scroll" id="batch-list-side" style="height: 420px; overflow-y: auto;">
                                        <!-- Batches -->
                                    </div>
                                    <div class="card-footer text-center border-0">
                                        <a href="data_produksi.php" class="btn btn-primary btn-sm light w-100">Lihat Semua</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLUMN RIGHT (xl-9) - Main Panel -->
                    <div class="col-xl-9 col-xxl-8">
                        
                        <!-- ROW 1: WIDGETS (EXACT Data Produksi Style) -->
                        <div class="row">
                            <div class="col-sm-6 col-xl-3">
                                <div class="widget-stat card bg-primary shadow-sm">
                                    <div class="card-body">
                                        <div class="media">
                                            <span class="me-3"><i class="flaticon-381-box"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white">Total Produksi</p>
                                                <h3 class="text-white mb-0" id="k-prod">0</h3>
                                                <small class="d-block mt-1">Unit Terdaftar</small>
                                                <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="widget-stat card bg-danger shadow-sm">
                                    <div class="card-body">
                                        <div class="media">
                                            <span class="me-3"><i class="flaticon-381-clock-1"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white">Antrian QC</p>
                                                <h3 class="text-white mb-0" id="k-pending-2">0</h3>
                                                <small class="d-block mt-1">Belum Diverifikasi</small>
                                                <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="widget-stat card bg-warning shadow-sm">
                                    <div class="card-body">
                                        <div class="media">
                                            <span class="me-3"><i class="flaticon-381-layer-1"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white">Stok Gudang</p>
                                                <h3 class="text-white mb-0" id="k-stok-2">0</h3>
                                                <small class="d-block mt-1">Unit Siap Kirim</small>
                                                <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="widget-stat card bg-success shadow-sm">
                                    <div class="card-body">
                                        <div class="media">
                                            <span class="me-3"><i class="flaticon-381-send"></i></span>
                                            <div class="media-body text-white text-end">
                                                <p class="mb-1 text-white">Pengiriman</p>
                                                <h3 class="text-white mb-0" id="k-ship">0</h3>
                                                <small class="d-block mt-1">Unit Terdistribusi</small>
                                                <span class="kpi-title-month"><i class="fa fa-calendar-alt me-1"></i> Bulan Ini</span>
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
                                    <div class="card-header pb-0">
                                        <h4 class="card-title">Tren Aktivitas Produksi (7 Hari)</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="barTrendChart" style="height: 350px;"></div>
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
                                        <div class="event-bx owl-carousel" id="log-carousel">
                                            <!-- Logs -->
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
        let barChart = null;
        let donutChart = null;

        async function updateDashboard() {
            try {
                const res = await fetch(`../api/get_dashboard_stats.php?_nocache=${Date.now()}`);
                const data = await res.json();

                // 1. STATS
                const ready = data.total_warehouse - data.total_shipped;
                const wait = data.total_production - data.total_warehouse;
                
                document.getElementById('k-prod').innerText = data.total_production.toLocaleString();
                document.getElementById('k-pending').innerText = wait.toLocaleString();
                document.getElementById('k-pending-2').innerText = wait.toLocaleString();
                document.getElementById('k-stok').innerText = ready.toLocaleString();
                document.getElementById('k-stok-2').innerText = ready.toLocaleString();
                document.getElementById('k-ship').innerText = data.total_shipped.toLocaleString();

                // 2. DONUT
                if(!donutChart) {
                    donutChart = new ApexCharts(document.querySelector("#donutChart"), {
                        series: [ready, wait, data.total_shipped],
                        chart: { type: 'donut', height: 200 },
                        labels: ['Ready', 'Pending', 'Shipped'],
                        colors: ['#1A237E', '#FFC107', '#00C853'],
                        legend: { show: false },
                        dataLabels: { enabled: false },
                        plotOptions: { pie: { donut: { size: '75%' } } }
                    });
                    donutChart.render();
                } else { donutChart.updateSeries([ready, wait, data.total_shipped]); }

                // 3. BAR CHART (3 Berdampingan)
                if(data.trend) {
                    const options = {
                        series: [
                            { name: 'Produksi', data: data.trend.produced },
                            { name: 'Verified', data: data.trend.verified },
                            { name: 'Kirim', data: data.trend.shipped }
                        ],
                        chart: { type: 'bar', height: 350, toolbar: { show: false } },
                        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
                        colors: ['#1A237E', '#FFC107', '#00C853'],
                        dataLabels: { enabled: false },
                        xaxis: { categories: data.trend.labels },
                        legend: { position: 'top', horizontalAlign: 'right' }
                    };
                    if(!barChart) {
                        barChart = new ApexCharts(document.querySelector("#barTrendChart"), options);
                        barChart.render();
                    } else { barChart.updateOptions(options); }
                }

                // 4. BATCH LIST (SIDE)
                const list = document.getElementById('batch-list-side');
                list.innerHTML = '';
                data.recent_batches.forEach(b => {
                    list.insertAdjacentHTML('beforeend', `
                        <div class="batch-item">
                            <div><p class="font-w700 text-black mb-0" style="font-size:13px;">${b.item}</p><span class="text-primary" style="font-size:11px;">#${b.batch}</span></div>
                            <span class="badge badge-sm light badge-primary">${parseInt(b.total_qty).toLocaleString()} Unit</span>
                        </div>
                    `);
                });

                // 5. LOG CAROUSEL
                const carousel = document.getElementById('log-carousel');
                let html = '';
                data.recent_logs.forEach(l => {
                    html += `
                        <div class="items">
                            <div class="p-4 bg-light" style="border-radius: 20px; border: 1px solid #eee; min-height: 160px;">
                                <span class="badge badge-primary mb-3">${l.action}</span>
                                <h4 class="fs-14 font-w700 text-black mb-2" style="height: 40px; overflow: hidden;">${l.details}</h4>
                                <p class="fs-12 text-muted mb-0"><i class="fa fa-clock me-2"></i>${l.time}</p>
                            </div>
                        </div>
                    `;
                });
                carousel.innerHTML = html;
                if ($(carousel).hasClass('owl-loaded')) { $(carousel).trigger('destroy.owl.carousel'); $(carousel).removeClass('owl-loaded'); }
                $(carousel).owlCarousel({
                    loop:true, margin:20, nav:true, dots:false,
                    navText: ['<i class="fa fa-caret-left"></i>', '<i class="fa fa-caret-right"></i>'],
                    responsive:{ 0:{ items:1 }, 768:{ items:2 }, 1200:{ items:3 } }
                });

            } catch (e) { console.error(e); }
        }

        updateDashboard();
        setInterval(updateDashboard, 15000);
        document.querySelector('a[href="index.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>