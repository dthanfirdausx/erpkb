-- SAP-like FI-AR incoming payment and tax management modules.

CREATE TABLE IF NOT EXISTS erp_incoming_payment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  incoming_payment_no VARCHAR(40) NOT NULL UNIQUE,
  customer_code VARCHAR(20) NOT NULL,
  sales_invoice_id INT NULL,
  sales_invoice_no VARCHAR(100) NULL,
  document_date DATE NOT NULL,
  posting_date DATE NOT NULL,
  value_date DATE NULL,
  bank_account VARCHAR(50) NOT NULL,
  ar_account VARCHAR(50) NOT NULL,
  amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  kurs DECIMAL(18,4) NOT NULL DEFAULT 1,
  payment_method ENUM('TRANSFER','GIRO','CASH','VIRTUAL_ACCOUNT','OTHER') NOT NULL DEFAULT 'TRANSFER',
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
  INDEX idx_incoming_payment_date (posting_date, status),
  INDEX idx_incoming_payment_customer (customer_code),
  INDEX idx_incoming_payment_invoice (sales_invoice_id)
);

CREATE TABLE IF NOT EXISTS erp_tax_invoice (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tax_direction ENUM('IN','OUT') NOT NULL,
  tax_invoice_no VARCHAR(100) NOT NULL,
  tax_invoice_date DATE NOT NULL,
  tax_period CHAR(7) NOT NULL,
  partner_code VARCHAR(20) NULL,
  partner_name VARCHAR(150) NULL,
  source_module VARCHAR(50) NULL,
  source_id INT NULL,
  source_document_no VARCHAR(100) NULL,
  tax_code_id INT NULL,
  dpp_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  vat_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
  currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
  status ENUM('DRAFT','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  validation_status ENUM('NOT_VALIDATED','VALID','INVALID') NOT NULL DEFAULT 'NOT_VALIDATED',
  description VARCHAR(255) NULL,
  created_by VARCHAR(100) NULL,
  created_at DATETIME NULL,
  updated_by VARCHAR(100) NULL,
  updated_at DATETIME NULL,
  posted_by VARCHAR(100) NULL,
  posted_at DATETIME NULL,
  UNIQUE KEY uk_tax_invoice_direction_no (tax_direction, tax_invoice_no),
  INDEX idx_tax_invoice_period (tax_period, tax_direction, status),
  INDEX idx_tax_invoice_source (source_module, source_id)
);

UPDATE sys_menu SET nav_act='incoming_payment', main_table='erp_incoming_payment' WHERE url='incoming-payment';
UPDATE sys_menu SET nav_act='ar_aging', main_table='sales_invoice' WHERE url='ar-aging';
UPDATE sys_menu SET nav_act='tax_invoice_in', main_table='erp_tax_invoice' WHERE url='tax-invoice-in';
UPDATE sys_menu SET nav_act='tax_invoice_out', main_table='erp_tax_invoice' WHERE url='tax-invoice-out';
UPDATE sys_menu SET nav_act='vat_report', main_table='erp_tax_invoice' WHERE url='vat-report';
