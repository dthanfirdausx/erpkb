-- Neraca now reads SAP-like FI journal sources.

UPDATE sys_menu
SET main_table = 'jurnal_header'
WHERE url = 'neraca';
