CREATE TABLE IF NOT EXISTS erp_payroll_process (
  id INT(11) NOT NULL AUTO_INCREMENT,
  payroll_run_no VARCHAR(30) NOT NULL,
  period_year INT(4) NOT NULL,
  period_month TINYINT(2) NOT NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  pay_date DATE NOT NULL,
  payroll_area ENUM('MONTHLY','DAILY','WEEKLY','CONTRACT','MANAGEMENT','ALL') NOT NULL DEFAULT 'MONTHLY',
  process_type ENUM('REGULAR','OFFCYCLE','THR','BONUS','FINAL_SETTLEMENT','CORRECTION') NOT NULL DEFAULT 'REGULAR',
  run_mode ENUM('SIMULATION','LIVE') NOT NULL DEFAULT 'SIMULATION',
  control_record_status ENUM('OPEN','RELEASED','LOCKED','EXITED') NOT NULL DEFAULT 'OPEN',
  process_status ENUM('DRAFT','CALCULATED','APPROVED','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  total_employee INT(11) NOT NULL DEFAULT 0,
  total_gross DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_deduction DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_tax DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_net DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  posting_reference VARCHAR(50) DEFAULT NULL,
  approved_by VARCHAR(50) DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  posted_by VARCHAR(50) DEFAULT NULL,
  posted_at DATETIME DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_run_no (payroll_run_no),
  KEY idx_payroll_process_period (period_year,period_month,payroll_area),
  KEY idx_payroll_process_status (process_status,control_record_status),
  KEY idx_payroll_process_date (period_from,period_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_payroll_process_employee (
  id INT(11) NOT NULL AUTO_INCREMENT,
  payroll_process_id INT(11) NOT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  department_code CHAR(8) DEFAULT NULL,
  employee_group VARCHAR(30) DEFAULT NULL,
  payroll_area VARCHAR(30) DEFAULT NULL,
  salary_structure_id INT(11) DEFAULT NULL,
  salary_structure_code VARCHAR(30) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  working_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  paid_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  absence_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  overtime_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  gross_pay DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_earning DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_deduction DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  net_pay DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  process_status ENUM('DRAFT','CALCULATED','LOCKED','POSTED','ERROR') NOT NULL DEFAULT 'DRAFT',
  error_message VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_process_employee (payroll_process_id,employee_id),
  KEY idx_payroll_employee_emp (employee_id),
  CONSTRAINT fk_payroll_process_employee_header
    FOREIGN KEY (payroll_process_id) REFERENCES erp_payroll_process(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_payroll_process_result (
  id INT(11) NOT NULL AUTO_INCREMENT,
  payroll_process_id INT(11) NOT NULL,
  payroll_employee_id INT(11) NOT NULL,
  employee_id INT(11) NOT NULL,
  component_code VARCHAR(30) NOT NULL,
  component_name VARCHAR(150) DEFAULT NULL,
  wage_type_code VARCHAR(20) DEFAULT NULL,
  component_type ENUM('EARNING','DEDUCTION','TAX','BENEFIT','EMPLOYER_CONTRIBUTION','INFORMATION') NOT NULL DEFAULT 'EARNING',
  calculation_method VARCHAR(30) DEFAULT NULL,
  base_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  rate DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  quantity DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  taxable ENUM('Y','N') NOT NULL DEFAULT 'Y',
  payslip_display ENUM('Y','N') NOT NULL DEFAULT 'Y',
  sequence_no INT(11) NOT NULL DEFAULT 100,
  formula_text VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_payroll_result_header (payroll_process_id),
  KEY idx_payroll_result_employee (payroll_employee_id,employee_id),
  KEY idx_payroll_result_component (component_code),
  CONSTRAINT fk_payroll_result_header
    FOREIGN KEY (payroll_process_id) REFERENCES erp_payroll_process(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_payroll_result_employee
    FOREIGN KEY (payroll_employee_id) REFERENCES erp_payroll_process_employee(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='payroll_process',
       main_table='erp_payroll_process',
       icon='fa-calculator',
       dt_table='Y',
       tampil='Y'
 WHERE url='payroll-process';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='payroll-process'
   AND r.id IS NULL;
