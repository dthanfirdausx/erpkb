-- SAP-like FI-AP Vendor Invoice module.

CREATE TABLE IF NOT EXISTS erp_vendor_invoice (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_invoice_no VARCHAR(40) NOT NULL UNIQUE,
  vendor_code VARCHAR(20) NOT NULL,
  vendor_reference_no VARCHAR(100) NOT NULL,
  invoice_type ENUM('STANDARD','DOWN_PAYMENT','CREDIT_MEMO','DEBIT_MEMO','OTHER') NOT NULL DEFAULT 'STANDARD',
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  due_date DATE NULL,
  payment_term VARCHAR(100) NULL,
  reference_po VARCHAR(100) NULL,
  reference_gr VARCHAR(100) NULL,
  expense_account VARCHAR(50) NOT NULL,
  ap_account VARCHAR(50) NOT NULL,
  tax_account VARCHAR(50) NULL,
  tax_code_id INT NULL,
  net_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  gross_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  kurs DECIMAL(18,4) NOT NULL DEFAULT 1,
  cost_center_id INT NULL,
  profit_center_id INT NULL,
  description VARCHAR(255) NULL,
  status ENUM('DRAFT','POSTED','REVERSED') NOT NULL DEFAULT 'DRAFT',
  payment_status ENUM('OPEN','PARTIAL','PAID','CANCELLED') NOT NULL DEFAULT 'OPEN',
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
  INDEX idx_vendor_invoice_date (posting_date, status),
  INDEX idx_vendor_invoice_vendor (vendor_code, payment_status),
  INDEX idx_vendor_invoice_journal (journal_header_id)
);

UPDATE sys_menu
SET nav_act = 'vendor_invoice',
    main_table = 'erp_vendor_invoice'
WHERE url = 'vendor-invoice';
