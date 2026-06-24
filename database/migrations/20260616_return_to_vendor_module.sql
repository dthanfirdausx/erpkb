CREATE TABLE IF NOT EXISTS erp_vendor_return (
  id BIGINT NOT NULL AUTO_INCREMENT,
  return_no VARCHAR(40) NOT NULL,
  source_no_bpb VARCHAR(100) NOT NULL,
  source_pemasukan_id INT NULL,
  vendor_code VARCHAR(50) NULL,
  vendor_name VARCHAR(150) NULL,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  movement_type VARCHAR(10) NOT NULL DEFAULT '122',
  return_reason_code VARCHAR(30) NOT NULL,
  return_reason_text VARCHAR(255) NULL,
  reference_no VARCHAR(100) NULL,
  plant_id INT NULL,
  storage_location_id INT NULL,
  status ENUM('POSTED','CANCELLED') NOT NULL DEFAULT 'POSTED',
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_erp_vendor_return_no (return_no),
  KEY idx_erp_vendor_return_source (source_no_bpb),
  KEY idx_erp_vendor_return_vendor (vendor_code),
  KEY idx_erp_vendor_return_posting (posting_date),
  KEY idx_erp_vendor_return_status (status)
);

CREATE TABLE IF NOT EXISTS erp_vendor_return_detail (
  id BIGINT NOT NULL AUTO_INCREMENT,
  return_id BIGINT NOT NULL,
  source_detail_id INT NOT NULL,
  source_no_bpb VARCHAR(100) NOT NULL,
  line_no INT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) NULL,
  qty DECIMAL(15,5) NOT NULL,
  uom VARCHAR(20) NULL,
  price DECIMAL(18,5) NOT NULL DEFAULT 0,
  amount DECIMAL(20,5) NOT NULL DEFAULT 0,
  no_aju VARCHAR(50) NULL,
  no_dokpab VARCHAR(50) NULL,
  jenis_dokpab VARCHAR(20) NULL,
  hs_code VARCHAR(20) NULL,
  lot_no VARCHAR(50) NULL,
  stock_type VARCHAR(20) NULL,
  storage_bin_id INT NULL,
  remarks TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_erp_vendor_return_detail_header (return_id),
  KEY idx_erp_vendor_return_detail_source (source_detail_id),
  CONSTRAINT fk_erp_vendor_return_detail_header FOREIGN KEY (return_id) REFERENCES erp_vendor_return(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS erp_vendor_return_history (
  id BIGINT NOT NULL AUTO_INCREMENT,
  return_id BIGINT NOT NULL,
  status_lama VARCHAR(20) NULL,
  status_baru VARCHAR(20) NOT NULL,
  remarks TEXT NULL,
  changed_by VARCHAR(50) NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_vendor_return_history_header (return_id),
  CONSTRAINT fk_erp_vendor_return_history_header FOREIGN KEY (return_id) REFERENCES erp_vendor_return(id) ON DELETE CASCADE
);

UPDATE sys_menu
SET nav_act='return_to_vendor',
    main_table='erp_vendor_return',
    page_name='Return to Vendor',
    icon='fa-reply',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='return-to-vendor';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'return_to_vendor','Return to Vendor','return-to-vendor','erp_vendor_return','fa-reply',7,506,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='return-to-vendor');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y',
       CASE WHEN g.group_level IN ('admin','system_administrator','purchasing','gudang','quality_control') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.group_level IN ('admin','system_administrator','purchasing','gudang','quality_control') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.group_level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N'
FROM sys_menu m
JOIN (
  SELECT 'admin' group_level UNION ALL
  SELECT 'system_administrator' UNION ALL
  SELECT 'purchasing' UNION ALL
  SELECT 'gudang' UNION ALL
  SELECT 'quality_control' UNION ALL
  SELECT 'finance_akunting' UNION ALL
  SELECT 'auditor' UNION ALL
  SELECT 'beacukai'
) g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='return-to-vendor' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','purchasing','gudang','quality_control') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','purchasing','gudang','quality_control') THEN 'Y' ELSE r.update_act END
WHERE m.url='return-to-vendor'
  AND r.group_level IN ('admin','system_administrator','purchasing','gudang','quality_control','finance_akunting','auditor','beacukai');
