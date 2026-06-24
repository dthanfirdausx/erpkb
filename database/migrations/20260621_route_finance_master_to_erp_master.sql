-- Route small finance master pages to the shared ERP master workbench.
-- Data tables remain unchanged; this only standardizes UI/CRUD handling.

UPDATE sys_menu
SET nav_act='erp_master',
    icon='fa-list-alt'
WHERE url='coa';

UPDATE sys_menu
SET nav_act='erp_master',
    icon='fa-money'
WHERE url='mata-uang';
