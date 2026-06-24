-- Route the GR Without PO menu to the concrete module folder.

UPDATE sys_menu
SET nav_act='gr_without_po',
    main_table='pemasukan',
    page_name='GR Without Purchase Order',
    icon='fa-download'
WHERE url='gr-without-po';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,g.level,'Y',
       CASE WHEN g.level IN ('admin','administrator','superadmin','gudang','purchasing') THEN 'Y' ELSE 'N' END,
       'N','N','N'
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='gr-without-po' AND r.id IS NULL;
