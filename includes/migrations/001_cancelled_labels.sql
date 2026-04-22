-- Migration 001 — Tabel cancelled_labels
-- Riwayat pembatalan label per (production_id, label_no) dengan kategori production/warehouse.

CREATE TABLE IF NOT EXISTS `cancelled_labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_id` int(11) NOT NULL,
  `label_no` int(11) NOT NULL,
  `category` enum('production','warehouse') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_prod_label` (`production_id`,`label_no`),
  KEY `idx_category` (`category`),
  CONSTRAINT `cancelled_labels_ibfk_1`
    FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_migrations` (`version`) VALUES ('001_cancelled_labels')
ON DUPLICATE KEY UPDATE `version` = `version`;
