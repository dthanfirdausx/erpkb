DELETE FROM erp_interview_schedule_panel
 WHERE interview_id IN (SELECT id FROM erp_interview_schedule WHERE interview_no LIKE 'INT-2026-%');
DELETE FROM erp_interview_schedule WHERE interview_no LIKE 'INT-2026-%';

INSERT INTO erp_interview_schedule
(interview_no,applicant_id,vacancy_id,interview_round,interview_type,interview_method,interview_status,schedule_date,start_time,end_time,timezone,location,meeting_link,
 recruiter_employee_id,primary_interviewer_employee_id,hr_interviewer_employee_id,technical_interviewer_employee_id,hiring_manager_employee_id,
 confirmation_sent,confirmation_sent_at,applicant_confirmed,applicant_confirmed_at,overall_score,recommendation,result_notes,agenda,preparation_notes,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'INT-2026-001',a.id,a.vacancy_id,1,'TECHNICAL','ONSITE','COMPLETED','2026-06-28','09:00:00','10:00:00','Asia/Jakarta','Meeting Room HR 1','',
       a.recruiter_employee_id,
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       a.recruiter_employee_id,
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       'Y','2026-06-26 14:00:00','Y','2026-06-26 16:30:00',78,'PASS',
       'Kandidat cukup kuat secara teknis, lanjut final user interview.',
       'Validasi PPIC process, MRP, production scheduling, leadership.',
       'Siapkan CV, hasil screening, dan job profile.',
       'SAP-HCM-INT-2026-001','Interview teknis PPIC Supervisor.','admin','admin',NOW()
  FROM erp_applicant_data a WHERE a.applicant_no='APP-2026-001' LIMIT 1;

INSERT INTO erp_interview_schedule
(interview_no,applicant_id,vacancy_id,interview_round,interview_type,interview_method,interview_status,schedule_date,start_time,end_time,timezone,location,meeting_link,
 recruiter_employee_id,primary_interviewer_employee_id,hr_interviewer_employee_id,technical_interviewer_employee_id,hiring_manager_employee_id,
 confirmation_sent,applicant_confirmed,overall_score,recommendation,agenda,preparation_notes,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'INT-2026-002',a.id,a.vacancy_id,1,'HR','ONLINE','CONFIRMED','2026-07-02','13:30:00','14:15:00','Asia/Jakarta','','https://meet.example.com/int-2026-002',
       a.recruiter_employee_id,a.recruiter_employee_id,a.recruiter_employee_id,NULL,
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       'Y','Y',0,'PENDING','HR interview dan validasi shift readiness.','Kirim reminder H-1.',
       'SAP-HCM-INT-2026-002','Online interview kandidat coating operator.','admin','admin',NOW()
  FROM erp_applicant_data a WHERE a.applicant_no='APP-2026-002' LIMIT 1;

INSERT INTO erp_interview_schedule
(interview_no,applicant_id,vacancy_id,interview_round,interview_type,interview_method,interview_status,schedule_date,start_time,end_time,timezone,location,meeting_link,
 recruiter_employee_id,primary_interviewer_employee_id,hr_interviewer_employee_id,technical_interviewer_employee_id,hiring_manager_employee_id,
 confirmation_sent,applicant_confirmed,overall_score,recommendation,agenda,preparation_notes,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'INT-2026-003',a.id,a.vacancy_id,1,'PANEL','ONSITE','SCHEDULED','2026-07-05','10:00:00','11:00:00','Asia/Jakarta','Meeting Room Production 2','',
       a.recruiter_employee_id,
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       a.recruiter_employee_id,
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       (SELECT id FROM erp_employee_master WHERE department_code='DEP-PRD' ORDER BY id LIMIT 1),
       'N','N',0,'PENDING','Panel interview coating operator.','Konfirmasi ke user produksi.',
       'SAP-HCM-INT-2026-003','Panel interview kandidat coating.','admin','admin',NOW()
  FROM erp_applicant_data a WHERE a.applicant_no='APP-2026-003' LIMIT 1;

INSERT INTO erp_interview_schedule_panel (interview_id,line_no,interviewer_employee_id,interviewer_role,attendance_status,score,feedback)
SELECT i.id,1,i.hr_interviewer_employee_id,'HR','ATTENDED',80,'Komunikasi baik dan ekspektasi sesuai.' FROM erp_interview_schedule i WHERE i.interview_no='INT-2026-001' AND i.hr_interviewer_employee_id IS NOT NULL;
INSERT INTO erp_interview_schedule_panel (interview_id,line_no,interviewer_employee_id,interviewer_role,attendance_status,score,feedback)
SELECT i.id,2,i.technical_interviewer_employee_id,'TECHNICAL','ATTENDED',76,'Pemahaman MRP cukup kuat, perlu adaptasi sistem internal.' FROM erp_interview_schedule i WHERE i.interview_no='INT-2026-001' AND i.technical_interviewer_employee_id IS NOT NULL;
INSERT INTO erp_interview_schedule_panel (interview_id,line_no,interviewer_employee_id,interviewer_role,attendance_status,score,feedback)
SELECT i.id,1,i.hr_interviewer_employee_id,'HR','CONFIRMED',0,'' FROM erp_interview_schedule i WHERE i.interview_no='INT-2026-002' AND i.hr_interviewer_employee_id IS NOT NULL;
INSERT INTO erp_interview_schedule_panel (interview_id,line_no,interviewer_employee_id,interviewer_role,attendance_status,score,feedback)
SELECT i.id,1,i.hr_interviewer_employee_id,'HR','INVITED',0,'' FROM erp_interview_schedule i WHERE i.interview_no='INT-2026-003' AND i.hr_interviewer_employee_id IS NOT NULL;
INSERT INTO erp_interview_schedule_panel (interview_id,line_no,interviewer_employee_id,interviewer_role,attendance_status,score,feedback)
SELECT i.id,2,i.technical_interviewer_employee_id,'TECHNICAL','INVITED',0,'' FROM erp_interview_schedule i WHERE i.interview_no='INT-2026-003' AND i.technical_interviewer_employee_id IS NOT NULL;

UPDATE erp_applicant_data a
JOIN erp_interview_schedule i ON i.applicant_id=a.id
   SET a.applicant_status=CASE WHEN a.applicant_status IN ('NEW','SCREENING','SHORTLISTED') THEN 'INTERVIEW' ELSE a.applicant_status END,
       a.interview_score=CASE WHEN i.overall_score>0 THEN i.overall_score ELSE a.interview_score END,
       a.final_score=CASE WHEN i.overall_score>0 THEN ROUND((a.screening_score+i.overall_score)/2,2) ELSE a.final_score END,
       a.interview_notes=COALESCE(NULLIF(i.result_notes,''),a.interview_notes),
       a.updated_by='admin',
       a.updated_at=NOW()
 WHERE i.interview_no LIKE 'INT-2026-%';
