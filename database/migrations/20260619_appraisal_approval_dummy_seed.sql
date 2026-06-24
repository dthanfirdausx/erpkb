DELETE FROM erp_appraisal_approval
 WHERE appraisal_no IN ('APA-DUMMY-001','APA-DUMMY-002','APA-DUMMY-003','APA-DUMMY-004');

INSERT INTO erp_appraisal_approval (
  appraisal_no, cycle_year, appraisal_period, appraisal_type, employee_id, appraiser_employee_id,
  second_appraiser_employee_id, hr_reviewer_employee_id, department_code, job_title_id, appraisal_date,
  submitted_at, kpi_score, competency_score, behavior_score, final_score, final_rating,
  calibration_status, approval_level, decision, manager_comment, hr_comment, employee_comment,
  development_plan, reward_recommendation, improvement_required, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'APA-DUMMY-001', 2026, 'H1', 'MID_YEAR', e.id, e.manager_employee_id,
       NULL, hr.id, e.department_code, e.job_title_id, '2026-06-10',
       '2026-06-10 10:00:00', 86.00, 82.00, 88.00, 85.40, 'B',
       'SUBMITTED', 'MANAGER', 'PENDING',
       'Kinerja stabil dan target produksi tercapai dengan disiplin.',
       'Perlu review konsistensi dokumentasi output shift.',
       'Siap mengikuti program multi-skill operator.',
       'Coaching line balancing dan pelatihan basic quality gate.',
       'Merit increase candidate', 'N', 'Dummy appraisal approval H1 operator.',
       'admin', '2026-06-10 10:00:00', 'admin', '2026-06-10 10:00:00'
  FROM erp_employee_master e
  JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0007';

INSERT INTO erp_appraisal_approval (
  appraisal_no, cycle_year, appraisal_period, appraisal_type, employee_id, appraiser_employee_id,
  second_appraiser_employee_id, hr_reviewer_employee_id, department_code, job_title_id, appraisal_date,
  submitted_at, kpi_score, competency_score, behavior_score, final_score, final_rating,
  calibration_status, approval_level, decision, manager_comment, hr_comment, employee_comment,
  development_plan, reward_recommendation, improvement_required, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'APA-DUMMY-002', 2026, 'H1', 'MID_YEAR', e.id, e.manager_employee_id,
       gm.id, hr.id, e.department_code, e.job_title_id, '2026-06-11',
       '2026-06-11 09:30:00', 91.00, 86.00, 90.00, 89.30, 'B',
       'HR_REVIEW', 'HR', 'PENDING',
       'Akurasi stock handling baik, koordinasi inbound/outbound meningkat.',
       'Menunggu kalibrasi HR untuk rekomendasi pengembangan warehouse.',
       'Butuh training advanced inventory control.',
       'Sertifikasi warehouse operation dan inventory accuracy.',
       'Promotion pool', 'N', 'Dummy appraisal approval warehouse.',
       'admin', '2026-06-11 09:30:00', 'admin', '2026-06-11 09:30:00'
  FROM erp_employee_master e
  JOIN erp_employee_master gm ON gm.employee_no='EMP-0001'
  JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0009';

INSERT INTO erp_appraisal_approval (
  appraisal_no, cycle_year, appraisal_period, appraisal_type, employee_id, appraiser_employee_id,
  second_appraiser_employee_id, hr_reviewer_employee_id, department_code, job_title_id, appraisal_date,
  submitted_at, kpi_score, competency_score, behavior_score, final_score, final_rating,
  calibration_status, approval_level, decision, decision_by, decision_at, manager_comment, hr_comment, employee_comment,
  development_plan, reward_recommendation, improvement_required, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'APA-DUMMY-003', 2026, 'ANNUAL', 'ANNUAL', e.id, e.manager_employee_id,
       gm.id, hr.id, e.department_code, e.job_title_id, '2026-06-12',
       '2026-06-12 14:00:00', 78.00, 75.00, 80.00, 77.50, 'C',
       'APPROVED', 'FINAL', 'APPROVED', 'admin', '2026-06-12 16:00:00',
       'Target purchasing terpenuhi, perlu peningkatan negosiasi dan vendor follow up.',
       'Disetujui untuk development plan procurement analytics.',
       'Membutuhkan exposure sourcing vendor baru.',
       'Training negotiation skill dan vendor evaluation.',
       'Development plan only', 'Y', 'Dummy approved appraisal procurement.',
       'admin', '2026-06-12 14:00:00', 'admin', '2026-06-12 16:00:00'
  FROM erp_employee_master e
  JOIN erp_employee_master gm ON gm.employee_no='EMP-0001'
  JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0012';

INSERT INTO erp_appraisal_approval (
  appraisal_no, cycle_year, appraisal_period, appraisal_type, employee_id, appraiser_employee_id,
  second_appraiser_employee_id, hr_reviewer_employee_id, department_code, job_title_id, appraisal_date,
  submitted_at, kpi_score, competency_score, behavior_score, final_score, final_rating,
  calibration_status, approval_level, decision, manager_comment, hr_comment, employee_comment,
  development_plan, reward_recommendation, improvement_required, remarks, created_by, created_at, updated_by, updated_at
)
SELECT 'APA-DUMMY-004', 2026, 'PROBATION', 'PROBATION', e.id, e.manager_employee_id,
       NULL, hr.id, e.department_code, e.job_title_id, '2026-06-13',
       NULL, 68.00, 72.00, 74.00, 70.00, 'C',
       'DRAFT', 'MANAGER', 'PENDING',
       'Draft review probation untuk teknisi maintenance.',
       '',
       '',
       'Pendampingan troubleshooting mesin dan preventive maintenance.',
       'Extend coaching', 'Y', 'Dummy draft probation appraisal.',
       'admin', '2026-06-13 08:00:00', 'admin', '2026-06-13 08:00:00'
  FROM erp_employee_master e
  JOIN erp_employee_master hr ON hr.employee_no='EMP-0003'
 WHERE e.employee_no='EMP-0011';
