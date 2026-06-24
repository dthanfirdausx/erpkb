DELETE FROM erp_applicant_data WHERE applicant_no LIKE 'APP-2026-%';

INSERT INTO erp_applicant_data
(applicant_no,vacancy_id,applicant_name,gender,birth_place,birth_date,nationality,identity_type,identity_no,email,phone,address,city,postal_code,
 education_level,major,university,graduation_year,gpa,current_company,current_position,years_experience,expected_salary,currency,source_channel,referred_by_employee_id,
 application_date,available_start_date,applicant_status,screening_score,interview_score,final_score,recruiter_employee_id,cv_reference,portfolio_url,linkedin_url,skills,
 screening_notes,interview_notes,rejection_reason,hired_employee_id,sap_reference,remarks,created_by,updated_by,updated_at)
VALUES
('APP-2026-001',(SELECT id FROM erp_job_vacancy WHERE vacancy_no='JV-2026-001' LIMIT 1),'Rizky Pratama','MALE','Bandung','1993-04-12','ID','KTP','3273011204930001','rizky.pratama@example.com','081234560001','Jl. Cihampelas No. 12','Bandung','40131',
 'S1','Industrial Engineering','Universitas Pasundan',2015,3.42,'PT Manufaktur Nusantara','PPIC Senior Staff',6.50,8500000,'IDR','LinkedIn',NULL,
 '2026-06-16','2026-08-01','INTERVIEW',82,78,80,(SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),'upload/applicant_cv/rizky_pratama.pdf','https://portfolio.example.com/rizky','https://linkedin.com/in/rizky-pratama','MRP, production scheduling, inventory control, SAP PP',
 'Match dengan requirement PPIC, pengalaman cukup kuat.','Interview teknis baik, perlu validasi leadership.','',NULL,'SAP-HCM-APP-2026-001','Kandidat utama untuk PPIC Supervisor.','admin','admin',NOW()),
('APP-2026-002',(SELECT id FROM erp_job_vacancy WHERE vacancy_no='JV-2026-002' LIMIT 1),'Siti Nurhaliza','FEMALE','Pemalang','1998-09-21','ID','KTP','3327112109980002','siti.nurhaliza@example.com','081234560002','Jl. Gatot Subroto No. 8','Pemalang','52312',
 'SMA_SMK','Teknik Mesin','SMK Negeri 1 Pemalang',2016,3.10,'PT Plastik Sejahtera','Operator Produksi',4.00,5200000,'IDR','Job Portal',NULL,
 '2026-06-21','2026-07-20','SHORTLISTED',76,0,76,(SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),'upload/applicant_cv/siti_nurhaliza.pdf','','','machine operation, coating, 5S, shift work',
 'Pengalaman operator cocok untuk coating line.','','',NULL,'SAP-HCM-APP-2026-002','Masuk shortlist batch pertama.','admin','admin',NOW()),
('APP-2026-003',(SELECT id FROM erp_job_vacancy WHERE vacancy_no='JV-2026-002' LIMIT 1),'Budi Santoso','MALE','Tegal','1997-01-07','ID','KTP','3328110701970003','budi.santoso@example.com','081234560003','Jl. Merdeka No. 22','Tegal','52111',
 'DIPLOMA','Teknik Industri','Politeknik Harapan Bersama',2018,3.25,'PT Coating Prima','Production Operator',5.00,5600000,'IDR','Walk-in',NULL,
 '2026-06-22','2026-07-15','SCREENING',68,0,68,(SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),'upload/applicant_cv/budi_santoso.pdf','','','coating machine, QC basic, safety',
 'Perlu cek stability kerja dan referensi.','','',NULL,'SAP-HCM-APP-2026-003','Candidate masih screening.','admin','admin',NOW()),
('APP-2026-004',(SELECT id FROM erp_job_vacancy WHERE vacancy_no='JV-2026-003' LIMIT 1),'Maya Lestari','FEMALE','Jakarta','1990-11-19','ID','KTP','3174011911900004','maya.lestari@example.com','081234560004','Jl. TB Simatupang No. 5','Jakarta','12540',
 'S1','Marketing Management','Universitas Indonesia',2012,3.55,'PT Global Salesindo','Sales Manager',9.00,14000000,'IDR','Internal Referral',(SELECT id FROM erp_employee_master WHERE employee_no='EMP-0001' LIMIT 1),
 '2026-06-25','2026-09-01','NEW',0,0,0,(SELECT id FROM erp_employee_master WHERE department_code='DEP-HR' ORDER BY id LIMIT 1),'upload/applicant_cv/maya_lestari.pdf','','https://linkedin.com/in/maya-lestari','B2B sales, key account, negotiation, forecast',
 'Belum mulai screening.','','',NULL,'SAP-HCM-APP-2026-004','Referral untuk vacancy sales manager.','admin','admin',NOW());

UPDATE erp_job_vacancy v
   SET applicant_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id),
       shortlisted_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status IN ('SHORTLISTED','INTERVIEW','OFFER','HIRED')),
       interview_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status IN ('INTERVIEW','OFFER','HIRED')),
       offer_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status IN ('OFFER','HIRED')),
       hired_count=(SELECT COUNT(*) FROM erp_applicant_data a WHERE a.vacancy_id=v.id AND a.applicant_status='HIRED'),
       updated_by='admin',
       updated_at=NOW()
 WHERE v.vacancy_no LIKE 'JV-2026-%';
