# AFTECH System Architecture & Development Guidelines
*Locked via Master Backend & UI/UX Standards*

## 1. UI/UX Identity (Indigo & Amber Theme)
Aplikasi menggunakan gaya visual yang profesional dan bersih.
- **Primary Color:** Indigo Deep (`#1A237E`) - Digunakan untuk Header, Tombol Aksi Utama, dan Navigasi Aktif.
- **Secondary Color:** Indigo Light (`#3F51B5`) - Digunakan untuk Hover dan status sekunder.
- **Accent Color:** Amber (`#FFC107`) - Digunakan untuk warning, Last Scanned item, dan tombol Edit/Transfer.
- **Status Colors:** Green (`#00C853`) untuk Success/Verified, Red (`#D50000`) untuk Error/Delete.
- **Table Style:** Selalu gunakan class `.table-responsive-md`, `.shadow-hover`, dengan thead `.bg-light`. Jangan gunakan `.table-sm` agar baris memiliki ruang bernapas (spacing) yang baik.
- **Skeleton Loading:** Wajib digunakan di semua pengambilan data via AJAX untuk memberikan feedback visual yang halus tanpa tulisan "Memuat data...".

## 2. SPA & JavaScript Engine (Pure AJAX)
- **Zero-Reload Policy:** Jangan pernah menggunakan `window.location.reload()`. Semua aksi (CRUD) wajib merender ulang tabel spesifik menggunakan AJAX.
- **Instant Caching:** Data tabel harus disimpan di `sessionStorage`. Saat pindah halaman, data cache dimuat seketika (0 detik), lalu di-update di *background* via AJAX.
- **Modal Destruction:** Sistem SPA di `public/js/spa_nav.js` otomatis melakukan `dispose()` pada setiap modal yang terbuka dan menghapus `.modal-backdrop` saat pindah menu.
- **Global Scope:** Semua fungsi penarik data (`fetchProduction`, `renderLog`, dll) harus diikat ke `window` agar bisa dipanggil oleh mesin SPA.

## 3. Database Architecture (Relational & Cascading)
- **Master Tables:** Data acuan (Item, Mesin, Shift, Unit) tersimpan di tabel `master_` dan menjadi sumber untuk dropdown form.
- **Production Hub:** Tabel `production_labels` adalah pusat data. Kolom `copies` mencatat total label yang dicetak.
- **Serialized QC:** 
  - `qc_scans`: Menyimpan ringkasan progress QC (Total scanned).
  - `qc_scan_details`: Menyimpan nomor unik label yang sudah dicheck. Berlaku rule `UNIQUE KEY (production_id, label_no)` untuk mencegah duplikasi.
- **Warehouse Inventory:** 
  - `warehouse_transfers`: Menyimpan log aktivitas transfer.
  - `warehouse_items`: Menyimpan nomor urut label yang fisik barangnya benar-benar sudah ada di gudang.
- **Cascading:** Tabel QC dan Gudang terhubung via `FOREIGN KEY ... ON DELETE CASCADE` ke tabel produksi. Jika batch produksi dihapus, semua histori scan dan stok gudangnya otomatis hilang.

## 4. QC Scanner Module
- Menggunakan `html5-qrcode` dengan FPS tinggi (20-25fps).
- **Cinema Seat Map:** Progres QC divisualisasikan dalam bentuk grid kursi bioskop.
  - Kotak Hijau: Sudah discan.
  - Kotak Abu: Belum discan.
  - Kotak Kuning (Pulse): Terakhir discan.
- **Anti-Double Scan:** Sistem menolak scan label yang sama untuk kedua kalinya berdasarkan tabel `qc_scan_details`.
- **Status Panel:** Notifikasi sukses/gagal di-render langsung di panel UI (di bawah kamera), menghindari penggunaan SweetAlert yang mengganggu kecepatan scan QC.

## 5. Warehouse Transfer Logic
- **Partial Transfer:** Menggunakan logika Drag-and-Select pada Modal Cinema Grid.
- Hanya label berstatus **Hijau (Lolos QC)** yang bisa dipilih untuk ditransfer ke gudang.
- Label yang sudah ditransfer akan berubah status menjadi **Indigo (Terkunci)** di grid pemilihan.
- Proses pengiriman dicatat secara individu di `warehouse_items`.

## 6. Security
- Password dienkripsi menggunakan `password_hash()`.
- Aksi hapus dan transfer kritis wajib dilindungi dengan `Swal.fire` confirm dialog.
- Segala aksi (Tambah, Edit, Hapus, Scan, Transfer) wajib dicatat ke tabel `activity_logs`.