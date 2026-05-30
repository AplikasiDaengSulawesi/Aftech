<?php

class ShipmentReverseHelper
{
    private mysqli $conn;
    private string $cutoffDate = '2026-05-30';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function parseBarcode(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new RuntimeException('QR Kosong');
        }

        $parts = explode('-', $raw, 2);
        if (count($parts) < 2) {
            throw new RuntimeException('Format QR Tidak Valid');
        }

        $labelNo = (int)$parts[0];
        $batch = trim($parts[1]);

        if ($labelNo < 1 || $batch === '') {
            throw new RuntimeException('Format QR Tidak Valid');
        }

        return [
            'label_no' => $labelNo,
            'batch' => $batch,
        ];
    }

    public function parseBatch(string $batch): array
    {
        $batch = trim($batch);
        if (!preg_match('/^(\d{6})-([0-9]{1,2})([A-Z])-([A-Z]+)-([0-9]+)-([A-Z]+)-([0-9]+)([A-Z]+)$/', $batch, $m)) {
            throw new RuntimeException('Format batch reverse tidak valid');
        }

        return [
            'batch' => $batch,
            'date_code' => $m[1],
            'machine_code' => str_pad($m[2], 2, '0', STR_PAD_LEFT),
            'shift_code' => strtoupper($m[3]),
            'item_code' => strtoupper($m[4]),
            'quantity' => $m[5],
            'operator_qc_code' => strtoupper($m[6]),
            'size_value' => $m[7],
            'unit_code' => strtoupper($m[8]),
        ];
    }

    public function parseProductionDate(string $dateCode): string
    {
        $date = DateTime::createFromFormat('dmy', $dateCode);
        if (!$date) {
            throw new RuntimeException('Tanggal produksi pada batch tidak valid');
        }

        return $date->format('Y-m-d');
    }

    public function assertReverseAllowedDate(string $productionDate): void
    {
        if ($productionDate >= $this->cutoffDate) {
            throw new RuntimeException('Mode reverse hanya berlaku untuk produksi sebelum 2026-05-30');
        }
    }

    public function reverseScan(string $barcode, string $user): array
    {
        $parsedBarcode = $this->parseBarcode($barcode);
        $parsedBatch = $this->parseBatch($parsedBarcode['batch']);
        $productionDate = $this->parseProductionDate($parsedBatch['date_code']);
        $this->assertReverseAllowedDate($productionDate);

        $this->conn->begin_transaction();
        try {
            $existingBatch = $this->findProductionByBatch($parsedBarcode['batch']);

            if ($existingBatch) {
                $this->assertReverseAllowedDate((string)$existingBatch['production_date']);
                $productionId = (int)$existingBatch['id'];
                $reverseStatus = $this->syncExistingBatch($productionId, $parsedBarcode['label_no'], $user, $parsedBarcode['batch']);
            } else {
                $resolved = $this->resolveBatchReferences($parsedBatch);
                $productionId = $this->createProductionBatch($parsedBarcode['batch'], $parsedBarcode['label_no'], $productionDate, $resolved);
                $this->ensureWarehouseTransfer($productionId, $user);
                $this->insertWarehouseLabel($productionId, $parsedBarcode['label_no'], $user);
                $reverseStatus = 'created_batch';
                $this->writeActivityLog('REVERSE_SCAN', "Reverse scan membuat batch lama #{$parsedBarcode['batch']} dan label #{$parsedBarcode['label_no']}");
            }

            $payload = $this->buildPayload($productionId, $parsedBarcode['batch'], $parsedBarcode['label_no'], $reverseStatus);
            $this->conn->commit();

            return $payload;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function resolveBatchReferences(array $parsed): array
    {
        $item = $this->resolveItem($parsed['item_code'], $parsed['unit_code']);
        $machine = $this->resolveMachine($parsed['machine_code'], (int)$item['default_machine_id']);
        $shift = $this->resolveShift($parsed['shift_code']);
        $this->assertQuantityExists($machine['id'], $parsed['quantity']);
        $this->assertSizeExists((int)$item['id'], $parsed['size_value']);
        $operatorQc = $this->resolveOperatorQc($parsed['operator_qc_code']);

        return [
            'item' => $item['name'],
            'size' => $parsed['size_value'],
            'unit' => $item['unit_name'],
            'machine' => $machine['name'],
            'shift' => $shift,
            'quantity' => $parsed['quantity'],
            'operator' => $operatorQc['operator'],
            'qc' => $operatorQc['qc'],
        ];
    }

    private function resolveItem(string $itemCode, string $unitCode): array
    {
        $stmt = $this->conn->prepare("
            SELECT i.id, i.name, i.default_machine_id, u.name AS unit_name
            FROM master_items i
            LEFT JOIN master_units u ON u.id = i.unit_id
            ORDER BY i.name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $prefix = strtoupper(substr((string)$row['name'], 0, 3));
            if ($prefix === $itemCode && strtoupper((string)$row['unit_name']) === $unitCode) {
                return $row;
            }
        }

        throw new RuntimeException("Kode item/unit {$itemCode}-{$unitCode} tidak ditemukan di master");
    }

    private function resolveMachine(string $machineCode, int $defaultMachineId): array
    {
        if ($defaultMachineId > 0) {
            $stmt = $this->conn->prepare("SELECT id, name, status FROM master_machines WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $defaultMachineId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && $this->extractMachineDigits((string)$row['name']) === ltrim($machineCode, '0')) {
                return $row;
            }
        }

        $stmt = $this->conn->prepare("SELECT id, name, status FROM master_machines ORDER BY status='active' DESC, id ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($this->extractMachineDigits((string)$row['name']) === ltrim($machineCode, '0')) {
                return $row;
            }
        }

        throw new RuntimeException("Kode mesin {$machineCode} tidak ditemukan di master");
    }

    private function resolveShift(string $shiftCode): string
    {
        $shift = 'SHIFT ' . strtoupper($shiftCode);
        $stmt = $this->conn->prepare("SELECT name FROM master_shifts WHERE UPPER(name) = ? LIMIT 1");
        $stmt->bind_param('s', $shift);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            throw new RuntimeException("Kode shift {$shiftCode} tidak ditemukan di master");
        }

        return $row['name'];
    }

    private function assertQuantityExists(int $machineId, string $quantity): void
    {
        $stmt = $this->conn->prepare("SELECT id FROM master_quantities WHERE machine_id = ? AND qty_value = ? LIMIT 1");
        $stmt->bind_param('is', $machineId, $quantity);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException("Qty {$quantity} tidak cocok untuk mesin pada master");
        }
    }

    private function assertSizeExists(int $itemId, string $sizeValue): void
    {
        $stmt = $this->conn->prepare("SELECT id FROM master_sizes WHERE item_id = ? AND size_value = ? LIMIT 1");
        $stmt->bind_param('is', $itemId, $sizeValue);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException("Ukuran {$sizeValue} tidak ditemukan di master");
        }
    }

    private function resolveOperatorQc(string $operatorQcCode): array
    {
        $likeCode = '%-' . $operatorQcCode . '-%';
        $stmt = $this->conn->prepare("
            SELECT operator, qc
            FROM production_labels
            WHERE batch LIKE ?
              AND operator IS NOT NULL AND operator <> ''
              AND qc IS NOT NULL AND qc <> ''
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->bind_param('s', $likeCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            return [
                'operator' => $operatorQcCode,
                'qc' => $operatorQcCode,
            ];
        }

        return [
            'operator' => $row['operator'],
            'qc' => $row['qc'],
        ];
    }

    private function findProductionByBatch(string $batch): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT id, item, size, unit, machine, shift, quantity, operator, qc, production_date, copies
            FROM production_labels
            WHERE batch = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $batch);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }

    private function syncExistingBatch(int $productionId, int $labelNo, string $user, string $batch): string
    {
        $this->assertNotShipped($productionId, $labelNo);

        if ($this->warehouseLabelExists($productionId, $labelNo)) {
            $this->writeActivityLog('REVERSE_SCAN', "Reverse scan mengenali label existing #{$labelNo} pada batch #{$batch}");
            return 'existing_label';
        }

        $stmt = $this->conn->prepare("SELECT copies FROM production_labels WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $productionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $copies = (int)($row['copies'] ?? 0);
        if ($labelNo > $copies) {
            $stmtUpdate = $this->conn->prepare("UPDATE production_labels SET copies = ? WHERE id = ?");
            $stmtUpdate->bind_param('ii', $labelNo, $productionId);
            $stmtUpdate->execute();
        }

        $this->ensureWarehouseTransfer($productionId, $user);
        $this->insertWarehouseLabel($productionId, $labelNo, $user);
        $this->writeActivityLog('REVERSE_SCAN', "Reverse scan menambah label #{$labelNo} ke batch existing #{$batch}");

        return 'created_label';
    }

    private function buildPayload(int $productionId, string $batch, int $labelNo, string $reverseStatus): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, item, size, unit, copies
            FROM production_labels
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $productionId);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        if (!$prod) {
            throw new RuntimeException('Data batch reverse gagal dimuat ulang');
        }

        $inWarehouse = [];
        $stmtWarehouse = $this->conn->prepare("SELECT label_no FROM warehouse_items WHERE production_id = ? ORDER BY label_no ASC");
        $stmtWarehouse->bind_param('i', $productionId);
        $stmtWarehouse->execute();
        $resultWarehouse = $stmtWarehouse->get_result();
        while ($row = $resultWarehouse->fetch_assoc()) {
            $inWarehouse[] = (int)$row['label_no'];
        }

        $alreadyShipped = [];
        $stmtShipped = $this->conn->prepare("SELECT label_no FROM distributor_shipments WHERE production_id = ? ORDER BY label_no ASC");
        $stmtShipped->bind_param('i', $productionId);
        $stmtShipped->execute();
        $resultShipped = $stmtShipped->get_result();
        while ($row = $resultShipped->fetch_assoc()) {
            $alreadyShipped[] = (int)$row['label_no'];
        }

        return [
            'production_id' => (int)$prod['id'],
            'batch' => $batch,
            'item' => $prod['item'],
            'size' => $prod['size'] . ' ' . $prod['unit'],
            'copies' => (int)$prod['copies'],
            'input_method' => 'scan',
            'scanned_label' => $labelNo,
            'in_warehouse' => $inWarehouse,
            'already_shipped' => $alreadyShipped,
            'reverse_status' => $reverseStatus,
        ];
    }

    private function createProductionBatch(string $batch, int $labelNo, string $productionDate, array $resolved): int
    {
        $productionTime = '00:00:00';
        $deviceModel = 'Reverse-Gudang';
        $stmt = $this->conn->prepare("
            INSERT INTO production_labels
            (batch, item, size, unit, machine, shift, quantity, operator, qc, production_date, production_time, copies, device_model)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'sssssssssssis',
            $batch,
            $resolved['item'],
            $resolved['size'],
            $resolved['unit'],
            $resolved['machine'],
            $resolved['shift'],
            $resolved['quantity'],
            $resolved['operator'],
            $resolved['qc'],
            $productionDate,
            $productionTime,
            $labelNo,
            $deviceModel
        );
        $stmt->execute();

        return (int)$this->conn->insert_id;
    }

    private function ensureWarehouseTransfer(int $productionId, string $user): void
    {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO warehouse_transfers (production_id, transferred_by) VALUES (?, ?)");
        $stmt->bind_param('is', $productionId, $user);
        $stmt->execute();
    }

    private function insertWarehouseLabel(int $productionId, int $labelNo, string $user): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO warehouse_items (production_id, label_no, transferred_by, input_method)
            VALUES (?, ?, ?, 'scan')
        ");
        $stmt->bind_param('iis', $productionId, $labelNo, $user);
        $stmt->execute();

        // Kurangi offset base stock maya, karena barang reverse ini diambil dari tumpukan
        // fisik lama yang belum tercatat. Sehingga saat barangnya selesai dikirim,
        // stok total di Dasbor akan benar-benar terhitung berkurang (minus).
        $this->conn->query("
            INSERT INTO app_settings (setting_key, setting_value) 
            VALUES ('warehouse_base_stock', '-1') 
            ON DUPLICATE KEY UPDATE setting_value = CAST(setting_value AS SIGNED) - 1
        ");
    }

    private function warehouseLabelExists(int $productionId, int $labelNo): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM warehouse_items WHERE production_id = ? AND label_no = ? LIMIT 1");
        $stmt->bind_param('ii', $productionId, $labelNo);
        $stmt->execute();

        return (bool)$stmt->get_result()->fetch_assoc();
    }

    private function assertNotShipped(int $productionId, int $labelNo): void
    {
        $stmt = $this->conn->prepare("SELECT id FROM distributor_shipments WHERE production_id = ? AND label_no = ? LIMIT 1");
        $stmt->bind_param('ii', $productionId, $labelNo);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new RuntimeException("Dus #{$labelNo} sudah pernah dikirim!");
        }
    }

    private function writeActivityLog(string $action, string $details): void
    {
        $stmt = $this->conn->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
        $stmt->bind_param('ss', $action, $details);
        $stmt->execute();
    }

    private function extractMachineDigits(string $name): string
    {
        if (preg_match('/(\d+)/', $name, $m)) {
            return (string)((int)$m[1]);
        }

        return '';
    }
}
