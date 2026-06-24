-- SAP-like FI-AR Customer Invoice module.

ALTER TABLE sales_invoice
  ADD COLUMN IF NOT EXISTS ar_account VARCHAR(50) NULL AFTER tax_code,
  ADD COLUMN IF NOT EXISTS revenue_account VARCHAR(50) NULL AFTER ar_account,
  ADD COLUMN IF NOT EXISTS tax_account VARCHAR(50) NULL AFTER revenue_account,
  ADD COLUMN IF NOT EXISTS journal_header_id INT NULL AFTER billing_status,
  ADD COLUMN IF NOT EXISTS reversal_journal_header_id INT NULL AFTER journal_header_id;

CREATE INDEX IF NOT EXISTS idx_sales_invoice_journal_header ON sales_invoice (journal_header_id);
CREATE INDEX IF NOT EXISTS idx_sales_invoice_bill_to_status ON sales_invoice (bill_to, billing_status);

UPDATE sys_menu
SET nav_act = 'customer_invoice',
    main_table = 'sales_invoice'
WHERE url = 'customer-invoice';
