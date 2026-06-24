CREATE TABLE IF NOT EXISTS erp_position (
  id INT(11) NOT NULL AUTO_INCREMENT,
  position_code VARCHAR(30) NOT NULL,
  position_name VARCHAR(150) NOT NULL,
  position_short_name VARCHAR(80) DEFAULT NULL,
  position_type ENUM('STRUCTURAL','FUNCTIONAL','OPERATIONAL','PROJECT','TEMPORARY') NOT NULL DEFAULT 'STRUCTURAL',
  position_category ENUM('REGULAR','KEY_POSITION','CRITICAL','SUCCESSION','APPRENTICE') NOT NULL DEFAULT 'REGULAR',
  job_title_id INT(11) DEFAULT NULL,
  department_code CHAR(8) DEFAULT NULL,
  company_structure_id INT(11) DEFAULT NULL,
  reports_to_position_id INT(11) DEFAULT NULL,
  holder_employee_id INT(11) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  work_location_id INT(11) DEFAULT NULL,
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  pay_grade VARCHAR(30) DEFAULT NULL,
  planned_fte DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  occupied_fte DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  headcount_plan INT(11) NOT NULL DEFAULT 1,
  vacancy_status ENUM('VACANT','OCCUPIED','PARTIAL','OVERSTAFFED','FROZEN') NOT NULL DEFAULT 'VACANT',
  position_status ENUM('PLANNED','APPROVED','ACTIVE','INACTIVE','OBSOLETE') NOT NULL DEFAULT 'PLANNED',
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  job_description TEXT DEFAULT NULL,
  qualification_requirement TEXT DEFAULT NULL,
  authority_limit TEXT DEFAULT NULL,
  succession_plan_note TEXT DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_position_code (position_code),
  KEY idx_position_job_title (job_title_id),
  KEY idx_position_department (department_code),
  KEY idx_position_company_structure (company_structure_id),
  KEY idx_position_reports_to (reports_to_position_id),
  KEY idx_position_holder (holder_employee_id),
  KEY idx_position_status (position_status,vacancy_status),
  KEY idx_position_validity (valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='position',
       main_table='erp_position',
       icon='fa-briefcase',
       dt_table='Y',
       tampil='Y'
 WHERE url='position';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='position'
   AND r.id IS NULL;
