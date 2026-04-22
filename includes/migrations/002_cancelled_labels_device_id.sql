-- Migration 002 — Tambah device_id ke cancelled_labels
-- Menyimpan UUID device yang melakukan pembatalan (dari mobile app).
-- Nullable agar backward-compatible dengan row lama & pembatalan dari web admin.

ALTER TABLE `cancelled_labels`
  ADD COLUMN `device_id` varchar(100) DEFAULT NULL AFTER `cancelled_by`,
  ADD KEY `idx_device_id` (`device_id`);

INSERT INTO `schema_migrations` (`version`) VALUES ('002_cancelled_labels_device_id')
ON DUPLICATE KEY UPDATE `version` = `version`;
