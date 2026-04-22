-- Migration 007 — Simpan metode input stok gudang per label
-- Nilai yang dipakai aplikasi:
-- scan   = hasil scan QR / auto-transfer dari mobile produksi
-- manual = input manual dari halaman inventori gudang
-- Data lama di-backfill 'scan' (sebelum fitur ini, semua transfer dari scan).

ALTER TABLE `warehouse_items`
  ADD COLUMN `input_method` varchar(20) DEFAULT NULL
  AFTER `transferred_at`,
  ALGORITHM=INPLACE, LOCK=NONE;

ALTER TABLE `warehouse_items`
  ADD INDEX `idx_input_method` (`input_method`),
  ALGORITHM=INPLACE, LOCK=NONE;

-- Backfill data lama sebagai 'scan'
UPDATE `warehouse_items`
  SET `input_method` = 'scan'
  WHERE `input_method` IS NULL;

INSERT INTO `schema_migrations` (`version`) VALUES ('007_warehouse_items_input_method')
ON DUPLICATE KEY UPDATE `version` = `version`;
