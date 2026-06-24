DELETE FROM erp_salary_structure_detail
 WHERE structure_id IN (SELECT id FROM erp_salary_structure WHERE structure_code LIKE 'SS-%');
DELETE FROM erp_salary_structure WHERE structure_code LIKE 'SS-%';

INSERT INTO erp_salary_structure
(structure_code,structure_name,pay_scale_type,pay_scale_area,pay_grade,pay_level,position_level,employee_group,payroll_area,currency,base_salary_min,base_salary_mid,base_salary_max,annual_ctc_min,annual_ctc_max,cost_center_code,profit_center_code,valid_from,valid_to,structure_status,sap_reference,remarks,created_by,updated_by,updated_at)
VALUES
('SS-MGT-G10-L1','Management Grade 10 Level 1','MANAGEMENT','ID-JKT','G10','L1','Senior Manager','MANAGER','MANAGEMENT','IDR',22000000,25000000,30000000,330000000,450000000,'1000-HR','1000-CORP','2026-01-01','9999-12-31','ACTIVE','SAP-HCM-PAY-001','Salary band untuk senior manager dan role strategis.', 'Nadia','Nadia',NOW()),
('SS-STF-G07-L2','Staff Grade 7 Level 2','MONTHLY','ID-JKT','G07','L2','Senior Staff','STAFF','MONTHLY','IDR',9000000,11000000,13500000,135000000,180000000,'1000-HR','1000-CORP','2026-01-01','9999-12-31','ACTIVE','SAP-HCM-PAY-002','Salary band staff senior dengan allowance tetap.', 'Tinna','Tinna',NOW()),
('SS-OPR-G04-L1','Operator Grade 4 Level 1','MONTHLY','ID-BDG','G04','L1','Operator','OPERATOR','MONTHLY','IDR',4800000,5600000,6500000,65000000,90000000,'3000-PRD','3000-MFG','2026-01-01','9999-12-31','ACTIVE','SAP-HCM-PAY-003','Salary band operator produksi dengan meal dan overtime.', 'IGPRD','IGPRD',NOW()),
('SS-DLY-G02-L1','Daily Worker Grade 2','DAILY','ID-BDG','G02','L1','Daily Worker','DAILY_WORKER','DAILY','IDR',180000,210000,250000,0,0,'3000-PRD','3000-MFG','2026-01-01','9999-12-31','ACTIVE','SAP-HCM-PAY-004','Struktur upah harian untuk tenaga borongan/harian.', 'Siti Aminah','Siti Aminah',NOW()),
('SS-TRN-G01-L1','Trainee Grade 1','MONTHLY','ID-JKT','G01','L1','Trainee','TRAINEE','MONTHLY','IDR',3500000,4000000,4500000,45000000,60000000,'1000-HR','1000-CORP','2026-01-01','9999-12-31','DRAFT','SAP-HCM-PAY-005','Draft salary band trainee.', 'Budi Hartono','Budi Hartono',NOW());

INSERT INTO erp_salary_structure_detail
(structure_id,component_code,calculation_method,amount,percentage_rate,formula_text,mandatory,taxable,payslip_display,sequence_no)
SELECT s.id,'PC-BASIC-001','FIXED_AMOUNT',s.base_salary_mid,0,NULL,'Y','Y','Y',10 FROM erp_salary_structure s WHERE s.structure_code LIKE 'SS-%';

INSERT INTO erp_salary_structure_detail
(structure_id,component_code,calculation_method,amount,percentage_rate,formula_text,mandatory,taxable,payslip_display,sequence_no)
SELECT s.id,'PC-ALLOW-TRANS','FIXED_AMOUNT',
       CASE WHEN s.employee_group='MANAGER' THEN 2000000 WHEN s.employee_group='STAFF' THEN 1000000 WHEN s.employee_group='OPERATOR' THEN 500000 ELSE 0 END,
       0,NULL,'N','Y','Y',20
  FROM erp_salary_structure s
 WHERE s.structure_code IN ('SS-MGT-G10-L1','SS-STF-G07-L2','SS-OPR-G04-L1');

INSERT INTO erp_salary_structure_detail
(structure_id,component_code,calculation_method,amount,percentage_rate,formula_text,mandatory,taxable,payslip_display,sequence_no)
SELECT s.id,'PC-ALLOW-MEAL','ATTENDANCE_BASED',25000,0,'WORKING_DAYS * 25000','N','Y','Y',30
  FROM erp_salary_structure s
 WHERE s.structure_code IN ('SS-STF-G07-L2','SS-OPR-G04-L1','SS-DLY-G02-L1');

INSERT INTO erp_salary_structure_detail
(structure_id,component_code,calculation_method,amount,percentage_rate,formula_text,mandatory,taxable,payslip_display,sequence_no)
SELECT s.id,'PC-BPJS-TK-EMP','PERCENTAGE',0,2.0000,'BASIC_SALARY * 2%','Y','N','Y',210 FROM erp_salary_structure s WHERE s.structure_code LIKE 'SS-%';

INSERT INTO erp_salary_structure_detail
(structure_id,component_code,calculation_method,amount,percentage_rate,formula_text,mandatory,taxable,payslip_display,sequence_no)
SELECT s.id,'PC-TAX-PPH21','FORMULA',0,0,'PPH21_PROGRESSIVE_TAX','Y','N','Y',230 FROM erp_salary_structure s WHERE s.structure_code LIKE 'SS-%';
