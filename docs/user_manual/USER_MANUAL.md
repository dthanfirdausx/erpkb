# ERPKB User Manual

Dokumen ini menjadi panduan operasional awal untuk user ERPKB. Fokusnya adalah alur kerja, data yang wajib diisi, kontrol yang harus dicek, dan cara membaca laporan.

## 1. Akses Aplikasi

1. Buka URL aplikasi ERPKB.
2. Login memakai username dan password yang diberikan administrator.
3. Menu yang tampil mengikuti group user dan role permission.
4. Jika lupa password, hubungi administrator untuk reset.

Catatan:

- User karyawan memakai menu `Employee Self Service`.
- User manager memakai menu `Manager Self Service`.
- User beacukai/customs hanya melihat dashboard customs, customs reports, dan log aktivitas sesuai permission.
- Super admin dapat memakai fitur `Login As` untuk membantu troubleshooting user.

## 2. Prinsip Data ERP

ERPKB memakai prinsip satu transaksi harus meninggalkan jejak yang lengkap:

- Stok dicatat di `stock_layer` agar lot/batch, lokasi, stock type, dan dokumen BC asal bisa ditelusuri.
- Transaksi barang yang berdampak nilai otomatis membentuk jurnal akuntansi.
- Dokumen produksi FG/SFG harus bisa ditrace sampai bahan baku dan dokumen BC asal.
- Laporan customs membaca transaksi terbaru, bukan tabel legacy.
- Sistem harus menolak transaksi yang menyebabkan stok minus.

## 3. Master Data Wajib

Sebelum transaksi berjalan, pastikan master berikut sudah lengkap:

| Area | Master Data | Fungsi |
|---|---|---|
| Organization | Plant, Storage Location, Storage Bin | Lokasi fisik dan administrasi stok. |
| Material | Material Master, Material Type, Material Group, UOM | Identitas barang dan satuan. |
| Purchasing | Vendor, Purchasing Organization, Payment Term | Proses PR/PO dan pembelian. |
| Sales | Customer/Business Partner, Sales Organization | Proses sales order dan delivery. |
| Finance | COA, Tax Code, Exchange Rate, Fiscal Period | Jurnal, pajak, dan periode posting. |
| Production | BOM, Routing, Production Version, Factory Calendar | Perencanaan dan eksekusi produksi. |
| HR | Company Structure, Department, Job Title, Employee | Struktur organisasi dan data karyawan. |

## 4. Alur Purchasing sampai Goods Receipt

1. Buat `Purchase Requisition` jika kebutuhan berasal dari internal.
2. Approval PR dilakukan melalui `Approval Center`.
3. Buat `Request for Quotation` jika perlu pembandingan vendor.
4. Buat `Purchase Order`.
5. Lakukan `GR for Purchase Order` saat barang datang.
6. Isi dokumen pabean di header GR, dan data item pabean di detail bila diperlukan.
7. Sistem membuat stock layer dan jurnal otomatis.

Validasi penting:

- PO yang dipilih hanya PO outstanding.
- GR qty tidak boleh melebihi outstanding PO.
- Plant, storage location, storage bin, material, UOM, qty, dan price harus benar.
- Untuk kawasan berikat, jenis BC, nomor dokumen, tanggal dokumen, nomor AJU, dan supplier harus sesuai dokumen resmi.

## 5. Warehouse

### Goods Receipt

Gunakan menu ini untuk penerimaan barang:

- `GR for Purchase Order`: penerimaan berdasarkan PO.
- `GR without Purchase Order`: penerimaan exceptional/manual.
- `GR Blocked Stock`: penerimaan ke stock blocked.
- `Release GR Blocked Stock`: release blocked ke unrestricted.
- `GR from Production Order`: penerimaan hasil produksi FG/SFG.
- `Material Documents`: monitoring dokumen material.
- `Customs Receiving Monitor`: monitoring penerimaan terkait BC.

### Goods Issue

Gunakan menu ini untuk pengeluaran barang:

- `Issue to Production`: kirim bahan baku/SFG ke produksi.
- `Issue to Cost Center`: pemakaian internal ke cost center.
- `Issue to Asset`: pemakaian untuk asset.
- `Scrap Issue`: pengeluaran barang scrap.
- `Sample Issue`: pengeluaran sample.
- `Return to Vendor`: retur ke vendor.
- `Other Goods Issue`: pengeluaran lain.
- `Goods Issue for Delivery`: pengeluaran barang untuk penjualan/delivery.

Aturan penting:

- Pilih lot/batch yang benar.
- Pastikan stock type sesuai: unrestricted, quality, atau blocked.
- Sistem tidak boleh membuat `qty_sisa` negatif.
- Untuk pengeluaran barang dari kawasan berikat, isi dokumen BC pengeluaran pada `Goods Issue for Delivery`.

### Stock Transfer

Gunakan untuk perpindahan internal stok:

- `Transfer Posting`: ubah material/lokasi/stock type sesuai kebutuhan.
- `Storage Location Transfer`: pindah antar storage location.
- `Storage Bin Transfer`: pindah antar bin/rak.
- `Stock Type Transfer`: pindah unrestricted, quality, blocked.
- `Transfer History`: riwayat transfer.

## 6. Production

Alur produksi standar:

1. PPIC membuat `Production Order`.
2. Jika siap produksi, lakukan `Production Order Release`.
3. Warehouse melakukan `Issue to Production`.
4. Produksi melakukan `Production Confirmation`.
5. Jika ada output FG/SFG, lakukan `GR from Production Order`.
6. Jika ada scrap, input di `Production Confirmation`; sistem mencatat qty scrap dan material scrap sesuai konfigurasi.

Catatan traceability:

- FG/SFG yang berasal dari produksi harus mewarisi trace bahan baku.
- Jika SFG dipakai lagi sebagai komponen produksi berikutnya, trace tetap harus turun sampai bahan baku awal dan dokumen BC asal.
- Menu `Production Traceability`, laporan mutasi, dan laporan pengeluaran customs dipakai untuk mengecek jejak ini.

## 7. Sales and Distribution

Alur penjualan:

1. Buat `Customer Inquiry` bila ada permintaan awal customer.
2. Buat `Sales Quotation`.
3. Jika disetujui, buat `Sales Order`.
4. Buat `Outbound Delivery`.
5. Lakukan `Picking`.
6. Buat `Packing List`.
7. Buat `Surat Jalan`.
8. Lakukan `Goods Issue for Delivery`.
9. Buat `Sales Invoice`.

Aturan penting:

- Surat jalan dibuat berdasarkan packing list.
- Goods Issue for Delivery menjadi titik pengeluaran stock dan pencatatan dokumen BC pengeluaran.
- Sales invoice idealnya mengacu ke delivery/surat jalan agar nilai dan qty sesuai barang yang keluar.

## 8. Customs Reports

Menu customs report dipakai untuk laporan kawasan berikat:

- `Laporan Pemasukan Barang per Dokumen Pabean`.
- `Laporan Pengeluaran Barang per Dokumen Pabean`.
- `Mutasi Bahan Baku`.
- `Mutasi Barang Jadi`.
- `Mutasi Barang Modal`.
- `Mutasi Scrap`.
- `Laporan Posisi WIP`.

Cara membaca:

- Gunakan filter tanggal sesuai periode pelaporan.
- Angka pemasukan, pengeluaran, penyesuaian, dan jumlah dapat diklik untuk melihat detail transaksi.
- Pada barang jadi/setengah jadi, expanded row menampilkan bahan baku asal dan dokumen BC masuk.
- Export Excel mengikuti format laporan resmi dan style export standar ERPKB.

## 9. Finance

Transaksi operasional yang berdampak nilai akan otomatis membentuk jurnal. Finance melakukan review melalui:

- `Jurnal Umum`: semua jurnal manual dan auto-posting.
- `Buku Besar`: saldo per akun.
- `Neraca`: posisi aset, kewajiban, dan modal.
- `Laporan Laba Rugi`: pendapatan dan beban.
- `Cash Journal`, `Bank Receipt`, `Bank Payment`, `Bank Reconciliation`.
- `Vendor Invoice`, `Vendor Payment`, `Customer Invoice`, `Incoming Payment`.
- `AR Aging`, `AP Aging`, dan laporan pajak.
- `Financial Closing`: proses closing periode.

Kontrol wajib:

- Total debit harus sama dengan total kredit untuk jurnal posted.
- Periode posting harus OPEN.
- Jurnal reversal tidak boleh menghapus jurnal asal; harus membuat dokumen pembalik.
- Balance sheet harus balance pada tanggal laporan.

## 10. HR, ESS, dan MSS

HR mengelola:

- Organization Management: company structure, department, position, job title.
- Personnel Administration: employee master, contract, mutation.
- Time Management: attendance, overtime, leave, shift schedule.
- Payroll: payroll component, salary structure, payroll process, payslip.
- Recruitment, Performance, Training, dan HR reports.

Employee Self Service:

- `My Profile`: lihat dan update foto/profil.
- `My Attendance`: ringkasan absensi pribadi.
- `My Leave`: pengajuan dan histori cuti.
- `My Payslip`: slip gaji pribadi.
- `My Request`: permintaan pribadi.

Manager Self Service:

- `Team Attendance`: monitoring absensi tim.
- `Team Leave Approval`: approval cuti bawahan.
- `Team Overtime Attendance`: monitoring lembur.
- `Team Performance`: monitoring performa tim.
- `Team Request Approval`: approval request tim.

## 11. Stock Opname dan Adjustment

Stock opname:

1. Pilih tanggal `As Of Date`.
2. Filter plant, storage location, storage bin, stock type, material, atau dokumen bila perlu.
3. Klik `Create Opname Doc`.
4. Buka `Count Entry`.
5. Isi `Counted Qty`; default dapat mengikuti system qty.
6. Jika ada selisih, lakukan `Difference Posting`.

Manual stock adjustment:

- Dipakai untuk koreksi stok di luar proses normal.
- Increase menambah stock layer.
- Decrease mengurangi stock layer dengan FIFO/lot yang dipilih.
- Adjustment harus masuk laporan mutasi dan jurnal.
- Qty input tetap positif; tipe adjustment menentukan tambah atau kurang.

## 12. Dashboard dan KPI

Dashboard Home bersifat role-based:

- Management melihat ringkasan keuangan, stock, produksi, sales, purchasing, dan compliance.
- Customs melihat pemasukan/pengeluaran BC, outstanding AJU, WIP, bahan baku, barang jadi, dan realisasi devisa.
- Employee melihat ringkasan attendance pribadi.
- Manager melihat ringkasan tim.

KPI utama yang wajib dipantau:

| KPI | Target |
|---|---:|
| Negative stock case | 0 |
| Unbalanced posted journal | 0 |
| Active menu without role | 0 |
| Customs trace completeness | 100% |
| Stock accuracy | >= 99.5% |
| Production output achievement | >= 95% |

## 13. Checklist Harian

Warehouse:

- Cek GR/ GI yang belum selesai.
- Cek stock minus harus 0.
- Cek dokumen material dan trace lot/batch.

Production:

- Cek production order released.
- Cek issue to production.
- Input confirmation dan scrap bila ada.
- Pastikan GR from production sudah dilakukan untuk output.

Finance:

- Cek jurnal auto-posting balance.
- Cek transaksi yang belum posted.
- Cek neraca dan laba rugi.

Customs:

- Cek dokumen BC pemasukan/pengeluaran.
- Cek trace bahan baku untuk pengeluaran barang jadi.
- Cek mutasi bahan baku, barang jadi, scrap, dan WIP.

Administrator:

- Cek log aktivitas.
- Cek role permission.
- Cek backup dan readiness deployment.

