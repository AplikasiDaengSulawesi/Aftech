<?php

if (!class_exists('ShipmentSubmissionException')) {
    class ShipmentSubmissionException extends RuntimeException
    {
        /** @var array<string, mixed> */
        private array $context;

        /**
         * @param array<string, mixed> $context
         */
        public function __construct(string $message, array $context = [])
        {
            parent::__construct($message);
            $this->context = $context;
        }

        /**
         * @return array<string, mixed>
         */
        public function getContext(): array
        {
            return $this->context;
        }
    }
}

if (!function_exists('persistShipmentLabelsWithGuard')) {
    /**
     * Persist shipment labels and fail fast if any label is not saved.
     *
     * The callback must return true for every successfully persisted label.
     *
     * @param array    $cart
     * @param callable $persistLabel function(int $productionId, int $labelNo): bool
     *
     * @return int Number of labels persisted.
     */
    function persistShipmentLabelsWithGuard(array $cart, callable $persistLabel): int
    {
        $expected = 0;
        $persisted = 0;

        foreach ($cart as $prodId => $labels) {
            if (!is_array($labels)) {
                throw new InvalidArgumentException('Format keranjang pengiriman tidak valid.');
            }

            $prodId = (int) $prodId;
            foreach ($labels as $labelNo) {
                $labelNo = (int) $labelNo;
                $expected++;

                if ($persistLabel($prodId, $labelNo) !== true) {
                    throw new ShipmentSubmissionException(
                        "Gagal menyimpan Dus #$labelNo untuk batch ID #$prodId.",
                        ['production_id' => $prodId, 'label_no' => $labelNo, 'stage' => 'persist_label']
                    );
                }

                $persisted++;
            }
        }

        if ($persisted !== $expected) {
            throw new ShipmentSubmissionException(
                "Jumlah label tersimpan tidak cocok. Diminta $expected, tersimpan $persisted.",
                ['stage' => 'count_mismatch', 'expected' => $expected, 'persisted' => $persisted]
            );
        }

        return $persisted;
    }
}

if (!function_exists('formatShipmentSubmissionFailureLog')) {
    /**
     * Build a structured log line for shipment submission failures.
     *
     * @param array<string, mixed> $context
     */
    function formatShipmentSubmissionFailureLog(array $context, string $message): string
    {
        $parts = [
            'shipment_submission_failed',
            'message=' . $message,
        ];

        foreach (['shipment_id', 'production_id', 'label_no', 'customer_name', 'total_qty'] as $key) {
            if (array_key_exists($key, $context) && $context[$key] !== null && $context[$key] !== '') {
                $parts[] = $key . '=' . $context[$key];
            }
        }

        if (!empty($context['details']) && is_array($context['details'])) {
            $parts[] = 'details=' . json_encode($context['details'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return implode(' | ', $parts);
    }
}

if (!function_exists('logShipmentSubmissionFailure')) {
    /**
     * Write a structured shipment failure log to PHP error log.
     *
     * @param array<string, mixed> $context
     */
    function logShipmentSubmissionFailure(array $context, string $message): void
    {
        error_log(formatShipmentSubmissionFailureLog($context, $message));
    }
}

if (!function_exists('resolveExpectedShipmentCount')) {
    function resolveExpectedShipmentCount(int $persistedLabels, int $existingCount, int $appendTo): int
    {
        return $appendTo > 0 ? ($existingCount + $persistedLabels) : $persistedLabels;
    }
}

if (!function_exists('formatShipmentCountMismatchMessage')) {
    function formatShipmentCountMismatchMessage(int $appendTo, int $persistedLabels, int $expectedTotal, int $persistedTotal): string
    {
        if ($appendTo > 0) {
            return "Jumlah total label shipment susulan tidak cocok. Diminta total akhir $expectedTotal, tersimpan $persistedTotal. Label submit saat ini: $persistedLabels.";
        }

        return "Jumlah label tersimpan tidak cocok. Diminta $expectedTotal, tersimpan $persistedTotal.";
    }
}
