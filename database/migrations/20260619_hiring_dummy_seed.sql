DELETE FROM erp_hiring WHERE hiring_no LIKE 'HIR-2026-%';

UPDATE erp_applicant_data
   SET applicant_status='INTERVIEW',
       updated_by='admin',
       updated_at=NOW()
 WHERE applicant_no IN ('APP-2026-002','APP-2026-003')
   AND applicant_status='OFFER';

INSERT INTO erp_hiring
(hiring_no,selection_result_id,applicant_id,vacancy_id,hiring_date,planned_join_date,actual_join_date,hiring_status,offer_status,onboarding_status,contract_status,document_status,medical_check_status,background_check_status,hiring_type,employment_type,employee_group,proposed_position_id,proposed_job_title_id,department_code,company_structure_id,work_location_id,cost_center_code,profit_center_code,pay_grade,salary_amount,currency,probation_months,recruiter_employee_id,hiring_manager_employee_id,hr_pic_employee_id,offer_sent_date,offer_accepted_date,contract_signed_date,onboarding_start_date,onboarding_completed_date,checklist_total,checklist_done,offer_notes,onboarding_notes,hr_notes,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'HIR-2026-001',s.id,a.id,v.id,'2026-06-30',s.proposed_join_date,NULL,'OFFER_ACCEPTED','ACCEPTED','IN_PROGRESS','SIGNED','COMPLETE','PASSED','PASSED','NEW_HIRE',v.employment_type,v.employee_group,s.proposed_position_id,s.proposed_job_title_id,v.department_code,v.company_structure_id,v.work_location_id,v.cost_center_code,v.profit_center_code,s.proposed_grade,s.proposed_salary,s.currency,3,v.recruiter_employee_id,v.hiring_manager_employee_id,v.recruiter_employee_id,'2026-06-30','2026-07-02','2026-07-04','2026-07-08',NULL,8,5,'Offer diterima kandidat.','Laptop, akses sistem, dan induction masih berjalan.','Siapkan employee master saat join date.','SAP-HCM-HIR-2026-001','Hiring PPIC Supervisor dari selection result.', 'admin','admin',NOW()
  FROM erp_selection_result s
  JOIN erp_applicant_data a ON a.id=s.applicant_id
  LEFT JOIN erp_job_vacancy v ON v.id=s.vacancy_id
 WHERE s.selection_no='SEL-2026-001'
 LIMIT 1;

UPDATE erp_applicant_data a
JOIN erp_hiring h ON h.applicant_id=a.id
   SET a.applicant_status=CASE WHEN h.hiring_status IN ('HIRED','ONBOARDED') THEN 'HIRED' ELSE 'OFFER' END,
       a.updated_by='admin',
       a.updated_at=NOW()
 WHERE h.hiring_no LIKE 'HIR-2026-%'
   AND h.hiring_status NOT IN ('CANCELLED','DECLINED');
