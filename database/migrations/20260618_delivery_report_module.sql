UPDATE sys_menu
SET nav_act='delivery_report',
    main_table='surat_jalan',
    icon='fa-truck',
    tampil='Y',
    type_menu='page'
WHERE url='delivery-report';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.group_level,'Y','N','N','N','Y'
FROM sys_menu m
JOIN (SELECT DISTINCT group_level FROM sys_menu_role WHERE COALESCE(group_level,'')<>'') g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.group_level
WHERE m.url='delivery-report' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',r.insert_act='N',r.update_act='N',r.delete_act='N',r.import_act='Y'
WHERE m.url='delivery-report';
