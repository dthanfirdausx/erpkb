-- PPIC menu restructure:
-- PPIC
--   Forecast
--   MRP
--   Bill Of Material (BOM)
--   Routing
--   Production Order
--   Material Request
--   Production Schedule
--   Production Monitoring

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'PPIC', '', '', 'fa-users', 6, 0, '', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='PPIC' AND type_menu='main');

SET @ppic_parent=(SELECT id FROM sys_menu WHERE page_name='PPIC' AND type_menu='main' LIMIT 1);

UPDATE sys_menu
SET parent=0,
    parent_name='',
    page_name='PPIC',
    icon='fa-users',
    urutan_menu=6,
    tampil='Y',
    type_menu='main'
WHERE id=@ppic_parent;

-- 1. Forecast
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Forecast','forecast','mrp','fa-line-chart',1,@ppic_parent,'PPIC','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='forecast');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Forecast',
    url='forecast',
    main_table='mrp',
    icon='fa-line-chart',
    urutan_menu=1,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE id=(SELECT id FROM (SELECT id FROM sys_menu WHERE url IN ('forecast','production-forecast') ORDER BY FIELD(url,'forecast','production-forecast') LIMIT 1) x);

-- 2. MRP
UPDATE sys_menu
SET page_name='MRP',
    url='mrp',
    nav_act='mrp',
    main_table='mrp',
    icon='fa-calculator',
    urutan_menu=2,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='mrp';

-- 3. Bill Of Material (BOM)
UPDATE sys_menu
SET page_name='Bill Of Material (BOM)',
    url='bom',
    nav_act='bom',
    main_table='bom',
    icon='fa-sitemap',
    urutan_menu=3,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='bom';

-- 4. Routing
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Routing','routing','manufactur','fa-random',4,@ppic_parent,'PPIC','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='routing');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Routing',
    url='routing',
    main_table='manufactur',
    icon='fa-random',
    urutan_menu=4,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='routing';

-- 5. Production Order
UPDATE sys_menu
SET nav_act='production_order',
    page_name='Production Order',
    url='production-order',
    main_table='production_order',
    icon='fa-industry',
    urutan_menu=5,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-order';

-- 6. Material Request (reuse existing RO module)
UPDATE sys_menu
SET nav_act='ro',
    page_name='Material Request',
    url='material-request',
    main_table='ro',
    icon='fa-clipboard',
    urutan_menu=6,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE id=(SELECT id FROM (SELECT id FROM sys_menu WHERE url IN ('material-request','ro') ORDER BY FIELD(url,'material-request','ro') LIMIT 1) x);

-- 7. Production Schedule
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Schedule','production-schedule','production_order','fa-calendar',7,@ppic_parent,'PPIC','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-schedule');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Production Schedule',
    main_table='production_order',
    icon='fa-calendar',
    urutan_menu=7,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-schedule';

-- 8. Production Monitoring
INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Monitoring','production-monitoring','production_order','fa-dashboard',8,@ppic_parent,'PPIC','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-monitoring');

UPDATE sys_menu
SET nav_act='erp_workspace',
    page_name='Production Monitoring',
    main_table='production_order',
    icon='fa-dashboard',
    urutan_menu=8,
    parent=@ppic_parent,
    parent_name='PPIC',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-monitoring';

-- Hide PPIC children not requested in this structure.
UPDATE sys_menu
SET tampil='N'
WHERE parent=@ppic_parent
  AND url NOT IN ('forecast','mrp','bom','routing','production-order','material-request','production-schedule','production-monitoring');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','ppic') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','ppic') THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','ppic','produksi','gudang','quality_control','auditor','manager_approver')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.parent=@ppic_parent
  AND m.url IN ('forecast','mrp','bom','routing','production-order','material-request','production-schedule','production-monitoring')
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','ppic') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','ppic') THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE m.parent=@ppic_parent
  AND m.url IN ('forecast','mrp','bom','routing','production-order','material-request','production-schedule','production-monitoring')
  AND r.group_level IN ('admin','system_administrator','ppic','produksi','gudang','quality_control','auditor','manager_approver');
