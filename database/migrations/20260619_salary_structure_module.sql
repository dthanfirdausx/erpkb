CREATE TABLE IF NOT EXISTS erp_salary_structure (
  id INT(11) NOT NULL AUTO_INCREMENT,
  structure_code VARCHAR(30) NOT NULL,
  structure_name VARCHAR(150) NOT NULL,
  pay_scale_type ENUM('MONTHLY','DAILY','HOURLY','CONTRACT','MANAGEMENT') NOT NULL DEFAULT 'MONTHLY',
  pay_scale_area VARCHAR(50) NOT NULL DEFAULT 'ID',
  pay_grade VARCHAR(30) NOT NULL,
  pay_level VARCHAR(30) NOT NULL,
  position_level VARCHAR(50) DEFAULT NULL,
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE','ALL') NOT NULL DEFAULT 'ALL',
  payroll_area ENUM('MONTHLY','DAILY','WEEKLY','CONTRACT','MANAGEMENT','ALL') NOT NULL DEFAULT 'MONTHLY',
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  base_salary_min DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  base_salary_mid DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  base_salary_max DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  annual_ctc_min DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  annual_ctc_max DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  structure_status ENUM('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_salary_structure_code (structure_code),
  KEY idx_salary_structure_grade (pay_grade,pay_level),
  KEY idx_salary_structure_area (pay_scale_type,payroll_area,employee_group),
  KEY idx_salary_structure_status (structure_status),
  KEY idx_salary_structure_validity (valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_salary_structure_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  structure_id INT(11) NOT NULL,
  component_code VARCHAR(30) NOT NULL,
  calculation_method ENUM('FIXED_AMOUNT','PERCENTAGE','FORMULA','ATTENDANCE_BASED','OVERTIME_BASED','MANUAL_INPUT') NOT NULL DEFAULT 'FIXED_AMOUNT',
  amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  percentage_rate DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  formula_text VARCHAR(255) DEFAULT NULL,
  mandatory ENUM('Y','N') NOT NULL DEFAULT 'Y',
  taxable ENUM('Y','N') NOT NULL DEFAULT 'Y',
  payslip_display ENUM('Y','N') NOT NULL DEFAULT 'Y',
  sequence_no INT(11) NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_salary_structure_detail_header (structure_id),
  KEY idx_salary_structure_detail_component (component_code),
  CONSTRAINT fk_salary_structure_detail_header
    FOREIGN KEY (structure_id) REFERENCES erp_salary_structure(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='salary_structure',
       main_table='erp_salary_structure',
       icon='fa-money',
       dt_table='Y',
       tampil='Y'
 WHERE url='salary-structure';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='salary-structure'
   AND r.id IS NULL;
