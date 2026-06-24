SET @gi_parent=(SELECT id FROM sys_menu WHERE parent=334 AND page_name='Goods Issue' AND type_menu='main' LIMIT 1);

INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'goods_issue_history','Goods Issue History','goods-issue-history','detail_transaksi','fa-history',9,@gi_parent,'Goods Issue','Y','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='goods-issue-history');

UPDATE sys_menu
SET nav_act='goods_issue_history',
    page_name='Goods Issue History',
    url='goods-issue-history',
    main_table='detail_transaksi',
    icon='fa-history',
    urutan_menu=9,
    parent=@gi_parent,
    parent_name='Goods Issue',
    dt_table='Y',
    tampil='Y',
    type_menu='page'
WHERE url='goods-issue-history';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','gudang','produksi','ppic','sales','beacukai','auditor','finance_akunting','manager_approver')
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='goods-issue-history'
  AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE m.url='goods-issue-history';
