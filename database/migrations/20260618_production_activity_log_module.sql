CREATE TABLE IF NOT EXISTS erp_production_activity_log (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  activity_no varchar(50) NOT NULL,
  activity_date date NOT NULL,
  activity_time datetime NOT NULL,
  id_production_order bigint(20) DEFAULT NULL,
  no_production_order varchar(30) DEFAULT NULL,
  id_operation bigint(20) DEFAULT NULL,
  operation_no varchar(20) DEFAULT NULL,
  operation_name varchar(150) DEFAULT NULL,
  work_center varchar(100) NOT NULL,
  work_center_name varchar(150) DEFAULT NULL,
  plant_code varchar(20) DEFAULT NULL,
  shift_id int(11) DEFAULT NULL,
  shift_code varchar(20) DEFAULT NULL,
  operator_name varchar(100) DEFAULT NULL,
  activity_type enum('ORDER_START','OPERATION_START','OPERATION_FINISH','MATERIAL_ISSUE','CONFIRMATION','DOWNTIME','QUALITY_CHECK','CLEANING','HANDOVER','NOTE','STOP','OTHER') NOT NULL DEFAULT 'NOTE',
  severity enum('INFO','WARNING','CRITICAL') NOT NULL DEFAULT 'INFO',
  activity_text varchar(255) NOT NULL,
  action_taken varchar(255) DEFAULT NULL,
  reference_type varchar(50) DEFAULT 'MANUAL',
  reference_id bigint(20) DEFAULT NULL,
  status enum('POSTED','CANCELLED') NOT NULL DEFAULT 'POSTED',
  remarks text DEFAULT NULL,
  created_by varchar(100) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_by varchar(100) DEFAULT NULL,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  cancelled_by varchar(100) DEFAULT NULL,
  cancelled_at datetime DEFAULT NULL,
  cancel_reason varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_activity_no (activity_no),
  KEY idx_activity_date_wc (activity_date,work_center,plant_code),
  KEY idx_activity_po (id_production_order,no_production_order),
  KEY idx_activity_type (activity_type,severity,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_production_activity_log_history (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  activity_id bigint(20) NOT NULL,
  status_lama varchar(30) DEFAULT NULL,
  status_baru varchar(30) NOT NULL,
  remarks varchar(255) DEFAULT NULL,
  changed_by varchar(100) DEFAULT NULL,
  changed_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_activity_history (activity_id,changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
SET nav_act='production_activity_log',
    page_name='Production Activity Log',
    url='production-activity-log',
    main_table='erp_production_activity_log',
    icon='fa-list-alt',
    tampil='Y',
    dt_table='Y',
    type_menu='page'
WHERE url='production-activity-log';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','Y','Y','Y','Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'')<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='production-activity-log' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',r.insert_act='Y',r.update_act='Y',r.delete_act='Y',r.import_act='Y'
WHERE m.url='production-activity-log';
