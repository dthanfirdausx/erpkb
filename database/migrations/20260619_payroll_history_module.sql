CREATE TABLE IF NOT EXISTS erp_payroll_history (
  id INT(11) NOT NULL AUTO_INCREMENT,
  history_no VARCHAR(40) NOT NULL,
  payroll_process_id INT(11) NOT NULL,
  payroll_employee_id INT(11) NOT NULL,
  payslip_id INT(11) DEFAULT NULL,
  payroll_posting_id INT(11) DEFAULT NULL,
  payroll_run_no VARCHAR(30) NOT NULL,
  payslip_no VARCHAR(40) DEFAULT NULL,
  posting_no VARCHAR(40) DEFAULT NULL,
  employee_id INT(11) NOT NULL,
  employee_no VARCHAR(20) NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  department_code CHAR(8) DEFAULT NULL,
  employee_group VARCHAR(30) DEFAULT NULL,
  payroll_area VARCHAR(30) DEFAULT NULL,
  period_year INT(4) NOT NULL,
  period_month TINYINT(2) NOT NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  pay_date DATE NOT NULL,
  salary_structure_code VARCHAR(30) DEFAULT NULL,
  working_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  paid_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  absence_days DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  overtime_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  gross_pay DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_earning DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  total_deduction DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  net_pay DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  payroll_process_status VARCHAR(30) DEFAULT NULL,
  payslip_status VARCHAR(30) DEFAULT NULL,
  posting_status VARCHAR(30) DEFAULT NULL,
  history_status ENUM('ACTIVE','ARCHIVED','LOCKED','VOID') NOT NULL DEFAULT 'ACTIVE',
  audit_source ENUM('AUTO_SNAPSHOT','MANUAL_ADJUSTMENT','IMPORT') NOT NULL DEFAULT 'AUTO_SNAPSHOT',
  release_channel VARCHAR(30) DEFAULT NULL,
  released_at DATETIME DEFAULT NULL,
  journal_no VARCHAR(80) DEFAULT NULL,
  sap_reference VARCHAR(60) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_history_no (history_no),
  UNIQUE KEY uq_payroll_history_employee_run (payroll_process_id, employee_id),
  KEY idx_payroll_history_period (period_year, period_month, payroll_area),
  KEY idx_payroll_history_employee (employee_id, employee_no),
  KEY idx_payroll_history_status (history_status, payslip_status, posting_status),
  KEY idx_payroll_history_process_employee (payroll_employee_id),
  KEY idx_payroll_history_payslip (payslip_id),
  KEY idx_payroll_history_posting (payroll_posting_id),
  CONSTRAINT fk_payroll_history_process
    FOREIGN KEY (payroll_process_id) REFERENCES erp_payroll_process(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_payroll_history_process_employee
    FOREIGN KEY (payroll_employee_id) REFERENCES erp_payroll_process_employee(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_payroll_history_payslip
    FOREIGN KEY (payslip_id) REFERENCES erp_payslip(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_payroll_history_posting
    FOREIGN KEY (payroll_posting_id) REFERENCES erp_payroll_posting(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_payroll_history_detail (
  id INT(11) NOT NULL AUTO_INCREMENT,
  payroll_history_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL,
  component_code VARCHAR(30) NOT NULL,
  component_name VARCHAR(150) DEFAULT NULL,
  wage_type_code VARCHAR(20) DEFAULT NULL,
  component_type ENUM('EARNING','DEDUCTION','TAX','BENEFIT','EMPLOYER_CONTRIBUTION','INFORMATION') NOT NULL DEFAULT 'EARNING',
  quantity DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  rate DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  taxable ENUM('Y','N') NOT NULL DEFAULT 'Y',
  sequence_no INT(11) NOT NULL DEFAULT 100,
  PRIMARY KEY (id),
  KEY idx_payroll_history_detail_header (payroll_history_id),
  KEY idx_payroll_history_detail_component (component_code),
  CONSTRAINT fk_payroll_history_detail_header
    FOREIGN KEY (payroll_history_id) REFERENCES erp_payroll_history(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='payroll_history',
       main_table='erp_payroll_history',
       icon='fa-history',
       dt_table='Y',
       tampil='Y'
 WHERE url='payroll-history';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd','finance_akunting'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd','finance_akunting'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='payroll-history'
   AND r.id IS NULL;
