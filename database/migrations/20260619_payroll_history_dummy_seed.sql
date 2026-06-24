DELETE FROM erp_payroll_history_detail
 WHERE payroll_history_id IN (SELECT id FROM erp_payroll_history WHERE history_no LIKE 'PH-2026-06-%');
DELETE FROM erp_payroll_history WHERE history_no LIKE 'PH-2026-06-%';

INSERT INTO erp_payroll_history
(history_no,payroll_process_id,payroll_employee_id,payslip_id,payroll_posting_id,payroll_run_no,payslip_no,posting_no,employee_id,employee_no,full_name,department_code,employee_group,payroll_area,period_year,period_month,period_from,period_to,pay_date,salary_structure_code,working_days,paid_days,absence_days,overtime_hours,gross_pay,total_earning,total_deduction,tax_amount,net_pay,currency,payroll_process_status,payslip_status,posting_status,history_status,audit_source,release_channel,released_at,journal_no,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT CONCAT('PH-2026-06-',pe.employee_no),
       p.id, pe.id, ps.id, pp.id,
       p.payroll_run_no, ps.payslip_no, pp.posting_no,
       pe.employee_id, pe.employee_no, pe.full_name, pe.department_code, pe.employee_group, pe.payroll_area,
       p.period_year, p.period_month, p.period_from, p.period_to, p.pay_date,
       pe.salary_structure_code, pe.working_days, pe.paid_days, pe.absence_days, pe.overtime_hours,
       pe.gross_pay, pe.total_earning, pe.total_deduction, pe.tax_amount, pe.net_pay, p.currency,
       pe.process_status, COALESCE(ps.payslip_status,'NOT_GENERATED'), COALESCE(pp.posting_status,'NOT_CREATED'),
       CASE WHEN ps.payslip_status='RELEASED' THEN 'LOCKED' ELSE 'ACTIVE' END,
       'AUTO_SNAPSHOT', ps.release_channel, ps.released_at,
       CASE WHEN pp.journal_header_id IS NULL THEN NULL ELSE CONCAT('JRN-', pp.journal_header_id) END,
       CONCAT('SAP-HCM-HIST-',p.period_year,'-',LPAD(p.period_month,2,'0'),'-',pe.employee_no),
       CONCAT('Dummy payroll history Juni 2026 - ',p.payroll_run_no,'.'),
       CASE WHEN pe.payroll_area='DAILY' THEN 'Tinna' ELSE 'Nadia' END,
       CASE WHEN pe.payroll_area='DAILY' THEN 'Tinna' ELSE 'Nadia' END,
       NOW()
  FROM erp_payroll_process_employee pe
  JOIN erp_payroll_process p ON p.id=pe.payroll_process_id
  LEFT JOIN erp_payslip ps ON ps.payroll_process_id=pe.payroll_process_id AND ps.employee_id=pe.employee_id
  LEFT JOIN erp_payroll_posting pp ON pp.payroll_process_id=p.id
 WHERE p.payroll_run_no IN ('PRUN-2026-06-MON','PRUN-2026-06-DLY');

INSERT INTO erp_payroll_history_detail
(payroll_history_id,line_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no)
SELECT ph.id, pd.line_no, pd.component_code, pd.component_name, pd.wage_type_code, pd.component_type,
       pd.quantity, pd.rate, pd.amount, pd.currency, pd.taxable, pd.sequence_no
  FROM erp_payroll_history ph
  JOIN erp_payslip_detail pd ON pd.payslip_id=ph.payslip_id
 WHERE ph.history_no LIKE 'PH-2026-06-%'
   AND ph.payslip_id IS NOT NULL;

INSERT INTO erp_payroll_history_detail
(payroll_history_id,line_no,component_code,component_name,wage_type_code,component_type,quantity,rate,amount,currency,taxable,sequence_no)
SELECT ph.id, r.sequence_no, r.component_code, r.component_name, r.wage_type_code, r.component_type,
       r.quantity, r.rate, r.amount, r.currency, r.taxable, r.sequence_no
  FROM erp_payroll_history ph
  JOIN erp_payroll_process_result r ON r.payroll_employee_id=ph.payroll_employee_id
 WHERE ph.history_no LIKE 'PH-2026-06-%'
   AND ph.payslip_id IS NULL
   AND r.payslip_display='Y';
