<!DOCTYPE html>
<html lang="en">
<?php
include '../includes/header.php';
require_once '../includes/db.php';

// Data dropdown (Pre-loaded for Modals)
$units = $pdo->query("SELECT * FROM master_units ORDER BY name ASC")->fetchAll();
$items = $pdo->query("SELECT * FROM master_items ORDER BY name ASC")->fetchAll();
$machines = $pdo->query("SELECT * FROM master_machines ORDER BY name ASC")->fetchAll();
$shifts = $pdo->query("SELECT * FROM master_shifts ORDER BY name ASC")->fetchAll();
?>
<style>
    /* Premium UI Styling */
    .settings-nav .nav-link { 
        padding: 15px 20px; border-radius: 10px; color: #666; font-weight: 500; 
        margin-bottom: 8px; transition: 0.3s; border: 1px solid transparent;
    }
    .settings-nav .nav-link i { font-size: 18px; margin-right: 10px; width: 25px; text-align: center; }
    .settings-nav .nav-link.active { 
        background: #1A237E !important; color: #fff !important; 
        box-shadow: 0 4px 15px rgba(26,35,126,0.3);
    }
    .content-card { border-radius: 15px; border: none; min-height: 550px; }
    .table thead th { background: #f8f9fa; border: none; font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
    
    /* Grouping Style */
    .badge-group { display: flex; flex-wrap: wrap; gap: 5px; }
    .badge-size { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; }
    .badge-qty { background: #fff8e1; color: #ff8f00; border: 1px solid #ffecb3; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; }
    .badge-role { padding: 4px 8px; border-radius: 5px; font-size: 10px; font-weight: 700; }
    .badge-status { padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; }
</style>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row">
                    <!-- SIDEBAR NAV -->
                    <div class="col-xl-3 col-lg-4">
                        <div class="card shadow-sm border-0" style="border-radius: 15px;">
                            <div class="card-body p-3">
                                <h5 class="text-black mb-4 ps-2 mt-2 font-w600">Command Center</h5>
                                <div class="nav flex-column nav-pills settings-nav" id="v-pills-tab" role="tablist">
                                    <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tab-user"><i class="la la-user-lock"></i> Akses Pengguna</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-general"><i class="la la-check-circle"></i> Checker</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-customer"><i class="la la-users"></i> Master Customer</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-item-group"><i class="la la-box"></i> Produk & Ukuran</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-machine-group"><i class="la la-industry"></i> Mesin & Dus</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-unit"><i class="la la-balance-scale"></i> Master Unit</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-shift"><i class="la la-clock"></i> Master Shift</button>
                                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-template"><i class="la la-clipboard-list"></i> Template Produksi</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KONTEN -->
                    <div class="col-xl-9 col-lg-8">
                        <div class="alert alert-primary alert-dismissible fade show small py-2 px-3 shadow-sm d-flex align-items-center mb-4" role="alert" style="border-radius: 12px; border-left: 4px solid var(--af-primary);">
                            <i class="fa fa-info-circle me-3 fs-3 text-primary"></i>
                            <div><strong>Catatan Sistem:</strong> Setelah Anda menyimpan perubahan pada halaman pengaturan (menambah/menghapus master data atau mengubah status Checker), sangat disarankan untuk melakukan <strong>Refresh Browser</strong> Anda.</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.75rem 1rem;"></button>
                        </div>
                        <div class="tab-content">
                            <!-- TAB: USER -->
                            <div class="tab-pane fade show active" id="tab-user">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Akses Pengguna</h4>
                                        <button onclick="openUserModal()" class="btn btn-primary btn-sm">+ User Baru</button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover">
                                            <thead><tr><th class="ps-4">USERNAME</th><th>NAMA</th><th>ROLE</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-user"></tbody>
                                        </table></div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: UMUM -> CHECKER -->
                            <div class="tab-pane fade" id="tab-general">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Pengaturan Checker</h4>
                                    </div>
                                    <div class="card-body">
                                        <form id="formGeneralSettings">
                                            <div class="mb-4 d-flex align-items-center justify-content-between p-3 border rounded shadow-sm" style="background:#f8f9fa;">
                                                <div>
                                                    <h6 class="mb-1 text-primary"><i class="flaticon-381-search-3 me-2"></i>QC Checker (Verifikasi Kualitas)</h6>
                                                    <small class="text-muted">Jika dimatikan, semua dus yang belum di-check akan <strong>otomatis masuk ke Gudang</strong>, dan produksi baru langsung masuk Gudang tanpa proses QC.</small>
                                                </div>
                                                <div class="form-check form-switch form-switch-lg">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="qc_checker_enabled" name="qc_checker_enabled" value="1">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary mt-2 shadow-sm">Simpan Pengaturan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: CUSTOMER -->
                            <div class="tab-pane fade" id="tab-customer">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Master Customer</h4>
                                        <button onclick="openCustomerModal()" class="btn btn-primary btn-sm">+ Customer Baru</button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover">
                                            <thead><tr><th class="ps-4">NAMA CUSTOMER</th><th>DETAIL KONTAK & ALAMAT</th><th class="text-center">TOTAL ORDER</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-customer"></tbody>
                                        </table></div>
                                    </div>
                                    <div class="card-footer border-0 d-flex justify-content-between align-items-center py-3">
                                        <div id="pagination-info-customer" class="small text-muted font-w600"></div>
                                        <nav><ul class="pagination pagination-xs mb-0" id="pagination-controls-customer"></ul></nav>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: PRODUK & UKURAN -->
                            <div class="tab-pane fade" id="tab-item-group">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Produk & Serialisasi Ukuran</h4>
                                        <div class="btn-group">
                                            <button onclick="openItemModal()" class="btn btn-primary btn-sm me-2">+ Produk</button>
                                            <button onclick="openSizeModal()" class="btn btn-outline-primary btn-sm bg-white">+ Ukuran</button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover">
                                            <thead><tr><th class="ps-4">NAMA PRODUK</th><th>SATUAN</th><th>MESIN DEFAULT</th><th>DAFTAR UKURAN (SIZE)</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-item-group"></tbody>
                                        </table></div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: MESIN & KAPASITAS -->
                            <div class="tab-pane fade" id="tab-machine-group">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Mesin & Kapasitas (Dus)</h4>
                                        <div class="btn-group">
                                            <button onclick="openMachineModal()" class="btn btn-primary btn-sm me-2">+ Mesin</button>
                                            <button onclick="openQtyModal()" class="btn btn-outline-primary btn-sm bg-white">+ Dus</button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover">
                                            <thead><tr><th class="ps-4">NAMA MESIN</th><th>STATUS</th><th>ITEM TERKAIT</th><th>DAFTAR ISI (DUS)</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-machine-group"></tbody>
                                        </table></div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: UNIT -->
                            <div class="tab-pane fade" id="tab-unit">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Satuan Unit</h4>
                                        <button onclick="openModal('unit')" class="btn btn-primary btn-sm">+ Unit</button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover mb-0">
                                            <thead><tr><th class="ps-4">NAMA UNIT</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-unit"></tbody>
                                        </table></div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: SHIFT -->
                            <div class="tab-pane fade" id="tab-shift">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Daftar Shift Kerja</h4>
                                        <button onclick="openModal('shift')" class="btn btn-primary btn-sm">+ Shift</button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover mb-0">
                                            <thead><tr><th class="ps-4">NAMA SHIFT</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-shift"></tbody>
                                        </table></div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: TEMPLATE -->
                            <div class="tab-pane fade" id="tab-template">
                                <div class="card content-card shadow-sm">
                                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                                        <h4 class="card-title text-black">Template Produksi</h4>
                                        <button onclick="openTemplateModal()" class="btn btn-primary btn-sm">+ Template Baru</button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive"><table class="table shadow-hover mb-0">
                                            <thead><tr><th class="ps-4">NAMA TEMPLATE</th><th>SETTING PRODUK</th><th>SETTING MESIN</th><th class="text-center">AKSI</th></tr></thead>
                                            <tbody id="tbody-template"></tbody>
                                        </table></div>
                                    </div>
                                    <div class="card-footer border-0 d-flex justify-content-between align-items-center py-3">
                                        <div id="pagination-info-template" class="small text-muted font-w600"></div>
                                        <nav><ul class="pagination pagination-xs mb-0" id="pagination-controls-template"></ul></nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODALS -->
        <!-- User Modal -->
        <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formUser"><div class="modal-header bg-primary text-white border-0"><h5>Data Pengguna</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="user_id"><div class="mb-3"><label class="font-w600 small">Username</label><input type="text" name="username" id="user_username" class="form-control" required></div><div class="mb-3"><label class="font-w600 small">Nama Lengkap</label><input type="text" name="full_name" id="user_full_name" class="form-control" required></div><div class="mb-3"><label class="font-w600 small">Role</label><select name="role" id="user_role" class="form-control default-select"><option value="admin">ADMIN</option><option value="qc">QC</option><option value="gudang">GUDANG</option></select></div><div class="mb-2"><label class="font-w600 small">Password</label><input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak ubah"></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan</button></div></form></div></div></div>
        
        <!-- Customer Modal -->
        <div class="modal fade" id="modalCustomer" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formCustomer"><div class="modal-header bg-primary text-white border-0"><h5>Data Customer</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="customer_id"><div class="mb-3"><label class="font-w600 small">Nama Customer</label><input type="text" name="name" id="customer_name" class="form-control" required></div><div class="mb-3"><label class="font-w600 small">Kontak / No. HP</label><input type="text" name="contact" id="customer_contact" class="form-control"></div><div class="mb-2"><label class="font-w600 small">Alamat Lengkap</label><textarea name="address" id="customer_address" class="form-control" rows="3"></textarea></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan Customer</button></div></form></div></div></div>

        <!-- Item Modal -->
        <div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formItem"><div class="modal-header bg-primary text-white border-0"><h5>Data Produk</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="item_id"><div class="mb-3"><label class="font-w600 small">Nama Produk</label><input type="text" name="name" id="item_name" class="form-control" required></div><div class="mb-3"><label class="font-w600 small">Unit Dasar</label><select name="unit_id" id="item_unit" class="form-control default-select"><?php foreach($units as $u) echo "<option value='{$u['id']}'>{$u['name']}</option>"; ?></select></div><div class="mb-2"><label class="font-w600 small">Mesin Default <small class="text-muted">(opsional)</small></label><select name="default_machine_id" id="item_default_machine" class="form-control default-select"><option value="">-- Tidak Ada --</option><?php foreach($machines as $m) echo "<option value='{$m['id']}'>{$m['name']}</option>"; ?></select></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan</button></div></form></div></div></div>
        
        <!-- Size Modal -->
        <div class="modal fade" id="modalSize" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formSize"><div class="modal-header bg-primary text-white border-0"><h5>Data Ukuran</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="size_id"><div class="mb-3"><label class="font-w600 small">Produk</label><select name="item_id" id="size_item" class="form-control default-select"><?php foreach($items as $i) echo "<option value='{$i['id']}'>{$i['name']}</option>"; ?></select></div><div class="mb-2"><label class="font-w600 small">Nilai Size</label><input type="text" name="size_value" id="size_val" class="form-control" required></div></div><div class="modal-footer border-0 d-flex justify-content-between"><button type="button" id="btnDeleteSize" class="btn btn-danger btn-sm shadow" style="display:none;">Hapus</button><button type="submit" class="btn btn-primary btn-sm shadow flex-grow-1 ms-2">Simpan</button></div></form></div></div></div>

        <!-- Machine Modal -->
        <div class="modal fade" id="modalMachine" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formMachine"><div class="modal-header bg-primary text-white border-0"><h5>Data Mesin</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="machine_id"><div class="mb-3"><label class="font-w600 small">Nama Mesin</label><input type="text" name="name" id="machine_name" class="form-control" required></div><div class="mb-2"><label class="font-w600 small">Status</label><select name="status" id="machine_status" class="form-control default-select"><option value="active">ACTIVE</option><option value="maintenance">MAINTENANCE</option></select></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan</button></div></form></div></div></div>

        <!-- Qty Modal -->
        <div class="modal fade" id="modalQty" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formQty"><div class="modal-header bg-primary text-white border-0"><h5>Data Isi (Dus)</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="qty_id"><div class="mb-3"><label class="font-w600 small">Mesin</label><select name="machine_id" id="qty_machine" class="form-control default-select"><?php foreach($machines as $m) echo "<option value='{$m['id']}'>{$m['name']}</option>"; ?></select></div><div class="mb-2"><label class="font-w600 small">Kuantitas</label><input type="text" name="qty_value" id="qty_val" class="form-control" required></div></div><div class="modal-footer border-0 d-flex justify-content-between"><button type="button" id="btnDeleteQty" class="btn btn-danger btn-sm shadow" style="display:none;">Hapus</button><button type="submit" class="btn btn-primary btn-sm shadow flex-grow-1 ms-2">Simpan</button></div></form></div></div></div>

        <!-- CRUD Modal (Unit & Shift) -->
        <div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formCrud"><div class="modal-header bg-primary text-white border-0"><h5 id="crud_title">Manage</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="crud_id"><input type="hidden" id="crud_type"><div class="mb-2"><label class="font-w600 small">Nama</label><input type="text" name="name" id="crud_name" class="form-control" required></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan</button></div></form></div></div></div>

        <!-- Template Modal -->
        <div class="modal fade" id="modalTemplate" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formTemplate"><div class="modal-header bg-primary text-white border-0"><h5 class="text-white">Data Template Produksi</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="template_id"><div class="mb-3"><label class="font-w600 small">Nama Template</label><input type="text" name="template_name" id="template_name" class="form-control" required></div><div class="row g-2 mb-3"><div class="col-6"><label class="font-w600 small">Item</label><select name="item" id="template_item" class="form-control default-select" onchange="updateTemplateSizes(this.value)"><option value="">Pilih Item</option><?php foreach($items as $i) echo "<option value='{$i['name']}'>{$i['name']}</option>"; ?></select></div><div class="col-3"><label class="font-w600 small">Size</label><input type="text" name="size" id="template_size" list="list_sizes" class="form-control" required><datalist id="list_sizes"></datalist></div><div class="col-3"><label class="font-w600 small">Unit</label><select name="unit" id="template_unit" class="form-control default-select"><?php foreach($units as $u) echo "<option value='{$u['name']}'>{$u['name']}</option>"; ?></select></div></div><div class="row g-2 mb-2"><div class="col-6"><label class="font-w600 small">Mesin</label><select name="machine" id="template_machine" class="form-control default-select" onchange="updateTemplateQtys(this.value)"><option value="">Pilih Mesin</option><?php foreach($machines as $m) echo "<option value='{$m['name']}'>{$m['name']}</option>"; ?></select></div><div class="col-6"><label class="font-w600 small">Shift</label><select name="shift" id="template_shift" class="form-control default-select"><?php foreach($shifts as $s) echo "<option value='{$s['name']}'>{$s['name']}</option>"; ?></select></div><div class="col-12 mt-2"><label class="font-w600 small">Jumlah Dus</label><input type="text" name="quantity" id="template_quantity" list="list_qtys" class="form-control" required><datalist id="list_qtys"></datalist></div></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan Template</button></div></form></div></div></div>
    </div>

    <?php include '../includes/footer.php' ?>
    <script>
        window.currentPages = { customer: 1, template: 1 };
        const itemsPerPage = 10;

        window.loadMasterData = async function() {
            const types = ['item', 'machine', 'user', 'customer', 'unit', 'size', 'quantity', 'shift', 'template', 'app_settings'];
            let allData = {};
            for(const type of types) {
                try {
                    const res = await fetch(`../api/get_settings_data.php?type=${type}&_nocache=${Date.now()}`);
                    allData[type] = await res.json();
                } catch(e) { allData[type] = []; }
            }
            window.allMasterData = allData;

            // Render App Settings
            if (allData.app_settings) {
                const qcSetting = allData.app_settings.find(s => s.setting_key === 'qc_checker_enabled');
                if (qcSetting) {
                    document.getElementById('qc_checker_enabled').checked = (qcSetting.setting_value === '1');
                }
            }

            // Render Groups
            const machineById = {};
            (allData.machine || []).forEach(m => { machineById[m.id] = m; });

            const tbodyItem = document.getElementById('tbody-item-group');
            if(tbodyItem) {
                tbodyItem.innerHTML = '';
                allData.item.forEach(item => {
                    const itemSizes = allData.size.filter(s => s.item_id == item.id);
                    const sizeHtml = itemSizes.map(s => `<span class="badge-size" onclick='openSizeModal(${JSON.stringify(s)})'>${s.size_value}</span>`).join('');
                    const defMachine = item.default_machine_id ? machineById[item.default_machine_id] : null;
                    const machineHtml = defMachine
                        ? `<span class="badge bg-primary text-white font-w600"><i class="la la-industry me-1"></i>${defMachine.name}</span>`
                        : `<small class="text-muted fst-italic">Belum di-set</small>`;
                    tbodyItem.insertAdjacentHTML('beforeend', `<tr><td class="ps-4 font-w600">${item.name}</td><td><span class="badge badge-light">${item.unit_name || '-'}</span></td><td>${machineHtml}</td><td><div class="badge-group">${sizeHtml || '<small class="text-muted">No Size</small>'}</div></td><td class="text-center"><button onclick='openItemModal(${JSON.stringify(item)})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('item', ${item.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`);
                });
            }

            const tbodyMachine = document.getElementById('tbody-machine-group');
            if(tbodyMachine) {
                tbodyMachine.innerHTML = '';
                allData.machine.forEach(m => {
                    const mQtys = allData.quantity.filter(q => q.machine_id == m.id);
                    const qtyHtml = mQtys.map(q => `<span class="badge-qty" onclick='openQtyModal(${JSON.stringify(q)})'>${q.qty_value}</span>`).join('');
                    const relatedItems = (allData.item || []).filter(i => i.default_machine_id == m.id);
                    const itemHtml = relatedItems.length
                        ? relatedItems.map(i => `<span class="badge bg-light text-primary border border-primary font-w600 me-1">${i.name}</span>`).join('')
                        : `<small class="text-muted fst-italic">Tidak ada</small>`;
                    const st = m.status === 'active' ? 'bg-success' : 'bg-danger';
                    tbodyMachine.insertAdjacentHTML('beforeend', `<tr><td class="ps-4 font-w600">${m.name}</td><td><span class="badge badge-status ${st} text-white">${m.status.toUpperCase()}</span></td><td>${itemHtml}</td><td><div class="badge-group">${qtyHtml || '<small class="text-muted">No Qty</small>'}</div></td><td class="text-center"><button onclick='openMachineModal(${JSON.stringify(m)})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('machine', ${m.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`);
                });
            }

            renderTable('user', allData.user);
            renderTableWithPagination('customer', allData.customer);
            renderTable('unit', allData.unit);
            renderTable('shift', allData.shift);
            renderTableWithPagination('template', allData.template);
        }

        function renderTable(type, data) {
            const tbody = document.getElementById('tbody-' + type);
            if(!tbody) return;
            tbody.innerHTML = '';
            data.forEach(row => {
                let html = '';
                if(type === 'user') html = `<tr><td class="ps-4 font-w600">${row.username}</td><td>${row.full_name}</td><td><span class="badge badge-role bg-light text-primary border border-primary font-w700">${row.role.toUpperCase()}</span></td><td class="text-center"><button onclick='openUserModal(${JSON.stringify(row)})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('user', ${row.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`;
                else if(type === 'unit') html = `<tr><td class="ps-4 font-w600">${row.name}</td><td class="text-center"><button onclick="openModal('unit', ${JSON.stringify(row).replace(/"/g, '&quot;')})" class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('unit', ${row.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`;
                else if(type === 'shift') html = `<tr><td class="ps-4 font-w600">${row.name}</td><td class="text-center"><button onclick="openModal('shift', ${JSON.stringify(row).replace(/"/g, '&quot;')})" class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('shift', ${row.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`;
                tbody.insertAdjacentHTML('beforeend', html);
            });
        }

        function renderTableWithPagination(type, data) {
            const tbody = document.getElementById('tbody-' + type);
            const info = document.getElementById(`pagination-info-${type}`);
            const controls = document.getElementById(`pagination-controls-${type}`);
            if(!tbody) return;
            
            const totalItems = data.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
            const currentPage = window.currentPages[type] > totalPages ? 1 : window.currentPages[type];
            window.currentPages[type] = currentPage;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const paginatedData = data.slice(startIndex, startIndex + itemsPerPage);

            tbody.innerHTML = '';
            if(totalItems === 0) tbody.innerHTML = '<tr><td colspan="10" class="text-center p-4 text-muted italic small">Data Kosong</td></tr>';
            
            paginatedData.forEach(row => {
                let html = '';
                if(type === 'customer') html = `<tr><td class="ps-4 font-w700 text-black">${row.name}</td><td><div class="text-primary font-w600 small"><i class="fa fa-phone-alt me-1"></i> ${row.contact || '-'}</div><small class="text-muted d-block">${row.address || '-'}</small></td><td class="text-center"><span class="badge badge-light border text-dark font-w800">${row.total_orders || 0}</span></td><td class="text-center"><button onclick='openCustomerModal(${JSON.stringify(row)})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('customer', ${row.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`;
                else if(type === 'template') html = `<tr><td class="ps-4 font-w700 text-primary">${row.template_name}</td><td><div class="font-w600">${row.item}</div><small class="text-muted">${row.size} ${row.unit}</small></td><td><div class="font-w600">${row.machine}</div><small class="text-muted">${row.shift} &bull; Dus: ${row.quantity}</small></td><td class="text-center"><button onclick='openTemplateModal(${JSON.stringify(row).replace(/"/g, '&quot;')})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('template', ${row.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`;
                tbody.insertAdjacentHTML('beforeend', html);
            });

            if(info) info.innerText = `Showing ${totalItems > 0 ? startIndex + 1 : 0} to ${Math.min(startIndex + itemsPerPage, totalItems)} of ${totalItems}`;
            if(controls) {
                controls.innerHTML = '';
                controls.insertAdjacentHTML('beforeend', `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" onclick="changePage('${type}', ${currentPage - 1})"><i class="fas fa-chevron-left"></i></a></li>`);
                for (let i = 1; i <= totalPages; i++) controls.insertAdjacentHTML('beforeend', `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" onclick="changePage('${type}', ${i})">${i}</a></li>`);
                controls.insertAdjacentHTML('beforeend', `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" onclick="changePage('${type}', ${currentPage + 1})"><i class="fas fa-chevron-right"></i></a></li>`);
            }
        }

        window.changePage = (type, page) => { window.currentPages[type] = page; loadMasterData(); }

        async function handleAJAXSave(e, type, modalId) {
            e.preventDefault();
            const res = await fetch(`../api/manage_settings.php?action=save&type=${type}`, { method: 'POST', body: new FormData(e.target) });
            const data = await res.json();
            if(data.status === 'success') { 
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide(); 
                toastr.success(`Berhasil disimpan.`); loadMasterData(); 
            } else toastr.error(data.message || 'Gagal menyimpan.');
        }

        window.deleteMaster = function(type, id) {
            Swal.fire({ title: 'Hapus Data?', text: "Data akan dihapus permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#D50000', confirmButtonText: 'Ya, Hapus' }).then(async (res) => {
                if(res.isConfirmed) {
                    const f = new FormData(); f.append('id', id);
                    const r = await fetch(`../api/manage_settings.php?action=delete&type=${type}`, { method: 'POST', body: f });
                    const d = await r.json();
                    if(d.status === 'success') { toastr.success('Berhasil dihapus.'); loadMasterData(); }
                    else toastr.error(d.message);
                }
            });
        }

        function cleanModal(id) { const el = document.getElementById(id); const old = bootstrap.Modal.getInstance(el); if(old) old.dispose(); return new bootstrap.Modal(el); }
        
        window.openUserModal = (data=null) => { document.getElementById('formUser').reset(); document.getElementById('user_id').value = data ? data.id : ''; if(data) { document.getElementById('user_username').value = data.username; document.getElementById('user_full_name').value = data.full_name; document.getElementById('user_role').value = data.role; } $('.default-select').selectpicker('refresh'); cleanModal('modalUser').show(); };
        window.openCustomerModal = (data=null) => { document.getElementById('formCustomer').reset(); document.getElementById('customer_id').value = data ? data.id : ''; if(data) { document.getElementById('customer_name').value = data.name; document.getElementById('customer_contact').value = data.contact; document.getElementById('customer_address').value = data.address; } cleanModal('modalCustomer').show(); };
        window.openItemModal = (data=null) => { document.getElementById('formItem').reset(); document.getElementById('item_id').value = data ? data.id : ''; if(data) { document.getElementById('item_name').value = data.name; document.getElementById('item_unit').value = data.unit_id; document.getElementById('item_default_machine').value = data.default_machine_id || ''; } $('.default-select').selectpicker('refresh'); cleanModal('modalItem').show(); };
        window.openMachineModal = (data=null) => { document.getElementById('formMachine').reset(); document.getElementById('machine_id').value = data ? data.id : ''; if(data) { document.getElementById('machine_name').value = data.name; document.getElementById('machine_status').value = data.status; } $('.default-select').selectpicker('refresh'); cleanModal('modalMachine').show(); };
        window.openSizeModal = (data=null) => { document.getElementById('formSize').reset(); document.getElementById('size_id').value = data ? data.id : ''; document.getElementById('btnDeleteSize').style.display = data ? 'block' : 'none'; document.getElementById('btnDeleteSize').onclick = () => deleteMaster('size', data.id); if(data) { document.getElementById('size_item').value = data.item_id; document.getElementById('size_val').value = data.size_value; } $('.default-select').selectpicker('refresh'); cleanModal('modalSize').show(); };
        window.openQtyModal = (data=null) => { document.getElementById('formQty').reset(); document.getElementById('qty_id').value = data ? data.id : ''; document.getElementById('btnDeleteQty').style.display = data ? 'block' : 'none'; document.getElementById('btnDeleteQty').onclick = () => deleteMaster('quantity', data.id); if(data) { document.getElementById('qty_machine').value = data.machine_id; document.getElementById('qty_val').value = data.qty_value; } $('.default-select').selectpicker('refresh'); cleanModal('modalQty').show(); };
        window.openModal = (type, data=null) => { document.getElementById('formCrud').reset(); document.getElementById('crud_id').value = data ? data.id : ''; document.getElementById('crud_type').value = type; document.getElementById('crud_name').value = data ? data.name : ''; document.getElementById('crud_title').innerText = 'Manage ' + type.charAt(0).toUpperCase() + type.slice(1); cleanModal('modalCrud').show(); };
        window.openTemplateModal = (data=null) => { 
            document.getElementById('formTemplate').reset(); 
            document.getElementById('template_id').value = data ? data.id : ''; 
            if(data) { 
                document.getElementById('template_name').value = data.template_name; 
                $('#template_item').val(data.item).trigger('change').selectpicker('refresh'); 
                document.getElementById('template_size').value = data.size;
                $('#template_unit').val(data.unit).selectpicker('refresh');
                $('#template_machine').val(data.machine).trigger('change').selectpicker('refresh');
                $('#template_shift').val(data.shift).selectpicker('refresh');
                document.getElementById('template_quantity').value = data.quantity;
            } else { document.getElementById('list_sizes').innerHTML = ''; document.getElementById('list_qtys').innerHTML = ''; }
            $('.default-select').selectpicker('refresh'); cleanModal('modalTemplate').show(); 
        };

        window.updateTemplateSizes = function(itemName) {
            if(!window.allMasterData) return;
            const item = window.allMasterData.item.find(i => i.name === itemName);
            const sizes = item ? window.allMasterData.size.filter(s => s.item_id == item.id) : [];
            let html = '';
            sizes.forEach(s => { html += `<option value="${s.size_value}">`; });
            document.getElementById('list_sizes').innerHTML = html;
        }

        window.updateTemplateQtys = function(machineName) {
            if(!window.allMasterData) return;
            const machine = window.allMasterData.machine.find(m => m.name === machineName);
            const qtys = machine ? window.allMasterData.quantity.filter(q => q.machine_id == machine.id) : [];
            let html = '';
            qtys.forEach(q => { html += `<option value="${q.qty_value}">`; });
            document.getElementById('list_qtys').innerHTML = html;
        }

        document.getElementById('formUser').onsubmit = (e) => handleAJAXSave(e, 'user', 'modalUser');
        document.getElementById('formCustomer').onsubmit = (e) => handleAJAXSave(e, 'customer', 'modalCustomer');
        document.getElementById('formItem').onsubmit = (e) => handleAJAXSave(e, 'item', 'modalItem');
        document.getElementById('formMachine').onsubmit = (e) => handleAJAXSave(e, 'machine', 'modalMachine');
        document.getElementById('formSize').onsubmit = (e) => handleAJAXSave(e, 'size', 'modalSize');
        document.getElementById('formQty').onsubmit = (e) => handleAJAXSave(e, 'quantity', 'modalQty');
        document.getElementById('formTemplate').onsubmit = (e) => handleAJAXSave(e, 'template', 'modalTemplate');
        document.getElementById('formCrud').onsubmit = (e) => handleAJAXSave(e, document.getElementById('crud_type').value, 'modalCrud');
        
        const formGeneral = document.getElementById('formGeneralSettings');
        if (formGeneral) {
            formGeneral.onsubmit = async (e) => {
                e.preventDefault();
                const isChecked = document.getElementById('qc_checker_enabled').checked;
                
                // Jika QC dimatikan, tampilkan peringatan
                if (!isChecked) {
                    const confirmResult = await Swal.fire({
                        title: '<i class="fa fa-exclamation-triangle text-warning me-2"></i> Matikan QC Checker?',
                        html: `
                            <div class="text-start" style="font-size: 14px; line-height: 1.8;">
                                <div class="alert alert-warning py-2 px-3 mb-3" style="border-radius: 10px;">
                                    <i class="fa fa-info-circle me-1"></i> <strong>Perhatian!</strong> Tindakan ini akan mengubah alur kerja sistem.
                                </div>
                                <p class="mb-2">Dengan mematikan QC Checker:</p>
                                <ul class="ps-3 mb-0">
                                    <li>Semua dus yang <strong>belum di-check/scan</strong> akan <strong class="text-success">otomatis masuk ke Gudang</strong>.</li>
                                    <li>Produksi baru selanjutnya juga akan langsung masuk Gudang <strong>tanpa proses QC</strong>.</li>
                                    <li>Menu <strong>QC Checker</strong> pada sidebar akan disembunyikan.</li>
                                </ul>
                            </div>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#D50000',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fa fa-check me-1"></i> Ya, Matikan QC',
                        cancelButtonText: 'Batal',
                        width: 520,
                        customClass: { popup: 'shadow-lg' }
                    });
                    
                    if (!confirmResult.isConfirmed) return;
                }
                
                // Proses simpan
                const formData = new FormData(formGeneral);
                formData.set('qc_checker_enabled', isChecked ? 1 : 0);

                // Tampilkan loading
                Swal.fire({ title: 'Memproses...', html: isChecked ? 'Menyimpan pengaturan...' : 'Memindahkan data ke Gudang...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                try {
                    const res = await fetch(`../api/manage_settings.php?action=save_app_settings`, { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        Swal.close();
                        if (!isChecked && data.auto_transferred > 0) {
                            // Simpan data transfer ke variabel global untuk PDF
                            window._lastTransferData = data;
                            showTransferReport(data);
                        } else {
                            toastr.success('Pengaturan berhasil disimpan');
                        }
                    } else {
                        Swal.fire('Gagal', data.message || 'Gagal menyimpan', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Koneksi ke server gagal', 'error');
                }
            };
        }

        // ============================================================
        // FUNGSI TAMPILKAN LAPORAN DETAIL TRANSFER OTOMATIS
        // ============================================================
        function showTransferReport(data) {
            const details = data.transfer_details || [];
            const now = new Date();
            const timestamp = now.toLocaleString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });

            let tableRows = '';
            details.forEach((d, idx) => {
                const pDate = d.production_date ? new Date(d.production_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                tableRows += `
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td class="font-w700 text-primary">#${d.batch}</td>
                        <td>${d.item}</td>
                        <td class="text-center">${d.size} ${d.unit}</td>
                        <td class="text-center">${d.quantity}</td>
                        <td class="text-center">${d.machine}</td>
                        <td class="text-center">${pDate}</td>
                        <td class="text-center font-w700">${d.total_copies}</td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${d.already_in_warehouse}</span></td>
                        <td class="text-center"><span class="badge bg-success text-white font-w700">${d.auto_transferred}</span></td>
                    </tr>
                `;
            });

            const modalHTML = `
                <div class="modal fade" id="modalTransferReport" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #1A237E 0%, #3F51B5 100%); border-radius: 16px 16px 0 0; padding: 20px 25px;">
                                <div>
                                    <h5 class="text-white mb-1"><i class="fa fa-file-alt me-2"></i>Laporan Transfer Otomatis</h5>
                                    <small class="text-white-50">${timestamp}</small>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Summary Cards -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="p-3 rounded-3 text-center" style="background: #E8F5E9; border: 1px solid #C8E6C9;">
                                            <div class="fs-2 fw-bold text-success">${data.auto_transferred}</div>
                                            <div class="small text-muted font-w600">Total Dus Dipindahkan</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded-3 text-center" style="background: #E8EAF6; border: 1px solid #C5CAE9;">
                                            <div class="fs-2 fw-bold text-primary">${data.auto_batches}</div>
                                            <div class="small text-muted font-w600">Batch Terproses</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 rounded-3 text-center" style="background: #FFF8E1; border: 1px solid #FFECB3;">
                                            <div class="fs-2 fw-bold text-warning"><i class="fa fa-robot"></i></div>
                                            <div class="small text-muted font-w600">Oleh: Auto-System</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detail Table -->
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered shadow-hover mb-0" id="tableTransferReport">
                                        <thead style="position: sticky; top: 0; z-index: 2;">
                                            <tr class="bg-light">
                                                <th class="text-center" style="width: 40px;">#</th>
                                                <th>BATCH</th>
                                                <th>ITEM PRODUK</th>
                                                <th class="text-center">SIZE</th>
                                                <th class="text-center">ISI DUS</th>
                                                <th class="text-center">MESIN</th>
                                                <th class="text-center">TGL PRODUKSI</th>
                                                <th class="text-center">TOTAL DUS</th>
                                                <th class="text-center">SUDAH DI GUDANG</th>
                                                <th class="text-center">BARU DIPINDAHKAN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${tableRows}
                                        </tbody>
                                        <tfoot>
                                            <tr style="background: #f8f9fa; font-weight: 700;">
                                                <td colspan="7" class="text-end pe-3">TOTAL</td>
                                                <td class="text-center">${details.reduce((s,d) => s + d.total_copies, 0)}</td>
                                                <td class="text-center">${details.reduce((s,d) => s + d.already_in_warehouse, 0)}</td>
                                                <td class="text-center text-success font-w800">${data.auto_transferred}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="alert alert-info small py-2 px-3 mt-3 mb-0 d-flex align-items-center" style="border-radius: 10px;">
                                    <i class="fa fa-info-circle me-2 fs-5"></i>
                                    <span>Data di atas menunjukkan rincian dus yang <strong>belum masuk gudang</strong> dan telah otomatis dipindahkan oleh sistem. Unduh PDF sebagai bukti dokumentasi.</span>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal"><i class="fa fa-times me-1"></i> Tutup</button>
                                <button type="button" class="btn btn-primary btn-sm shadow" onclick="downloadTransferPDF()">
                                    <i class="fa fa-file-pdf me-1"></i> Unduh Laporan PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Hapus modal lama jika ada
            const oldModal = document.getElementById('modalTransferReport');
            if (oldModal) { bootstrap.Modal.getInstance(oldModal)?.dispose(); oldModal.remove(); }
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

            document.body.insertAdjacentHTML('beforeend', modalHTML);
            const modal = new bootstrap.Modal(document.getElementById('modalTransferReport'));
            modal.show();
        }

        // ============================================================
        // FUNGSI GENERATE PDF LAPORAN
        // ============================================================
        window.downloadTransferPDF = function() {
            const data = window._lastTransferData;
            if (!data || !data.transfer_details) { toastr.error('Data tidak tersedia'); return; }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const now = new Date();
            const timestamp = now.toLocaleString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

            // ---- HEADER ----
            doc.setFillColor(26, 35, 126); // Indigo Deep
            doc.rect(0, 0, pageWidth, 32, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('AFTECH SYSTEM', 14, 14);
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Laporan Transfer Otomatis ke Gudang', 14, 22);
            
            doc.setFontSize(9);
            doc.text(`Dicetak: ${timestamp}`, pageWidth - 14, 14, { align: 'right' });
            doc.text(`Oleh: Auto-System`, pageWidth - 14, 22, { align: 'right' });

            // ---- SUMMARY BOX ----
            const summaryY = 40;
            doc.setDrawColor(200, 200, 200);
            
            // Box 1: Total Dus
            doc.setFillColor(232, 245, 233);
            doc.roundedRect(14, summaryY, 80, 22, 3, 3, 'FD');
            doc.setTextColor(0, 200, 83);
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text(String(data.auto_transferred), 54, summaryY + 10, { align: 'center' });
            doc.setTextColor(100, 100, 100);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.text('Total Dus Dipindahkan', 54, summaryY + 18, { align: 'center' });

            // Box 2: Batch
            doc.setFillColor(232, 234, 246);
            doc.roundedRect(104, summaryY, 80, 22, 3, 3, 'FD');
            doc.setTextColor(26, 35, 126);
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text(String(data.auto_batches), 144, summaryY + 10, { align: 'center' });
            doc.setTextColor(100, 100, 100);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.text('Batch Terproses', 144, summaryY + 18, { align: 'center' });

            // Box 3: Status
            doc.setFillColor(255, 248, 225);
            doc.roundedRect(194, summaryY, 80, 22, 3, 3, 'FD');
            doc.setTextColor(255, 143, 0);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('QC CHECKER OFF', 234, summaryY + 10, { align: 'center' });
            doc.setTextColor(100, 100, 100);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.text('Status Saat Ini', 234, summaryY + 18, { align: 'center' });

            // ---- TABEL DETAIL ----
            const tableData = data.transfer_details.map((d, idx) => {
                const pDate = d.production_date ? new Date(d.production_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                return [
                    idx + 1,
                    '#' + d.batch,
                    d.item,
                    d.size + ' ' + d.unit,
                    d.quantity,
                    d.machine,
                    pDate,
                    d.total_copies,
                    d.already_in_warehouse,
                    d.auto_transferred
                ];
            });

            // Footer row
            const totalCopies = data.transfer_details.reduce((s,d) => s + d.total_copies, 0);
            const totalExisting = data.transfer_details.reduce((s,d) => s + d.already_in_warehouse, 0);

            doc.autoTable({
                startY: summaryY + 30,
                head: [['#', 'Batch', 'Item Produk', 'Size', 'Isi Dus', 'Mesin', 'Tgl Produksi', 'Total Dus', 'Sudah di Gudang', 'Baru Dipindahkan']],
                body: tableData,
                foot: [['', '', '', '', '', '', 'TOTAL', totalCopies, totalExisting, data.auto_transferred]],
                theme: 'grid',
                headStyles: { 
                    fillColor: [26, 35, 126], textColor: [255, 255, 255], fontSize: 8, fontStyle: 'bold',
                    halign: 'center', cellPadding: 3
                },
                bodyStyles: { fontSize: 8, cellPadding: 2.5 },
                footStyles: { 
                    fillColor: [245, 245, 245], textColor: [26, 35, 126], fontSize: 9, fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10 },
                    1: { fontStyle: 'bold', textColor: [26, 35, 126] },
                    3: { halign: 'center' },
                    4: { halign: 'center' },
                    5: { halign: 'center' },
                    6: { halign: 'center' },
                    7: { halign: 'center', fontStyle: 'bold' },
                    8: { halign: 'center' },
                    9: { halign: 'center', fontStyle: 'bold', textColor: [0, 150, 0] }
                },
                alternateRowStyles: { fillColor: [248, 249, 250] },
                margin: { left: 14, right: 14 },
                didDrawPage: function(data) {
                    // Footer setiap halaman
                    const pageCount = doc.internal.getNumberOfPages();
                    doc.setFontSize(7);
                    doc.setTextColor(150, 150, 150);
                    doc.text(`AFTECH System — Laporan Transfer Otomatis`, 14, doc.internal.pageSize.getHeight() - 8);
                    doc.text(`Halaman ${data.pageNumber} dari ${pageCount}`, pageWidth - 14, doc.internal.pageSize.getHeight() - 8, { align: 'right' });
                }
            });

            // ---- CATATAN KAKI ----
            const finalY = doc.lastAutoTable.finalY + 10;
            if (finalY < doc.internal.pageSize.getHeight() - 30) {
                doc.setFillColor(248, 249, 255);
                doc.setDrawColor(26, 35, 126);
                doc.roundedRect(14, finalY, pageWidth - 28, 16, 2, 2, 'FD');
                doc.setTextColor(26, 35, 126);
                doc.setFontSize(8);
                doc.setFont('helvetica', 'bold');
                doc.text('Catatan:', 18, finalY + 6);
                doc.setFont('helvetica', 'normal');
                doc.setTextColor(80, 80, 80);
                doc.text('Laporan ini digenerate otomatis oleh sistem saat fitur QC Checker dimatikan. Semua dus yang belum terverifikasi', 18, finalY + 11);
                doc.text('telah dipindahkan langsung ke gudang oleh Auto-System. Dokumen ini dapat digunakan sebagai bukti audit.', 18, finalY + 15);
            }

            // Save
            const fileName = `Laporan_Transfer_Otomatis_${now.getFullYear()}${String(now.getMonth()+1).padStart(2,'0')}${String(now.getDate()).padStart(2,'0')}_${String(now.getHours()).padStart(2,'0')}${String(now.getMinutes()).padStart(2,'0')}.pdf`;
            doc.save(fileName);
            toastr.success('PDF berhasil diunduh');
        }

        loadMasterData();
        document.querySelector('a[href="settings.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
    <!-- jsPDF for report generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</body>
</html>