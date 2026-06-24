-- Mutasi Scrap report under Customs Report
-- Mirrors access from Mutasi Barang Jadi (sys_menu.id = 372).

UPDATE sys_menu
SET page_name = 'Mutasi Scrap',
    url = 'mutasi-scrap',
    nav_act = 'mutasi_scrap',
    main_table = 'detail_transaksi',
    icon = 'fa-recycle',
    urutan_menu = 8,
    parent = 347,
    parent_name = 'Customs Report',
    dt_table = 'Y',
    tampil = 'Y',
    type_menu = 'page'
WHERE id = 374
   OR url = 'mutasi-scrap'
   OR nav_act = 'mutasi_scrap';

INSERT INTO sys_menu_role (
    id_menu,
    group_level,
    read_act,
    insert_act,
    update_act,
    delete_act,
    import_act
)
SELECT
    374,
    r.group_level,
    r.read_act,
    r.insert_act,
    r.update_act,
    r.delete_act,
    r.import_act
FROM sys_menu_role r
WHERE r.id_menu = 372
  AND NOT EXISTS (
      SELECT 1
      FROM sys_menu_role x
      WHERE x.id_menu = 374
        AND x.group_level = r.group_level
  );

UPDATE sys_menu_role t
JOIN sys_menu_role s
  ON s.id_menu = 372
 AND s.group_level = t.group_level
SET t.read_act = s.read_act,
    t.insert_act = s.insert_act,
    t.update_act = s.update_act,
    t.delete_act = s.delete_act,
    t.import_act = s.import_act
WHERE t.id_menu = 374;

INSERT INTO sys_menu_role (
    id_menu,
    group_level,
    read_act,
    insert_act,
    update_act,
    delete_act,
    import_act
)
SELECT 374, x.group_level, 'Y',
       CASE WHEN x.group_level = 'admin' THEN 'Y' ELSE 'N' END,
       CASE WHEN x.group_level = 'admin' THEN 'Y' ELSE 'N' END,
       CASE WHEN x.group_level = 'admin' THEN 'Y' ELSE 'N' END,
       CASE WHEN x.group_level = 'admin' THEN 'Y' ELSE 'N' END
FROM (
    SELECT 'admin' AS group_level
    UNION ALL
    SELECT 'beacukai'
) x
WHERE NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu = 374
      AND r.group_level = x.group_level
);

UPDATE sys_menu_role
SET read_act = 'Y',
    insert_act = CASE WHEN group_level = 'admin' THEN 'Y' ELSE 'N' END,
    update_act = CASE WHEN group_level = 'admin' THEN 'Y' ELSE 'N' END,
    delete_act = CASE WHEN group_level = 'admin' THEN 'Y' ELSE 'N' END,
    import_act = CASE WHEN group_level = 'admin' THEN 'Y' ELSE 'N' END
WHERE id_menu = 374
  AND group_level IN ('admin','beacukai');

INSERT INTO sys_menu_role (
    id_menu,
    group_level,
    read_act,
    insert_act,
    update_act,
    delete_act,
    import_act
)
SELECT 347, x.group_level, 'Y', 'N', 'N', 'N', 'N'
FROM (
    SELECT 'admin' AS group_level
    UNION ALL
    SELECT 'beacukai'
) x
WHERE NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu = 347
      AND r.group_level = x.group_level
);

UPDATE sys_menu_role
SET read_act = 'Y'
WHERE id_menu = 347
  AND group_level IN ('admin','beacukai');
