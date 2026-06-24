UPDATE sys_menu
SET nav_act='material_availability',
    page_name='Material Availability',
    url='material-availability',
    main_table='production_order_material',
    icon='fa-check-circle',
    tampil='Y',
    type_menu='page'
WHERE url='material-availability';
