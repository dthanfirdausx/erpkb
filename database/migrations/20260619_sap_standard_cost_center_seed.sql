INSERT INTO erp_cost_center (cost_center_code, cost_center_name, department_code, valid_from, valid_to, status)
VALUES
('1000-ADM', 'General Administration', 'DEP-ADM', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-HR', 'Human Resources', 'DEP-HR', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-FIN', 'Finance & Accounting', 'DEP-FIN', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-IT', 'Information Technology', 'DEP-IT', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-PRC', 'Procurement / Purchasing', 'DEP-PRC', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-WH', 'Warehouse / Inventory Management', 'DEP-WH', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-QA', 'Quality Assurance / Quality Control', 'DEP-QA', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-MNT', 'Maintenance', 'DEP-MNT', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-ENG', 'Engineering / Technical Support', 'DEP-ENG', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-PRD', 'Production - General', 'DEP-PRD', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-MIX', 'Production - Mixing', 'DEP-PRD', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-COAT', 'Production - Coating', 'DEP-PRD', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-LAM', 'Production - Laminating', 'DEP-PRD', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-SEP', 'Production - Separating', 'DEP-PRD', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-PACK', 'Production - Packing', 'DEP-PRD', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-RND', 'Research & Development', 'DEP-RND', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-SLS', 'Sales & Marketing', 'DEP-SLS', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-LOG', 'Logistics / Shipping', 'DEP-LOG', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-CUS', 'Customs Compliance', 'DEP-CUS', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-HSE', 'Health, Safety & Environment', 'DEP-HSE', '2026-01-01', '9999-12-31', 'Aktif')
ON DUPLICATE KEY UPDATE
  cost_center_name = VALUES(cost_center_name),
  department_code = VALUES(department_code),
  valid_from = VALUES(valid_from),
  valid_to = VALUES(valid_to),
  status = VALUES(status);
