-- Refresh Mutasi Barang Modal as read-only Customs Report.
-- Data is calculated from detail_transaksi instead of legacy generated views.

UPDATE sys_menu
SET page_name = 'Mutasi Barang Modal',
    parent_name = 'Customs Report',
    main_table = 'detail_transaksi',
    dt_table = 'Y',
    tampil = 'Y',
    type_menu = 'page'
WHERE url = 'mutasi-barang-modal'
   OR nav_act = 'mutasi_barang_modal';

-- Dummy finished goods created for customs traceability tests must stay in
-- Barang Jadi, otherwise they pollute Mutasi Barang Modal.
UPDATE barang
SET kd_kategori = 'K02'
WHERE kd_barang LIKE 'CUSTFG%'
   OR kd_barang LIKE 'CUSTFGI%';
