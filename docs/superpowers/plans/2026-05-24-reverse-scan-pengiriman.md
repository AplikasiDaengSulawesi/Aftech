# Reverse Scan Pengiriman Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan mode reverse pada halaman scan pengiriman untuk merekonstruksi batch/label gudang lama dari barcode, dengan cutoff produksi sebelum `2026-05-25`.

**Architecture:** Backend reverse dipisah ke helper baru yang menangani parsing barcode, parsing batch, lookup master referensi, validasi cutoff, dan sinkronisasi `production_labels`/`warehouse_items`. `api/process_shipment.php` hanya menjadi entry point JSON, sedangkan `pages/shipment_scan.php` menambahkan toggle mode reverse dan memutuskan endpoint scan yang dipanggil.

**Tech Stack:** Vanilla PHP, mysqli/PDO campuran, MySQL/MariaDB, jQuery/fetch API, Bootstrap, html5-qrcode, smoke test PHP CLI kustom.

---

## Chunk 1: Backend Reverse Helper dan Test Harness

### Task 1: Siapkan harness smoke test reverse scan

**Files:**
- Create: `tests/bootstrap.php`
- Create: `tests/ReverseShipmentScanTest.php`
- Reference: `includes/db_credentials.php`
- Reference: `docs/superpowers/specs/2026-05-24-reverse-scan-pengiriman-design.md`

- [ ] **Step 1: Buat failing smoke test untuk parsing dan cutoff reverse**

```php
<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../includes/shipment_reverse_helper.php';

$service = new ShipmentReverseHelper($conn);
$result = $service->parseBarcode('12-080326-01C-SED-1000-RAMA-100ML');

assertSame(12, $result['label_no']);
assertSame('080326-01C-SED-1000-RAMA-100ML', $result['batch']);
assertSame('2026-03-08', $service->parseProductionDate('080326'));
assertThrows(fn() => $service->assertReverseAllowedDate('2026-05-25'));
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: FAIL karena file/helper/class reverse belum ada.

- [ ] **Step 3: Buat bootstrap test minimal**

```php
<?php
require __DIR__ . '/../api/config.php';

function assertSame($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new Exception($message ?: 'Assertion failed: values differ');
    }
}

function assertThrows(callable $fn, $message = '') {
    try {
        $fn();
    } catch (Throwable $e) {
        return;
    }
    throw new Exception($message ?: 'Assertion failed: exception expected');
}
```

- [ ] **Step 4: Tambah test lookup referensi dan format batch**

```php
$parsed = $service->parseBatch('080326-01C-SED-1000-RAMA-100ML');
assertSame('SED', $parsed['item_code']);
assertSame('01', $parsed['machine_code']);
assertSame('C', $parsed['shift_code']);
assertSame('1000', $parsed['quantity']);
assertSame('RAMA', $parsed['operator_qc_code']);
assertSame('100', $parsed['size_value']);
assertSame('ML', $parsed['unit_code']);
```

- [ ] **Step 5: Commit**

```bash
git add tests/bootstrap.php tests/ReverseShipmentScanTest.php
git commit -m "test: add reverse shipment scan smoke harness"
```

### Task 2: Bangun helper reverse scan yang bisa diuji secara terpisah

**Files:**
- Create: `includes/shipment_reverse_helper.php`
- Modify: `tests/ReverseShipmentScanTest.php`
- Reference: `db_aftech.sql`
- Reference: `pages/warehouse_inventory.php`

- [ ] **Step 1: Tulis failing test untuk helper parse dan lookup**

```php
$service = new ShipmentReverseHelper($conn);
$parsed = $service->parseBatch('080326-01C-SED-1000-RAMA-100ML');
$resolved = $service->resolveBatchReferences($parsed);

assertSame('SEDOTAN', $resolved['item']);
assertSame('100', $resolved['size']);
assertSame('ML', $resolved['unit']);
assertSame('SHIFT C', $resolved['shift']);
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: FAIL karena method lookup belum ada / hasil belum sesuai.

- [ ] **Step 3: Implement helper parse barcode, parse batch, cutoff, dan lookup referensi**

```php
class ShipmentReverseHelper
{
    public function parseBarcode(string $raw): array {}
    public function parseBatch(string $batch): array {}
    public function parseProductionDate(string $ddmmyy): string {}
    public function assertReverseAllowedDate(string $productionDate): void {}
    public function resolveBatchReferences(array $parsed): array {}
}
```

Implement lookup dengan prinsip:
- kode mesin `01` dipetakan via `master_machines.name` yang mengandung nomor mesin relevan
- shift `C` dipetakan ke `master_shifts.name = 'SHIFT C'`
- item `SED` dipetakan dari 3 huruf awal `master_items.name`
- size/unit `100ML` dipecah jadi `100` + `ML`, lalu cocokkan ke `master_sizes` + `master_units`
- kode `RAMA` dicari dari data referensi yang tersedia; bila tidak ada sumber master terpisah, bungkus di resolver yang jelas agar kegagalan lookup eksplisit dan mudah diubah

- [ ] **Step 4: Jalankan test untuk memastikan helper lulus**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: PASS untuk parsing, cutoff, dan lookup referensi dasar.

- [ ] **Step 5: Refactor helper agar tanggung jawab tetap sempit**

```php
private function resolveMachineName(string $machineCode): string {}
private function resolveShiftName(string $shiftCode): string {}
private function resolveItemAndUnit(string $itemCode, string $unitCode): array {}
private function resolveSize(string $itemName, string $sizeValue): string {}
```

- [ ] **Step 6: Commit**

```bash
git add includes/shipment_reverse_helper.php tests/ReverseShipmentScanTest.php
git commit -m "feat: add reverse shipment parser and reference lookup"
```

## Chunk 2: Reverse Persistence Flow di Backend

### Task 3: Tambahkan test untuk create batch baru dan create label baru

**Files:**
- Modify: `tests/ReverseShipmentScanTest.php`
- Reference: `api/mobile/save_label-mobile.php`
- Reference: `api/process_scan.php`
- Reference: `api/process_shipment.php`

- [ ] **Step 1: Tulis failing test untuk batch baru**

```php
$barcode = '12-080326-01C-SED-1000-RAMA-100ML';
$result = $service->reverseScan($barcode, 'Test User');

assertSame('created_batch', $result['reverse_status']);
assertSame(12, $result['scanned_label']);
assertTrue(in_array(12, $result['in_warehouse'], true));
assertSame(12, fetchCopiesByBatch($conn, '080326-01C-SED-1000-RAMA-100ML'));
```

- [ ] **Step 2: Tulis failing test untuk batch existing dengan label baru**

```php
seedProductionBatch($conn, [
    'batch' => '080326-01C-SED-1000-RAMA-100ML',
    'copies' => 10,
]);

$result = $service->reverseScan('12-080326-01C-SED-1000-RAMA-100ML', 'Test User');

assertSame('created_label', $result['reverse_status']);
assertSame(12, fetchCopiesByBatch($conn, '080326-01C-SED-1000-RAMA-100ML'));
assertWarehouseLabelExists($conn, $result['production_id'], 12);
```

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: FAIL karena persistence reverse belum diimplementasikan.

- [ ] **Step 4: Tambah helper test untuk seed dan cleanup data**

```php
function seedProductionBatch(mysqli $conn, array $overrides = []): int {}
function assertWarehouseLabelExists(mysqli $conn, int $productionId, int $labelNo): void {}
function fetchCopiesByBatch(mysqli $conn, string $batch): int {}
function cleanupReverseFixtures(mysqli $conn, string $batch): void {}
```

- [ ] **Step 5: Commit**

```bash
git add tests/ReverseShipmentScanTest.php
git commit -m "test: cover reverse batch creation and label append"
```

### Task 4: Implement reverse persistence dengan transaction

**Files:**
- Modify: `includes/shipment_reverse_helper.php`
- Reference: `api/mobile/save_label-mobile.php`
- Reference: `api/process_scan.php`

- [ ] **Step 1: Implement failing API-neutral method `reverseScan()`**

```php
public function reverseScan(string $barcode, string $user): array
{
    $this->conn->begin_transaction();
    try {
        // parse, resolve, load batch, sync copies, insert warehouse item, collect payload
        $this->conn->commit();
        return $payload;
    } catch (Throwable $e) {
        $this->conn->rollback();
        throw $e;
    }
}
```

- [ ] **Step 2: Tangani cabang `batch` belum ada**

Implement:
- create `production_labels` dengan `copies = label_no`
- isi `production_date`, `machine`, `shift`, `item`, `size`, `unit`, `quantity`, `operator`, `qc`
- insert `warehouse_items (production_id, label_no, transferred_by, input_method='scan')`

- [ ] **Step 3: Tangani cabang `batch` sudah ada, label belum ada**

Implement:
- lock row batch / cek row existing
- jika `label_no > copies`, update `production_labels.copies = label_no`
- insert `warehouse_items`
- set `reverse_status = 'created_label'`

- [ ] **Step 4: Tangani cabang `batch` dan label sudah ada**

Implement:
- jangan ubah `production_labels.copies`
- jangan insert `warehouse_items`
- kembalikan `reverse_status = 'existing_label'`

- [ ] **Step 5: Tolak label yang sudah terkirim atau tanggal yang tidak eligible**

Implement:
- cek `distributor_shipments`
- cek cutoff `production_date < 2026-05-25`
- lempar exception dengan pesan user-facing Indonesia yang spesifik

- [ ] **Step 6: Jalankan test untuk memastikan lulus**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: PASS untuk batch baru dan label baru.

- [ ] **Step 7: Commit**

```bash
git add includes/shipment_reverse_helper.php tests/ReverseShipmentScanTest.php
git commit -m "feat: implement reverse shipment persistence flow"
```

## Chunk 3: Endpoint JSON, UI Shipment Scan, dan Verifikasi

### Task 5: Tambahkan test untuk existing label, shipped label, dan cutoff rejection

**Files:**
- Modify: `tests/ReverseShipmentScanTest.php`

- [ ] **Step 1: Tulis failing test untuk existing label**

```php
$result = $service->reverseScan('5-080326-01C-SED-1000-RAMA-100ML', 'Test User');
assertSame('existing_label', $result['reverse_status']);
assertSame(10, fetchCopiesByBatch($conn, '080326-01C-SED-1000-RAMA-100ML'));
```

- [ ] **Step 2: Tulis failing test untuk shipped label dan cutoff**

```php
assertThrows(fn() => $service->reverseScan('2-260526-01C-SED-1000-RAMA-100ML', 'Test User'));
assertThrows(fn() => $service->reverseScan('7-080326-01C-SED-1000-RAMA-100ML', 'Test User'));
```

- [ ] **Step 3: Jalankan test untuk memastikan gagal**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: FAIL karena guard existing/shipped/cutoff belum lengkap.

- [ ] **Step 4: Lengkapi helper sesuai test**

Implement:
- payload `existing_label`
- guard `already shipped`
- guard cutoff untuk batch baru maupun existing

- [ ] **Step 5: Jalankan test untuk memastikan lulus**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: PASS untuk seluruh skenario reverse utama.

- [ ] **Step 6: Commit**

```bash
git add includes/shipment_reverse_helper.php tests/ReverseShipmentScanTest.php
git commit -m "test: cover reverse guard rails"
```

### Task 6: Hubungkan action `reverse_scan` ke `api/process_shipment.php`

**Files:**
- Modify: `api/process_shipment.php`
- Modify: `includes/shipment_reverse_helper.php`
- Test: `tests/ReverseShipmentScanTest.php`

- [ ] **Step 1: Tulis failing integration test endpoint-level ringan**

```php
$_REQUEST['action'] = 'reverse_scan';
$_GET['qr'] = '12-080326-01C-SED-1000-RAMA-100ML';
ob_start();
require __DIR__ . '/../api/process_shipment.php';
$json = json_decode(ob_get_clean(), true);

assertSame('success', $json['status']);
assertSame('created_batch', $json['data']['reverse_status']);
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: FAIL karena action `reverse_scan` belum ada.

- [ ] **Step 3: Implement action baru di endpoint**

```php
elseif ($action === 'reverse_scan') {
    require_once __DIR__ . '/../includes/shipment_reverse_helper.php';
    $helper = new ShipmentReverseHelper($conn);
    $payload = $helper->reverseScan($_GET['qr'] ?? '', $user);
    echo json_encode(['status' => 'success', 'data' => $payload]);
}
```

Syarat:
- jangan rusak aksi `get_batch_data`, `submit_bulk`, `history`
- pakai pesan error Indonesia yang konsisten
- return payload yang kompatibel dengan `addToCart()`

- [ ] **Step 4: Jalankan test untuk memastikan lulus**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: PASS, termasuk path endpoint.

- [ ] **Step 5: Commit**

```bash
git add api/process_shipment.php includes/shipment_reverse_helper.php tests/ReverseShipmentScanTest.php
git commit -m "feat: expose reverse shipment scan endpoint"
```

### Task 7: Integrasikan mode reverse ke UI shipment scan

**Files:**
- Modify: `pages/shipment_scan.php`
- Test: `tests/ReverseShipmentScanTest.php`

- [ ] **Step 1: Tulis failing UI smoke expectation di plan note**

Manual expectation:
- default mode tetap scan normal
- toggle reverse terlihat jelas
- saat reverse aktif, barcode lama yang belum ada di sistem tetap bisa menambah batch/label dan langsung masuk keranjang
- status panel menyebut mode reverse aktif

- [ ] **Step 2: Tambah toggle mode reverse di area scanner**

```html
<div class="form-check form-switch mt-3">
  <input class="form-check-input" type="checkbox" id="reverse-mode-toggle">
  <label class="form-check-label" for="reverse-mode-toggle">Mode Reverse Gudang Lama</label>
</div>
```

- [ ] **Step 3: Ubah handler `onScanSuccess()` agar memilih action sesuai mode**

```javascript
const reverseMode = document.getElementById('reverse-mode-toggle')?.checked;
const action = reverseMode ? 'reverse_scan' : 'get_batch_data';
const res = await fetch(`../api/process_shipment.php?action=${action}&qr=${encodeURIComponent(decodedText)}`);
```

- [ ] **Step 4: Tampilkan status UI yang membedakan hasil reverse**

Implement:
- `created_batch` → “Batch lama dibuat dan label masuk gudang”
- `created_label` → “Label lama ditambahkan ke batch existing”
- `existing_label` → “Label sudah dikenal, dilanjutkan ke keranjang”

- [ ] **Step 5: Pastikan `addToCart()` tetap kompatibel**

Verifikasi:
- payload reverse harus memiliki `production_id`, `batch`, `item`, `size`, `copies`, `scanned_label`, `in_warehouse`, `already_shipped`, `input_method`
- `copies` terbaru dipakai untuk render grid saat reverse menaikkan label maksimum

- [ ] **Step 6: Verifikasi manual di browser lokal**

Run:

```bash
php -S localhost:8000
```

Manual checks:
- buka `http://localhost:8000/`
- login
- masuk halaman `pages/shipment_scan.php`
- scan normal masih bekerja
- aktifkan reverse dan scan barcode lama sebelum `2026-05-25`
- pastikan batch/label baru masuk ke keranjang
- scan ulang label yang sama tidak membuat duplikasi

- [ ] **Step 7: Commit**

```bash
git add pages/shipment_scan.php
git commit -m "feat: add reverse mode to shipment scan ui"
```

### Task 8: Verifikasi akhir dan catatan operasional

**Files:**
- Modify: `docs/superpowers/specs/2026-05-24-reverse-scan-pengiriman-design.md` (hanya jika perilaku final bergeser)
- Reference: `docs/superpowers/plans/2026-05-24-reverse-scan-pengiriman.md`

- [ ] **Step 1: Jalankan seluruh smoke test**

Run: `php tests/ReverseShipmentScanTest.php`
Expected: PASS tanpa error/notice.

- [ ] **Step 2: Jalankan pemeriksaan syntax file yang disentuh**

Run:

```bash
php -l includes/shipment_reverse_helper.php
php -l api/process_shipment.php
php -l pages/shipment_scan.php
php -l tests/ReverseShipmentScanTest.php
```

Expected: `No syntax errors detected` untuk semua file.

- [ ] **Step 3: Verifikasi query data hasil reverse**

Run query manual:

```sql
SELECT id, batch, copies, production_date FROM production_labels WHERE batch = '080326-01C-SED-1000-RAMA-100ML';
SELECT production_id, label_no FROM warehouse_items WHERE production_id = ? ORDER BY label_no ASC;
SELECT production_id, label_no FROM distributor_shipments WHERE production_id = ? ORDER BY label_no ASC;
```

Expected:
- batch lama ada dengan `copies` sesuai label tertinggi hasil reverse
- label yang direverse ada di `warehouse_items`
- tidak ada duplikasi

- [ ] **Step 4: Update spec bila perlu dan commit final**

```bash
git add docs/superpowers/specs/2026-05-24-reverse-scan-pengiriman-design.md docs/superpowers/plans/2026-05-24-reverse-scan-pengiriman.md
git commit -m "docs: finalize reverse shipment scan plan"
```
