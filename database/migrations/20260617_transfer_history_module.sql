UPDATE sys_menu
SET nav_act='transfer_history',
    page_name='Transfer History',
    url='transfer-history',
    main_table='detail_transaksi',
    icon='fa-history',
    urutan_menu=5,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='transfer-history';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'transfer_history','Transfer History','transfer-history','detail_transaksi','fa-history',5,p.id,'Stock Transfer','Y','Y','page'
FROM sys_menu p
WHERE p.parent=334 AND p.page_name='Stock Transfer' AND p.type_menu='main'
  AND NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='transfer-history')
LIMIT 1;

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='transfer-history'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',r.insert_act='N',r.update_act='N',r.delete_act='N',r.import_act='N'
WHERE m.url='transfer-history';
