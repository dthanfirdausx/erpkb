CREATE TABLE IF NOT EXISTS erp_issue_production (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_no VARCHAR(50) NOT NULL UNIQUE,
  production_id INT DEFAULT NULL,
  production_no VARCHAR(100) DEFAULT NULL,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  movement_type VARCHAR(5) NOT NULL DEFAULT '261',
  reference_no VARCHAR(100) DEFAULT NULL,
  reason_code VARCHAR(50) DEFAULT NULL,
  reason_text VARCHAR(255) DEFAULT NULL,
  plant_id INT DEFAULT NULL,
  storage_location_id INT DEFAULT NULL,
  storage_bin_id INT DEFAULT NULL,
  status ENUM('POSTED','REVERSED') NOT NULL DEFAULT 'POSTED',
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reversed_by VARCHAR(50) DEFAULT NULL,
  reversed_at DATETIME DEFAULT NULL,
  reversal_reason VARCHAR(255) DEFAULT NULL,
  INDEX idx_gip_posting_date (posting_date),
  INDEX idx_gip_production (production_id, production_no),
  INDEX idx_gip_status (status)
);

CREATE TABLE IF NOT EXISTS erp_issue_production_detail (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_id BIGINT NOT NULL,
  production_detail_id INT DEFAULT NULL,
  line_no INT DEFAULT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(150) DEFAULT NULL,
  planned_qty DECIMAL(15,5) DEFAULT 0,
  issued_qty DECIMAL(15,5) NOT NULL DEFAULT 0,
  uom VARCHAR(20) DEFAULT NULL,
  stock_type VARCHAR(20) DEFAULT 'UNRESTRICTED',
  remarks VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gipd_issue (issue_id),
  INDEX idx_gipd_material (material_code),
  INDEX idx_gipd_prod_detail (production_detail_id),
  CONSTRAINT fk_gipd_issue FOREIGN KEY (issue_id) REFERENCES erp_issue_production(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS erp_issue_production_trace (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_id BIGINT NOT NULL,
  issue_detail_id BIGINT NOT NULL,
  stock_layer_id INT NOT NULL,
  material_doc_id BIGINT DEFAULT NULL,
  qty DECIMAL(15,5) NOT NULL DEFAULT 0,
  stock_type VARCHAR(20) DEFAULT NULL,
  plant_id INT DEFAULT NULL,
  storage_location_id INT DEFAULT NULL,
  storage_bin_id INT DEFAULT NULL,
  no_bpb VARCHAR(50) DEFAULT NULL,
  no_aju VARCHAR(50) DEFAULT NULL,
  no_dokpab VARCHAR(50) DEFAULT NULL,
  jenis_dokpab VARCHAR(20) DEFAULT NULL,
  hs_code VARCHAR(20) DEFAULT NULL,
  lot_no VARCHAR(50) DEFAULT NULL,
  source_ref_table VARCHAR(50) DEFAULT NULL,
  source_ref_id INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_gipt_issue (issue_id),
  INDEX idx_gipt_detail (issue_detail_id),
  INDEX idx_gipt_layer (stock_layer_id),
  INDEX idx_gipt_customs (no_aju, no_dokpab),
  INDEX idx_gipt_lot (lot_no),
  CONSTRAINT fk_gipt_issue FOREIGN KEY (issue_id) REFERENCES erp_issue_production(id) ON DELETE CASCADE,
  CONSTRAINT fk_gipt_detail FOREIGN KEY (issue_detail_id) REFERENCES erp_issue_production_detail(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS erp_issue_production_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_id BIGINT NOT NULL,
  status_lama VARCHAR(20) DEFAULT NULL,
  status_baru VARCHAR(20) NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  changed_by VARCHAR(50) DEFAULT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_giph_issue (issue_id),
  CONSTRAINT fk_giph_issue FOREIGN KEY (issue_id) REFERENCES erp_issue_production(id) ON DELETE CASCADE
);

SET @gi_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main' LIMIT 1);

UPDATE sys_menu
SET nav_act='issue_to_production',
    page_name='Issue to Production',
    url='issue-to-production',
    main_table='erp_issue_production',
    icon='fa-industry',
    urutan_menu=1,
    parent=@gi_parent,
    parent_name='Goods Issue',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='issue-to-production';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','Y','N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','beacukai','auditor')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='issue-to-production'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','gudang','produksi','ppic') THEN 'Y' ELSE r.insert_act END,
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='issue-to-production'
  AND r.group_level IN ('admin','system_administrator','gudang','produksi','ppic','beacukai','auditor');
