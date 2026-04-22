-- Migration 000 — Tabel tracker migrasi
-- Mencegah migrasi di-apply dua kali. Setiap file migrasi diakhiri dengan
-- INSERT ... ON DUPLICATE KEY UPDATE ke tabel ini sebagai penanda.
-- Cek status: SELECT * FROM schema_migrations ORDER BY version;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(50) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_migrations` (`version`) VALUES ('000_schema_migrations')
ON DUPLICATE KEY UPDATE `version` = `version`;
