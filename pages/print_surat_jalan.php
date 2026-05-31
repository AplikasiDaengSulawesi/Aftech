<?php
// Cetak Surat Jalan format continuous form 9.5x11 inci (dot matrix).
// Nomor surat jalan diambil dari database outbound_shipments.surat_jalan_no.

session_start();
require_once '../includes/db.php';
date_default_timezone_set('Asia/Makassar');

// === KOP PERUSAHAAN (ubah di sini bila berubah) ===
$company = [
    'name'    => 'PT AFTECH MAKASSAR INDONESIA',
    'address' => 'Jl. Kima 8 Kav. SS-23 B Kawasan Industri Makassar',
    'city'    => 'Makassar - Sul. Sel',
    'phone'   => '(0411) 518 419 / 082344662266',
    'email'   => 'iloaftech@gmail.com',
];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$auto = isset($_GET['auto']) ? (int)$_GET['auto'] : 0;
if (!$id) die("ID Pengiriman tidak valid.");

try {
    $stmt = $pdo->prepare("SELECT * FROM outbound_shipments WHERE id = ?");
    $stmt->execute([$id]);
    $header = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        die("Data pengiriman tidak ditemukan.");
    }
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
    @page {
        size: 8.5in 5.5in;
        margin: 0.3in 0.1in;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        background: #fff;
        width: 100%;
    }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 9pt;
        color: #000;
        line-height: 1.3;
    }

    .sheet {
        width: 100%;
        max-width: 7.8in;      /* dari 8.8in — lebih sempit untuk hindari area lubang */
        margin: 0 auto;
        padding: 0.1in 0;
    }

    /* --- KOP --- */
    .kop-table {
        width: 100%;
        border-collapse: collapse;
    }
    .kop-table td {
        vertical-align: top;
        padding: 0;
    }
    .kop-name {
        font-size: 11pt;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .kop-meta {
        font-size: 8.5pt;
    }

    /* --- JUDUL DOKUMEN --- */
    .doc-title {
        font-size: 13pt;
        font-weight: 700;
        text-decoration: underline;
        letter-spacing: 2px;
        text-align: center;
    }

    /* --- META NO SURAT --- */
    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }
    .meta-table td {
        padding: 1px 3px;
        font-size: 9pt;
        vertical-align: top;
    }
    .meta-table .lbl { width: 70px; }
    .meta-table .sep { width: 10px; text-align: center; }

    /* --- KEPADA --- */
    .recipient {
        margin-top: 6px;
        padding-top: 5px;
        border-top: 1px dashed #000;
    }
    .recipient .row {
        display: flex;
        margin-bottom: 1px;
    }
    .recipient .lbl { width: 75px; font-size: 9pt; }
    .recipient .sep { width: 14px; text-align: center; font-size: 9pt; }
    .recipient .val { font-size: 9pt; }

    /* --- TABEL ITEM --- */
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        font-size: 9pt;
    }
    table.items th,
    table.items td {
        border: 1px solid #000;
        padding: 3px 5px;
        vertical-align: middle;
    }
    table.items th {
        font-weight: 700;
        text-align: center;
        background: #fff;
    }
    table.items td.no   { width: 35px;  text-align: center; }
    table.items td.qty  { width: 65px;  text-align: right; }
    table.items td.unit { width: 75px;  text-align: center; }
    table.items td.isi  { width: 140px; text-align: right; }
    table.items td.name small { font-size: 8pt; color: #333; }

    /* --- TANGGAL --- */
    .place-date {
        text-align: right;
        margin-top: 8px;
        font-size: 9pt;
    }

    /* --- TTD --- */
    table.ttd {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    table.ttd td {
        width: 25%;
        text-align: center;
        font-size: 9pt;
        vertical-align: top;
        padding: 0 4px;
    }
    table.ttd .role      { font-weight: 700; font-size: 10pt; }
    table.ttd .sign-space { height: 50px; }
    table.ttd .name-line {
        border-top: 1px solid #000;
        padding-top: 2px;
        display: inline-block;
        min-width: 90%;
    }

    /* --- DISTRIBUSI & FOOTNOTE --- */
    .distribution {
        margin-top: 8px;
        text-align: center;
        font-size: 8pt;
        border-top: 1px dashed #000;
        padding-top: 4px;
    }
    .footnote {
        margin-top: 2px;
        text-align: right;
        font-size: 7.5pt;
        color: #444;
    }

    /* --- TOOLBAR (tidak ikut cetak) --- */
    .toolbar {
        position: fixed;
        top: 8px;
        right: 12px;
        background: #fff;
        border: 1px solid #999;
        padding: 4px 8px;
        font-family: Arial, sans-serif;
        font-size: 12px;
        z-index: 9999;
    }
    .toolbar button {
        cursor: pointer;
        padding: 4px 10px;
        margin-left: 4px;
        font-size: 12px;
        border: 1px solid #555;
        background: #f3f3f3;
    }

    /* --- PRINT OVERRIDES --- */
    @media print {
        html, body    { background: #fff; }
        .sheet        { padding: 0; }
        .no-print     { display: none !important; }
    }
    <?php if ($auto): ?>
    .toolbar { display: none !important; }
    <?php endif; ?>
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const btn = document.getElementById('btn-download');
        if(btn) { btn.innerHTML = '⏳ Memproses...'; btn.disabled = true; }

        const element = document.querySelector('.sheet');
        const opt = {
            margin:       0.1,
            filename:     'Surat_Jalan_<?php echo preg_replace("/[^A-Za-z0-9\-]/", "_", $header['surat_jalan_no'] ?? $id); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: [8.5, 5.5], orientation: 'landscape' }
        };
        
        html2pdf().set(opt).from(element).save().then(() => {
            if(btn) { btn.innerHTML = '⬇ Download PDF'; btn.disabled = false; }
            <?php if ($auto): ?>
            if (window.parent) {
                window.parent.postMessage({action: 'pdf_downloaded', id: <?php echo $id; ?>}, '*');
            }
            <?php endif; ?>
        });
    }
</script>
</head>
<body onload="downloadPDF()">

<div class="toolbar no-print">
    <button id="btn-download" onclick="downloadPDF()">⬇ Download PDF</button>
    <button onclick="window.close()">✕ Tutup</button>
</div>

<div class="sheet">

    <!-- ====== KOP & JUDUL ====== -->
    <table class="kop-table">
        <tr>
            <td style="width: 55%;">
                <div class="kop-name"><?php echo htmlspecialchars($company['name']); ?></div>
                <div class="kop-meta"><?php echo htmlspecialchars($company['address']); ?></div>
                <div class="kop-meta"><?php echo htmlspecialchars($company['city']); ?></div>
                <div class="kop-meta">Phone : <?php echo htmlspecialchars($company['phone']); ?></div>
                <div class="kop-meta">Email : <?php echo htmlspecialchars($company['email']); ?></div>
            </td>
            <td style="width: 45%;">
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
                <th style="width:35px;">No</th>
                <th>Nama Barang</th>
                <th style="width:65px;">Qty</th>
                <th style="width:75px;">Item</th>
                <th style="width:140px;">Jumlah Isi<br><small>(Satuan)</small></th>
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
            // Baris kosong minimal 8 baris
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
        Putih : Customer &nbsp;·&nbsp; Merah : Security &nbsp;·&nbsp; Kuning : Gudang
    </div>
    <div class="footnote">Dicetak: <?php echo $tanggal_cetak; ?> WITA</div>

</div><!-- /.sheet -->

</body>
</html>