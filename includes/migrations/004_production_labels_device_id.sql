-- Migration 004 — Tambah device_id di production_labels
-- device_model sudah ada (nama/tipe HP), device_id untuk UUID unik per device
-- supaya bisa diakses via get_labels_report & get_reports berdasarkan device.

ALTER TABLE `production_labels`
  ADD COLUMN `device_id` varchar(100) DEFAULT NULL AFTER `device_model`,
  ADD INDEX `idx_device_id` (`device_id`),
  ADD INDEX `idx_device_model` (`device_model`),
  ALGORITHM=INPLACE, LOCK=NONE;

INSERT INTO `schema_migrations` (`version`) VALUES ('004_production_labels_device_id')
ON DUPLICATE KEY UPDATE `version` = `version`;
