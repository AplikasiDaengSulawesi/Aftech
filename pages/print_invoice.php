<?php
require_once '../includes/db.php';
date_default_timezone_set('Asia/Makassar');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die("ID Pengiriman tidak valid.");

// Fetch Header
$stmt = $pdo->prepare("SELECT * FROM outbound_shipments WHERE id = ?");
$stmt->execute([$id]);
$header = $stmt->fetch();

if (!$header) die("Data pengiriman tidak ditemukan.");

// Fetch Details per Batch (agar label_no tidak bercampur antar batch)
$stmtDet = $pdo->prepare("
    SELECT p.id AS production_id, p.item, p.size, p.unit, p.batch,
           p.machine, p.shift,
           b.label_qty AS dus_qty,
           GROUP_CONCAT(d.label_no ORDER BY d.label_no SEPARATOR ',') AS label_nos
    FROM outbound_shipment_batches b
    JOIN production_labels p ON b.production_id = p.id
    LEFT JOIN distributor_shipments d
      ON d.shipment_id = b.shipment_id AND d.production_id = p.id
    WHERE b.shipment_id = ?
    GROUP BY p.id, b.id
    ORDER BY p.item, p.size, p.id
");
$stmtDet->execute([$id]);
$details = $stmtDet->fetchAll();

// Kompak list label_no jadi range, mis. [1,2,3,5,7,8] → "1-3, 5, 7-8"
function compact_label_ranges($csv) {
    if (!$csv) return '-';
    $nums = array_values(array_unique(array_map('intval', explode(',', $csv))));
    sort($nums, SORT_NUMERIC);
    if (!$nums) return '-';
    $ranges = [];
    $start = $prev = $nums[0];
    for ($i = 1; $i < count($nums); $i++) {
        if ($nums[$i] === $prev + 1) {
            $prev = $nums[$i];
            continue;
        }
        $ranges[] = ($start === $prev) ? "$start" : "$start-$prev";
        $start = $prev = $nums[$i];
    }
    $ranges[] = ($start === $prev) ? "$start" : "$start-$prev";
    return implode(', ', $ranges);
}

// 1. Calculate Daily Sequence (Urutan pengiriman hari itu)
$stmtSeq = $pdo->prepare("SELECT COUNT(id) as seq FROM outbound_shipments WHERE shipment_date = ? AND id <= ?");
$stmtSeq->execute([$header['shipment_date'], $id]);
$seq = $stmtSeq->fetchColumn();

// 2. Format Tanggal dan Waktu
$datetime_str = date('dmYHi', strtotime($header['shipped_at']));

// 3. Format Inisial Customer
$name_parts = explode(' ', trim($header['customer_name']));
$initials = '';
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
} else {
    $initials = strtoupper(substr(trim($header['customer_name']), 0, 2));
}

// 4. Generate No Resi (Urutan-TanggalWaktu-TotalDus-Inisial)
$total_dus = $header['total_qty'];
$no_trx = $seq . '-' . $datetime_str . '-' . $total_dus . '-' . $initials;

function tgl_indo($tanggal) {
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

$tanggal_kirim = tgl_indo($header['shipment_date']);
$tanggal_cetak = tgl_indo(date('Y-m-d')) . ' ' . date('H:i:s');

// PAGINATION LOGIC
$items_per_first_page = 15;
$items_per_next_page = 25;
$total_items = count($details);

$pages = [];
if ($total_items <= $items_per_first_page) {
    $pages[] = $details;
} else {
    $pages[] = array_slice($details, 0, $items_per_first_page);
    $remaining = array_slice($details, $items_per_first_page);
    while (count($remaining) > 0) {
        $pages[] = array_slice($remaining, 0, $items_per_next_page);
        $remaining = array_slice($remaining, $items_per_next_page);
    }
}
$total_pages = count($pages);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Pengiriman #<?php echo $no_trx; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #000; margin: 0; padding: 0; background: #eee; }
        
        /* FIX UKURAN KERTAS A4 */
        @page { size: A4 portrait; margin: 0; }
        
        .page { 
            width: 210mm; 
            min-height: 297mm; 
            margin: 20mm auto; 
            padding: 15mm; 
            border: 1px solid #ddd; 
            background: #fff; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            position: relative; 
            box-sizing: border-box;
        }

        table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        table td { padding: 5px; vertical-align: top; }
        .header-table td { padding-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #1A237E; }
        .info-box { background: #f8f9fa; padding: 10px; border-radius: 5px; }
        .items-table th { background: #1A237E !important; color: #fff !important; padding: 10px; text-align: left; font-size: 13px; -webkit-print-color-adjust: exact; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .items-table tr.item:last-child td { border-bottom: 2px solid #1A237E; }
        .total-row td { font-weight: bold; font-size: 16px; padding-top: 15px; }
        .footer-ttd { width: 100%; margin-top: 40px; text-align: center; }
        .footer-ttd td { width: 50%; padding-top: 50px; }
        .ttd-line { border-top: 1px solid #000; display: inline-block; width: 150px; padding-top: 5px; font-weight: bold; }
        
        .continuation-badge { background: #FFC107; color: #000; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; display: inline-block; margin-bottom: 10px; }
        
        /* FOOTER DI POJOK BAWAH */
        .print-footer {
            position: absolute;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }

        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .page { margin: 0; border: none; box-shadow: none; width: 100%; min-height: 297mm; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body onload="window.print()">
    <?php 
    $global_no = 1;
    foreach ($pages as $index => $page_items): 
        $current_page = $index + 1;
    ?>
    <div class="page">
        
        <?php if ($current_page == 1): ?>
        <table class="header-table">
            <tr>
                <td>
                    <div class="title">PT AFTECH MAKASSAR INDONESIA</div>
                    <div>JL.KIMA 15 KAVLING KODE R-4 A1</div>
                </td>
                <td style="text-align: right;">
                    <h2 style="margin: 0; color: #D50000;">SURAT JALAN</h2>
                    <div>No. Resi: <strong>#<?php echo $no_trx; ?></strong></div>
                    <div>Tanggal Kirim: <strong><?php echo $tanggal_kirim; ?></strong></div>
                </td>
            </tr>
        </table>
        
        <table style="margin-bottom: 20px;">
            <tr>
                <td style="width: 50%;">
                    <div class="info-box">
                        <strong>Kirim Ke:</strong><br>
                        <span style="font-size: 16px; font-weight: bold; color: #1A237E;"><?php echo htmlspecialchars($header['customer_name']); ?></span><br>
                        <?php if($header['customer_contact']) echo "Telp: " . htmlspecialchars($header['customer_contact']) . "<br>"; ?>
                        <?php if($header['customer_address']) echo nl2br(htmlspecialchars($header['customer_address'])); ?>
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="info-box" style="display: inline-block; text-align: left; min-width: 200px;">
                        <strong>Informasi Pengirim:</strong><br>
                        Petugas: <?php echo htmlspecialchars($header['shipped_by']); ?><br>
                        Waktu Catat: <?php echo date('H:i', strtotime($header['shipped_at'])); ?> WITA
                    </div>
                </td>
            </tr>
        </table>
        <?php else: ?>
        <!-- Header Kecil untuk Halaman Lanjutan -->
        <div class="continuation-badge">LANJUTAN HALAMAN SEBELUMNYA</div>
        <div style="margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
            <strong style="color: #1A237E; font-size: 16px;">Nota Pengiriman #<?php echo $no_trx; ?></strong><br>
            <small>Kirim Ke: <strong><?php echo htmlspecialchars($header['customer_name']); ?></strong></small>
        </div>
        <?php endif; ?>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">NO</th>
                    <th>NAMA ITEM & UKURAN</th>
                    <th style="text-align: center; width: 90px;">JUMLAH DUS</th>
                    <th style="text-align: center; width: 180px;">NO. LABEL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($page_items as $row): ?>
                <tr class="item">
                    <td><?php echo $global_no++; ?></td>
                    <td>
                        <strong style="font-size: 15px;"><?php echo htmlspecialchars($row['item']); ?> (<?php echo htmlspecialchars($row['size']); ?> <?php echo htmlspecialchars($row['unit']); ?>) | <small style="color: #666; font-weight: 600;"><?php echo strtoupper(htmlspecialchars($row['machine'])); ?></small></strong><br>
                        <small style="color: #888;">Batch: <?php echo htmlspecialchars($row['batch']); ?> &middot; <?php echo htmlspecialchars($row['shift']); ?></small>
                    </td>
                    <td style="text-align: center; font-weight: bold; font-size: 15px; color: #1A237E;">
                        <?php echo $row['dus_qty']; ?> Dus
                    </td>
                    <td style="text-align: center; font-size: 11px; color: #333; font-family: 'Consolas', 'Courier New', monospace;">
                        <?php echo htmlspecialchars(compact_label_ranges($row['label_nos'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if ($current_page == $total_pages): ?>
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;">TOTAL KESELURUHAN DIKIRIM :</td>
                    <td style="text-align: center; color: #D50000; font-size: 18px;"><?php echo $header['total_qty']; ?> Dus</td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($current_page == $total_pages): ?>
        <table class="footer-ttd">
            <tr>
                <td>
                    <div class="ttd-line">Sopir / Kurir</div>
                </td>
                <td>
                    <div class="ttd-line">Admin Gudang</div>
                    <div style="font-size: 12px; margin-top: 5px;"><?php echo htmlspecialchars($header['shipped_by']); ?></div>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <!-- FOOTER HALAMAN -->
        <div class="print-footer">
            <div>Dicetak pada: <?php echo $tanggal_cetak; ?> WITA oleh Sistem AFTECH</div>
            <div style="font-weight: bold; color: #444;">Halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?></div>
        </div>

    </div>
    <?php endforeach; ?>
</body>
</html>