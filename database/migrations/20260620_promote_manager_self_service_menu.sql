UPDATE sys_menu
SET parent=0,
    parent_name='',
    urutan_menu=15,
    icon='fa-users',
    tampil='Y',
    type_menu='main'
WHERE page_name='Manager Self Service';

UPDATE sys_menu c
JOIN sys_menu p ON p.page_name='Manager Self Service'
SET c.parent=p.id,
    c.parent_name='Manager Self Service',
    c.tampil='Y',
    c.type_menu='page'
WHERE c.url IN ('team-attendance','team-leave-approval','team-overtime-approval','team-performance','team-request-approval');

DELETE r
FROM sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
WHERE (m.page_name='Manager Self Service'
       OR m.parent=(SELECT id FROM sys_menu WHERE page_name='Manager Self Service' LIMIT 1))
  AND r.group_level NOT IN ('manager_approver','admin','system_administrator');

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, 'manager_approver', 'Y', 'N', 'N', 'N', 'N'
FROM sys_menu m
WHERE (m.page_name='Manager Self Service'
       OR m.parent=(SELECT id FROM sys_menu WHERE page_name='Manager Self Service' LIMIT 1))
  AND NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu=m.id
      AND r.group_level='manager_approver'
  );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act='N',
    r.update_act='N',
    r.delete_act='N',
    r.import_act='N'
WHERE r.group_level='manager_approver'
  AND (m.page_name='Manager Self Service'
       OR m.parent=(SELECT id FROM sys_menu WHERE page_name='Manager Self Service' LIMIT 1));
