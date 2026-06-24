UPDATE sys_menu
   SET nav_act='leave_report',
       main_table='erp_leave_request',
       icon='fa-file-text-o',
       dt_table='Y',
       tampil='Y'
 WHERE url='leave-report';

UPDATE erp_leave_request
   SET created_by='admin',
       updated_by='admin',
       updated_at=COALESCE(updated_at,NOW())
 WHERE created_by IS NULL
    OR created_by=''
    OR created_by<>'admin'
    OR updated_by IS NULL
    OR updated_by=''
    OR updated_by<>'admin';

UPDATE erp_leave_approval
   SET created_by='admin',
       updated_by='admin',
       updated_at=COALESCE(updated_at,NOW())
 WHERE created_by IS NULL
    OR created_by=''
    OR created_by<>'admin'
    OR updated_by IS NULL
    OR updated_by=''
    OR updated_by<>'admin';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id,g.level,'Y','N','N','N','N'
  FROM sys_menu m JOIN sys_group_users g
 WHERE m.url='leave-report'
   AND NOT EXISTS (SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level);

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu AND m.url='leave-report'
   SET r.read_act='Y',
       r.insert_act='N',
       r.update_act='N',
       r.delete_act='N',
       r.import_act='N';
