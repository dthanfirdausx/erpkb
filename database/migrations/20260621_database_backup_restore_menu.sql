INSERT INTO sys_menu (nav_act,page_name,url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu)
SELECT 'database_backup_restore','Backup Restore Database','backup-restore-database','information_schema.TABLES','fa-database',5,354,'management system','N','Y','page'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='backup-restore-database');

UPDATE sys_menu
SET nav_act='database_backup_restore',
    page_name='Backup Restore Database',
    main_table='information_schema.TABLES',
    icon='fa-database',
    urutan_menu=5,
    parent=354,
    parent_name='management system',
    tampil='Y',
    type_menu='page'
WHERE url='backup-restore-database';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
       CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END
FROM sys_menu m
JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu=m.id AND r.group_level=g.level
WHERE m.url='backup-restore-database' AND r.id IS NULL;

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='backup-restore-database'
SET r.read_act='Y', r.insert_act='Y', r.update_act='Y', r.delete_act='Y', r.import_act='Y'
WHERE r.group_level IN ('admin','system_administrator');
