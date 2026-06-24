UPDATE sys_menu
SET parent=0,
    parent_name='',
    urutan_menu=14,
    tampil='Y',
    type_menu='main',
    icon='fa-user'
WHERE page_name='Employee Self Service';

UPDATE sys_menu c
JOIN sys_menu p ON p.page_name='Employee Self Service'
SET c.parent=p.id,
    c.parent_name='Employee Self Service',
    c.tampil='Y'
WHERE c.id IN (
  SELECT id FROM (
    SELECT id FROM sys_menu WHERE parent=(SELECT id FROM sys_menu WHERE page_name='Employee Self Service' LIMIT 1)
       OR url IN ('my-profile','my-attendance','my-leave','my-payslip','my-request')
  ) x
);

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, 'employee_self_service', 'Y',
       CASE WHEN m.url IN ('my-leave','my-request') THEN 'Y' ELSE 'N' END,
       CASE WHEN m.url IN ('my-profile','my-leave','my-request') THEN 'Y' ELSE 'N' END,
       'N',
       'N'
FROM sys_menu m
WHERE (m.page_name='Employee Self Service' OR m.parent=(SELECT id FROM sys_menu WHERE page_name='Employee Self Service' LIMIT 1))
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level='employee_self_service'
  );

UPDATE sys_menu_role r
JOIN sys_menu m ON m.id=r.id_menu
SET r.read_act='Y',
    r.insert_act=CASE WHEN m.url IN ('my-leave','my-request') THEN 'Y' ELSE 'N' END,
    r.update_act=CASE WHEN m.url IN ('my-profile','my-leave','my-request') THEN 'Y' ELSE 'N' END,
    r.delete_act='N',
    r.import_act='N'
WHERE r.group_level='employee_self_service'
  AND (m.page_name='Employee Self Service' OR m.parent=(SELECT id FROM sys_menu WHERE page_name='Employee Self Service' LIMIT 1));
