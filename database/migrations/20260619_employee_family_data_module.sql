CREATE TABLE IF NOT EXISTS erp_employee_family_data (
  id INT(11) NOT NULL AUTO_INCREMENT,
  family_no VARCHAR(30) NOT NULL,
  employee_id INT(11) NOT NULL,
  relationship_type ENUM('SPOUSE','CHILD','FATHER','MOTHER','SIBLING','GUARDIAN','OTHER') NOT NULL DEFAULT 'SPOUSE',
  family_name VARCHAR(160) NOT NULL,
  gender ENUM('MALE','FEMALE','OTHER') NOT NULL DEFAULT 'MALE',
  birth_place VARCHAR(80) DEFAULT NULL,
  birth_date DATE DEFAULT NULL,
  marital_status ENUM('SINGLE','MARRIED','DIVORCED','WIDOWED') DEFAULT 'SINGLE',
  nationality VARCHAR(3) NOT NULL DEFAULT 'ID',
  identity_type ENUM('KTP','PASSPORT','KITAS','BIRTH_CERTIFICATE','FAMILY_CARD','OTHER') NOT NULL DEFAULT 'KTP',
  identity_no VARCHAR(50) DEFAULT NULL,
  occupation VARCHAR(100) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  is_dependent ENUM('Y','N') NOT NULL DEFAULT 'N',
  tax_dependent ENUM('Y','N') NOT NULL DEFAULT 'N',
  bpjs_dependent ENUM('Y','N') NOT NULL DEFAULT 'N',
  emergency_contact ENUM('Y','N') NOT NULL DEFAULT 'N',
  benefit_eligible ENUM('Y','N') NOT NULL DEFAULT 'N',
  effective_from DATE NOT NULL DEFAULT '2026-01-01',
  effective_to DATE NOT NULL DEFAULT '9999-12-31',
  document_ref VARCHAR(255) DEFAULT NULL,
  status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_family_no (family_no),
  KEY idx_family_employee (employee_id),
  KEY idx_family_relation (relationship_type,status),
  KEY idx_family_effective (effective_from,effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='employee_family_data',
       main_table='erp_employee_family_data',
       icon='fa-users',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-family-data';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='employee-family-data'
   AND r.id IS NULL;
