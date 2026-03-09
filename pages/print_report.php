<?php
include '../includes/db.php';
date_default_timezone_set('Asia/Makassar');

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$item_filter = $_GET['item'] ?? '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == 'true';

// --- LOGIKA DATA (Sama dengan API) ---
$where = "WHERE 1=1";
if (!$show_all) {
    $where .= " AND p.production_date BETWEEN '$start_date' AND '$end_date'";
}
if ($item_filter) {
    $where .= " AND p.item = '$item_filter'";
}

// 1. Summary
$res_prod = $pdo->query("SELECT SUM(quantity * copies) FROM production_labels p $where")->fetchColumn();
$total_produced = (int)$res_prod;

$v_where = $show_all ? "" : "WHERE pl.production_date BETWEEN '$start_date' AND '$end_date'";
if($item_filter) $v_where .= ($v_where ? " AND" : "WHERE") . " pl.item = '$item_filter'";
$total_verified = (int)$pdo->query("SELECT SUM(pl.quantity) FROM warehouse_items w JOIN production_labels pl ON w.production_id = pl.id $v_where")->fetchColumn();

$s_where = $show_all ? "" : "WHERE s.shipment_date BETWEEN '$start_date' AND '$end_date'";
if($item_filter) $s_where .= ($s_where ? " AND" : "WHERE") . " pl.item = '$item_filter'";
$total_shipped = (int)$pdo->query("SELECT SUM(b.unit_qty) FROM outbound_shipment_batches b JOIN outbound_shipments s ON b.shipment_id = s.id JOIN production_labels pl ON b.production_id = pl.id $s_where")->fetchColumn();

// 2. Details
$sql = "SELECT p.production_date, p.batch, p.item, p.size, p.unit, (p.quantity * p.copies) as produced_qty,
               (SELECT COUNT(*) FROM warehouse_items WHERE production_id = p.id) * p.quantity as verified_qty,
               COALESCE((SELECT SUM(unit_qty) FROM outbound_shipment_batches WHERE production_id = p.id), 0) as shipped_qty
        FROM production_labels p $where ORDER BY p.production_date DESC, p.id DESC";
$details = $pdo->query($sql)->fetchAll();

// Labeling
$bulan_indonesia = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
$periode_label = $show_all ? "SEMUA WAKTU" : date('d/m/Y', strtotime($start_date)) . " s/d " . date('d/m/Y', strtotime($end_date));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Rekapitulasi - AFTECH</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; color: #000; background: #fff; padding: 20px; }
        @page { size: A4 landscape; margin: 15mm; }
        
        .header-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 20px; padding-bottom: 10px; }
        .header-table td { vertical-align: top; }
        
        .report-title { text-align: center; text-transform: uppercase; margin-bottom: 20px; }
        .report-title h2 { margin: 0; font-size: 18px; }
        .report-title p { margin: 5px 0; font-size: 13px; font-weight: bold; }

        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-box td { border: 1px solid #000; padding: 10px; text-align: center; width: 25%; }
        .summary-label { font-size: 9px; text-transform: uppercase; color: #444; display: block; margin-bottom: 3px; }
        .summary-value { font-size: 16px; font-weight: bold; }

        .main-table { width: 100%; border-collapse: collapse; }
        .main-table thead { display: table-header-group; } /* Agar header tabel mengulang di setiap halaman */
        .main-table th { background: #eee !important; border: 1px solid #000; padding: 8px 5px; font-size: 11px; text-transform: uppercase; -webkit-print-color-adjust: exact; }
        .main-table td { border: 1px solid #000; padding: 7px 5px; vertical-align: middle; }
        
        .signature-area { width: 100%; margin-top: 50px; page-break-inside: avoid; }
        .signature-area td { width: 33%; text-align: center; height: 100px; vertical-align: top; }
        .sig-name { margin-top: 60px; font-weight: bold; text-decoration: underline; text-transform: uppercase; }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .bg-light { background: #f9f9f9; }
        
        @media print {
            .btn-print { display: none; }
        }
        .btn-print { 
            position: fixed; top: 20px; right: 20px; 
            padding: 10px 20px; background: #1A237E; color: #fff; 
            border: none; border-radius: 5px; cursor: pointer; font-weight: bold;
        }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">CETAK SEKARANG</button>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1 style="margin:0; font-size: 24px;">PT AFTECH MAKASSAR INDONESIA</h1>
                <p style="margin:2px 0;">Sistem Manajemen Produksi & Inventaris Gudang</p>
            </td>
            <td style="text-align: right;">
                <p style="margin:0;">Tanggal Cetak: <?php echo date('d F Y H:i'); ?></p>
                <p style="margin:5px 0 0 0;">Periode: <strong><?php echo $periode_label; ?></strong></p>
            </td>
        </tr>
    </table>

    <div class="report-title">
        <h2>LAPORAN REKAPITULASI BARANG</h2>
        <p><?php echo $item_filter ? "Kategori Produk: $item_filter" : "Seluruh Kategori Produk"; ?></p>
    </div>

    <table class="summary-box">
        <tr>
            <td>
                <span class="summary-label">Total Produksi</span>
                <span class="summary-value"><?php echo number_format($total_produced, 0, ',', '.'); ?></span>
            </td>
            <td>
                <span class="summary-label">Total Terverifikasi</span>
                <span class="summary-value"><?php echo number_format($total_verified, 0, ',', '.'); ?></span>
            </td>
            <td>
                <span class="summary-label">Total Terkirim</span>
                <span class="summary-value"><?php echo number_format($total_shipped, 0, ',', '.'); ?></span>
            </td>
            <td>
                <span class="summary-label">Sisa Stok Akhir</span>
                <span class="summary-value" style="color: #D50000;"><?php echo number_format($total_verified - $total_shipped, 0, ',', '.'); ?></span>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 100px;">Tanggal</th>
                <th style="width: 120px;">Nomor Batch</th>
                <th>Nama Item & Ukuran</th>
                <th style="width: 90px;">Produksi</th>
                <th style="width: 90px;">Verified</th>
                <th style="width: 90px;">Terkirim</th>
                <th style="width: 100px;">Stok Gudang</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($details)): ?>
                <tr><td colspan="8" class="text-center">Tidak ada data yang ditemukan untuk periode ini.</td></tr>
            <?php else: 
                $no = 1;
                foreach ($details as $row): 
                    $stock = (int)$row['verified_qty'] - (int)$row['shipped_qty'];
            ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo date('d/m/Y', strtotime($row['production_date'])); ?></td>
                    <td class="text-center">#<?php echo $row['batch']; ?></td>
                    <td><?php echo $row['item']; ?> (<?php echo $row['size']; ?> <?php echo $row['unit']; ?>)</td>
                    <td class="text-center"><?php echo number_format($row['produced_qty'], 0, ',', '.'); ?></td>
                    <td class="text-center"><?php echo number_format($row['verified_qty'], 0, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $row['shipped_qty'] > 0 ? number_format($row['shipped_qty'], 0, ',', '.') : '-'; ?></td>
                    <td class="text-end" style="font-weight: bold;"><?php echo $stock > 0 ? number_format($stock, 0, ',', '.') : '0'; ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <table class="signature-area">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <div class="sig-name">( Admin Produksi )</div>
            </td>
            <td>
                <p>Diperiksa Oleh,</p>
                <div class="sig-name">( Warehouse Manager )</div>
            </td>
            <td>
                <p>Diketahui Oleh,</p>
                <div class="sig-name">( Pimpinan )</div>
            </td>
        </tr>
    </table>

    <script>
        // Auto trigger print dialog
        window.onload = function() {
            window.print(); 
        }
    </script>
</body>
</html>