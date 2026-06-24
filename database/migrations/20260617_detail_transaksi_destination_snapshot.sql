UPDATE detail_transaksi
SET destination_material_code = kd_barang
WHERE (destination_material_code IS NULL OR destination_material_code = '')
  AND kd_barang IS NOT NULL
  AND kd_barang <> '';

UPDATE detail_transaksi
SET destination_storage_location_id = COALESCE(destination_storage_location_id, storage_location_id),
    destination_storage_bin_id = COALESCE(destination_storage_bin_id, storage_bin_id),
    destination_stock_type = COALESCE(destination_stock_type, stock_type)
WHERE direction = 'IN'
  AND (
    destination_storage_location_id IS NULL
    OR destination_storage_bin_id IS NULL
    OR destination_stock_type IS NULL
  );

ALTER TABLE detail_transaksi
  ADD INDEX IF NOT EXISTS idx_detail_transaksi_destination_location (destination_storage_location_id,destination_storage_bin_id),
  ADD INDEX IF NOT EXISTS idx_detail_transaksi_destination_material (destination_material_code);
