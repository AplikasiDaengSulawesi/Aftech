<?php

if (!function_exists('normalizeMobileProductionDate')) {
    function normalizeMobileProductionDate(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        $dateObj = DateTime::createFromFormat('d-m-Y', $rawDate);
        if (!$dateObj) {
            return null;
        }

        $errors = DateTime::getLastErrors();
        if (!empty($errors['warning_count']) || !empty($errors['error_count'])) {
            return null;
        }

        return $dateObj->format('Y-m-d');
    }
}

if (!function_exists('normalizeMobileInputMethod')) {
    function normalizeMobileInputMethod(?string $inputMethod): string
    {
        $method = strtolower(trim((string) $inputMethod));

        return in_array($method, ['scan', 'manual'], true) ? $method : 'scan';
    }
}

if (!function_exists('buildMobileLabelRange')) {
    /**
     * Build the label range for a production append.
     *
     * @return array{first_label_no:int,last_label_no:int,label_nos:array<int>,qr_codes:array<int,string>}
     */
    function buildMobileLabelRange(int $currentCopies, int $copies, string $batch): array
    {
        if ($copies <= 0) {
            throw new InvalidArgumentException('Jumlah copies harus lebih besar dari 0.');
        }

        $firstLabelNo = $currentCopies + 1;
        $lastLabelNo = $currentCopies + $copies;
        $labelNos = [];
        $qrCodes = [];

        for ($labelNo = $firstLabelNo; $labelNo <= $lastLabelNo; $labelNo++) {
            $labelNos[] = $labelNo;
            $qrCodes[] = $labelNo . '-' . $batch;
        }

        return [
            'first_label_no' => $firstLabelNo,
            'last_label_no' => $lastLabelNo,
            'label_nos' => $labelNos,
            'qr_codes' => $qrCodes,
        ];
    }
}

if (!function_exists('normalizeMobileProductionRecord')) {
    /**
     * Normalize one production payload before persistence.
     *
     * @param array<string, mixed> $record
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    function normalizeMobileProductionRecord(array $record, array $defaults = []): array
    {
        $merged = array_merge($defaults, $record);
        $requiredFields = ['item', 'size', 'unit', 'batch', 'machine', 'shift', 'quantity', 'operator', 'qc', 'production_date', 'production_time', 'copies'];

        foreach ($requiredFields as $field) {
            if (!isset($merged[$field]) || trim((string) $merged[$field]) === '') {
                throw new InvalidArgumentException("Input tidak valid. Field $field wajib diisi.");
            }
        }

        $formattedDate = normalizeMobileProductionDate((string) $merged['production_date']);
        if ($formattedDate === null) {
            throw new InvalidArgumentException('Input tidak valid. production_date harus berformat dd-MM-yyyy.');
        }

        $copies = (int) $merged['copies'];
        if ($copies <= 0) {
            throw new InvalidArgumentException('Input tidak valid. copies harus lebih besar dari 0.');
        }

        return [
            'item' => trim((string) $merged['item']),
            'size' => trim((string) $merged['size']),
            'unit' => trim((string) $merged['unit']),
            'batch' => trim((string) $merged['batch']),
            'machine' => trim((string) $merged['machine']),
            'shift' => trim((string) $merged['shift']),
            'quantity' => trim((string) $merged['quantity']),
            'operator' => trim((string) $merged['operator']),
            'qc' => trim((string) $merged['qc']),
            'production_date' => $formattedDate,
            'production_time' => trim((string) $merged['production_time']),
            'copies' => $copies,
            'input_method' => normalizeMobileInputMethod($merged['input_method'] ?? 'scan'),
            'device_model' => trim((string) ($merged['device_model'] ?? 'Unknown')),
            'device_id' => trim((string) ($merged['device_id'] ?? '')),
        ];
    }
}

if (!function_exists('normalizeMobileProductionSyncEnvelope')) {
    /**
     * Normalize a bulk sync envelope into a list of production records.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    function normalizeMobileProductionSyncEnvelope(array $data): array
    {
        $defaults = [
            'device_id' => trim((string) ($data['device_id'] ?? '')),
            'device_model' => trim((string) ($data['device_model'] ?? 'Unknown')),
            'input_method' => normalizeMobileInputMethod($data['input_method'] ?? 'scan'),
        ];

        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            if (empty($data['items'])) {
                throw new InvalidArgumentException('Input tidak valid. items tidak boleh kosong.');
            }

            foreach ($data['items'] as $item) {
                if (!is_array($item)) {
                    throw new InvalidArgumentException('Input tidak valid. Setiap item harus berupa object/array.');
                }

                $items[] = normalizeMobileProductionRecord($item, $defaults);
            }
        } else {
            $items[] = normalizeMobileProductionRecord($data, $defaults);
        }

        return [
            'device_id' => $defaults['device_id'],
            'device_model' => $defaults['device_model'],
            'input_method' => $defaults['input_method'],
            'items' => $items,
        ];
    }
}

if (!function_exists('persistMobileProductionRecord')) {
    /**
     * Persist one normalized production record into the current schema.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    function persistMobileProductionRecord(mysqli $conn, array $record): array
    {
        $required = ['item', 'size', 'unit', 'batch', 'machine', 'shift', 'quantity', 'operator', 'qc', 'production_date', 'production_time', 'copies', 'input_method', 'device_model', 'device_id'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $record)) {
                throw new InvalidArgumentException("Input tidak valid. Field $field wajib diisi.");
            }
        }

        $item = $conn->real_escape_string(trim((string) $record['item']));
        $size = $conn->real_escape_string(trim((string) $record['size']));
        $unit = $conn->real_escape_string(trim((string) $record['unit']));
        $batch = $conn->real_escape_string(trim((string) $record['batch']));
        $machine = $conn->real_escape_string(trim((string) $record['machine']));
        $shift = $conn->real_escape_string(trim((string) $record['shift']));
        $quantity = $conn->real_escape_string(trim((string) $record['quantity']));
        $operator = $conn->real_escape_string(trim((string) $record['operator']));
        $qc = $conn->real_escape_string(trim((string) $record['qc']));
        $formattedDate = $conn->real_escape_string((string) $record['production_date']);
        $time = $conn->real_escape_string(trim((string) $record['production_time']));
        $copies = (int) $record['copies'];
        $inputMethod = $conn->real_escape_string(normalizeMobileInputMethod($record['input_method']));
        $deviceModel = $conn->real_escape_string(trim((string) $record['device_model']));
        $deviceId = $conn->real_escape_string(trim((string) $record['device_id']));

        $conn->begin_transaction();

        try {
            $currCopies = 0;
            $prodId = 0;

            $resBatch = $conn->query("SELECT id, copies FROM production_labels WHERE batch='$batch' LIMIT 1 FOR UPDATE");
            if ($resBatch === false) {
                throw new RuntimeException($conn->error);
            }

            if ($resBatch->num_rows > 0) {
                $rowBatch = $resBatch->fetch_assoc();
                $currCopies = (int) $rowBatch['copies'];
                $prodId = (int) $rowBatch['id'];
            }

            $sql = "INSERT INTO production_labels (item, size, unit, batch, machine, shift, quantity, operator, qc, production_date, production_time, copies, device_model, device_id)
                    VALUES ('$item', '$size', '$unit', '$batch', '$machine', '$shift', '$quantity', '$operator', '$qc', '$formattedDate', '$time', $copies, '$deviceModel', '$deviceId')
                    ON DUPLICATE KEY UPDATE
                    copies = copies + VALUES(copies),
                    shift = VALUES(shift),
                    qc = VALUES(qc),
                    production_time = VALUES(production_time),
                    device_model = VALUES(device_model),
                    device_id = VALUES(device_id)";

            if ($conn->query($sql) !== true) {
                throw new RuntimeException($conn->error);
            }

            if ($prodId === 0) {
                $prodId = (int) $conn->insert_id;
            }

            $range = buildMobileLabelRange($currCopies, $copies, (string) $record['batch']);

            foreach ($range['label_nos'] as $index => $labelNo) {
                $qr = $conn->real_escape_string($range['qr_codes'][$index]);
                if ($conn->query("INSERT INTO print_queues (production_id, batch, label_no, qr_code) VALUES ($prodId, '$batch', $labelNo, '$qr')") !== true) {
                    throw new RuntimeException($conn->error);
                }
            }

            $qcCheckRes = $conn->query("SELECT setting_value FROM app_settings WHERE setting_key='qc_checker_enabled'");
            if ($qcCheckRes === false) {
                throw new RuntimeException($conn->error);
            }

            $isQcEnabled = ($row = $qcCheckRes->fetch_assoc()) ? (int) $row['setting_value'] : 0;
            if (!$isQcEnabled) {
                if ($conn->query("INSERT IGNORE INTO warehouse_transfers (production_id, transferred_by) VALUES ($prodId, 'Auto-System')") !== true) {
                    throw new RuntimeException($conn->error);
                }

                foreach ($range['label_nos'] as $labelNo) {
                    if ($conn->query("INSERT IGNORE INTO warehouse_items (production_id, label_no, transferred_by, input_method) VALUES ($prodId, $labelNo, 'Auto-System', '$inputMethod')") !== true) {
                        throw new RuntimeException($conn->error);
                    }
                }
            }

            $conn->commit();

            return [
                'status' => 'success',
                'message' => 'Berhasil Disimpan',
                'production_id' => $prodId,
                'batch' => (string) $record['batch'],
                'copies' => $copies,
                'first_label_no' => $range['first_label_no'],
                'last_label_no' => $range['last_label_no'],
                'input_method' => $inputMethod,
                'label_nos' => $range['label_nos'],
                'qr_codes' => $range['qr_codes'],
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
