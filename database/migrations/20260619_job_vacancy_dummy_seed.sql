DELETE FROM erp_job_vacancy WHERE vacancy_no LIKE 'JV-2026-%';

INSERT INTO erp_job_vacancy
(vacancy_no,vacancy_title,manpower_plan_id,manpower_plan_detail_id,position_id,job_title_id,company_structure_id,department_code,cost_center_code,profit_center_code,work_location_id,
 vacancy_type,employment_type,employee_group,pay_grade,priority,vacancy_status,headcount_requested,headcount_approved,headcount_filled,salary_min,salary_max,currency,
 posting_date,closing_date,target_join_date,recruiter_employee_id,hiring_manager_employee_id,publish_internal,publish_external,source_channel,
 applicant_count,shortlisted_count,interview_count,offer_count,hired_count,job_description,qualification_requirement,responsibilities,benefits,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'JV-2026-001','PPIC Supervisor',
       mp.id,mpd.id,pos.id,pos.job_title_id,pos.company_structure_id,pos.department_code,pos.cost_center_code,pos.profit_center_code,pos.work_location_id,
       'REPLACEMENT','PERMANENT',pos.employee_group,pos.pay_grade,'CRITICAL','OPEN',1,1,0,7000000,9500000,'IDR',
       '2026-06-15','2026-07-15','2026-08-01',
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       'Y','Y','LinkedIn, Internal Referral',
       12,5,2,0,0,
       'Mengelola perencanaan produksi, material availability, dan koordinasi release production order.',
       'Pengalaman PPIC minimal 3 tahun, memahami MRP, BOM, dan production scheduling.',
       'Menyusun schedule produksi, follow up material, dan monitoring WIP.',
       'BPJS, tunjangan makan, transport, dan bonus kinerja.',
       'SAP-HCM-JV-2026-001','Vacancy dari critical replacement plan.','admin','admin',NOW()
  FROM erp_position pos
  LEFT JOIN erp_manpower_plan mp ON mp.plan_no='MPP-2026-003'
  LEFT JOIN erp_manpower_plan_detail mpd ON mpd.plan_id=mp.id AND mpd.position_id=pos.id
 WHERE pos.position_code='POS-PPIC-SPV-001'
 LIMIT 1;

INSERT INTO erp_job_vacancy
(vacancy_no,vacancy_title,manpower_plan_id,manpower_plan_detail_id,position_id,job_title_id,company_structure_id,department_code,cost_center_code,profit_center_code,work_location_id,
 vacancy_type,employment_type,employee_group,pay_grade,priority,vacancy_status,headcount_requested,headcount_approved,headcount_filled,salary_min,salary_max,currency,
 posting_date,closing_date,target_join_date,recruiter_employee_id,hiring_manager_employee_id,publish_internal,publish_external,source_channel,
 applicant_count,shortlisted_count,interview_count,offer_count,hired_count,job_description,qualification_requirement,responsibilities,benefits,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'JV-2026-002','Coating Operator',
       mp.id,mpd.id,pos.id,pos.job_title_id,pos.company_structure_id,pos.department_code,pos.cost_center_code,pos.profit_center_code,pos.work_location_id,
       'NEW_POSITION','CONTRACT',pos.employee_group,pos.pay_grade,'HIGH','SCREENING',2,2,0,4500000,6000000,'IDR',
       '2026-06-20','2026-07-20','2026-08-05',
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       'Y','Y','Job Portal, Walk-in',
       28,10,4,0,0,
       'Operator coating untuk mendukung peningkatan kapasitas produksi Q3.',
       'Pengalaman operator produksi, disiplin shift, dan memahami basic quality check.',
       'Menjalankan mesin coating, mencatat output, dan menjaga kebersihan area kerja.',
       'BPJS, uang shift, dan insentif produksi.',
       'SAP-HCM-JV-2026-002','Vacancy dari Q3 ramp-up manpower plan.','admin','admin',NOW()
  FROM erp_position pos
  LEFT JOIN erp_manpower_plan mp ON mp.plan_no='MPP-2026-002'
  LEFT JOIN erp_manpower_plan_detail mpd ON mpd.plan_id=mp.id AND mpd.position_id=pos.id
 WHERE pos.position_code='POS-COAT-OPR-001'
 LIMIT 1;

INSERT INTO erp_job_vacancy
(vacancy_no,vacancy_title,position_id,job_title_id,company_structure_id,department_code,cost_center_code,profit_center_code,work_location_id,
 vacancy_type,employment_type,employee_group,pay_grade,priority,vacancy_status,headcount_requested,headcount_approved,headcount_filled,salary_min,salary_max,currency,
 posting_date,closing_date,target_join_date,recruiter_employee_id,hiring_manager_employee_id,publish_internal,publish_external,source_channel,
 applicant_count,shortlisted_count,interview_count,offer_count,hired_count,job_description,qualification_requirement,responsibilities,benefits,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'JV-2026-003','Sales Manager',
       pos.id,pos.job_title_id,pos.company_structure_id,pos.department_code,pos.cost_center_code,pos.profit_center_code,pos.work_location_id,
       'REPLACEMENT','PERMANENT',pos.employee_group,pos.pay_grade,'MEDIUM','DRAFT',1,0,0,10000000,15000000,'IDR',
       NULL,'2026-08-31','2026-09-15',
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-SLS' ORDER BY id LIMIT 1),
       'Y','N','Internal Posting',
       0,0,0,0,0,
       'Mengembangkan strategi sales dan menjaga pipeline customer.',
       'Pengalaman sales B2B/manufacturing minimal 5 tahun dan leadership team.',
       'Mengelola target sales, forecast, quotation follow-up, dan customer relationship.',
       'BPJS, kendaraan operasional, insentif sales.',
       'SAP-HCM-JV-2026-003','Draft replacement untuk posisi sales manager.','admin','admin',NOW()
  FROM erp_position pos
 WHERE pos.position_code='POS-SLS-MGR-001'
 LIMIT 1;
