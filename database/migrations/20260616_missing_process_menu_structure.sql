-- Add ERP process menu structure for areas that do not have full modules yet.
-- These menus are intentionally routed to erp_workspace as placeholders.

SET @ppic_parent=(SELECT id FROM sys_menu WHERE page_name='PPIC' AND type_menu='main' LIMIT 1);
SET @produksi_parent=(SELECT id FROM sys_menu WHERE page_name='produksi' AND type_menu='main' LIMIT 1);
SET @sales_parent=(SELECT id FROM sys_menu WHERE page_name='sales' AND type_menu='main' LIMIT 1);
SET @qm_parent=(SELECT id FROM sys_menu WHERE page_name='quality management' AND type_menu='main' LIMIT 1);
SET @acct_parent=(SELECT id FROM sys_menu WHERE page_name='akunting' AND type_menu='main' LIMIT 1);

-- Production execution gap after Issue to Production.
UPDATE sys_menu
SET urutan_menu=urutan_menu+1
WHERE parent=@produksi_parent
  AND tampil='Y'
  AND urutan_menu>=2
  AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM sys_menu WHERE url='production-confirmation') existing_menu);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Confirmation','production-confirmation','production_order_confirmation','fa-check-square-o',2,@produksi_parent,'produksi','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-confirmation');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Production Confirmation',
    main_table='production_order_confirmation',
    icon='fa-check-square-o',
    urutan_menu=2,
    parent=@produksi_parent,
    parent_name='produksi',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-confirmation';

-- Keep PPIC planning structure, but add a visible shortcut to the execution confirmation.
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Confirmation','ppic-production-confirmation','production_order_confirmation','fa-check-square-o',9,@ppic_parent,'PPIC','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='ppic-production-confirmation');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Production Confirmation',
    main_table='production_order_confirmation',
    icon='fa-check-square-o',
    urutan_menu=9,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='ppic-production-confirmation';

-- Sales process structure.
UPDATE sys_menu
SET urutan_menu=1,
    parent=@sales_parent,
    parent_name='sales',
    tampil='Y'
WHERE url='sales-quotation';

UPDATE sys_menu
SET urutan_menu=2,
    parent=@sales_parent,
    parent_name='sales',
    tampil='Y'
WHERE url='sales-order';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Outbound Delivery','outbound-delivery','sales_order','fa-truck',3,@sales_parent,'sales','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='outbound-delivery');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Outbound Delivery', main_table='sales_order', icon='fa-truck',
    urutan_menu=3, parent=@sales_parent, parent_name='sales', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='outbound-delivery';

UPDATE sys_menu
SET urutan_menu=4
WHERE url='sales-invoice';

UPDATE sys_menu
SET urutan_menu=5
WHERE url='sales-return';

-- Quality process structure.
UPDATE sys_menu
SET urutan_menu=urutan_menu+1
WHERE parent=@qm_parent
  AND tampil='Y'
  AND urutan_menu>=1
  AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM sys_menu WHERE url='quality-dashboard') existing_menu);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Quality Dashboard','quality-dashboard','data_ng','fa-dashboard',1,@qm_parent,'quality management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='quality-dashboard');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Quality Dashboard', main_table='data_ng', icon='fa-dashboard',
    urutan_menu=1, parent=@qm_parent, parent_name='quality management', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='quality-dashboard';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Inspection Lot','inspection-lot','incoming_terima','fa-clipboard',2,@qm_parent,'quality management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='inspection-lot');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Inspection Lot', main_table='incoming_terima', icon='fa-clipboard',
    urutan_menu=2, parent=@qm_parent, parent_name='quality management', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='inspection-lot';

UPDATE sys_menu SET urutan_menu=3 WHERE url='incoming-inspection';
UPDATE sys_menu SET urutan_menu=4 WHERE url='process-inspection';
UPDATE sys_menu SET urutan_menu=5 WHERE url='final-inspection';
UPDATE sys_menu SET urutan_menu=6 WHERE url='quality-notification';
UPDATE sys_menu SET urutan_menu=7 WHERE url='capa';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Usage Decision','usage-decision','data_ng','fa-gavel',8,@qm_parent,'quality management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='usage-decision');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Usage Decision', main_table='data_ng', icon='fa-gavel',
    urutan_menu=8, parent=@qm_parent, parent_name='quality management', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='usage-decision';

-- Accounting process groups.
UPDATE sys_menu
SET nav_act='', url='', main_table='', page_name='Cash and Bank', icon='fa-bank',
    urutan_menu=7, parent=@acct_parent, parent_name='akunting', dt_table='Y', tampil='Y', type_menu='main'
WHERE url='cash-bank' OR (parent=@acct_parent AND page_name='Cash and Bank');

UPDATE sys_menu
SET nav_act='', url='', main_table='', page_name='Accounts Payable', icon='fa-money',
    urutan_menu=8, parent=@acct_parent, parent_name='akunting', dt_table='Y', tampil='Y', type_menu='main'
WHERE url='accounts-payable' OR (parent=@acct_parent AND page_name='Accounts Payable');

UPDATE sys_menu
SET nav_act='', url='', main_table='', page_name='Accounts Receivable', icon='fa-credit-card',
    urutan_menu=9, parent=@acct_parent, parent_name='akunting', dt_table='Y', tampil='Y', type_menu='main'
WHERE url='accounts-receivable' OR (parent=@acct_parent AND page_name='Accounts Receivable');

UPDATE sys_menu
SET nav_act='', url='', main_table='', page_name='Tax Management', icon='fa-percent',
    urutan_menu=10, parent=@acct_parent, parent_name='akunting', dt_table='Y', tampil='Y', type_menu='main'
WHERE url='tax-management' OR (parent=@acct_parent AND page_name='Tax Management');

SET @cash_parent=(SELECT id FROM sys_menu WHERE parent=@acct_parent AND page_name='Cash and Bank' AND type_menu='main' LIMIT 1);
SET @ap_parent=(SELECT id FROM sys_menu WHERE parent=@acct_parent AND page_name='Accounts Payable' AND type_menu='main' LIMIT 1);
SET @ar_parent=(SELECT id FROM sys_menu WHERE parent=@acct_parent AND page_name='Accounts Receivable' AND type_menu='main' LIMIT 1);
SET @tax_parent=(SELECT id FROM sys_menu WHERE parent=@acct_parent AND page_name='Tax Management' AND type_menu='main' LIMIT 1);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Cash Journal','cash-journal','jurnal_header','fa-book',1,@cash_parent,'Cash and Bank','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='cash-journal');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Cash Journal', main_table='jurnal_header', icon='fa-book', urutan_menu=1, parent=@cash_parent, parent_name='Cash and Bank', dt_table='Y', tampil='Y', type_menu='page' WHERE url='cash-journal';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Bank Receipt','bank-receipt','jurnal_header','fa-download',2,@cash_parent,'Cash and Bank','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='bank-receipt');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Bank Receipt', main_table='jurnal_header', icon='fa-download', urutan_menu=2, parent=@cash_parent, parent_name='Cash and Bank', dt_table='Y', tampil='Y', type_menu='page' WHERE url='bank-receipt';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Bank Payment','bank-payment','jurnal_header','fa-upload',3,@cash_parent,'Cash and Bank','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='bank-payment');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Bank Payment', main_table='jurnal_header', icon='fa-upload', urutan_menu=3, parent=@cash_parent, parent_name='Cash and Bank', dt_table='Y', tampil='Y', type_menu='page' WHERE url='bank-payment';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Bank Reconciliation','bank-reconciliation','jurnal_header','fa-check',4,@cash_parent,'Cash and Bank','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='bank-reconciliation');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Bank Reconciliation', main_table='jurnal_header', icon='fa-check', urutan_menu=4, parent=@cash_parent, parent_name='Cash and Bank', dt_table='Y', tampil='Y', type_menu='page' WHERE url='bank-reconciliation';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Vendor Invoice','vendor-invoice','jurnal_header','fa-file-text-o',1,@ap_parent,'Accounts Payable','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='vendor-invoice');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Vendor Invoice', main_table='jurnal_header', icon='fa-file-text-o', urutan_menu=1, parent=@ap_parent, parent_name='Accounts Payable', dt_table='Y', tampil='Y', type_menu='page' WHERE url='vendor-invoice';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Vendor Payment','vendor-payment','jurnal_header','fa-money',2,@ap_parent,'Accounts Payable','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='vendor-payment');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Vendor Payment', main_table='jurnal_header', icon='fa-money', urutan_menu=2, parent=@ap_parent, parent_name='Accounts Payable', dt_table='Y', tampil='Y', type_menu='page' WHERE url='vendor-payment';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','AP Aging','ap-aging','jurnal_header','fa-clock-o',3,@ap_parent,'Accounts Payable','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='ap-aging');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='AP Aging', main_table='jurnal_header', icon='fa-clock-o', urutan_menu=3, parent=@ap_parent, parent_name='Accounts Payable', dt_table='Y', tampil='Y', type_menu='page' WHERE url='ap-aging';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Customer Invoice','customer-invoice','sales_invoice','fa-file-text',1,@ar_parent,'Accounts Receivable','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='customer-invoice');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Customer Invoice', main_table='sales_invoice', icon='fa-file-text', urutan_menu=1, parent=@ar_parent, parent_name='Accounts Receivable', dt_table='Y', tampil='Y', type_menu='page' WHERE url='customer-invoice';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Incoming Payment','incoming-payment','jurnal_header','fa-download',2,@ar_parent,'Accounts Receivable','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='incoming-payment');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Incoming Payment', main_table='jurnal_header', icon='fa-download', urutan_menu=2, parent=@ar_parent, parent_name='Accounts Receivable', dt_table='Y', tampil='Y', type_menu='page' WHERE url='incoming-payment';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','AR Aging','ar-aging','jurnal_header','fa-clock-o',3,@ar_parent,'Accounts Receivable','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='ar-aging');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='AR Aging', main_table='jurnal_header', icon='fa-clock-o', urutan_menu=3, parent=@ar_parent, parent_name='Accounts Receivable', dt_table='Y', tampil='Y', type_menu='page' WHERE url='ar-aging';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Tax Invoice In','tax-invoice-in','pajak','fa-sign-in',1,@tax_parent,'Tax Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='tax-invoice-in');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Tax Invoice In', main_table='pajak', icon='fa-sign-in', urutan_menu=1, parent=@tax_parent, parent_name='Tax Management', dt_table='Y', tampil='Y', type_menu='page' WHERE url='tax-invoice-in';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Tax Invoice Out','tax-invoice-out','pajak','fa-sign-out',2,@tax_parent,'Tax Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='tax-invoice-out');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='Tax Invoice Out', main_table='pajak', icon='fa-sign-out', urutan_menu=2, parent=@tax_parent, parent_name='Tax Management', dt_table='Y', tampil='Y', type_menu='page' WHERE url='tax-invoice-out';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','VAT Report','vat-report','pajak','fa-file-excel-o',3,@tax_parent,'Tax Management','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='vat-report');
UPDATE sys_menu SET nav_act='erp_workspace', page_name='VAT Report', main_table='pajak', icon='fa-file-excel-o', urutan_menu=3, parent=@tax_parent, parent_name='Tax Management', dt_table='Y', tampil='Y', type_menu='page' WHERE url='vat-report';

-- Give baseline access to new placeholder pages and converted process groups.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','manager_approver','auditor','finance_akunting','ppic','produksi','quality_control','sales','gudang')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url IN (
  'production-confirmation','ppic-production-confirmation','outbound-delivery',
  'quality-dashboard','inspection-lot','usage-decision',
  'cash-journal','bank-receipt','bank-payment','bank-reconciliation',
  'vendor-invoice','vendor-payment','ap-aging',
  'customer-invoice','incoming-payment','ar-aging',
  'tax-invoice-in','tax-invoice-out','vat-report'
)
AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator') THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE m.url IN (
  'production-confirmation','ppic-production-confirmation','outbound-delivery',
  'quality-dashboard','inspection-lot','usage-decision',
  'cash-journal','bank-receipt','bank-payment','bank-reconciliation',
  'vendor-invoice','vendor-payment','ap-aging',
  'customer-invoice','incoming-payment','ar-aging',
  'tax-invoice-in','tax-invoice-out','vat-report'
);
