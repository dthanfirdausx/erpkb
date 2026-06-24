-- =====================================================
-- CREDIT / DEBIT MEMO MODULE
-- SAP SD billing adjustment based on existing invoice
-- =====================================================

ALTER TABLE sales_invoice
  ADD COLUMN IF NOT EXISTS memo_reason_code varchar(50) NULL AFTER dp_open_amount,
  ADD COLUMN IF NOT EXISTS memo_reason_text varchar(255) NULL AFTER memo_reason_code,
  ADD COLUMN IF NOT EXISTS original_invoice_id int(11) NULL AFTER memo_reason_text;

ALTER TABLE sales_invoice_detail
  ADD COLUMN IF NOT EXISTS original_invoice_detail_id int(11) NULL AFTER billing_item_type;

CREATE INDEX IF NOT EXISTS idx_sales_invoice_original ON sales_invoice(original_invoice_id);

SET @sales := (SELECT id FROM sys_menu WHERE page_name='Sales & Distribution' AND parent=0 LIMIT 1);
SET @billing := (SELECT id FROM sys_menu WHERE page_name='Billing' AND parent=@sales LIMIT 1);

UPDATE sys_menu
SET parent=@billing,
    parent_name='Billing',
    nav_act='credit_debit_memo',
    page_name='Credit / Debit Memo',
    main_table='sales_invoice',
    icon='fa-adjust',
    urutan_menu=3,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='credit-debit-memo';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'Y', 'Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> '') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='credit-debit-memo'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='Y',
    r.update_act='Y',
    r.delete_act='Y',
    r.import_act='Y'
WHERE m.url='credit-debit-memo';
