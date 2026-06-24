ALTER TABLE transfer
  ADD COLUMN IF NOT EXISTS destination_storage_location_id INT DEFAULT NULL AFTER ke,
  ADD COLUMN IF NOT EXISTS destination_storage_bin_id INT DEFAULT NULL AFTER destination_storage_location_id,
  ADD COLUMN IF NOT EXISTS destination_stock_type ENUM('UNRESTRICTED','QUALITY','BLOCKED') NOT NULL DEFAULT 'UNRESTRICTED' AFTER destination_storage_bin_id;

ALTER TABLE transfer_detail
  ADD COLUMN IF NOT EXISTS destination_material_code VARCHAR(100) DEFAULT NULL AFTER id_barang;

ALTER TABLE detail_transaksi
  ADD COLUMN IF NOT EXISTS destination_storage_location_id INT DEFAULT NULL AFTER no_bpb,
  ADD COLUMN IF NOT EXISTS destination_storage_bin_id INT DEFAULT NULL AFTER destination_storage_location_id,
  ADD COLUMN IF NOT EXISTS destination_stock_type VARCHAR(20) DEFAULT NULL AFTER destination_storage_bin_id,
  ADD COLUMN IF NOT EXISTS destination_material_code VARCHAR(100) DEFAULT NULL AFTER destination_stock_type;

ALTER TABLE transfer
  ADD INDEX IF NOT EXISTS idx_transfer_dest_storage_location (destination_storage_location_id),
  ADD INDEX IF NOT EXISTS idx_transfer_dest_storage_bin (destination_storage_bin_id),
  ADD INDEX IF NOT EXISTS idx_transfer_dest_stock_type (destination_stock_type);

ALTER TABLE transfer_detail
  ADD INDEX IF NOT EXISTS idx_transfer_detail_dest_material (destination_material_code);
