-- =====================================================
-- BILLING MENU STRUCTURE
-- Sales & Distribution > Billing
-- =====================================================

SET @sales := (SELECT id FROM sys_menu WHERE page_name='Sales & Distribution' AND parent=0 LIMIT 1);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Billing', '', '', 'fa-file-text-o', 4, @sales, 'Sales & Distribution', 'N', 'Y', 'main'
WHERE @sales IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Billing' AND parent=@sales);

SET @billing := (SELECT id FROM sys_menu WHERE page_name='Billing' AND parent=@sales LIMIT 1);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'sales_invoice', 'Sales Invoice', 'sales-invoice', 'sales_invoice', 'fa-file-text', 1, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE @billing IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='sales-invoice');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Down Payment Invoice', 'down-payment-invoice', 'sales_invoice', 'fa-credit-card', 2, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE @billing IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='down-payment-invoice');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Credit / Debit Memo', 'credit-debit-memo', 'sales_invoice', 'fa-adjust', 3, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE @billing IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='credit-debit-memo');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'erp_workspace', 'Proforma Invoice', 'proforma-invoice', 'sales_order', 'fa-file-text-o', 4, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE @billing IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='proforma-invoice');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'billing_history', 'Billing History', 'billing-history', 'sales_invoice', 'fa-history', 5, @billing, 'Billing', 'Y', 'Y', 'page'
WHERE @billing IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='billing-history');

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='sales_invoice', page_name='Sales Invoice',
    main_table='sales_invoice', icon='fa-file-text', urutan_menu=1, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sales-invoice';

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='erp_workspace', page_name='Down Payment Invoice',
    main_table='sales_invoice', icon='fa-credit-card', urutan_menu=2, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='down-payment-invoice';

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='erp_workspace', page_name='Credit / Debit Memo',
    main_table='sales_invoice', icon='fa-adjust', urutan_menu=3, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='credit-debit-memo';

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='erp_workspace', page_name='Proforma Invoice',
    main_table='sales_order', icon='fa-file-text-o', urutan_menu=4, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='proforma-invoice';

UPDATE sys_menu
SET parent=@billing, parent_name='Billing', nav_act='billing_history', page_name='Billing History',
    main_table='sales_invoice', icon='fa-history', urutan_menu=5, dt_table='Y', tampil='Y', type_menu='page'
WHERE url='billing-history';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'Y', 'Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'') <> '') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url IN ('sales-invoice','down-payment-invoice','credit-debit-memo','proforma-invoice','billing-history')
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='Y',
    r.update_act='Y',
    r.delete_act='Y',
    r.import_act='Y'
WHERE m.url IN ('sales-invoice','down-payment-invoice','credit-debit-memo','proforma-invoice','billing-history');
