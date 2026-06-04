<?php

if (!function_exists('buildWarehouseBarcodeEntries')) {
    /**
     * Build barcode entries for the full batch.
     *
     * @param int   $copies         Total labels in the batch.
     * @param array  $warehouseLabels Labels that are still in warehouse_items.
     * @param array  $shippedLabels  Labels that already exist in distributor_shipments.
     *
     * @return array<int, array{label_no:int,is_shipped:bool,status:string}>
     */
    function buildWarehouseBarcodeEntries(int $copies, array $warehouseLabels, array $shippedLabels): array
    {
        $warehouseLookup = array_fill_keys(array_map('intval', $warehouseLabels), true);
        $shippedLookup = array_fill_keys(array_map('intval', $shippedLabels), true);
        $entries = [];

        for ($labelNo = 1; $labelNo <= $copies; $labelNo++) {
            $isShipped = isset($shippedLookup[$labelNo]);
            $entries[] = [
                'label_no' => $labelNo,
                'is_shipped' => $isShipped,
                'is_in_warehouse' => isset($warehouseLookup[$labelNo]),
                'status' => $isShipped ? 'Terkirim' : 'Belum Terkirim',
            ];
        }

        return $entries;
    }
}
