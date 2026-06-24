INSERT INTO sys_group_users (level, level_name, deskripsi)
SELECT 'employee_self_service', 'Employee Self Service', 'Akun karyawan untuk akses ESS pribadi'
WHERE NOT EXISTS (SELECT 1 FROM sys_group_users WHERE level='employee_self_service');

SET @ess_group_id := (SELECT id FROM sys_group_users WHERE level='employee_self_service' LIMIT 1);
SET @default_password := 'employee123';
SET @default_password_hash := MD5(@default_password);

INSERT INTO sys_users
(first_name,last_name,username,password,plain_pass,email,lang,date_created,foto_user,group_level,aktif)
SELECT
  SUBSTRING_INDEX(e.full_name,' ',1) AS first_name,
  TRIM(SUBSTRING(e.full_name, LENGTH(SUBSTRING_INDEX(e.full_name,' ',1))+1)) AS last_name,
  LOWER(e.employee_no) AS username,
  @default_password_hash AS password,
  @default_password AS plain_pass,
  CONCAT(LOWER(e.employee_no),'@erpkb.local') AS email,
  'en' AS lang,
  CURDATE() AS date_created,
  'default_user.png' AS foto_user,
  @ess_group_id AS group_level,
  'Y' AS aktif
FROM erp_employee_master e
LEFT JOIN sys_users u ON u.username=LOWER(e.employee_no)
WHERE e.user_id IS NULL
  AND e.employment_status IN ('ACTIVE','PROBATION','CONTRACT')
  AND u.id IS NULL;

UPDATE erp_employee_master e
JOIN sys_users u ON u.username=LOWER(e.employee_no)
SET e.user_id=u.id,
    e.updated_by='admin',
    e.updated_at=NOW()
WHERE e.user_id IS NULL
  AND u.group_level=@ess_group_id;
