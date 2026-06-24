CREATE TABLE IF NOT EXISTS erp_job_title (
  id int(11) NOT NULL AUTO_INCREMENT,
  job_title_code varchar(20) NOT NULL,
  job_title_name varchar(120) NOT NULL,
  job_title_short_name varchar(60) DEFAULT NULL,
  job_family enum('EXECUTIVE','MANAGEMENT','PROFESSIONAL','SUPERVISOR','STAFF','OPERATOR','TECHNICIAN','ADMINISTRATION','SALES','QUALITY','WAREHOUSE','PRODUCTION','FINANCE','HR','IT','PROCUREMENT') NOT NULL DEFAULT 'STAFF',
  job_level enum('L1','L2','L3','L4','L5','L6','L7','L8','L9','L10') NOT NULL DEFAULT 'L1',
  employee_group enum('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE') NOT NULL DEFAULT 'STAFF',
  employee_subgroup varchar(50) DEFAULT NULL,
  department_code char(8) DEFAULT NULL,
  company_structure_id int(11) DEFAULT NULL,
  reports_to_job_title_id int(11) DEFAULT NULL,
  cost_center_code varchar(20) DEFAULT NULL,
  profit_center_code varchar(20) DEFAULT NULL,
  pay_grade varchar(30) DEFAULT NULL,
  work_location_type enum('OFFICE','PLANT','WAREHOUSE','FIELD','REMOTE','HYBRID') NOT NULL DEFAULT 'OFFICE',
  headcount_plan int(11) NOT NULL DEFAULT 0,
  minimum_education varchar(100) DEFAULT NULL,
  competency_profile varchar(150) DEFAULT NULL,
  job_purpose text DEFAULT NULL,
  key_responsibility text DEFAULT NULL,
  authority_limit text DEFAULT NULL,
  valid_from date NOT NULL DEFAULT '2026-01-01',
  valid_to date NOT NULL DEFAULT '9999-12-31',
  status enum('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  sap_reference varchar(50) DEFAULT NULL,
  remarks text DEFAULT NULL,
  created_by varchar(50) DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT current_timestamp(),
  updated_by varchar(50) DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  inactive_reason varchar(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_job_title_code (job_title_code),
  KEY idx_job_title_family (job_family,job_level),
  KEY idx_job_title_dept (department_code),
  KEY idx_job_title_org (company_structure_id),
  KEY idx_job_title_reports_to (reports_to_job_title_id),
  KEY idx_job_title_cost (cost_center_code),
  KEY idx_job_title_profit (profit_center_code),
  KEY idx_job_title_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
   SET nav_act='job_title', main_table='erp_job_title', icon='fa-id-badge', dt_table='Y', tampil='Y'
 WHERE url='job-title';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='job-title' AND r.id IS NULL;
