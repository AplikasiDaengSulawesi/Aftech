<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php';
require_once '../includes/auth_check.php';
protect_page('settings');
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
                                            <thead><tr><th class="ps-4">NAMA PRODUK</th><th>SATUAN</th><th>DAFTAR UKURAN (SIZE)</th><th class="text-center">AKSI</th></tr></thead>
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
                                            <thead><tr><th class="ps-4">NAMA MESIN</th><th>STATUS</th><th>DAFTAR ISI (DUS)</th><th class="text-center">AKSI</th></tr></thead>
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
        <div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><form id="formItem"><div class="modal-header bg-primary text-white border-0"><h5>Data Produk</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><input type="hidden" name="id" id="item_id"><div class="mb-3"><label class="font-w600 small">Nama Produk</label><input type="text" name="name" id="item_name" class="form-control" required></div><div class="mb-2"><label class="font-w600 small">Unit Dasar</label><select name="unit_id" id="item_unit" class="form-control default-select"><?php foreach($units as $u) echo "<option value='{$u['id']}'>{$u['name']}</option>"; ?></select></div></div><div class="modal-footer border-0"><button type="submit" class="btn btn-primary btn-sm shadow w-100">Simpan</button></div></form></div></div></div>
        
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
            const types = ['item', 'machine', 'user', 'customer', 'unit', 'size', 'quantity', 'shift', 'template'];
            let allData = {};
            for(const type of types) {
                try {
                    const res = await fetch(`../api/get_settings_data.php?type=${type}&_nocache=${Date.now()}`);
                    allData[type] = await res.json();
                } catch(e) { allData[type] = []; }
            }
            window.allMasterData = allData;

            // Render Groups
            const tbodyItem = document.getElementById('tbody-item-group');
            if(tbodyItem) {
                tbodyItem.innerHTML = '';
                allData.item.forEach(item => {
                    const itemSizes = allData.size.filter(s => s.item_id == item.id);
                    const sizeHtml = itemSizes.map(s => `<span class="badge-size" onclick='openSizeModal(${JSON.stringify(s)})'>${s.size_value}</span>`).join('');
                    tbodyItem.insertAdjacentHTML('beforeend', `<tr><td class="ps-4 font-w600">${item.name}</td><td><span class="badge badge-light">${item.unit_name || '-'}</span></td><td><div class="badge-group">${sizeHtml || '<small class="text-muted">No Size</small>'}</div></td><td class="text-center"><button onclick='openItemModal(${JSON.stringify(item)})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('item', ${item.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`);
                });
            }

            const tbodyMachine = document.getElementById('tbody-machine-group');
            if(tbodyMachine) {
                tbodyMachine.innerHTML = '';
                allData.machine.forEach(m => {
                    const mQtys = allData.quantity.filter(q => q.machine_id == m.id);
                    const qtyHtml = mQtys.map(q => `<span class="badge-qty" onclick='openQtyModal(${JSON.stringify(q)})'>${q.qty_value}</span>`).join('');
                    const st = m.status === 'active' ? 'bg-success' : 'bg-danger';
                    tbodyMachine.insertAdjacentHTML('beforeend', `<tr><td class="ps-4 font-w600">${m.name}</td><td><span class="badge badge-status ${st} text-white">${m.status.toUpperCase()}</span></td><td><div class="badge-group">${qtyHtml || '<small class="text-muted">No Qty</small>'}</div></td><td class="text-center"><button onclick='openMachineModal(${JSON.stringify(m)})' class="btn btn-primary btn-xs sharp me-1"><i class="fa fa-pencil"></i></button><button onclick="deleteMaster('machine', ${m.id})" class="btn btn-danger btn-xs sharp"><i class="fa fa-trash"></i></button></td></tr>`);
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
        window.openItemModal = (data=null) => { document.getElementById('formItem').reset(); document.getElementById('item_id').value = data ? data.id : ''; if(data) { document.getElementById('item_name').value = data.name; document.getElementById('item_unit').value = data.unit_id; } $('.default-select').selectpicker('refresh'); cleanModal('modalItem').show(); };
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

        loadMasterData();
        document.querySelector('a[href="settings.php"]')?.closest('li')?.classList.add('mm-active');
    </script>
</body>
</html>