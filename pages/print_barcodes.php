<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/warehouse_barcode_helper.php';
date_default_timezone_set('Asia/Makassar');

// Pastikan user sudah login agar tidak di-redirect
if (!isset($_SESSION['user_id'])) {
    die("Sesi Anda telah habis. Silakan login kembali.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die("ID Produksi tidak valid.");

$stmt = $pdo->prepare("SELECT * FROM production_labels WHERE id = ?");
$stmt->execute([$id]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) die("Data batch tidak ditemukan.");

$stmtWh = $pdo->prepare("SELECT label_no FROM warehouse_items WHERE production_id = ? ORDER BY label_no ASC");
$stmtWh->execute([$id]);
$warehouseLabels = array_map('intval', $stmtWh->fetchAll(PDO::FETCH_COLUMN));

$stmtShipped = $pdo->prepare("SELECT label_no FROM distributor_shipments WHERE production_id = ? ORDER BY label_no ASC");
$stmtShipped->execute([$id]);
$shippedLabels = array_map('intval', $stmtShipped->fetchAll(PDO::FETCH_COLUMN));

$entries = buildWarehouseBarcodeEntries((int)$batch['copies'], $warehouseLabels, $shippedLabels);

if (empty($entries)) die("Tidak ada label untuk batch ini.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Code Batch <?php echo htmlspecialchars($batch['batch']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eee; margin: 0; padding: 20px; color: #333; }
        .page { background: #fff; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #1A237E; padding-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; color: #1A237E; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px; }
        .qr-card { border: 1px dashed #ccc; padding: 10px; border-radius: 8px; text-align: center; background: #fafafa; break-inside: avoid; }
        .qr-code { display: flex; justify-content: center; margin-bottom: 8px; }
        .qr-text { font-size: 9px; word-break: break-all; font-weight: 600; color: #555; font-family: monospace; }
        .qr-label-no { font-size: 13px; font-weight: 900; color: #D50000; margin-bottom: 5px; }
        .qr-status { display: inline-flex; align-items: center; justify-content: center; margin-bottom: 8px; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; letter-spacing: 0.2px; }
        .qr-status.shipped { background: #e8f5e9; color: #1b5e20; border: 1px solid #c8e6c9; }
        .qr-status.pending { background: #fff8e1; color: #ef6c00; border: 1px solid #ffe0b2; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .page { margin: 0; padding: 5mm; box-shadow: none; width: 100%; min-height: auto; }
            .no-print { display: none !important; }
            .qr-card { page-break-inside: avoid; border: 1px solid #ccc;}
        }
    </style>
    <!-- Load library QR Code via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <div style="margin-bottom: 10px; font-size: 14px; color: #555;">Gunakan tombol di bawah ini untuk mencetak atau menyimpan sebagai file PDF.</div>
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 15px; cursor: pointer; background: #1A237E; color: #fff; border: none; border-radius: 5px; font-weight: bold;">🖨 Cetak / Save PDF</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 15px; cursor: pointer; background: #ccc; color: #333; border: none; border-radius: 5px; font-weight: bold; margin-left: 10px;">✕ Tutup</button>
    </div>
    
    <div class="page">
        <div class="header">
            <h2>QR Code Stok Gudang</h2>
            <div style="font-size: 14px;">Batch: <strong><?php echo htmlspecialchars($batch['batch']); ?></strong> | Item: <strong><?php echo htmlspecialchars($batch['item']); ?></strong></div>
            <div style="font-size: 12px; margin-top: 5px; color: #666;">Total Label Dalam Batch: <?php echo count($entries); ?> Dus</div>
        </div>
        
        <div class="grid">
            <?php foreach ($entries as $entry): 
                $no = $entry['label_no'];
                $qrString = $no . '-' . $batch['batch'];
                $statusClass = $entry['is_shipped'] ? 'shipped' : 'pending';
            ?>
            <div class="qr-card">
                <div class="qr-label-no">DUS #<?php echo $no; ?></div>
                <div class="qr-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($entry['status']); ?></div>
                <div class="qr-code" id="qr-<?php echo $no; ?>"></div>
                <div class="qr-text"><?php echo htmlspecialchars($qrString); ?></div>
            </div>
            
            <script>
                new QRCode(document.getElementById("qr-<?php echo $no; ?>"), {
                    text: "<?php echo addslashes($qrString); ?>",
                    width: 100,
                    height: 100,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.L
                });
            </script>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Otomatis membuka dialog print/save PDF ketika semua QR code selesai dibuat
        setTimeout(() => {
            window.print();
        }, 1200);
    </script>
</body>
</html>
