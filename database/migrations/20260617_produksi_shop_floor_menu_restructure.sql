-- Produksi menu restructure:
-- Produksi
--   Shop Floor Execution
--     Work Center Dashboard
--     Production Confirmation
--     Input Downtime
--     Production Activity Log
--   Monitoring
--     My Production Orders
--     Operation Queue
--     Output Monitoring

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'produksi', '', '', 'fa-industry', 8, 0, '', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name IN ('produksi','Produksi') AND parent=0 AND type_menu='main');

SET @produksi_parent := (SELECT id FROM sys_menu WHERE page_name IN ('produksi','Produksi') AND parent=0 AND type_menu='main' ORDER BY id LIMIT 1);

UPDATE sys_menu
SET page_name='Produksi',
    parent=0,
    parent_name='',
    icon='fa-industry',
    urutan_menu=8,
    tampil='Y',
    type_menu='main'
WHERE id=@produksi_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Shop Floor Execution', '', '', 'fa-cogs', 1, @produksi_parent, 'Produksi', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Shop Floor Execution' AND parent=@produksi_parent AND type_menu='main');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Monitoring', '', '', 'fa-dashboard', 2, @produksi_parent, 'Produksi', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Monitoring' AND parent=@produksi_parent AND type_menu='main');

SET @shop_floor := (SELECT id FROM sys_menu WHERE page_name='Shop Floor Execution' AND parent=@produksi_parent AND type_menu='main' ORDER BY id LIMIT 1);
SET @monitoring := (SELECT id FROM sys_menu WHERE page_name='Monitoring' AND parent=@produksi_parent AND type_menu='main' ORDER BY id LIMIT 1);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Work Center Dashboard','work-center-dashboard','production_order','fa-desktop',1,@shop_floor,'Shop Floor Execution','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='work-center-dashboard');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Work Center Dashboard',
    main_table='production_order',
    icon='fa-desktop',
    urutan_menu=1,
    parent=@shop_floor,
    parent_name='Shop Floor Execution',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='work-center-dashboard';

UPDATE sys_menu
SET nav_act='production_confirmation',
    page_name='Production Confirmation',
    url='production-confirmation',
    main_table='production_order_confirmation',
    icon='fa-check-square-o',
    urutan_menu=2,
    parent=@shop_floor,
    parent_name='Shop Floor Execution',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-confirmation';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Input Downtime','input-downtime','production_order_confirmation','fa-clock-o',3,@shop_floor,'Shop Floor Execution','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='input-downtime');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Input Downtime',
    main_table='production_order_confirmation',
    icon='fa-clock-o',
    urutan_menu=3,
    parent=@shop_floor,
    parent_name='Shop Floor Execution',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='input-downtime';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Activity Log','production-activity-log','production_order_confirmation','fa-list-alt',4,@shop_floor,'Shop Floor Execution','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-activity-log');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Production Activity Log',
    main_table='production_order_confirmation',
    icon='fa-list-alt',
    urutan_menu=4,
    parent=@shop_floor,
    parent_name='Shop Floor Execution',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-activity-log';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','My Production Orders','my-production-orders','production_order','fa-tasks',1,@monitoring,'Monitoring','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='my-production-orders');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='My Production Orders',
    main_table='production_order',
    icon='fa-tasks',
    urutan_menu=1,
    parent=@monitoring,
    parent_name='Monitoring',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='my-production-orders';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Operation Queue','operation-queue','production_order_operation','fa-list-ol',2,@monitoring,'Monitoring','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='operation-queue');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Operation Queue',
    main_table='production_order_operation',
    icon='fa-list-ol',
    urutan_menu=2,
    parent=@monitoring,
    parent_name='Monitoring',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='operation-queue';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Output Monitoring','output-monitoring','production_order_confirmation','fa-line-chart',3,@monitoring,'Monitoring','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='output-monitoring');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Output Monitoring',
    main_table='production_order_confirmation',
    icon='fa-line-chart',
    urutan_menu=3,
    parent=@monitoring,
    parent_name='Monitoring',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='output-monitoring';

UPDATE sys_menu
SET tampil='N'
WHERE parent=@produksi_parent
  AND id NOT IN (@shop_floor,@monitoring);

UPDATE sys_menu
SET tampil='N'
WHERE parent IN (
  SELECT id FROM (
    SELECT id
    FROM sys_menu
    WHERE id<>@shop_floor
      AND id<>@monitoring
      AND (parent=@produksi_parent OR parent_name IN ('produksi','Produksi'))
  ) legacy_parent
);

UPDATE sys_menu
SET tampil='N'
WHERE url IN ('lp-barang-jadi','lp-gabungan','lp-sparepart','laporan-scrap','stock-barang-jadi-produksi','stock-barang-setengah-jadi-produksi','inbox-produksi','lpb-produksi','produksi-to-outgoing','produksi-to-incoming','laporan-ng')
  AND parent<>@shop_floor
  AND parent<>@monitoring;

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','produksi') AND m.type_menu='page' THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','produksi') AND m.type_menu='page' THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','produksi','ppic','gudang','quality_control','auditor','manager_approver')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE (m.id=@produksi_parent OR m.parent IN (@produksi_parent,@shop_floor,@monitoring))
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','produksi') AND m.type_menu='page' THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','produksi') AND m.type_menu='page' THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE (m.id=@produksi_parent OR m.parent IN (@produksi_parent,@shop_floor,@monitoring))
  AND r.group_level IN ('admin','system_administrator','produksi','ppic','gudang','quality_control','auditor','manager_approver');
