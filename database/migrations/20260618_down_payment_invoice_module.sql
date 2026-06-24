-- =====================================================
-- DOWN PAYMENT INVOICE MODULE
-- SAP SD/FI-AR down payment billing based on Sales Order
-- =====================================================

ALTER TABLE sales_invoice
  ADD COLUMN IF NOT EXISTS reference_type varchar(30) NULL AFTER billing_type,
  ADD COLUMN IF NOT EXISTS reference_no varchar(100) NULL AFTER reference_type,
  ADD COLUMN IF NOT EXISTS dp_percent decimal(9,4) NOT NULL DEFAULT 0.0000 AFTER gross_amount,
  ADD COLUMN IF NOT EXISTS dp_base_amount decimal(18,2) NOT NULL DEFAULT 0.00 AFTER dp_percent,
  ADD COLUMN IF NOT EXISTS dp_applied_amount decimal(18,2) NOT NULL DEFAULT 0.00 AFTER dp_base_amount,
  ADD COLUMN IF NOT EXISTS dp_open_amount decimal(18,2) NOT NULL DEFAULT 0.00 AFTER dp_applied_amount;

ALTER TABLE sales_invoice_detail
  ADD COLUMN IF NOT EXISTS billing_item_type varchar(30) NULL AFTER line_no;

CREATE INDEX IF NOT EXISTS idx_sales_invoice_reference ON sales_invoice(reference_type, reference_no);

INSERT INTO rekening (no_rek, induk, level, nama_rek, mapping_coa, kat_coa, jenis)
SELECT '21401', '21', 3, 'Uang Muka Pelanggan', NULL, 2, 2
WHERE NOT EXISTS (SELECT 1 FROM rekening WHERE no_rek='21401');

SET @sales := (SELECT id FROM sys_menu WHERE page_name='Sales & Distribution' AND parent=0 LIMIT 1);
SET @billing := (SELECT id FROM sys_menu WHERE page_name='Billing' AND parent=@sales LIMIT 1);

UPDATE sys_menu
SET parent=@billing,
    parent_name='Billing',
    nav_act='down_payment_invoice',
    page_name='Down Payment Invoice',
    main_table='sales_invoice',
    icon='fa-credit-card',
    urutan_menu=2,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='down-payment-invoice';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'Y', 'Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> '') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='down-payment-invoice'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='Y',
    r.update_act='Y',
    r.delete_act='Y',
    r.import_act='Y'
WHERE m.url='down-payment-invoice';
