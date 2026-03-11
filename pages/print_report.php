<?php
include '../includes/db.php';
date_default_timezone_set('Asia/Makassar');

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$item_filter = $_GET['item'] ?? '';
$size_filter = $_GET['size'] ?? '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == 'true';
$report_type = $_GET['report_type'] ?? 'rekap';

// --- LOGIKA DATA ---
$where = "WHERE 1=1";
if (!$show_all) { $where .= " AND p.production_date BETWEEN '$start_date' AND '$end_date'"; }
if ($item_filter) { $where .= " AND p.item = '$item_filter'"; }
if ($size_filter && $size_filter !== 'Custom') { $where .= " AND p.size = '$size_filter'"; }

// 1. Summary Totals
$res_prod = $pdo->query("SELECT SUM(copies) FROM production_labels p $where")->fetchColumn();
$total_produced = (int)$res_prod;

$v_where = $show_all ? "WHERE 1=1" : "WHERE pl.production_date BETWEEN '$start_date' AND '$end_date'";
if($item_filter) $v_where .= " AND pl.item = '$item_filter'";
if($size_filter && $size_filter !== 'Custom') $v_where .= " AND pl.size = '$size_filter'";
$total_verified = (int)$pdo->query("SELECT COUNT(*) FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id $v_where")->fetchColumn();

$s_where = $show_all ? "WHERE 1=1" : "WHERE s.shipment_date BETWEEN '$start_date' AND '$end_date'";
if($item_filter) $s_where .= " AND pl.item = '$item_filter'";
$total_shipped = (int)$pdo->query("SELECT COUNT(ds.id) FROM distributor_shipments ds JOIN outbound_shipments s ON ds.shipment_id = s.id JOIN production_labels pl ON ds.production_id = pl.id $s_where")->fetchColumn();

// 2. Details Query
$sql = "";
$report_title = "LAPORAN REKAPITULASI BARANG";

if ($report_type == 'produksi') {
    $report_title = "LAPORAN DETAIL PRODUKSI";
    $sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit, p.copies as produced_qty, p.machine, p.shift, p.operator, p.qc, p.production_time,
                   (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as scanned
            FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
} elseif ($report_type == 'gudang') {
    $report_title = "LAPORAN INVENTORI GUDANG";
    $sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit,
                   (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as verified_qty,
                   (SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id) as shipped_qty
            FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
} elseif ($report_type == 'pengiriman') {
    $report_title = "LAPORAN PENGIRIMAN BARANG";
    $sql = "SELECT s.id as shipment_id, s.shipment_date, s.customer_name, s.shipped_at, s.shipped_by, s.total_qty as total_shipped_qty,
                   GROUP_CONCAT(CONCAT(p.item, ' (', p.size, ' ', p.unit, ')|', (SELECT COUNT(*) FROM distributor_shipments WHERE shipment_id = s.id AND production_id = p.id), '|', p.batch) SEPARATOR ';') as item_summary
            FROM outbound_shipments s
            JOIN outbound_shipment_batches b ON s.id = b.shipment_id
            JOIN production_labels p ON b.production_id = p.id
            " . ($show_all ? "WHERE 1=1" : "WHERE s.shipment_date BETWEEN '$start_date' AND '$end_date'") . "
            " . ($item_filter ? " AND p.item = '$item_filter'" : "") . "
            GROUP BY s.id ORDER BY s.shipment_date DESC, s.id DESC";
} else { // rekap
    $sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit, p.copies as produced_qty,
                   (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) as verified_qty,
                   COALESCE((SELECT COUNT(*) FROM distributor_shipments WHERE production_id = p.id), 0) as shipped_qty
            FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
}

$stmt_details = $pdo->query($sql);
$details = [];
while($row = $stmt_details->fetch()) {
    if ($report_type == 'pengiriman') {
        $ship_id = $row['shipment_id']; $ship_date = $row['shipment_date'];
        $seq = $pdo->query("SELECT COUNT(id) FROM outbound_shipments WHERE shipment_date = '$ship_date' AND id <= $ship_id")->fetchColumn();
        $name_parts = explode(' ', trim($row['customer_name']));
        $initials = (count($name_parts) >= 2) ? strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1)) : strtoupper(substr(trim($row['customer_name']), 0, 2));
        $row['no_resi'] = $seq . '-' . date('dmYHi', strtotime($row['shipped_at'])) . '-' . $row['total_shipped_qty'] . '-' . $initials;
    } else { $row['stock_qty'] = (int)($row['verified_qty'] ?? 0) - (int)($row['shipped_qty'] ?? 0); }
    $details[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $report_title; ?> - AFTECH</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 10px; color: #000; background: #fff; padding: 10px; }
        @page { size: A4 landscape; margin: 10mm; }
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 15px; padding-bottom: 5px; }
        .report-title { text-align: center; text-transform: uppercase; margin-bottom: 10px; }
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .summary-box td { border: 1px solid #000; padding: 6px; text-align: center; width: 25%; }
        .summary-value { font-size: 13px; font-weight: bold; }
        .main-table { width: 100%; border-collapse: collapse; }
        .main-table th { background: #f2f2f2 !important; border: 1px solid #000; padding: 5px; text-transform: uppercase; font-size: 9px; }
        .main-table td { border: 1px solid #000; padding: 4px; vertical-align: middle; }
        
        /* Mini Table Style for nested items */
        .inner-table { width: 100%; border-collapse: collapse; background: #fff; margin: 0; }
        .inner-table th, .inner-table td { border: 1px solid #000 !important; padding: 3px 5px !important; font-size: 9px; }
        .inner-table th { background: #f9f9f9 !important; }

        .signature-area { width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .signature-area td { width: 33%; text-align: center; }
        .sig-name { margin-top: 40px; font-weight: bold; text-decoration: underline; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td><h1 style="margin:0; font-size: 18px;">PT AFTECH MAKASSAR INDONESIA</h1><p style="margin:2px 0;">JL.KIMA 15 KAVLING KODE R-4 A1, MAKASSAR</p></td>
            <td style="text-align: right;"><p style="margin:0;">Dicetak: <?php echo date('d/m/Y H:i'); ?></p><p style="margin:2px 0;">Periode: <strong><?php echo $show_all ? "SEMUA WAKTU" : date('d/m/Y', strtotime($start_date)) . " s/d " . date('d/m/Y', strtotime($end_date)); ?></strong></p></td>
        </tr>
    </table>
    <div class="report-title"><h2><?php echo $report_title; ?></h2><p><?php echo $item_filter ? "Kategori: $item_filter" : "Semua Kategori Produk"; ?></p></div>
    <table class="summary-box">
        <tr>
            <td>Target Produksi<br><span class="summary-value"><?php echo number_format($total_produced, 0, ',', '.'); ?> Dus</span></td>
            <td>Total Verified<br><span class="summary-value"><?php echo number_format($total_verified, 0, ',', '.'); ?> Dus</span></td>
            <td>Total Terkirim<br><span class="summary-value"><?php echo number_format($total_shipped, 0, ',', '.'); ?> Dus</span></td>
            <td>Sisa Stok<br><span class="summary-value" style="color: #D50000;"><?php echo number_format($total_verified - $total_shipped, 0, ',', '.'); ?> Dus</span></td>
        </tr>
    </table>
    <table class="main-table">
        <thead>
            <tr>
                <th style="width:25px;">No</th>
                <?php if ($report_type == 'produksi'): ?>
                    <th style="width:60px;">Batch</th><th style="width:100px;">Tanggal | Waktu</th><th>Nama Item & Ukuran</th><th style="width:60px;">Total</th><th style="width:70px;">Mesin</th><th style="width:50px;">Shift</th><th style="width:60px;">QC Check</th><th>OP | QC</th>
                <?php elseif ($report_type == 'gudang'): ?>
                    <th>Batch | Tanggal</th><th>Nama Item & Ukuran</th><th style="width:80px;">Masuk</th><th style="width:80px;">Terkirim</th><th style="width:80px;">Sisa Stok</th>
                <?php elseif ($report_type == 'pengiriman'): ?>
                    <th style="width:100px;">Waktu Kirim</th>
                    <th style="width:250px;">Customer | No. Resi</th>
                    <th style="padding:0; width:350px;">
                        <table class="inner-table" style="border:none !important; table-layout: fixed; width: 350px;">
                            <tr>
                                <th style="border:none !important; border-right:1px solid #000 !important; width: 180px;">Rincian Item & Ukuran</th>
                                <th style="width:40px; border:none !important; border-right:1px solid #000 !important; text-align:center;">Qty</th>
                                <th style="width:130px; border:none !important; text-align:center;">Batch</th>
                            </tr>
                        </table>
                    </th>
                    <th style="width:50px;">Total</th>
                    <th style="width:120px;">Petugas</th>
                <?php else: ?>
                    <th>Tanggal | Batch</th><th>Nama Item & Ukuran</th><th>Produksi</th><th>Verified</th><th>Terkirim</th><th>Stok</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($details)): ?><tr><td colspan="10" class="text-center">Tidak ada data.</td></tr><?php else: 
                $no = 1; foreach ($details as $row): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <?php if ($report_type == 'produksi'): ?>
                        <td class="text-center">#<?php echo $row['batch']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['production_date'])); ?> | <?php echo $row['production_time']; ?></td>
                        <td><?php echo $row['item']; ?> (<?php echo $row['size']; ?> <?php echo $row['unit']; ?>)</td>
                        <td class="text-center"><?php echo number_format($row['produced_qty'], 0, ',', '.'); ?></td>
                        <td><?php echo $row['machine']; ?></td>
                        <td class="text-center"><?php echo $row['shift']; ?></td>
                        <td class="text-center" style="font-weight:bold;"><?php echo $row['scanned']; ?> / <?php echo $row['produced_qty']; ?></td>
                        <td><?php echo $row['operator']; ?> | <?php echo $row['qc']; ?></td>
                    <?php elseif ($report_type == 'gudang'): ?>
                        <td>#<?php echo $row['batch']; ?> | <?php echo date('d/m/Y', strtotime($row['production_date'])); ?></td>
                        <td><?php echo $row['item']; ?> (<?php echo $row['size']; ?> <?php echo $row['unit']; ?>)</td>
                        <td class="text-center"><?php echo number_format($row['verified_qty'], 0, ',', '.'); ?> Dus</td>
                        <td class="text-center"><?php echo number_format($row['shipped_qty'], 0, ',', '.'); ?> Dus</td>
                        <td class="text-center" style="font-weight:bold;"><?php echo number_format($row['stock_qty'], 0, ',', '.'); ?> Dus</td>
                    <?php elseif ($report_type == 'pengiriman'): ?>
                        <td><?php echo date('d/m/Y', strtotime($row['shipment_date'])); ?> | <?php echo date('H:i', strtotime($row['shipped_at'])); ?></td>
                        <td><strong><?php echo $row['customer_name']; ?></strong><br>Resi: #<?php echo $row['no_resi']; ?></td>
                        <td style="padding:0; width:350px;">
                            <table class="inner-table" style="border:none !important; table-layout: fixed; width: 350px;">
                                <?php 
                                if($row['item_summary']) {
                                    $items = explode(';', $row['item_summary']);
                                    foreach($items as $it) {
                                        $p = explode('|', $it);
                                        echo '<tr>';
                                        echo '<td style="border:none !important; border-right:1px solid #000 !important; border-bottom:1px solid #000 !important; width: 180px;">'.$p[0].'</td>';
                                        echo '<td style="width:40px; text-align:center; border:none !important; border-right:1px solid #000 !important; border-bottom:1px solid #000 !important;">'.$p[1].'</td>';
                                        echo '<td style="width:130px; text-align:center; border:none !important; border-bottom:1px solid #000 !important; word-break: break-all;">#'.$p[2].'</td>';
                                        echo '</tr>';
                                    }
                                }
                                ?>
                            </table>
                        </td>
                        <td class="text-center" style="font-weight:bold;"><?php echo number_format($row['total_shipped_qty'], 0, ',', '.'); ?></td>
                        <td><?php echo $row['shipped_by']; ?></td>
                    <?php else: ?>
                        <td><?php echo date('d/m/Y', strtotime($row['production_date'])); ?> | #<?php echo $row['batch']; ?></td>
                        <td><?php echo $row['item']; ?> (<?php echo $row['size']; ?> <?php echo $row['unit']; ?>)</td>
                        <td class="text-center"><?php echo number_format($row['produced_qty'], 0, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($row['verified_qty'], 0, ',', '.'); ?></td>
                        <td class="text-center"><?php echo number_format($row['shipped_qty'], 0, ',', '.'); ?></td>
                        <td class="text-center" style="font-weight:bold;"><?php echo number_format($row['stock_qty'], 0, ',', '.'); ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <table class="signature-area"><tr><td>Dibuat Oleh,<div class="sig-name">( Admin )</div></td><td>Diperiksa Oleh,<div class="sig-name">( Kepala Gudang )</div></td><td>Mengetahui,<div class="sig-name">( Pimpinan )</div></td></tr></table>
    <script>window.onload = function() { window.print(); }</script>
</body>
</html>