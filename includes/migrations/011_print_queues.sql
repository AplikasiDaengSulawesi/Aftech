-- Migration 011 — Tabel antrian cetak (print_queues)

CREATE TABLE IF NOT EXISTS `print_queues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `production_id` int(11) NOT NULL,
  `batch` varchar(100) NOT NULL,
  `label_no` int(11) NOT NULL,
  `qr_code` varchar(150) NOT NULL,
  `status` enum('pending','printed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `printed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_print_queues_status` (`status`),
  KEY `idx_print_queues_production_id` (`production_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_migrations` (`version`) VALUES ('011_print_queues')
ON DUPLICATE KEY UPDATE `version` = `version`;