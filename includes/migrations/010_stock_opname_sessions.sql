-- Migration 010 — Stock opname sessions and adjustments

CREATE TABLE IF NOT EXISTS `stock_opname_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_code` varchar(30) NOT NULL,
  `session_name` varchar(120) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('open','closed','adjusted') NOT NULL DEFAULT 'open',
  `started_by` varchar(100) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stock_opname_session_code` (`session_code`),
  KEY `idx_stock_opname_status_started` (`status`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `stock_opname_session_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `production_id` int(11) DEFAULT NULL,
  `warehouse_item_id` int(11) DEFAULT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `label_no` int(11) DEFAULT NULL,
  `barcode_raw` varchar(180) DEFAULT NULL,
  `scan_status` enum('matched','extra','extra_unknown_batch','duplicate_in_session','invalid','missing_in_scan') NOT NULL DEFAULT 'invalid',
  `scanned_by` varchar(100) DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_action` enum('pending','add_to_stock','mark_damaged','remove_from_stock') NOT NULL DEFAULT 'pending',
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_stock_opname_item_session` (`session_id`),
  KEY `idx_stock_opname_item_lookup` (`session_id`,`batch`,`label_no`),
  KEY `idx_stock_opname_item_status` (`session_id`,`scan_status`,`resolved_action`),
  CONSTRAINT `fk_stock_opname_item_session`
    FOREIGN KEY (`session_id`) REFERENCES `stock_opname_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_opname_item_production`
    FOREIGN KEY (`production_id`) REFERENCES `production_labels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stock_opname_item_warehouse`
    FOREIGN KEY (`warehouse_item_id`) REFERENCES `warehouse_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `stock_opname_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_item_id` int(11) NOT NULL,
  `action_type` enum('add_to_stock','mark_damaged','remove_from_stock') NOT NULL,
  `action_notes` text DEFAULT NULL,
  `acted_by` varchar(100) DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_opname_adjustment_item` (`session_item_id`),
  CONSTRAINT `fk_stock_opname_adjustment_item`
    FOREIGN KEY (`session_item_id`) REFERENCES `stock_opname_session_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_migrations` (`version`) VALUES ('010_stock_opname_sessions');
