-- Migration 006 — Simpan metode input shipment untuk badge histori
-- Nilai yang dipakai aplikasi: scan, manual, campuran
-- Data lama di-backfill 'scan' (sebelum fitur ini, semua shipment hasil scan QR).

ALTER TABLE `outbound_shipments`
  ADD COLUMN `input_method` varchar(20) DEFAULT NULL
  AFTER `total_actual_qty`,
  ALGORITHM=INPLACE, LOCK=NONE;

-- Backfill data lama sebagai 'scan'
UPDATE `outbound_shipments`
  SET `input_method` = 'scan'
  WHERE `input_method` IS NULL;

INSERT INTO `schema_migrations` (`version`) VALUES ('006_outbound_shipments_input_method')
ON DUPLICATE KEY UPDATE `version` = `version`;
