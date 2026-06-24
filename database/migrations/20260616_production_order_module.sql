SET @prod_parent=(SELECT id FROM sys_menu WHERE page_name='produksi' AND type_menu='main' LIMIT 1);

UPDATE sys_menu
SET nav_act='production_order',
    page_name='Production Order',
    url='production-order',
    main_table='production_order',
    icon='fa-industry',
    urutan_menu=6,
    parent=@prod_parent,
    parent_name='produksi',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='production-order';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','Y','Y','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','produksi','ppic','gudang','quality_control','auditor')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='production-order'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','produksi','ppic') THEN 'Y' ELSE r.insert_act END,
    r.update_act=CASE WHEN r.group_level IN ('admin','system_administrator','produksi','ppic') THEN 'Y' ELSE r.update_act END,
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='production-order'
  AND r.group_level IN ('admin','system_administrator','produksi','ppic','gudang','quality_control','auditor');
