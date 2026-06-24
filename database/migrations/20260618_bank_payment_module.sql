-- SAP-like FI Bank Payment module.

CREATE TABLE IF NOT EXISTS erp_bank_payment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bank_payment_no VARCHAR(40) NOT NULL UNIQUE,
  payment_category ENUM('VENDOR','EXPENSE','TAX','INTERCOMPANY','OTHER') NOT NULL DEFAULT 'VENDOR',
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  value_date DATE NULL,
  bank_account VARCHAR(50) NOT NULL,
  offset_account VARCHAR(50) NOT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  kurs DECIMAL(18,4) NOT NULL DEFAULT 1,
  payee_name VARCHAR(150) NULL,
  bank_reference VARCHAR(100) NULL,
  external_reference VARCHAR(100) NULL,
  payment_method ENUM('TRANSFER','GIRO','CHEQUE','VIRTUAL_ACCOUNT','OTHER') NOT NULL DEFAULT 'TRANSFER',
  cost_center_id INT NULL,
  profit_center_id INT NULL,
  tax_code_id INT NULL,
  description VARCHAR(255) NULL,
  status ENUM('DRAFT','POSTED','REVERSED') NOT NULL DEFAULT 'DRAFT',
  journal_header_id INT NULL,
  reversal_journal_header_id INT NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME NULL,
  updated_by VARCHAR(100) NULL,
  updated_at DATETIME NULL,
  posted_by VARCHAR(100) NULL,
  posted_at DATETIME NULL,
  reversed_by VARCHAR(100) NULL,
  reversed_at DATETIME NULL,
  INDEX idx_bank_payment_date (posting_date, status),
  INDEX idx_bank_payment_bank_account (bank_account),
  INDEX idx_bank_payment_journal (journal_header_id)
);

UPDATE sys_menu
SET nav_act = 'bank_payment',
    main_table = 'erp_bank_payment'
WHERE url = 'bank-payment';
