CREATE TABLE IF NOT EXISTS erp_company_structure (
  id INT(11) NOT NULL AUTO_INCREMENT,
  structure_code VARCHAR(20) NOT NULL,
  structure_name VARCHAR(150) NOT NULL,
  structure_type ENUM('COMPANY','COMPANY_CODE','BUSINESS_AREA','PERSONNEL_AREA','PERSONNEL_SUBAREA','ORG_UNIT') NOT NULL DEFAULT 'ORG_UNIT',
  parent_id INT(11) NULL,
  legal_entity_name VARCHAR(150) NULL,
  tax_id VARCHAR(50) NULL,
  country CHAR(3) NOT NULL DEFAULT 'ID',
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  valid_from DATE NOT NULL,
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  address VARCHAR(255) NULL,
  city VARCHAR(100) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(100) NULL,
  cost_center_code VARCHAR(30) NULL,
  profit_center_code VARCHAR(30) NULL,
  sap_reference VARCHAR(50) NULL,
  status ENUM('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  remarks TEXT NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  inactive_reason VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_company_structure_code (structure_code),
  KEY idx_company_structure_parent (parent_id),
  KEY idx_company_structure_type (structure_type),
  KEY idx_company_structure_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sys_menu
SET nav_act='company_structure', main_table='erp_company_structure'
WHERE url='company-structure';
