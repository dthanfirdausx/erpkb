-- Auto journal support for inventory adjustment, physical inventory, production, and return vendor.

INSERT INTO rekening (no_rek,induk,level,nama_rek,kat_coa,jenis)
SELECT '71199','711',4,'Pendapatan Selisih Stock',20,4
WHERE NOT EXISTS (SELECT 1 FROM rekening WHERE no_rek='71199');

INSERT INTO rekening (no_rek,induk,level,nama_rek,kat_coa,jenis)
SELECT '72199','721',4,'Beban Selisih Stock',19,5
WHERE NOT EXISTS (SELECT 1 FROM rekening WHERE no_rek='72199');

UPDATE rekening SET kat_coa=20, jenis=4 WHERE no_rek='71199';
UPDATE rekening SET kat_coa=19, jenis=5 WHERE no_rek='72199';

ALTER TABLE erp_issue_production_detail
  ADD COLUMN IF NOT EXISTS price DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER uom,
  ADD COLUMN IF NOT EXISTS amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER price;

ALTER TABLE erp_issue_production_trace
  ADD COLUMN IF NOT EXISTS price DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER qty,
  ADD COLUMN IF NOT EXISTS amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER price;

ALTER TABLE erp_issue_production
  ADD COLUMN IF NOT EXISTS total_amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER status;

ALTER TABLE erp_gr_production_detail
  ADD COLUMN IF NOT EXISTS price DECIMAL(18,5) NOT NULL DEFAULT 0 AFTER uom,
  ADD COLUMN IF NOT EXISTS amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER price;

ALTER TABLE erp_gr_production
  ADD COLUMN IF NOT EXISTS total_amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER status;
