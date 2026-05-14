<?php
// Cetak Surat Jalan format continuous form 9.5x11 inci (dot matrix).
// Nomor surat jalan dialokasikan sekali (lazy) lalu disimpan di outbound_shipments.surat_jalan_no.
// Format nomor: NNN/SJ-AM/{bulan_romawi}/{tahun}, counter reset tiap bulan menurut shipment_date.

session_start();
require_once '../includes/db.php';
date_default_timezone_set('Asia/Makassar');

// === KOP PERUSAHAAN (ubah di sini bila berubah) ===
$company = [
    'name'    => 'PT AFTECH MAKASSAR INDONESIA',
    'address' => 'Jl. Ir. Sutami Kav. SS-23 B Kawasan Industri Makassar',
    'city'    => 'Makassar - Sul. Sel',
    'phone'   => '(0411) 518 419 / 087234652266',
    'email'   => 'ibaftech@gmail.com',
];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die("ID Pengiriman tidak valid.");

// Bulan romawi untuk format nomor
$ROMAN = [1=>'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

// Lazy-allocate surat_jalan_no dalam satu transaksi (idempoten saat cetak ulang)
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT * FROM outbound_shipments WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $header = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        $pdo->rollBack();
        die("Data pengiriman tidak ditemukan.");
    }

    if (empty($header['surat_jalan_no'])) {
        $month = (int)date('n', strtotime($header['shipment_date']));
        $year  = (int)date('Y', strtotime($header['shipment_date']));

        $seqStmt = $pdo->prepare("
            SELECT COALESCE(
                MAX(CAST(SUBSTRING_INDEX(surat_jalan_no, '/', 1) AS UNSIGNED)),
                0
            ) + 1 AS next_seq
            FROM outbound_shipments
            WHERE surat_jalan_no IS NOT NULL
              AND MONTH(shipment_date) = ?
              AND YEAR(shipment_date)  = ?
        ");
        $seqStmt->execute([$month, $year]);
        $nextSeq = (int)$seqStmt->fetchColumn();

        $newNo = sprintf('%03d/SJ-AM/%s/%d', $nextSeq, $ROMAN[$month], $year);

        $upd = $pdo->prepare("UPDATE outbound_shipments SET surat_jalan_no = ? WHERE id = ?");
        $upd->execute([$newNo, $id]);

        // Audit trail — catat penerbitan nomor surat jalan
        $userName = $_SESSION['full_name'] ?? 'Sistem';
        $detail   = "$userName menerbitkan Surat Jalan $newNo untuk " . $header['customer_name'];
        $logStmt  = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('CETAK', ?)");
        $logStmt->execute([$detail]);

        $header['surat_jalan_no'] = $newNo;
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    die("Gagal menyiapkan surat jalan: " . htmlspecialchars($e->getMessage()));
}

// Detail item digabung per (item + size + unit + mesin).
// Beberapa batch produksi yang menghasilkan barang sama di mesin sama akan dijumlahkan,
// meskipun beda tanggal produksi atau penerbitan label.
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
        /* Continuous form 9.5 x 11 inci untuk dot matrix — LANDSCAPE.
           Lebar kertas saat dipasang horizontal = 11in, tinggi = 9.5in.
           Hindari warna & shading: karbon kuning hanya nge-print outline hitam. */
        @page { size: 11in 9.5in; margin: 0.4in 0.45in; }

        html, body { margin: 0; padding: 0; background: #fff; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11pt;
            color: #000;
            line-height: 1.35;
        }
        .sheet {
            width: 10.1in;      /* 11 - margin kiri-kanan */
            margin: 0 auto;
            padding: 0.2in 0;
        }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-table td { vertical-align: top; padding: 0; }
        .kop-name { font-size: 14pt; font-weight: 700; letter-spacing: 0.5px; }
        .kop-meta { font-size: 10.5pt; }
        .doc-title {
            font-size: 16pt;
            font-weight: 700;
            text-decoration: underline;
            letter-spacing: 2px;
            text-align: center;
        }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .meta-table td { padding: 1px 4px; font-size: 11pt; vertical-align: top; }
        .meta-table .lbl { width: 80px; }
        .meta-table .sep { width: 8px; text-align: center; }

        .recipient {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #000;
        }
        .recipient .row { display: flex; }
        .recipient .lbl { width: 70px; }
        .recipient .sep { width: 12px; text-align: center; }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 11pt;
        }
        table.items th, table.items td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.items th {
            font-weight: 700;
            text-align: center;
            background: #fff;
        }
        table.items td.no    { width: 30px;  text-align: center; }
        table.items td.qty   { width: 60px;  text-align: right; }
        table.items td.unit  { width: 70px;  text-align: center; }
        table.items td.isi   { width: 130px; text-align: right; }
        table.items td.name small { font-size: 9.5pt; color: #333; }

        .place-date { text-align: right; margin-top: 14px; }

        table.ttd { width: 100%; border-collapse: collapse; margin-top: 30px; }
        table.ttd td {
            width: 25%;
            text-align: center;
            font-size: 10.5pt;
            vertical-align: top;
            padding: 0 4px;
        }
        table.ttd .role { font-weight: 700; }
        table.ttd .sign-space { height: 60px; }
        table.ttd .name-line {
            border-top: 1px solid #000;
            padding-top: 2px;
            display: inline-block;
            min-width: 90%;
        }

        .distribution {
            margin-top: 18px;
            text-align: center;
            font-size: 9.5pt;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }
        .footnote {
            margin-top: 4px;
            text-align: right;
            font-size: 8.5pt;
            color: #444;
        }

        @media print {
            html, body { background: #fff; }
            .sheet { padding: 0; }
            .no-print { display: none !important; }
        }
        .toolbar {
            position: fixed; top: 8px; right: 12px;
            background: #fff; border: 1px solid #999; padding: 4px 8px;
            font-family: Arial, sans-serif; font-size: 12px;
        }
        .toolbar button {
            cursor: pointer; padding: 4px 10px; margin-left: 4px;
            font-size: 12px; border: 1px solid #555; background: #f3f3f3;
        }
    </style>
</head>
<body onload="window.print()">

<div class="toolbar no-print">
    <button onclick="window.print()">🖨 Cetak Ulang</button>
    <button onclick="window.close()">✕ Tutup</button>
</div>

<div class="sheet">
    <!-- KOP & TITLE -->
    <table class="kop-table">
        <tr>
            <td style="width: 58%;">
                <div class="kop-name"><?php echo htmlspecialchars($company['name']); ?></div>
                <div class="kop-meta"><?php echo htmlspecialchars($company['address']); ?></div>
                <div class="kop-meta"><?php echo htmlspecialchars($company['city']); ?></div>
                <div class="kop-meta">Phone : <?php echo htmlspecialchars($company['phone']); ?></div>
                <div class="kop-meta">Email : <?php echo htmlspecialchars($company['email']); ?></div>
            </td>
            <td style="width: 42%;">
                <div class="doc-title">SURAT JALAN</div>
                <table class="meta-table">
                    <tr>
                        <td class="lbl">No</td>
                        <td class="sep">:</td>
                        <td><strong><?php echo htmlspecialchars($header['surat_jalan_no']); ?></strong></td>
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

    <!-- KEPADA -->
    <div class="recipient">
        <div class="row">
            <div class="lbl">Kepada</div>
            <div class="sep">:</div>
            <div><strong><?php echo htmlspecialchars($header['customer_name']); ?></strong></div>
        </div>
        <div class="row">
            <div class="lbl">Alamat</div>
            <div class="sep">:</div>
            <div><?php echo nl2br(htmlspecialchars($header['customer_address'] ?? '-')); ?></div>
        </div>
        <div class="row">
            <div class="lbl">Telp/Hp</div>
            <div class="sep">:</div>
            <div><?php echo htmlspecialchars($header['customer_contact'] ?? '-'); ?></div>
        </div>
    </div>

    <!-- TABEL ITEM -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Nama Barang</th>
                <th style="width: 60px;">Qty</th>
                <th style="width: 70px;">Item</th>
                <th style="width: 130px;">Jumlah Isi<br><small>(Satuan)</small></th>
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
            // Padding baris kosong supaya tabel rapi (minimal 5 baris terlihat)
            $minRows = 5;
            $pad = max(0, $minRows - count($details));
            for ($i = 0; $i < $pad; $i++):
            ?>
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

    <!-- PLACE & DATE -->
    <div class="place-date">Makassar, <?php echo $tanggal_kirim; ?></div>

    <!-- TTD 4 KOLOM -->
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

    <!-- DISTRIBUSI RANGKAP -->
    <div class="distribution">
        Putih : Customer &nbsp;·&nbsp; Merah : Security &nbsp;·&nbsp; Kuning : Gudang
    </div>
    <div class="footnote">Dicetak: <?php echo $tanggal_cetak; ?> WITA</div>
</div>

</body>
</html>
