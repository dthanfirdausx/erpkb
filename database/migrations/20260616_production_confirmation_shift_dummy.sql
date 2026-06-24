-- Ensure standard dummy shift master data exists for production confirmation.

INSERT INTO erp_shift (kode_shift, nama_shift, jam_mulai, jam_selesai, status)
SELECT 'SHIFT-1', 'Shift 1', '07:00:00', '15:00:00', 'Aktif'
WHERE NOT EXISTS (SELECT 1 FROM erp_shift WHERE kode_shift='SHIFT-1');

UPDATE erp_shift
SET nama_shift='Shift 1',
    jam_mulai='07:00:00',
    jam_selesai='15:00:00',
    status='Aktif'
WHERE kode_shift='SHIFT-1';

INSERT INTO erp_shift (kode_shift, nama_shift, jam_mulai, jam_selesai, status)
SELECT 'SHIFT-2', 'Shift 2', '15:00:00', '23:00:00', 'Aktif'
WHERE NOT EXISTS (SELECT 1 FROM erp_shift WHERE kode_shift='SHIFT-2');

UPDATE erp_shift
SET nama_shift='Shift 2',
    jam_mulai='15:00:00',
    jam_selesai='23:00:00',
    status='Aktif'
WHERE kode_shift='SHIFT-2';

INSERT INTO erp_shift (kode_shift, nama_shift, jam_mulai, jam_selesai, status)
SELECT 'SHIFT-3', 'Shift 3', '23:00:00', '07:00:00', 'Aktif'
WHERE NOT EXISTS (SELECT 1 FROM erp_shift WHERE kode_shift='SHIFT-3');

UPDATE erp_shift
SET nama_shift='Shift 3',
    jam_mulai='23:00:00',
    jam_selesai='07:00:00',
    status='Aktif'
WHERE kode_shift='SHIFT-3';
