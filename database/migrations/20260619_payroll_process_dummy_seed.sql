DELETE FROM erp_payroll_process WHERE payroll_run_no LIKE 'PRUN-2026-%';

INSERT INTO erp_payroll_process
(payroll_run_no,period_year,period_month,period_from,period_to,pay_date,payroll_area,process_type,run_mode,control_record_status,process_status,total_employee,total_gross,total_deduction,total_tax,total_net,currency,posting_reference,sap_reference,remarks,created_by,updated_by,updated_at)
VALUES
('PRUN-2026-06-MON','2026','6','2026-06-01','2026-06-30','2026-06-30','MONTHLY','REGULAR','LIVE','RELEASED','CALCULATED',0,0,0,0,0,'IDR','PAYPOST-2026-06-MON','SAP-PY-2026-06-MON','Dummy payroll run bulanan Juni 2026.', 'Nadia','Nadia',NOW()),
('PRUN-2026-06-DLY','2026','6','2026-06-01','2026-06-30','2026-06-30','DAILY','REGULAR','SIMULATION','OPEN','DRAFT',0,0,0,0,0,'IDR',NULL,'SAP-PY-2026-06-DLY','Dummy payroll run harian simulasi.', 'Tinna','Tinna',NOW());

INSERT INTO erp_payroll_process_employee
(payroll_process_id,employee_id,employee_no,full_name,department_code,employee_group,payroll_area,salary_structure_id,salary_structure_code,cost_center_code,profit_center_code,working_days,paid_days,absence_days,overtime_hours,process_status)
SELECT p.id,e.id,e.employee_no,e.full_name,e.department_code,e.employee_group,e.payroll_area,s.id,s.structure_code,e.cost_center_code,e.profit_center_code,22,22,0,8,'CALCULATED'
  FROM erp_payroll_process p
  JOIN erp_employee_master e ON e.employee_no IN ('EMP-0006','EMP-0009','EMP-0011')
  JOIN erp_salary_structure s ON s.structure_code='SS-STF-G07-L2'
 WHERE p.payroll_run_no='PRUN-2026-06-MON';

INSERT INTO erp_payroll_process_employee
(payroll_process_id,employee_id,employee_no,full_name,department_code,employee_group,payroll_area,salary_structure_id,salary_structure_code,cost_center_code,profit_center_code,working_days,paid_days,absence_days,overtime_hours,process_status)
SELECT p.id,e.id,e.employee_no,e.full_name,e.department_code,e.employee_group,e.payroll_area,s.id,s.structure_code,e.cost_center_code,e.profit_center_code,24,24,0,12,'DRAFT'
  FROM erp_payroll_process p
  JOIN erp_employee_master e ON e.employee_no IN ('EMP-0007','EMP-0008')
  JOIN erp_salary_structure s ON s.structure_code='SS-DLY-G02-L1'
 WHERE p.payroll_run_no='PRUN-2026-06-DLY';

INSERT INTO erp_payroll_process_result
(payroll_process_id,payroll_employee_id,employee_id,component_code,component_name,wage_type_code,component_type,calculation_method,base_amount,rate,quantity,amount,currency,taxable,payslip_display,sequence_no,formula_text)
SELECT pe.payroll_process_id,pe.id,pe.employee_id,d.component_code,pc.component_name,pc.wage_type_code,pc.component_type,d.calculation_method,d.amount,d.percentage_rate,1,
       CASE
         WHEN pc.component_type='DEDUCTION' THEN ABS(IF(d.amount>0,d.amount,(11000000*d.percentage_rate/100)))
         WHEN pc.component_type='TAX' THEN 350000
         WHEN d.calculation_method='ATTENDANCE_BASED' THEN d.amount*pe.paid_days
         WHEN d.calculation_method='PERCENTAGE' THEN 11000000*d.percentage_rate/100
         ELSE d.amount
       END,
       'IDR',d.taxable,d.payslip_display,d.sequence_no,d.formula_text
  FROM erp_payroll_process_employee pe
  JOIN erp_salary_structure_detail d ON d.structure_id=pe.salary_structure_id
  JOIN erp_payroll_component pc ON pc.component_code=d.component_code;

UPDATE erp_payroll_process_employee pe
JOIN (
  SELECT payroll_employee_id,
         SUM(CASE WHEN component_type IN ('EARNING','BENEFIT') THEN amount ELSE 0 END) earning,
         SUM(CASE WHEN component_type='DEDUCTION' THEN amount ELSE 0 END) deduction,
         SUM(CASE WHEN component_type='TAX' THEN amount ELSE 0 END) tax
    FROM erp_payroll_process_result
   GROUP BY payroll_employee_id
) x ON x.payroll_employee_id=pe.id
SET pe.total_earning=x.earning,
    pe.gross_pay=x.earning,
    pe.total_deduction=x.deduction,
    pe.tax_amount=x.tax,
    pe.net_pay=x.earning-x.deduction-x.tax;

UPDATE erp_payroll_process p
JOIN (
  SELECT payroll_process_id,COUNT(*) total_employee,SUM(gross_pay) gross,SUM(total_deduction) deduction,SUM(tax_amount) tax,SUM(net_pay) net
    FROM erp_payroll_process_employee
   GROUP BY payroll_process_id
) x ON x.payroll_process_id=p.id
SET p.total_employee=x.total_employee,
    p.total_gross=x.gross,
    p.total_deduction=x.deduction,
    p.total_tax=x.tax,
    p.total_net=x.net;
