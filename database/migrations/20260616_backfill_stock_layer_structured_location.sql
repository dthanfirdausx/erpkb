INSERT IGNORE INTO erp_storage_bin (bin_code, storage_location_id, bin_name, zone, status)
SELECT 'DEFAULT', s.id, CONCAT('Default Bin ', s.storage_code), 'DEFAULT', 'Aktif'
FROM erp_storage_location s
LEFT JOIN erp_storage_bin b ON b.storage_location_id = s.id
WHERE b.id IS NULL;

UPDATE barang b
JOIN erp_plant p ON p.plant_code = 'PL01'
SET b.plant_id = p.id
WHERE b.plant_id IS NULL;

UPDATE barang b
JOIN erp_storage_location s ON s.storage_code = CASE
  WHEN b.kd_kategori = 'K02' THEN 'FG01'
  WHEN b.kd_kategori = 'K07' THEN 'WIP1'
  WHEN b.kd_kategori = 'K04' THEN 'SCR1'
  ELSE 'RM01'
END
SET b.default_storage_location_id = s.id
WHERE b.default_storage_location_id IS NULL;

UPDATE stock_layer sl
LEFT JOIN pemasukan_detail pd ON pd.id = sl.ref_id AND sl.ref_table = 'pemasukan_detail'
LEFT JOIN pemasukan p ON p.no_bpb = COALESCE(sl.no_bpb, pd.no_bpb)
LEFT JOIN barang br ON br.kd_barang = sl.kode
SET sl.plant_id = COALESCE(sl.plant_id, p.plant_id, br.plant_id),
    sl.storage_location_id = COALESCE(sl.storage_location_id, p.storage_location_id, br.default_storage_location_id),
    sl.storage_bin_id = COALESCE(sl.storage_bin_id, pd.storage_bin_id)
WHERE sl.plant_id IS NULL
   OR sl.storage_location_id IS NULL
   OR sl.storage_bin_id IS NULL;

UPDATE stock_layer sl
LEFT JOIN erp_storage_bin selected_bin ON selected_bin.id = sl.storage_bin_id
JOIN (
  SELECT storage_location_id, MIN(id) AS default_bin_id
  FROM erp_storage_bin
  WHERE status = 'Aktif'
  GROUP BY storage_location_id
) default_bin ON default_bin.storage_location_id = sl.storage_location_id
SET sl.storage_bin_id = default_bin.default_bin_id
WHERE sl.storage_bin_id IS NULL
   OR selected_bin.storage_location_id <> sl.storage_location_id;
