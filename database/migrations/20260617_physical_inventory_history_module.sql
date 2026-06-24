UPDATE sys_menu
SET nav_act='physical_inventory_history',
    page_name='Physical Inventory History',
    url='physical-inventory-history',
    main_table='detail_transaksi',
    icon='fa-history',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='physical-inventory-history' OR page_name='Physical Inventory History';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'physical_inventory_history','Physical Inventory History','physical-inventory-history','detail_transaksi','fa-history',5,573,'Physical Inventory','Y','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='physical-inventory-history');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','N','N','N','N'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE group_level IS NOT NULL AND group_level<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='physical-inventory-history' AND r.id IS NULL;
