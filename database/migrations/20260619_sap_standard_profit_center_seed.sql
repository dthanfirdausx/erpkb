INSERT INTO erp_profit_center (profit_center_code, profit_center_name, valid_from, valid_to, status)
VALUES
('1000-CORP', 'Corporate / Company Level', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-PLANT01', 'Plant 01 Operations', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-MFG', 'Manufacturing Operations', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-LOCAL', 'Local Sales Business', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-EXPORT', 'Export Sales Business', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-OEM', 'OEM / Contract Manufacturing', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-TRADING', 'Trading Business', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-SERVICE', 'Service / Support Business', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-BU01', 'Business Unit 01', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-BU02', 'Business Unit 02', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-PROD-A', 'Product Line A', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-PROD-B', 'Product Line B', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-CUSTOMS', 'Bonded Zone / Customs Business', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-INT', 'Intercompany Business', '2026-01-01', '9999-12-31', 'Aktif'),
('1000-SCRAP', 'Scrap / By Product Revenue', '2026-01-01', '9999-12-31', 'Aktif')
ON DUPLICATE KEY UPDATE
  profit_center_name = VALUES(profit_center_name),
  valid_from = VALUES(valid_from),
  valid_to = VALUES(valid_to),
  status = VALUES(status);
