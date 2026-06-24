-- Restructure Goods Issue into ERP workspace placeholders requested by user.
-- All child menus point to erp_workspace for now until each module is developed.

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Goods Issue', '', '', 'fa-upload', 4, 334, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main');

SET @gi_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main' LIMIT 1);

UPDATE sys_menu
SET parent=334,
    parent_name='Warehouse',
    urutan_menu=4,
    icon='fa-upload',
    tampil='Y'
WHERE id=@gi_parent;

-- Hide older/previous Goods Issue children that are not part of the requested structure.
UPDATE sys_menu
SET tampil='N'
WHERE parent=@gi_parent
  AND url IN (
    'picking-pengeluaran',
    'gi-production-order',
    'gi-production-reversal',
    'gi-cost-center',
    'pengeluaran-hamparan',
    'surat-jalan',
    'mutasi-scrap',
    'gi-sampling-rnd',
    'goods-issue-monitor',
    'outbound-customs-monitor'
  );

-- Keep the real Return to Vendor transaction under Goods Receipt; add a GI workspace entry with a different URL.

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Issue to Production','issue-to-production','detail_transaksi','fa-industry',1,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='issue-to-production');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Issue to Production', main_table='detail_transaksi', icon='fa-industry',
    urutan_menu=1, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='issue-to-production';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Issue to Cost Center','issue-to-cost-center','detail_transaksi','fa-building',2,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='issue-to-cost-center');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Issue to Cost Center', main_table='detail_transaksi', icon='fa-building',
    urutan_menu=2, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='issue-to-cost-center';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Issue to Asset','issue-to-asset','detail_transaksi','fa-cubes',3,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='issue-to-asset');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Issue to Asset', main_table='detail_transaksi', icon='fa-cubes',
    urutan_menu=3, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='issue-to-asset';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Scrap Issue','scrap-issue','detail_transaksi','fa-trash',4,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='scrap-issue');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Scrap Issue', main_table='detail_transaksi', icon='fa-trash',
    urutan_menu=4, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='scrap-issue';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Sample Issue','sample-issue','detail_transaksi','fa-flask',5,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='sample-issue');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Sample Issue', main_table='detail_transaksi', icon='fa-flask',
    urutan_menu=5, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='sample-issue';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Return to Vendor','gi-return-to-vendor','erp_vendor_return','fa-reply',6,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gi-return-to-vendor');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Return to Vendor', main_table='erp_vendor_return', icon='fa-reply',
    urutan_menu=6, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='gi-return-to-vendor';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Other Goods Issue','other-goods-issue','detail_transaksi','fa-random',7,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='other-goods-issue');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Other Goods Issue', main_table='detail_transaksi', icon='fa-random',
    urutan_menu=7, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='other-goods-issue';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Goods Issue History','goods-issue-history','detail_transaksi','fa-history',8,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='goods-issue-history');

UPDATE sys_menu
SET nav_act='erp_workspace', page_name='Goods Issue History', main_table='detail_transaksi', icon='fa-history',
    urutan_menu=8, parent=@gi_parent, parent_name='Goods Issue', dt_table='Y', tampil='Y', type_menu='page'
WHERE url='goods-issue-history';

-- Role access for placeholder workspace menus.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','sales','beacukai','auditor','finance_akunting')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.parent=@gi_parent
  AND m.url IN ('issue-to-production','issue-to-cost-center','issue-to-asset','scrap-issue','sample-issue','gi-return-to-vendor','other-goods-issue','goods-issue-history')
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.parent=@gi_parent
  AND m.url IN ('issue-to-production','issue-to-cost-center','issue-to-asset','scrap-issue','sample-issue','gi-return-to-vendor','other-goods-issue','goods-issue-history')
  AND r.group_level IN ('admin','system_administrator','gudang','produksi','ppic','sales','beacukai','auditor','finance_akunting');
