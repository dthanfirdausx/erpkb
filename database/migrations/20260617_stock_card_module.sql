UPDATE sys_menu
SET nav_act='stock_card',
    page_name='Stock Card',
    url='stock-card',
    main_table='detail_transaksi',
    icon='fa-list-alt',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='stock-card' OR page_name='Stock Card';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'stock_card','Stock Card','stock-card','detail_transaksi','fa-list-alt',2,572,'Inventory Management','Y','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='stock-card');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','N','N','N','N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='stock-card' AND r.id IS NULL;
