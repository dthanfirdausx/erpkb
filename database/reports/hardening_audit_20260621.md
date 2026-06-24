# ERPKB Hardening Audit - 2026-06-21

## Scope

Audit ini dipakai untuk pekerjaan hardening P0:

- Pastikan laporan aktif tidak membaca tabel/view lama yang sudah tidak sesuai flow baru.
- Pastikan tidak ada saldo stok minus pada `stock_layer`.
- Pastikan semua jurnal auto-posting dalam status `POSTED` balance.

## P0 Result

| Check | Result | Notes |
|---|---:|---|
| Active report/menu files scanned | 235 | Modul aktif dari `sys_menu.tampil='Y'` dan file laporan/report/mutasi/stock/inventory/traceability/dashboard/home. |
| Active menus without role | 0 | Semua menu aktif sudah punya `sys_menu_role`. |
| Negative stock layer | 0 | Query: `stock_layer.qty_sisa < 0`. |
| Unbalanced posted journal | 0 | Group by `jurnal_header.id`, compare `SUM(debet)` vs `SUM(kredit)`. |
| Journal header without detail | 0 | Tidak ada header jurnal kosong. |
| Journal detail without header | 0 | Tidak ada orphan journal detail. |
| Stock layer without material | 0 | Semua `stock_layer.kode` ada di `barang.kd_barang`. |
| Detail transaksi without material | 0 | Semua `detail_transaksi.kd_barang` ada di `barang.kd_barang`. |
| Open stock without valuation | 0 | Open layer punya harga dari `pemasukan_detail` atau `detail_transaksi`. |

## Fixes Applied

| Area | File | Fix |
|---|---|---|
| Dashboard Home | `modul/home/home.php` | Query KPI diarahkan ke tabel aktif: `erp_outbound_delivery`, `production_order_confirmation`, `log_aktifitas`, transfer storage/bin/type, dan `detail_transaksi.posting_date/date_created`. |
| Stock Pemasukan | `modul/stock_pemasukan/stock_pemasukan_data.php` | Tombol sync legacy dihapus dari datatable; list tetap membaca `v_stock_transaksi` aktif. |
| Stock Pemasukan Action | `modul/stock_pemasukan/stock_pemasukan_action.php` | Aksi sync/add/edit/delete legacy dikunci; export/detail diarahkan ke `stock_layer`. |
| Stock Pemasukan Route | `modul/stock_pemasukan/stock_pemasukan.php` | Route detail legacy ke `vtotalstockpemasukan` ditutup ke view report. |
| Stock Summary Helpers | `inc/function.php` | `rekap_stock`, `rekap_stock_produksi`, dan `rekap_stock_outgoing` tidak lagi membaca view rekap lama; fallback helper memakai `stock_layer` dan prepared query. |
| Stock Outgoing | `modul/stock_outgoing/stock_outgoing_data.php` | Tidak lagi membaca `v_stock_outgoing`; sumber data dipindahkan ke `stock_layer`. |
| Stock Outgoing Detail | `modul/stock_outgoing/stock_outgoing_action.php` | Tidak lagi membaca `v_rekap_stok_outgoing2`; detail layer dibaca dari `stock_layer` + `pemasukan_detail`. |
| Stock Outgoing UI | `modul/stock_outgoing/stock_outgoing_view.php` | Tampilan dirapikan menjadi report read-only dengan filter Select2, action detail layer, dan export DataTables. |
| Stock Outgoing Route | `modul/stock_outgoing/stock_outgoing.php` | Route tambah/edit/detail legacy dikunci ke view report agar tidak membaca `v_stock_outgoing`. |
| Stock Produksi | `modul/stock_bahan_baku_produksi/*` | List, detail, export, dan UI workbench tidak lagi membaca `vtotalstockprodbb`, `v_rekap_stok_produksi`, atau tabel produksi lama; sumber data diarahkan ke `stock_layer` lokasi `PRODUKSI`. |
| Laporan Transfer Pemasukan | `modul/laporan_transfer_pemasukan/laporan_transfer_pemasukan_data.php` | Tidak lagi membaca view hilang `v_rekap_transfer_incoming`; sumber data diganti ke `transfer`, `transfer_detail`, `barang`, dan `bagian`. |
| GR for PO | `modul/pemasukan_hamparan/pemasukan_hamparan_action.php` | Header, detail, stock layer, material document, PO received qty, dan journal dibuat atomic dalam transaction. |
| GR for PO Import Legacy | `modul/pemasukan_hamparan/pemasukan_hamparan_action.php` | `upload_excel` dan `upload_tpb` lama dikunci agar tidak menulis `pemasukan_baru`/`stock_incoming`. |
| GR without PO | `modul/gr_without_po/gr_without_po_action.php` | Proses simpan dibuat atomic dan rollback jika stock/material document/journal gagal. |
| GR Blocked Stock | `modul/gr_blocked_stock/gr_blocked_stock_action.php` | Proses simpan dibuat atomic dan validasi outstanding PO diperketat. |
| GR Blocked Import Legacy | `modul/gr_blocked_stock/gr_blocked_stock_action.php` | `upload_excel` dan `upload_tpb` lama dikunci agar tidak menulis `pemasukan_baru`/`stock_incoming`. |
| Legacy Pengeluaran | `modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php` | Pengurangan FIFO stock diberi guard `qty_sisa >= qty` dan journal dibuat dalam satu transaction. |
| Pengeluaran Import Legacy | `modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php` | `upload_excel` lama dikunci agar pengeluaran tetap lewat Goods Issue for Delivery/stock layer. |
| Goods Issue Detail Trace | `modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php` | Tab bahan baku pada detail pengeluaran tidak lagi membaca `brgjadi_detail`; trace diarahkan ke `erp_goods_issue_delivery_trace` dan `erp_gr_production_trace`. |
| Goods Issue for Delivery List | `modul/pengeluaran_hamparan/pengeluaran_hamparan_view.php`, `modul/pengeluaran_hamparan/pengeluaran_hamparan_data.php` | List lama dirapikan menjadi workbench UI dengan filter tanggal, filter jenis dokumen pabean, Select2, action detail/edit ringkas, dan query datatable berparameter. |
| Goods Issue for Delivery Active Route | `modul/pengeluaran_hamparan/pengeluaran_hamparan.php`, `modul/goods_issue_delivery/goods_issue_delivery_view.php` | Route menu aktif `pengeluaran-hamparan` sekarang membuka workbench SAP-style `goods_issue_delivery`. Route tambah/edit legacy ditutup dengan pesan operasional. |
| Goods Issue for Delivery Legacy Writes | `modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php` | Direct action legacy `in`, `up`, `delete`, dan `del_massal` dikunci. Perubahan GI yang sudah posted harus lewat reversal agar stock layer, material document, jurnal, dan trace BC tetap audit-safe. |
| Sales Order Detail | `modul/sales_order/sales_order_detail.php` | Progress produksi tidak lagi menggabungkan data legacy `brgjadi`; sumber produksi memakai `erp_gr_production` dan `erp_gr_production_detail`. |
| Stock Lookup Endpoint | `get_stock.php` | Endpoint stock lookup global tidak lagi membaca `stock_barang`; semua cek stock diarahkan ke `stock_layer` sesuai lokasi. |
| Transfer Produksi Stock Check | `modul/transfer_produksi/transfer_produksi_edit.php`, `modul/transfer_produksi/transfer_produksi_action_new.php` | Cek stock edit/helper diarahkan ke `stock_layer` lokasi `GUDANG`; referensi endpoint lama diganti ke nama stock layer. |
| Packing List Stock Check | `modul/packing_list/packing_list_detail.php` | Cek stock detail diarahkan ke endpoint `get_stock_layer_filtered` berbasis `stock_layer`. |

## Remaining Watchlist

| Area | Risk | Recommendation |
|---|---|---|
| Legacy hidden modules | Beberapa modul `tampil='N'` masih punya query ke object lama seperti `brgjadi*` dan `v_rekap_stok_*`. | Jangan aktifkan kembali tanpa refactor ke `production_order_confirmation`, `erp_gr_production`, `stock_layer`, dan trace table baru. |
| Import legacy action | Bagian import lama masih ada sebagai kode historis dan menyebut tabel lama seperti `pemasukan_baru`/`stock_incoming`. | Entry action sudah dikunci agar tidak bisa dijalankan. Jika fitur import masih diperlukan, rewrite import ke flow transaksi baru. |
| Manual SQL string legacy | Masih ada beberapa query lama yang tidak parameterized. | Saat modul disentuh berikutnya, ubah ke prepared query dan validasi input. |

## Recheck - 2026-06-21

| Check | Result |
|---|---:|
| Negative stock layer | 0 |
| Unbalanced posted journal | 0 |
| Posted journal header without detail | 0 |
| Journal detail without header | 0 |
| Active menu without role | 0 |
| Open stock without valuation | 0 |

Tambahan recheck aktif:

- Active menu scan masih menemukan string lama hanya pada blok import legacy GR/GI yang sudah langsung `action_response(...); break;`.
- Tidak ada lagi active hit untuk Sales Order detail, Transfer Produksi stock check, Packing List stock check, Stock Overview, Stock Outgoing, dan Stock Produksi.
- Query sampel detail jurnal imbalance dan stock layer minus mengembalikan 0 baris.
- Recheck setelah perapihan Goods Issue for Delivery list: active menu scan untuk `brgjadi*`, `stock_barang`, `stock_incoming`, `stock_outgoing`, `v_stock_outgoing`, `vtotalstock*`, `v_rekap_stok*`, dan `pemasukan_baru*` mengembalikan 0 hit dari route menu aktif.
- Gate P0 sekarang bisa dijalankan ulang via `php database/scripts/run_p0_hardening_audit.php`.
- Hasil terakhir script: semua gate `PASS`, termasuk `open_stock_without_valuation = 0`.
- Active legacy report reference scan sekarang bisa dijalankan ulang via `php database/scripts/scan_active_legacy_report_refs.php`.
- Hasil terakhir scanner: 218 active menus, 486 active read/report files, `legacy SQL read hit = 0`.
- Paket deploy bisa dibuat via `./database/scripts/create_deployment_package.sh`.
- Hasil build lokal terakhir: `outputs/deployment/erpkb_deploy_20260621_113851.tar.gz`, size `148157471` bytes, dibuat setelah P0 audit, legacy report scan, dan UI audit pass.
- Audit UI modul aktif bisa dijalankan ulang via `php database/scripts/audit_active_module_ui.php`.
- Hasil terakhir audit UI: P0 avg `100%`, P1 avg `100%`, P2 avg `97.3%`. Detail tersimpan di `docs/qa/active_module_ui_audit.md`.
- Route aktif `gi-return-to-vendor` sudah diarahkan ke modul `return_to_vendor` dan permission-nya disamakan dengan menu Return to Vendor utama.
- Shared generic CRUD view sudah memakai hero/KPI/filter/Select2 marker/DataTables export/compact actions sehingga modul master-finance yang memakai `erp_crud` lebih konsisten.
- Shared generic master view `erp_master` sudah dirapikan dan dipakai untuk Bank Master, Payment Terms, COA, dan Currency.
- AR Aging dan GR Without PO sudah disamakan ke pola workbench: hero/KPI, filter Bootstrap, Select2, DataTables/export, dan detail action yang lebih jelas.
- P0 UI closeout selesai: semua modul P0 aktif sudah memenuhi heuristic UI standard audit.
- P1 tambahan: My Profile dan Training Report placeholder sudah ditingkatkan dengan filter, DataTables/export, dan detail action.
- P1 closeout tambahan: My Attendance, My Leave, Team Attendance, Production Reports, dan Quotation Follow Up sudah ditambah explicit detail action/modal; My Attendance dan Team Attendance juga punya DataTables export.

## Recheck - 2026-06-21 04:24

| Check | Result |
|---|---:|
| Negative stock layer | 0 |
| Stock layer without material | 0 |
| Detail transaksi without material | 0 |
| Unbalanced posted journal | 0 |
| Posted journal header without detail | 0 |
| Journal detail without header | 0 |
| Active menu without role | 0 |
| Open stock without valuation | 0 |
| Active legacy report hits | 0 |

Audit UI aktif setelah P1 closeout:

| Priority | Avg Score |
|---|---:|
| P0 | 100% |
| P1 | 100% |
| P2 | 93.4% |

## Recheck - 2026-06-21 04:35

| Check | Result |
|---|---:|
| Negative stock layer | 0 |
| Stock layer without material | 0 |
| Detail transaksi without material | 0 |
| Unbalanced posted journal | 0 |
| Posted journal header without detail | 0 |
| Journal detail without header | 0 |
| Active menu without role | 0 |
| Open stock without valuation | 0 |
| Active legacy report hits | 0 |

P2 UI tambahan:

- ERP Workspace sekarang punya filter, readiness matrix, DataTables export, dan detail modal untuk Management Dashboard/System Configuration.
- Tax Invoice In/Out sekarang terbaca sebagai workbench dengan DataTables export dan detail action.
- VAT Report, Info KB, dan Laporan Pengeluaran Per Dokumen Pabean sudah dirapikan ke pola filter/workbench.
- KPI monitoring snapshot sudah dibuat di `erp_kpi_monitoring_snapshot` untuk tanggal `2026-06-21` sebanyak 8 KPI; 7 `GOOD` dan 1 `WARNING` pada `FIN_NET_RESULT_MTD` karena nilai dummy/operasional bulan berjalan negatif.
- Master data consistency audit sudah ditambahkan via `php database/scripts/audit_master_data_consistency.php`; hasil terakhir semua check `PASS`, termasuk duplicate material/vendor/UOM/category/BC/user dan orphan user/role/menu.
- Unique guard database sudah ditambahkan untuk `barang.kd_barang`, `satuan_packing.satuan_packing`, dan `sys_users.username`.

## Official Test Data Verification - 2026-06-21

Seed resmi berhasil dijalankan:

```bash
php database/scripts/seed_dummy_e2e_pr_to_delivery.php
```

Marker: `DUMMY-E2E-001`.

Dokumen yang terbentuk:

- PR, PO, GR PO, 2 Issue to Production, 2 Production Confirmation, 2 GR Production.
- Sales Order, Outbound Delivery, Picking, Packing List, Surat Jalan, GI Delivery, dan Sales Invoice.

Trace yang terbentuk:

| Trace Table | Rows |
|---|---:|
| `erp_gr_production_trace` | 15 |
| `erp_issue_production_trace` | 9 |
| `erp_goods_issue_delivery_trace` | 1 |

Invariant setelah seed:

| Check | Result |
|---|---:|
| Negative stock layer | 0 |
| Unbalanced posted journal | 0 |

Lint bersih untuk file yang disentuh pada siklus ini:

- `modul/stock_outgoing/stock_outgoing.php`
- `modul/stock_outgoing/stock_outgoing_view.php`
- `modul/stock_outgoing/stock_outgoing_data.php`
- `modul/stock_outgoing/stock_outgoing_action.php`
- `modul/stock_pemasukan/stock_pemasukan.php`
- `modul/stock_pemasukan/stock_pemasukan_data.php`
- `modul/stock_pemasukan/stock_pemasukan_action.php`
- `modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi.php`
- `modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_view.php`
- `modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_data.php`
- `modul/stock_bahan_baku_produksi/stock_bahan_baku_produksi_action.php`
- `inc/function.php`
- `get_stock.php`
- `modul/sales_order/sales_order_detail.php`
- `modul/transfer_produksi/transfer_produksi_edit.php`
- `modul/transfer_produksi/transfer_produksi_action_new.php`
- `modul/packing_list/packing_list_detail.php`
- `modul/pengeluaran_hamparan/pengeluaran_hamparan_action.php`
- `modul/pengeluaran_hamparan/pengeluaran_hamparan_view.php`
- `modul/pengeluaran_hamparan/pengeluaran_hamparan_data.php`
- `modul/pengeluaran_hamparan/pengeluaran_hamparan.php`
- `modul/goods_issue_delivery/goods_issue_delivery_view.php`
- `modul/goods_issue_delivery/goods_issue_delivery_lib.php`
- `modul/goods_issue_delivery/goods_issue_delivery_action.php`
- `modul/goods_issue_delivery/goods_issue_delivery_data.php`
