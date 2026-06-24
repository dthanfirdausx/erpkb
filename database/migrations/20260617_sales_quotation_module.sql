-- =====================================================
-- SALES QUOTATION MODULE
-- SAP SD Quotation compatible with existing sales_order flow
-- =====================================================

ALTER TABLE sales_quotation
  ADD COLUMN IF NOT EXISTS inquiry_id INT(11) NULL AFTER id_quotation,
  ADD COLUMN IF NOT EXISTS subject VARCHAR(200) NULL AFTER contact_person,
  ADD COLUMN IF NOT EXISTS requested_delivery_date DATE NULL AFTER valid_date,
  ADD COLUMN IF NOT EXISTS status ENUM('OPEN','SENT','ACCEPTED','REJECTED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'OPEN' AFTER requested_delivery_date,
  ADD COLUMN IF NOT EXISTS customer_id INT(11) NULL AFTER kode_penerima,
  ADD COLUMN IF NOT EXISTS customer_name VARCHAR(150) NULL AFTER customer_id,
  ADD COLUMN IF NOT EXISTS payment_term VARCHAR(100) NULL AFTER term,
  ADD COLUMN IF NOT EXISTS incoterm VARCHAR(30) NULL AFTER payment_term,
  ADD COLUMN IF NOT EXISTS created_by VARCHAR(50) NULL AFTER catatan,
  ADD COLUMN IF NOT EXISTS updated_by VARCHAR(50) NULL AFTER created_by,
  ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL AFTER updated_by,
  ADD COLUMN IF NOT EXISTS accepted_at DATETIME NULL AFTER updated_at,
  ADD COLUMN IF NOT EXISTS rejected_reason VARCHAR(255) NULL AFTER accepted_at;

ALTER TABLE sales_quotation
  ADD KEY IF NOT EXISTS idx_sales_quotation_date (tgl),
  ADD KEY IF NOT EXISTS idx_sales_quotation_status (status),
  ADD KEY IF NOT EXISTS idx_sales_quotation_customer (customer_id, kode_penerima),
  ADD KEY IF NOT EXISTS idx_sales_quotation_inquiry (inquiry_id);

ALTER TABLE sales_quotation_detail
  ADD COLUMN IF NOT EXISTS line_no INT(11) NOT NULL DEFAULT 10 AFTER id_quotation,
  ADD COLUMN IF NOT EXISTS uom VARCHAR(30) NULL AFTER qty,
  ADD COLUMN IF NOT EXISTS requested_delivery_date DATE NULL AFTER nilai,
  ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER price,
  ADD COLUMN IF NOT EXISTS tax_percent DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER discount_percent;

ALTER TABLE sales_quotation_detail
  ADD KEY IF NOT EXISTS idx_sales_quotation_detail_line (id_quotation, line_no);

UPDATE sys_menu
SET nav_act='sales_quotation',
    main_table='sales_quotation',
    page_name='Sales Quotation',
    tampil='Y'
WHERE url='sales-quotation';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level
  FROM sys_menu_role
  WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='sales-quotation'
  AND NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
