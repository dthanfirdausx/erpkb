CREATE TABLE IF NOT EXISTS erp_usage_decision (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  ud_no varchar(40) NOT NULL,
  inspection_lot_id bigint(20) NOT NULL,
  lot_no varchar(40) NOT NULL,
  decision_code enum('A','R','P','RW','RTV','SCRAP') NOT NULL,
  decision_text varchar(150) NOT NULL,
  follow_up_action enum('RELEASE','BLOCK','REWORK','RETURN_TO_VENDOR','SCRAP','PARTIAL_RELEASE','NO_STOCK_POSTING') NOT NULL DEFAULT 'RELEASE',
  movement_type varchar(10) DEFAULT NULL,
  stock_posted enum('Y','N') NOT NULL DEFAULT 'N',
  source_stock_layer_id int(11) DEFAULT NULL,
  accepted_stock_layer_id int(11) DEFAULT NULL,
  rejected_stock_layer_id int(11) DEFAULT NULL,
  material_code varchar(100) DEFAULT NULL,
  material_name varchar(255) DEFAULT NULL,
  lot_qty decimal(18,5) NOT NULL DEFAULT 0.00000,
  accepted_qty decimal(18,5) NOT NULL DEFAULT 0.00000,
  rejected_qty decimal(18,5) NOT NULL DEFAULT 0.00000,
  uom varchar(20) DEFAULT NULL,
  plant_id int(11) DEFAULT NULL,
  storage_location_id int(11) DEFAULT NULL,
  storage_bin_id int(11) DEFAULT NULL,
  source_stock_type varchar(20) DEFAULT NULL,
  accepted_stock_type varchar(20) DEFAULT NULL,
  rejected_stock_type varchar(20) DEFAULT NULL,
  no_aju varchar(50) DEFAULT NULL,
  jenis_dokpab varchar(20) DEFAULT NULL,
  no_dokpab varchar(50) DEFAULT NULL,
  no_bpb varchar(50) DEFAULT NULL,
  reason_code varchar(80) DEFAULT NULL,
  defect_summary text,
  notes text,
  decision_by varchar(100) DEFAULT NULL,
  decision_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_usage_decision_no (ud_no),
  UNIQUE KEY uk_erp_usage_decision_lot (inspection_lot_id),
  KEY idx_erp_usage_decision_lot_no (lot_no),
  KEY idx_erp_usage_decision_material (material_code),
  KEY idx_erp_usage_decision_date (decision_at),
  KEY idx_erp_usage_decision_status (decision_code, follow_up_action),
  CONSTRAINT fk_erp_usage_decision_lot FOREIGN KEY (inspection_lot_id) REFERENCES erp_inspection_lot(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_usage_decision_action (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  usage_decision_id bigint(20) NOT NULL,
  action_type enum('POST','STOCK','COMMENT','REVERSAL') NOT NULL DEFAULT 'COMMENT',
  action_text text,
  action_by varchar(100) DEFAULT NULL,
  action_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_usage_decision_action_ud (usage_decision_id),
  CONSTRAINT fk_erp_usage_decision_action_ud FOREIGN KEY (usage_decision_id) REFERENCES erp_usage_decision(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='usage_decision',
    page_name='Usage Decision',
    main_table='erp_usage_decision',
    dt_table='N',
    icon='fa-gavel',
    tampil='Y',
    type_menu='page',
    parent=417,
    parent_name='quality management',
    urutan_menu=8
WHERE url='usage-decision';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'N', 'Y'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='usage-decision' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.import_act='Y'
WHERE m.url='usage-decision';
