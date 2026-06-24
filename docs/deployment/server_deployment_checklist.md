# ERPKB Server Deployment Checklist

## Pre-Deployment

- Backup database lokal dan server.
- Backup folder `public_html/new` di server.
- Pastikan PHP extension aktif: PDO MySQL, mbstring, zip, gd, xml.
- Pastikan permission folder upload:
  - `upload/`
  - `upload/infokb/`
  - folder export Excel bila ada.
- Pastikan konfigurasi database server sesuai `inc/config.php`.

## Database

- Export database dengan opsi aman:
  - `--single-transaction`
  - `--routines`
  - `--triggers`
  - `--default-character-set=utf8`
- Command lokal yang direkomendasikan:

```bash
/Applications/XAMPP/xamppfiles/bin/mysqldump \
  -udthan -prealmadrid -h127.0.0.1 -P3307 \
  --single-transaction --quick --routines --triggers --events \
  --default-character-set=utf8mb4 \
  --set-gtid-purged=OFF \
  erpkb > database/backups/erpkb_deploy_$(date +%Y%m%d_%H%M%S).sql
```

- Import ke server staging terlebih dahulu.
- Jalankan validasi:
  - semua view valid.
  - tidak ada stok minus.
  - jurnal posted balance.
  - menu role coverage 100%.

Validasi SQL setelah import:

```sql
SELECT 'negative_stock_layer' check_name, COUNT(*) total
FROM stock_layer
WHERE COALESCE(qty_sisa,0) < -0.00001
UNION ALL
SELECT 'unbalanced_posted_journal', COUNT(*)
FROM (
  SELECT h.id, ROUND(SUM(COALESCE(d.debet,0)-COALESCE(d.kredit,0)),2) diff
  FROM jurnal_header h
  JOIN jurnal_detail d ON d.id_header=h.id
  WHERE h.posting_status='POSTED'
  GROUP BY h.id
  HAVING ABS(diff)>0.01
) x
UNION ALL
SELECT 'active_menu_without_role', COUNT(*)
FROM sys_menu m
LEFT JOIN sys_menu_role r ON r.id_menu=m.id
WHERE m.tampil='Y'
  AND m.type_menu='page'
  AND m.url<>'#'
  AND r.id_menu IS NULL;
```

Expected result semua `total = 0`.

## Application Files

- Upload file aplikasi via FTP/SFTP.
- Jangan upload file lokal yang tidak diperlukan:
  - `.DS_Store`
  - folder backup lokal besar.
  - temporary export.
  - `.codex_tmp/`
  - `outputs/`
  - `database/backups/*.sql` kecuali memang sedang deploy DB.
- Pastikan asset default user dan logo KB ikut terupload.

Checklist folder penting yang wajib ikut:

- `assets/dist/img/default-user-neutral.svg`
- `upload/infokb/`
- `inc/accounting_journal.php`
- `inc/excel_style_helper.php`
- semua folder modul baru di `modul/`
- `database/migrations/20260621_kpi_monitoring_snapshot.sql`
- `database/scripts/generate_kpi_snapshot.php`
- `database/scripts/run_p0_hardening_audit.php`
- `database/scripts/scan_active_legacy_report_refs.php`
- `database/scripts/audit_active_module_ui.php`
- `database/scripts/create_deployment_package.sh`

## Build Deployment Package

Paket deploy aplikasi dapat dibuat dengan:

```bash
./database/scripts/create_deployment_package.sh
```

Script ini akan:

- menjalankan `run_p0_hardening_audit.php`.
- menjalankan `scan_active_legacy_report_refs.php`.
- menjalankan `audit_active_module_ui.php` untuk membuat laporan kualitas UI modul aktif.
- membuat archive `.tar.gz` di `outputs/deployment/`.
- membuat manifest hasil gate dan ukuran file.

Hasil lokal terakhir:

- Package: `outputs/deployment/erpkb_deploy_20260621_104901.tar.gz`
- Manifest: `outputs/deployment/erpkb_deploy_20260621_104901.manifest.txt`
- Size: `148129994` bytes.

## Scheduled Jobs

Untuk monitoring KPI harian, jalankan generator snapshot lewat cron di server:

```bash
0 1 * * * /usr/bin/php /path/to/public_html/new/database/scripts/generate_kpi_snapshot.php >> /path/to/public_html/new/logs/kpi_snapshot.log 2>&1
```

Sesuaikan path PHP dan folder aplikasi dengan server.

## Smoke Test

- Login admin.
- Login beacukai.
- Login employee self service.
- Buka dashboard home.
- Buka Stock Overview.
- Buka Customs Report pemasukan/pengeluaran.
- Download Excel minimal satu laporan.
- Test satu transaksi kecil di staging jika tersedia.
- Jalankan seed staging opsional:

```bash
php database/scripts/seed_dummy_e2e_pr_to_delivery.php
php database/scripts/generate_kpi_snapshot.php
php database/scripts/run_p0_hardening_audit.php
php database/scripts/scan_active_legacy_report_refs.php
php database/scripts/audit_active_module_ui.php
```

Lalu ulangi validasi stok minus dan jurnal balance.

## Rollback

- Simpan backup database sebelum import.
- Simpan backup folder server sebelum upload.
- Jika smoke test gagal, restore folder dan database terakhir.
