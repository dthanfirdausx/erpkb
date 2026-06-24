UPDATE sys_menu
SET nav_act='production_reports',
    page_name='Production Reports',
    url='production-reports',
    main_table='production_order',
    icon='fa-file-text-o',
    tampil='Y',
    type_menu='page'
WHERE url='production-reports';
