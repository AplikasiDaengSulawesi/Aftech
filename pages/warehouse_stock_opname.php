<?php
require_once '../includes/auth_check.php';
protect_page('warehouse');
require_once '../includes/db.php';

function is_admin_user(): bool {
    return (($_SESSION['role'] ?? '') === 'admin');
}

function ensure_stock_opname_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;

    $statements = [
        "CREATE TABLE IF NOT EXISTS `stock_opname_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_code` varchar(30) NOT NULL,
            `session_name` varchar(120) NOT NULL,
            `notes` text DEFAULT NULL,
            `status` enum('open','closed','adjusted') NOT NULL DEFAULT 'open',
            `started_by` varchar(100) DEFAULT NULL,
            `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `finished_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_stock_opname_session_code` (`session_code`),
            KEY `idx_stock_opname_status_started` (`status`,`started_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS `stock_opname_session_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_id` int(11) NOT NULL,
            `production_id` int(11) DEFAULT NULL,
            `warehouse_item_id` int(11) DEFAULT NULL,
            `batch` varchar(100) DEFAULT NULL,
            `label_no` int(11) DEFAULT NULL,
            `barcode_raw` varchar(180) DEFAULT NULL,
            `scan_status` enum('matched','extra','extra_unknown_batch','duplicate_in_session','invalid','missing_in_scan') NOT NULL DEFAULT 'invalid',
            `scanned_by` varchar(100) DEFAULT NULL,
            `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `resolved_action` enum('pending','add_to_stock','mark_damaged','remove_from_stock') NOT NULL DEFAULT 'pending',
            `resolved_at` datetime DEFAULT NULL,
            `resolved_by` varchar(100) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_stock_opname_item_session` (`session_id`),
            KEY `idx_stock_opname_item_lookup` (`session_id`,`batch`,`label_no`),
            KEY `idx_stock_opname_item_status` (`session_id`,`scan_status`,`resolved_action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        "CREATE TABLE IF NOT EXISTS `stock_opname_adjustments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_item_id` int(11) NOT NULL,
            `action_type` enum('add_to_stock','mark_damaged','remove_from_stock') NOT NULL,
            `action_notes` text DEFAULT NULL,
            `acted_by` varchar(100) DEFAULT NULL,
            `acted_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_stock_opname_adjustment_item` (`session_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    $done = true;
}

function json_response(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function esc($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function build_session_code(): string {
    return 'OPN-' . date('Ymd-His') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

function get_session_row(PDO $pdo, int $sessionId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM stock_opname_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function normalize_barcode(string $barcode): array {
    $barcode = trim($barcode);
    if ($barcode === '') {
        return ['ok' => false, 'message' => 'Barcode kosong.'];
    }

    $parts = explode('-', $barcode, 2);
    if (count($parts) < 2) {
        return ['ok' => false, 'message' => 'Format barcode tidak valid. Gunakan No-Batch.'];
    }

    $labelNo = (int)trim($parts[0]);
    $batch = trim($parts[1]);
    if ($labelNo <= 0 || $batch === '') {
        return ['ok' => false, 'message' => 'Nomor label atau batch tidak valid.'];
    }

    return ['ok' => true, 'label_no' => $labelNo, 'batch' => $batch];
}

function save_session_item(PDO $pdo, array $data): int {
    $stmt = $pdo->prepare("
        INSERT INTO stock_opname_session_items
        (session_id, production_id, warehouse_item_id, batch, label_no, barcode_raw, scan_status, scanned_by, notes)
        VALUES (:session_id, :production_id, :warehouse_item_id, :batch, :label_no, :barcode_raw, :scan_status, :scanned_by, :notes)
    ");
    $stmt->execute([
        ':session_id' => $data['session_id'],
        ':production_id' => $data['production_id'],
        ':warehouse_item_id' => $data['warehouse_item_id'],
        ':batch' => $data['batch'],
        ':label_no' => $data['label_no'],
        ':barcode_raw' => $data['barcode_raw'],
        ':scan_status' => $data['scan_status'],
        ':scanned_by' => $data['scanned_by'],
        ':notes' => $data['notes'],
    ]);
    return (int)$pdo->lastInsertId();
}

function fetch_session_items(PDO $pdo, int $sessionId): array {
    $stmt = $pdo->prepare("
        SELECT soi.*, pl.item, pl.size, pl.unit, pl.quantity, pl.copies
        FROM stock_opname_session_items soi
        LEFT JOIN production_labels pl ON pl.id = soi.production_id
        WHERE soi.session_id = ?
        ORDER BY soi.scanned_at DESC, soi.id DESC
    ");
    $stmt->execute([$sessionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_batch_production_map(PDO $pdo, array $batches): array {
    if (!$batches) return [];
    $placeholders = implode(',', array_fill(0, count($batches), '?'));
    $stmt = $pdo->prepare("
        SELECT id, batch, item, size, unit, quantity, copies
        FROM production_labels
        WHERE batch IN ($placeholders)
    ");
    $stmt->execute(array_values($batches));
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[$row['batch']] = $row;
    }
    return $map;
}

function fetch_system_labels(PDO $pdo, array $productionIds): array {
    $sql = "
        SELECT w.id, w.production_id, w.label_no, pl.batch, pl.item, pl.size, pl.unit, pl.quantity, pl.copies
        FROM warehouse_items w
        JOIN production_labels pl ON pl.id = w.production_id
    ";

    if ($productionIds) {
        $placeholders = implode(',', array_fill(0, count($productionIds), '?'));
        $sql .= " WHERE w.production_id IN ($placeholders)";
    }

    $sql .= " ORDER BY pl.batch ASC, w.label_no ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($productionIds ? array_values($productionIds) : []);
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $pid = (int)$row['production_id'];
        if (!isset($map[$pid])) {
            $map[$pid] = [
                'meta' => [
                    'production_id' => $pid,
                    'batch' => $row['batch'],
                    'item' => $row['item'],
                    'size' => $row['size'],
                    'unit' => $row['unit'],
                    'quantity' => (int)$row['quantity'],
                    'copies' => (int)$row['copies'],
                ],
                'labels' => [],
                'warehouse_item_ids' => [],
            ];
        }
        $map[$pid]['labels'][] = (int)$row['label_no'];
        $map[$pid]['warehouse_item_ids'][(int)$row['label_no']] = (int)$row['id'];
    }
    return $map;
}

function build_session_snapshot(PDO $pdo, int $sessionId): array {
    $session = get_session_row($pdo, $sessionId);
    if (!$session) {
        return [];
    }

    $items = fetch_session_items($pdo, $sessionId);
    $batches = [];
    foreach ($items as $item) {
        if (!empty($item['batch'])) $batches[$item['batch']] = $item['batch'];
    }

    $batchMap = fetch_batch_production_map($pdo, $batches);
    $systemMap = fetch_system_labels($pdo, []);

    foreach ($systemMap as $systemRow) {
        $meta = $systemRow['meta'];
        $batchMap[$meta['batch']] = [
            'id' => $meta['production_id'],
            'batch' => $meta['batch'],
            'item' => $meta['item'],
            'size' => $meta['size'],
            'unit' => $meta['unit'],
            'quantity' => $meta['quantity'],
            'copies' => $meta['copies'],
        ];
    }

    foreach ($batchMap as $prod) {
        $pid = (int)$prod['id'];
        if (!isset($systemMap[$pid])) {
            $systemMap += fetch_system_labels($pdo, [$pid]);
        }
    }

    $knownScans = [];
    $unknownBatches = [];
    $duplicates = [];
    $invalidRows = [];
    foreach ($items as $row) {
        $status = $row['scan_status'];
        $batch = (string)($row['batch'] ?? '');
        $labelNo = $row['label_no'] !== null ? (int)$row['label_no'] : null;
        $key = ($batch !== '' && $labelNo !== null) ? $batch . '#' . $labelNo : 'row-' . $row['id'];

        if ($status === 'duplicate_in_session') {
            $duplicates[] = $row;
            continue;
        }
        if ($status === 'invalid') {
            $invalidRows[] = $row;
            continue;
        }
        if ($status === 'extra_unknown_batch') {
            $unknownBatches[] = $row;
            continue;
        }
        if ($status === 'missing_in_scan') {
            continue;
        }
        if (!isset($knownScans[$key])) {
            $knownScans[$key] = $row;
        }
    }

    $batchRows = [];
    foreach ($batchMap as $batch => $prod) {
        $pid = (int)$prod['id'];
        $systemLabels = $systemMap[$pid]['labels'] ?? [];
        $systemSet = array_fill_keys($systemLabels, true);
        $scannedLabels = [];
        $matchedLabels = [];
        $extraLabels = [];

        foreach ($knownScans as $scan) {
            if (($scan['batch'] ?? '') !== $batch) continue;
            $labelNo = (int)$scan['label_no'];
            $scannedLabels[] = $labelNo;
            if (isset($systemSet[$labelNo])) $matchedLabels[] = $labelNo;
            else $extraLabels[] = $labelNo;
        }

        sort($scannedLabels);
        sort($matchedLabels);
        sort($extraLabels);

        $missingLabels = array_values(array_diff($systemLabels, $scannedLabels));
        sort($missingLabels);
        $qtyPerLabel = (int)$prod['quantity'];

        $batchRows[] = [
            'production_id' => $pid,
            'batch' => $batch,
            'item' => $prod['item'],
            'size' => $prod['size'],
            'unit' => $prod['unit'],
            'quantity' => $qtyPerLabel,
            'system_labels_count' => count($systemLabels),
            'scanned_labels_count' => count($scannedLabels),
            'matched_labels_count' => count($matchedLabels),
            'extra_labels_count' => count($extraLabels),
            'missing_labels_count' => count($missingLabels),
            'difference_labels_count' => count($scannedLabels) - count($systemLabels),
            'system_units' => count($systemLabels) * $qtyPerLabel,
            'scanned_units' => count($scannedLabels) * $qtyPerLabel,
            'system_labels' => $systemLabels,
            'scanned_labels' => $scannedLabels,
            'matched_labels' => $matchedLabels,
            'extra_labels' => $extraLabels,
            'missing_labels' => $missingLabels,
        ];
    }

    usort($batchRows, fn($a, $b) => strcmp($a['batch'], $b['batch']));

    $summary = [
        'system_batches' => count($batchRows),
        'system_labels' => array_sum(array_column($batchRows, 'system_labels_count')),
        'system_units' => array_sum(array_column($batchRows, 'system_units')),
        'scanned_batches' => count(array_filter($batchRows, fn($row) => $row['scanned_labels_count'] > 0)) + count(array_unique(array_map(fn($r) => $r['batch'] ?? '', $unknownBatches))),
        'scanned_labels' => array_sum(array_column($batchRows, 'scanned_labels_count')) + count($unknownBatches),
        'scanned_units' => array_sum(array_column($batchRows, 'scanned_units')),
        'matched_labels' => array_sum(array_column($batchRows, 'matched_labels_count')),
        'extra_labels' => array_sum(array_column($batchRows, 'extra_labels_count')) + count($unknownBatches),
        'missing_labels' => array_sum(array_column($batchRows, 'missing_labels_count')),
        'duplicates' => count($duplicates),
        'invalid' => count($invalidRows),
    ];

    return [
        'session' => $session,
        'summary' => $summary,
        'batches' => $batchRows,
        'unknown_batches' => $unknownBatches,
        'duplicates' => $duplicates,
        'invalid_rows' => $invalidRows,
        'session_items' => $items,
    ];
}

function log_activity(PDO $pdo, string $action, string $details): void {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
    $stmt->execute([$action, $details]);
}

ensure_stock_opname_schema($pdo);

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    try {
        if ($action === 'create_session' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['session_name'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            if ($name === '') {
                json_response(['status' => 'error', 'message' => 'Nama sesi wajib diisi.']);
            }

            $stmt = $pdo->prepare("
                INSERT INTO stock_opname_sessions (session_code, session_name, notes, started_by)
                VALUES (?, ?, ?, ?)
            ");
            $code = build_session_code();
            $stmt->execute([$code, $name, $notes ?: null, $_SESSION['full_name'] ?? 'User']);
            $sessionId = (int)$pdo->lastInsertId();
            log_activity($pdo, 'OPNAME', "Membuat sesi stock opname $code - $name");
            json_response(['status' => 'success', 'session_id' => $sessionId]);
        }

        if ($action === 'get_snapshot') {
            $sessionId = (int)($_GET['session_id'] ?? 0);
            $snapshot = build_session_snapshot($pdo, $sessionId);
            if (!$snapshot) json_response(['status' => 'error', 'message' => 'Sesi tidak ditemukan.']);
            json_response(['status' => 'success', 'data' => $snapshot]);
        }

        if ($action === 'scan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $barcode = trim($_POST['barcode'] ?? '');
            $session = get_session_row($pdo, $sessionId);
            if (!$session) json_response(['status' => 'error', 'message' => 'Sesi tidak ditemukan.']);
            if ($session['status'] !== 'open') json_response(['status' => 'error', 'message' => 'Sesi sudah ditutup.']);

            $parsed = normalize_barcode($barcode);
            if (!$parsed['ok']) {
                save_session_item($pdo, [
                    'session_id' => $sessionId,
                    'production_id' => null,
                    'warehouse_item_id' => null,
                    'batch' => null,
                    'label_no' => null,
                    'barcode_raw' => $barcode,
                    'scan_status' => 'invalid',
                    'scanned_by' => $_SESSION['full_name'] ?? 'User',
                    'notes' => $parsed['message'],
                ]);
                json_response(['status' => 'error', 'message' => $parsed['message']]);
            }

            $labelNo = (int)$parsed['label_no'];
            $batch = $parsed['batch'];

            $stmtProd = $pdo->prepare("SELECT id, batch, item, size, unit, quantity, copies FROM production_labels WHERE batch = ? LIMIT 1");
            $stmtProd->execute([$batch]);
            $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

            $status = 'invalid';
            $message = '';
            $productionId = null;
            $warehouseItemId = null;
            $notes = null;

            if (!$prod) {
                $status = 'extra_unknown_batch';
                $message = "Batch $batch tidak terdaftar, disimpan sebagai selisih lebih.";
                $notes = 'Batch tidak terdaftar di production_labels.';
            } else {
                $productionId = (int)$prod['id'];
                if ($labelNo < 1 || $labelNo > (int)$prod['copies']) {
                    $status = 'invalid';
                    $message = "Label #$labelNo di luar kuota batch.";
                    $notes = 'Nomor label di luar jangkauan copies.';
                } else {
                    $stmtDup = $pdo->prepare("
                        SELECT id FROM stock_opname_session_items
                        WHERE session_id = ? AND batch = ? AND label_no = ?
                          AND scan_status IN ('matched','extra','extra_unknown_batch','duplicate_in_session')
                        LIMIT 1
                    ");
                    $stmtDup->execute([$sessionId, $batch, $labelNo]);
                    $dup = $stmtDup->fetch(PDO::FETCH_ASSOC);

                    if ($dup) {
                        $status = 'duplicate_in_session';
                        $message = "Label #$labelNo batch $batch sudah pernah discan di sesi ini.";
                        $notes = 'Scan duplikat dalam sesi yang sama.';
                    } else {
                        $stmtWh = $pdo->prepare("SELECT id FROM warehouse_items WHERE production_id = ? AND label_no = ? LIMIT 1");
                        $stmtWh->execute([$productionId, $labelNo]);
                        $wh = $stmtWh->fetch(PDO::FETCH_ASSOC);
                        if ($wh) {
                            $status = 'matched';
                            $warehouseItemId = (int)$wh['id'];
                            $message = "Label #$labelNo batch $batch cocok dengan stok sistem.";
                        } else {
                            $status = 'extra';
                            $message = "Label #$labelNo batch $batch tidak ada di stok sistem, disimpan sebagai selisih lebih.";
                            $notes = 'Label valid tetapi tidak ada di warehouse_items.';
                        }
                    }
                }
            }

            save_session_item($pdo, [
                'session_id' => $sessionId,
                'production_id' => $productionId,
                'warehouse_item_id' => $warehouseItemId,
                'batch' => $batch,
                'label_no' => $labelNo,
                'barcode_raw' => $barcode,
                'scan_status' => $status,
                'scanned_by' => $_SESSION['full_name'] ?? 'User',
                'notes' => $notes,
            ]);

            json_response([
                'status' => in_array($status, ['matched', 'extra', 'extra_unknown_batch'], true) ? 'success' : 'error',
                'scan_status' => $status,
                'message' => $message,
                'data' => build_session_snapshot($pdo, $sessionId),
            ]);
        }

        if ($action === 'change_session_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $sessionId = (int)($_POST['session_id'] ?? 0);
            $targetStatus = trim($_POST['target_status'] ?? '');
            $session = get_session_row($pdo, $sessionId);
            if (!$session) json_response(['status' => 'error', 'message' => 'Sesi tidak ditemukan.']);
            if (!in_array($targetStatus, ['open', 'closed', 'adjusted'], true)) {
                json_response(['status' => 'error', 'message' => 'Status tidak valid.']);
            }
            if (($targetStatus === 'open' || $targetStatus === 'adjusted') && !is_admin_user()) {
                json_response(['status' => 'error', 'message' => 'Hanya admin yang boleh mengubah status ini.']);
            }

            $stmt = $pdo->prepare("UPDATE stock_opname_sessions SET status = ?, finished_at = ? WHERE id = ?");
            $finishedAt = $targetStatus === 'open' ? null : date('Y-m-d H:i:s');
            $stmt->execute([$targetStatus, $finishedAt, $sessionId]);
            log_activity($pdo, 'OPNAME', "Mengubah status sesi {$session['session_code']} menjadi $targetStatus");
            json_response(['status' => 'success']);
        }

        if ($action === 'extend_batch_copies' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_admin_user()) {
        json_response(['status' => 'error', 'message' => 'Hanya admin yang dapat memperluas kuota batch.']);
    }

    $sessionId  = (int)($_POST['session_id'] ?? 0);
    $barcodeRaw = trim($_POST['barcode_raw'] ?? '');
    $newCopies  = (int)($_POST['new_copies'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');

    $parsed = normalize_barcode($barcodeRaw);
    if (!$parsed['ok']) {
        json_response(['status' => 'error', 'message' => $parsed['message']]);
    }

    $labelNo = (int)$parsed['label_no'];
    $batch   = $parsed['batch'];

    $stmtProd = $pdo->prepare(
        "SELECT id, batch, item, copies FROM production_labels WHERE batch = ? LIMIT 1"
    );
    $stmtProd->execute([$batch]);
    $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$prod) {
        json_response(['status' => 'error', 'message' => "Batch $batch tidak ditemukan."]);
    }
    if ($newCopies <= (int)$prod['copies']) {
        json_response([
            'status'  => 'error',
            'message' => "Kuota baru ($newCopies) harus lebih besar dari kuota saat ini ({$prod['copies']}).",
        ]);
    }
    if ($labelNo > $newCopies) {
        json_response([
            'status'  => 'error',
            'message' => "Label #$labelNo masih di luar kuota baru ($newCopies).",
        ]);
    }

    $session = get_session_row($pdo, $sessionId);
    if (!$session || $session['status'] !== 'open') {
        json_response(['status' => 'error', 'message' => 'Sesi tidak ditemukan atau sudah ditutup.']);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE production_labels SET copies = ? WHERE id = ?")
            ->execute([$newCopies, (int)$prod['id']]);

        // Hapus entri invalid lama untuk barcode ini di sesi ini
        $pdo->prepare("
            DELETE FROM stock_opname_session_items
            WHERE session_id = ? AND barcode_raw = ? AND scan_status = 'invalid'
        ")->execute([$sessionId, $barcodeRaw]);

        // Re-scan dengan kuota baru
        $stmtWh = $pdo->prepare(
            "SELECT id FROM warehouse_items WHERE production_id = ? AND label_no = ? LIMIT 1"
        );
        $stmtWh->execute([(int)$prod['id'], $labelNo]);
        $wh = $stmtWh->fetch(PDO::FETCH_ASSOC);

        $scanStatus     = $wh ? 'matched' : 'extra';
        $warehouseItemId = $wh ? (int)$wh['id'] : null;

        save_session_item($pdo, [
            'session_id'       => $sessionId,
            'production_id'    => (int)$prod['id'],
            'warehouse_item_id'=> $warehouseItemId,
            'batch'            => $batch,
            'label_no'         => $labelNo,
            'barcode_raw'      => $barcodeRaw,
            'scan_status'      => $scanStatus,
            'scanned_by'       => $_SESSION['full_name'] ?? 'Admin',
            'notes'            => "[EXTEND COPIES $newCopies] $notes",
        ]);

        log_activity($pdo, 'OPNAME',
            "Extend copies batch $batch dari {$prod['copies']} → $newCopies, label #$labelNo diterima ($scanStatus)."
        );

        $pdo->commit();
        json_response([
            'status'  => 'success',
            'message' => "Kuota batch $batch diperbarui ke $newCopies. Label #$labelNo diterima sebagai $scanStatus.",
            'data'    => build_session_snapshot($pdo, $sessionId),
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

        if ($action === 'adjust_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_admin_user()) {
                json_response(['status' => 'error', 'message' => 'Hanya admin yang dapat melakukan penyesuaian.']);
            }

            $sessionId = (int)($_POST['session_id'] ?? 0);
            $actionType = trim($_POST['action_type'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $session = get_session_row($pdo, $sessionId);
            if (!$session) json_response(['status' => 'error', 'message' => 'Sesi tidak ditemukan.']);

            $pdo->beginTransaction();
            try {
                if ($actionType === 'remove_from_stock') {
                    $productionId = (int)($_POST['production_id'] ?? 0);
                    $labelNo = (int)($_POST['label_no'] ?? 0);
                    $batch = trim($_POST['batch'] ?? '');
                    if ($productionId <= 0 || $labelNo <= 0) {
                        throw new RuntimeException('Label stok yang akan dihapus tidak lengkap.');
                    }

                    $stmtWh = $pdo->prepare("SELECT id FROM warehouse_items WHERE production_id = ? AND label_no = ? LIMIT 1");
                    $stmtWh->execute([$productionId, $labelNo]);
                    $wh = $stmtWh->fetch(PDO::FETCH_ASSOC);
                    if (!$wh) {
                        throw new RuntimeException('Label sudah tidak ada di stok sistem.');
                    }

                    $stmtFind = $pdo->prepare("
                        SELECT id FROM stock_opname_session_items
                        WHERE session_id = ? AND production_id = ? AND label_no = ? AND scan_status = 'missing_in_scan'
                        LIMIT 1
                    ");
                    $stmtFind->execute([$sessionId, $productionId, $labelNo]);
                    $sessionItemId = (int)($stmtFind->fetchColumn() ?: 0);

                    if ($sessionItemId <= 0) {
                        $sessionItemId = save_session_item($pdo, [
                            'session_id' => $sessionId,
                            'production_id' => $productionId,
                            'warehouse_item_id' => (int)$wh['id'],
                            'batch' => $batch,
                            'label_no' => $labelNo,
                            'barcode_raw' => null,
                            'scan_status' => 'missing_in_scan',
                            'scanned_by' => $_SESSION['full_name'] ?? 'Admin',
                            'notes' => 'Dibuat otomatis untuk aksi hapus stok dari selisih kurang.',
                        ]);
                    }

                    $pdo->prepare("DELETE FROM warehouse_items WHERE id = ?")->execute([(int)$wh['id']]);
                    $pdo->prepare("
                        UPDATE stock_opname_session_items
                        SET resolved_action = 'remove_from_stock', resolved_at = NOW(), resolved_by = ?, notes = CONCAT(COALESCE(notes,''), ?)
                        WHERE id = ?
                    ")->execute([$_SESSION['full_name'] ?? 'Admin', "\n[REMOVE] " . $notes, $sessionItemId]);
                    $pdo->prepare("
                        INSERT INTO stock_opname_adjustments (session_item_id, action_type, action_notes, acted_by)
                        VALUES (?, 'remove_from_stock', ?, ?)
                    ")->execute([$sessionItemId, $notes ?: 'Dihapus dari stok karena tidak ditemukan saat opname.', $_SESSION['full_name'] ?? 'Admin']);

                    log_activity($pdo, 'OPNAME', "Hapus stok batch #$batch label #$labelNo dari hasil opname.");
                } else {
                    $sessionItemId = (int)($_POST['session_item_id'] ?? 0);
                    $stmt = $pdo->prepare("SELECT * FROM stock_opname_session_items WHERE id = ? AND session_id = ? LIMIT 1");
                    $stmt->execute([$sessionItemId, $sessionId]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$item) throw new RuntimeException('Data item sesi tidak ditemukan.');

                    $productionId = (int)($item['production_id'] ?? 0);
                    $labelNo = (int)($item['label_no'] ?? 0);
                    $batch = (string)($item['batch'] ?? '');
                    if ($productionId <= 0 || $labelNo <= 0) {
                        throw new RuntimeException('Item sesi tidak punya referensi label yang valid.');
                    }

                    if ($actionType === 'add_to_stock') {
                        $stmtWh = $pdo->prepare("SELECT id FROM warehouse_items WHERE production_id = ? AND label_no = ? LIMIT 1");
                        $stmtWh->execute([$productionId, $labelNo]);
                        if ($stmtWh->fetch(PDO::FETCH_ASSOC)) {
                            throw new RuntimeException('Label sudah ada di stok sistem.');
                        }

                        $pdo->prepare("
                            INSERT INTO warehouse_items (production_id, label_no, transferred_by, input_method)
                            VALUES (?, ?, ?, 'manual')
                        ")->execute([$productionId, $labelNo, $_SESSION['full_name'] ?? 'Admin']);
                    } elseif ($actionType === 'mark_damaged') {
                        $pdo->prepare("
                            INSERT INTO cancelled_labels (production_id, label_no, category, reason, cancelled_by)
                            VALUES (?, ?, 'warehouse', ?, ?)
                            ON DUPLICATE KEY UPDATE
                                category = VALUES(category),
                                reason = VALUES(reason),
                                cancelled_by = VALUES(cancelled_by),
                                cancelled_at = CURRENT_TIMESTAMP()
                        ")->execute([$productionId, $labelNo, $notes ?: 'Rusak saat stock opname', $_SESSION['full_name'] ?? 'Admin']);
                    } else {
                        throw new RuntimeException('Aksi penyesuaian tidak dikenali.');
                    }

                    $pdo->prepare("
                        UPDATE stock_opname_session_items
                        SET resolved_action = ?, resolved_at = NOW(), resolved_by = ?, notes = CONCAT(COALESCE(notes,''), ?)
                        WHERE id = ?
                    ")->execute([$actionType, $_SESSION['full_name'] ?? 'Admin', "\n[" . strtoupper($actionType) . "] " . $notes, $sessionItemId]);
                    $pdo->prepare("
                        INSERT INTO stock_opname_adjustments (session_item_id, action_type, action_notes, acted_by)
                        VALUES (?, ?, ?, ?)
                    ")->execute([$sessionItemId, $actionType, $notes, $_SESSION['full_name'] ?? 'Admin']);
                    log_activity($pdo, 'OPNAME', ucfirst(str_replace('_', ' ', $actionType)) . " batch #$batch label #$labelNo.");
                }

                $pdo->commit();
                json_response(['status' => 'success', 'data' => build_session_snapshot($pdo, $sessionId)]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                json_response(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    } catch (Throwable $e) {
        json_response(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

$openSessions = $pdo->query("
    SELECT id, session_code, session_name, status, started_by, started_at
    FROM stock_opname_sessions
    ORDER BY FIELD(status, 'open', 'closed', 'adjusted'), started_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

$defaultSessionId = 0;
foreach ($openSessions as $sess) {
    if ($sess['status'] === 'open') {
        $defaultSessionId = (int)$sess['id'];
        break;
    }
}
if ($defaultSessionId === 0 && !empty($openSessions)) {
    $defaultSessionId = (int)$openSessions[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../includes/header.php'; ?>
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
    .scanner-shell { position: sticky; top: 90px; }
    .opname-card, .summary-card, .batch-card { border-radius: 18px; border: none; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); }
    .opname-title { font-weight: 800; color: #111827; }
    .summary-card { min-height: 145px; overflow: hidden; position: relative; }
    .summary-card.system { background: linear-gradient(135deg, #1A237E, #3F51B5); color: #fff; }
    .summary-card.session { background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff; }
    .summary-card .meta-label { opacity: 0.8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; }
    .summary-card .meta-value { font-size: 28px; font-weight: 800; line-height: 1; }
    .summary-card .sub-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 18px; }
    .summary-card .sub-box { padding: 10px 12px; border-radius: 12px; background: rgba(255,255,255,0.14); }
    .scanner-box { border: 2px dashed rgba(26,35,126,0.25); border-radius: 18px; padding: 14px; background: #f8fafc; }
    #reader { width: 100%; min-height: 260px; border-radius: 14px; overflow: hidden; background: #000; }
    #reader video { width: 100% !important; object-fit: cover !important; }
    #cameraSelect { font-size: 12px; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; }
    .camera-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .status-panel { border-radius: 14px; border: 1px solid #e5e7eb; padding: 14px; display: flex; gap: 12px; align-items: center; background: #fff; }
    .status-panel.success { border-color: #bbf7d0; background: #f0fdf4; }
    .status-panel.error { border-color: #fecaca; background: #fef2f2; }
    .status-panel.info { border-color: #c7d2fe; background: #eef2ff; }
    .batch-table td, .batch-table th { vertical-align: middle; }
    .batch-table tbody tr:hover { background: #f8faff; }
    .label-chip { display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; margin: 2px; border: 1px solid transparent; }
    .label-chip.match { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
    .label-chip.extra { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    .label-chip.missing { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
    .label-chip.unknown { background: #faf5ff; border-color: #e9d5ff; color: #7c3aed; }
    .mini-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
    .mini-badge.open { background: #dcfce7; color: #166534; }
    .mini-badge.closed { background: #fee2e2; color: #991b1b; }
    .mini-badge.adjusted { background: #dbeafe; color: #1d4ed8; }
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
    .detail-box { border: 1px solid #e5e7eb; border-radius: 14px; padding: 14px; background: #fff; }
    .detail-box h6 { font-weight: 800; margin-bottom: 10px; }
    .session-toolbar { display: flex; flex-wrap: wrap; gap: 10px; }
    .metric-inline { display: flex; flex-wrap: wrap; gap: 8px; }
    .unknown-list .list-group-item { border: 0; border-bottom: 1px solid #f1f5f9; }
    .batch-label-preview { padding: 14px 18px 16px; background: #fbfdff; border-top: 1px dashed #e2e8f0; }
    .preview-section { margin-bottom: 10px; }
    .preview-section:last-child { margin-bottom: 0; }
    .preview-title { font-size: 11px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 6px; color: #64748b; }
    .load-more-wrap { display: flex; justify-content: center; margin-top: 18px; }
    @media (max-width: 991px) { .scanner-shell { position: static; } }
</style>
<body>
<div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
<div id="main-wrapper">
    <?php include '../includes/navbar.php' ?>
    <?php include '../includes/sidebar.php' ?>
    <div class="content-body">
        <div class="container-fluid">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h3 class="opname-title mb-1">Stock Opname Gudang</h3>
                    <p class="text-muted mb-0">Scan barcode per sesi, bandingkan stok sistem dengan hasil scan, lalu selesaikan selisih oleh admin.</p>
                </div>
                <div class="session-toolbar">
                    <a href="warehouse_inventory.php" class="btn btn-light shadow-sm"><i class="fa fa-arrow-left me-2 text-primary"></i>Kembali ke Stock Gudang</a>
                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSession">
                        <i class="fa fa-plus-circle me-2"></i>Buat Sesi
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-4 col-lg-5 mb-4">
                    <div class="scanner-shell">
                        <div class="card opname-card">
                            <div class="card-header border-0 pb-0">
                                <h4 class="card-title text-black mb-1">Scanner Sesi Aktif</h4>
                                <p class="small text-muted mb-0">Pilih sesi dulu, lalu scan barcode label gudang.</p>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label small font-w700 text-black">Sesi Stock Opname</label>
                                    <select id="sessionSelect" class="form-control default-select">
                                        <option value="">Pilih sesi...</option>
                                        <?php foreach ($openSessions as $session): ?>
                                            <option value="<?= (int)$session['id'] ?>" <?= $defaultSessionId === (int)$session['id'] ? 'selected' : '' ?>>
                                                <?= esc($session['session_code']) ?> - <?= esc($session['session_name']) ?> (<?= esc($session['status']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="scanner-box">
                                    <div id="reader"></div>
                                    <div class="mt-3">
                                        <select id="cameraSelect" class="form-control" onchange="switchCamera(this.value)">
                                            <option value="">Pilih kamera...</option>
                                        </select>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="initCamera()"><i class="fa fa-camera me-2"></i>Aktifkan Kamera</button>
                                        </div>
                                        <div class="camera-actions mt-2">
                                            <button type="button" class="btn btn-outline-warning btn-sm" id="torchToggleBtn" onclick="toggleTorch()" disabled>
                                                <i class="fa fa-lightbulb me-2"></i>Aktifkan Flash
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="resumeScanner()"><i class="fa fa-redo me-2"></i>Resume Scanner</button>
                                        </div>
                                    </div>
                                </div>

                                <div id="scanStatus" class="status-panel info mt-3">
                                    <div><i class="fa fa-qrcode fa-2x text-primary"></i></div>
                                    <div>
                                        <div class="font-w700 text-black">Siap Scan</div>
                                        <small class="text-muted">Arahkan kamera ke barcode label.</small>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label small font-w700 text-black">Input Manual Barcode</label>
                                    <div class="input-group">
                                        <input type="text" id="manualBarcode" class="form-control" placeholder="Contoh: 12-BATCH-ABC">
                                        <button class="btn btn-outline-primary" type="button" onclick="submitManualScan()"><i class="fa fa-check"></i></button>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="changeSessionStatus('closed')">Selesai Scan</button>
                                    <?php if (is_admin_user()): ?>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="changeSessionStatus('open')">Buka Lagi</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="changeSessionStatus('adjusted')">Tandai Adjusted</button>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 font-w800 text-black">Riwayat Error Cepat</h6>
                                        <span class="small text-muted" id="sessionBadgeText">Belum ada sesi dipilih</span>
                                    </div>
                                    <div id="quickIssues" class="small text-muted">Belum ada scan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8 col-lg-7">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card summary-card system">
                                <div class="card-body">
                                    <div class="meta-label">Stok Sistem Gudang</div>
                                    <div class="meta-value" id="systemLabelsCount">0</div>
                                    <div class="small mt-1">Label aktif di batch yang tersentuh sesi ini</div>
                                    <div class="sub-grid">
                                        <div class="sub-box">
                                            <div class="meta-label">Batch Sistem</div>
                                            <div class="h4 mb-0" id="systemBatchCount">0</div>
                                        </div>
                                        <div class="sub-box">
                                            <div class="meta-label">Total Unit</div>
                                            <div class="h4 mb-0" id="systemUnitsCount">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card summary-card session">
                                <div class="card-body">
                                    <div class="meta-label">Hasil Scan Sesi</div>
                                    <div class="meta-value" id="scannedLabelsCount">0</div>
                                    <div class="small mt-1">Label unik dari hasil scan sesi ini</div>
                                    <div class="sub-grid">
                                        <div class="sub-box">
                                            <div class="meta-label">Selisih Lebih</div>
                                            <div class="h4 mb-0" id="extraLabelsCount">0</div>
                                        </div>
                                        <div class="sub-box">
                                            <div class="meta-label">Selisih Kurang</div>
                                            <div class="h4 mb-0" id="missingLabelsCount">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card batch-card mb-4">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title text-black mb-1">Ringkasan Per Batch</h4>
                                <p class="small text-muted mb-0">Bandingkan stok gudang dengan hasil scan untuk setiap batch.</p>
                            </div>
                            <div class="metric-inline" id="toplineMetrics"></div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table batch-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Batch</th>
                                            <th>Item / Size</th>
                                            <th class="text-center">Stok Sistem</th>
                                            <th class="text-center">Hasil Scan</th>
                                            <th class="text-center">Selisih</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="batchTableBody">
                                        <tr><td colspan="6" class="text-center py-5 text-muted">Pilih sesi untuk memulai.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="load-more-wrap">
                                <button type="button" id="loadMoreBatchesBtn" class="btn btn-outline-primary btn-sm d-none" onclick="loadMoreBatches()">
                                    <i class="fa fa-plus-circle me-2"></i>Load More
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card batch-card h-100">
                                <div class="card-header border-0 pb-0">
                                    <h4 class="card-title text-black mb-1">Batch Tidak Terdaftar</h4>
                                    <p class="small text-muted mb-0">Tetap disimpan sebagai selisih lebih untuk direview admin.</p>
                                </div>
                                <div class="card-body">
                                    <div id="unknownBatchArea" class="unknown-list text-muted">Belum ada batch tidak terdaftar.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card batch-card h-100">
                                <div class="card-header border-0 pb-0">
                                    <h4 class="card-title text-black mb-1">Scan Ganda / Invalid</h4>
                                    <p class="small text-muted mb-0">Tidak dihitung ke total hasil scan bersih.</p>
                                </div>
                                <div class="card-body">
                                    <div id="issueArea" class="text-muted">Belum ada isu scan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal: Buat Sesi -->
<div class="modal fade" id="modalCreateSession" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <form id="createSessionForm">
                <div class="modal-header border-0 text-white" style="background:#1A237E;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa fa-plus-circle"></i>
                        <h5 class="modal-title mb-0">Buat Sesi Stock Opname</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-black">Nama Sesi</label>
                        <input type="text" name="session_name" class="form-control" placeholder="Contoh: OPNAME GUDANG SHIFT PAGI" required>
                    </div>
                    <div>
                        <label class="form-label small fw-bold text-black">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary flex-fill fw-bold">
                        <i class="fa fa-save me-2"></i>Simpan Sesi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Extend Copies -->
<div class="modal fade" id="modalExtendCopies" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 text-white" style="background:#1A237E;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-expand-arrows-alt"></i>
                    <h5 class="modal-title mb-0">Tambah Kuota Batch</h5>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="badge" style="background:rgba(255,255,255,0.18);font-size:11px;">Hanya Admin</span>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom">
                    <p class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;">Info Batch</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Batch</div>
                                <div class="fw-bold text-primary" id="ecBatch">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Item</div>
                                <div class="fw-bold text-dark" id="ecItem">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Kuota saat ini</div>
                                <div class="fw-bold text-dark"><span id="ecCurrentCopies">-</span> label</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#fff3cd;">
                                <div style="font-size:11px;color:#856404;">Label di luar kuota</div>
                                <div class="fw-bold" style="color:#856404;">#<span id="ecLabelNo">-</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-3 border-bottom">
                    <label class="form-label small fw-bold text-black">Kuota Baru (copies)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-hashtag"></i></span>
                        <input type="number" id="ecNewCopies" class="form-control form-control-lg" placeholder="Masukkan jumlah label baru...">
                        <span class="input-group-text text-muted small" id="ecMinHint">Min: -</span>
                    </div>
                    <div class="form-text" id="ecCopiesHelp">Harus lebih besar dari kuota saat ini.</div>
                </div>
                <div class="p-3">
                    <label class="form-label small fw-bold text-black">Alasan Penambahan Kuota</label>
                    <textarea id="ecNotes" class="form-control" rows="2" placeholder="Contoh: Penambahan produksi dari shift malam..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-3 pb-3 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="ecSubmitBtn" class="btn btn-primary flex-fill fw-bold" onclick="submitExtendCopies()">
                    <i class="fa fa-check me-2"></i>Simpan &amp; Terapkan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Resolve Item (add_to_stock / mark_damaged) -->
<div class="modal fade" id="modalResolveItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 text-white" id="resolveModalHeader" style="background:#0f766e;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa" id="resolveModalIcon"></i>
                    <h5 class="modal-title mb-0" id="resolveModalTitle">Selesaikan Item</h5>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom">
                    <p class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;">Detail Label</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Batch</div>
                                <div class="fw-bold text-primary" id="risBatch">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Label No</div>
                                <div class="fw-bold text-dark">#<span id="risLabelNo">-</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 p-2 rounded-3" id="risActionDesc" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <small class="text-muted" id="risActionText">-</small>
                    </div>
                </div>
                <div class="p-3">
                    <label class="form-label small fw-bold text-black" id="risNotesLabel">Catatan</label>
                    <textarea id="risNotes" class="form-control" rows="2" placeholder="Opsional..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-3 pb-3 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="risSubmitBtn" class="btn flex-fill fw-bold" onclick="submitResolveItem()">
                    Konfirmasi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Remove Missing Stock -->
<div class="modal fade" id="modalRemoveStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
            <div class="modal-header border-0 text-white" style="background:#991b1b;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa fa-trash-alt"></i>
                    <h5 class="modal-title mb-0">Hapus Label dari Stok</h5>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom">
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#fef2f2;border:1px solid #fecaca;">
                        <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                        <div>
                            <div class="fw-bold text-danger">Label ini akan dihapus permanen dari stok sistem.</div>
                            <small class="text-muted">Tindakan ini tidak dapat dibatalkan. Pastikan label memang tidak ada secara fisik.</small>
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Batch</div>
                                <div class="fw-bold text-primary" id="rmsBatch">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded-3 p-2" style="background:#f8fafc;">
                                <div class="text-muted" style="font-size:11px;">Label No</div>
                                <div class="fw-bold text-dark">#<span id="rmsLabelNo">-</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-3">
                    <label class="form-label small fw-bold text-black">Alasan Penghapusan</label>
                    <textarea id="rmsNotes" class="form-control" rows="2" placeholder="Contoh: Label tidak ditemukan saat pengecekan fisik..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-3 pb-3 gap-2">
                <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="rmsSubmitBtn" class="btn btn-danger flex-fill fw-bold" onclick="submitRemoveStock()">
                    <i class="fa fa-trash me-2"></i>Hapus dari Stok
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php' ?>
<script>
/* ── state ───────────────────────────────────────── */
let html5QrCode     = null;
let activeStream    = null;
let isProcessing    = false;
let activeSessionId = <?= (int)$defaultSessionId ?>;
let latestSnapshot  = null;
let batchRenderLimit = 15;
const isAdmin       = <?= is_admin_user() ? 'true' : 'false' ?>;
const cameraSelect   = document.getElementById('cameraSelect');
const torchToggleBtn = document.getElementById('torchToggleBtn');
let torchEnabled = false;

/* modal singletons — dibuat sekali setelah DOM ready */
let _modalExtend  = null;
let _modalResolve = null;
let _modalRemove  = null;

/* pending data untuk setiap modal aksi */
let _ecBarcodeRaw = '';
let _ecMinCopies  = 1;
let _risData      = {};   /* { batch, labelNo, actionType, sessionItemId } */
let _rmsData      = {};   /* { batch, productionId, labelNo } */

document.addEventListener('DOMContentLoaded', () => {
    _modalExtend  = new bootstrap.Modal(document.getElementById('modalExtendCopies'));
    _modalResolve = new bootstrap.Modal(document.getElementById('modalResolveItem'));
    _modalRemove  = new bootstrap.Modal(document.getElementById('modalRemoveStock'));
});

/* ── helpers ─────────────────────────────────────── */
function formatNum(v) {
    return Number(v || 0).toLocaleString('id-ID');
}

function updateScanStatus(type, title, text) {
    const box = document.getElementById('scanStatus');
    box.className = `status-panel ${type}`;
    box.innerHTML = `
        <div><i class="fa ${type==='success'?'fa-check-circle text-success':type==='error'?'fa-exclamation-triangle text-danger':'fa-qrcode text-primary'} fa-2x"></i></div>
        <div>
            <div class="font-w700 text-black">${title}</div>
            <small class="text-muted">${text}</small>
        </div>`;
}

/* ── torch ───────────────────────────────────────── */
function getActiveTorchTrack() {
    if (!activeStream) return null;
    const tracks = activeStream.getVideoTracks();
    return tracks.length ? tracks[0] : null;
}
function isTorchSupported() {
    const track = getActiveTorchTrack();
    if (!track) return false;
    const caps = track.getCapabilities ? track.getCapabilities() : {};
    return !!caps.torch;
}
function updateTorchButtonState(supported) {
    if (!torchToggleBtn) return;
    torchToggleBtn.disabled = !supported;
    torchToggleBtn.innerHTML = `<i class="fa fa-lightbulb me-2"></i>${torchEnabled ? 'Matikan Flash' : 'Aktifkan Flash'}`;
    torchToggleBtn.classList.toggle('btn-warning', supported && torchEnabled);
    torchToggleBtn.classList.toggle('text-white', supported && torchEnabled);
    torchToggleBtn.classList.toggle('btn-outline-warning', !(supported && torchEnabled));
}
async function toggleTorch() {
    const track = getActiveTorchTrack();
    if (!track || !isTorchSupported()) {
        toastr.error('Flash tidak didukung perangkat atau browser ini.');
        updateTorchButtonState(false);
        return;
    }
    try {
        torchEnabled = !torchEnabled;
        await track.applyConstraints({ advanced: [{ torch: torchEnabled }] });
        updateTorchButtonState(true);
        toastr.success(torchEnabled ? 'Flash diaktifkan.' : 'Flash dimatikan.');
    } catch (err) {
        torchEnabled = false;
        updateTorchButtonState(isTorchSupported());
        toastr.error(err?.message || 'Gagal mengubah status flash.');
    }
}

/* ── snapshot ────────────────────────────────────── */
async function loadSnapshot(sessionId) {
    if (!sessionId) {
        latestSnapshot = null;
        batchRenderLimit = 15;
        document.getElementById('batchTableBody').innerHTML =
            '<tr><td colspan="6" class="text-center py-5 text-muted">Pilih sesi untuk memulai.</td></tr>';
        document.getElementById('unknownBatchArea').innerHTML = 'Belum ada batch tidak terdaftar.';
        document.getElementById('issueArea').innerHTML = 'Belum ada isu scan.';
        document.getElementById('sessionBadgeText').innerText = 'Belum ada sesi dipilih';
        document.getElementById('loadMoreBatchesBtn').classList.add('d-none');
        return;
    }
    const res = await fetch(`warehouse_stock_opname.php?action=get_snapshot&session_id=${sessionId}&_nocache=${Date.now()}`);
    const result = await res.json();
    if (result.status !== 'success') { toastr.error(result.message || 'Gagal memuat sesi'); return; }
    latestSnapshot   = result.data;
    batchRenderLimit = 15;
    renderSnapshot(result.data);
}

function findPreviewSessionItem(batch, labelNo, allowedStatuses) {
    if (!latestSnapshot?.session_items?.length) return null;
    return latestSnapshot.session_items.find(item =>
        item.batch === batch &&
        Number(item.label_no) === Number(labelNo) &&
        allowedStatuses.includes(item.scan_status)
    ) || null;
}

function renderBatchLabelPreview(row) {
    const matchedHtml = row.matched_labels_count
        ? `<div class="d-flex flex-wrap gap-1">${row.matched_labels.map(l =>
            `<span class="label-chip match">#${l}</span>`).join('')}</div>`
        : '<span class="text-muted small">Tidak ada label.</span>';

    const missingHtml = row.missing_labels_count
        ? `<div class="d-flex flex-wrap gap-1">${row.missing_labels.map(l => `
            <span class="label-chip missing d-inline-flex align-items-center gap-1">#${l}
                ${isAdmin ? `<i class="fa fa-trash" style="font-size:10px;cursor:pointer;opacity:0.75;"
                    onclick="openRemoveStock('${row.batch}',${row.production_id},${l})" title="Hapus dari Stok"></i>` : ''}
            </span>`).join('')}</div>`
        : '<span class="text-muted small">Tidak ada label.</span>';

    const extraHtml = row.extra_labels_count
        ? `<div class="d-flex flex-wrap gap-1">${row.extra_labels.map(l => `
            <span class="label-chip extra d-inline-flex align-items-center gap-1">#${l}
                ${isAdmin ? `
                    <i class="fa fa-plus-circle" style="font-size:10px;cursor:pointer;opacity:0.75;"
                        onclick="openResolveItem('${row.batch}',${l},'add_to_stock')" title="Masuk Stok"></i>
                    <i class="fa fa-times-circle" style="font-size:10px;cursor:pointer;opacity:0.75;"
                        onclick="openResolveItem('${row.batch}',${l},'mark_damaged')" title="Tandai Rusak"></i>
                ` : ''}
            </span>`).join('')}</div>`
        : '<span class="text-muted small">Tidak ada label.</span>';

    return `<tr><td colspan="6" class="p-0">
        <div class="batch-label-preview">
            <div class="preview-section">
                <div class="preview-title">Label Cocok (${formatNum(row.matched_labels_count)})</div>
                ${matchedHtml}
            </div>
            <div class="preview-section">
                <div class="preview-title">Label Tidak Ada di Gudang (${formatNum(row.missing_labels_count)})</div>
                ${missingHtml}
            </div>
            <div class="preview-section">
                <div class="preview-title">Label Selisih Lebih (${formatNum(row.extra_labels_count)})</div>
                ${extraHtml}
            </div>
        </div>
    </td></tr>`;
}

function renderSnapshot(data) {
    const session = data.session || {};
    const summary = data.summary || {};
    document.getElementById('systemLabelsCount').innerText  = formatNum(summary.system_labels);
    document.getElementById('systemBatchCount').innerText   = formatNum(summary.system_batches);
    document.getElementById('systemUnitsCount').innerText   = formatNum(summary.system_units);
    document.getElementById('scannedLabelsCount').innerText = formatNum(summary.scanned_labels);
    document.getElementById('extraLabelsCount').innerText   = formatNum(summary.extra_labels);
    document.getElementById('missingLabelsCount').innerText = formatNum(summary.missing_labels);
    document.getElementById('sessionBadgeText').innerText   = `${session.session_code||'-'} • ${session.status||'-'}`;

    document.getElementById('toplineMetrics').innerHTML = `
        <span class="mini-badge ${session.status||'open'}">${(session.status||'open').toUpperCase()}</span>
        <span class="mini-badge" style="background:#f1f5f9;color:#334155;">Matched ${formatNum(summary.matched_labels)}</span>
        <span class="mini-badge" style="background:#fef3c7;color:#92400e;">Duplikat ${formatNum(summary.duplicates)}</span>
        <span class="mini-badge" style="background:#fee2e2;color:#991b1b;">Invalid ${formatNum(summary.invalid)}</span>`;

    const tbody       = document.getElementById('batchTableBody');
    const loadMoreBtn = document.getElementById('loadMoreBatchesBtn');
    if (!data.batches.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data batch pada sesi ini.</td></tr>';
        loadMoreBtn.classList.add('d-none');
    } else {
        const visibleRows = data.batches.slice(0, batchRenderLimit);
        tbody.innerHTML = visibleRows.map(row => `
            <tr>
                <td><div class="font-w800 text-primary">#${row.batch}</div><small class="text-muted">${row.quantity} unit/dus</small></td>
                <td><div class="font-w700 text-black">${row.item||'-'}</div><small class="text-muted">${row.size||'-'} ${row.unit||''}</small></td>
                <td class="text-center"><div class="font-w800 text-black">${formatNum(row.system_labels_count)}</div><small class="text-muted">${formatNum(row.system_units)} unit</small></td>
                <td class="text-center"><div class="font-w800 text-black">${formatNum(row.scanned_labels_count)}</div><small class="text-muted">${formatNum(row.scanned_units)} unit</small></td>
                <td class="text-center">
                    <span class="badge ${row.difference_labels_count===0?'bg-success':row.difference_labels_count>0?'bg-danger':'bg-warning'} text-white">
                        ${row.difference_labels_count>0?'+':''}${formatNum(row.difference_labels_count)}
                    </span>
                    <div class="small text-muted mt-1">Kurang ${formatNum(row.missing_labels_count)} • Lebih ${formatNum(row.extra_labels_count)}</div>
                </td>
                <td class="text-center"><span class="text-muted small">Label di bawah</span></td>
            </tr>
            ${renderBatchLabelPreview(row)}`).join('');

        if (data.batches.length > batchRenderLimit) {
            loadMoreBtn.classList.remove('d-none');
            loadMoreBtn.innerHTML = `<i class="fa fa-plus-circle me-2"></i>Load More (${formatNum(data.batches.length - batchRenderLimit)} sisa)`;
        } else {
            loadMoreBtn.classList.add('d-none');
        }
    }

    const unknown = data.unknown_batches || [];
    document.getElementById('unknownBatchArea').innerHTML = unknown.length
        ? `<div class="list-group">${unknown.map(row => `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="font-w700 text-black">#${row.batch||'-'}</div>
                        <small class="text-muted">Label #${row.label_no||'-'} • ${row.notes||'Batch tidak terdaftar'}</small>
                    </div>
                    <span class="label-chip unknown">Review</span>
                </div>
            </div>`).join('')}</div>`
        : 'Belum ada batch tidak terdaftar.';

    const issues = [...(data.duplicates||[]), ...(data.invalid_rows||[])];
    document.getElementById('issueArea').innerHTML = issues.length
        ? issues.map(row => {
            const isOutOfQuota = row.scan_status === 'invalid' &&
                (row.notes||'').includes('di luar jangkauan');
            const extendBtn = (isAdmin && isOutOfQuota && row.barcode_raw)
                ? `<div class="mt-1">
                    <button class="btn btn-outline-primary btn-sm py-0 px-2"
                        onclick="openExtendCopies('${row.barcode_raw}')">
                        <i class="fa fa-plus me-1"></i>Tambah Kuota Batch
                    </button>
                   </div>`
                : '';
            return `
                <div class="border-bottom py-2">
                    <div class="font-w700 text-black">${row.barcode_raw||'-'}
                        <span class="badge badge-light border ms-1">${row.scan_status}</span>
                    </div>
                    <small class="text-muted">${row.notes||'Perlu dicek ulang.'}</small>
                    ${extendBtn}
                </div>`;
          }).join('')
        : 'Belum ada isu scan.';

    const qi = [];
    if (summary.duplicates)     qi.push(`${summary.duplicates} scan duplikat`);
    if (summary.invalid)        qi.push(`${summary.invalid} scan invalid`);
    if (summary.extra_labels)   qi.push(`${summary.extra_labels} selisih lebih`);
    if (summary.missing_labels) qi.push(`${summary.missing_labels} selisih kurang`);
    document.getElementById('quickIssues').innerHTML = qi.length ? qi.join(' • ') : 'Semua scan bersih.';
}

function loadMoreBatches() {
    if (!latestSnapshot?.batches?.length) return;
    batchRenderLimit += 15;
    renderSnapshot(latestSnapshot);
}

/* ── scan ────────────────────────────────────────── */
async function submitScan(barcode) {
    if (!activeSessionId) { toastr.error('Pilih sesi stock opname terlebih dahulu.'); return; }
    if (isProcessing) return;
    isProcessing = true;
    try {
        const fd = new FormData();
        fd.append('session_id', activeSessionId);
        fd.append('barcode', barcode);
        const res    = await fetch('warehouse_stock_opname.php?action=scan', { method:'POST', body:fd });
        const result = await res.json();
        if (result.data) renderSnapshot(result.data);
        if (result.status === 'success') {
            updateScanStatus('success', 'Scan Tersimpan', result.message);
            toastr.success(result.message);
        } else {
            updateScanStatus('error', 'Scan Ditolak', result.message||'Periksa barcode.');
            toastr.error(result.message||'Scan gagal');
            /* Tawarkan extend hanya setelah renderSnapshot selesai */
            if (isAdmin && result.scan_status === 'invalid' &&
                (result.message||'').includes('luar kuota')) {
                setTimeout(() => openExtendCopies(barcode), 300);
            }
        }
    } catch (err) {
        updateScanStatus('error', 'Server Error', err.message);
        toastr.error('Koneksi terputus.');
    } finally {
        isProcessing = false;
        document.getElementById('manualBarcode').value = '';
        setTimeout(resumeScanner, 500);
    }
}

function submitManualScan() {
    const value = document.getElementById('manualBarcode').value.trim();
    if (value) submitScan(value);
}

/* ── modal: extend copies ────────────────────────── */
function openExtendCopies(barcodeRaw) {
    if (!isAdmin || !_modalExtend) return;

    const parts   = barcodeRaw.split('-');
    const labelNo = parseInt(parts[0], 10) || 0;
    const batch   = parts.slice(1).join('-');

    let itemName      = '-';
    let currentCopies = labelNo;

    if (latestSnapshot?.session_items) {
        const ref = latestSnapshot.session_items.find(i => i.batch === batch);
        if (ref?.item) itemName = ref.item;
        if (ref?.copies) currentCopies = ref.copies;
    }
    if (latestSnapshot?.batches) {
        const brow = latestSnapshot.batches.find(b => b.batch === batch);
        if (brow?.item) itemName = brow.item;
    }

    _ecBarcodeRaw = barcodeRaw;
    _ecMinCopies  = labelNo;

    document.getElementById('ecBatch').textContent         = batch || '-';
    document.getElementById('ecItem').textContent          = itemName;
    document.getElementById('ecCurrentCopies').textContent = currentCopies;
    document.getElementById('ecLabelNo').textContent       = labelNo;
    document.getElementById('ecMinHint').textContent       = `Min: ${labelNo}`;
    document.getElementById('ecCopiesHelp').textContent    =
        `Harus lebih besar dari kuota saat ini dan mencakup label #${labelNo}.`;
    document.getElementById('ecNewCopies').value           = labelNo;
    document.getElementById('ecNewCopies').min             = labelNo;
    document.getElementById('ecNotes').value               = '';
    document.getElementById('ecSubmitBtn').disabled        = false;
    document.getElementById('ecSubmitBtn').innerHTML       =
        '<i class="fa fa-check me-2"></i>Simpan &amp; Terapkan';

    _modalExtend.show();
}

async function submitExtendCopies() {
    const newCopies = parseInt(document.getElementById('ecNewCopies').value, 10);
    if (!newCopies || newCopies < _ecMinCopies) {
        toastr.error(`Kuota baru harus ≥ ${_ecMinCopies}.`);
        document.getElementById('ecNewCopies').focus();
        return;
    }
    const notes = document.getElementById('ecNotes').value.trim();
    const fd = new FormData();
    fd.append('session_id',  activeSessionId);
    fd.append('barcode_raw', _ecBarcodeRaw);
    fd.append('new_copies',  newCopies);
    fd.append('notes',       notes);

    const btn = document.getElementById('ecSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Menyimpan...';

    try {
        const res    = await fetch('warehouse_stock_opname.php?action=extend_batch_copies', { method:'POST', body:fd });
        const result = await res.json();
        if (result.status !== 'success') {
            toastr.error(result.message || 'Gagal memperluas kuota batch.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check me-2"></i>Simpan &amp; Terapkan';
            return;
        }
        _modalExtend.hide();
        latestSnapshot = result.data;
        renderSnapshot(result.data);
        toastr.success(result.message || 'Kuota batch berhasil diperbarui.');
    } catch (err) {
        toastr.error('Koneksi terputus.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check me-2"></i>Simpan &amp; Terapkan';
    }
}

/* ── modal: resolve item (add / damaged) ─────────── */
function openResolveItem(batch, labelNo, actionType) {
    if (!isAdmin || !_modalResolve) return;

    const row = findPreviewSessionItem(batch, labelNo, ['extra','extra_unknown_batch']);
    if (!row) { toastr.error('Item sesi tidak ditemukan.'); return; }

    _risData = { batch, labelNo, actionType, sessionItemId: row.id };

    const isAdd     = actionType === 'add_to_stock';
    const headerEl  = document.getElementById('resolveModalHeader');
    headerEl.style.background = isAdd ? '#0f766e' : '#92400e';
    document.getElementById('resolveModalIcon').className =
        `fa ${isAdd ? 'fa-plus-circle' : 'fa-times-circle'}`;
    document.getElementById('resolveModalTitle').textContent =
        isAdd ? 'Tambahkan ke Stok' : 'Tandai Rusak';

    document.getElementById('risBatch').textContent   = batch;
    document.getElementById('risLabelNo').textContent = labelNo;

    const descEl = document.getElementById('risActionDesc');
    const textEl = document.getElementById('risActionText');
    if (isAdd) {
        descEl.style.background = '#f0fdf4';
        descEl.style.border     = '1px solid #bbf7d0';
        textEl.textContent      = 'Label ini akan ditambahkan ke stok gudang sistem sebagai label aktif.';
    } else {
        descEl.style.background = '#fff7ed';
        descEl.style.border     = '1px solid #fed7aa';
        textEl.textContent      = 'Label ini akan dicatat sebagai rusak dan dikeluarkan dari stok aktif.';
    }
    document.getElementById('risNotesLabel').textContent =
        isAdd ? 'Catatan (opsional)' : 'Alasan kerusakan';
    document.getElementById('risNotes').placeholder =
        isAdd ? 'Catatan tambahan...' : 'Contoh: Kardus penyok, label sobek...';
    document.getElementById('risNotes').value = '';

    const submitBtn = document.getElementById('risSubmitBtn');
    submitBtn.className = `btn flex-fill fw-bold ${isAdd ? 'btn-success' : 'btn-warning'}`;
    submitBtn.innerHTML = `<i class="fa ${isAdd ? 'fa-plus' : 'fa-exclamation-triangle'} me-2"></i>
        ${isAdd ? 'Tambah ke Stok' : 'Tandai Rusak'}`;
    submitBtn.disabled = false;

    _modalResolve.show();
}

async function submitResolveItem() {
    const { batch, labelNo, actionType, sessionItemId } = _risData;
    const notes = document.getElementById('risNotes').value.trim();
    const fd = new FormData();
    fd.append('session_id',      activeSessionId);
    fd.append('session_item_id', sessionItemId);
    fd.append('action_type',     actionType);
    fd.append('notes',           notes);

    const btn = document.getElementById('risSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Menyimpan...';

    try {
        const res    = await fetch('warehouse_stock_opname.php?action=adjust_item', { method:'POST', body:fd });
        const result = await res.json();
        if (result.status !== 'success') {
            toastr.error(result.message || 'Gagal memproses adjustment.');
            btn.disabled = false;
            return;
        }
        _modalResolve.hide();
        latestSnapshot = result.data;
        renderSnapshot(result.data);
        toastr.success('Adjustment berhasil disimpan.');
    } catch (err) {
        toastr.error('Koneksi terputus.');
        btn.disabled = false;
    }
}

/* ── modal: remove missing stock ─────────────────── */
function openRemoveStock(batch, productionId, labelNo) {
    if (!isAdmin || !_modalRemove) return;
    _rmsData = { batch, productionId, labelNo };
    document.getElementById('rmsBatch').textContent   = batch;
    document.getElementById('rmsLabelNo').textContent = labelNo;
    document.getElementById('rmsNotes').value         = '';
    document.getElementById('rmsSubmitBtn').disabled  = false;
    document.getElementById('rmsSubmitBtn').innerHTML =
        '<i class="fa fa-trash me-2"></i>Hapus dari Stok';
    _modalRemove.show();
}

async function submitRemoveStock() {
    const { batch, productionId, labelNo } = _rmsData;
    const notes = document.getElementById('rmsNotes').value.trim();
    const fd = new FormData();
    fd.append('session_id',    activeSessionId);
    fd.append('action_type',   'remove_from_stock');
    fd.append('production_id', productionId);
    fd.append('batch',         batch);
    fd.append('label_no',      labelNo);
    fd.append('notes',         notes);

    const btn = document.getElementById('rmsSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Menghapus...';

    try {
        const res    = await fetch('warehouse_stock_opname.php?action=adjust_item', { method:'POST', body:fd });
        const result = await res.json();
        if (result.status !== 'success') {
            toastr.error(result.message || 'Gagal menghapus dari stok.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-trash me-2"></i>Hapus dari Stok';
            return;
        }
        _modalRemove.hide();
        latestSnapshot = result.data;
        renderSnapshot(result.data);
        toastr.success('Label berhasil dihapus dari stok.');
    } catch (err) {
        toastr.error('Koneksi terputus.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-trash me-2"></i>Hapus dari Stok';
    }
}

/* ── camera ──────────────────────────────────────── */
async function initCamera() {
    if (!window.isSecureContext && !['localhost','127.0.0.1'].includes(window.location.hostname)) {
        const msg = 'Browser memblokir kamera pada HTTP. Buka halaman ini lewat HTTPS atau localhost.';
        updateScanStatus('error', 'Kamera Diblokir Browser', msg);
        toastr.error(msg);
        return;
    }
    try {
        const devices = await Html5Qrcode.getCameras();
        if (!devices?.length) {
            updateScanStatus('error', 'Kamera Tidak Ditemukan', 'Perangkat tidak mengembalikan daftar kamera.');
            toastr.error('Kamera tidak ditemukan.');
            return;
        }
        cameraSelect.innerHTML = '';
        devices.forEach(d => cameraSelect.appendChild(new Option(d.label || `Camera ${d.id}`, d.id)));
        const backCam    = devices.find(d => /back|rear|environment/i.test(d.label || ''));
        const selectedId = backCam ? backCam.id : devices[0].id;
        cameraSelect.value = selectedId;
        await startScanner(selectedId);
    } catch (err) {
        const msg = err?.message || 'Gagal mengaktifkan kamera.';
        updateScanStatus('error', 'Gagal Aktifkan Kamera', msg);
        toastr.error(msg);
    }
}

async function startScanner(deviceId) {
    if (!deviceId) return;
    try {
        if (html5QrCode) {
            try {
                const state = html5QrCode.getState();
                if (state === 2 || state === 3) await html5QrCode.stop();
                else await html5QrCode.clear();
            } catch (_) {}
        }
        html5QrCode = new Html5Qrcode('reader');
        const boxWidth = window.innerWidth < 576 ? 220 : 280;
        await html5QrCode.start(
            deviceId,
            { fps: 12, qrbox: { width: boxWidth, height: 140 }, aspectRatio: 1.77 },
            (decodedText) => {
                if (isProcessing) return;
                try { if (html5QrCode.getState() === 2) html5QrCode.pause(true); } catch (_) {}
                submitScan(decodedText);
            }
        );
        /* Ambil stream dari video element yang sudah aktif */
        const videoEl = document.querySelector('#reader video');
        if (videoEl?.srcObject instanceof MediaStream) {
            activeStream = videoEl.srcObject;
        }
        torchEnabled = false;
        updateTorchButtonState(isTorchSupported());
        updateScanStatus('info', 'Kamera Aktif', 'Arahkan kamera ke barcode label gudang.');
    } catch (err) {
        const msg = err?.message || 'Start kamera gagal.';
        torchEnabled  = false;
        activeStream  = null;
        updateTorchButtonState(false);
        updateScanStatus('error', 'Start Kamera Gagal', msg);
        toastr.error(msg);
    }
}

function switchCamera(deviceId) { if (deviceId) startScanner(deviceId); }

function resumeScanner() {
    if (!html5QrCode) return;
    try {
        if (html5QrCode.getState() === 3) html5QrCode.resume();
        updateScanStatus('info', 'Scanner Ready', 'Arahkan kamera ke barcode label gudang.');
    } catch (_) {}
}

/* ── session ─────────────────────────────────────── */
async function createSession(event) {
    event.preventDefault();
    const fd = new FormData(event.target);
    const res    = await fetch('warehouse_stock_opname.php?action=create_session', { method:'POST', body:fd });
    const result = await res.json();
    if (result.status !== 'success') { toastr.error(result.message||'Gagal membuat sesi'); return; }
    toastr.success('Sesi berhasil dibuat.');
    bootstrap.Modal.getInstance(document.getElementById('modalCreateSession')).hide();
    window.location.reload();
}

async function changeSessionStatus(targetStatus) {
    if (!activeSessionId) { toastr.error('Pilih sesi terlebih dahulu.'); return; }
    const fd = new FormData();
    fd.append('session_id',    activeSessionId);
    fd.append('target_status', targetStatus);
    const res    = await fetch('warehouse_stock_opname.php?action=change_session_status', { method:'POST', body:fd });
    const result = await res.json();
    if (result.status !== 'success') { toastr.error(result.message||'Gagal mengubah status sesi.'); return; }
    toastr.success('Status sesi diperbarui.');
    await loadSnapshot(activeSessionId);
}

/* ── init ────────────────────────────────────────── */
document.getElementById('createSessionForm').addEventListener('submit', createSession);
document.getElementById('sessionSelect').addEventListener('change', e => {
    activeSessionId = Number(e.target.value || 0);
    loadSnapshot(activeSessionId);
});
document.getElementById('manualBarcode').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); submitManualScan(); }
});

<?php if ($defaultSessionId > 0): ?>
loadSnapshot(<?= (int)$defaultSessionId ?>);
<?php endif; ?>
</script>
</body>
</html>
