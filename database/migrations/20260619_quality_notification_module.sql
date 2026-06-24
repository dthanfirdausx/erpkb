CREATE TABLE IF NOT EXISTS erp_quality_notification (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  notification_no VARCHAR(40) NOT NULL,
  notification_type ENUM('NCR','CUSTOMER_COMPLAINT','SUPPLIER_DEFECT','INTERNAL_DEFECT','AUDIT_FINDING') NOT NULL DEFAULT 'NCR',
  source_type ENUM('INSPECTION_LOT','NG','MANUAL','CUSTOMER','SUPPLIER','AUDIT') NOT NULL DEFAULT 'MANUAL',
  source_ref_id BIGINT(20) DEFAULT NULL,
  source_ref_no VARCHAR(80) DEFAULT NULL,
  inspection_lot_id BIGINT(20) DEFAULT NULL,
  material_code VARCHAR(100) DEFAULT NULL,
  material_name VARCHAR(255) DEFAULT NULL,
  defect_qty DECIMAL(18,5) NOT NULL DEFAULT 0.00000,
  uom VARCHAR(20) DEFAULT NULL,
  severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
  priority ENUM('LOW','NORMAL','HIGH','URGENT') NOT NULL DEFAULT 'NORMAL',
  defect_category VARCHAR(80) DEFAULT NULL,
  defect_code VARCHAR(50) DEFAULT NULL,
  defect_description TEXT DEFAULT NULL,
  containment_action TEXT DEFAULT NULL,
  root_cause TEXT DEFAULT NULL,
  corrective_action TEXT DEFAULT NULL,
  preventive_action TEXT DEFAULT NULL,
  responsible_user VARCHAR(100) DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  status ENUM('OPEN','IN_REVIEW','CONTAINED','CAPA_REQUIRED','CAPA_IN_PROGRESS','CLOSED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  closed_at DATETIME DEFAULT NULL,
  closed_by VARCHAR(100) DEFAULT NULL,
  plant_id INT(11) DEFAULT NULL,
  storage_location_id INT(11) DEFAULT NULL,
  storage_bin_id INT(11) DEFAULT NULL,
  no_aju VARCHAR(50) DEFAULT NULL,
  jenis_dokpab VARCHAR(20) DEFAULT NULL,
  no_dokpab VARCHAR(50) DEFAULT NULL,
  no_bpb VARCHAR(50) DEFAULT NULL,
  created_by VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(100) DEFAULT NULL,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erp_quality_notification_no (notification_no),
  KEY idx_erp_quality_notification_status (status),
  KEY idx_erp_quality_notification_material (material_code),
  KEY idx_erp_quality_notification_source (source_type, source_ref_id),
  KEY idx_erp_quality_notification_lot (inspection_lot_id),
  KEY idx_erp_quality_notification_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_quality_notification_action (
  id BIGINT(20) NOT NULL AUTO_INCREMENT,
  notification_id BIGINT(20) NOT NULL,
  action_type ENUM('STATUS','CONTAINMENT','ROOT_CAUSE','CORRECTIVE','PREVENTIVE','COMMENT') NOT NULL DEFAULT 'COMMENT',
  action_text TEXT NOT NULL,
  action_by VARCHAR(100) DEFAULT NULL,
  action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_erp_quality_notification_action_notification (notification_id),
  CONSTRAINT fk_erp_quality_notification_action_notification FOREIGN KEY (notification_id) REFERENCES erp_quality_notification(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='quality_notification',
    page_name='Quality Notification / NCR',
    main_table='erp_quality_notification',
    icon='fa-exclamation-triangle',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='quality-notification';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y', 'Y', 'Y', 'N', 'Y'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='quality-notification'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.import_act='Y'
WHERE m.url='quality-notification';
