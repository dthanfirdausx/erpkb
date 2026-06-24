CREATE TABLE IF NOT EXISTS erp_employee_education (
  id INT(11) NOT NULL AUTO_INCREMENT,
  education_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  education_level ENUM('SD','SMP','SMA_SMK','D1','D2','D3','D4','S1','S2','S3','PROFESSIONAL','CERTIFICATION','OTHER') NOT NULL DEFAULT 'S1',
  education_type ENUM('FORMAL','NON_FORMAL','CERTIFICATION','TRAINING','LICENSE') NOT NULL DEFAULT 'FORMAL',
  institution_name VARCHAR(160) NOT NULL,
  major VARCHAR(120) DEFAULT NULL,
  faculty VARCHAR(120) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  country VARCHAR(3) NOT NULL DEFAULT 'ID',
  start_year YEAR DEFAULT NULL,
  graduation_year YEAR DEFAULT NULL,
  certificate_no VARCHAR(80) DEFAULT NULL,
  gpa VARCHAR(20) DEFAULT NULL,
  score VARCHAR(50) DEFAULT NULL,
  highest_education ENUM('Y','N') NOT NULL DEFAULT 'N',
  relevant_to_position ENUM('Y','N') NOT NULL DEFAULT 'N',
  verified_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  verified_by_employee_id INT(11) DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  document_ref VARCHAR(255) DEFAULT NULL,
  effective_from DATE NOT NULL DEFAULT '2026-01-01',
  effective_to DATE NOT NULL DEFAULT '9999-12-31',
  status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_education_no (education_no),
  KEY idx_education_employee (employee_id),
  KEY idx_education_level (education_level,education_type),
  KEY idx_education_verified (verified_status),
  KEY idx_education_effective (effective_from,effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='employee_education',
       main_table='erp_employee_education',
       icon='fa-graduation-cap',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-education';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='employee-education'
   AND r.id IS NULL;
