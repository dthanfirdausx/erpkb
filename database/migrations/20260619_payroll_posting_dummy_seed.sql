DELETE FROM jurnal_detail
 WHERE id_header IN (SELECT id FROM jurnal_header WHERE source_module IN ('PAYROLL_POSTING','PAYROLL_POSTING_REVERSAL') AND source_document_no LIKE 'PYP-2026-%');
DELETE FROM jurnal_header WHERE source_module IN ('PAYROLL_POSTING','PAYROLL_POSTING_REVERSAL') AND source_document_no LIKE 'PYP-2026-%';
DELETE FROM erp_payroll_posting WHERE posting_no LIKE 'PYP-2026-%';

INSERT INTO erp_payroll_posting
(posting_no,payroll_process_id,payroll_run_no,posting_date,document_date,fiscal_year,fiscal_period,document_type,posting_variant,posting_status,payroll_area,total_employee,gross_amount,deduction_amount,tax_amount,net_amount,currency,salary_expense_account,payroll_payable_account,tax_payable_account,deduction_payable_account,external_reference,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'PYP-2026-06-MON',p.id,p.payroll_run_no,'2026-06-30','2026-06-30',2026,6,'PY','SUMMARY','READY',p.payroll_area,p.total_employee,p.total_gross,p.total_deduction,p.total_tax,p.total_net,p.currency,'51301','21199','21801','21199','PAYROLL-JUN-2026','SAP-FI-PY-2026-06-MON','Ready posting payroll bulanan Juni 2026.', 'Nadia','Nadia',NOW()
  FROM erp_payroll_process p WHERE p.payroll_run_no='PRUN-2026-06-MON';

INSERT INTO erp_payroll_posting
(posting_no,payroll_process_id,payroll_run_no,posting_date,document_date,fiscal_year,fiscal_period,document_type,posting_variant,posting_status,payroll_area,total_employee,gross_amount,deduction_amount,tax_amount,net_amount,currency,salary_expense_account,payroll_payable_account,tax_payable_account,deduction_payable_account,external_reference,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'PYP-2026-06-DLY',p.id,p.payroll_run_no,'2026-06-30','2026-06-30',2026,6,'PY','BY_COST_CENTER','DRAFT',p.payroll_area,p.total_employee,p.total_gross,p.total_deduction,p.total_tax,p.total_net,p.currency,'51301','21199','21801','21199','PAYROLL-DLY-JUN-2026','SAP-FI-PY-2026-06-DLY','Draft posting payroll harian Juni 2026.', 'Tinna','Tinna',NOW()
  FROM erp_payroll_process p WHERE p.payroll_run_no='PRUN-2026-06-DLY';

INSERT INTO erp_payroll_posting_line
(payroll_posting_id,line_no,account_no,account_name,posting_key,line_text,amount,currency,source_amount_type)
SELECT pp.id,10,pp.salary_expense_account,r.nama_rek,'DEBIT',CONCAT('Payroll gross expense ',pp.payroll_run_no),pp.gross_amount,pp.currency,'GROSS'
  FROM erp_payroll_posting pp LEFT JOIN rekening r ON r.no_rek=pp.salary_expense_account
 WHERE pp.posting_no LIKE 'PYP-2026-%';

INSERT INTO erp_payroll_posting_line
(payroll_posting_id,line_no,account_no,account_name,posting_key,line_text,amount,currency,source_amount_type)
SELECT pp.id,20,pp.payroll_payable_account,r.nama_rek,'CREDIT',CONCAT('Payroll net payable ',pp.payroll_run_no),pp.net_amount,pp.currency,'NET'
  FROM erp_payroll_posting pp LEFT JOIN rekening r ON r.no_rek=pp.payroll_payable_account
 WHERE pp.posting_no LIKE 'PYP-2026-%';

INSERT INTO erp_payroll_posting_line
(payroll_posting_id,line_no,account_no,account_name,posting_key,line_text,amount,currency,source_amount_type)
SELECT pp.id,30,pp.tax_payable_account,r.nama_rek,'CREDIT',CONCAT('Payroll tax payable ',pp.payroll_run_no),pp.tax_amount,pp.currency,'TAX'
  FROM erp_payroll_posting pp LEFT JOIN rekening r ON r.no_rek=pp.tax_payable_account
 WHERE pp.posting_no LIKE 'PYP-2026-%' AND pp.tax_amount>0;

INSERT INTO erp_payroll_posting_line
(payroll_posting_id,line_no,account_no,account_name,posting_key,line_text,amount,currency,source_amount_type)
SELECT pp.id,40,pp.deduction_payable_account,r.nama_rek,'CREDIT',CONCAT('Payroll deduction payable ',pp.payroll_run_no),pp.deduction_amount,pp.currency,'DEDUCTION'
  FROM erp_payroll_posting pp LEFT JOIN rekening r ON r.no_rek=pp.deduction_payable_account
 WHERE pp.posting_no LIKE 'PYP-2026-%' AND pp.deduction_amount>0;
