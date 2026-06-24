CREATE TABLE IF NOT EXISTS erp_sample_issue (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_no VARCHAR(50) NOT NULL UNIQUE,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  movement_type VARCHAR(5) NOT NULL DEFAULT '333',
  reference_no VARCHAR(100) DEFAULT NULL,
  reason_code VARCHAR(50) NOT NULL,
  reason_text VARCHAR(255) NOT NULL,
  sample_type VARCHAR(50) DEFAULT NULL,
  recipient_type VARCHAR(50) DEFAULT NULL,
  recipient_name VARCHAR(150) DEFAULT NULL,
  plant_id INT DEFAULT NULL,
  storage_location_id INT DEFAULT NULL,
  storage_bin_id INT DEFAULT NULL,
  status ENUM('POSTED','REVERSED') NOT NULL DEFAULT 'POSTED',
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reversed_by VARCHAR(50) DEFAULT NULL,
  reversed_at DATETIME DEFAULT NULL,
  reversal_reason VARCHAR(255) DEFAULT NULL,
  INDEX idx_sample_posting_date (posting_date),
  INDEX idx_sample_reason (reason_code),
  INDEX idx_sample_recipient (recipient_type,recipient_name),
  INDEX idx_sample_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_sample_issue_detail (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_id BIGINT NOT NULL,
  line_no INT DEFAULT NULL,
  material_code VARCHAR(100) NOT NULL,
  material_name VARCHAR(150) DEFAULT NULL,
  qty DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  uom VARCHAR(20) DEFAULT NULL,
  stock_type VARCHAR(20) DEFAULT 'UNRESTRICTED',
  price DECIMAL(18,5) DEFAULT 0.00000,
  amount DECIMAL(18,2) DEFAULT 0.00,
  remarks VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sampled_issue (issue_id),
  INDEX idx_sampled_material (material_code),
  CONSTRAINT fk_sampled_issue FOREIGN KEY (issue_id) REFERENCES erp_sample_issue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_sample_issue_trace (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_id BIGINT NOT NULL,
  issue_detail_id BIGINT NOT NULL,
  stock_layer_id INT NOT NULL,
  material_doc_id BIGINT DEFAULT NULL,
  qty DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  price DECIMAL(18,5) DEFAULT 0.00000,
  amount DECIMAL(18,2) DEFAULT 0.00,
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
  INDEX idx_samplet_issue (issue_id),
  INDEX idx_samplet_detail (issue_detail_id),
  INDEX idx_samplet_layer (stock_layer_id),
  INDEX idx_samplet_customs (no_aju,no_dokpab),
  INDEX idx_samplet_lot (lot_no),
  CONSTRAINT fk_samplet_issue FOREIGN KEY (issue_id) REFERENCES erp_sample_issue(id) ON DELETE CASCADE,
  CONSTRAINT fk_samplet_detail FOREIGN KEY (issue_detail_id) REFERENCES erp_sample_issue_detail(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_sample_issue_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  issue_id BIGINT NOT NULL,
  status_lama VARCHAR(20) DEFAULT NULL,
  status_baru VARCHAR(20) NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  changed_by VARCHAR(50) DEFAULT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sampleh_issue (issue_id),
  CONSTRAINT fk_sampleh_issue FOREIGN KEY (issue_id) REFERENCES erp_sample_issue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rekening (no_rek,induk,level,nama_rek,kat_coa,jenis)
SELECT '62399','620',3,'Beban Sample Material',18,6
WHERE NOT EXISTS (SELECT 1 FROM rekening WHERE no_rek='62399');

SET @gi_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main' LIMIT 1);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'sample_issue','Sample Issue','sample-issue','erp_sample_issue','fa-flask',5,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='sample-issue');

UPDATE sys_menu
SET nav_act='sample_issue',
    page_name='Sample Issue',
    url='sample-issue',
    main_table='erp_sample_issue',
    icon='fa-flask',
    urutan_menu=5,
    parent=@gi_parent,
    parent_name='Goods Issue',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='sample-issue';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','gudang','finance_akunting','marketing_sales') THEN 'Y' ELSE 'N' END,
       'N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','finance_akunting','marketing_sales','auditor','manager_approver')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='sample-issue'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','gudang','finance_akunting','marketing_sales') THEN 'Y' ELSE r.insert_act END,
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='sample-issue'
  AND r.group_level IN ('admin','system_administrator','gudang','finance_akunting','marketing_sales','auditor','manager_approver');
