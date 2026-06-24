UPDATE sys_menu
SET nav_act='transfer_produksi',
    page_name='Transfer Posting (311)',
    url='transfer-produksi',
    main_table='transfer',
    icon='fa-exchange',
    parent=334,
    parent_name='Warehouse',
    urutan_menu=6,
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='transfer-produksi';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','Y','N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','auditor')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='transfer-produksi' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN r.group_level IN ('admin','system_administrator','gudang') THEN 'Y' ELSE r.insert_act END,
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='transfer-produksi'
  AND r.group_level IN ('admin','system_administrator','gudang','produksi','ppic','auditor');
