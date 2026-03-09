# AFTECH Production Label - Project Standards v6.2 (FINAL & LOCKED)

## 📌 Project Architecture
Sistem pencetakan label industri terintegrasi dengan ekosistem **Bluetooth (RPP02N)** dan **IP Network**, menggunakan antrean lokal **SQLite v7** (High Persistence) dan sinkronisasi **MySQL Cloud via Secure Tunnel**.

---

## 🏗️ Core Logic & Workflows

### 1. Database Engine (v7)
- **Local Persistence:** Master data (Items, Machines, Shifts, Units) dan **Templates** disimpan secara lokal di SQLite.
- **Offline First:** Tab Template tidak melakukan fetch internet setiap dibuka; data hanya diperbarui saat aksi "SINGKRON DATABASE" dilakukan.
- **Audit Trail:** Mencatat `device_model` (Model HP) pada setiap transaksi produksi untuk pengawasan multi-perangkat.

### 2. Tab Template (The FastTrack)
- **UI:** Grid View elegan yang serasi dengan Tab Laporan (Ikon soft, shadow halus).
- **Alur:** Klik Template -> **Quick Entry Modal** (Isi Operator & QC) -> Simpan Otomatis.
- **Logika:** Batch Code disusun di latar belakang menggunakan data template yang terkunci.

### 3. Tab Input (Manual Config)
- **Ergonomics:** Tombol **SIMPAN KE ANTREAN** bersifat **Fixed (Statis)** di bagian bawah layar. Form isian dapat di-scroll secara independen tanpa menutupi tombol utama.
- **Relational:** Pilihan Ukuran (Size) otomatis terfilter berdasarkan Item; Pilihan Qty otomatis terfilter berdasarkan Mesin.

### 4. Tab Antrean & Laporan
- **Real-time Badge:** Ikon Antrean di navbar menampilkan bulat merah berisi angka jumlah batch yang belum dicetak.
- **Date Resilience:** Backend melakukan konversi otomatis format tanggal Indonesia (`dd-MM-yyyy`) ke format MySQL (`yyyy-MM-dd`) untuk menjamin Laporan tidak kosong (`0000-00-00`).
- **Smart Merge:** Batch duplikat di antrean akan digabungkan jumlahnya (Ikon Log: **Copy** Amber).

---

## 🎨 UI/UX Master Standards

### Navigasi: Fast Liquid Curved
- **Library:** `curved_navigation_bar`.
- **Config:** Tinggi 75px, Animasi 350ms, Kurva `easeInOut`.
- **Minimalist Icon:** Hanya menampilkan ikon besar (Tanpa Teks) untuk tampilan bersih.
- **Physical Boundary:** `extendBody: false`. Konten aplikasi wajib berhenti tepat di atas navbar (tidak menembus ke belakang lekukan).

### Overlay: Elegant Glass Print
- **Visual:** Backdrop Blur (kaca buram) saat proses cetak berlangsung.
- **Animation:** Ikon printer berdenyut (*Pulse*) dengan tipografi tipis ber-spasi lebar.
- **Safety:** Tombol *Emergency Stop* selalu tersedia dalam bentuk outline tipis.

---

## 🛠️ Connectivity & Security
- **Dual Mode:** Mendukung koneksi Bluetooth dan Simulasi Koneksi IP Printer (Connect/Disconnect manual).
- **Tunnel Safety:** Alamat `ngrok` backend disembunyikan dari UI; sistem hanya menampilkan "Printer Endpoint" teknis.
- **Data Guard:** Fitur Reset Lokal dilindungi PIN 4-digit (0503) untuk mencegah penghapusan data tanpa sengaja.

---
**Status: PRODUCTION READY**
*Dokumen ini mencakup seluruh konfigurasi stabil hingga 6 Maret 2026.*

 flutter build apk --release --split-per-abi build flutter
