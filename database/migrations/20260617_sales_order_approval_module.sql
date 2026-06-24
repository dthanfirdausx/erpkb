-- =====================================================
-- SALES ORDER APPROVAL MODULE
-- SAP SD release/approval workflow for Sales Order
-- =====================================================

ALTER TABLE sales_order
  ADD COLUMN IF NOT EXISTS approval_status ENUM('DRAFT','SUBMITTED','PENDING','APPROVED','REJECTED','CANCELLED') NOT NULL DEFAULT 'SUBMITTED' AFTER status,
  ADD COLUMN IF NOT EXISTS submitted_by VARCHAR(50) NULL AFTER approval_status,
  ADD COLUMN IF NOT EXISTS submitted_at DATETIME NULL AFTER submitted_by,
  ADD COLUMN IF NOT EXISTS approved_by VARCHAR(50) NULL AFTER submitted_at,
  ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by,
  ADD COLUMN IF NOT EXISTS rejected_by VARCHAR(50) NULL AFTER approved_at,
  ADD COLUMN IF NOT EXISTS rejected_at DATETIME NULL AFTER rejected_by,
  ADD COLUMN IF NOT EXISTS rejection_reason VARCHAR(255) NULL AFTER rejected_at;

ALTER TABLE sales_order
  ADD KEY IF NOT EXISTS idx_sales_order_approval_status (approval_status),
  ADD KEY IF NOT EXISTS idx_sales_order_so_date (so_date);

CREATE TABLE IF NOT EXISTS sales_order_approval (
  id_approval INT(11) NOT NULL AUTO_INCREMENT,
  id_sales_order INT(11) NOT NULL,
  approval_level INT(11) NOT NULL DEFAULT 1,
  approver VARCHAR(100) NULL,
  approver_group VARCHAR(100) NULL,
  status ENUM('PENDING','APPROVED','REJECTED','SKIPPED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  approval_date DATETIME NULL,
  note VARCHAR(255) NULL,
  created_by VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_by VARCHAR(50) NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id_approval),
  KEY idx_soa_so (id_sales_order),
  KEY idx_soa_status (status),
  KEY idx_soa_approver (approver, approver_group),
  CONSTRAINT fk_soa_sales_order
    FOREIGN KEY (id_sales_order) REFERENCES sales_order(id_sales_order)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS sales_order_approval_history (
  id INT(11) NOT NULL AUTO_INCREMENT,
  id_sales_order INT(11) NOT NULL,
  id_approval INT(11) NULL,
  status_lama VARCHAR(50) NULL,
  status_baru VARCHAR(50) NULL,
  remarks VARCHAR(255) NULL,
  changed_by VARCHAR(50) NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_soah_so (id_sales_order),
  KEY idx_soah_approval (id_approval)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE sales_order
SET approval_status = CASE
    WHEN COALESCE(status,'') IN ('APPROVED','RELEASED') THEN 'APPROVED'
    WHEN COALESCE(status,'') IN ('REJECTED') THEN 'REJECTED'
    WHEN COALESCE(approval_status,'') = '' THEN 'SUBMITTED'
    ELSE approval_status
  END,
  submitted_by = COALESCE(submitted_by, user),
  submitted_at = COALESCE(submitted_at, date_created)
WHERE approval_status IS NULL OR approval_status IN ('DRAFT','SUBMITTED','PENDING','APPROVED','REJECTED','CANCELLED');

INSERT INTO sales_order_approval (id_sales_order, approval_level, approver, approver_group, status, created_by)
SELECT so.id_sales_order, 1, NULL, 'sales_manager',
       CASE WHEN so.approval_status='APPROVED' THEN 'APPROVED'
            WHEN so.approval_status='REJECTED' THEN 'REJECTED'
            WHEN so.approval_status='CANCELLED' THEN 'CANCELLED'
            ELSE 'PENDING' END,
       COALESCE(so.user, so.submitted_by)
FROM sales_order so
WHERE NOT EXISTS (
  SELECT 1 FROM sales_order_approval a
  WHERE a.id_sales_order=so.id_sales_order AND a.approval_level=1
);

UPDATE sales_order so
JOIN sales_order_approval a ON a.id_sales_order=so.id_sales_order AND a.approval_level=1
SET so.approval_status='PENDING'
WHERE so.approval_status='SUBMITTED' AND a.status='PENDING';

UPDATE sys_menu
SET nav_act='sales_order_approval',
    main_table='sales_order_approval',
    page_name='Sales Order Approval',
    tampil='Y'
WHERE url='sales-order-approval';

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.group_level, 'Y', 'Y', 'Y', 'N', 'N'
FROM sys_menu m
JOIN (
  SELECT DISTINCT group_level
  FROM sys_menu_role
  WHERE COALESCE(group_level,'') <> ''
) g
WHERE m.url='sales-order-approval'
  AND NOT EXISTS (
    SELECT 1
    FROM sys_menu_role r
    WHERE r.id_menu=m.id AND r.group_level=g.group_level
  );
