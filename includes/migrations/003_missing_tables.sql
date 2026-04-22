-- Migration 003 — Tabel yang dipakai kode tapi hilang dari db_aftech.sql
-- (app_settings, app_config, qc_scans). Lihat audit awal konsistensi DB.

-- 1) app_settings — key-value config (qc_checker_enabled, dll)
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default: QC checker OFF (match perilaku sekarang yang otomatis auto-transfer)
INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
  ('qc_checker_enabled', '0')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 2) app_config — daftar PIN reset device (dipakai api/get_pins.php)
CREATE TABLE IF NOT EXISTS `app_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pin_code` varchar(20) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) qc_scans — ringkasan progress QC (dipakai api/get_warehouse_stock.php)
CREATE TABLE IF NOT EXISTS `qc_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_id` int(11) NOT NULL,
  `total_scanned` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_production_id` (`production_id`),
  CONSTRAINT `qc_scans_ibfk_1`
    FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_migrations` (`version`) VALUES ('003_missing_tables')
ON DUPLICATE KEY UPDATE `version` = `version`;
