<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
$role = $_SESSION['role'] ?? 'gudang';
if($role !== 'admin') { header("Location: index.php"); exit; }
?>
<style>
    .log-table thead th {
        background: #f8f9fa;
        color: #1A237E;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        border: none;
    }
    .badge-soft {
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 0.5px;
    }
    .badge-soft-success { background: #E8F5E9; color: #2E7D32; }
    .badge-soft-danger { background: #FFEBEE; color: #C62828; }
    .badge-soft-info { background: #E1F5FE; color: #0277BD; }
    .badge-soft-warning { background: #FFF8E1; color: #EF6C00; }
    .badge-soft-primary { background: #E8EAF6; color: #1A237E; }
    
    .log-icon-box {
        width: 32px; height: 32px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        margin-right: 12px; font-size: 14px;
    }

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
        .header-btn-group .btn { width: 100%; justify-content: center; }
    }

    /* TABLE STYLE */
    .table-responsive-md .shadow-hover tbody tr { cursor: pointer; transition: 0.2s; }
    .table-responsive-md .shadow-hover tbody tr:hover { background-color: #f8f9ff !important; }

    .card-kpi { border-radius: 15px; border: none; transition: 0.3s; background: #fff; }
    .card-kpi .card-body { padding: 1.5rem; }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        <div class="content-body">
            <div class="container-fluid">
            
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                            <div class="card-body p-3 p-md-4">
                                <div class="filter-card-header">
                                    <div>
                                        <h4 class="text-black mb-0 font-w800"><i class="fas fa-history text-primary me-2"></i>Histori Aktivitas Sistem</h4>
                                        <p class="mb-0 small text-muted">Jejak audit seluruh transaksi dan perubahan data</p>
                                    </div>
                                    <div class="header-btn-group">
                                        <button onclick="refreshLog()" class="btn btn-light btn-xs shadow-sm font-w600"><i class="fa fa-sync-alt text-primary me-1"></i> Refresh</button>
                                        <button onclick="hapusLog()" class="btn btn-light btn-xs shadow-sm text-danger font-w600"><i class="fas fa-trash-alt me-1"></i> Bersihkan Log</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="tableLog" class="table log-table shadow-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4"><strong>WAKTU</strong></th>
                                                <th><strong>AKSI</strong></th>
                                                <th class="pe-4"><strong>DETAIL AKTIVITAS</strong></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tableLogBody">
                                            <tr><td colspan="3" class="text-center py-5">Memuat riwayat aktivitas...</td></tr>
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

        window.renderLog = async function(isManual = false) {
        const tbody = document.getElementById('tableLogBody');
        if(!tbody) return;

        try {
            if(isManual) tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5"><i class="fa fa-spinner fa-spin text-primary fa-2x"></i></td></tr>';

            const res = await fetch(`../api/get_logs.php?_nocache=${Date.now()}`, {
                cache: 'no-store',
                headers: { 'Pragma': 'no-cache', 'Cache-Control': 'no-cache' }
            });
            const response = await res.json();

            displayLogData(response.data || response);
            if(isManual) toastr.success('Data aktivitas diperbarui');
        } catch (e) {
                console.error("Log AJAX Error:", e);
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5 text-danger">Gagal memuat data histori.</td></tr>';
            }
        }

        window.refreshLog = () => renderLog(true);

        // Auto-Polling: Refresh Live setiap 5 detik
        setInterval(() => {
            if (!document.hidden) renderLog();
        }, 5000);

        function displayLogData(logs) {
            const tbody = document.getElementById('tableLogBody');
            if(!tbody) return;
            tbody.innerHTML = '';
            
            if(logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5 text-muted">Belum ada aktivitas yang tercatat.</td></tr>';
                return;
            }

            logs.forEach((log) => {
                let badgeClass = 'badge-soft-primary';
                let iconType = 'fas fa-info-circle';
                let iconColor = '#1A237E';
                let iconBg = '#E8EAF6';

                const action = log.action ? log.action.toUpperCase() : 'INFO';

                if(action.includes('TAMBAH') || action.includes('INPUT')) {
                    badgeClass = 'badge-soft-success'; iconType = 'fas fa-plus'; iconColor = '#2E7D32'; iconBg = '#E8F5E9';
                } else if(action.includes('EDIT') || action.includes('UPDATE')) {
                    badgeClass = 'badge-soft-info'; iconType = 'fas fa-pencil-alt'; iconColor = '#0277BD'; iconBg = '#E1F5FE';
                } else if(action.includes('HAPUS') || action.includes('DELETE')) {
                    badgeClass = 'badge-soft-danger'; iconType = 'fas fa-trash-alt'; iconColor = '#C62828'; iconBg = '#FFEBEE';
                } else if(action.includes('LOGIN')) {
                    badgeClass = 'badge-soft-warning'; iconType = 'fas fa-key'; iconColor = '#EF6C00'; iconBg = '#FFF8E1';
                } else if(action.includes('SCAN')) {
                    badgeClass = 'badge-soft-primary'; iconType = 'fas fa-barcode'; iconColor = '#1A237E'; iconBg = '#E8EAF6';
                }

                const dateObj = new Date(log.timestamp);
                const timeStr = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const dateStr = dateObj.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td class="ps-4">
                            <span class="text-black font-w700 d-block">${timeStr}</span>
                            <small class="text-muted">${dateStr}</small>
                        </td>
                        <td>
                            <span class="badge ${badgeClass}"><i class="${iconType} me-1"></i> ${action}</span>
                        </td>
                        <td class="pe-4">
                            <div class="text-black font-w600" style="max-width: 500px; white-space: normal; line-height: 1.4;">${log.details}</div>
                        </td>
                    </tr>
                `);
            });
        }

        async function hapusLog() {
            Swal.fire({
                title: 'Bersihkan Seluruh Log?',
                text: "Tindakan ini permanen dan tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D50000',
                cancelButtonColor: '#333',
                confirmButtonText: 'Ya, Hapus Semua Log',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const res = await fetch('../api/admin_manage.php?action=clear_logs');
                        const data = await res.json();
                        if(data.status === 'success') {
                            toastr.success('Histori aktivitas berhasil dibersihkan.');
                            renderLog();
                        } else {
                            toastr.error('Gagal menghapus log.');
                        }
                    } catch(e) {
                        toastr.error('Terjadi kesalahan server.');
                    }
                }
            });
        }

        renderLog();
        document.querySelector('a[href="log_aktivitas.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>