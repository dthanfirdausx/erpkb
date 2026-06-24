-- Refresh Laporan Posisi WIP as Customs Report.
-- The module now reads WIP from the latest production flow:
-- erp_issue_production_trace - erp_gr_production_trace - erp_production_scrap_trace.

UPDATE sys_menu
SET page_name = 'Laporan Posisi WIP',
    parent_name = 'Customs Report',
    main_table = 'erp_issue_production_trace',
    dt_table = 'Y',
    tampil = 'Y',
    type_menu = 'page'
WHERE url = 'laporan-posisi-wip'
   OR nav_act = 'laporan_posisi_wip';
