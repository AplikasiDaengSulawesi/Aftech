# Reverse Scan Pengiriman Design

## Ringkasan

Tambahkan mode `Reverse Gudang` pada halaman scan pengiriman untuk merekonstruksi data batch dan label lama dari barcode gudang. Fitur ini hanya aktif untuk data produksi dengan tanggal sebelum `2026-05-25`.

Barcode tetap mengikuti format sistem yang ada:

`{label_no}-{batch}`

Contoh:

`12-080326-01C-SED-1000-RAMA-100ML`

Mode reverse dipakai ketika barcode lama dari gudang discan tetapi data batch atau label belum lengkap di sistem. Tujuannya adalah mengisi data historis tanpa mengubah alur scan pengiriman normal untuk data produksi baru.

## Tujuan

- Memungkinkan scan barcode gudang lama dari halaman scan pengiriman.
- Jika batch belum ada di `production_labels`, sistem membuat data batch dari parsing kode batch dan tabel referensi.
- Jika batch sudah ada tetapi label hasil scan belum terdaftar, sistem menambahkan label itu ke gudang.
- Jika batch dan label sudah ada, sistem tidak membuat data baru dan hanya mengembalikan status informatif.
- Menolak reverse untuk data produksi pada atau setelah `2026-05-25`.

## Ruang Lingkup

Masuk dalam scope:

- Toggle mode reverse di halaman `pages/shipment_scan.php`.
- Endpoint baru berupa action tambahan di `api/process_shipment.php`.
- Helper PHP baru untuk parsing batch, lookup referensi, validasi cutoff, dan sinkronisasi data label.
- Logging aktivitas reverse ke `activity_logs`.
- Test untuk aturan reverse utama.

Di luar scope:

- Perubahan format barcode.
- Workflow retur pengiriman.
- Rekonstruksi data untuk batch yang tidak bisa dipetakan ke master referensi.
- Reverse untuk produksi tanggal `2026-05-25` dan sesudahnya.

## Format Batch

Format batch yang disepakati:

`080326-01C-SED-1000-RAMA-100ML`

Makna segmen:

- `080326`: tanggal produksi `ddmmyy` → `2026-03-08`
- `01C`: kode thermo/mesin + shift
- `SED`: kode item
- `1000`: quantity per label
- `RAMA`: kode operator/QC
- `100ML`: ukuran barang

Semua kode tersebut sudah memiliki tabel referensi di sistem. Fitur reverse tidak boleh menanamkan mapping hardcoded untuk operator, QC, machine, item, atau ukuran.

## Arsitektur

### UI

Halaman `pages/shipment_scan.php` mendapat kontrol tambahan untuk mengaktifkan mode `Reverse Gudang`.

Perilaku UI:

- Default tetap mode scan pengiriman normal.
- Saat reverse aktif, hasil scan barcode dikirim ke action backend reverse.
- Response reverse ditampilkan pada panel status yang sama agar operator tetap bekerja dari halaman yang sama.
- Setelah reverse sukses:
  - jika label baru dibuat/ditambahkan ke gudang, UI dapat langsung meneruskan label itu ke keranjang pengiriman;
  - jika label sudah ada, UI menerima data batch seperti scan normal dan dapat melanjutkan seleksi pengiriman.

### Backend Entry Point

File `api/process_shipment.php` menambah action baru, misalnya `reverse_scan`.

Action ini tidak memuat semua logika bisnis di file utama. Ia hanya:

- membaca barcode mentah;
- memvalidasi format dasar;
- memanggil helper reverse;
- mengubah hasil helper menjadi payload JSON untuk UI.

### Helper Reverse

Tambahkan helper baru, misalnya `includes/shipment_reverse_helper.php`, dengan tanggung jawab:

- parse barcode menjadi `label_no` dan `batch`;
- parse batch menjadi komponen domain;
- konversi tanggal `ddmmyy` ke `Y-m-d`;
- validasi cutoff tanggal;
- lookup data referensi dari master yang sudah ada;
- cek/muat batch produksi existing;
- membuat `production_labels` jika batch belum ada;
- menyesuaikan `copies` jika label baru berada di luar range terbit saat ini;
- menambahkan row `warehouse_items` jika label belum ada di gudang;
- menolak jika label sudah pernah dikirim;
- mengembalikan payload yang konsisten untuk dipakai UI shipment scan.

## Aturan Data

### Aturan cutoff

- Reverse hanya boleh jika `production_date < 2026-05-25`.
- Data dengan `production_date = 2026-05-25` atau lebih baru harus ditolak dengan pesan yang jelas.

### Jika batch belum ada

Sistem membuat satu row baru di `production_labels` menggunakan hasil parsing dan lookup referensi.

Nilai penting:

- `batch`: dari barcode
- `production_date`: hasil parse dari segmen tanggal
- `machine` dan `shift`: hasil lookup dari segmen `01C`
- `item`: hasil lookup dari kode item
- `quantity`: dari segmen `1000`
- `size` dan `unit`: hasil lookup dari segmen `100ML`
- `operator` dan `qc`: hasil lookup dari kode `RAMA`
- `copies`: di-set minimal sebesar `label_no` hasil scan, karena label itu harus dianggap sudah pernah diterbitkan

Setelah batch dibuat, sistem menambahkan label ke `warehouse_items`.

### Jika batch sudah ada dan label belum ada

Sistem memeriksa:

- apakah label sudah ada di `distributor_shipments`;
- apakah label sudah ada di `warehouse_items`;
- apakah label sudah tercatat sebagai cancelled bila aturan pembatalan perlu dihormati pada data lama.

Jika label belum ada di gudang dan belum pernah dikirim:

- bila `label_no > copies`, update `production_labels.copies = label_no`;
- insert row baru di `warehouse_items`.

### Jika batch dan label sudah ada

Mengikuti keputusan bisnis yang disetujui:

- sistem tidak menambah batch;
- sistem tidak menambah copies;
- sistem tidak insert ulang ke `warehouse_items`;
- sistem mengembalikan status sukses informatif agar scan bisa diperlakukan sebagai label yang sudah dikenal.

### Jika label sudah pernah dikirim

Reverse ditolak. Data kirim historis tidak boleh ditimpa oleh rekonstruksi gudang.

## Lookup Referensi

Reverse bergantung pada tabel referensi yang sudah tersedia di aplikasi. Implementasi harus memakai lookup data master, bukan mapping statis di kode.

Jika salah satu komponen tidak bisa di-resolve, proses gagal dengan pesan spesifik, misalnya:

- kode item tidak ditemukan
- kode machine/shift tidak ditemukan
- kode operator/QC tidak ditemukan
- kode ukuran tidak ditemukan

Hal ini sengaja ketat agar reverse tidak membuat data produksi yang salah.

## Alur Response

Response reverse perlu kompatibel dengan kebutuhan halaman shipment scan.

Minimal payload sukses memuat:

- `production_id`
- `batch`
- `item`
- `size`
- `copies`
- `scanned_label`
- `in_warehouse`
- `already_shipped`
- status operasi reverse, misalnya `created_batch`, `created_label`, atau `existing_label`

Dengan payload ini, UI dapat:

- menampilkan hasil reverse ke operator;
- langsung memuat data batch ke keranjang pengiriman;
- membedakan apakah ada data yang baru direkonstruksi atau hanya ditemukan.

## Error Handling

Kasus gagal yang wajib ditangani:

- barcode kosong
- format barcode tidak valid
- format batch tidak valid
- tanggal produksi batch tidak valid
- batch setelah cutoff reverse
- referensi master tidak ditemukan
- label sudah pernah dikirim
- kegagalan insert/update database

Semua operasi tulis harus dibungkus transaction agar tidak ada batch setengah jadi.

## Logging dan Audit

Setiap reverse yang mengubah data harus menulis `activity_logs` dengan detail yang membedakan:

- pembuatan batch lama dari reverse scan
- penambahan label ke batch existing
- scan label existing tanpa perubahan data

Log minimal menyebut batch, label, dan user session yang menjalankan reverse.

## Testing

Test yang dibutuhkan:

1. Reverse scan membuat batch baru untuk barcode lama dengan tanggal sebelum cutoff.
2. Reverse scan menambah label baru ke batch existing dan menaikkan `copies` bila perlu.
3. Reverse scan untuk label existing tidak menambah `copies` dan tidak membuat row gudang duplikat.
4. Reverse scan ditolak untuk `production_date >= 2026-05-25`.
5. Reverse scan ditolak bila lookup referensi gagal.
6. Reverse scan ditolak bila label sudah ada di `distributor_shipments`.

## Risiko dan Mitigasi

- Parser batch salah memetakan referensi.
  Mitigasi: validasi ketat dan gagal cepat jika lookup master tidak match.
- `copies` tidak sinkron dengan nomor label hasil reverse.
  Mitigasi: selalu naikkan `copies` minimal sampai `label_no` tertinggi yang direkonstruksi.
- Reverse mencampur data lama dan data baru.
  Mitigasi: cutoff keras di `2026-05-25`.
- UI operator bingung membedakan mode normal dan reverse.
  Mitigasi: toggle reverse harus jelas dan status panel harus menyebut mode aktif.
