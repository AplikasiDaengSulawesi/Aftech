# Mobile API — Warehouse ADS

Dokumentasi endpoint REST yang dipakai oleh aplikasi mobile (Flutter). Semua path relatif ke base URL project, mis. `https://domain/api/mobile/`.

---

## Autentikasi

Semua endpoint (kecuali **`request_access`** dan **`check_access`**) memerlukan header:

```
X-API-Key: <api_key device>
Content-Type: application/json   (untuk POST JSON)
```

API key didapat dari endpoint `check_access` setelah device disetujui admin. Mekanisme verifikasi di `api/config.php::verify_api_access()` juga menerima web session sebagai fallback.

### Response umum saat gagal auth (HTTP 401)

```json
{
  "status": "error",
  "message": "401 Unauthorized: API Key Tidak Valid atau Session Login Tidak Ditemukan."
}
```

---

## Daftar Endpoint

| # | Method | Path | Auth | Fungsi |
|---|---|---|---|---|
| 1 | POST | `request_access-mobile.php` | ❌ publik | Device minta akses (pending) |
| 2 | POST / GET | `check_access-mobile.php` | ❌ publik | Cek status approval + ambil API key |
| 3 | GET | `get_master_data-mobile.php` | ✅ | Master data (items, units, sizes, shifts, machines, quantities) |
| 4 | GET | `get_templates-mobile.php` | ✅ | Daftar template label |
| 5 | POST | `save_label-mobile.php` | ✅ | Simpan label produksi |
| 6 | POST | `cancel_labels-mobile.php` | ✅ | Batalkan label per `label_no` |
| 7 | GET | `get_label_status-mobile.php` | ✅ | Statistik label per batch (issued/active/shipped/cancelled) |
| 8 | GET | `get_reports-mobile.php` | ✅ | Laporan produksi (filter tanggal/item) |
| 9 | POST | `save_log-mobile.php` | ✅ | Tulis entri ke activity_logs |
| 10 | GET | `get_labels_report-mobile.php` | ✅ | Laporan label per-batch dengan array `labels` + status |

---

## 1. `POST /api/mobile/request_access-mobile.php`

Device baru mendaftar untuk akses. Tidak perlu API key.

**Request body (JSON):**
```json
{ "device_name": "SM-A556E", "device_uuid": "BP2A.250605.031.A3" }
```

**Response:**
```json
{ "status": "success", "message": "Permintaan akses terkirim! Silakan hubungi Admin untuk persetujuan." }
```
```json
{ "status": "pending", "message": "Permintaan sedang menunggu persetujuan Admin." }
```
```json
{ "status": "success", "message": "Sudah disetujui sebelumnya." }
```

---

## 2. `POST|GET /api/mobile/check_access-mobile.php`

Cek apakah device sudah di-approve dan ambil `api_key` + `reset_pin`.

**Request (POST JSON atau query string):**
```json
{ "device_uuid": "BP2A.250605.031.A3", "device_name": "SM-A556E" }
```
> `device_name` wajib dikirim jika ada **>1 record** dengan `device_uuid` sama (konflik nama).

**Response sukses:**
```json
{
  "status": "approved",
  "api_key": "AFTECH-499F-C349-2026",
  "reset_pin": "1111",
  "message": "Otorisasi Berhasil."
}
```

**Response lain:**
- `pending` — belum disetujui admin
- `not_found` — UUID belum pernah daftar
- `error` — konflik: beberapa device pakai UUID sama, `device_name` tidak dikirim

---

## 3. `GET /api/mobile/get_master_data-mobile.php`

Ambil seluruh master data sekaligus. Cocok untuk dipanggil sekali saat startup app.

**Response:**
```json
{
  "items":      [ { "name": "SEDOTAN", "unit": "PCS" }, ... ],
  "shifts":     ["SHIFT A", "SHIFT B", "SHIFT C"],
  "units":      ["PCS", "ML", "KG"],
  "machines":   [ { "name": "MACHINE 1", "status": "active" }, ... ],
  "sizes":      [ { "size_value": "100", "parent_item": "SEDOTAN" }, ... ],
  "quantities": [ { "qty_value": "1000", "parent_machine": "MACHINE 1" }, ... ]
}
```

---

## 4. `GET /api/mobile/get_templates-mobile.php`

Daftar template label yang di-save admin.

**Response (array):**
```json
[
  {
    "id": 1,
    "template_name": "Sedotan 100PCS Thermo A",
    "item": "SEDOTAN",
    "size": "100",
    "unit": "PCS",
    "machine": "THERMO TINGGI 01",
    "shift": "SHIFT A",
    "quantity": "1000"
  }
]
```

**Error:**
```json
{ "status": "error", "message": "<mysql error>" }
```

---

## 5. `POST /api/mobile/save_label-mobile.php`

Simpan label produksi. Kalau batch sudah ada → `copies` ditambah (mode re-print/append).

**Request body (JSON):**
```json
{
  "batch": "080326-01C-SED-1000-RAMA-100PCS",
  "item": "SEDOTAN",
  "size": "100",
  "unit": "PCS",
  "machine": "THERMO TINGGI 01",
  "shift": "SHIFT C",
  "quantity": "1000",
  "operator": "rahmat",
  "qc": "mamat",
  "production_date": "08-03-2026",   // format dd-MM-yyyy
  "production_time": "02:53:49",
  "copies": 10,
  "input_method": "scan",            // optional: "scan" | "manual", default "scan"
  "device_model": "SM-A556E",         // optional
  "device_id": "BP2A.250605.031.A3"   // optional, UUID device
}
```

**Efek samping:**
- INSERT ke `production_labels` (atau `copies += VALUES(copies)` kalau batch ada)
- Jika `app_settings.qc_checker_enabled = 0` → auto-transfer:
  - INSERT IGNORE ke `warehouse_transfers`
  - INSERT IGNORE ke `warehouse_items` per `label_no` (1..copies)
  - `warehouse_items.input_method` diisi dari request `input_method`
    - `scan` → stok gudang ditandai sebagai hasil scan QR
    - `manual` → stok gudang ditandai sebagai input manual
    - jika field tidak dikirim / nilainya tidak valid → fallback ke `scan`

**Catatan `input_method`:**
- Field ini hanya memengaruhi label yang **langsung auto-masuk gudang**
- Jika QC Checker aktif (`qc_checker_enabled = 1`), label masih berstatus produksi dan belum ada record `warehouse_items`
- Untuk kompatibilitas dengan client lama, endpoint ini tetap aman dipanggil tanpa `input_method`; hasilnya dianggap `scan`

**Response sukses:**
```json
{
  "status": "success",
  "message": "Berhasil Disimpan",
  "production_id": 120,
  "batch": "080326-01C-SED-1000-RAMA-100PCS",
  "copies": 10,
  "first_label_no": 11,
  "last_label_no": 20,
  "input_method": "scan",
  "label_nos": [11,12,13,14,15,16,17,18,19,20],
  "qr_codes": [
    "11-080326-01C-SED-1000-RAMA-100PCS",
    "12-080326-01C-SED-1000-RAMA-100PCS",
    "...",
    "20-080326-01C-SED-1000-RAMA-100PCS"
  ]
}
```

> `first_label_no` / `last_label_no` = range yang **baru** diterbitkan di request ini. Kalau batch sudah ada 10 copies lalu request ini `copies=5`, range-nya `11..15`. QR per dus sudah siap pakai (`{label_no}-{batch}`), tinggal di-render ke printer.

> `input_method` pada response menunjukkan metode yang dipakai untuk auto-transfer gudang pada request tersebut.

**Response error:**
```json
{ "status": "error", "message": "<mysql error>" }
```

---

## 6. `POST /api/mobile/cancel_labels-mobile.php`

Batalkan label spesifik (per `label_no`) dari 1 batch. Otomatis mendeteksi kategori:
- `production` — label belum masuk gudang
- `warehouse` — label sudah di `warehouse_items` (akan dihapus dari gudang)
- Label yang sudah ada di `distributor_shipments` → **diblokir** (pakai proses retur)

Lihat juga: [docs perilaku pembatalan](#catatan-pembatalan-label).

**Request body (JSON):**
```json
{
  "production_id": 120,
  "label_nos": [3, 7, 9],
  "reason": "Rusak saat printing",     // optional
  "cancelled_by": "Rahmat",            // optional, default: session user / "Mobile User"
  "device_id": "BP2A.250605.031.A3"    // optional, UUID device (disimpan untuk audit & filter)
}
```

**Response:**
```json
{
  "status": "success",
  "production_id": 120,
  "batch": "080326-01C-SED-1000-RAMA-100PCS",
  "summary": {
    "cancelled_production": 1,
    "cancelled_warehouse": 1,
    "blocked_shipped": 1,
    "skipped_duplicate": 0,
    "out_of_range": 0
  },
  "details": [
    { "label_no": 3, "action": "cancelled", "category": "production" },
    { "label_no": 7, "action": "cancelled", "category": "warehouse" },
    { "label_no": 9, "action": "blocked",   "reason": "sudah dikirim ke distributor, gunakan proses retur" }
  ]
}
```

**Error validasi:**
```json
{ "status": "error", "message": "Input tidak valid. Wajib: production_id (int), label_nos (array of int)." }
```
```json
{ "status": "error", "message": "Batch produksi tidak ditemukan." }
```

**Catatan:**
- Semua operasi dibungkus 1 transaction (rollback kalau error sistemik)
- `production_labels.copies` **tidak dikurangi** (tetap mencerminkan jumlah terbitan awal)
- Kalau sukses minimal 1 label dibatalkan → entry `activity_logs` dengan `action='BATAL_LABEL'`
- Uniqueness `(production_id, label_no)` di tabel `cancelled_labels` mencegah duplikat pembatalan

---

## 7. `GET /api/mobile/get_label_status-mobile.php`

Statistik & daftar pembatalan per batch.

**Query params (salah satu wajib):**
- `production_id=120`
- `batch=080326-01C-SED-1000-RAMA-100PCS`

**Response:**
```json
{
  "status": "success",
  "batch": {
    "production_id": 120,
    "batch": "080326-01C-SED-1000-RAMA-100PCS",
    "item": "SEDOTAN",
    "size": "100",
    "unit": "PCS"
  },
  "stats": {
    "issued": 10,
    "active": 7,
    "shipped": 1,
    "cancelled_production": 1,
    "cancelled_warehouse": 1,
    "cancelled_total": 2,
    "is_consistent": true
  },
  "cancelled_labels": [
    { "label_no": 3, "category": "production", "reason": "Rusak saat printing", "cancelled_by": "Rahmat", "cancelled_at": "2026-04-17 09:12:03" },
    { "label_no": 7, "category": "warehouse",  "reason": "Rusak saat printing", "cancelled_by": "Rahmat", "cancelled_at": "2026-04-17 09:12:03" }
  ]
}
```

**Invariant:** `issued = active + shipped + cancelled_total`. Kalau `is_consistent=false` → ada ketidaksinkronan antar tabel.

---

## 8. `GET /api/mobile/get_reports-mobile.php`

Laporan produksi untuk view mobile.

**Query params (semua opsional):**
| Param | Default | Keterangan |
|---|---|---|
| `start_date`   | tanggal 1 bulan ini | `YYYY-MM-DD`, filter `production_date` |
| `end_date`     | hari ini | `YYYY-MM-DD`, filter `production_date` |
| `item`         | — | filter exact item name |
| `device_id`    | — | filter exact UUID device |
| `device_model` | — | filter exact nama/tipe device |

**Response (array):**
```json
[
  {
    "production_date": "2026-03-08",
    "batch": "080326-01C-SED-1000-RAMA-100PCS",
    "item": "SEDOTAN",
    "size": "100",
    "unit": "PCS",
    "operator": "rahmat",
    "qc": "mamat",
    "machine": "THERMO TINGGI 01",
    "shift": "SHIFT C",
    "production_time": "02:53:49",
    "device_model": "SM-A556E",
    "device_id": "BP2A.250605.031.A3",
    "produced_qty": 10000,
    "copies": 10
  }
]
```

> `produced_qty = quantity × copies`. `device_model` / `device_id` diisi dari request `save_label` terakhir untuk batch tersebut (bisa kosong untuk data lama).

---

## 9. `POST /api/mobile/save_log-mobile.php`

Tulis entri aktivitas ke `activity_logs`.

**Request body (JSON):**
```json
{ "action": "LOGIN", "details": "User ahmad login dari SM-A556E" }
```

**Response:**
```json
{ "status": "success" }
```
```json
{ "status": "error", "message": "<mysql error>" }
```
```json
{ "status": "error", "message": "No data received" }
```

---

## 10. `GET /api/mobile/get_labels_report-mobile.php`

Laporan per-batch + **daftar label (array)** dengan status tiap label. Cocok untuk preview/print ulang dari mobile.

**Query params (semua opsional):**
| Param | Default | Keterangan |
|---|---|---|
| `start_date`   | tanggal 1 bulan ini | `YYYY-MM-DD`, filter `production_date` |
| `end_date`     | hari ini | `YYYY-MM-DD`, filter `production_date` |
| `item`         | — | filter exact `item` |
| `batch`        | — | filter exact `batch` (single batch) |
| `device_id`    | — | filter exact UUID device |
| `device_model` | — | filter exact nama/tipe device |

**Response (array batch):**
```json
[
  {
    "production_id": 120,
    "batch": "080326-01C-SED-1000-RAMA-100PCS",
    "item": "SEDOTAN",
    "size": "100",
    "unit": "PCS",
    "machine": "THERMO TINGGI 01",
    "shift": "SHIFT C",
    "operator": "rahmat",
    "qc": "mamat",
    "production_date": "2026-03-08",
    "production_time": "02:53:49",
    "copies": 10,
    "quantity": 1000,
    "device_model": "SM-A556E",
    "device_id": "BP2A.250605.031.A3",
    "stats": {
      "issued": 10,
      "active": 7,
      "shipped": 1,
      "cancelled": 2,
      "pending": 0,
      "is_consistent": true
    },
    "labels": [
      { "label_no": 1, "status": "active",    "qr_code": "1-080326-01C-SED-1000-RAMA-100PCS" },
      { "label_no": 2, "status": "shipped",   "qr_code": "2-080326-01C-SED-1000-RAMA-100PCS" },
      { "label_no": 3, "status": "cancelled", "qr_code": "3-080326-01C-SED-1000-RAMA-100PCS",
        "category": "production", "reason": "Rusak saat printing", "cancelled_by": "Rahmat", "cancelled_at": "2026-04-17 09:12:03" }
    ]
  }
]
```

**Status label:**
- `active` — ada di `warehouse_items`, belum dikirim
- `shipped` — ada di `distributor_shipments`
- `cancelled` — ada di `cancelled_labels` (dengan `category`: `production` | `warehouse`)
- `pending` — belum masuk gudang (mode QC aktif, label terbit tapi belum di-scan QC)

**Invariant:** `issued = active + shipped + cancelled + pending`. `is_consistent=false` menandakan ketidaksinkronan antar tabel.

**Efisiensi:** 1 query header + 3 query (warehouse, shipped, cancelled) dengan `IN (...)` — tidak N+1 per batch.

---

## Catatan pembatalan label

Alur status per label `(production_id, label_no)`:

```
belum terbit → production_labels (copies+=1)
       ↓ (via save_label / QC scan / auto-transfer)
  warehouse_items (aktif di gudang)
       ↓ (via process_shipment web/admin)
  distributor_shipments (sudah dikirim)
```

Pembatalan dibolehkan **sebelum** label masuk `distributor_shipments`. Setelah dikirim, pakai proses **retur** (tidak diekspos di mobile saat dokumen ini ditulis).

`cancelled_labels` menyimpan jejak per label yang dibatalkan beserta kategorinya. `production_labels.copies` **tidak pernah dikurangi**, jadi:

```
terbitan_awal = production_labels.copies
dibatalkan    = COUNT(cancelled_labels WHERE production_id = X)
aktif         = COUNT(warehouse_items  WHERE production_id = X)
terkirim      = COUNT(distributor_shipments WHERE production_id = X)
```

---

## Response konvensi

- Sukses: `status = "success"` (atau key spesifik seperti `approved`)
- Gagal: `status = "error"` + `message`
- Endpoint yang mengembalikan array (list) sering **tidak membungkus** dalam `{ status, data }` — cek masing-masing bagian di atas.

## Error auth umum

```json
{ "status": "error", "message": "401 Unauthorized: API Key Tidak Valid atau Session Login Tidak Ditemukan." }
```

## Base URL development

Jika pakai PHP built-in server: `php -S localhost:8000` dari root project → `http://localhost:8000/api/mobile/<endpoint>.php`.
