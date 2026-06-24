SET @gr_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Receipt' AND type_menu='main' LIMIT 1);

UPDATE sys_menu
SET nav_act='customs_receiving_monitor',
    page_name='Customs Receiving Monitor',
    url='customs-receiving-monitor',
    main_table='pemasukan',
    icon='fa-file-text',
    urutan_menu=10,
    parent=@gr_parent,
    parent_name='Goods Receipt',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='customs-receiving-monitor';

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'customs_receiving_monitor','Customs Receiving Monitor','customs-receiving-monitor','pemasukan','fa-file-text',10,@gr_parent,'Goods Receipt','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='customs-receiving-monitor');

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','purchasing','quality_control','finance_akunting','auditor','beacukai')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='customs-receiving-monitor' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='customs-receiving-monitor'
  AND r.group_level IN ('admin','system_administrator','gudang','purchasing','quality_control','finance_akunting','auditor','beacukai');
