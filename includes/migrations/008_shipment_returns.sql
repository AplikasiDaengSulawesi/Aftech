-- Migration 008 — Riwayat return pengiriman (parent + child)
-- Analog dengan relasi: outbound_shipment_batches <-> distributor_shipments
--
-- shipment_return_batches (PARENT, satu baris per event return per batch+kondisi):
--   id, shipment_id, production_id, condition_status (utuh/rusak),
--   label_qty, unit_qty, reason, returned_by, returned_at
--
-- shipment_returns (CHILD, satu baris per label dalam event):
--   id, return_batch_id (FK ke parent), label_no
--
-- condition_status:
--   utuh  = label dikembalikan ke gudang sebagai stok tersedia
--   rusak = label hanya tercatat sebagai return, TIDAK dikembalikan ke stok

-- 1. PARENT
CREATE TABLE IF NOT EXISTS `shipment_return_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `condition_status` enum('utuh','rusak') NOT NULL DEFAULT 'utuh',
  `label_qty` int(11) NOT NULL DEFAULT 0,
  `unit_qty` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL,
  `returned_by` varchar(100) DEFAULT NULL,
  `returned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shipment_id` (`shipment_id`),
  KEY `idx_production_id` (`production_id`),
  KEY `idx_condition` (`condition_status`),
  KEY `idx_returned_at` (`returned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. CHILD (slim: hanya FK + label_no)
CREATE TABLE IF NOT EXISTS `shipment_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_batch_id` int(11) DEFAULT NULL,
  `label_no` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_return_batch_id` (`return_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_migrations` (`version`) VALUES ('008_shipment_returns')
ON DUPLICATE KEY UPDATE `version` = `version`;
