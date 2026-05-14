-- Migration 009 — Nomor Surat Jalan persisten per pengiriman
-- Format: {seq:03d}/SJ-AM/{bulan_romawi}/{tahun}, contoh: 067/SJ-AM/V/2026
-- Counter di-reset tiap bulan, mengikuti shipment_date.
-- Dialokasikan saat surat jalan pertama kali dicetak (lazy), bukan saat shipment dibuat.
-- UNIQUE supaya tidak pernah ada 2 shipment dengan no surat jalan sama.

ALTER TABLE `outbound_shipments`
  ADD COLUMN `surat_jalan_no` VARCHAR(20) DEFAULT NULL AFTER `input_method`,
  ADD UNIQUE KEY `uq_surat_jalan_no` (`surat_jalan_no`);

INSERT INTO `schema_migrations` (`version`) VALUES ('009_outbound_shipments_surat_jalan_no')
ON DUPLICATE KEY UPDATE `version` = `version`;
