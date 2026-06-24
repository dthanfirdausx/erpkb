-- SAP-like FI-AP Vendor Payment module.

CREATE TABLE IF NOT EXISTS erp_vendor_payment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_payment_no VARCHAR(40) NOT NULL UNIQUE,
  vendor_code VARCHAR(20) NOT NULL,
  vendor_invoice_id INT NULL,
  vendor_invoice_no VARCHAR(40) NULL,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  value_date DATE NULL,
  bank_account VARCHAR(50) NOT NULL,
  ap_account VARCHAR(50) NOT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  kurs DECIMAL(18,4) NOT NULL DEFAULT 1,
  payment_method ENUM('TRANSFER','GIRO','CHEQUE','VIRTUAL_ACCOUNT','OTHER') NOT NULL DEFAULT 'TRANSFER',
  bank_reference VARCHAR(100) NULL,
  external_reference VARCHAR(100) NULL,
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
  INDEX idx_vendor_payment_date (posting_date, status),
  INDEX idx_vendor_payment_vendor (vendor_code),
  INDEX idx_vendor_payment_invoice (vendor_invoice_id),
  INDEX idx_vendor_payment_journal (journal_header_id)
);

UPDATE sys_menu
SET nav_act = 'vendor_payment',
    main_table = 'erp_vendor_payment'
WHERE url = 'vendor-payment';

ALTER TABLE erp_bank_reconciliation_match
  MODIFY source_module ENUM('BANK_RECEIPT','BANK_PAYMENT','CASH_JOURNAL','VENDOR_PAYMENT') NOT NULL;
