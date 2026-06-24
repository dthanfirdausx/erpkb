-- Route Goods Issue > Return to Vendor to the real Return to Vendor module.

UPDATE sys_menu
SET nav_act='return_to_vendor',
    main_table='erp_vendor_return',
    page_name='Return to Vendor',
    icon='fa-reply'
WHERE url='gi-return-to-vendor';

UPDATE sys_menu_role target
JOIN sys_menu target_menu ON target_menu.id=target.id_menu AND target_menu.url='gi-return-to-vendor'
JOIN sys_menu source_menu ON source_menu.url='return-to-vendor'
JOIN sys_menu_role source
  ON source.id_menu=source_menu.id
 AND source.group_level=target.group_level
SET target.read_act=source.read_act,
    target.insert_act=source.insert_act,
    target.update_act=source.update_act,
    target.delete_act=source.delete_act,
    target.import_act=source.import_act;

