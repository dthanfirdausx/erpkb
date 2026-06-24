ALTER TABLE sales_invoice
  ADD COLUMN IF NOT EXISTS billing_type VARCHAR(10) NOT NULL DEFAULT 'F2' AFTER id_sales,
  ADD COLUMN IF NOT EXISTS billing_status ENUM('DRAFT','POSTED','CANCELLED','REVERSED') NOT NULL DEFAULT 'POSTED' AFTER tax,
  ADD COLUMN IF NOT EXISTS posting_date DATE NULL AFTER invoice_date,
  ADD COLUMN IF NOT EXISTS due_date DATE NULL AFTER term,
  ADD COLUMN IF NOT EXISTS tax_code VARCHAR(20) NULL AFTER tax,
  ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(9,4) NOT NULL DEFAULT 11.0000 AFTER tax_code,
  ADD COLUMN IF NOT EXISTS net_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER tax_rate,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER net_amount,
  ADD COLUMN IF NOT EXISTS gross_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER tax_amount,
  ADD COLUMN IF NOT EXISTS created_by VARCHAR(50) NULL AFTER date_created,
  ADD COLUMN IF NOT EXISTS posted_by VARCHAR(50) NULL AFTER created_by,
  ADD COLUMN IF NOT EXISTS posted_at DATETIME NULL AFTER posted_by,
  ADD COLUMN IF NOT EXISTS cancelled_by VARCHAR(50) NULL AFTER posted_at,
  ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER cancelled_by,
  ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(255) NULL AFTER cancelled_at,
  ADD INDEX IF NOT EXISTS idx_sales_invoice_status (billing_status),
  ADD INDEX IF NOT EXISTS idx_sales_invoice_due_date (due_date),
  ADD INDEX IF NOT EXISTS idx_sales_invoice_posting_date (posting_date),
  ADD INDEX IF NOT EXISTS idx_sales_invoice_do (no_do);

ALTER TABLE sales_invoice_detail
  ADD COLUMN IF NOT EXISTS sales_order_detail_id BIGINT NULL AFTER id_sales,
  ADD COLUMN IF NOT EXISTS surat_jalan_detail_id INT NULL AFTER sales_order_detail_id,
  ADD COLUMN IF NOT EXISTS line_no INT NULL AFTER surat_jalan_detail_id,
  ADD COLUMN IF NOT EXISTS tax_code VARCHAR(20) NULL AFTER nilai,
  ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(9,4) NOT NULL DEFAULT 11.0000 AFTER tax_code,
  ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER tax_rate,
  ADD COLUMN IF NOT EXISTS gross_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER tax_amount,
  ADD INDEX IF NOT EXISTS idx_sid_so_detail (sales_order_detail_id),
  ADD INDEX IF NOT EXISTS idx_sid_sj_detail (surat_jalan_detail_id);

UPDATE sales_invoice si
LEFT JOIN (
  SELECT id_sales, COALESCE(SUM(nilai),0) subtotal
  FROM sales_invoice_detail
  GROUP BY id_sales
) d ON d.id_sales=si.id_sales
SET si.posting_date = COALESCE(si.posting_date, si.invoice_date),
    si.billing_status = COALESCE(NULLIF(si.billing_status,''),'POSTED'),
    si.tax_code = COALESCE(NULLIF(si.tax_code,''), CASE WHEN si.tax='1' THEN 'PPN11' ELSE 'NON_TAX' END),
    si.tax_rate = CASE WHEN si.tax='1' THEN 11.0000 ELSE 0.0000 END,
    si.net_amount = COALESCE(d.subtotal,0),
    si.tax_amount = CASE WHEN si.tax='1' THEN ROUND(COALESCE(d.subtotal,0) * 0.11, 2) ELSE 0 END,
    si.gross_amount = COALESCE(d.subtotal,0) + CASE WHEN si.tax='1' THEN ROUND(COALESCE(d.subtotal,0) * 0.11, 2) ELSE 0 END,
    si.due_date = COALESCE(
      si.due_date,
      CASE
        WHEN CAST(NULLIF(REGEXP_REPLACE(COALESCE(si.term,''),'[^0-9]',''),'') AS UNSIGNED) > 0
        THEN DATE_ADD(si.invoice_date, INTERVAL CAST(NULLIF(REGEXP_REPLACE(COALESCE(si.term,''),'[^0-9]',''),'') AS UNSIGNED) DAY)
        ELSE NULL
      END
    ),
    si.posted_at = COALESCE(si.posted_at, si.date_created),
    si.posted_by = COALESCE(si.posted_by, si.created_by);

UPDATE sales_invoice_detail d
JOIN sales_invoice si ON si.id_sales=d.id_sales
SET d.tax_code = COALESCE(NULLIF(d.tax_code,''), si.tax_code),
    d.tax_rate = si.tax_rate,
    d.tax_amount = CASE WHEN si.tax='1' THEN ROUND(COALESCE(d.nilai,0) * 0.11, 2) ELSE 0 END,
    d.gross_amount = COALESCE(d.nilai,0) + CASE WHEN si.tax='1' THEN ROUND(COALESCE(d.nilai,0) * 0.11, 2) ELSE 0 END;
