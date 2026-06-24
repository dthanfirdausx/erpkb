-- Urgent ERP master-data foundation.
-- Safe to run repeatedly on MariaDB used by this application.

CREATE TABLE IF NOT EXISTS erp_plant (
  id INT NOT NULL AUTO_INCREMENT,
  plant_code VARCHAR(10) NOT NULL,
  plant_name VARCHAR(100) NOT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  country VARCHAR(3) NOT NULL DEFAULT 'ID',
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_plant_code (plant_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_storage_location (
  id INT NOT NULL AUTO_INCREMENT,
  storage_code VARCHAR(10) NOT NULL,
  plant_id INT NOT NULL,
  storage_name VARCHAR(100) NOT NULL,
  storage_type ENUM('RAW_MATERIAL','WIP','FINISHED_GOODS','SCRAP','GENERAL') NOT NULL DEFAULT 'GENERAL',
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_storage_location (plant_id, storage_code),
  KEY idx_erp_storage_location_plant (plant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_storage_bin (
  id INT NOT NULL AUTO_INCREMENT,
  bin_code VARCHAR(20) NOT NULL,
  storage_location_id INT NOT NULL,
  bin_name VARCHAR(100) NOT NULL,
  zone VARCHAR(50) DEFAULT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_storage_bin (storage_location_id, bin_code),
  KEY idx_erp_storage_bin_location (storage_location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_material_type (
  id INT NOT NULL AUTO_INCREMENT,
  type_code VARCHAR(10) NOT NULL,
  type_name VARCHAR(100) NOT NULL,
  inventory_managed ENUM('Ya','Tidak') NOT NULL DEFAULT 'Ya',
  valuation_managed ENUM('Ya','Tidak') NOT NULL DEFAULT 'Ya',
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_material_type_code (type_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_material_group (
  id INT NOT NULL AUTO_INCREMENT,
  group_code VARCHAR(20) NOT NULL,
  group_name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_material_group_code (group_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_purchasing_organization (
  id INT NOT NULL AUTO_INCREMENT,
  org_code VARCHAR(10) NOT NULL,
  org_name VARCHAR(100) NOT NULL,
  plant_id INT DEFAULT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_purchasing_org_code (org_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_purchasing_group (
  id INT NOT NULL AUTO_INCREMENT,
  group_code VARCHAR(10) NOT NULL,
  group_name VARCHAR(100) NOT NULL,
  purchasing_org_id INT DEFAULT NULL,
  buyer_name VARCHAR(100) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_purchasing_group_code (group_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_sales_organization (
  id INT NOT NULL AUTO_INCREMENT,
  org_code VARCHAR(10) NOT NULL,
  org_name VARCHAR(100) NOT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_sales_org_code (org_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_distribution_channel (
  id INT NOT NULL AUTO_INCREMENT,
  channel_code VARCHAR(10) NOT NULL,
  channel_name VARCHAR(100) NOT NULL,
  sales_org_id INT NOT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_distribution_channel (sales_org_id, channel_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_shipping_point (
  id INT NOT NULL AUTO_INCREMENT,
  shipping_code VARCHAR(10) NOT NULL,
  shipping_name VARCHAR(100) NOT NULL,
  plant_id INT NOT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_shipping_point_code (shipping_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_cost_center (
  id INT NOT NULL AUTO_INCREMENT,
  cost_center_code VARCHAR(20) NOT NULL,
  cost_center_name VARCHAR(100) NOT NULL,
  department_code VARCHAR(20) DEFAULT NULL,
  valid_from DATE NOT NULL,
  valid_to DATE NOT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_cost_center_code (cost_center_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_profit_center (
  id INT NOT NULL AUTO_INCREMENT,
  profit_center_code VARCHAR(20) NOT NULL,
  profit_center_name VARCHAR(100) NOT NULL,
  valid_from DATE NOT NULL,
  valid_to DATE NOT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_profit_center_code (profit_center_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_tax_code (
  id INT NOT NULL AUTO_INCREMENT,
  tax_code VARCHAR(20) NOT NULL,
  tax_name VARCHAR(100) NOT NULL,
  tax_type ENUM('INPUT','OUTPUT','WITHHOLDING') NOT NULL,
  rate DECIMAL(9,4) NOT NULL DEFAULT 0,
  valid_from DATE NOT NULL,
  valid_to DATE NOT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_tax_code (tax_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_exchange_rate (
  id INT NOT NULL AUTO_INCREMENT,
  currency_code VARCHAR(10) NOT NULL,
  rate_date DATE NOT NULL,
  rate_type VARCHAR(10) NOT NULL DEFAULT 'M',
  rate_to_idr DECIMAL(20,6) NOT NULL,
  source VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_exchange_rate (currency_code, rate_date, rate_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE barang ADD COLUMN IF NOT EXISTS material_type_id INT DEFAULT NULL AFTER type;
ALTER TABLE barang ADD COLUMN IF NOT EXISTS material_group_id INT DEFAULT NULL AFTER material_type_id;
ALTER TABLE barang ADD COLUMN IF NOT EXISTS plant_id INT DEFAULT NULL AFTER material_group_id;
ALTER TABLE barang ADD COLUMN IF NOT EXISTS default_storage_location_id INT DEFAULT NULL AFTER plant_id;
ALTER TABLE barang ADD INDEX IF NOT EXISTS idx_barang_material_type (material_type_id);
ALTER TABLE barang ADD INDEX IF NOT EXISTS idx_barang_material_group (material_group_id);
ALTER TABLE barang ADD INDEX IF NOT EXISTS idx_barang_plant (plant_id);

INSERT IGNORE INTO erp_plant (plant_code, plant_name, company_name, country)
VALUES ('PL01', 'Main Plant', 'PT Kemenangan', 'ID');

INSERT IGNORE INTO erp_storage_location (storage_code, plant_id, storage_name, storage_type)
SELECT 'RM01', id, 'Raw Material Warehouse', 'RAW_MATERIAL' FROM erp_plant WHERE plant_code='PL01';
INSERT IGNORE INTO erp_storage_location (storage_code, plant_id, storage_name, storage_type)
SELECT 'WIP1', id, 'Work In Process', 'WIP' FROM erp_plant WHERE plant_code='PL01';
INSERT IGNORE INTO erp_storage_location (storage_code, plant_id, storage_name, storage_type)
SELECT 'FG01', id, 'Finished Goods Warehouse', 'FINISHED_GOODS' FROM erp_plant WHERE plant_code='PL01';
INSERT IGNORE INTO erp_storage_location (storage_code, plant_id, storage_name, storage_type)
SELECT 'SCR1', id, 'Scrap Warehouse', 'SCRAP' FROM erp_plant WHERE plant_code='PL01';

INSERT IGNORE INTO erp_material_type (type_code, type_name) VALUES
('ROH', 'Raw Material'), ('HALB', 'Semi Finished Material'),
('FERT', 'Finished Product'), ('HIBE', 'Operating Supplies'), ('SCRP', 'Scrap Material');

INSERT IGNORE INTO erp_material_group (group_code, group_name, description)
SELECT kd_kategori, nm_kategori, 'Migrated from legacy material category' FROM kategori;

INSERT IGNORE INTO erp_purchasing_organization (org_code, org_name, plant_id)
SELECT 'PO01', 'Main Purchasing Organization', id FROM erp_plant WHERE plant_code='PL01';
INSERT IGNORE INTO erp_purchasing_group (group_code, group_name, purchasing_org_id)
SELECT 'PG01', 'General Purchasing', id FROM erp_purchasing_organization WHERE org_code='PO01';
INSERT IGNORE INTO erp_sales_organization (org_code, org_name, company_name)
VALUES ('SO01', 'Main Sales Organization', 'PT Kemenangan');
INSERT IGNORE INTO erp_distribution_channel (channel_code, channel_name, sales_org_id)
SELECT '10', 'Direct Sales', id FROM erp_sales_organization WHERE org_code='SO01';
INSERT IGNORE INTO erp_shipping_point (shipping_code, shipping_name, plant_id)
SELECT 'SP01', 'Main Shipping Point', id FROM erp_plant WHERE plant_code='PL01';

INSERT IGNORE INTO erp_cost_center (cost_center_code, cost_center_name, department_code, valid_from, valid_to)
SELECT CONCAT('CC-', REPLACE(kd_dept, 'DEP-', '')), nm_dept, kd_dept, '2026-01-01', '9999-12-31' FROM dept;
INSERT IGNORE INTO erp_profit_center (profit_center_code, profit_center_name, valid_from, valid_to)
VALUES ('PC-0001', 'Main Operations', '2026-01-01', '9999-12-31');

INSERT IGNORE INTO erp_tax_code (tax_code, tax_name, tax_type, rate, valid_from, valid_to) VALUES
('PPN-OUT', 'PPN Keluaran', 'OUTPUT', 11.0000, '2026-01-01', '9999-12-31'),
('PPN-IN', 'PPN Masukan', 'INPUT', 11.0000, '2026-01-01', '9999-12-31'),
('PPH23', 'PPh Pasal 23', 'WITHHOLDING', 2.0000, '2026-01-01', '9999-12-31');
INSERT IGNORE INTO erp_exchange_rate (currency_code, rate_date, rate_type, rate_to_idr, source)
VALUES ('IDR', '2026-06-15', 'M', 1.000000, 'Base currency');

INSERT IGNORE INTO erp_shift (kode_shift, nama_shift, jam_mulai, jam_selesai, status) VALUES
('SHIFT-1', 'Shift 1', '07:00:00', '15:00:00', 'Aktif'),
('SHIFT-2', 'Shift 2', '15:00:00', '23:00:00', 'Aktif'),
('SHIFT-3', 'Shift 3', '23:00:00', '07:00:00', 'Aktif');

INSERT IGNORE INTO erp_factory_calendar (tanggal, nama_hari, tipe_hari, keterangan)
WITH RECURSIVE dates AS (
  SELECT DATE('2026-01-01') AS d
  UNION ALL
  SELECT DATE_ADD(d, INTERVAL 1 DAY) FROM dates WHERE d < '2027-12-31'
)
SELECT d,
       ELT(DAYOFWEEK(d), 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
       IF(DAYOFWEEK(d) IN (1,7), 'Libur', 'Kerja'),
       'Baseline kalender pabrik; hari libur nasional perlu diverifikasi'
FROM dates;

-- Normalize legacy material status and unit references.
UPDATE barang SET status=1 WHERE status IS NULL;
UPDATE barang SET satuan='KGM' WHERE satuan='KG';
UPDATE barang SET satuan='ROL' WHERE satuan='RO';
UPDATE barang SET satuan='PCE' WHERE satuan='x';
INSERT IGNORE INTO satuan (kode, jenis, nama) VALUES ('ERP-ROL', 'ROL', 'Roll');

-- Connect existing material records to the new organizational master data.
UPDATE barang b
JOIN erp_material_group g ON g.group_code=b.kd_kategori
SET b.material_group_id=g.id
WHERE b.material_group_id IS NULL;
UPDATE barang b
JOIN erp_material_type t ON t.type_code=CASE
  WHEN b.kd_kategori='K01' THEN 'ROH'
  WHEN b.kd_kategori='K02' THEN 'FERT'
  WHEN b.kd_kategori='K04' THEN 'SCRP'
  WHEN b.kd_kategori='K07' THEN 'HALB'
  ELSE 'HIBE' END
SET b.material_type_id=t.id
WHERE b.material_type_id IS NULL;
UPDATE barang b
JOIN erp_plant p ON p.plant_code='PL01'
SET b.plant_id=p.id
WHERE b.plant_id IS NULL;
UPDATE barang b
JOIN erp_plant p ON p.id=b.plant_id
JOIN erp_storage_location s ON s.plant_id=p.id AND s.storage_code=CASE
  WHEN b.kd_kategori='K01' THEN 'RM01'
  WHEN b.kd_kategori='K02' THEN 'FG01'
  WHEN b.kd_kategori='K04' THEN 'SCR1'
  WHEN b.kd_kategori='K07' THEN 'WIP1'
  ELSE '' END
SET b.default_storage_location_id=s.id
WHERE b.default_storage_location_id IS NULL;
