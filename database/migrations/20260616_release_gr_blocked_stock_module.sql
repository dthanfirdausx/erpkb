-- Release GR Blocked Stock (movement type 105) module.

UPDATE sys_menu
SET nav_act='release_gr_blocked_stock',
    main_table='pemasukan',
    page_name='Release GR Blocked Stock (105)',
    icon='fa-unlock',
    tampil='Y'
WHERE url='release-gr-blocked-stock';
