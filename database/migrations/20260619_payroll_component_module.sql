CREATE TABLE IF NOT EXISTS erp_payroll_component (
  id INT(11) NOT NULL AUTO_INCREMENT,
  component_code VARCHAR(30) NOT NULL,
  component_name VARCHAR(150) NOT NULL,
  wage_type_code VARCHAR(20) NOT NULL,
  component_type ENUM('EARNING','DEDUCTION','TAX','BENEFIT','EMPLOYER_CONTRIBUTION','INFORMATION') NOT NULL DEFAULT 'EARNING',
  component_category ENUM('BASIC_PAY','ALLOWANCE','OVERTIME','BONUS','REIMBURSEMENT','LOAN','INSURANCE','TAX','SOCIAL_SECURITY','ABSENCE','OTHER') NOT NULL DEFAULT 'BASIC_PAY',
  payroll_area ENUM('MONTHLY','DAILY','WEEKLY','CONTRACT','MANAGEMENT','ALL') NOT NULL DEFAULT 'ALL',
  employee_group ENUM('DIRECTOR','MANAGER','STAFF','NON_STAFF','OPERATOR','CONTRACT','DAILY_WORKER','TRAINEE','ALL') NOT NULL DEFAULT 'ALL',
  calculation_method ENUM('FIXED_AMOUNT','PERCENTAGE','FORMULA','ATTENDANCE_BASED','OVERTIME_BASED','MANUAL_INPUT') NOT NULL DEFAULT 'FIXED_AMOUNT',
  calculation_base ENUM('BASIC_SALARY','GROSS_PAY','NET_PAY','WORKING_DAYS','ATTENDANCE_HOURS','OVERTIME_HOURS','NONE') NOT NULL DEFAULT 'NONE',
  default_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  percentage_rate DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  formula_text VARCHAR(255) DEFAULT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  taxable ENUM('Y','N') NOT NULL DEFAULT 'Y',
  bpjs_base ENUM('Y','N') NOT NULL DEFAULT 'N',
  recurring ENUM('Y','N') NOT NULL DEFAULT 'Y',
  prorate ENUM('Y','N') NOT NULL DEFAULT 'Y',
  retroactive_allowed ENUM('Y','N') NOT NULL DEFAULT 'N',
  payslip_display ENUM('Y','N') NOT NULL DEFAULT 'Y',
  posting_required ENUM('Y','N') NOT NULL DEFAULT 'Y',
  debit_credit ENUM('DEBIT','CREDIT','NONE') NOT NULL DEFAULT 'DEBIT',
  gl_account_code VARCHAR(30) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  sequence_no INT(11) NOT NULL DEFAULT 100,
  valid_from DATE NOT NULL DEFAULT '2026-01-01',
  valid_to DATE NOT NULL DEFAULT '9999-12-31',
  component_status ENUM('DRAFT','ACTIVE','INACTIVE') NOT NULL DEFAULT 'DRAFT',
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_component_code (component_code),
  UNIQUE KEY uq_payroll_wage_type (wage_type_code),
  KEY idx_payroll_component_type (component_type,component_category),
  KEY idx_payroll_component_area (payroll_area,employee_group),
  KEY idx_payroll_component_status (component_status),
  KEY idx_payroll_component_validity (valid_from,valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='payroll_component',
       main_table='erp_payroll_component',
       icon='fa-list-alt',
       dt_table='Y',
       tampil='Y'
 WHERE url='payroll-component';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='payroll-component'
   AND r.id IS NULL;
