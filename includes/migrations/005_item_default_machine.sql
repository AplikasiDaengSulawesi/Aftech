-- Migration 005 — Relasi master_items → master_machines
-- Tambah default_machine_id di master_items: mesin yang biasanya
-- memproduksi item tersebut. Dipakai oleh form "Tambah Stok" supaya
-- saat user pilih item, mesin otomatis terisi (masih bisa di-override).

-- Step 1: ADD COLUMN + INDEX, online (non-blocking)
ALTER TABLE `master_items`
  ADD COLUMN `default_machine_id` int(11) DEFAULT NULL AFTER `unit_id`,
  ADD INDEX `idx_default_machine` (`default_machine_id`),
  ALGORITHM=INPLACE, LOCK=NONE;

-- Step 2: ADD FOREIGN KEY — dipisah karena ADD FK di MariaDB/MySQL
-- tidak selalu support LOCK=NONE (butuh foreign_key_checks=0).
-- master_items tabel kecil, SHARED lock sesaat tidak masalah.
ALTER TABLE `master_items`
  ADD CONSTRAINT `master_items_ibfk_machine`
    FOREIGN KEY (`default_machine_id`) REFERENCES `master_machines` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

INSERT INTO `schema_migrations` (`version`) VALUES ('005_item_default_machine')
ON DUPLICATE KEY UPDATE `version` = `version`;
