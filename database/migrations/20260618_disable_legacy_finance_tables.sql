-- Legacy finance tables are no longer used by the application.
-- Active GL source must be jurnal_header + jurnal_detail.

UPDATE sys_menu
SET main_table = 'jurnal_header'
WHERE main_table IN ('jurnal_umum', 'jurnal_penyesuaian', 'jurnalentri', 'jurnalentri_detail')
  AND nav_act IN ('jurnal_umum', 'laporan_rugi_laba');
