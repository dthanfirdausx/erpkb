DELETE FROM erp_leave_request
 WHERE leave_no IN ('LVR-DUMMY-001','LVR-DUMMY-002','LVR-DUMMY-003','LVR-DUMMY-004');

INSERT INTO erp_leave_request (leave_no,employee_id,department_code,job_title_id,leave_type,request_date,start_date,end_date,start_half_day,end_half_day,total_days,leave_quota_before,leave_quota_after,reason,handover_to_employee_id,approver_employee_id,hr_reviewer_employee_id,workflow_status,approval_level,decision,sap_reference,remarks,created_by,created_at,updated_by,updated_at)
SELECT 'LVR-DUMMY-001',e.id,e.department_code,e.job_title_id,'ANNUAL_LEAVE','2026-06-03','2026-06-10','2026-06-12','FULL_DAY','FULL_DAY',3.00,12.00,9.00,'Cuti tahunan keluarga.',h.id,e.manager_employee_id,hr.id,'SUBMITTED','MANAGER','PENDING','SAP-LVR-001','Dummy annual leave.', 'admin','2026-06-03 09:00:00','admin','2026-06-03 09:00:00'
  FROM erp_employee_master e JOIN erp_employee_master h ON h.employee_no='EMP-0008' JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0007';

INSERT INTO erp_leave_request (leave_no,employee_id,department_code,job_title_id,leave_type,request_date,start_date,end_date,start_half_day,end_half_day,total_days,leave_quota_before,leave_quota_after,reason,attachment_ref,handover_to_employee_id,approver_employee_id,hr_reviewer_employee_id,workflow_status,approval_level,decision,decision_by,decision_at,approver_note,sap_reference,remarks,created_by,created_at,updated_by,updated_at)
SELECT 'LVR-DUMMY-002',e.id,e.department_code,e.job_title_id,'SICK_LEAVE','2026-06-05','2026-06-05','2026-06-05','FULL_DAY','FULL_DAY',1.00,12.00,12.00,'Sakit dan melampirkan surat dokter.','MC-20260605.pdf',h.id,e.manager_employee_id,hr.id,'APPROVED','FINAL','APPROVED','admin','2026-06-05 15:30:00','Disetujui sesuai dokumen medis.','SAP-LVR-002','Dummy sick leave approved.', 'admin','2026-06-05 08:30:00','admin','2026-06-05 15:30:00'
  FROM erp_employee_master e JOIN erp_employee_master h ON h.employee_no='EMP-0009' JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0011';

INSERT INTO erp_leave_request (leave_no,employee_id,department_code,job_title_id,leave_type,request_date,start_date,end_date,start_half_day,end_half_day,total_days,leave_quota_before,leave_quota_after,reason,handover_to_employee_id,approver_employee_id,hr_reviewer_employee_id,workflow_status,approval_level,decision,sap_reference,remarks,created_by,created_at,updated_by,updated_at)
SELECT 'LVR-DUMMY-003',e.id,e.department_code,e.job_title_id,'PERMISSION','2026-06-07','2026-06-14','2026-06-14','AM','AM',0.50,10.00,9.50,'Izin setengah hari urusan administrasi keluarga.',NULL,e.manager_employee_id,hr.id,'MANAGER_APPROVED','HR','PENDING','SAP-LVR-003','Dummy half day permission.', 'admin','2026-06-07 10:15:00','admin','2026-06-07 11:30:00'
  FROM erp_employee_master e JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0009';

INSERT INTO erp_leave_request (leave_no,employee_id,department_code,job_title_id,leave_type,request_date,start_date,end_date,start_half_day,end_half_day,total_days,leave_quota_before,leave_quota_after,reason,handover_to_employee_id,approver_employee_id,hr_reviewer_employee_id,workflow_status,approval_level,decision,approver_note,sap_reference,remarks,created_by,created_at,updated_by,updated_at)
SELECT 'LVR-DUMMY-004',e.id,e.department_code,e.job_title_id,'UNPAID_LEAVE','2026-06-08','2026-06-20','2026-06-21','FULL_DAY','FULL_DAY',2.00,0.00,0.00,'Pengajuan unpaid leave untuk keperluan pribadi.',h.id,e.manager_employee_id,hr.id,'RETURNED','FINAL','RETURNED','Perlu klarifikasi tanggal dan handover pekerjaan.','SAP-LVR-004','Dummy returned unpaid leave.', 'admin','2026-06-08 13:00:00','admin','2026-06-08 16:00:00'
  FROM erp_employee_master e JOIN erp_employee_master h ON h.employee_no='EMP-0012' JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0002';
