<?php
function approval_center_sync_pr_history_approvals()
{
  global $db;

  return $db->query(
    "INSERT INTO purchase_requisition_approval (id_pr, approval_level, approver, status, note)
     SELECT pr.id_pr,
            1 AS approval_level,
            CASE
              WHEN pr.priority IN ('HIGH','URGENT') THEN 'manager_approver'
              ELSE 'purchasing'
            END AS approver,
            'PENDING' AS status,
            CONCAT('Auto-synced from purchase_requisition_history #', h.id) AS note
     FROM purchase_requisition pr
     JOIN (
       SELECT h1.*
       FROM purchase_requisition_history h1
       JOIN (
         SELECT id_pr, MAX(id) AS max_id
         FROM purchase_requisition_history
         WHERE status_baru='SUBMITTED'
         GROUP BY id_pr
       ) hx ON hx.id_pr=h1.id_pr AND hx.max_id=h1.id
     ) h ON h.id_pr=pr.id_pr
     WHERE pr.status='SUBMITTED'
       AND NOT EXISTS (
         SELECT 1
         FROM purchase_requisition_approval a
         WHERE a.id_pr=pr.id_pr
           AND a.status='PENDING'
       )"
  );
}
?>
