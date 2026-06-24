START TRANSACTION;

DELETE FROM erp_overtime
 WHERE overtime_no IN ('OT-20260617-0001','OT-20260617-0002','OT-20260618-0001','OT-20260619-0001','OT-20260619-0002','OT-20260620-0001');

INSERT INTO erp_overtime (overtime_no,employee_id,employee_no,department_code,cost_center_code,attendance_id,attendance_no,shift_schedule_id,assignment_no,overtime_date,planned_start,planned_end,actual_start,actual_end,requested_hours,approved_hours,payable_hours,rate_multiplier,hourly_rate,estimated_amount,overtime_type,overtime_reason,request_source,overtime_status,requested_by,requested_at,approved_by,approved_at,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'OT-20260617-0001',a.employee_id,a.employee_no,a.department_code,'1000-QA',a.id,a.attendance_no,a.shift_schedule_id,a.assignment_no,a.attendance_date,a.planned_end,DATE_ADD(a.planned_end,INTERVAL 2 HOUR),a.planned_end,a.actual_clock_out,a.overtime_hours,a.overtime_hours,a.overtime_hours,1.50,50000,(a.overtime_hours*1.50*50000),'REGULAR_OT','Final inspection urgent order','ATTENDANCE','APPROVED','Siti Aminah',NOW(),'Siti Aminah',NOW(),'SAP-OT-0001','Overtime dari attendance QA.', 'Siti Aminah','Siti Aminah',NOW()
  FROM erp_attendance a WHERE a.attendance_no='ATT-20260617-0004' LIMIT 1;

INSERT INTO erp_overtime (overtime_no,employee_id,employee_no,department_code,cost_center_code,attendance_id,attendance_no,shift_schedule_id,assignment_no,overtime_date,planned_start,planned_end,actual_start,actual_end,requested_hours,approved_hours,payable_hours,rate_multiplier,hourly_rate,estimated_amount,overtime_type,overtime_reason,request_source,overtime_status,requested_by,requested_at,approved_by,approved_at,posted_by,posted_at,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'OT-20260617-0002',a.employee_id,a.employee_no,a.department_code,'1000-FIN',a.id,a.attendance_no,a.shift_schedule_id,a.assignment_no,a.attendance_date,a.planned_end,DATE_ADD(a.planned_end,INTERVAL 1 HOUR),a.planned_end,a.actual_clock_out,a.overtime_hours,a.overtime_hours,a.overtime_hours,1.50,65000,(a.overtime_hours*1.50*65000),'REGULAR_OT','Month end finance support','ATTENDANCE','POSTED','Nadia',NOW(),'Nadia',NOW(),'Nadia',NOW(),'SAP-OT-0002','Sudah diposting payroll.', 'Nadia','Nadia',NOW()
  FROM erp_attendance a WHERE a.attendance_no='ATT-20260617-0008' LIMIT 1;

INSERT INTO erp_overtime (overtime_no,employee_id,employee_no,department_code,cost_center_code,attendance_id,attendance_no,shift_schedule_id,assignment_no,overtime_date,planned_start,planned_end,actual_start,actual_end,requested_hours,approved_hours,payable_hours,rate_multiplier,hourly_rate,estimated_amount,overtime_type,overtime_reason,request_source,overtime_status,requested_by,requested_at,approved_by,approved_at,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'OT-20260618-0001',a.employee_id,a.employee_no,a.department_code,'1000-PRD',a.id,a.attendance_no,a.shift_schedule_id,a.assignment_no,a.attendance_date,a.planned_end,DATE_ADD(a.planned_end,INTERVAL 1 HOUR),a.planned_end,a.actual_clock_out,a.overtime_hours,a.overtime_hours,a.overtime_hours,1.50,42000,(a.overtime_hours*1.50*42000),'REGULAR_OT','Coating line support','ATTENDANCE','APPROVED','IGPRD',NOW(),'IGPRD',NOW(),'SAP-OT-0003','Overtime coating.', 'IGPRD','IGPRD',NOW()
  FROM erp_attendance a WHERE a.attendance_no='ATT-20260618-0002' LIMIT 1;

INSERT INTO erp_overtime (overtime_no,employee_id,employee_no,department_code,cost_center_code,overtime_date,planned_start,planned_end,actual_start,actual_end,requested_hours,approved_hours,payable_hours,rate_multiplier,hourly_rate,estimated_amount,overtime_type,overtime_reason,request_source,overtime_status,requested_by,requested_at,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'OT-20260619-0001',e.id,e.employee_no,e.department_code,'1000-MNT','2026-06-19','2026-06-19 17:00:00','2026-06-19 20:00:00','2026-06-19 17:00:00','2026-06-19 20:15:00',3.00,0,0,1.50,45000,0,'EMERGENCY_OT','Emergency maintenance mesin coating','MANUAL','REQUESTED','Budi Hartono',NOW(),'SAP-OT-0004','Menunggu approval manager maintenance.', 'Budi Hartono','Budi Hartono',NOW()
  FROM erp_employee_master e WHERE e.employee_no='EMP-0011' LIMIT 1;

INSERT INTO erp_overtime (overtime_no,employee_id,employee_no,department_code,cost_center_code,overtime_date,planned_start,planned_end,actual_start,actual_end,requested_hours,approved_hours,payable_hours,rate_multiplier,hourly_rate,estimated_amount,overtime_type,overtime_reason,request_source,overtime_status,requested_by,requested_at,reject_reason,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'OT-20260619-0002',e.id,e.employee_no,e.department_code,'1000-HR','2026-06-19','2026-06-19 17:30:00','2026-06-19 19:00:00',NULL,NULL,1.50,0,0,1.50,60000,0,'PROJECT_OT','HR data cleansing','WEB','REJECTED','Tinna',NOW(),'Tidak urgent, dijadwalkan jam kerja normal.','SAP-OT-0005','Rejected sample.', 'Tinna','Tinna',NOW()
  FROM erp_employee_master e WHERE e.employee_no='EMP-0003' LIMIT 1;

INSERT INTO erp_overtime (overtime_no,employee_id,employee_no,department_code,cost_center_code,overtime_date,planned_start,planned_end,actual_start,actual_end,requested_hours,approved_hours,payable_hours,rate_multiplier,hourly_rate,estimated_amount,overtime_type,overtime_reason,request_source,overtime_status,requested_by,requested_at,sap_reference,remarks,created_by,updated_by,updated_at)
SELECT 'OT-20260620-0001',e.id,e.employee_no,e.department_code,'1000-WH','2026-06-20','2026-06-20 08:00:00','2026-06-20 12:00:00',NULL,NULL,4.00,0,0,2.00,40000,0,'WEEKEND_OT','Stock opname warehouse weekend','MANUAL','DRAFT','IGWH_IN',NOW(),'SAP-OT-0006','Draft overtime weekend.', 'IGWH_IN','IGWH_IN',NOW()
  FROM erp_employee_master e WHERE e.employee_no='EMP-0009' LIMIT 1;

COMMIT;
