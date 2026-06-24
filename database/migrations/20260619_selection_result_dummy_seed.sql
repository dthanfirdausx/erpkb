DELETE FROM erp_selection_result WHERE selection_no LIKE 'SEL-2026-%';

INSERT INTO erp_selection_result
(selection_no,applicant_id,vacancy_id,final_interview_id,selection_date,selection_stage,selection_status,decision_result,ranking_no,
 screening_score,interview_score,assessment_score,overall_score,recommendation,proposed_position_id,proposed_job_title_id,proposed_grade,proposed_salary,currency,proposed_join_date,
 approved_by_employee_id,approved_at,rejected_reason,hold_reason,selection_committee_notes,hr_notes,user_notes,next_action,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'SEL-2026-001',a.id,a.vacancy_id,i.id,'2026-06-29','FINAL','APPROVED','SELECTED',1,
       a.screening_score,a.interview_score,82,ROUND((a.screening_score+a.interview_score+82)/3,2),'HIRE',
       v.position_id,v.job_title_id,v.pay_grade,9000000,'IDR','2026-08-01',
       v.hiring_manager_employee_id,'2026-06-29 15:00:00','','',
       'Kandidat memenuhi requirement PPIC dan direkomendasikan lanjut offering.',
       'Salary masih dalam range vacancy.',
       'User menyetujui kandidat dengan catatan adaptasi sistem internal.',
       'CREATE_OFFER','SAP-HCM-SEL-2026-001','Keputusan final PPIC Supervisor.','admin','admin',NOW()
  FROM erp_applicant_data a
  JOIN erp_job_vacancy v ON v.id=a.vacancy_id
  LEFT JOIN erp_interview_schedule i ON i.applicant_id=a.id AND i.interview_status='COMPLETED'
 WHERE a.applicant_no='APP-2026-001'
 LIMIT 1;

INSERT INTO erp_selection_result
(selection_no,applicant_id,vacancy_id,final_interview_id,selection_date,selection_stage,selection_status,decision_result,ranking_no,
 screening_score,interview_score,assessment_score,overall_score,recommendation,proposed_position_id,proposed_job_title_id,proposed_grade,proposed_salary,currency,proposed_join_date,
 approved_by_employee_id,rejected_reason,hold_reason,selection_committee_notes,hr_notes,user_notes,next_action,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'SEL-2026-002',a.id,a.vacancy_id,i.id,'2026-07-02','INTERVIEW','SUBMITTED','HOLD',2,
       a.screening_score,a.interview_score,70,ROUND((a.screening_score+70)/2,2),'HOLD',
       v.position_id,v.job_title_id,v.pay_grade,5400000,'IDR','2026-07-25',
       v.hiring_manager_employee_id,'','Menunggu pembanding kandidat batch berikutnya.',
       'Kandidat potensial namun perlu validasi shift dan referensi kerja.',
       'HR menunggu feedback user produksi.',
       'User belum final memilih.',
       'KEEP_TALENT_POOL','SAP-HCM-SEL-2026-002','Hold untuk coating operator.','admin','admin',NOW()
  FROM erp_applicant_data a
  JOIN erp_job_vacancy v ON v.id=a.vacancy_id
  LEFT JOIN erp_interview_schedule i ON i.applicant_id=a.id
 WHERE a.applicant_no='APP-2026-002'
 LIMIT 1;

INSERT INTO erp_selection_result
(selection_no,applicant_id,vacancy_id,final_interview_id,selection_date,selection_stage,selection_status,decision_result,ranking_no,
 screening_score,interview_score,assessment_score,overall_score,recommendation,proposed_position_id,proposed_job_title_id,proposed_grade,proposed_salary,currency,proposed_join_date,
 approved_by_employee_id,rejected_reason,hold_reason,selection_committee_notes,hr_notes,user_notes,next_action,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'SEL-2026-003',a.id,a.vacancy_id,i.id,'2026-07-05','INTERVIEW','DRAFT','REINTERVIEW',3,
       a.screening_score,a.interview_score,0,a.final_score,'REINTERVIEW',
       v.position_id,v.job_title_id,v.pay_grade,0,'IDR',NULL,
       v.hiring_manager_employee_id,'','','Butuh reinterview technical karena data pengalaman belum cukup.',
       'Schedule ulang interview dengan user produksi.',
       'Belum ada keputusan final.',
       'SCHEDULE_REINTERVIEW','SAP-HCM-SEL-2026-003','Draft selection result coating operator.','admin','admin',NOW()
  FROM erp_applicant_data a
  JOIN erp_job_vacancy v ON v.id=a.vacancy_id
  LEFT JOIN erp_interview_schedule i ON i.applicant_id=a.id
 WHERE a.applicant_no='APP-2026-003'
 LIMIT 1;

UPDATE erp_applicant_data a
JOIN erp_selection_result s ON s.applicant_id=a.id
   SET a.applicant_status=CASE
         WHEN s.decision_result='SELECTED' THEN 'OFFER'
         WHEN s.decision_result='REJECTED' THEN 'REJECTED'
         WHEN s.decision_result IN ('HOLD','WAITING_LIST','REINTERVIEW') THEN 'INTERVIEW'
         ELSE a.applicant_status END,
       a.final_score=CASE WHEN s.overall_score>0 THEN s.overall_score ELSE a.final_score END,
       a.rejection_reason=CASE WHEN s.decision_result='REJECTED' THEN s.rejected_reason ELSE a.rejection_reason END,
       a.updated_by='admin',
       a.updated_at=NOW()
 WHERE s.selection_no LIKE 'SEL-2026-%';
