-- SAP-like FI journal entry controls for manual and automatic GL posting.

ALTER TABLE jurnal_header
  ADD COLUMN IF NOT EXISTS document_type varchar(10) DEFAULT 'SA' AFTER no_jurnal,
  ADD COLUMN IF NOT EXISTS posting_status enum('DRAFT','POSTED','REVERSED') DEFAULT 'POSTED' AFTER document_type,
  ADD COLUMN IF NOT EXISTS source_module varchar(50) DEFAULT 'MANUAL_GL' AFTER no_bukti,
  ADD COLUMN IF NOT EXISTS source_document_no varchar(100) NULL AFTER source_module,
  ADD COLUMN IF NOT EXISTS reversal_of int(11) NULL AFTER source_document_no,
  ADD COLUMN IF NOT EXISTS posted_by varchar(100) NULL AFTER username,
  ADD COLUMN IF NOT EXISTS posted_at datetime NULL AFTER posted_by,
  ADD COLUMN IF NOT EXISTS updated_by varchar(100) NULL AFTER posted_at,
  ADD COLUMN IF NOT EXISTS updated_at datetime NULL AFTER updated_by,
  ADD INDEX IF NOT EXISTS idx_jh_period_status (tgl_jurnal, posting_status),
  ADD INDEX IF NOT EXISTS idx_jh_source (source_module, source_document_no),
  ADD INDEX IF NOT EXISTS idx_jh_reversal (reversal_of);

ALTER TABLE jurnal_detail
  ADD COLUMN IF NOT EXISTS line_no int(11) NULL AFTER id_header,
  ADD COLUMN IF NOT EXISTS line_text varchar(255) NULL AFTER no_rek,
  ADD COLUMN IF NOT EXISTS cost_center_id int(11) NULL AFTER line_text,
  ADD COLUMN IF NOT EXISTS profit_center_id int(11) NULL AFTER cost_center_id,
  ADD COLUMN IF NOT EXISTS tax_code_id int(11) NULL AFTER profit_center_id;

UPDATE jurnal_header
SET document_type = COALESCE(document_type, 'SA'),
    posting_status = COALESCE(posting_status, 'POSTED'),
    source_module = COALESCE(source_module, 'MANUAL_GL'),
    posted_by = COALESCE(posted_by, username),
    posted_at = COALESCE(posted_at, tgl_insert)
WHERE posting_status IS NULL
   OR posted_at IS NULL
   OR posted_by IS NULL
   OR source_module IS NULL
   OR document_type IS NULL;

UPDATE jurnal_detail d
JOIN (
  SELECT id, ROW_NUMBER() OVER (PARTITION BY id_header ORDER BY id) rn
  FROM jurnal_detail
) x ON x.id = d.id
SET d.line_no = COALESCE(d.line_no, x.rn);
