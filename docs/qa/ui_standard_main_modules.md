# ERPKB UI Standard - Main Modules

Standar ini dipakai saat merapikan UI modul utama.

## Layout

- Gunakan `content-header` dengan breadcrumb konsisten.
- Gunakan card/box filter di atas tabel.
- Filter wajib memakai `.form-horizontal` atau grid Bootstrap yang rapi.
- Select master data memakai Select2.
- Tabel list memakai DataTables dengan:
  - filter tanggal/status/material bila relevan.
  - action button ringkas.
  - expanded row untuk detail panjang.
  - badge jumlah item bila ada detail.

## Form Add/Edit

- Header transaksi dipisah dari detail item.
- Field mandatory diberi label visual dan validasi sebelum save.
- Tombol save disabled sampai field mandatory valid untuk form kompleks.
- Detail item yang panjang memakai:
  - kolom utama di tabel kecil.
  - informasi panjang/jarang dipakai di expandable row.

## Excel Export

- Gunakan style export standar:
  - judul laporan di atas.
  - periode/filter tampil.
  - header berwarna dan freeze pane bila memungkinkan.
  - angka qty/nilai align kanan.
  - border tipis, tidak terlalu tebal.

## Priority Modules for UI Cleanup

| Priority | Module | Required UI Work |
|---|---|---|
| P0 | GR for PO | Confirm filter style, action layout, detail expandable, outstanding PO only. |
| P0 | GR without PO | Confirm DataTable, filters, action expanded row, Excel export. |
| P0 | GR Blocked Stock | Same as GR for PO. |
| P0 | Issue to Production | Ensure trace detail is readable and not horizontally overwhelming. |
| P0 | Production Confirmation | Detail tab for raw material usage, lot, and BC origin. |
| P0 | GR from Production Order | Trace inherited SFG/FG material origin clearly. |
| P0 | Goods Issue for Delivery | Customs document fields and trace detail must be simple to read. |
| P0 | Customs Reports | Table header follows official format and export matches view. |
| P0 | Stock Overview/Card/Aging/Traceability | Filter card, Select2, clickable qty detail. |
| P0 | Finance Reports | Cockpit layout, export, and balanced status clear. |
| P1 | HRD / ESS / MSS | Dashboard cards and employee-specific data visibility. |
| P1 | Sales & Distribution | Form add/edit/list consistent with delivery/billing flow. |
| P1 | Purchasing | PR/RFQ/PO approval flow with clear status badges. |

## Implementation Log

| Date | Module | Status | Notes |
|---|---|---|---|
| 2026-06-21 | Stock Outgoing | Done | Converted old CRUD-style page into read-only stock report with Select2 category filter, DataTables export, clickable stock quantity, and layer detail modal. Legacy sync/add/edit/delete routes are locked. |
| 2026-06-21 | Stock Pemasukan / Stock Overview | Done | Kept modern overview layout, removed legacy sync action from datatable, locked old CRUD routes, and moved export/detail source to `stock_layer`. |
| 2026-06-21 | Stock Bahan Baku Produksi | Done | Data/action/export hardened to `stock_layer` lokasi `PRODUKSI`, old write routes locked, and view redesigned into workbench UI with KPI cards, Select2 filter, DataTables action, export, and stock layer detail modal. |
| 2026-06-21 | Sales Order Detail | Data Done | Production progress/detail now reads only the new `erp_gr_production` flow, removing legacy `brgjadi` fallback. |
| 2026-06-21 | Transfer/Packing Stock Check | Data Done | Stock lookup endpoints and active form checks now read `stock_layer`, not `stock_barang` summaries. |
| 2026-06-21 | Goods Issue for Delivery Detail | Data Done | Raw-material trace tab now reads GI Delivery and GR Production trace tables instead of legacy production tables. |
| 2026-06-21 | Goods Issue for Delivery Workbench | Done | Active `pengeluaran-hamparan` route now opens the SAP-style `goods_issue_delivery` workbench. Legacy add/edit routes and direct write actions are locked; posting must use the workbench/reversal flow for consistent stock layer, journal, and BC trace. |
| 2026-06-21 | Active Module UI Audit | Done | Added `database/scripts/audit_active_module_ui.php` and generated `docs/qa/active_module_ui_audit.md`. Latest heuristic score: P0 avg 100%, P1 avg 100%, P2 avg 97.3%. Audit now prioritizes runtime `nav_act`, follows shared include views, and falls back to the module main file when a dedicated `_view.php` does not exist. |
| 2026-06-21 | Generic ERP CRUD UI | Done | Shared `modul/erp_crud/view.php` now has hero, KPI cards, filter form, Select2 marker, DataTables export, and compact action buttons; this improves finance/master modules such as Cost Center, Profit Center, Fiscal Period, Tax Code, and Exchange Rate. |
| 2026-06-21 | Goods Issue Return to Vendor Route | Done | Active `gi-return-to-vendor` menu now routes to the real `return_to_vendor` module and mirrors permissions from the Goods Receipt Return to Vendor menu. |
| 2026-06-21 | Generic ERP Master UI | Done | Shared `modul/erp_master/erp_master_view.php` now has hero, KPI cards, Bootstrap filter, Select2, DataTables export, compact actions, and detail modal. Bank Master, Payment Terms, COA, and Currency now use this workbench. |
| 2026-06-21 | AR Aging UI | Done | AR Aging now has SAP-style hero, filter card, Select2 customer filter, DataTables export, detail modal, and action column without changing aging calculation logic. |
| 2026-06-21 | GR Without PO UI | Done | Added hero, KPI cards, and DataTables Excel/Print export to the existing filter and expanded-item workbench. |
| 2026-06-21 | Finance Cockpit DataTables | Done | AP Aging, Vendor Invoice/Payment, Bank Receipt/Payment/Reconciliation, Cash Journal, Incoming Payment, and Customer Invoice now rebuild DataTables after AJAX load with Excel/Print export. |
| 2026-06-21 | Physical Inventory Detail Actions | Done | Count Entry, Difference Posting, and Physical Inventory History now include row detail actions/modal for audit-friendly stock count review. |
| 2026-06-21 | Goods Receipt UI Closeout | Done | GR Blocked Stock and GR for PO now include workbench hero context; Release GR Blocked Stock now includes row detail modal. |
| 2026-06-21 | ESS/HR Report UI | Done | My Profile now includes a profile data checklist with filter, export, and detail modal; Training Report placeholder now has KPI, filter, DataTable export, and detail modal. |
| 2026-06-21 | P1 UI Closeout | Done | My Attendance, My Leave, Team Attendance, Production Reports, and Quotation Follow Up now expose explicit detail actions/modals. My Attendance and Team Attendance also include DataTables Excel/Print export from the list view. |
| 2026-06-21 | P2 Dashboard/Tax/Customs UI | Done | ERP Workspace now includes filter, readiness matrix, DataTables export, and detail modal for Management Dashboard/System Configuration. Tax Invoice In/Out, VAT Report, Info KB, and Laporan Pengeluaran Per Dokumen Pabean were upgraded to clearer workbench-style views. |
| 2026-06-21 | Master Data Hardening | Done | Material, Vendor, UOM, Packing Unit, Material Category, BC Masuk/Keluar, Data User, Group User, and Menu Management now share the master data toolbar. Core master actions validate mandatory fields, duplicate codes, active status, and delete guards for records already used in transactions. |

## Next UI Targets

| Order | Module | Reason |
|---:|---|---|
| 1 | Remaining P2 report pages | P2 avg sekarang 97.3%; sisa gap utama tinggal beberapa laporan/finance lama seperti Kategori Pengiriman, Laba Rugi, Financial Closing, Balance Sheet, dan beberapa report export/detail marker. |
| 2 | Browser smoke test for P0/P1 routes | Jalankan klik manual login/list/filter/detail/export untuk modul prioritas setelah server lokal aktif. |
| 3 | Visual consistency pass | Samakan spacing, action width, dan modal detail pada modul P2 yang belum dirapikan penuh. |
