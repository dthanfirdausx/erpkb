# ERPKB Role Permission Matrix

## Coverage Summary

| Metric | Value |
|---|---:|
| Active page menus | 218 |
| Active page menus without role | 0 |
| Groups without any role | 0 |

## Role Summary

| Role Level | Role Name | Role Rows | Read | Insert | Update | Delete | Import |
|---|---|---:|---:|---:|---:|---:|---:|
| admin | admin | 319 | 317 | 258 | 249 | 186 | 90 |
| system_administrator | Administrator Sistem | 319 | 246 | 169 | 161 | 103 | 21 |
| auditor | Auditor | 313 | 209 | 17 | 17 | 11 | 21 |
| beacukai | Beacukai | 319 | 9 | 0 | 0 | 0 | 0 |
| employee_self_service | Employee Self Service | 12 | 6 | 2 | 3 | 0 | 0 |
| finance_akunting | Finance / Akunting | 292 | 149 | 43 | 38 | 29 | 21 |
| gudang | Gudang | 292 | 173 | 38 | 32 | 15 | 21 |
| hrd | Human Resource | 63 | 62 | 56 | 56 | 0 | 0 |
| manager_approver | Manager / Approver | 319 | 210 | 24 | 28 | 11 | 21 |
| ppic | PPIC | 292 | 168 | 39 | 39 | 11 | 21 |
| produksi | Produksi | 292 | 166 | 29 | 27 | 11 | 21 |
| purchasing | Purchasing | 292 | 108 | 29 | 29 | 17 | 22 |
| quality_control | Quality Control | 292 | 159 | 21 | 22 | 11 | 21 |
| sales | Sales | 292 | 139 | 25 | 25 | 18 | 21 |

## Governance Rule

- `admin`: full control, termasuk konfigurasi, user, master, dan semua transaksi.
- `system_administrator`: konfigurasi sistem, menu, role, master teknis, user support; tidak menjadi pemilik transaksi bisnis.
- `beacukai`: read-only untuk customs report dan log aktivitas.
- `auditor`: read-only untuk laporan dan audit trail; import hanya untuk kebutuhan audit-support yang eksplisit.
- `finance_akunting`: create/post/reverse transaksi FI, read inventory valuation, no delete transaksi posted.
- `gudang`: create/post transaksi warehouse, no delete transaksi posted.
- `purchasing`: PR/RFQ/PO/vendor flow, tanpa hak delete transaksi posted.
- `ppic`: planning, BOM/routing/version, production order planning/release.
- `produksi`: shop floor execution, confirmation, downtime, activity log.
- `quality_control`: inspection lot, incoming/in-process/final inspection, usage decision, NCR/CAPA.
- `sales`: inquiry, quotation, sales order, delivery/billing read sesuai otorisasi.
- `hrd`: HR master, time management, payroll process/report sesuai policy.
- `employee_self_service`: hanya data sendiri.
- `manager_approver`: approval dan monitoring bawahannya.

## Verification Query

Gunakan query ini setelah perubahan menu atau role:

```sql
SELECT COUNT(*) active_page_without_role
FROM sys_menu m
LEFT JOIN sys_menu_role r ON r.id_menu=m.id
WHERE m.tampil='Y'
  AND m.type_menu='page'
  AND m.url<>'#'
  AND r.id_menu IS NULL;

SELECT COUNT(*) group_without_any_role
FROM sys_group_users g
LEFT JOIN sys_menu_role r ON r.group_level=g.level
WHERE r.id IS NULL;
```

Expected result untuk keduanya: `0`.
