CREATE TABLE IF NOT EXISTS erp_stock_type_transfer (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transfer_no VARCHAR(50) NOT NULL UNIQUE,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  movement_type VARCHAR(5) NOT NULL,
  source_plant_id INT DEFAULT NULL,
  source_storage_location_id INT NOT NULL,
  source_storage_bin_id INT DEFAULT NULL,
  source_stock_type VARCHAR(20) NOT NULL,
  destination_plant_id INT DEFAULT NULL,
  destination_storage_location_id INT NOT NULL,
  destination_storage_bin_id INT DEFAULT NULL,
  destination_stock_type VARCHAR(20) NOT NULL,
  reference_no VARCHAR(100) DEFAULT NULL,
  reason_code VARCHAR(50) DEFAULT NULL,
  reason_text VARCHAR(255) DEFAULT NULL,
  status ENUM('POSTED','REVERSED') NOT NULL DEFAULT 'POSTED',
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reversed_by VARCHAR(50) DEFAULT NULL,
  reversed_at DATETIME DEFAULT NULL,
  reversal_reason VARCHAR(255) DEFAULT NULL,
  INDEX idx_stt_posting_date (posting_date),
  INDEX idx_stt_source (source_storage_location_id,source_storage_bin_id,source_stock_type),
  INDEX idx_stt_destination (destination_storage_location_id,destination_storage_bin_id,destination_stock_type),
  INDEX idx_stt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_stock_type_transfer_detail (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transfer_id BIGINT NOT NULL,
  line_no INT NOT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(255) DEFAULT NULL,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  uom VARCHAR(20) DEFAULT NULL,
  price DECIMAL(18,5) DEFAULT 0.00000,
  amount DECIMAL(18,2) DEFAULT 0.00,
  remarks VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sttd_transfer (transfer_id),
  INDEX idx_sttd_material (material_code),
  CONSTRAINT fk_sttd_transfer FOREIGN KEY (transfer_id) REFERENCES erp_stock_type_transfer(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_stock_type_transfer_trace (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transfer_id BIGINT NOT NULL,
  transfer_detail_id BIGINT NOT NULL,
  source_stock_layer_id INT NOT NULL,
  destination_stock_layer_id INT DEFAULT NULL,
  material_doc_out_id BIGINT DEFAULT NULL,
  material_doc_in_id BIGINT DEFAULT NULL,
  qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  price DECIMAL(18,5) DEFAULT 0.00000,
  amount DECIMAL(18,2) DEFAULT 0.00,
  source_stock_type VARCHAR(20) DEFAULT NULL,
  destination_stock_type VARCHAR(20) DEFAULT NULL,
  source_plant_id INT DEFAULT NULL,
  source_storage_location_id INT DEFAULT NULL,
  source_storage_bin_id INT DEFAULT NULL,
  destination_plant_id INT DEFAULT NULL,
  destination_storage_location_id INT DEFAULT NULL,
  destination_storage_bin_id INT DEFAULT NULL,
  no_bpb VARCHAR(50) DEFAULT NULL,
  no_aju VARCHAR(50) DEFAULT NULL,
  jenis_dokpab VARCHAR(20) DEFAULT NULL,
  no_dokpab VARCHAR(50) DEFAULT NULL,
  source_ref_table VARCHAR(50) DEFAULT NULL,
  source_ref_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sttt_transfer (transfer_id),
  INDEX idx_sttt_detail (transfer_detail_id),
  INDEX idx_sttt_source_layer (source_stock_layer_id),
  INDEX idx_sttt_dest_layer (destination_stock_layer_id),
  INDEX idx_sttt_customs (no_aju,no_dokpab),
  CONSTRAINT fk_sttt_transfer FOREIGN KEY (transfer_id) REFERENCES erp_stock_type_transfer(id) ON DELETE CASCADE,
  CONSTRAINT fk_sttt_detail FOREIGN KEY (transfer_detail_id) REFERENCES erp_stock_type_transfer_detail(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_stock_type_transfer_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transfer_id BIGINT NOT NULL,
  status_lama VARCHAR(20) DEFAULT NULL,
  status_baru VARCHAR(20) NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  changed_by VARCHAR(50) DEFAULT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_stth_transfer (transfer_id),
  CONSTRAINT fk_stth_transfer FOREIGN KEY (transfer_id) REFERENCES erp_stock_type_transfer(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='stock_type_transfer',
    page_name='Stock Type Transfer',
    url='stock-type-transfer',
    main_table='erp_stock_type_transfer',
    icon='fa-tags',
    urutan_menu=4,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='stock-type-transfer';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'stock_type_transfer','Stock Type Transfer','stock-type-transfer','erp_stock_type_transfer','fa-tags',4,p.id,'Stock Transfer','Y','Y','page'
FROM sys_menu p
WHERE p.parent=334 AND p.page_name='Stock Transfer' AND p.type_menu='main'
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='stock-type-transfer')
LIMIT 1;

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE 'N' END,
       'N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','ppic','produksi','auditor','finance_akunting','beacukai','manager_approver')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='stock-type-transfer'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE r.insert_act END,
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='stock-type-transfer';
