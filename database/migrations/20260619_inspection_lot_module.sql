CREATE TABLE IF NOT EXISTS erp_inspection_lot (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  lot_no VARCHAR(40) NOT NULL,
  inspection_origin ENUM('GOODS_RECEIPT','PRODUCTION','MANUAL','TRANSFER','RETURN') NOT NULL DEFAULT 'GOODS_RECEIPT',
  inspection_type VARCHAR(20) NOT NULL DEFAULT '01',
  source_ref_type VARCHAR(50) DEFAULT NULL,
  source_ref_id BIGINT(20) DEFAULT NULL,
  source_ref_no VARCHAR(80) DEFAULT NULL,
  stock_layer_id INT(11) DEFAULT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) DEFAULT NULL,
  lot_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  sample_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  accepted_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  rejected_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  uom VARCHAR(20) DEFAULT NULL,
  plant_id INT(11) DEFAULT NULL,
  storage_location_id INT(11) DEFAULT NULL,
  storage_bin_id INT(11) DEFAULT NULL,
  stock_type VARCHAR(20) DEFAULT 'QUALITY',
  inspection_plan VARCHAR(80) DEFAULT NULL,
  batch_no VARCHAR(80) DEFAULT NULL,
  lot_status ENUM('CREATED','IN_INSPECTION','RESULT_RECORDED','UD_ACCEPTED','UD_REJECTED','UD_PARTIAL','CANCELLED') NOT NULL DEFAULT 'CREATED',
  ud_code VARCHAR(30) DEFAULT NULL,
  ud_text VARCHAR(150) DEFAULT NULL,
  ud_date DATETIME DEFAULT NULL,
  ud_by VARCHAR(100) DEFAULT NULL,
  no_aju VARCHAR(50) DEFAULT NULL,
  jenis_dokpab VARCHAR(20) DEFAULT NULL,
  no_dokpab VARCHAR(50) DEFAULT NULL,
  no_bpb VARCHAR(50) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100) DEFAULT NULL,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_inspection_lot_no (lot_no),
  KEY idx_erp_inspection_lot_material (material_code),
  KEY idx_erp_inspection_lot_date (created_at),
  KEY idx_erp_inspection_lot_status (lot_status),
  KEY idx_erp_inspection_lot_source (source_ref_type, source_ref_id),
  KEY idx_erp_inspection_lot_stock_layer (stock_layer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_inspection_lot_result (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  inspection_lot_id BIGINT(20) NOT NULL,
  characteristic_no VARCHAR(30) DEFAULT NULL,
  characteristic_name VARCHAR(150) NOT NULL,
  specification VARCHAR(150) DEFAULT NULL,
  sample_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  result_value VARCHAR(150) DEFAULT NULL,
  result_status ENUM('PASS','FAIL','INFO') NOT NULL DEFAULT 'INFO',
  defect_code VARCHAR(50) DEFAULT NULL,
  defect_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  remarks VARCHAR(255) DEFAULT NULL,
  recorded_by VARCHAR(100) DEFAULT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_inspection_lot_result_lot (inspection_lot_id),
  CONSTRAINT fk_erp_inspection_lot_result_lot FOREIGN KEY (inspection_lot_id) REFERENCES erp_inspection_lot(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='inspection_lot',
    page_name='Inspection Lot',
    main_table='erp_inspection_lot',
    icon='fa-clipboard',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='inspection-lot';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'Y', 'Y'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='inspection-lot'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.delete_act='Y', r.import_act='Y'
WHERE m.url='inspection-lot';
