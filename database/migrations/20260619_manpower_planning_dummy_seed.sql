DELETE FROM erp_manpower_plan_detail
 WHERE plan_id IN (SELECT id FROM erp_manpower_plan WHERE plan_no LIKE 'MPP-2026-%');
DELETE FROM erp_manpower_plan WHERE plan_no LIKE 'MPP-2026-%';

INSERT INTO erp_manpower_plan
(plan_no, plan_name, plan_year, plan_version, planning_type, planning_status, period_from, period_to,
 company_structure_id, department_code, cost_center_code, profit_center_code, budget_currency,
 approved_by_employee_id, approved_at, sap_reference, remarks, created_by, updated_by, updated_at)
VALUES
('MPP-2026-001','Annual Workforce Plan 2026 - Manufacturing & Warehouse',2026,'V1','ANNUAL','APPROVED','2026-01-01','2026-12-31',
 (SELECT id FROM erp_company_structure WHERE status='ACTIVE' ORDER BY id LIMIT 1),NULL,
 (SELECT cost_center_code FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code LIMIT 1),
 (SELECT profit_center_code FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code LIMIT 1),
 'IDR',(SELECT id FROM erp_employee_master WHERE employment_status IN ('ACTIVE','PROBATION','CONTRACT') ORDER BY id LIMIT 1),
 '2026-01-10 09:00:00','SAP-HCM-MPP-2026-001','Baseline annual manpower planning untuk produksi, gudang, PPIC, dan quality.','admin','admin',NOW()),
('MPP-2026-002','Q3 Production Ramp-up Manpower Plan',2026,'V1','QUARTERLY','SUBMITTED','2026-07-01','2026-09-30',
 (SELECT id FROM erp_company_structure WHERE status='ACTIVE' ORDER BY id LIMIT 1),'DEP-PRD',
 (SELECT cost_center_code FROM erp_cost_center WHERE status='Aktif' AND department_code='DEP-PRD' ORDER BY cost_center_code LIMIT 1),
 (SELECT profit_center_code FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code LIMIT 1),
 'IDR',NULL,NULL,'SAP-HCM-MPP-2026-002','Tambahan manpower untuk kenaikan output Q3.','admin','admin',NOW()),
('MPP-2026-003','Critical Vacancy Replacement Plan',2026,'V1','REPLACEMENT','DRAFT','2026-06-01','2026-12-31',
 (SELECT id FROM erp_company_structure WHERE status='ACTIVE' ORDER BY id LIMIT 1),NULL,
 (SELECT cost_center_code FROM erp_cost_center WHERE status='Aktif' ORDER BY cost_center_code LIMIT 1),
 (SELECT profit_center_code FROM erp_profit_center WHERE status='Aktif' ORDER BY profit_center_code LIMIT 1),
 'IDR',NULL,NULL,'SAP-HCM-MPP-2026-003','Rencana pengganti untuk vacancy kritikal dan posisi yang turnover risk tinggi.','admin','admin',NOW());

INSERT INTO erp_manpower_plan_detail
(plan_id,line_no,department_code,position_id,job_title_id,employee_group,pay_grade,current_headcount,current_fte,planned_headcount,planned_fte,requested_headcount,approved_headcount,gap_headcount,hire_type,priority,target_hire_date,estimated_monthly_cost,budget_amount,recruitment_status,reason,remarks)
SELECT p.id,1,'DEP-PRD',pos.id,pos.job_title_id,pos.employee_group,pos.pay_grade,1,COALESCE(pos.occupied_fte,0),2,2,1,1,1,'NEW_HIRE','HIGH','2026-07-15',6500000,78000000,'OPEN','Tambahan shift produksi untuk output coating/mixing.','Line produksi prioritas.'
  FROM erp_manpower_plan p JOIN erp_position pos ON pos.position_code='POS-MIX-OPR-001' WHERE p.plan_no='MPP-2026-001';
INSERT INTO erp_manpower_plan_detail
(plan_id,line_no,department_code,position_id,job_title_id,employee_group,pay_grade,current_headcount,current_fte,planned_headcount,planned_fte,requested_headcount,approved_headcount,gap_headcount,hire_type,priority,target_hire_date,estimated_monthly_cost,budget_amount,recruitment_status,reason,remarks)
SELECT p.id,2,'DEP-WH',pos.id,pos.job_title_id,pos.employee_group,pos.pay_grade,1,COALESCE(pos.occupied_fte,0),2,2,1,1,1,'NEW_HIRE','MEDIUM','2026-08-01',5500000,66000000,'NOT_STARTED','Tambahan warehouse staff untuk penerimaan dan pengeluaran barang.','Mendukung traceability lot dan dokumen BC.'
  FROM erp_manpower_plan p JOIN erp_position pos ON pos.position_code='POS-WH-MGR-001' WHERE p.plan_no='MPP-2026-001';
INSERT INTO erp_manpower_plan_detail
(plan_id,line_no,department_code,position_id,job_title_id,employee_group,pay_grade,current_headcount,current_fte,planned_headcount,planned_fte,requested_headcount,approved_headcount,gap_headcount,hire_type,priority,target_hire_date,estimated_monthly_cost,budget_amount,recruitment_status,reason,remarks)
SELECT p.id,1,'DEP-PRD',pos.id,pos.job_title_id,pos.employee_group,pos.pay_grade,1,COALESCE(pos.occupied_fte,0),3,3,2,0,2,'NEW_HIRE','CRITICAL','2026-07-01',6000000,36000000,'IN_PROGRESS','Ramp-up Q3 membutuhkan operator tambahan.','Menunggu approval final.'
  FROM erp_manpower_plan p JOIN erp_position pos ON pos.position_code='POS-COAT-OPR-001' WHERE p.plan_no='MPP-2026-002';
INSERT INTO erp_manpower_plan_detail
(plan_id,line_no,department_code,position_id,job_title_id,employee_group,pay_grade,current_headcount,current_fte,planned_headcount,planned_fte,requested_headcount,approved_headcount,gap_headcount,hire_type,priority,target_hire_date,estimated_monthly_cost,budget_amount,recruitment_status,reason,remarks)
SELECT p.id,2,'DEP-QA',pos.id,pos.job_title_id,pos.employee_group,pos.pay_grade,1,COALESCE(pos.occupied_fte,0),2,2,1,0,1,'NEW_HIRE','HIGH','2026-08-10',6500000,39000000,'NOT_STARTED','Perlu tambahan inspeksi incoming dan final inspection.','Quality gate Q3.'
  FROM erp_manpower_plan p JOIN erp_position pos ON pos.position_code='POS-QA-SPV-001' WHERE p.plan_no='MPP-2026-002';
INSERT INTO erp_manpower_plan_detail
(plan_id,line_no,department_code,position_id,job_title_id,employee_group,pay_grade,current_headcount,current_fte,planned_headcount,planned_fte,requested_headcount,approved_headcount,gap_headcount,hire_type,priority,target_hire_date,estimated_monthly_cost,budget_amount,recruitment_status,reason,remarks)
SELECT p.id,1,'DEP-PRD',pos.id,pos.job_title_id,pos.employee_group,pos.pay_grade,0,COALESCE(pos.occupied_fte,0),1,1,1,0,1,'REPLACEMENT','CRITICAL','2026-06-30',8000000,56000000,'OPEN','Posisi PPIC Supervisor masih vacant.','Replacement kritikal untuk planning produksi.'
  FROM erp_manpower_plan p JOIN erp_position pos ON pos.position_code='POS-PPIC-SPV-001' WHERE p.plan_no='MPP-2026-003';

UPDATE erp_manpower_plan p
   SET total_current_headcount=(SELECT COALESCE(SUM(current_headcount),0) FROM erp_manpower_plan_detail d WHERE d.plan_id=p.id),
       total_planned_headcount=(SELECT COALESCE(SUM(planned_headcount),0) FROM erp_manpower_plan_detail d WHERE d.plan_id=p.id),
       total_requested_headcount=(SELECT COALESCE(SUM(requested_headcount),0) FROM erp_manpower_plan_detail d WHERE d.plan_id=p.id),
       total_gap_headcount=(SELECT COALESCE(SUM(gap_headcount),0) FROM erp_manpower_plan_detail d WHERE d.plan_id=p.id),
       total_budget_amount=(SELECT COALESCE(SUM(budget_amount),0) FROM erp_manpower_plan_detail d WHERE d.plan_id=p.id)
 WHERE p.plan_no LIKE 'MPP-2026-%';
