<?php
// Cetak Surat Jalan format continuous form 8.5x5.5 inci landscape (dot matrix).
// Dioptimalkan untuk Epson LX-310/LX-350/FX-890 atau setara.

session_start();
require_once '../includes/db.php';
date_default_timezone_set('Asia/Makassar');

// === KOP PERUSAHAAN ===
$company = [
    'name'    => 'PT AFTECH MAKASSAR INDONESIA',
    'address' => 'Jl. Kima 8 Kav. SS-23 B Kawasan Industri Makassar',
    'city'    => 'Makassar - Sul. Sel',
    'phone'   => '(0411) 518 419 / 082344662266',
    'email'   => 'iloaftech@gmail.com',
];

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$auto = isset($_GET['auto']) ? (int)$_GET['auto'] : 0;
if (!$id) die("ID Pengiriman tidak valid.");

try {
    $stmt = $pdo->prepare("SELECT * FROM outbound_shipments WHERE id = ?");
    $stmt->execute([$id]);
    $header = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$header) die("Data pengiriman tidak ditemukan.");
} catch (Throwable $e) {
    die("Gagal menyiapkan surat jalan: " . htmlspecialchars($e->getMessage()));
}

$stmtDet = $pdo->prepare("
    SELECT p.item, p.size, p.unit, p.machine,
           SUM(b.label_qty) AS label_qty,
           SUM(b.unit_qty)  AS unit_qty
    FROM outbound_shipment_batches b
    JOIN production_labels p ON b.production_id = p.id
    WHERE b.shipment_id = ?
    GROUP BY p.item, p.size, p.unit, p.machine
    ORDER BY p.item, p.size, p.machine
");
$stmtDet->execute([$id]);
$details = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

function tgl_indo($tanggal) {
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $p = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $p[2] . ' ' . $bulan[(int)$p[1]] . ' ' . $p[0];
}

$tanggal_kirim = tgl_indo($header['shipment_date']);
$tanggal_cetak = date('d-m-Y H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Jalan <?php echo htmlspecialchars($header['surat_jalan_no']); ?></title>
<style>
/* ================================================================
   DOT MATRIX OPTIMIZED — Epson LX / FX series
   Form   : Continuous 8.5 x 5.5 in landscape (half-sheet)
   Semua ukuran font dikurangi 1pt dari versi sebelumnya.
================================================================ */

@page {
    size: 8.5in 5.5in;
    /* Top 0.5in  |  Right 1.5in  |  Bottom 0.2in  |  Left 0.25in */
    margin: 0.3in 1.5in 0.0in 0.0in;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    background: #fff;
    color: #000;
    width: 100%;
}

body {
    font-family: 'Calibri', 'Carlito', sans-serif;
    font-size: 10pt;        /* was 12pt */
    line-height: 1.65;
    color: #000;
}

.sheet {
    width: 100%;
    max-width: 7.9in;
    margin: 0 auto 0 0; /* rata kiri — margin kanan otomatis, kiri 0 */
}

/* ---- KOP ---- */
.kop-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1px solid #000;
    padding-bottom: 3px;
    margin-bottom: 3px;
}
.kop-table td { vertical-align: top; padding: 0 2px; }

.kop-name {
    font-size: 12pt;        /* was 14pt */
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.4;
}
.kop-meta { font-size: 9pt; line-height: 1.55; }  /* was 11pt */

/* ---- JUDUL ---- */
.doc-title {
    font-size: 14pt;        /* was 16pt */
    font-weight: 700;
    text-decoration: underline;
    text-align: center;
    letter-spacing: 4px;
    line-height: 1.5;
}

/* ---- META NOMOR ---- */
.meta-table { width: 100%; border-collapse: collapse; margin-top: 3px; }
.meta-table td { padding: 0 2px; font-size: 9pt; line-height: 1.6; vertical-align: top; }  /* was 11pt */
.meta-table .lbl { width: 60px; }
.meta-table .sep { width: 12px; text-align: center; }

/* ---- KEPADA ---- */
.recipient {
    margin-top: 4px;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    padding: 2px 0;
}
.recipient .row { display: flex; line-height: 1.6; }
.recipient .lbl { width: 72px; font-size: 9pt; font-weight: 700; }  /* was 11pt */
.recipient .sep { width: 14px; text-align: center; font-size: 9pt; }  /* was 11pt */
.recipient .val { font-size: 9pt; }  /* was 11pt */

/* ---- TABEL ITEM ---- */
table.items {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
    font-size: 9pt;        /* was 11pt */
}
table.items th,
table.items td {
    border: 1px solid #000;
    padding: 2px 4px;
    vertical-align: middle;
    line-height: 1.5;
    background: #fff;
    color: #000;
}
table.items th { font-weight: 700; text-align: center; }
table.items td.no   { width: 30px;  text-align: center; }
table.items td.qty  { width: 58px;  text-align: right;  }
table.items td.unit { width: 68px;  text-align: center; }
table.items td.isi  { width: 126px; text-align: right;  }

/* small tag di nama barang — tetap hitam, bukan abu */
table.items td.name small { font-size: 8pt; color: #000; }  /* was 10pt */

/* ---- TANGGAL ---- */
.place-date { text-align: right; margin-top: 5px; font-size: 9pt; }  /* was 11pt */

/* ---- TANDA TANGAN ---- */
table.ttd { width: 100%; border-collapse: collapse; margin-top: 6px; }
table.ttd td {
    width: 25%;
    text-align: center;
    font-size: 9pt;        /* was 11pt */
    vertical-align: top;
    padding: 0 3px;
    line-height: 1.5;
}
table.ttd .role { font-weight: 700; font-size: 10pt; text-decoration: underline; }  /* was 12pt */
table.ttd .sign-space { height: 42px; }
table.ttd .name-line {
    border-top: 1px solid #000;
    padding-top: 2px;
    display: inline-block;
    min-width: 88%;
}

/* ---- DISTRIBUSI & FOOTNOTE ---- */
.distribution {
    margin-top: 5px;
    text-align: center;
    font-size: 8pt;         /* was 10pt */
    border-top: 1px solid #000;
    padding-top: 3px;
    line-height: 1.5;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #000;
}
.footnote {
    margin-top: 1px;
    text-align: right;
    font-size: 7.5pt;       /* was 9.5pt */
    line-height: 1.4;
    color: #000;
}

/* ---- TOOLBAR (layar saja) ---- */
.toolbar {
    position: fixed; top: 8px; right: 12px;
    background: #fff; border: 1px solid #000;
    padding: 4px 8px;
    font-family: 'Calibri', 'Carlito', sans-serif; font-size: 11px;
    z-index: 9999;
}
.toolbar button {
    cursor: pointer; padding: 4px 10px; margin-left: 4px;
    font-size: 11px; font-family: 'Calibri', 'Carlito', sans-serif;
    border: 1px solid #000; background: #fff; color: #000;
}

/* ---- PRINT OVERRIDES ---- */
@media print {
    html, body  { background: #fff; }
    .sheet      { padding: 0; }
    .no-print   { display: none !important; }

    /* Paksa border konsisten saat print */
    table.items th,
    table.items td      { border: 1px solid #000 !important; background: #fff !important; color: #000 !important; }
    table.ttd .name-line { border-top: 1px solid #000 !important; }
    .kop-table           { border-bottom: 1px solid #000 !important; }
    .recipient           { border-top: 1px solid #000 !important; border-bottom: 1px solid #000 !important; }
    .distribution        { border-top: 1px solid #000 !important; }
}
<?php if ($auto): ?>
.toolbar { display: none !important; }
<?php endif; ?>
</style>
</head>
<body onload="window.print()">

<div class="toolbar no-print">
    <button id="btn-download" onclick="window.print()">Cetak Surat Jalan</button>
    <button onclick="window.close()">Tutup</button>
</div>

<div class="sheet">

<!-- ====== KOP & JUDUL ====== -->
<table class="kop-table">
    <tr>
        <td style="width:55%;">
            <div class="kop-name"><?php echo htmlspecialchars($company['name']); ?></div>
            <div class="kop-meta"><?php echo htmlspecialchars($company['address']); ?></div>
            <div class="kop-meta"><?php echo htmlspecialchars($company['city']); ?></div>
            <div class="kop-meta">Phone : <?php echo htmlspecialchars($company['phone']); ?></div>
            <div class="kop-meta">Email : <?php echo htmlspecialchars($company['email']); ?></div>
        </td>
        <td style="width:45%;">
            <div class="doc-title">SURAT JALAN</div>
            <table class="meta-table">
                <tr>
                    <td class="lbl">No</td>
                    <td class="sep">:</td>
                    <td><strong><?php echo htmlspecialchars($header['surat_jalan_no'] ?? '-'); ?></strong></td>
                </tr>
                <tr>
                    <td class="lbl">No SP</td>
                    <td class="sep">:</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="lbl">Hal</td>
                    <td class="sep">:</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- ====== KEPADA ====== -->
<div class="recipient">
    <div class="row">
        <div class="lbl">Kepada</div>
        <div class="sep">:</div>
        <div class="val"><strong><?php echo htmlspecialchars($header['customer_name']); ?></strong></div>
    </div>
    <div class="row">
        <div class="lbl">Alamat</div>
        <div class="sep">:</div>
        <div class="val"><?php echo nl2br(htmlspecialchars($header['customer_address'] ?? '-')); ?></div>
    </div>
    <div class="row">
        <div class="lbl">Telp/Hp</div>
        <div class="sep">:</div>
        <div class="val"><?php echo htmlspecialchars($header['customer_contact'] ?? '-'); ?></div>
    </div>
</div>

<!-- ====== TABEL ITEM ====== -->
<table class="items">
    <thead>
        <tr>
            <th style="width:30px;">No</th>
            <th>Nama Barang</th>
            <th style="width:58px;">Qty</th>
            <th style="width:68px;">Item</th>
            <th style="width:126px;">Jumlah Isi<br>(Satuan)</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; foreach ($details as $row): ?>
            <?php
            $perDus = ($row['label_qty'] > 0)
                ? (int)round($row['unit_qty'] / $row['label_qty'])
                : (int)$row['unit_qty'];
            $namaBarang = trim(strtoupper(
                $row['item'] . ' ' . $row['size'] . ' ' . $row['unit']
            ));
            $isiText = ($perDus > 0)
                ? number_format($perDus, 0, ',', '.') . ' ' . strtoupper($row['unit'] ?? '')
                : '-';
            $mesin = trim($row['machine'] ?? '');
            ?>
            <tr>
                <td class="no"><?php echo $no++; ?></td>
                <td class="name">
                    <?php if ($mesin !== ''): ?>
                        <small>Mesin: <?php echo htmlspecialchars(strtoupper($mesin)); ?>, </small>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($namaBarang); ?>
                </td>
                <td class="qty"><?php echo (int)$row['label_qty']; ?></td>
                <td class="unit">Dos</td>
                <td class="isi"><?php echo htmlspecialchars(trim($isiText)); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php
        $minRows = 3;
        $pad = max(0, $minRows - count($details));
        for ($i = 0; $i < $pad; $i++): ?>
            <tr>
                <td class="no">&nbsp;</td>
                <td class="name">&nbsp;</td>
                <td class="qty">&nbsp;</td>
                <td class="unit">&nbsp;</td>
                <td class="isi">&nbsp;</td>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>

<!-- ====== TANGGAL ====== -->
<div class="place-date">Makassar, <?php echo $tanggal_kirim; ?></div>

<!-- ====== TANDA TANGAN ====== -->
<table class="ttd">
    <tr>
        <td class="role">Customer</td>
        <td class="role">Security</td>
        <td class="role">Gudang</td>
        <td class="role">Direktur</td>
    </tr>
    <tr>
        <td class="sign-space">&nbsp;</td>
        <td class="sign-space">&nbsp;</td>
        <td class="sign-space">&nbsp;</td>
        <td class="sign-space">&nbsp;</td>
    </tr>
    <tr>
        <td><span class="name-line">&nbsp;</span></td>
        <td><span class="name-line">&nbsp;</span></td>
        <td><span class="name-line"><?php echo htmlspecialchars($header['shipped_by']); ?></span></td>
        <td><span class="name-line">&nbsp;</span></td>
    </tr>
</table>

<!-- ====== DISTRIBUSI ====== -->
<div class="distribution">
    Putih : Customer &nbsp;&middot;&nbsp; Merah : Security &nbsp;&middot;&nbsp; Kuning : Gudang
</div>
<div class="footnote">Dicetak: <?php echo $tanggal_cetak; ?> WITA</div>

</div><!-- /.sheet -->
</body>
</html>