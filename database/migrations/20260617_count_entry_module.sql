UPDATE sys_menu
SET nav_act='count_entry',
    page_name='Count Entry',
    url='count-entry',
    main_table='stock_layer',
    icon='fa-pencil-square-o',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='count-entry' OR page_name='Count Entry';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'count_entry','Count Entry','count-entry','stock_layer','fa-pencil-square-o',3,573,'Physical Inventory','Y','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='count-entry');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','Y','Y','N','N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='count-entry' AND r.id IS NULL;
