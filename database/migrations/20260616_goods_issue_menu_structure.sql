-- SAP-style Goods Issue menu structure.
-- This migration reorganizes existing outbound menus and adds monitor/workspace placeholders
-- without deleting old transaction data or module folders.

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Goods Issue', '', '', 'fa-upload', 4, 334, 'Warehouse', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main');

SET @gi_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main' LIMIT 1);

UPDATE sys_menu
SET page_name='Stock Overview',
    urutan_menu=1,
    parent=334,
    parent_name='Warehouse',
    tampil='Y'
WHERE url='stock-pemasukan';

UPDATE sys_menu
SET parent=334,
    parent_name='Warehouse',
    urutan_menu=2,
    tampil='Y'
WHERE page_name='Goods Receipt' AND type_menu='main';

UPDATE sys_menu
SET page_name='Transfer Posting (311)',
    parent=334,
    parent_name='Warehouse',
    urutan_menu=3,
    icon='fa-exchange',
    tampil='Y'
WHERE url='transfer-produksi';

-- Existing outbound picking module becomes the GI pre-process.
UPDATE sys_menu
SET nav_act='picking_pengeluaran',
    page_name='Picking / Staging for GI',
    url='picking-pengeluaran',
    main_table='pengeluaran_temp',
    icon='fa-list-alt',
    parent=@gi_parent,
    parent_name='Goods Issue',
    urutan_menu=1,
    tampil='Y',
    type_menu='page'
WHERE url='picking-pengeluaran';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'picking_pengeluaran','Picking / Staging for GI','picking-pengeluaran','pengeluaran_temp','fa-list-alt',1,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='picking-pengeluaran');

-- Placeholder workspaces for modules that should be developed next.
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','GI to Production Order (261)','gi-production-order','detail_transaksi','fa-industry',2,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gi-production-order');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','GI Reversal Production Order (262)','gi-production-reversal','detail_transaksi','fa-undo',3,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gi-production-reversal');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','GI to Cost Center (201)','gi-cost-center','detail_transaksi','fa-building',4,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gi-cost-center');

-- Existing goods issue transaction is currently sales/customer outbound, movement 601.
UPDATE sys_menu
SET nav_act='pengeluaran_hamparan',
    page_name='GI to Sales Delivery (601)',
    url='pengeluaran-hamparan',
    main_table='pengeluaran_hamparan',
    icon='fa-truck',
    parent=@gi_parent,
    parent_name='Goods Issue',
    urutan_menu=5,
    tampil='Y',
    type_menu='page'
WHERE url='pengeluaran-hamparan';

-- Existing surat jalan stays near sales delivery as outbound document support.
UPDATE sys_menu
SET page_name='Delivery Note / Surat Jalan',
    parent=@gi_parent,
    parent_name='Goods Issue',
    urutan_menu=6,
    icon='fa-file-text-o',
    tampil='Y'
WHERE url='surat-jalan';

-- Existing scrap mutation is a report-like menu; expose it as GI for Scrap monitor/action for now.
UPDATE sys_menu
SET nav_act='mutasi_scrap',
    page_name='GI for Scrap (551)',
    url='mutasi-scrap',
    main_table='mutasi_scrap',
    icon='fa-trash',
    parent=@gi_parent,
    parent_name='Goods Issue',
    urutan_menu=7,
    tampil='Y',
    type_menu='page'
WHERE url='mutasi-scrap';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','GI for Sampling / R&D','gi-sampling-rnd','detail_transaksi','fa-flask',8,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='gi-sampling-rnd');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Goods Issue Monitor','goods-issue-monitor','detail_transaksi','fa-search',9,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='goods-issue-monitor');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Outbound Customs Monitor','outbound-customs-monitor','pengeluaran_hamparan','fa-file-text',10,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='outbound-customs-monitor');

-- Hide older duplicate/top-level entries that are superseded by the new Goods Issue grouping.
UPDATE sys_menu SET tampil='N' WHERE url='picking' AND parent=334;
UPDATE sys_menu SET tampil='N' WHERE url='produksi-to-outgoing';

-- Keep Warehouse ordering clear after introducing Goods Issue.
UPDATE sys_menu SET urutan_menu=5 WHERE id=338;
UPDATE sys_menu SET urutan_menu=6 WHERE url='packing-list';

-- Roles: warehouse/admin can operate, production/ppic/sales/customs/auditor get relevant visibility.
INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT @gi_parent,g.level,'Y','N','N','N','N'
FROM sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=@gi_parent AND r.group_level=g.level
WHERE g.level IN ('admin','system_administrator','gudang','produksi','ppic','sales','beacukai','auditor','finance_akunting')
  AND r.id IS NULL;

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','sales','beacukai','auditor','finance_akunting')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.parent=@gi_parent
  AND r.id IS NULL;

-- Tighten insert access by process.
UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE
      WHEN m.url IN ('gi-production-order','gi-production-reversal') AND r.group_level IN ('admin','system_administrator','gudang','produksi') THEN 'Y'
      WHEN m.url IN ('picking-pengeluaran','pengeluaran-hamparan','surat-jalan') AND r.group_level IN ('admin','system_administrator','gudang','sales') THEN 'Y'
      WHEN m.url IN ('gi-cost-center','mutasi-scrap','gi-sampling-rnd') AND r.group_level IN ('admin','system_administrator','gudang') THEN 'Y'
      ELSE 'N'
    END,
    r.update_act=CASE
      WHEN r.group_level IN ('admin','system_administrator','gudang') THEN 'Y'
      ELSE 'N'
    END,
    r.delete_act='N',
    r.import_act='N'
WHERE m.parent=@gi_parent;
