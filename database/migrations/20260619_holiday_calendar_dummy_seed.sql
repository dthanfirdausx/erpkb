START TRANSACTION;

DELETE FROM erp_holiday_calendar
 WHERE holiday_code IN ('HC-2026-0001','HC-2026-0002','HC-2026-0003','HC-2026-0004','HC-2026-0005','HC-2026-0006','HC-2026-0007','HC-2026-0008');

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0001','Tahun Baru Masehi','2026-01-01','2026-01-01','PUBLIC_HOLIDAY','NATIONAL',id,calendar_code,plant_code,'ID-NATIONAL','ID',0,'Y','Y','SKB Libur Nasional 2026','ACTIVE','SAP-HC-0001','Libur nasional tahun baru.', 'Tinna','Tinna',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0002','Hari Raya Idul Fitri','2026-03-20','2026-03-21','RELIGIOUS_HOLIDAY','NATIONAL',id,calendar_code,plant_code,'ID-NATIONAL','ID',0,'Y','N','SKB Libur Nasional 2026','ACTIVE','SAP-HC-0002','Libur nasional Idul Fitri.', 'Tinna','Tinna',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0003','Cuti Bersama Idul Fitri','2026-03-18','2026-03-19','COLLECTIVE_LEAVE','COMPANY',id,calendar_code,plant_code,'ID-COMPANY','ID',0,'Y','N','Policy HR 2026','ACTIVE','SAP-HC-0003','Cuti bersama perusahaan.', 'Nadia','Nadia',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0004','Hari Buruh Nasional','2026-05-01','2026-05-01','PUBLIC_HOLIDAY','NATIONAL',id,calendar_code,plant_code,'ID-NATIONAL','ID',0,'Y','Y','SKB Libur Nasional 2026','ACTIVE','SAP-HC-0004','Libur nasional Hari Buruh.', 'Tinna','Tinna',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0005','Hari Kemerdekaan RI','2026-08-17','2026-08-17','PUBLIC_HOLIDAY','NATIONAL',id,calendar_code,plant_code,'ID-NATIONAL','ID',0,'Y','Y','SKB Libur Nasional 2026','ACTIVE','SAP-HC-0005','Libur nasional kemerdekaan.', 'IGPRD','IGPRD',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0006','Maintenance Shutdown Plant','2026-09-10','2026-09-12','COMPANY_HOLIDAY','PLANT',id,calendar_code,plant_code,'PL01','ID',0,'Y','N','Production shutdown plan','ACTIVE','SAP-HC-0006','Plant shutdown untuk preventive maintenance.', 'Budi Hartono','Budi Hartono',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0007','Stock Opname Half Day','2026-12-30','2026-12-30','HALF_DAY','COMPANY',id,calendar_code,plant_code,'ID-COMPANY','ID',4,'Y','N','Finance closing schedule','DRAFT','SAP-HC-0007','Setengah hari kerja untuk stock opname.', 'Nadia','Nadia',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

INSERT INTO erp_holiday_calendar (holiday_code,holiday_name,holiday_date,holiday_end_date,holiday_type,holiday_scope,calendar_id,calendar_code,plant_code,region_code,country,working_hours,paid_holiday,recurring_annual,source_reference,holiday_status,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HC-2026-0008','Local Election Holiday','2026-11-25','2026-11-25','REGIONAL_HOLIDAY','REGIONAL',id,calendar_code,plant_code,'ID-JB','ID',0,'Y','N','Regional government holiday','INACTIVE','SAP-HC-0008','Contoh regional holiday inactive.', 'Siti Aminah','Siti Aminah',NOW() FROM erp_factory_calendar_header WHERE calendar_code='FC-BASE-2026' LIMIT 1;

COMMIT;
