CREATE TABLE IF NOT EXISTS erp_capa (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  capa_no varchar(30) NOT NULL,
  capa_type enum('CORRECTIVE','PREVENTIVE','BOTH') NOT NULL DEFAULT 'BOTH',
  source_type enum('QUALITY_NOTIFICATION','AUDIT','CUSTOMER_COMPLAINT','SUPPLIER_DEFECT','PROCESS_DEVIATION','MANUAL') NOT NULL DEFAULT 'MANUAL',
  notification_id bigint(20) DEFAULT NULL,
  notification_no varchar(30) DEFAULT NULL,
  material_code varchar(80) DEFAULT NULL,
  material_name varchar(255) DEFAULT NULL,
  defect_category varchar(120) DEFAULT NULL,
  defect_code varchar(80) DEFAULT NULL,
  problem_statement text,
  root_cause text,
  correction_action text,
  corrective_action text,
  preventive_action text,
  verification_plan text,
  effectiveness_result text,
  owner_user varchar(80) DEFAULT NULL,
  approver_user varchar(80) DEFAULT NULL,
  start_date date DEFAULT NULL,
  due_date date DEFAULT NULL,
  verification_date date DEFAULT NULL,
  closed_at datetime DEFAULT NULL,
  closed_by varchar(80) DEFAULT NULL,
  priority enum('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  risk_level enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  status enum('DRAFT','OPEN','IN_PROGRESS','WAITING_VERIFICATION','EFFECTIVE','INEFFECTIVE','CLOSED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  created_by varchar(80) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by varchar(80) DEFAULT NULL,
  updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_capa_no (capa_no),
  KEY idx_erp_capa_notification (notification_id),
  KEY idx_erp_capa_status (status),
  KEY idx_erp_capa_due (due_date),
  KEY idx_erp_capa_material (material_code),
  CONSTRAINT fk_erp_capa_notification FOREIGN KEY (notification_id) REFERENCES erp_quality_notification(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_capa_action (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  capa_id bigint(20) NOT NULL,
  action_type enum('STATUS','COMMENT','APPROVAL','VERIFICATION') NOT NULL DEFAULT 'COMMENT',
  action_text text,
  action_by varchar(80) DEFAULT NULL,
  action_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_capa_action_capa (capa_id),
  CONSTRAINT fk_erp_capa_action_capa FOREIGN KEY (capa_id) REFERENCES erp_capa(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='capa',
    page_name='CAPA',
    main_table='erp_capa',
    dt_table='N',
    icon='fa-wrench',
    tampil='Y',
    type_menu='page',
    parent=417,
    parent_name='quality management',
    urutan_menu=7
WHERE url='capa';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'N', 'Y'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='capa' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.import_act='Y'
WHERE m.url='capa';
