UPDATE rekening
SET kat_coa=13
WHERE no_rek='21401'
  AND EXISTS (SELECT 1 FROM coa_kategori WHERE id=13 AND kategori_akun='kewajiban');

UPDATE jurnal_detail
SET no_rek='12199'
WHERE no_rek='121'
  AND EXISTS (SELECT 1 FROM rekening WHERE no_rek='12199');

ALTER TABLE jurnal_header
  ADD UNIQUE KEY IF NOT EXISTS uk_journal_source_status (source_module,source_document_no,posting_status);

DROP TABLE IF EXISTS jurnal_umum;
DROP TABLE IF EXISTS jurnal_penyesuaian;
DROP TABLE IF EXISTS jurnalentri_detail;
DROP TABLE IF EXISTS jurnalentri;
DROP TABLE IF EXISTS inv;
