-- SAP-like FI Cash Journal module.

CREATE TABLE IF NOT EXISTS erp_cash_journal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cash_journal_no VARCHAR(40) NOT NULL UNIQUE,
  transaction_type ENUM('RECEIPT','PAYMENT') NOT NULL,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  cash_account VARCHAR(50) NOT NULL,
  offset_account VARCHAR(50) NOT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  kurs DECIMAL(18,4) NOT NULL DEFAULT 1,
  cost_center_id INT NULL,
  profit_center_id INT NULL,
  tax_code_id INT NULL,
  reference_no VARCHAR(100) NULL,
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
  INDEX idx_cash_journal_date (posting_date, status),
  INDEX idx_cash_journal_cash_account (cash_account),
  INDEX idx_cash_journal_journal (journal_header_id)
);

UPDATE sys_menu
SET nav_act = 'cash_journal',
    main_table = 'erp_cash_journal'
WHERE url = 'cash-journal';
