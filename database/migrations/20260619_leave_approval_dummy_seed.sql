DELETE FROM erp_leave_approval
 WHERE approval_no IN ('LVA-DUMMY-001','LVA-DUMMY-002','LVA-DUMMY-003');

INSERT INTO erp_leave_approval (
  approval_no, leave_request_id, approval_step, approver_employee_id, decision, decision_date,
  approval_note, previous_status, new_status, created_by, created_at, updated_by, updated_at
)
SELECT 'LVA-DUMMY-001', l.id, 'MANAGER', l.approver_employee_id, 'APPROVED', '2026-06-05 15:30:00',
       'Disetujui sesuai dokumen medis.', 'SUBMITTED', 'APPROVED', 'Nadia', '2026-06-05 15:30:00', 'Nadia', '2026-06-05 15:30:00'
  FROM erp_leave_request l WHERE l.leave_no='LVR-DUMMY-002';

INSERT INTO erp_leave_approval (
  approval_no, leave_request_id, approval_step, approver_employee_id, decision, decision_date,
  approval_note, previous_status, new_status, created_by, created_at, updated_by, updated_at
)
SELECT 'LVA-DUMMY-002', l.id, 'MANAGER', l.approver_employee_id, 'APPROVED', '2026-06-07 11:30:00',
       'Manager approved, menunggu review HR.', 'SUBMITTED', 'MANAGER_APPROVED', 'Tinna', '2026-06-07 11:30:00', 'Tinna', '2026-06-07 11:30:00'
  FROM erp_leave_request l WHERE l.leave_no='LVR-DUMMY-003';

INSERT INTO erp_leave_approval (
  approval_no, leave_request_id, approval_step, approver_employee_id, decision, decision_date,
  approval_note, previous_status, new_status, created_by, created_at, updated_by, updated_at
)
SELECT 'LVA-DUMMY-003', l.id, 'MANAGER', l.approver_employee_id, 'RETURNED', '2026-06-08 16:00:00',
       'Perlu klarifikasi tanggal dan handover pekerjaan.', 'SUBMITTED', 'RETURNED', 'IGPRD', '2026-06-08 16:00:00', 'IGPRD', '2026-06-08 16:00:00'
  FROM erp_leave_request l WHERE l.leave_no='LVR-DUMMY-004';
