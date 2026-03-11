<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("X-Content-Type-Options: nosniff");
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Menentukan Judul Halaman Secara Dinamis
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'index.php' => 'Dashboard Utama',
    'production_data.php' => 'Manajemen Produksi',
    'warehouse_inventory.php' => 'Inventori Gudang',
    'activity_logs.php' => 'Log Aktivitas Sistem',
    'shipment_data.php' => 'Riwayat Pengiriman',
    'settings.php' => 'Pengaturan Master Data',
    'qc_checker.php' => 'Pengecekan QC',
    'shipment_scan.php' => 'Quick Scan Pengiriman',
	'access_control.php' => 'Hak Akses'
];
$display_title = $page_titles[$current_page] ?? 'AFTECH System';
?>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="../assets/vendor/chartist/css/chartist.min.css">
    <style>
        .skeleton {
            background: #eee;
            background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
            border-radius: 5px;
            background-size: 200% 100%;
            animation: 1.5s shine linear infinite;
        }
        @keyframes shine { to { background-position-x: -200%; } }
        .skeleton-text { height: 12px; margin-bottom: 6px; width: 100%; }
        .skeleton-badge { height: 20px; width: 60px; }
    </style>
	<link href="../assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
	<link href="../assets/vendor/datatables/css/jquery.dataTables.min.css" rel="stylesheet">
	<link href="../assets/vendor/toastr/css/toastr.min.css" rel="stylesheet">
	<link href="../assets/vendor/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/vendor/owl-carousel/owl.carousel.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
	<style>
		:root {
			--af-primary: #1A237E;      /* Indigo Deep */
			--af-secondary: #3F51B5;    /* Indigo Light */
			--af-accent: #FFC107;       /* Amber/Gold */
			--af-success: #00C853;      /* Status Green */
			--af-danger: #D50000;       /* Status Red */
		}
		body, .form-label, .form-control, .nav-text, .card-title, .content-body, table, span, p, h1, h2, h3, h4, h5, h6, select, option, textarea {
			color: #212529;
		}
		/* ========================================================
		   AFTECH SIDEBAR SMART COLORS (FIXED - FULL WHITE MODE)
		   ======================================================== */
		
		/* 1. Sidebar Latar Belakang (Selalu Putih) */
		.deznav, .metismenu {
			background: #ffffff !important;
		}

		/* Default State: Teks & Ikon Hitam */
		.metismenu li a {
			background: transparent !important;
		}
		.metismenu li a i, 
		.metismenu li a span { 
			color: #000000 !important; 
			font-weight: 500 !important; /* Diperingan */
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}

		/* Memaksa Ikon Flaticon agar tidak bold/tebal */
		.metismenu li a i:before {
			font-weight: normal !important;
		}

		/* Aktif State (SEMUA MODE): Tanpa Latar Biru, Hanya Warna Konten Indigo */
		.metismenu li.mm-active > a {
			background: transparent !important;
		}
		.metismenu li.mm-active > a i, 
		.metismenu li.mm-active > a span {
			color: var(--af-primary) !important;
			fill: var(--af-primary) !important;
		}

		/* Hover State: Indigo kontras */
		.metismenu li a:hover i, 
		.metismenu li a:hover span {
			color: var(--af-primary) !important;
		}

		/* Hover State: Hanya berubah warna teks ke Indigo */
		.metismenu li a:hover i, 
		.metismenu li a:hover span {
			color: var(--af-primary) !important;
		}
		/* Memaksa elemen icon dan text menjadi putih pada background Indigo/Primary */
		.bg-primary i, .bg-primary svg, .bg-primary span,
		.btn-primary i, .btn-primary svg, .btn-primary span,
		.btn-indigo i, .btn-indigo svg, .btn-indigo span,
		.bg-secondary i, .bg-secondary svg, .bg-secondary span,
		.btn-secondary i, .btn-secondary svg, .btn-secondary span,
		.badge-primary i, .badge-primary svg, .badge-primary span,
		.badge-success i, .badge-success svg, .badge-success span,
		.btn-danger i, .btn-danger svg, .btn-danger span,
		.btn-info i, .btn-info svg, .btn-info span {
			color: #ffffff !important;
			fill: #ffffff !important;
		}

		/* Khusus tombol Edit (Primary) dan Hapus (Danger) agar iconnya fix putih */
		.btn-xs.sharp i {
			color: #ffffff !important;
		}
		
		/* Custom Table Styles */
		.table.shadow-hover tbody tr { transition: all 0.2s ease; border-left: 4px solid transparent; }
		.table.shadow-hover tbody tr:hover {
			background-color: rgba(26, 35, 126, 0.04) !important;
			transform: scale(1.002);
			box-shadow: 0 4px 15px rgba(0,0,0,0.05);
			border-left: 4px solid var(--af-primary);
		}
		/* Menyelaraskan Tinggi Nav Header & Header Utama (Fix Desktop & Mobile) */
		.nav-header, .header {
			height: 80px !important;
		}
		.nav-control {
			height: 80px !important;
			display: flex;
			align-items: center;
		}
		.dashboard_bar {
			line-height: 80px !important;
			padding-top: 0 !important;
			padding-bottom: 0 !important;
		}
		@media (max-width: 768px) {
			.nav-header {
				width: 80px !important;
			}
			.header {
				width: 100% !important;
				padding-left: 80px !important;
			}
		}
		/* SOFT & CLEAN TOASTR NOTIFICATION */
		#toast-container > div {
			border-radius: 12px !important;
			box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
			opacity: 1 !important;
			padding: 15px 20px 15px 55px !important;
			font-family: 'Poppins', sans-serif !important;
			font-weight: 600 !important;
			font-size: 13px !important;
			width: auto !important;
			min-width: 300px !important;
			max-width: 450px !important;
		}
		.toast-success { background-color: #00C853 !important; }
		.toast-error { background-color: #D50000 !important; }
		.toast-info { background-color: #1A237E !important; }
		.toast-warning { background-color: #FFC107 !important; color: #000 !important; }
	</style>
	<script>
		// Global Toastr Configuration (Sync with uc-toastr format)
		window.addEventListener('load', function() {
			if(typeof toastr !== 'undefined') {
				toastr.options = {
					"closeButton": true,
					"debug": false,
					"newestOnTop": true,
					"progressBar": true,
					"positionClass": "toast-top-right",
					"preventDuplicates": false,
					"onclick": null,
					"showDuration": "300",
					"hideDuration": "1000",
					"timeOut": "5000",
					"extendedTimeOut": "1000",
					"showEasing": "swing",
					"hideEasing": "linear",
					"showMethod": "fadeIn",
					"hideMethod": "fadeOut"
				};
			}
		});
	</script>
    <style>
        :root {
            --af-primary: #1A237E;
            --af-primary-light: #3F51B5;
            --af-accent: #FFC107;
        }
        
        /* OVERRIDE PINK/ORANGE HOVERS & BADGES */
        .btn-primary:hover { background-color: var(--af-primary-light) !important; border-color: var(--af-primary-light) !important; }
        .btn-primary:active, .btn-primary:focus { background-color: var(--af-primary) !important; border-color: var(--af-primary) !important; }
        
        /* Table Hovers */
        .table.shadow-hover tbody tr:hover { background-color: rgba(26, 35, 126, 0.05) !important; }
        
        /* Force Indigo on primary light elements */
        .badge-primary.light { background-color: rgba(26, 35, 126, 0.1) !important; color: var(--af-primary) !important; }
        .text-primary { color: var(--af-primary) !important; }
        .bg-primary { background-color: var(--af-primary) !important; }
        
        /* Pagination */
        .pagination .page-item.active .page-link { background-color: var(--af-primary) !important; border-color: var(--af-primary) !important; }
        
        /* Fix for potential pinkish buttons in template */
        [data-primary="color_1"] .btn-primary:hover,
        .btn-primary:hover { background-color: var(--af-primary-light) !important; border-color: var(--af-primary-light) !important; }
    </style>
</head>

<!--**********************************
			Header start
***********************************-->
<div class="header">
	<div class="header-content">
		<nav class="navbar navbar-expand">
			<div class="collapse navbar-collapse justify-content-between">
				<div class="header-left">
					<div class="dashboard_bar" style="color: var(--af-primary); font-weight: 800; font-size: 22px;">
						<?php echo $display_title; ?>
					</div>
				</div>
				<ul class="navbar-nav header-right">
					<li class="nav-item">
						<a class="nav-link ai-icon" href="javascript:void(0)" onclick="toggleFullScreen()" title="Full Screen">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--af-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
						</a>
					</li>
					<!-- NOTIFIKASI DIUBAH JADI LOG AKTIVITAS -->
					<li class="nav-item dropdown notification_dropdown">
						<a class="nav-link ai-icon" href="javascript:void(0)" role="button" data-bs-toggle="dropdown" title="Log Aktivitas">
							<svg width="24" height="24" viewBox="0 0 28 28" fill="none"><path d="M14 2.33331C14.6428 2.33331 15.1667 2.85598 15.1667 3.49998V5.91732C16.9003 6.16698 18.5208 6.97198 19.7738 8.22498C21.3057 9.75681 22.1667 11.8346 22.1667 14V18.3913L23.1105 20.279C23.562 21.1831 23.5142 22.2565 22.9822 23.1163C22.4513 23.9761 21.5122 24.5 20.5018 24.5H7.49817C6.48667 24.5 5.54752 23.9761 5.01669 23.1163C4.48469 22.2565 4.43684 21.1831 4.88951 20.279L5.83333 18.3913V14C5.83333 11.8346 6.69319 9.75681 8.22502 8.22498C9.47919 6.97198 11.0985 6.16698 12.8333 5.91732V3.49998C12.8333 2.85598 13.356 2.33331 14 2.33331Z" fill="var(--af-primary)"/></svg>
							<div class="pulse-css" style="background: var(--af-accent);"></div>
						</a>
						<div class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width: 300px; border-radius: 20px; overflow: hidden;">
							<div id="DZ_W_Notification1" class="widget-media dz-scroll p-3" style="height: 350px;">
								<h6 class="border-bottom pb-3 mb-3 text-black font-w800" style="font-size: 14px; letter-spacing: -0.5px;">Log Aktivitas Terbaru</h6>
								<ul class="timeline" id="header-log-list" style="margin: 0; padding: 0;">
									<li class="text-center py-4 small text-muted">Memuat log...</li>
								</ul>
							</div>
							<a class="all-notification d-flex justify-content-center align-items-center py-3" href="activity_logs.php" style="background: #f8f9ff; color: var(--af-primary); font-weight: 800; font-size: 13px; transition: 0.3s; border-top: 1px solid #f1f5f9;">
								<span>Lihat Semua Log</span>
								<i class="fas fa-chevron-right ms-2" style="font-size: 10px;"></i>
							</a>
						</div>
					</li>
					<li class="nav-item dropdown header-profile">
						<a class="nav-link" href="javascript:void(0)" role="button" data-bs-toggle="dropdown">
							<div style="width: 35px; height: 35px; border-radius: 50%; background: var(--af-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
								<?php echo substr($_SESSION['full_name'] ?? 'U', 0, 1); ?>
							</div>
						</a>
						<div class="dropdown-menu dropdown-menu-end">
							<div class="dropdown-item ai-icon border-bottom mb-2" style="pointer-events: none;">
								<p class="mb-0 text-primary font-weight-bold"><?php echo $_SESSION['full_name'] ?? 'User'; ?></p>
								<small class="text-muted"><?php echo strtoupper($_SESSION['role'] ?? 'Guest'); ?></small>
							</div>
							<a href="auth/logout.php" class="dropdown-item ai-icon text-danger">
								<svg id="icon-logout" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
								<span class="ms-2">Logout </span>
							</a>
						</div>
					</li>
				</ul>
			</div>
		</nav>
	</div>
</div>

<script>
    // Live AJAX Log untuk Header Dropdown (Soft & Minimalist UI)
    async function fetchHeaderLogs() {
        const logCont = document.getElementById('header-log-list');
        if(!logCont) return;
        try {
            const res = await fetch('../api/get_dashboard_stats.php?_nocache=' + Date.now());
            const data = await res.json();
            if(data.recent_logs && data.recent_logs.length > 0) {
                logCont.innerHTML = '';
                data.recent_logs.forEach(l => {
                    let color = '#64748b'; // Default Grey
                    const action = l.action.toUpperCase();
                    
                    if(action.includes('TAMBAH')) color = '#10b981'; // Emerald
                    else if(action.includes('EDIT')) color = '#3b82f6'; // Blue
                    else if(action.includes('HAPUS')) color = '#ef4444'; // Rose
                    else if(action.includes('LOGIN')) color = '#f59e0b'; // Amber
                    else if(action.includes('SCAN')) color = '#6366f1'; // Indigo

                    logCont.insertAdjacentHTML('beforeend', `
                        <li style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; list-style: none;">
                            <div class="d-flex align-items-start">
                                <div style="width: 4px; height: 35px; background: ${color}; border-radius: 10px; margin-right: 12px; opacity: 0.6;"></div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 11px; font-weight: 800; color: ${color}; text-transform: uppercase; letter-spacing: 0.5px;">${l.action}</span>
                                        <span style="font-size: 10px; color: #94a3b8; font-weight: 500;">${l.time}</span>
                                    </div>
                                    <p style="font-size: 12px; color: #334155; margin: 0; white-space: nowrap; overflow: hidden; text-truncate: ellipsis; font-weight: 500;">${l.details}</p>
                                </div>
                            </div>
                        </li>
                    `);
                });
            } else {
                logCont.innerHTML = '<li class="text-center py-4 small text-muted">No recent activity.</li>';
            }
        } catch(e) {}
    }
    fetchHeaderLogs();
    setInterval(fetchHeaderLogs, 10000);
</script>
