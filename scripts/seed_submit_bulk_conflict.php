<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script ini hanya boleh dijalankan via CLI.\n");
    exit(1);
}

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/seed_submit_bulk_conflict_helper.php';

function usage(): void
{
    $msg = <<<TXT
Usage:
  php scripts/seed_submit_bulk_conflict.php seed [--copies=3] [--batch=BATCH] [--item=ITEM] [--size=SIZE] [--unit=UNIT]
  php scripts/seed_submit_bulk_conflict.php conflict --production-id=123 --label-no=2 [--customer-name=NAME]

Modes:
  seed      Membuat batch demo baru dengan label 1..N di warehouse_items.
  conflict  Menandai satu label sebagai sudah terkirim tanpa menghapus stok gudangnya, sehingga submit_bulk akan gagal saat label itu ikut diproses.

TXT;
    fwrite(STDOUT, $msg);
}

function argValue(array $argv, string $key, ?string $default = null): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $key . '=')) {
            return substr($arg, strlen($key) + 1);
        }
    }

    return $default;
}

function printResult(array $payload): void
{
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

function seedDemoBatch(PDO $pdo, array $argv): void
{
    $copies = max(1, (int) (argValue($argv, '--copies', '3') ?? 3));
    $batchArg = argValue($argv, '--batch', null);
    $batch  = $batchArg ?: buildDemoConflictBatchCode();
    $item   = argValue($argv, '--item', 'BOTOL') ?? 'BOTOL';
    $size   = argValue($argv, '--size', '600') ?? '600';
    $unit   = argValue($argv, '--unit', 'ML') ?? 'ML';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO production_labels
                (batch, item, size, unit, machine, shift, quantity, operator, qc, copies, production_date, production_time, device_model)
             VALUES
                (?, ?, ?, ?, 'DEMO MACHINE', 'SHIFT A', '200', 'Demo Operator', 'Demo QC', ?, CURDATE(), CURTIME(), 'Demo Script')"
        );
        $stmt->execute([$batch, $item, $size, $unit, $copies]);
        $productionId = (int) $pdo->lastInsertId();

        $stmtWh = $pdo->prepare(
            "INSERT INTO warehouse_items (production_id, label_no, transferred_by, input_method)
             VALUES (?, ?, 'Demo Script', 'manual')"
        );
        $stmtQueue = $pdo->prepare(
            "INSERT INTO print_queues (production_id, batch, label_no, qr_code)
             VALUES (?, ?, ?, ?)"
        );

        for ($i = 1; $i <= $copies; $i++) {
            $stmtWh->execute([$productionId, $i]);
            $stmtQueue->execute([$productionId, $batch, $i, $i . '-' . $batch]);
        }

        $pdo->prepare(
            "INSERT INTO activity_logs (action, details) VALUES ('DUMMY', ?)"
        )->execute([
            "Seed dummy shipment conflict batch #$batch (production_id=$productionId, copies=$copies)"
        ]);

        $pdo->commit();
        printResult([
            'status' => 'success',
            'mode' => 'seed',
            'production_id' => $productionId,
            'batch' => $batch,
            'copies' => $copies,
            'next_step' => [
                'scan_labels' => 'Scan label 1..N di shipment_scan.php sampai masuk cart.',
                'conflict_command' => 'php scripts/seed_submit_bulk_conflict.php conflict --production-id=' . $productionId . ' --label-no=2',
            ],
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        printResult([
            'status' => 'error',
            'mode' => 'seed',
            'message' => $e->getMessage(),
        ]);
        exit(1);
    }
}

function createConflict(PDO $pdo, array $argv): void
{
    $productionId = (int) (argValue($argv, '--production-id', '0') ?? 0);
    $labelNo      = (int) (argValue($argv, '--label-no', '0') ?? 0);
    $customerName = argValue($argv, '--customer-name', 'DUMMY CONFLICT') ?? 'DUMMY CONFLICT';

    if ($productionId <= 0 || $labelNo <= 0) {
        fwrite(STDERR, "Parameter --production-id dan --label-no wajib diisi.\n");
        exit(1);
    }

    $stmtProd = $pdo->prepare("SELECT id, batch, quantity, item, size, unit FROM production_labels WHERE id = ?");
    $stmtProd->execute([$productionId]);
    $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$prod) {
        printResult([
            'status' => 'error',
            'mode' => 'conflict',
            'message' => "Production ID $productionId tidak ditemukan.",
        ]);
        exit(1);
    }

    $stmtWh = $pdo->prepare("SELECT id FROM warehouse_items WHERE production_id = ? AND label_no = ? LIMIT 1");
    $stmtWh->execute([$productionId, $labelNo]);
    if (!$stmtWh->fetch(PDO::FETCH_ASSOC)) {
        printResult([
            'status' => 'error',
            'mode' => 'conflict',
            'message' => "Label #$labelNo tidak ada di warehouse_items untuk production_id $productionId.",
        ]);
        exit(1);
    }

    $stmtShipped = $pdo->prepare("SELECT id FROM distributor_shipments WHERE production_id = ? AND label_no = ? LIMIT 1");
    $stmtShipped->execute([$productionId, $labelNo]);
    if ($stmtShipped->fetch(PDO::FETCH_ASSOC)) {
        printResult([
            'status' => 'error',
            'mode' => 'conflict',
            'message' => "Label #$labelNo sudah ada di distributor_shipments untuk production_id $productionId.",
        ]);
        exit(1);
    }

    $pdo->beginTransaction();
    try {
        $shipmentDate = date('Y-m-d');
        $totalQty = 1;
        $perUnitQty = (int) $prod['quantity'];

        $stmtHeader = $pdo->prepare(
            "INSERT INTO outbound_shipments
                (customer_name, customer_contact, customer_address, shipment_date, total_qty, shipped_by, total_actual_qty, input_method, surat_jalan_no)
             VALUES
                (?, '', 'DUMMY CONFLICT', ?, ?, 'Demo Script', ?, 'manual', NULL)"
        );
        $stmtHeader->execute([$customerName, $shipmentDate, $totalQty, $perUnitQty]);
        $shipmentId = (int) $pdo->lastInsertId();

        $stmtBatch = $pdo->prepare(
            "INSERT INTO outbound_shipment_batches (shipment_id, production_id, label_qty, unit_qty)
             VALUES (?, ?, 1, ?)"
        );
        $stmtBatch->execute([$shipmentId, $productionId, $perUnitQty]);

        $stmtDetail = $pdo->prepare(
            "INSERT INTO distributor_shipments (shipment_id, production_id, label_no)
             VALUES (?, ?, ?)"
        );
        $stmtDetail->execute([$shipmentId, $productionId, $labelNo]);

        $pdo->prepare(
            "INSERT INTO activity_logs (action, details) VALUES ('DUMMY', ?)"
        )->execute([
            "Created shipment conflict for production_id=$productionId label_no=$labelNo shipment_id=$shipmentId"
        ]);

        $pdo->commit();
        printResult([
            'status' => 'success',
            'mode' => 'conflict',
            'shipment_id' => $shipmentId,
            'production_id' => $productionId,
            'label_no' => $labelNo,
            'message' => 'Sekarang submit_bulk akan gagal kalau label ini sudah terlanjur dipilih di cart.',
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        printResult([
            'status' => 'error',
            'mode' => 'conflict',
            'message' => $e->getMessage(),
        ]);
        exit(1);
    }
}

$mode = $argv[1] ?? '';
if (!in_array($mode, ['seed', 'conflict'], true)) {
    usage();
    exit($mode === '' ? 0 : 1);
}

if ($mode === 'seed') {
    seedDemoBatch($pdo, $argv);
} else {
    createConflict($pdo, $argv);
}
