ALTER TABLE infokb
  ADD COLUMN IF NOT EXISTS company_code varchar(20) NULL AFTER kode,
  ADD COLUMN IF NOT EXISTS business_area varchar(50) NULL AFTER company_code,
  ADD COLUMN IF NOT EXISTS default_plant_id int(11) NULL AFTER business_area,
  ADD COLUMN IF NOT EXISTS purchasing_org_id int(11) NULL AFTER default_plant_id,
  ADD COLUMN IF NOT EXISTS sales_org_id int(11) NULL AFTER purchasing_org_id,
  ADD COLUMN IF NOT EXISTS fiscal_year_variant varchar(10) NULL AFTER sales_org_id,
  ADD COLUMN IF NOT EXISTS local_currency varchar(3) NULL AFTER fiscal_year_variant,
  ADD COLUMN IF NOT EXISTS tax_registration_no varchar(50) NULL AFTER npwp,
  ADD COLUMN IF NOT EXISTS nomor_nib varchar(50) NULL AFTER tax_registration_no,
  ADD COLUMN IF NOT EXISTS nomor_api varchar(50) NULL AFTER nomor_nib,
  ADD COLUMN IF NOT EXISTS postal_code varchar(20) NULL AFTER kota,
  ADD COLUMN IF NOT EXISTS country varchar(3) NULL AFTER postal_code,
  ADD COLUMN IF NOT EXISTS email varchar(100) NULL AFTER fax,
  ADD COLUMN IF NOT EXISTS website varchar(150) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS jenis_fasilitas varchar(50) NULL AFTER kantor_pengawas,
  ADD COLUMN IF NOT EXISTS bank_name varchar(100) NULL AFTER bank,
  ADD COLUMN IF NOT EXISTS bank_account_name varchar(100) NULL AFTER bank_name,
  ADD COLUMN IF NOT EXISTS swift_code varchar(30) NULL AFTER bank_account_name,
  ADD COLUMN IF NOT EXISTS bank_currency varchar(3) NULL AFTER swift_code;

UPDATE infokb
SET company_code = IF(company_code IS NULL OR company_code = '', kode, company_code),
    local_currency = IF(local_currency IS NULL OR local_currency = '', 'IDR', local_currency),
    bank_currency = IF(bank_currency IS NULL OR bank_currency = '', 'IDR', bank_currency),
    country = IF(country IS NULL OR country = '', 'ID', country),
    jenis_fasilitas = IF(jenis_fasilitas IS NULL OR jenis_fasilitas = '', 'KAWASAN_BERIKAT', jenis_fasilitas);
