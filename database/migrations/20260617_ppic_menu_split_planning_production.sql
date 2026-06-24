-- PPIC menu split into Planning, Master Data Planning, Production Planning, and Monitoring & Reports.

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'PPIC', '', '', 'fa-users', 6, 0, '', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='PPIC' AND type_menu='main');

SET @ppic_parent := (SELECT id FROM sys_menu WHERE page_name='PPIC' AND type_menu='main' ORDER BY id LIMIT 1);

UPDATE sys_menu
SET parent=0,parent_name='',page_name='PPIC',icon='fa-users',urutan_menu=6,tampil='Y',type_menu='main'
WHERE id=@ppic_parent;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Planning', '', '', 'fa-calendar-check-o', 1, @ppic_parent, 'PPIC', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Planning' AND parent=@ppic_parent AND type_menu='main');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Master Data Planning', '', '', 'fa-database', 2, @ppic_parent, 'PPIC', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Master Data Planning' AND parent=@ppic_parent AND type_menu='main');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Production Planning', '', '', 'fa-industry', 3, @ppic_parent, 'PPIC', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Production Planning' AND parent=@ppic_parent AND type_menu='main');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT '', 'Monitoring & Reports', '', '', 'fa-dashboard', 4, @ppic_parent, 'PPIC', 'Y', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Monitoring & Reports' AND parent=@ppic_parent AND type_menu='main');

SET @planning := (SELECT id FROM sys_menu WHERE page_name='Planning' AND parent=@ppic_parent AND type_menu='main' ORDER BY id LIMIT 1);
SET @master_planning := (SELECT id FROM sys_menu WHERE page_name='Master Data Planning' AND parent=@ppic_parent AND type_menu='main' ORDER BY id LIMIT 1);
SET @production_planning := (SELECT id FROM sys_menu WHERE page_name='Production Planning' AND parent=@ppic_parent AND type_menu='main' ORDER BY id LIMIT 1);
SET @monitoring_reports := (SELECT id FROM sys_menu WHERE page_name='Monitoring & Reports' AND parent=@ppic_parent AND type_menu='main' ORDER BY id LIMIT 1);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Forecast','forecast','mrp','fa-line-chart',1,@planning,'Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='forecast');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Forecast',url='forecast',main_table='mrp',icon='fa-line-chart',
    urutan_menu=1,parent=@planning,parent_name='Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='forecast';

UPDATE sys_menu
SET tampil='N',
    url=CONCAT('forecast-legacy-',id),
    parent=@ppic_parent,
    parent_name='PPIC'
WHERE url='production-forecast';

SET @forecast_keep := (SELECT MAX(id) FROM sys_menu WHERE url='forecast');

UPDATE sys_menu
SET tampil='N',
    url=CONCAT('forecast-legacy-',id),
    parent=@ppic_parent,
    parent_name='PPIC'
WHERE url='forecast'
  AND id<>@forecast_keep;

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Demand Management','demand-management','mrp','fa-area-chart',2,@planning,'Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='demand-management');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Demand Management',main_table='mrp',icon='fa-area-chart',
    urutan_menu=2,parent=@planning,parent_name='Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='demand-management';

UPDATE sys_menu
SET page_name='MRP',url='mrp',nav_act='mrp',main_table='mrp',icon='fa-calculator',
    urutan_menu=3,parent=@planning,parent_name='Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='mrp';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'ro','Material Requirement','material-requirement','ro','fa-clipboard',4,@planning,'Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url IN ('material-requirement','material-request'));

UPDATE sys_menu
SET nav_act='ro',page_name='Material Requirement',url='material-requirement',main_table='ro',icon='fa-clipboard',
    urutan_menu=4,parent=@planning,parent_name='Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url IN ('material-requirement','material-request');

UPDATE sys_menu
SET page_name='Bill of Material (BOM)',url='bom',nav_act='bom',main_table='bom',icon='fa-sitemap',
    urutan_menu=1,parent=@master_planning,parent_name='Master Data Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='bom';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Routing','routing','manufactur','fa-random',2,@master_planning,'Master Data Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='routing');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Routing',main_table='manufactur',icon='fa-random',
    urutan_menu=2,parent=@master_planning,parent_name='Master Data Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='routing';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Version','production-version','production_order','fa-code-fork',3,@master_planning,'Master Data Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-version');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Production Version',main_table='production_order',icon='fa-code-fork',
    urutan_menu=3,parent=@master_planning,parent_name='Master Data Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='production-version';

UPDATE sys_menu
SET nav_act='factory_calendar',page_name='Factory Calendar',url='factory-calendar',main_table='erp_factory_calendar',icon='fa-calendar-check-o',
    urutan_menu=4,parent=@master_planning,parent_name='Master Data Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='factory-calendar';

UPDATE sys_menu
SET nav_act='production_order',page_name='Production Order',url='production-order',main_table='production_order',icon='fa-industry',
    urutan_menu=1,parent=@production_planning,parent_name='Production Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='production-order';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Order Release','production-order-release','production_order','fa-unlock',2,@production_planning,'Production Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-order-release');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Production Order Release',main_table='production_order',icon='fa-unlock',
    urutan_menu=2,parent=@production_planning,parent_name='Production Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='production-order-release';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Material Staging Request','material-staging-request','production_order_material','fa-truck',3,@production_planning,'Production Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='material-staging-request');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Material Staging Request',main_table='production_order_material',icon='fa-truck',
    urutan_menu=3,parent=@production_planning,parent_name='Production Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='material-staging-request';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Schedule','production-schedule','production_order','fa-calendar',4,@production_planning,'Production Planning','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-schedule');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Production Schedule',main_table='production_order',icon='fa-calendar',
    urutan_menu=4,parent=@production_planning,parent_name='Production Planning',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='production-schedule';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Order Monitoring','production-order-monitoring','production_order','fa-dashboard',1,@monitoring_reports,'Monitoring & Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url IN ('production-order-monitoring','production-monitoring'));

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Production Order Monitoring',url='production-order-monitoring',main_table='production_order',icon='fa-dashboard',
    urutan_menu=1,parent=@monitoring_reports,parent_name='Monitoring & Reports',dt_table='Y',tampil='Y',type_menu='page'
WHERE url IN ('production-order-monitoring','production-monitoring');

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Material Availability','material-availability','stock_layer','fa-check-circle',2,@monitoring_reports,'Monitoring & Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='material-availability');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Material Availability',main_table='stock_layer',icon='fa-check-circle',
    urutan_menu=2,parent=@monitoring_reports,parent_name='Monitoring & Reports',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='material-availability';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','WIP Monitoring','wip-monitoring','production_order','fa-hourglass-half',3,@monitoring_reports,'Monitoring & Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='wip-monitoring');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='WIP Monitoring',main_table='production_order',icon='fa-hourglass-half',
    urutan_menu=3,parent=@monitoring_reports,parent_name='Monitoring & Reports',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='wip-monitoring';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Traceability','production-traceability','erp_gr_production_trace','fa-random',4,@monitoring_reports,'Monitoring & Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-traceability');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Production Traceability',main_table='erp_gr_production_trace',icon='fa-random',
    urutan_menu=4,parent=@monitoring_reports,parent_name='Monitoring & Reports',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='production-traceability';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'erp_workspace','Production Reports','production-reports','production_order','fa-file-text-o',5,@monitoring_reports,'Monitoring & Reports','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='production-reports');

UPDATE sys_menu
SET nav_act='erp_workspace',page_name='Production Reports',main_table='production_order',icon='fa-file-text-o',
    urutan_menu=5,parent=@monitoring_reports,parent_name='Monitoring & Reports',dt_table='Y',tampil='Y',type_menu='page'
WHERE url='production-reports';

UPDATE sys_menu
SET tampil='N'
WHERE parent=@ppic_parent
  AND id NOT IN (@planning,@master_planning,@production_planning,@monitoring_reports);

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','system_administrator','ppic') AND m.type_menu='page' THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator','ppic') AND m.type_menu='page' THEN 'Y' ELSE 'N' END,
       'N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','ppic','produksi','gudang','quality_control','auditor','manager_approver')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE (m.id=@ppic_parent OR m.parent IN (@ppic_parent,@planning,@master_planning,@production_planning,@monitoring_reports))
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','ppic') AND m.type_menu='page' THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','ppic') AND m.type_menu='page' THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE (m.id=@ppic_parent OR m.parent IN (@ppic_parent,@planning,@master_planning,@production_planning,@monitoring_reports))
  AND r.group_level IN ('admin','system_administrator','ppic','produksi','gudang','quality_control','auditor','manager_approver');
