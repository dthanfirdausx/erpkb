DELETE FROM erp_payslip_detail
 WHERE payslip_id IN (SELECT id FROM erp_payslip WHERE payslip_no LIKE 'PSL-2026-06-%');
DELETE FROM erp_payslip WHERE payslip_no LIKE 'PSL-2026-06-%';

INSERT INTO erp_payslip
(payslip_no,payroll_process_id,payroll_employee_id,payroll_run_no,employee_id,employee_no,full_name,department_code,employee_group,payroll_area,period_year,period_month,period_from,period_to,pay_date,salary_structure_code,working_days,paid_days,absence_days,overtime_hours,gross_pay,total_earning,total_deduction,tax_amount,net_pay,currency,payslip_status,release_channel,generated_by,generated_at,released_by,released_at,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT CONCAT('PSL-2026-06-',pe.employee_no),p.id,pe.id,p.payroll_run_no,pe.employee_id,pe.employee_no,pe.full_name,pe.department_code,pe.employee_group,pe.payroll_area,p.period_year,p.period_month,p.period_from,p.period_to,p.pay_date,pe.salary_structure_code,pe.working_days,pe.paid_days,pe.absence_days,pe.overtime_hours,pe.gross_pay,pe.total_earning,pe.total_deduction,pe.tax_amount,pe.net_pay,p.currency,
       CASE WHEN pe.employee_no='EMP-0006' THEN 'RELEASED' ELSE 'GENERATED' END,
       'PORTAL','Nadia',NOW(),
       CASE WHEN pe.employee_no='EMP-0006' THEN 'Nadia' ELSE NULL END,
       CASE WHEN pe.employee_no='EMP-0006' THEN NOW() ELSE NULL END,
       CONCAT('SAP-HCM-PSL-',pe.employee_no),'Dummy payslip Juni 2026.','Nadia','Nadia',NOW()
  FROM erp_payroll_process p
  JOIN erp_payroll_process_employee pe ON pe.payroll_process_id=p.id
 WHERE p.payroll_run_no='PRUN-2026-06-MON';

INSERT INTO erp_payslip_detail
(payslip_id,line_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no)
SELECT ps.id,r.sequence_no,r.component_code,r.component_name,r.wage_type_code,r.component_type,r.quantity,r.rate,r.amount,r.currency,r.taxable,r.sequence_no
  FROM erp_payslip ps
  JOIN erp_payroll_process_result r
    ON r.payroll_process_id=ps.payroll_process_id
   AND r.employee_id=ps.employee_id
 WHERE ps.payslip_no LIKE 'PSL-2026-06-%'
   AND r.payslip_display='Y';
