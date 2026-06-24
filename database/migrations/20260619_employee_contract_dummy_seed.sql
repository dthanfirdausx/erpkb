START TRANSACTION;

DELETE FROM erp_employee_contract
 WHERE contract_no IN ('CTR-2026-0001','CTR-2026-0002','CTR-2026-0003','CTR-2026-0004','CTR-2026-0005','CTR-2026-0006','CTR-2026-0007','CTR-2026-0008');

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0001', e.id, 'PERMANENT', 'ACTIVE', '2026-01-01', NULL, NULL, NULL,
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 25000000, 'IDR', 40, 60,
       'NEW_HIRE', 'APPROVED', appr.id, '2026-01-01 09:00:00', 'contracts/CTR-2026-0001.pdf', 'SAP-CTR-0001', 'Permanent contract untuk General Manager.', 'Tinna', 'Tinna', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0001' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0002', e.id, 'PERMANENT', 'ACTIVE', '2026-01-01', NULL, NULL, NULL,
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 18000000, 'IDR', 40, 45,
       'NEW_HIRE', 'APPROVED', appr.id, '2026-01-01 10:00:00', 'contracts/CTR-2026-0002.pdf', 'SAP-CTR-0002', 'Permanent contract Finance Manager.', 'Nadia', 'Nadia', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0002' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0003', e.id, 'PERMANENT', 'ACTIVE', '2026-01-01', NULL, NULL, NULL,
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 17500000, 'IDR', 40, 45,
       'NEW_HIRE', 'APPROVED', appr.id, '2026-01-01 11:00:00', 'contracts/CTR-2026-0003.pdf', 'SAP-CTR-0003', 'Permanent contract HR Manager.', 'Tinna', 'Tinna', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-HQ-JKT'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0001'
 WHERE e.employee_no='EMP-0003' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0004', e.id, 'FIXED_TERM', 'ACTIVE', '2026-02-01', '2027-01-31', '2026-02-01', '2026-04-30',
       1, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 12500000, 'IDR', 40, 30,
       'RENEWAL', 'APPROVED', appr.id, '2026-01-25 14:00:00', 'contracts/CTR-2026-0004.pdf', 'SAP-CTR-0004', 'PKWT Production Manager.', 'IGPRD', 'IGPRD', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0004' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0005', e.id, 'FIXED_TERM', 'ACTIVE', '2026-03-01', '2026-08-31', '2026-03-01', '2026-05-31',
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 7000000, 'IDR', 40, 30,
       'NEW_HIRE', 'APPROVED', appr.id, '2026-02-25 13:00:00', 'contracts/CTR-2026-0005.pdf', 'SAP-CTR-0005', 'PKWT Warehouse Manager.', 'IGWH_IN', 'IGWH_IN', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-WH-RM'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0005' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0006', e.id, 'FIXED_TERM', 'ACTIVE', '2026-04-01', '2026-09-30', '2026-04-01', '2026-06-30',
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 6000000, 'IDR', 40, 30,
       'NEW_HIRE', 'PENDING', NULL, NULL, 'contracts/CTR-2026-0006-draft.pdf', 'SAP-CTR-0006', 'Kontrak QA Supervisor menunggu approval HR.', 'Nadia', 'Nadia', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
 WHERE e.employee_no='EMP-0006' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0007', e.id, 'DAILY_WORKER', 'ACTIVE', '2026-05-01', '2026-10-31', NULL, NULL,
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 4500000, 'IDR', 40, 14,
       'NEW_HIRE', 'APPROVED', appr.id, '2026-04-28 10:00:00', 'contracts/CTR-2026-0007.pdf', 'SAP-CTR-0007', 'Kontrak operator mixing.', 'IGPRD', 'IGPRD', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0004'
 WHERE e.employee_no='EMP-0007' LIMIT 1;

INSERT INTO erp_employee_contract (
  contract_no, employee_id, contract_type, contract_status, contract_start, contract_end, probation_start, probation_end,
  renewal_no, previous_contract_id, department_code, job_title_id, position_id, company_structure_id, work_location_id,
  employee_group, pay_grade, payroll_area, basic_salary, currency, working_hours_per_week, notice_period_days,
  contract_reason, approval_status, approved_by_employee_id, approved_at, attachment_ref, sap_reference, remarks, created_by, updated_by, updated_at
)
SELECT 'CTR-2026-0008', e.id, 'DAILY_WORKER', 'EXPIRED', '2025-11-01', '2026-04-30', NULL, NULL,
       0, NULL, e.department_code, e.job_title_id, p.id, e.company_structure_id, wl.id,
       e.employee_group, e.pay_grade, e.payroll_area, 4400000, 'IDR', 40, 14,
       'NEW_HIRE', 'APPROVED', appr.id, '2025-10-28 10:00:00', 'contracts/CTR-2026-0008.pdf', 'SAP-CTR-0008', 'Contoh kontrak expired untuk monitoring renewal.', 'IGPRD', 'IGPRD', NOW()
  FROM erp_employee_master e
  LEFT JOIN erp_position p ON p.holder_employee_id=e.id
  LEFT JOIN erp_work_location wl ON wl.location_code='WL-PL01-MFG'
  LEFT JOIN erp_employee_master appr ON appr.employee_no='EMP-0004'
 WHERE e.employee_no='EMP-0008' LIMIT 1;

COMMIT;
