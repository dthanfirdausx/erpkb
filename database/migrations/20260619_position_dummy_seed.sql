START TRANSACTION;

DELETE FROM erp_position
 WHERE position_code IN ('POS-GM-001','POS-FIN-MGR-001','POS-HR-MGR-001','POS-PPIC-SPV-001','POS-PRD-MGR-001','POS-WH-MGR-001','POS-QA-SPV-001','POS-MIX-OPR-001','POS-COAT-OPR-001','POS-SLS-MGR-001');

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-GM-001','General Manager Position','GM','STRUCTURAL','KEY_POSITION',jt.id,'DEP-ADM',cs.id,
       NULL,e.id,'1000-ADM','1000-CORP',wl.id,'DIRECTOR','DIR',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Memimpin keseluruhan operasi dan memastikan strategi bisnis berjalan.',
       'Leadership, finance literacy, manufacturing operation, dan governance.',
       'Approval strategis lintas fungsi dan kebijakan perusahaan.',
       'Key position untuk succession planning level executive.',
       'SAP-POS-GM-001','Dummy SAP position object.', 'Tinna','Tinna',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='PSA-ADM'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0001'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
 WHERE jt.job_title_code='JT-GM' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-FIN-MGR-001','Finance Manager Position','Finance Mgr','STRUCTURAL','KEY_POSITION',jt.id,'DEP-FIN',cs.id,
       gm.id,e.id,'1000-FIN','1000-CORP',wl.id,'MANAGER','M03',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Mengelola accounting, tax, AP/AR, treasury, dan closing.',
       'Finance accounting, tax, reporting, dan leadership.',
       'Approval jurnal, payment proposal, dan financial closing.',
       'Kandidat suksesi dari senior accounting.',
       'SAP-POS-FIN-MGR-001','Dummy finance position.', 'Nadia','Nadia',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-FIN'
  LEFT JOIN erp_position gm ON gm.position_code='POS-GM-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0002'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
 WHERE jt.job_title_code='JT-FIN-MGR' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-HR-MGR-001','HR Manager Position','HR Mgr','STRUCTURAL','KEY_POSITION',jt.id,'DEP-HR',cs.id,
       gm.id,e.id,'1000-HR','1000-CORP',wl.id,'MANAGER','M03',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Mengelola organization management, employee admin, attendance, dan HR operation.',
       'HR operation, industrial relation, organization design, dan payroll coordination.',
       'Approval perubahan master HR dan struktur organisasi.',
       'Kandidat suksesi dari HR supervisor.',
       'SAP-POS-HR-MGR-001','Dummy HR position.', 'Tinna','Tinna',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-HR'
  LEFT JOIN erp_position gm ON gm.position_code='POS-GM-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0003'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
 WHERE jt.job_title_code='JT-HR-MGR' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-PRD-MGR-001','Production Manager Position','Prod Mgr','STRUCTURAL','KEY_POSITION',jt.id,'DEP-PRD',cs.id,
       gm.id,e.id,'1000-PRD','1000-MFG',wl.id,'MANAGER','M03',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Mengelola shop floor execution, output, yield, WIP, dan efficiency.',
       'Manufacturing operation, production planning, quality awareness, dan leadership.',
       'Approval penggunaan resource produksi dan review confirmation.',
       'Critical manufacturing leadership.',
       'SAP-POS-PRD-MGR-001','Dummy production manager position.', 'IGPRD','IGPRD',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-PROD'
  LEFT JOIN erp_position gm ON gm.position_code='POS-GM-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0004'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
 WHERE jt.job_title_code='JT-PRD-MGR' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-PPIC-SPV-001','PPIC Supervisor Position','PPIC Spv','STRUCTURAL','CRITICAL',jt.id,'DEP-PRD',cs.id,
       prd.id,NULL,'1000-PRD','1000-MFG',wl.id,'STAFF','G07',1,0,1,'VACANT','ACTIVE','2026-01-01','9999-12-31',
       'Mengatur forecast, MRP, material requirement, dan production schedule.',
       'PPIC, MRP, material planning, dan production scheduling.',
       'Mengusulkan production order dan material staging request.',
       'Vacant critical position untuk segera diisi.',
       'SAP-POS-PPIC-SPV-001','Dummy vacant PPIC position.', 'IGPPIC','IGPPIC',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-PPIC'
  LEFT JOIN erp_position prd ON prd.position_code='POS-PRD-MGR-001'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
 WHERE jt.job_title_code='JT-PPIC-SPV' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-WH-MGR-001','Warehouse Manager Position','WH Mgr','STRUCTURAL','KEY_POSITION',jt.id,'DEP-WH',cs.id,
       gm.id,e.id,'1000-WH','1000-PLANT01',wl.id,'MANAGER','M03',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Mengelola GR, GI, stock transfer, stock accuracy, dan traceability.',
       'Warehouse management, inventory control, customs traceability, dan leadership.',
       'Approval warehouse movement dan stock adjustment sesuai matrix.',
       'Key warehouse leadership.',
       'SAP-POS-WH-MGR-001','Dummy warehouse manager position.', 'IGWH_IN','IGWH_IN',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-WH'
  LEFT JOIN erp_position gm ON gm.position_code='POS-GM-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0005'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-WH-RM'
 WHERE jt.job_title_code='JT-WH-MGR' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-QA-SPV-001','QA/QC Supervisor Position','QA Spv','STRUCTURAL','CRITICAL',jt.id,'DEP-QA',cs.id,
       prd.id,e.id,'1000-QA','1000-MFG',wl.id,'STAFF','G07',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Mengelola incoming, in-process, final inspection, NCR, CAPA, dan usage decision.',
       'Quality management, inspection process, CAPA, dan problem solving.',
       'Approval usage decision sesuai limit QA.',
       'Critical quality position.',
       'SAP-POS-QA-SPV-001','Dummy quality position.', 'Nadia','Nadia',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-QA'
  LEFT JOIN erp_position prd ON prd.position_code='POS-PRD-MGR-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0006'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
 WHERE jt.job_title_code='JT-QA-SPV' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-MIX-OPR-001','Mixing Operator Position','Mix Op','OPERATIONAL','REGULAR',jt.id,'DEP-0001',cs.id,
       prd.id,e.id,'1000-MIX','1000-PROD-A',wl.id,'OPERATOR','G02',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Menjalankan proses mixing sesuai production order dan work instruction.',
       'SMA/SMK, memahami WI produksi, material handling, dan safety.',
       'Mengoperasikan mesin mixing sesuai area responsibility.',
       'Operator regular.',
       'SAP-POS-MIX-OPR-001','Dummy operator position.', 'IGPRD','IGPRD',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-MIX'
  LEFT JOIN erp_position prd ON prd.position_code='POS-PRD-MGR-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0007'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
 WHERE jt.job_title_code='JT-MIX-OPR' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-COAT-OPR-001','Coating Operator Position','Coat Op','OPERATIONAL','REGULAR',jt.id,'DEP-0002',cs.id,
       prd.id,e.id,'1000-COAT','1000-PROD-A',wl.id,'OPERATOR','G02',1,1,1,'OCCUPIED','ACTIVE','2026-01-01','9999-12-31',
       'Menjalankan proses coating sesuai routing dan quality parameter.',
       'SMA/SMK, memahami parameter coating, safety, dan quality check.',
       'Mengoperasikan line coating sesuai WI.',
       'Operator regular.',
       'SAP-POS-COAT-OPR-001','Dummy operator position.', 'IGPRD','IGPRD',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-COAT'
  LEFT JOIN erp_position prd ON prd.position_code='POS-PRD-MGR-001'
  LEFT JOIN erp_employee_master e ON e.employee_no='EMP-0008'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
 WHERE jt.job_title_code='JT-COAT-OPR' LIMIT 1;

INSERT INTO erp_position (
  position_code,position_name,position_short_name,position_type,position_category,job_title_id,department_code,company_structure_id,
  reports_to_position_id,holder_employee_id,cost_center_code,profit_center_code,work_location_id,employee_group,pay_grade,planned_fte,occupied_fte,
  headcount_plan,vacancy_status,position_status,valid_from,valid_to,job_description,qualification_requirement,authority_limit,succession_plan_note,
  sap_reference,remarks,created_by,updated_by,updated_at
)
SELECT 'POS-SLS-MGR-001','Sales Manager Position','Sales Mgr','STRUCTURAL','KEY_POSITION',jt.id,'DEP-SLS',cs.id,
       gm.id,NULL,'1000-SLS','1000-LOCAL',wl.id,'MANAGER','M03',1,0,1,'VACANT','ACTIVE','2026-01-01','9999-12-31',
       'Mengelola pipeline sales, quotation, sales order, dan customer relationship.',
       'Sales management, commercial negotiation, customer relationship, dan reporting.',
       'Approval commercial proposal sesuai matrix.',
       'Vacant key sales position.',
       'SAP-POS-SLS-MGR-001','Dummy vacant sales manager position.', 'Nadia','Nadia',NOW()
  FROM erp_job_title jt
  LEFT JOIN erp_company_structure cs ON cs.structure_code='OU-SLS'
  LEFT JOIN erp_position gm ON gm.position_code='POS-GM-001'
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-SO-JKT'
 WHERE jt.job_title_code='JT-SLS-MGR' LIMIT 1;

COMMIT;
