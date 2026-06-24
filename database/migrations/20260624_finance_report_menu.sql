INSERT INTO sys_menu (
  nav_act,page_name,page_name_zh,page_name_ja,page_name_ko,page_name_en,page_name_id,
  url,main_table,icon,urutan_menu,parent,parent_name,dt_table,tampil,type_menu
)
SELECT
  'finance_report','Finance Reports','财务报表','Finance Reports','재무 보고서','Finance Reports','Laporan Keuangan',
  'finance-report','jurnal_header','fa-file-text-o',99,408,'Finance','N','Y','page'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE url='finance-report');

UPDATE sys_menu
SET nav_act='finance_report',
    page_name='Finance Reports',
    page_name_zh='财务报表',
    page_name_ko='재무 보고서',
    page_name_en='Finance Reports',
    page_name_id='Laporan Keuangan',
    url='finance-report',
    main_table='jurnal_header',
    icon='fa-file-text-o',
    urutan_menu=99,
    parent=408,
    parent_name='Finance',
    dt_table='N',
    tampil='Y',
    type_menu='page'
WHERE url='finance-report';

INSERT INTO sys_menu_role (id_menu,group_level,read_act,insert_act,update_act,delete_act,import_act)
SELECT m.id,r.group_level,r.read_act,'N','N','N',COALESCE(r.import_act,'N')
FROM sys_menu m
JOIN sys_menu_role r ON r.id_menu=413
WHERE m.url='finance-report'
  AND NOT EXISTS (
    SELECT 1 FROM sys_menu_role x
    WHERE x.id_menu=m.id AND x.group_level=r.group_level
  );

UPDATE sys_menu_role target
JOIN sys_menu m ON m.id=target.id_menu AND m.url='finance-report'
JOIN sys_menu_role source ON source.id_menu=413 AND source.group_level=target.group_level
SET target.read_act=source.read_act,
    target.insert_act='N',
    target.update_act='N',
    target.delete_act='N',
    target.import_act=COALESCE(source.import_act,'N');

UPDATE sys_menu child
JOIN sys_menu parent ON parent.url='finance-report'
SET child.page_name='Laba/Rugi (Standar)',
    child.page_name_en='Profit & Loss (Standard)',
    child.page_name_id='Laba/Rugi (Standar)',
    child.icon='fa-line-chart',
    child.urutan_menu=1,
    child.parent=0,
    child.parent_name='Finance Reports',
    child.dt_table='N',
    child.tampil='N',
    child.type_menu='page'
WHERE child.url='laporan-rugi-laba';

UPDATE sys_menu child
JOIN sys_menu parent ON parent.url='finance-report'
SET child.page_name='Neraca (Standar)',
    child.page_name_en='Balance Sheet (Standard)',
    child.page_name_id='Neraca (Standar)',
    child.icon='fa-balance-scale',
    child.urutan_menu=2,
    child.parent=0,
    child.parent_name='Finance Reports',
    child.dt_table='N',
    child.tampil='N',
    child.type_menu='page',
    child.main_table='jurnal_header'
WHERE child.url='neraca';
