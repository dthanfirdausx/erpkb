# Management Dashboard & KPI Monitoring

## Current Implementation

Home dashboard sudah role-based dan membaca:

- `dashboard_widget`
- `dashboard_widget_role`
- `sys_group_users`
- `erp_kpi_monitoring_snapshot`

Widget yang tampil mengikuti `group_level` user login.

## Management Widgets

| Widget Code | Purpose | Source |
|---|---|---|
| `MGMT_COMPANY_SCORECARD` | Ringkasan status ERP dan role cockpit. | Home widget function. |
| `MGMT_PROFIT_LOSS_MTD` | Laba/rugi berjalan bulan ini. | `jurnal_header`, `jurnal_detail`. |
| `FIN_CASH_BANK_BALANCE` | Saldo kas/bank. | `jurnal_detail`. |
| `WH_STOCK_VALUE` | Posisi stock on hand. | `stock_layer`. |
| `WH_STOCK_CRITICAL` | Material zero/negative. | `stock_layer`. |
| `PP_PROD_ORDER_PLAN` | Production order terbuka. | `production_order`. |
| `PRD_OUTPUT_TODAY` | Output produksi hari ini. | `production_order_confirmation`. |
| `SYS_MENU_ROLE_COVERAGE` | Coverage role menu. | `sys_menu`, `sys_menu_role`. |

## KPI Monitoring Targets

| Area | KPI | Formula / Source | Target |
|---|---|---|---|
| Finance | Net Result MTD | Revenue - expense from posted journal. | Positive / monitored. |
| Warehouse | Stock Accuracy | Difference posting vs system stock. | >= 99.5%. |
| Warehouse | Negative Stock Case | Count open stock layer below zero. | 0. |
| Customs | Traceability Completeness | GI trace rows with BC origin / total GI rows. | 100%. |
| Production | Output Achievement | Confirmation yield / planned qty. | >= 95%. |
| Production | WIP Aging | Open WIP older than threshold. | Monitored. |
| Sales | Delivery Pending | Open outbound delivery. | By SLA. |
| Purchasing | PO Outstanding | PO not fully received. | By SLA. |
| System | Role Coverage | Active menu without role. | 0. |
| System | Journal Balance | Unbalanced posted journal. | 0. |

## Next Enhancement

- Tambahkan menu khusus `Management Dashboard` bila dashboard home ingin dipisah dari landing page.
- Tambahkan visual trend historis dari `erp_kpi_monitoring_snapshot`.
- Tambahkan cron harian untuk menjalankan generator KPI.

## KPI Snapshot

Tabel snapshot sudah tersedia melalui migration:

```bash
database/migrations/20260621_kpi_monitoring_snapshot.sql
```

Generator snapshot harian:

```bash
php database/scripts/generate_kpi_snapshot.php
php database/scripts/generate_kpi_snapshot.php 2026-06-21
```

KPI yang dihitung saat ini:

| KPI Code | Area | Target |
|---|---|---:|
| `P0_NEGATIVE_STOCK_CASE` | Warehouse | 0 |
| `P0_UNBALANCED_POSTED_JOURNAL` | Finance | 0 |
| `P0_ACTIVE_MENU_ROLE_GAP` | System | 0 |
| `P0_STOCK_LAYER_MATERIAL_GAP` | Master Data | 0 |
| `CUS_TRACEABILITY_COMPLETENESS` | Customs | 100% |
| `PP_OUTPUT_ACHIEVEMENT_MTD` | Production | >= 95% |
| `FIN_NET_RESULT_MTD` | Finance | >= 0 |
| `PUR_PO_OUTSTANDING_LINE` | Purchasing | Info |

Hasil generate lokal `2026-06-21`:

- 8 row snapshot terbentuk.
- P0 negative stock, unbalanced journal, role gap, dan material gap = `GOOD`.
- Customs traceability completeness = `100%`.
- Net result MTD masih `WARNING` karena nilai dummy/operasional bulan berjalan negatif.
