ALTER TABLE detail_transaksi
  ADD COLUMN IF NOT EXISTS plant_id INT NULL AFTER no_bpb,
  ADD COLUMN IF NOT EXISTS storage_location_id INT NULL AFTER plant_id,
  ADD COLUMN IF NOT EXISTS storage_bin_id INT NULL AFTER storage_location_id,
  ADD COLUMN IF NOT EXISTS stock_type VARCHAR(20) NULL AFTER storage_bin_id,
  ADD INDEX IF NOT EXISTS idx_detail_transaksi_source_location (plant_id,storage_location_id,storage_bin_id),
  ADD INDEX IF NOT EXISTS idx_detail_transaksi_stock_type (stock_type);

UPDATE detail_transaksi dt
JOIN stock_layer sl ON sl.id=dt.ref_id
SET dt.plant_id=COALESCE(dt.plant_id,sl.plant_id),
    dt.storage_location_id=COALESCE(dt.storage_location_id,sl.storage_location_id),
    dt.storage_bin_id=COALESCE(dt.storage_bin_id,sl.storage_bin_id),
    dt.stock_type=COALESCE(dt.stock_type,sl.stock_type)
WHERE dt.ref_id IS NOT NULL
  AND (dt.plant_id IS NULL OR dt.storage_location_id IS NULL OR dt.storage_bin_id IS NULL OR dt.stock_type IS NULL);

UPDATE detail_transaksi dt
JOIN stock_layer sl ON sl.ref_id=dt.ref_id AND sl.kode=dt.kd_barang
SET dt.plant_id=COALESCE(dt.plant_id,sl.plant_id),
    dt.storage_location_id=COALESCE(dt.storage_location_id,sl.storage_location_id),
    dt.storage_bin_id=COALESCE(dt.storage_bin_id,sl.storage_bin_id),
    dt.stock_type=COALESCE(dt.stock_type,sl.stock_type)
WHERE dt.ref_id IS NOT NULL
  AND (dt.plant_id IS NULL OR dt.storage_location_id IS NULL OR dt.storage_bin_id IS NULL OR dt.stock_type IS NULL);
