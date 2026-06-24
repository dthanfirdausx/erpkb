-- =====================================================
-- PROFORMA INVOICE MODULE
-- SAP SD non-accounting proforma billing
-- =====================================================

ALTER TABLE sales_invoice
  ADD COLUMN IF NOT EXISTS proforma_valid_until date NULL AFTER original_invoice_id;

SET @sales := (SELECT id FROM sys_menu WHERE page_name='Sales & Distribution' AND parent=0 LIMIT 1);
SET @billing := (SELECT id FROM sys_menu WHERE page_name='Billing' AND parent=@sales LIMIT 1);

UPDATE sys_menu
SET parent=@billing,
    parent_name='Billing',
    nav_act='proforma_invoice',
    page_name='Proforma Invoice',
    main_table='sales_invoice',
    icon='fa-file-text-o',
    urutan_menu=4,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='proforma-invoice';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'Y', 'Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> '') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='proforma-invoice'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='Y',
    r.update_act='Y',
    r.delete_act='Y',
    r.import_act='Y'
WHERE m.url='proforma-invoice';
