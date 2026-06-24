# Dummy Data ERP Demo

Dokumen ini menjelaskan seeder dummy end-to-end untuk demo/testing ERP KB.

## File

- Seeder utama: `database/seeders/seed_demo_full_erp.php`
- Seeder core E2E yang dipanggil ulang: `database/scripts/seed_dummy_e2e_pr_to_delivery.php`
- Backup sebelum insert dummy: `database/backups/erpkb_backup_before_dummy_20260623_055914.sql`

## Cara Eksekusi

Jalankan dari root project:

```bash
php database/seeders/seed_demo_full_erp.php
```

Seeder bersifat idempotent. Jika dijalankan ulang, data demo akan di-update/rebuild berdasarkan marker demo, bukan membuat duplicate bebas.

## Marker Data

- Supporting demo data: `DEMO-ERP-202606`
- Core end-to-end flow: `DUMMY-E2E-001`
- Master demo:
  - Vendor: `DVEN0001` sampai `DVEN0005`
  - Customer: `DCUS0001` sampai `DCUS0005`
  - Material: `DEMO-RM-*`, `DEMO-SFG-*`, `DEMO-FG-*`
  - Employee: `DEMO-EMP-001` sampai `DEMO-EMP-010`

## Skenario Core E2E

Seeder membuat alur manufaktur kawasan berikat yang bisa dites dari awal sampai akhir:

1. Purchase Requisition
2. Purchase Order
3. Goods Receipt PO dengan BC 2.3
4. Issue to Production untuk barang setengah jadi
5. Production Confirmation barang setengah jadi
6. GR from Production untuk barang setengah jadi
7. Issue to Production untuk barang jadi
8. Production Confirmation barang jadi
9. GR from Production untuk barang jadi
10. Sales Order
11. Outbound Delivery
12. Picking
13. Packing List
14. Surat Jalan
15. Goods Issue for Delivery
16. Sales Invoice

Dokumen kunci:

- `PR-DUMMY-E2E-001`
- `PO-DUMMY-E2E-001`
- `BPB-DUMMY-E2E-001`
- `POPR-BSJ-DUMMY-E2E-001`
- `POPR-FG-DUMMY-E2E-001`
- `GIP-BSJ-DUMMY-E2E-001`
- `GIP-FG-DUMMY-E2E-001`
- `GRP-BSJ-DUMMY-E2E-001`
- `GRP-FG-DUMMY-E2E-001`
- `SO-DUMMY-E2E-001`
- `OD-DUMMY-E2E-001`
- `PICK-DUMMY-E2E-001`
- `PL-DUMMY-E2E-001`
- `SJ-DUMMY-E2E-001`
- `GID-DUMMY-E2E-001`
- `INV-S-DUMMY-E2E-001`

## Skenario Mulai Dari Sales Order

Seeder juga membuat data demo khusus untuk mengetes Sales Order Monitoring dari awal Sales Order sampai produksi dan pengiriman.

Gunakan filter tanggal sekitar `2026-06-21`.

| Sales Order | Qty SO | Qty Produksi | Qty Kirim | Status di `v_sales_status` |
|---|---:|---:|---:|---|
| `SO-DEMO-SO-FLOW-001` | 100 | 0 | 0 | `BELUM PRODUKSI` |
| `SO-DEMO-SO-FLOW-002` | 100 | 40 | 0 | `PRODUKSI BELUM FULL` |
| `SO-DEMO-SO-FLOW-003` | 100 | 100 | 0 | `PROSES PRODUKSI` |
| `SO-DEMO-SO-FLOW-004` | 100 | 100 | 40 | `DIKIRIM SEBAGIAN` |
| `SO-DEMO-SO-FLOW-005` | 100 | 100 | 100 | `SUDAH DIKIRIM` |

Data ini dibuat untuk menjawab demo trace dari Sales Order:

- Sales Order yang belum diproduksi.
- Sales Order yang sudah mulai diproduksi sebagian.
- Sales Order yang sudah selesai produksi tapi belum dikirim.
- Sales Order yang sudah dikirim sebagian.
- Sales Order yang sudah selesai dikirim dan sudah dibuat invoice.

Dokumen turunannya memakai pola:

- Production Order: `POPR-DEMO-SO-FLOW-*`
- Production Confirmation: `PC-DEMO-SO-FLOW-*`
- GR Production: `GRP-DEMO-SO-FLOW-*`
- Outbound Delivery: `OD-DEMO-SO-FLOW-*`
- Picking: `PICK-DEMO-SO-FLOW-*`
- Packing List: `PL-DEMO-SO-FLOW-*`
- Surat Jalan: `SJ-DEMO-SO-FLOW-*`
- Goods Issue Delivery: `GID-DEMO-SO-FLOW-*`
- Invoice full delivery: `INV-S-DEMO-SO-FLOW-005`

## Traceability Customs

Trace bahan baku ke barang jadi dapat dites dari:

- `stock_layer` untuk penerimaan BC 2.3.
- `erp_issue_production_trace` untuk pemakaian bahan baku.
- `erp_gr_production_trace` untuk trace bahan baku yang diwariskan ke barang setengah jadi/barang jadi.
- `erp_goods_issue_delivery_trace` untuk pengeluaran barang jadi.

FG/SFG bertingkat memakai trace source:

- `DIRECT`: bahan baku langsung dipakai.
- `INHERITED`: bahan baku asal diwariskan dari barang setengah jadi ke barang jadi.

## Modul Pendukung Yang Diisi

Seeder juga menyiapkan data demo untuk:

- Master data: plant, storage location, storage bin, material type/group, material, vendor, customer, cost center, profit center, shift, factory calendar/work center jika tabel tersedia.
- Planning: forecast, demand management, MRP, material requirement.
- Sales pre-sales: customer inquiry, sales quotation, quotation follow up.
- HRD: employee master, family, education, document, attendance, overtime, leave, payroll component, salary structure, payroll process, payslip.
- Quality: inspection lot, quality notification/NCR, CAPA, usage decision.
- Inventory: manual stock adjustment, cycle count, stock opname.
- Finance: journal auto-posting, cash/bank, vendor invoice/payment, tax invoice in/out.

Seeder memakai pengecekan `table_exists`, sehingga modul tambahan yang tabelnya belum ada akan dilewati dengan aman.

## Checklist Testing Menu

Gunakan filter tanggal sekitar `2026-06-21`.

- Purchasing:
  - Purchase Requisition
  - Purchase Order
  - RFQ
  - Vendor Evaluation
- Warehouse:
  - GR for Purchase Order
  - Issue to Production
  - GR from Production Order
  - Goods Issue for Delivery
  - Stock Overview
  - Stock Card
  - Batch/Lot Traceability
  - Customs Stock Traceability
  - Cycle Count
  - Stock Opname
  - Manual Stock Adjustment
- Production:
  - Production Order
  - Production Confirmation
  - Production Traceability
  - WIP Monitoring
- Sales:
  - Customer Inquiry
  - Sales Quotation
  - Sales Order
  - Outbound Delivery
  - Picking
  - Packing List
  - Surat Jalan
  - Sales Invoice
  - Billing History
- Customs Report:
  - Laporan Pemasukan
  - Laporan Pengeluaran
  - Mutasi Bahan Baku
  - Mutasi Barang Jadi
  - Laporan Posisi WIP
- Finance:
  - Jurnal Umum
  - Buku Besar
  - Neraca
  - Laba Rugi
  - Vendor Invoice
  - Vendor Payment
  - Tax Invoice In/Out
  - VAT Report
- HRD/ESS/MSS:
  - Employee Master
  - Attendance
  - Leave Request/Approval
  - Payroll Process
  - Payslip
  - My Attendance
  - Team Attendance

## Query Validasi

Tidak boleh ada stock layer minus:

```sql
SELECT COUNT(*) AS negative_stock_layers
FROM stock_layer
WHERE COALESCE(qty_sisa, 0) < 0;
```

Tidak boleh ada jurnal posted yang tidak balance:

```sql
SELECT h.id, h.no_jurnal,
       ROUND(SUM(COALESCE(d.debet, 0) - COALESCE(d.kredit, 0)), 2) AS diff
FROM jurnal_header h
JOIN jurnal_detail d ON d.id_header = h.id
WHERE h.posting_status = 'POSTED'
GROUP BY h.id, h.no_jurnal
HAVING ABS(diff) > 0.01;
```

Trace bahan baku asal dokumen BC masuk:

```sql
SELECT source_material_code, raw_material_code, qty, no_aju, no_dokpab, jenis_dokpab, trace_source
FROM erp_gr_production_trace
WHERE no_aju LIKE 'AJU23%DUMMY%'
ORDER BY id;
```

Goods issue delivery demo:

```sql
SELECT *
FROM erp_goods_issue_delivery
WHERE gi_no LIKE '%DUMMY-E2E-001%';
```

## Hasil Validasi Terakhir

Validasi terakhir setelah seeder dijalankan:

- `negative_stock_layers`: 0
- `unbalanced_posted_journals`: 0
- `demo_materials`: 30
- `demo_vendors`: 5
- `demo_customers`: 5
- `demo_employees`: 10
- `core_production_orders`: 2
- `core_sales_invoices`: 1
- `customs_incoming_layers`: 8
- `gi_delivery_rows`: 1
- `gi_delivery_fg_trace_rows`: 1
- `production_raw_bc_trace_rows`: 15
- `production_inherited_trace_rows`: 7
- `sales_order_started_flow`: 5
- `stock_layers_demo_core`: 11
- `hr_attendance`: 10
- `finance_vendor_invoice`: 1
- `cycle_count`: 1
- `stock_opname`: 1
