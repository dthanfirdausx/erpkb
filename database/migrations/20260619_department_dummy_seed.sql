START TRANSACTION;
SET @manager_id = (SELECT id FROM sys_users WHERE username IN ('admin','dthan') ORDER BY FIELD(username,'admin','dthan'), id LIMIT 1);
SET @psa_adm = (SELECT id FROM erp_company_structure WHERE structure_code='PSA-ADM' LIMIT 1);
SET @psa_prod = (SELECT id FROM erp_company_structure WHERE structure_code='PSA-PROD' LIMIT 1);
SET @psa_wh = (SELECT id FROM erp_company_structure WHERE structure_code='PSA-WH' LIMIT 1);
SET @psa_sls = (SELECT id FROM erp_company_structure WHERE structure_code='PSA-SLS' LIMIT 1);

INSERT INTO dept (kd_dept,nm_dept,dept_short_name,dept_type,parent_dept_code,company_structure_id,cost_center_code,profit_center_code,manager_user_id,functional_area,valid_from,valid_to,status,sap_reference,remarks,created_by,updated_by,updated_at)
VALUES
('DEP-ADM','Administration','Admin','SUPPORT',NULL,@psa_adm,'1000-ADM','1000-CORP',@manager_id,'ADMINISTRATION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-ADM','Root department untuk fungsi administrasi dan shared service.','codex','codex',NOW()),
('DEP-FIN','Finance & Accounting','Finance','FINANCE','DEP-ADM',(SELECT id FROM erp_company_structure WHERE structure_code='OU-FIN' LIMIT 1),'1000-FIN','1000-CORP',@manager_id,'FINANCE','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-FIN','GL, AP, AR, tax, cash bank, dan financial closing.','codex','codex',NOW()),
('DEP-HR','Human Resources','HR','HR','DEP-ADM',(SELECT id FROM erp_company_structure WHERE structure_code='OU-HR' LIMIT 1),'1000-HR','1000-CORP',@manager_id,'HUMAN_RESOURCE','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-HR','Employee master, attendance, payroll coordination, dan organization management.','codex','codex',NOW()),
('DEP-IT','Information Technology','IT','SUPPORT','DEP-ADM',(SELECT id FROM erp_company_structure WHERE structure_code='OU-IT' LIMIT 1),'1000-IT','1000-SERVICE',@manager_id,'IT_SUPPORT','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-IT','ERP support, infrastructure, user access, dan helpdesk.','codex','codex',NOW()),
('DEP-PRC','Procurement & Purchasing','Purchasing','SUPPORT','DEP-ADM',(SELECT id FROM erp_company_structure WHERE structure_code='OU-PRC' LIMIT 1),'1000-PRC','1000-CORP',@manager_id,'PROCUREMENT','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-PRC','PR, RFQ, PO, vendor evaluation, dan supplier coordination.','codex','codex',NOW()),
('DEP-CUS','Customs Compliance','Customs','SUPPORT','DEP-ADM',(SELECT id FROM erp_company_structure WHERE structure_code='OU-CUS' LIMIT 1),'1000-CUS','1000-CUSTOMS',@manager_id,'CUSTOMS_COMPLIANCE','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-CUS','Dokumen BC, CEISA, bonded zone compliance, dan traceability kepabeanan.','codex','codex',NOW()),
('DEP-PRD','Production','Production','PRODUCTION',NULL,@psa_prod,'1000-PRD','1000-MFG',@manager_id,'PRODUCTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-PRD','Root department untuk shop floor dan production execution.','codex','codex',NOW()),
('DEP-0001','Mixing','Mixing','PRODUCTION','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-MIX' LIMIT 1),'1000-MIX','1000-PROD-A',@manager_id,'PRODUCTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-MIX','Proses mixing bahan baku sesuai production order dan formula.','codex','codex',NOW()),
('DEP-0002','Coating','Coating','PRODUCTION','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-COAT' LIMIT 1),'1000-COAT','1000-PROD-A',@manager_id,'PRODUCTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-COAT','Proses coating dan intermediate goods.','codex','codex',NOW()),
('DEP-0003','Laminating','Laminating','PRODUCTION','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-LAM' LIMIT 1),'1000-LAM','1000-PROD-B',@manager_id,'PRODUCTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-LAM','Proses laminating dan semi finished goods.','codex','codex',NOW()),
('DEP-0004','Separating','Separating','PRODUCTION','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-PROD' LIMIT 1),'1000-SEP','1000-MFG',@manager_id,'PRODUCTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-SEP','Proses separating/slitting hasil produksi.','codex','codex',NOW()),
('DEP-0005','Packing','Packing','PRODUCTION','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-PACK' LIMIT 1),'1000-PACK','1000-MFG',@manager_id,'PRODUCTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-PACK','Packing finished goods dan labeling.','codex','codex',NOW()),
('DEP-WH','Warehouse','Warehouse','WAREHOUSE',NULL,@psa_wh,'1000-WH','1000-PLANT01',@manager_id,'WAREHOUSE','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-WH','Goods receipt, goods issue, stock transfer, physical inventory, dan material document.','codex','codex',NOW()),
('DEP-LOG','Logistics & Shipping','Logistics','WAREHOUSE','DEP-WH',(SELECT id FROM erp_company_structure WHERE structure_code='OU-LOG' LIMIT 1),'1000-LOG','1000-LOCAL',@manager_id,'SALES_DISTRIBUTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-LOG','Outbound delivery, packing list, surat jalan, dan shipment.','codex','codex',NOW()),
('DEP-QA','Quality Assurance & Control','QA/QC','QUALITY','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-QA' LIMIT 1),'1000-QA','1000-MFG',@manager_id,'QUALITY','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-QA','Inspection lot, usage decision, NCR, CAPA, dan quality monitoring.','codex','codex',NOW()),
('DEP-MNT','Maintenance Engineering','Maintenance','OPERATIONAL','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-MNT' LIMIT 1),'1000-MNT','1000-SERVICE',@manager_id,'ENGINEERING','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-MNT','Maintenance mesin, utility, spare part, dan downtime.','codex','codex',NOW()),
('DEP-RND','Research & Development','R&D','FUNCTIONAL','DEP-PRD',(SELECT id FROM erp_company_structure WHERE structure_code='OU-RND' LIMIT 1),'1000-RND','1000-PROD-B',@manager_id,'ENGINEERING','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-RND','Trial produk, sample, formulation, dan pengembangan produk.','codex','codex',NOW()),
('DEP-SLS','Sales & Marketing','Sales','SALES',NULL,@psa_sls,'1000-SLS','1000-LOCAL',@manager_id,'SALES_DISTRIBUTION','2026-01-01','9999-12-31','ACTIVE','SAP-DEPT-SLS','Inquiry, quotation, sales order, customer relationship, dan sales monitoring.','codex','codex',NOW())
ON DUPLICATE KEY UPDATE
  nm_dept=VALUES(nm_dept),
  dept_short_name=VALUES(dept_short_name),
  dept_type=VALUES(dept_type),
  parent_dept_code=VALUES(parent_dept_code),
  company_structure_id=VALUES(company_structure_id),
  cost_center_code=VALUES(cost_center_code),
  profit_center_code=VALUES(profit_center_code),
  manager_user_id=VALUES(manager_user_id),
  functional_area=VALUES(functional_area),
  valid_from=VALUES(valid_from),
  valid_to=VALUES(valid_to),
  status=VALUES(status),
  sap_reference=VALUES(sap_reference),
  remarks=VALUES(remarks),
  updated_by='codex',
  updated_at=NOW();
COMMIT;
