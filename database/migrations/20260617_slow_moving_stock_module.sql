UPDATE sys_menu
SET nav_act='slow_moving_stock',
    page_name='Slow Moving Stock',
    url='slow-moving-stock',
    main_table='stock_layer',
    icon='fa-hourglass-half',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='slow-moving-stock' OR page_name='Slow Moving Stock';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'slow_moving_stock','Slow Moving Stock','slow-moving-stock','stock_layer','fa-hourglass-half',6,572,'Inventory Management','Y','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='slow-moving-stock');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','N','N','N','N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='slow-moving-stock' AND r.id IS NULL;
