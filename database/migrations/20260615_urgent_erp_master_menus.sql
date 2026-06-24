-- Menu structure for urgent ERP master data.

UPDATE sys_menu SET page_name='Business Partner Customer', main_table='penerima', urutan_menu=1
WHERE url='master-customer';
UPDATE sys_menu SET tampil='N' WHERE url='penerima';

UPDATE sys_menu SET urutan_menu=1 WHERE id=483;
UPDATE sys_menu SET urutan_menu=2 WHERE id=484;
UPDATE sys_menu SET urutan_menu=3 WHERE id=485;
UPDATE sys_menu SET urutan_menu=6 WHERE id=486;
UPDATE sys_menu SET urutan_menu=7 WHERE id=487;
UPDATE sys_menu SET urutan_menu=8 WHERE id=328;
UPDATE sys_menu SET urutan_menu=9 WHERE id=488;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Purchasing Master', '', '', 'fa-shopping-cart', 4, 308, 'Master Data', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=308 AND page_name='Purchasing Master');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Sales Master', '', '', 'fa-line-chart', 5, 308, 'Master Data', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=308 AND page_name='Sales Master');

SET @purchasing_parent=(SELECT id FROM sys_menu WHERE parent=308 AND page_name='Purchasing Master' LIMIT 1);
SET @sales_parent=(SELECT id FROM sys_menu WHERE parent=308 AND page_name='Sales Master' LIMIT 1);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Plant','plant','erp_plant','fa-building-o',3,483,'Organisasi','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='plant');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Material Type','material-type','erp_material_type','fa-tags',2,485,'Material & Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='material-type');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Material Group','material-group','erp_material_group','fa-object-group',3,485,'Material & Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='material-group');
UPDATE sys_menu SET urutan_menu=4 WHERE url='kategori-barang';
UPDATE sys_menu SET urutan_menu=5 WHERE url='satuan';
UPDATE sys_menu SET urutan_menu=6 WHERE url='satuan-packing';
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Storage Location','storage-location','erp_storage_location','fa-archive',7,485,'Material & Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='storage-location');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Storage Bin','storage-bin','erp_storage_bin','fa-th',8,485,'Material & Inventory','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='storage-bin');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Purchasing Organization','purchasing-organization','erp_purchasing_organization','fa-sitemap',1,@purchasing_parent,'Purchasing Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='purchasing-organization');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Purchasing Group','purchasing-group','erp_purchasing_group','fa-users',2,@purchasing_parent,'Purchasing Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='purchasing-group');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Sales Organization','sales-organization','erp_sales_organization','fa-sitemap',1,@sales_parent,'Sales Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='sales-organization');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Distribution Channel','distribution-channel','erp_distribution_channel','fa-share-alt',2,@sales_parent,'Sales Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='distribution-channel');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Shipping Point','shipping-point','erp_shipping_point','fa-truck',3,@sales_parent,'Sales Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='shipping-point');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Cost Center','cost-center','erp_cost_center','fa-bullseye',6,487,'Finance Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='cost-center');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Profit Center','profit-center','erp_profit_center','fa-crosshairs',7,487,'Finance Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='profit-center');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Fiscal Period','fiscal-period','erp_financial_period','fa-calendar',8,487,'Finance Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='fiscal-period');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Tax Code','tax-code','erp_tax_code','fa-percent',9,487,'Finance Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='tax-code');
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_master','Exchange Rate','exchange-rate','erp_exchange_rate','fa-exchange',10,487,'Finance Master','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='exchange-rate');

-- Administrators maintain all new master data.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id, g.group_level, 'Y','Y','Y','Y','N'
FROM sys_menu m
JOIN (SELECT 'admin' group_level UNION ALL SELECT 'system_administrator') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE (m.url IN ('plant','material-type','material-group','storage-location','storage-bin','purchasing-organization','purchasing-group','sales-organization','distribution-channel','shipping-point','cost-center','profit-center','fiscal-period','tax-code','exchange-rate')
       OR m.id IN (@purchasing_parent,@sales_parent)) AND r.id IS NULL;

-- Functional ownership.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id, x.group_level, 'Y',x.write_act,x.write_act,x.write_act,'N'
FROM sys_menu m
JOIN (
 SELECT 'gudang' group_level, 'Y' write_act, 'material-type' url UNION ALL
 SELECT 'gudang','Y','material-group' UNION ALL SELECT 'gudang','Y','storage-location' UNION ALL SELECT 'gudang','Y','storage-bin' UNION ALL SELECT 'gudang','N','plant' UNION ALL
 SELECT 'purchasing','Y','purchasing-organization' UNION ALL SELECT 'purchasing','Y','purchasing-group' UNION ALL SELECT 'purchasing','N','plant' UNION ALL SELECT 'purchasing','N','material-type' UNION ALL SELECT 'purchasing','N','material-group' UNION ALL
 SELECT 'sales','Y','sales-organization' UNION ALL SELECT 'sales','Y','distribution-channel' UNION ALL SELECT 'sales','Y','shipping-point' UNION ALL SELECT 'sales','N','plant' UNION ALL
 SELECT 'finance_akunting','Y','cost-center' UNION ALL SELECT 'finance_akunting','Y','profit-center' UNION ALL SELECT 'finance_akunting','Y','fiscal-period' UNION ALL SELECT 'finance_akunting','Y','tax-code' UNION ALL SELECT 'finance_akunting','Y','exchange-rate' UNION ALL
 SELECT 'ppic','N','plant' UNION ALL SELECT 'ppic','N','material-type' UNION ALL SELECT 'ppic','N','material-group' UNION ALL SELECT 'ppic','N','storage-location' UNION ALL SELECT 'ppic','N','storage-bin' UNION ALL
 SELECT 'produksi','N','plant' UNION ALL SELECT 'produksi','N','material-type' UNION ALL SELECT 'produksi','N','material-group' UNION ALL
 SELECT 'quality_control','N','material-type' UNION ALL SELECT 'quality_control','N','material-group' UNION ALL
 SELECT 'auditor','N','cost-center' UNION ALL SELECT 'auditor','N','profit-center' UNION ALL SELECT 'auditor','N','fiscal-period' UNION ALL SELECT 'auditor','N','tax-code' UNION ALL SELECT 'auditor','N','exchange-rate' UNION ALL
 SELECT 'manager_approver','N','cost-center' UNION ALL SELECT 'manager_approver','N','profit-center' UNION ALL SELECT 'manager_approver','N','fiscal-period' UNION ALL SELECT 'manager_approver','N','tax-code' UNION ALL SELECT 'manager_approver','N','exchange-rate'
) x ON x.url=m.url
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=x.group_level
WHERE r.id IS NULL;

-- New category visibility for its functional users.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT @purchasing_parent,'purchasing','Y','N','N','N','N'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu_role WHERE id_menu=@purchasing_parent AND group_level='purchasing');
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT @sales_parent,'sales','Y','N','N','N','N'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu_role WHERE id_menu=@sales_parent AND group_level='sales');

-- Customer is now the single Business Partner Customer menu.
UPDATE sys_menu_role SET read_act='Y',insert_act='Y',update_act='Y',delete_act='Y'
WHERE id_menu=(SELECT id FROM sys_menu WHERE url='master-customer' LIMIT 1) AND group_level='sales';
UPDATE sys_menu_role SET read_act='Y'
WHERE id_menu=(SELECT id FROM sys_menu WHERE url='master-customer' LIMIT 1) AND group_level IN ('purchasing','finance_akunting');

-- Each master is routed to its own CRUD module folder.
UPDATE sys_menu SET nav_act='business_partner_customer' WHERE url='master-customer';
UPDATE sys_menu SET nav_act='plant' WHERE url='plant';
UPDATE sys_menu SET nav_act='storage_location' WHERE url='storage-location';
UPDATE sys_menu SET nav_act='storage_bin' WHERE url='storage-bin';
UPDATE sys_menu SET nav_act='material_type' WHERE url='material-type';
UPDATE sys_menu SET nav_act='material_group' WHERE url='material-group';
UPDATE sys_menu SET nav_act='purchasing_organization' WHERE url='purchasing-organization';
UPDATE sys_menu SET nav_act='purchasing_group' WHERE url='purchasing-group';
UPDATE sys_menu SET nav_act='sales_organization' WHERE url='sales-organization';
UPDATE sys_menu SET nav_act='distribution_channel' WHERE url='distribution-channel';
UPDATE sys_menu SET nav_act='shipping_point' WHERE url='shipping-point';
UPDATE sys_menu SET nav_act='cost_center' WHERE url='cost-center';
UPDATE sys_menu SET nav_act='profit_center' WHERE url='profit-center';
UPDATE sys_menu SET nav_act='fiscal_period' WHERE url='fiscal-period';
UPDATE sys_menu SET nav_act='tax_code' WHERE url='tax-code';
UPDATE sys_menu SET nav_act='exchange_rate' WHERE url='exchange-rate';
UPDATE sys_menu SET nav_act='shift_management' WHERE url='shift-management';
UPDATE sys_menu SET nav_act='factory_calendar' WHERE url='factory-calendar';
