DELETE FROM erp_work_location
 WHERE location_code IN ('WL-HQ-JKT','WL-PL01-MFG','WL-WH-RM','WL-WH-FG','WL-SO-JKT','WL-REMOTE');

INSERT INTO erp_work_location (
  location_code, location_name, location_type, company_structure_id, plant_id, storage_location_id,
  cost_center_code, profit_center_code, country, province, city, district, postal_code, address,
  latitude, longitude, timezone, work_location_category, attendance_required, geo_fence_radius_meter,
  capacity_headcount, working_calendar_code, default_shift_code, contact_person, phone, email,
  valid_from, valid_to, sap_reference, status, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'WL-HQ-JKT','Head Office Jakarta','HEAD_OFFICE',cs.id,NULL,NULL,
       '1000-ADM','1000-CORP','ID','DKI Jakarta','Jakarta','Cakung','13910',
       'Jl. Kawasan Industri No. 1, Jakarta',-6.1842500,106.9401200,'Asia/Jakarta','PRIMARY','Y',150,
       80,'ID_OFFICE_CALENDAR','NON-SHIFT','HR Admin','021-555-0101','hr.admin@erpkb.local',
       '2026-01-01','9999-12-31','SAP-WL-HQ-JKT','ACTIVE','Lokasi kantor pusat untuk fungsi corporate dan support.',
       'admin','2026-06-19 09:00:00','admin','2026-06-19 09:00:00'
  FROM erp_company_structure cs WHERE cs.structure_code='PA-HQ' LIMIT 1;

INSERT INTO erp_work_location (
  location_code, location_name, location_type, company_structure_id, plant_id, storage_location_id,
  cost_center_code, profit_center_code, country, province, city, district, postal_code, address,
  latitude, longitude, timezone, work_location_category, attendance_required, geo_fence_radius_meter,
  capacity_headcount, working_calendar_code, default_shift_code, contact_person, phone, email,
  valid_from, valid_to, sap_reference, status, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'WL-PL01-MFG','Main Manufacturing Plant','PLANT',cs.id,p.id,NULL,
       '1000-PRD','1000-PLANT01','ID','Banten','Tangerang','Cikupa','15710',
       'Kawasan Industri Plant 01, Tangerang',-6.2305000,106.5201000,'Asia/Jakarta','PRIMARY','Y',250,
       350,'SHIFT_24_7','SHIFT-1','Plant HR','021-555-0201','plant.hr@erpkb.local',
       '2026-01-01','9999-12-31','SAP-WL-PL01','ACTIVE','Lokasi kerja utama produksi dan shop floor.',
       'admin','2026-06-19 09:05:00','admin','2026-06-19 09:05:00'
  FROM erp_company_structure cs JOIN erp_plant p ON p.plant_code='PL01'
 WHERE cs.structure_code='PA-PLT' LIMIT 1;

INSERT INTO erp_work_location (
  location_code, location_name, location_type, company_structure_id, plant_id, storage_location_id,
  cost_center_code, profit_center_code, country, province, city, district, postal_code, address,
  latitude, longitude, timezone, work_location_category, attendance_required, geo_fence_radius_meter,
  capacity_headcount, working_calendar_code, default_shift_code, contact_person, phone, email,
  valid_from, valid_to, sap_reference, status, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'WL-WH-RM','Raw Material Warehouse Work Location','WAREHOUSE',cs.id,p.id,sl.id,
       '1000-WH','1000-PLANT01','ID','Banten','Tangerang','Cikupa','15710',
       'Gudang RM Plant 01, Tangerang',-6.2310000,106.5210000,'Asia/Jakarta','PRIMARY','Y',180,
       90,'SHIFT_24_7','SHIFT-1','Warehouse Supervisor','021-555-0301','warehouse.rm@erpkb.local',
       '2026-01-01','9999-12-31','SAP-WL-WH-RM','ACTIVE','Lokasi kerja employee gudang bahan baku.',
       'admin','2026-06-19 09:10:00','admin','2026-06-19 09:10:00'
  FROM erp_company_structure cs JOIN erp_plant p ON p.plant_code='PL01' JOIN erp_storage_location sl ON sl.plant_id=p.id AND sl.storage_code='RM01'
 WHERE cs.structure_code='PSA-WH' LIMIT 1;

INSERT INTO erp_work_location (
  location_code, location_name, location_type, company_structure_id, plant_id, storage_location_id,
  cost_center_code, profit_center_code, country, province, city, district, postal_code, address,
  latitude, longitude, timezone, work_location_category, attendance_required, geo_fence_radius_meter,
  capacity_headcount, working_calendar_code, default_shift_code, contact_person, phone, email,
  valid_from, valid_to, sap_reference, status, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'WL-WH-FG','Finished Goods Warehouse Work Location','WAREHOUSE',cs.id,p.id,sl.id,
       '1000-LOG','1000-LOCAL','ID','Banten','Tangerang','Cikupa','15710',
       'Gudang FG Plant 01, Tangerang',-6.2319000,106.5223000,'Asia/Jakarta','PRIMARY','Y',180,
       70,'SHIFT_24_7','SHIFT-2','Shipping Coordinator','021-555-0302','warehouse.fg@erpkb.local',
       '2026-01-01','9999-12-31','SAP-WL-WH-FG','ACTIVE','Lokasi kerja finished goods dan shipping.',
       'admin','2026-06-19 09:15:00','admin','2026-06-19 09:15:00'
  FROM erp_company_structure cs JOIN erp_plant p ON p.plant_code='PL01' JOIN erp_storage_location sl ON sl.plant_id=p.id AND sl.storage_code='FG01'
 WHERE cs.structure_code='PSA-WH' LIMIT 1;

INSERT INTO erp_work_location (
  location_code, location_name, location_type, company_structure_id, plant_id, storage_location_id,
  cost_center_code, profit_center_code, country, province, city, district, postal_code, address,
  latitude, longitude, timezone, work_location_category, attendance_required, geo_fence_radius_meter,
  capacity_headcount, working_calendar_code, default_shift_code, contact_person, phone, email,
  valid_from, valid_to, sap_reference, status, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'WL-SO-JKT','Jakarta Sales Office','SALES_OFFICE',cs.id,NULL,NULL,
       '1000-SLS','1000-LOCAL','ID','DKI Jakarta','Jakarta','Kelapa Gading','14240',
       'Ruko Sales Office Jakarta Utara',-6.1581000,106.9057000,'Asia/Jakarta','SECONDARY','Y',120,
       25,'ID_OFFICE_CALENDAR','NON-SHIFT','Sales Admin','021-555-0401','sales.office@erpkb.local',
       '2026-01-01','9999-12-31','SAP-WL-SO-JKT','ACTIVE','Lokasi tim sales lokal dan customer service.',
       'admin','2026-06-19 09:20:00','admin','2026-06-19 09:20:00'
  FROM erp_company_structure cs WHERE cs.structure_code='PSA-SLS' LIMIT 1;

INSERT INTO erp_work_location (
  location_code, location_name, location_type, company_structure_id, plant_id, storage_location_id,
  cost_center_code, profit_center_code, country, province, city, district, postal_code, address,
  latitude, longitude, timezone, work_location_category, attendance_required, geo_fence_radius_meter,
  capacity_headcount, working_calendar_code, default_shift_code, contact_person, phone, email,
  valid_from, valid_to, sap_reference, status, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'WL-REMOTE','Remote Work Location','REMOTE',cs.id,NULL,NULL,
       '1000-IT','1000-CORP','ID','','','','',
       'Remote / Work From Anywhere',NULL,NULL,'Asia/Jakarta','VIRTUAL','N',NULL,
       20,'REMOTE_CALENDAR','FLEXIBLE','HR Operations','021-555-0501','remote.hr@erpkb.local',
       '2026-01-01','9999-12-31','SAP-WL-REMOTE','ACTIVE','Lokasi virtual untuk employee remote/hybrid.',
       'admin','2026-06-19 09:25:00','admin','2026-06-19 09:25:00'
  FROM erp_company_structure cs WHERE cs.structure_code='PA-HQ' LIMIT 1;
