CREATE TABLE IF NOT EXISTS erp_payroll_posting (
  id INT(11) NOT NULL AUTO_INCREMENT,
  posting_no VARCHAR(40) NOT NULL,
  payroll_process_id INT(11) NOT NULL,
  payroll_run_no VARCHAR(30) NOT NULL,
  posting_date DATE NOT NULL,
  document_date DATE NOT NULL,
  fiscal_year INT(4) NOT NULL,
  fiscal_period TINYINT(2) NOT NULL,
  document_type VARCHAR(10) NOT NULL DEFAULT 'PY',
  posting_variant ENUM('SUMMARY','BY_COST_CENTER','BY_EMPLOYEE') NOT NULL DEFAULT 'SUMMARY',
  posting_status ENUM('DRAFT','READY','POSTED','REVERSED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  payroll_area VARCHAR(30) DEFAULT NULL,
  total_employee INT(11) NOT NULL DEFAULT 0,
  gross_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  deduction_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  net_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  salary_expense_account VARCHAR(50) NOT NULL,
  payroll_payable_account VARCHAR(50) NOT NULL,
  tax_payable_account VARCHAR(50) DEFAULT NULL,
  deduction_payable_account VARCHAR(50) DEFAULT NULL,
  journal_header_id INT(11) DEFAULT NULL,
  reversal_journal_header_id INT(11) DEFAULT NULL,
  posted_by VARCHAR(50) DEFAULT NULL,
  posted_at DATETIME DEFAULT NULL,
  reversed_by VARCHAR(50) DEFAULT NULL,
  reversed_at DATETIME DEFAULT NULL,
  external_reference VARCHAR(80) DEFAULT NULL,
  sap_reference VARCHAR(50) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_by VARCHAR(50) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) DEFAULT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payroll_posting_no (posting_no),
  UNIQUE KEY uq_payroll_posting_process (payroll_process_id),
  KEY idx_payroll_posting_date (posting_date,posting_status),
  KEY idx_payroll_posting_journal (journal_header_id),
  CONSTRAINT fk_payroll_posting_process
    FOREIGN KEY (payroll_process_id) REFERENCES erp_payroll_process(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_payroll_posting_line (
  id INT(11) NOT NULL AUTO_INCREMENT,
  payroll_posting_id INT(11) NOT NULL,
  line_no INT(11) NOT NULL,
  account_no VARCHAR(50) NOT NULL,
  account_name VARCHAR(100) DEFAULT NULL,
  posting_key ENUM('DEBIT','CREDIT') NOT NULL,
  line_text VARCHAR(255) DEFAULT NULL,
  cost_center_code VARCHAR(20) DEFAULT NULL,
  profit_center_code VARCHAR(20) DEFAULT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  source_amount_type ENUM('GROSS','NET','TAX','DEDUCTION','OTHER') NOT NULL DEFAULT 'OTHER',
  PRIMARY KEY (id),
  KEY idx_payroll_posting_line_header (payroll_posting_id),
  KEY idx_payroll_posting_line_account (account_no),
  CONSTRAINT fk_payroll_posting_line_header
    FOREIGN KEY (payroll_posting_id) REFERENCES erp_payroll_posting(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE sys_menu
   SET nav_act='payroll_posting',
       main_table='erp_payroll_posting',
       icon='fa-cloud-upload',
       dt_table='Y',
       tampil='Y'
 WHERE url='payroll-posting';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
       IF(g.level IN ('admin','system_administrator','hrd','finance_akunting'),'Y','N'),
       IF(g.level IN ('admin','system_administrator','hrd','finance_akunting'),'Y','N'),
       IF(g.level IN ('admin','system_administrator'),'Y','N'),
       'N'
  FROM sys_menu m
  JOIN sys_group_users g
  LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
 WHERE m.url='payroll-posting'
   AND r.id IS NULL;
