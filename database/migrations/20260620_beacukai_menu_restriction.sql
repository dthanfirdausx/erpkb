-- Restrict Beacukai/Customs user menu to Customs Report and Log Aktifitas only.
-- Keep all other menu permissions disabled for this operational customs role.

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, 'beacukai', 'N', 'N', 'N', 'N', 'N'
FROM sys_menu m
LEFT JOIN sys_menu_role r
  ON r.id_menu = m.id
 AND r.group_level = 'beacukai'
WHERE r.id IS NULL;

UPDATE sys_menu_role
SET read_act = 'N',
    insert_act = 'N',
    update_act = 'N',
    delete_act = 'N',
    import_act = 'N'
WHERE group_level = 'beacukai';

UPDATE sys_menu_role
SET read_act = 'Y'
WHERE group_level = 'beacukai'
  AND (
    id_menu IN (391, 347)
    OR id_menu IN (SELECT id FROM sys_menu WHERE parent = 347)
  );
