-- SAP-like FI Bank Reconciliation module.

CREATE TABLE IF NOT EXISTS erp_bank_statement_line (
  id INT AUTO_INCREMENT PRIMARY KEY,
  statement_no VARCHAR(40) NOT NULL,
  bank_account VARCHAR(50) NOT NULL,
  statement_date DATE NOT NULL,
  value_date DATE NULL,
  bank_reference VARCHAR(100) NULL,
  description VARCHAR(255) NULL,
  debit_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  credit_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  status ENUM('OPEN','MATCHED','CANCELLED') NOT NULL DEFAULT 'OPEN',
  matched_at DATETIME NULL,
  matched_by VARCHAR(100) NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME NULL,
  updated_by VARCHAR(100) NULL,
  updated_at DATETIME NULL,
  INDEX idx_statement_bank_date (bank_account, statement_date, status),
  INDEX idx_statement_no (statement_no)
);

CREATE TABLE IF NOT EXISTS erp_bank_reconciliation_match (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_no VARCHAR(40) NOT NULL UNIQUE,
  bank_statement_line_id INT NOT NULL,
  source_module ENUM('BANK_RECEIPT','BANK_PAYMENT','CASH_JOURNAL') NOT NULL,
  source_id INT NOT NULL,
  source_document_no VARCHAR(50) NOT NULL,
  bank_account VARCHAR(50) NOT NULL,
  match_date DATE NOT NULL,
  statement_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  erp_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  difference_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  status ENUM('MATCHED','UNMATCHED') NOT NULL DEFAULT 'MATCHED',
  notes VARCHAR(255) NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME NULL,
  unmatched_by VARCHAR(100) NULL,
  unmatched_at DATETIME NULL,
  INDEX idx_recon_statement (bank_statement_line_id, status),
  INDEX idx_recon_source (source_module, source_id, status),
  INDEX idx_recon_bank_date (bank_account, match_date)
);

UPDATE sys_menu
SET nav_act = 'bank_reconciliation',
    main_table = 'erp_bank_reconciliation_match'
WHERE url = 'bank-reconciliation';
