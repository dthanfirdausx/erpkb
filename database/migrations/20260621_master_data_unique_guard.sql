-- Master data uniqueness guard.
-- Apply only after duplicate audit returns zero rows.

SET @idx_barang := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='barang' AND index_name='uk_barang_kd_barang'
);
SET @sql_barang := IF(@idx_barang=0,
  'ALTER TABLE barang ADD UNIQUE KEY uk_barang_kd_barang (kd_barang)',
  'SELECT ''uk_barang_kd_barang already exists'' info'
);
PREPARE stmt FROM @sql_barang;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_user := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='sys_users' AND index_name='uk_sys_users_username'
);
SET @sql_user := IF(@idx_user=0,
  'ALTER TABLE sys_users ADD UNIQUE KEY uk_sys_users_username (username)',
  'SELECT ''uk_sys_users_username already exists'' info'
);
PREPARE stmt FROM @sql_user;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_packing := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='satuan_packing' AND index_name='uk_satuan_packing_name'
);
SET @sql_packing := IF(@idx_packing=0,
  'ALTER TABLE satuan_packing ADD UNIQUE KEY uk_satuan_packing_name (satuan_packing)',
  'SELECT ''uk_satuan_packing_name already exists'' info'
);
PREPARE stmt FROM @sql_packing;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
