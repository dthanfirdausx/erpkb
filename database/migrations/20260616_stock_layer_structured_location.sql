ALTER TABLE stock_layer
  ADD COLUMN IF NOT EXISTS plant_id INT DEFAULT NULL AFTER lokasi,
  ADD COLUMN IF NOT EXISTS storage_location_id INT DEFAULT NULL AFTER plant_id,
  ADD COLUMN IF NOT EXISTS storage_bin_id INT DEFAULT NULL AFTER storage_location_id,
  ADD INDEX IF NOT EXISTS idx_stock_layer_plant (plant_id),
  ADD INDEX IF NOT EXISTS idx_stock_layer_storage_location (storage_location_id),
  ADD INDEX IF NOT EXISTS idx_stock_layer_storage_bin (storage_bin_id);

UPDATE stock_layer sl
JOIN pemasukan_detail pd ON pd.id = sl.ref_id AND sl.ref_table = 'pemasukan_detail'
JOIN pemasukan p ON p.no_bpb = pd.no_bpb
SET sl.plant_id = COALESCE(sl.plant_id, p.plant_id),
    sl.storage_location_id = COALESCE(sl.storage_location_id, p.storage_location_id),
    sl.storage_bin_id = COALESCE(sl.storage_bin_id, pd.storage_bin_id);

UPDATE stock_layer sl
JOIN erp_storage_bin b ON b.bin_code = sl.lokasi AND b.id = sl.storage_bin_id
SET sl.lokasi = 'GUDANG'
WHERE sl.ref_table = 'pemasukan_detail';

UPDATE pemasukan_detail pd
JOIN erp_storage_bin b ON b.bin_code = pd.lokasi AND b.id = pd.storage_bin_id
SET pd.lokasi = 'GUDANG';

UPDATE detail_transaksi dt
JOIN erp_storage_bin b ON b.bin_code = dt.lokasi
SET dt.lokasi = 'GUDANG'
WHERE dt.direction = 'IN'
  AND dt.ref_type IN ('PURCHASE_ORDER', 'GR_WITHOUT_PO')
  AND dt.lokasi <> 'GUDANG';
