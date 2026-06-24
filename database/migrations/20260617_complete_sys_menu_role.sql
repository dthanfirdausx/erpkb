-- Complete and normalize sys_menu_role against current sys_menu and sys_group_users.
-- Existing valid permissions are preserved; this only removes orphan rows and inserts missing pairs.

DELETE r
FROM sys_menu_role r
LEFT JOIN sys_menu m ON m.id = r.id_menu
WHERE m.id IS NULL;

DELETE r
FROM sys_menu_role r
LEFT JOIN sys_group_users g ON g.level = r.group_level
WHERE g.level IS NULL;

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT
  m.id,
  g.level,
  CASE
    WHEN g.level IN ('admin','system_administrator') AND m.tampil='Y' THEN 'Y'
    ELSE 'N'
  END AS read_act,
  CASE
    WHEN g.level IN ('admin','system_administrator') AND m.type_menu='page' AND m.tampil='Y' THEN 'Y'
    ELSE 'N'
  END AS insert_act,
  CASE
    WHEN g.level IN ('admin','system_administrator') AND m.type_menu='page' AND m.tampil='Y' THEN 'Y'
    ELSE 'N'
  END AS update_act,
  CASE
    WHEN g.level IN ('admin','system_administrator') AND m.type_menu='page' AND m.tampil='Y' THEN 'Y'
    ELSE 'N'
  END AS delete_act,
  'N' AS import_act
FROM sys_menu m
CROSS JOIN sys_group_users g
LEFT JOIN sys_menu_role r ON r.id_menu = m.id AND r.group_level = g.level
WHERE r.id IS NULL;

-- Ensure a main menu can be opened when the same group already has at least one readable visible child.
UPDATE sys_menu_role parent_role
JOIN sys_menu parent_menu ON parent_menu.id = parent_role.id_menu
SET parent_role.read_act = 'Y'
WHERE parent_menu.type_menu = 'main'
  AND parent_menu.tampil = 'Y'
  AND EXISTS (
    SELECT 1
    FROM sys_menu child_menu
    JOIN sys_menu_role child_role
      ON child_role.id_menu = child_menu.id
     AND child_role.group_level = parent_role.group_level
    WHERE child_menu.parent = parent_menu.id
      AND child_menu.tampil = 'Y'
      AND child_role.read_act = 'Y'
  );

-- Keep hidden menus hidden for newly completed non-admin roles unless explicitly opened elsewhere.
UPDATE sys_menu_role r
JOIN sys_menu m ON m.id = r.id_menu
SET r.read_act = CASE WHEN r.group_level IN ('admin','system_administrator') THEN r.read_act ELSE 'N' END,
    r.insert_act = CASE WHEN r.group_level IN ('admin','system_administrator') THEN r.insert_act ELSE 'N' END,
    r.update_act = CASE WHEN r.group_level IN ('admin','system_administrator') THEN r.update_act ELSE 'N' END,
    r.delete_act = CASE WHEN r.group_level IN ('admin','system_administrator') THEN r.delete_act ELSE 'N' END,
    r.import_act = 'N'
WHERE m.tampil = 'N';
