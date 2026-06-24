-- GR Blocked Stock (movement type 103) module support.

ALTER TABLE stock_layer
  ADD COLUMN IF NOT EXISTS stock_type ENUM('UNRESTRICTED','QUALITY','BLOCKED') NOT NULL DEFAULT 'UNRESTRICTED' AFTER lokasi,
  ADD INDEX IF NOT EXISTS idx_stock_layer_stock_type (stock_type);

UPDATE stock_layer
SET stock_type = 'UNRESTRICTED'
WHERE stock_type IS NULL OR stock_type = '';

CREATE OR REPLACE VIEW v_stock_transaksi AS
SELECT
  MIN(sl.id) AS id,
  b.id AS id_barang,
  sl.kode AS kd_barang,
  b.nm_barang AS nm_barang,
  b.satuan AS satuan,
  b.kd_kategori AS kd_kategori,
  k.nm_kategori AS nm_kategori,
  SUM(sl.qty_sisa) AS stock,
  sl.plant_id AS plant_id,
  sl.storage_location_id AS storage_location_id,
  sl.storage_bin_id AS storage_bin_id,
  p.plant_code AS plant_code,
  CONCAT(s.storage_code, ' - ', s.storage_name) AS storage_location,
  CONCAT(bin.bin_code, ' - ', bin.bin_name) AS storage_bin
FROM stock_layer sl
LEFT JOIN barang b ON b.kd_barang = sl.kode
LEFT JOIN kategori k ON k.kd_kategori = b.kd_kategori
LEFT JOIN erp_plant p ON p.id = sl.plant_id
LEFT JOIN erp_storage_location s ON s.id = sl.storage_location_id
LEFT JOIN erp_storage_bin bin ON bin.id = sl.storage_bin_id
WHERE sl.lokasi = 'GUDANG'
  AND COALESCE(sl.stock_type, 'UNRESTRICTED') = 'UNRESTRICTED'
GROUP BY sl.kode,b.id,b.nm_barang,b.satuan,b.kd_kategori,k.nm_kategori,sl.plant_id,sl.storage_location_id,sl.storage_bin_id,p.plant_code,s.storage_code,s.storage_name,bin.bin_code,bin.bin_name
HAVING SUM(sl.qty_sisa) > 0;

UPDATE sys_menu
SET nav_act='gr_blocked_stock',
    main_table='pemasukan',
    page_name='GR Blocked Stock (103)',
    icon='fa-lock',
    tampil='Y'
WHERE url='gr-blocked-stock';
