-- SAP-like FI-AP Aging module.

UPDATE sys_menu
SET nav_act = 'ap_aging',
    main_table = 'erp_vendor_invoice'
WHERE url = 'ap-aging';
