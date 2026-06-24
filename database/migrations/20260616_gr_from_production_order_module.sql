-- GR from Production Order module based on Production Confirmation.

CREATE TABLE IF NOT EXISTS erp_gr_production (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  gr_no varchar(50) NOT NULL,
  id_confirmation bigint(20) NOT NULL,
  id_production_order bigint(20) NOT NULL,
  no_production_order varchar(30) NOT NULL,
  confirmation_no varchar(30) DEFAULT NULL,
  document_date date NOT NULL,
  posting_date date NOT NULL,
  movement_type varchar(10) NOT NULL DEFAULT '101',
  plant_id int(11) DEFAULT NULL,
  storage_location_id int(11) DEFAULT NULL,
  storage_bin_id int(11) DEFAULT NULL,
  stock_type enum('UNRESTRICTED','QUALITY','BLOCKED') NOT NULL DEFAULT 'UNRESTRICTED',
  status enum('POSTED','REVERSED') NOT NULL DEFAULT 'POSTED',
  remarks varchar(255) DEFAULT NULL,
  created_by varchar(100) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  reversed_by varchar(100) DEFAULT NULL,
  reversed_at datetime DEFAULT NULL,
  reversal_reason varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gr_no (gr_no),
  KEY idx_confirmation (id_confirmation),
  KEY idx_production_order (id_production_order),
  KEY idx_posting_date (posting_date),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_gr_production_detail (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  gr_id bigint(20) NOT NULL,
  stock_layer_id int(11) DEFAULT NULL,
  material_doc_id bigint(20) DEFAULT NULL,
  material_code varchar(100) NOT NULL,
  material_name varchar(255) DEFAULT NULL,
  qty decimal(18,5) NOT NULL DEFAULT 0.00000,
  uom varchar(20) DEFAULT NULL,
  stock_type varchar(20) DEFAULT NULL,
  remarks varchar(255) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_gr_id (gr_id),
  KEY idx_material (material_code),
  KEY idx_stock_layer (stock_layer_id),
  CONSTRAINT fk_grprod_detail_header FOREIGN KEY (gr_id) REFERENCES erp_gr_production(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_gr_production_trace (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  gr_id bigint(20) NOT NULL,
  gr_detail_id bigint(20) NOT NULL,
  output_stock_layer_id int(11) DEFAULT NULL,
  source_issue_id bigint(20) DEFAULT NULL,
  source_issue_detail_id bigint(20) DEFAULT NULL,
  source_issue_trace_id bigint(20) DEFAULT NULL,
  source_stock_layer_id int(11) DEFAULT NULL,
  source_material_code varchar(100) DEFAULT NULL,
  source_material_name varchar(255) DEFAULT NULL,
  raw_material_code varchar(100) DEFAULT NULL,
  raw_material_name varchar(255) DEFAULT NULL,
  qty decimal(18,5) NOT NULL DEFAULT 0.00000,
  uom varchar(20) DEFAULT NULL,
  lot_no varchar(50) DEFAULT NULL,
  no_bpb varchar(50) DEFAULT NULL,
  no_aju varchar(50) DEFAULT NULL,
  jenis_dokpab varchar(20) DEFAULT NULL,
  no_dokpab varchar(50) DEFAULT NULL,
  hs_code varchar(20) DEFAULT NULL,
  trace_source enum('DIRECT','INHERITED') NOT NULL DEFAULT 'DIRECT',
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_gr (gr_id),
  KEY idx_detail (gr_detail_id),
  KEY idx_output_layer (output_stock_layer_id),
  KEY idx_source_layer (source_stock_layer_id),
  KEY idx_customs (no_aju,no_dokpab),
  KEY idx_lot (lot_no),
  CONSTRAINT fk_grprod_trace_header FOREIGN KEY (gr_id) REFERENCES erp_gr_production(id) ON DELETE CASCADE,
  CONSTRAINT fk_grprod_trace_detail FOREIGN KEY (gr_detail_id) REFERENCES erp_gr_production_detail(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='gr_from_production_order',
    main_table='erp_gr_production',
    icon='fa-download',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='incoming-terima';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','gudang','produksi','ppic') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','quality_control','manager_approver','auditor')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='incoming-terima'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','gudang','produksi','ppic') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='incoming-terima';
