# ERPKB Module Test Checklist

Checklist ini dipakai sebagai baseline test resmi. Status awal: `TODO`.

| Priority | Module Area | Test Scenario | Expected Result | Status |
|---|---|---|---|---|
| P0 | GR for Purchase Order | Save GR dari PO outstanding dengan BC document lengkap. | `pemasukan`, `pemasukan_detail`, `stock_layer`, `detail_transaksi`, dan auto journal terbentuk balance. | VERIFIED BY SEED |
| P0 | GR for Purchase Order | Save GR qty melebihi outstanding PO. | Ditolak, tidak ada partial insert, tidak ada stock/journal baru. | TODO |
| P0 | GR without PO | Save GR tanpa PO dengan material, lokasi, dokumen BC, price. | Stock layer bertambah, material document 501 terbentuk, journal pembelian balance. | TODO |
| P0 | GR Blocked Stock | Save GR blocked dari PO. | Stock masuk `BLOCKED`, material document 103, journal balance. | TODO |
| P0 | Release GR Blocked Stock | Release blocked ke unrestricted. | Layer blocked berkurang, layer unrestricted bertambah, material document transfer terbentuk. | TODO |
| P0 | Issue to Production | Issue bahan baku/SFG ke production order. | Stock gudang berkurang, WIP bertambah, trace BC asal tersimpan. | VERIFIED BY SEED |
| P0 | Production Confirmation | Confirm yield/scrap/rework. | Confirmation posted, bahan baku terpakai muncul, scrap sesuai handling. | VERIFIED BY SEED |
| P0 | GR from Production Order | Receive FG/SFG dari production order. | FG/SFG masuk stock, trace bahan baku sampai dokumen BC asal bisa dibuka. | VERIFIED BY SEED |
| P0 | Goods Issue for Delivery | Post GI dari delivery/packing list. | Stock FG/SFG berkurang, trace dokumen BC asal bisa dibuka, journal COGS balance. | VERIFIED BY SEED |
| P0 | Manual Stock Adjustment | Increase/decrease stock. | Stock layer/material document terbentuk, journal adjustment balance, tidak bisa membuat stok minus. | TODO |
| P0 | Physical Inventory Difference Posting | Post selisih opname positif/negatif. | Material document 701/702/711/712 dan journal PI diff balance. | TODO |
| P0 | Customs Mutasi Bahan Baku | Filter periode setelah transaksi PR-to-GI. | Saldo awal + pemasukan - pengeluaran + adjustment = saldo akhir. | TODO |
| P0 | Customs Mutasi Barang Jadi | Klik angka pengeluaran. | Popup detail GI delivery muncul, qty bisa expand sampai BC asal bahan baku. | TODO |
| P0 | Laporan Posisi WIP | Filter per tanggal. | WIP hanya menampilkan issue yang belum diselesaikan/GR production. | TODO |
| P0 | Stock Overview | Filter plant/SLoc/bin/stock type. | Qty sama dengan aggregate `stock_layer.qty_sisa`. | TODO |
| P0 | Stock Card | Klik saldo stock. | Detail layer batch/lot/dokumen BC muncul. | TODO |
| P0 | Jurnal Umum | Import template dan posting manual journal. | Total debit = credit, period open, detail journal tersimpan. | TODO |
| P0 | Neraca | Load per tanggal transaksi dummy. | Selisih balance = 0.00. | TODO |
| P0 | Laba Rugi | Load periode transaksi dummy. | Revenue, COGS, expense terbaca dari `jurnal_header/detail`. | TODO |
| P1 | Sales Order to Production | Buat SO MTO lalu production order. | Production order punya reference SO dan progress produksi bisa ditrace. | VERIFIED BY SEED |
| P1 | Purchase Requisition Approval | Submit PR, approve di approval center. | Approval/history tersimpan dan PR masuk flow RFQ/PO. | TODO |
| P1 | ESS My Attendance | Login employee. | Dashboard hanya data employee login. | TODO |
| P1 | Manager Self Service | Login manager. | Team attendance/leave/overtime hanya bawahan manager. | TODO |

## Test Data Baseline

Gunakan seed resmi:

- Script: `database/scripts/seed_dummy_e2e_pr_to_delivery.php`
- Marker: `DUMMY-E2E-001`
- Flow: PR -> PO -> GR -> Issue Production -> Confirmation -> GR Production -> SO -> Outbound Delivery -> Picking -> Packing List -> Surat Jalan -> GI Delivery -> Customer Invoice.

Kriteria wajib setelah seed:

- Tidak ada `stock_layer.qty_sisa < 0`.
- Semua jurnal `POSTED` balance.
- Neraca per tanggal seed balance.
- FG/SFG dapat ditrace sampai bahan baku dan dokumen BC asal.

## Automated P0 Gates

Jalankan gate berikut sebelum dan sesudah test transaksi besar:

```bash
php database/scripts/run_p0_hardening_audit.php
php database/scripts/scan_active_legacy_report_refs.php
```

Expected:

- Semua gate `run_p0_hardening_audit.php` berstatus `PASS`.
- `scan_active_legacy_report_refs.php` menghasilkan `Legacy hits: 0`.

## Seed Verification - 2026-06-21

Perintah:

```bash
php database/scripts/seed_dummy_e2e_pr_to_delivery.php
```

Output dokumen resmi:

| Document | Count |
|---|---:|
| PR-DUMMY-E2E-001 | 1 |
| PO-DUMMY-E2E-001 | 1 |
| BPB-DUMMY-E2E-001 | 1 |
| GIP-BSJ-DUMMY-E2E-001 / GIP-FG-DUMMY-E2E-001 | 2 |
| PC-BSJ-DUMMY-E2E-001 / PC-FG-DUMMY-E2E-001 | 2 |
| GRP-BSJ-DUMMY-E2E-001 / GRP-FG-DUMMY-E2E-001 | 2 |
| SO-DUMMY-E2E-001 | 1 |
| OD-DUMMY-E2E-001 | 1 |
| PICK-DUMMY-E2E-001 | 1 |
| PL-DUMMY-E2E-001 | 1 |
| SJ-DUMMY-E2E-001 | 1 |
| GID-DUMMY-E2E-001 | 1 |
| INV-S-DUMMY-E2E-001 | 1 |

Trace verification:

| Trace Table | Rows |
|---|---:|
| `erp_gr_production_trace` | 15 |
| `erp_issue_production_trace` | 9 |
| `erp_goods_issue_delivery_trace` | 1 |

Invariant after seed:

| Check | Result |
|---|---:|
| Negative stock layer | 0 |
| Unbalanced posted journal | 0 |
