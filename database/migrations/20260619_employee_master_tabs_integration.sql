UPDATE sys_menu
   SET tampil='N'
 WHERE url IN ('employee-family-data','employee-education','employee-document');

UPDATE sys_menu
   SET nav_act='employee_master_data',
       main_table='erp_employee_master',
       icon='fa-address-card',
       dt_table='Y',
       tampil='Y'
 WHERE url='employee-master-data';
