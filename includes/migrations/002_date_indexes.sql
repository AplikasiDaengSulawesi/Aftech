-- Migration 002 — Index kolom tanggal untuk dashboard query
-- Pakai ALGORITHM=INPLACE supaya tidak mengunci tabel (MariaDB/MySQL 5.6+).
-- Jalankan satu per satu. Jika index sudah ada, MySQL akan error — abaikan saja.

ALTER TABLE `production_labels`
  ADD INDEX `idx_production_date` (`production_date`),
  ALGORITHM=INPLACE, LOCK=NONE;

ALTER TABLE `warehouse_items`
  ADD INDEX `idx_transferred_at` (`transferred_at`),
  ALGORITHM=INPLACE, LOCK=NONE;

ALTER TABLE `outbound_shipments`
  ADD INDEX `idx_shipment_date` (`shipment_date`),
  ALGORITHM=INPLACE, LOCK=NONE;

INSERT INTO `schema_migrations` (`version`) VALUES ('002_date_indexes')
ON DUPLICATE KEY UPDATE `version` = `version`;
