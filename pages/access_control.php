<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
require_once '../includes/auth_check.php';
protect_page('access_control');
require_once '../includes/db.php';
?>
<style>
    .content-card { border-radius: 15px; border: none; min-height: 600px; }
    .table thead th { background: #f8f9fa; border: none; font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
    .badge-status { padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; }
    .bg-light-warning { background-color: #fff9e6 !important; }
    .pin-code-display { font-family: monospace; font-weight: 900; color: #1A237E; letter-spacing: 1px; font-size: 14px; }
    
    /* TABLE STYLE */
    .table-responsive-md .shadow-hover tbody tr { cursor: pointer; transition: 0.2s; }
    .table-responsive-md .shadow-hover tbody tr:hover { background-color: #f8f9ff !important; }

    /* ACTION MENU (SweetAlert Custom) */
    .action-list { padding: 0; margin: 0; }
    .action-item {
        display: flex; align-items: center; padding: 12px 15px; color: #333; font-weight: 600; font-size: 14px;
        cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f1f1f1; width: 100%; background: none; border-left: none; border-right: none; border-top: none;
        text-align: left; text-decoration: none;
    }
    .action-item:last-child { border-bottom: none; }
    .action-item:hover { background: #f8f9ff; color: #1A237E; }
    .action-item i { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 12px; font-size: 14px; }
    .icon-view { background: #e8eaf6; color: #1A237E; }
    .icon-edit { background: #fff8e1; color: #ffa000; }
    .icon-delete { background: #ffebee; color: #d32f2f; }
    
    .swal2-close-custom {
        position: absolute; top: 12px; right: 12px; background: #f5f5f5; border-radius: 50%; width: 26px; height: 26px;
        display: flex; align-items: center; justify-content: center; cursor: pointer; color: #999; font-size: 12px;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .nav-tabs .nav-link { font-size: 12px; padding: 10px 12px; }
        .device-header { flex-direction: column; align-items: flex-start !important; gap: 10px; }
        .pin-code-display { font-size: 12px; }
        .table td, .table th { font-size: 12px; padding: 10px 8px !important; }
    }
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
                        <div class="card content-card shadow-sm">
                            <div class="card-header border-0 pb-0">
                                <ul class="nav nav-tabs border-0" role="tablist">
                                    <li class="nav-item"><a class="nav-link active font-w600" data-bs-toggle="tab" href="#tab-devices"><i class="la la-mobile-alt me-2"></i> Perangkat & API</a></li>
                                    <li class="nav-item"><a class="nav-link font-w600" data-bs-toggle="tab" href="#tab-roles"><i class="la la-user-shield me-2"></i> Hak Akses Role</a></li>
                                </ul>
                            </div>
                            <div class="card-body p-0">
                                <div class="tab-content">
                                    <!-- TAB 1: DEVICES -->
                                    <div class="tab-pane fade show active" id="tab-devices">
                                        <div class="p-4 d-flex justify-content-between align-items-center bg-light-warning mb-3 mx-md-4 mx-2 mt-4 device-header" style="border-radius:10px;">
                                            <div><h5 class="mb-0 text-black font-w600">Manajemen Perangkat Mobile</h5><p class="small text-muted mb-0">Kelola API Keys dan PIN Reset</p></div>
                                            <div class="alert alert-info py-2 px-3 mb-0 border-0 small"><i class="fa fa-info-circle me-1"></i> UUID Lock Aktif</div>
                                        </div>
                                        <div class="table-responsive px-md-4 px-2 pb-4">
                                            <table class="table shadow-hover mb-0">
                                                <thead><tr><th class="ps-0">PERANGKAT & UUID</th><th>API KEY</th><th>PIN RESET</th><th>STATUS</th><th class="text-center">AKSI</th></tr></thead>
                                                <tbody id="tbody-access"></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- TAB 2: ROLES -->
                                    <div class="tab-pane fade" id="tab-roles">
                                        <div class="p-4">
                                            <div class="mb-4"><h5 class="text-black font-w600">Matriks Hak Akses Halaman</h5><p class="small text-muted">Tentukan akses menu per Role User.</p></div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center">
                                                    <thead class="bg-light"><tr><th class="text-start ps-4" style="width: 300px;">MODUL</th><th>ADMIN</th><th>QC</th><th>GUDANG</th></tr></thead>
                                                    <tbody id="tbody-roles">
                                                        <?php 
                                                        $pages = [
                                                            ['name'=>'Dashboard','slug'=>'dashboard'],
                                                            ['name'=>'Data Produksi','slug'=>'production_data'],
                                                            ['name'=>'QC Checker','slug'=>'qc_checker'],
                                                            ['name'=>'Inventory','slug'=>'warehouse'],
                                                            ['name'=>'Pengiriman','slug'=>'shipment_reports'],
                                                            ['name'=>'Laporan Rekap','slug'=>'reports'],
                                                            ['name'=>'Master Data','slug'=>'settings'],
                                                            ['name'=>'Hak Akses','slug'=>'access_control'],
                                                            ['name'=>'Log','slug'=>'activity_logs']
                                                        ];
                                                        foreach($pages as $p):
                                                        ?>
                                                        <tr><td class="text-start ps-4 font-w600 text-black"><?= $p['name'] ?></td><td><input type="checkbox" checked disabled></td><td><input type="checkbox" class="perm-check" data-role="qc" data-page="<?= $p['slug'] ?>"></td><td><input type="checkbox" class="perm-check" data-role="gudang" data-page="<?= $p['slug'] ?>"></td></tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-end mt-4"><button onclick="savePermissions()" class="btn btn-primary px-5">Simpan Konfigurasi</button></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODALS -->
        <div class="modal fade" id="modalApprove" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formApprove"><div class="modal-header bg-success text-white border-0"><h5>Setujui Akses</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="approve_id"><div class="mb-3"><label class="small">Nama Perangkat</label><input type="text" id="approve_name" class="form-control bg-light" readonly></div><div class="mb-3"><label class="small">Set PIN Reset (4 Digit)</label><input type="text" name="reset_pin" class="form-control text-center font-w900" style="font-size:24px; letter-spacing:5px;" maxlength="4" value="0503" required></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-success btn-sm w-100">Setujui & Aktifkan</button></div></form></div></div></div>
        <div class="modal fade" id="modalEditPin" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formEditPin"><div class="modal-header bg-primary text-white border-0"><h5>Update PIN</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="editpin_id"><div class="mb-3"><label class="small">PIN Baru</label><input type="text" name="reset_pin" id="editpin_val" class="form-control text-center font-w900" style="font-size:24px; letter-spacing:5px;" maxlength="4" required></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm w-100">Simpan</button></div></form></div></div></div>

        <?php include '../includes/footer.php' ?>
    </div>

    <script>
        async function loadAccessData() {
            try {
                const res = await fetch(`../api/get_settings_data.php?type=api_key&_nocache=${Date.now()}`);
                const data = await res.json();
                window.latestAccessData = data;
                renderTable(data);
            } catch(e) { console.error(e); }
        }

        function renderTable(data) {
            const tbody = document.getElementById('tbody-access');
            tbody.innerHTML = '';
            data.forEach((row, index) => {
                let html = `
                <tr onclick="showAccessActions(${index})">
                    <td class="ps-4">
                        <div class="font-w600 ${row.status==='pending'?'text-warning':'text-primary'}">${row.device_name}</div>
                        <small class="text-muted d-block" style="font-size:9px;">${row.device_uuid || '-'}</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="small font-w900 text-black me-3" style="letter-spacing:1px; font-family:monospace; min-width:80px;" id="apikey-text-${row.id}">********</div>
                            <button class="btn btn-light btn-xs sharp shadow-sm" onclick="event.stopPropagation(); toggleApiKeyVisibility(${row.id}, '${row.api_key}')">
                                <i class="fa fa-eye" id="apikey-icon-${row.id}"></i>
                            </button>
                        </div>
                    </td>
                    <td><span class="pin-code-display">${row.reset_pin || '-'}</span></td>
                    <td><span class="badge badge-${row.status==='approved'?'success':'warning'} light font-w800">${row.status.toUpperCase()}</span></td>
                    <td class="text-center text-muted"><small>Klik Baris</small></td>
                </tr>`;
                tbody.insertAdjacentHTML('beforeend', html);
            });
        }

        window.showAccessActions = function(index) {
            const row = window.latestAccessData[index];
            let actionsHtml = '';
            if (row.status === 'pending') {
                actionsHtml = `<button onclick="Swal.close(); openApproveModal(${row.id}, '${row.device_name}')" class="action-item"><i class="fa fa-check icon-view" style="background:#e8f5e9; color:#2e7d32;"></i> Setujui Akses</button>`;
            } else {
                actionsHtml = `<button onclick="Swal.close(); openEditPinModal(${row.id}, '${row.reset_pin}')" class="action-item"><i class="fa fa-key icon-edit"></i> Ubah PIN Reset</button>`;
            }

            Swal.fire({
                html: `<div class="swal2-close-custom" onclick="Swal.close()"><i class="fa fa-times"></i></div><div class="text-center mb-3"><small class="text-muted d-block mb-1">Perangkat</small><strong class="text-black">${row.device_name}</strong></div><div class="action-list">${actionsHtml}<button onclick="Swal.close(); deleteAccess(${row.id})" class="action-item text-danger"><i class="fa fa-trash icon-delete"></i> Hapus Akses</button></div>`,
                showConfirmButton: false, padding: '1.2rem', width: '320px', borderRadius: '15px'
            });
        };

        async function loadPermissions() {
            try {
                const res = await fetch(`../api/get_settings_data.php?type=role_permissions&_nocache=${Date.now()}`);
                const data = await res.json();
                document.querySelectorAll('.perm-check').forEach(cb => cb.checked = false);
                data.forEach(p => {
                    const cb = document.querySelector(`.perm-check[data-role="${p.role}"][data-page="${p.page_slug}"]`);
                    if (cb) cb.checked = true;
                });
            } catch(e) {}
        }

        async function savePermissions() {
            const perms = [];
            document.querySelectorAll('.perm-check:checked').forEach(cb => { perms.push({ role: cb.getAttribute('data-role'), page: cb.getAttribute('data-page') }); });
            const f = new FormData(); f.append('permissions', JSON.stringify(perms));
            const res = await fetch(`../api/manage_settings.php?action=save_role_permissions`, { method: 'POST', body: f });
            const d = await res.json();
            if(d.status === 'success') toastr.success('Hak Akses Diperbarui!');
        }

        window.toggleApiKeyVisibility = (id, key) => {
            const txt = document.getElementById(`apikey-text-${id}`);
            const ico = document.getElementById(`apikey-icon-${id}`);
            if (txt.innerText === '********') { txt.innerText = key; ico.className = 'fa fa-eye-slash'; }
            else { txt.innerText = '********'; ico.className = 'fa fa-eye'; }
        }

        window.openApproveModal = (id, name) => { document.getElementById('approve_id').value = id; document.getElementById('approve_name').value = name; new bootstrap.Modal(document.getElementById('modalApprove')).show(); }
        window.openEditPinModal = (id, pin) => { document.getElementById('editpin_id').value = id; document.getElementById('editpin_val').value = pin; new bootstrap.Modal(document.getElementById('modalEditPin')).show(); }

        document.getElementById('formApprove').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch(`../api/manage_settings.php?action=approve_api_key`, { method: 'POST', body: new FormData(e.target) });
            if((await res.json()).status === 'success') { bootstrap.Modal.getInstance(document.getElementById('modalApprove')).hide(); toastr.success('Disetujui!'); loadAccessData(); }
        }

        document.getElementById('formEditPin').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch(`../api/manage_settings.php?action=update_reset_pin`, { method: 'POST', body: new FormData(e.target) });
            if((await res.json()).status === 'success') { bootstrap.Modal.getInstance(document.getElementById('modalEditPin')).hide(); toastr.success('PIN Diperbarui!'); loadAccessData(); }
        }

        window.deleteAccess = (id) => {
            Swal.fire({ title: 'Hapus?', text: "Akses perangkat akan dicabut!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#D50000', confirmButtonText: 'Ya, Hapus' }).then(async (res) => {
                if(res.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const r = await fetch(`../api/manage_settings.php?action=delete&type=api_key`, { method: 'POST', body: f });
                    if((await r.json()).status === 'success') { toastr.success('Dihapus.'); loadAccessData(); }
                }
            });
        }

        loadAccessData(); loadPermissions();
        setInterval(loadAccessData, 10000);
        document.querySelector('a[href="access_control.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>