-- Route Purchase Request menu to the new SAP-style purchase_requisition schema.

UPDATE sys_menu
SET page_name='Purchase Requisition',
    nav_act='pr',
    main_table='purchase_requisition',
    icon='fa-file-text-o',
    tampil='Y'
WHERE url='pr';
