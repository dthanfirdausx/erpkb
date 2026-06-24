INSERT INTO sys_group_users (level, level_name, deskripsi)
SELECT 'hrd', 'Human Resource', 'Group user untuk modul HRD / Human Capital'
WHERE NOT EXISTS (SELECT 1 FROM sys_group_users WHERE level='hrd');

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Human Resource', '', '', 'fa-users', 13, 0, '', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Human Resource' AND parent=0);

SET @hrd_parent := (SELECT id FROM sys_menu WHERE page_name='Human Resource' AND parent=0 ORDER BY id DESC LIMIT 1);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Organization Management', '', '', 'fa-sitemap', 1, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Organization Management' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Personnel Administration', '', '', 'fa-id-card', 2, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Personnel Administration' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Time Management', '', '', 'fa-clock-o', 3, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Time Management' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Payroll', '', '', 'fa-money', 4, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Payroll' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Recruitment', '', '', 'fa-user-plus', 5, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Recruitment' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Training & Development', '', '', 'fa-graduation-cap', 6, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Training & Development' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Performance Management', '', '', 'fa-line-chart', 7, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Performance Management' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Employee Self Service', '', '', 'fa-user', 8, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Employee Self Service' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'Manager Self Service', '', '', 'fa-users', 9, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='Manager Self Service' AND parent=@hrd_parent);
INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT '', 'HR Reports', '', '', 'fa-file-text-o', 10, @hrd_parent, 'Human Resource', 'N', 'Y', 'main'
WHERE NOT EXISTS (SELECT 1 FROM sys_menu WHERE page_name='HR Reports' AND parent=@hrd_parent);

SET @org := (SELECT id FROM sys_menu WHERE page_name='Organization Management' AND parent=@hrd_parent LIMIT 1);
SET @pa := (SELECT id FROM sys_menu WHERE page_name='Personnel Administration' AND parent=@hrd_parent LIMIT 1);
SET @time := (SELECT id FROM sys_menu WHERE page_name='Time Management' AND parent=@hrd_parent LIMIT 1);
SET @payroll := (SELECT id FROM sys_menu WHERE page_name='Payroll' AND parent=@hrd_parent LIMIT 1);
SET @recruit := (SELECT id FROM sys_menu WHERE page_name='Recruitment' AND parent=@hrd_parent LIMIT 1);
SET @training := (SELECT id FROM sys_menu WHERE page_name='Training & Development' AND parent=@hrd_parent LIMIT 1);
SET @perf := (SELECT id FROM sys_menu WHERE page_name='Performance Management' AND parent=@hrd_parent LIMIT 1);
SET @ess := (SELECT id FROM sys_menu WHERE page_name='Employee Self Service' AND parent=@hrd_parent LIMIT 1);
SET @mss := (SELECT id FROM sys_menu WHERE page_name='Manager Self Service' AND parent=@hrd_parent LIMIT 1);
SET @reports := (SELECT id FROM sys_menu WHERE page_name='HR Reports' AND parent=@hrd_parent LIMIT 1);

INSERT INTO sys_menu (nav_act, page_name, url, main_table, icon, urutan_menu, parent, parent_name, dt_table, tampil, type_menu)
SELECT 'hrd_dummy', v.page_name, v.url, '', v.icon, v.urutan_menu, v.parent_id, v.parent_name, 'N', 'Y', 'page'
FROM (
  SELECT 'Company Structure' page_name, 'company-structure' url, 'fa-building' icon, 1 urutan_menu, @org parent_id, 'Organization Management' parent_name UNION ALL
  SELECT 'Department','hrd-department','fa-sitemap',2,@org,'Organization Management' UNION ALL
  SELECT 'Position','position','fa-briefcase',3,@org,'Organization Management' UNION ALL
  SELECT 'Job Title','job-title','fa-id-badge',4,@org,'Organization Management' UNION ALL
  SELECT 'Work Location','work-location','fa-map-marker',5,@org,'Organization Management' UNION ALL
  SELECT 'Employee Master Data','employee-master-data','fa-address-card',1,@pa,'Personnel Administration' UNION ALL
  SELECT 'Employee Contract','employee-contract','fa-file-text',2,@pa,'Personnel Administration' UNION ALL
  SELECT 'Employee Family Data','employee-family-data','fa-users',3,@pa,'Personnel Administration' UNION ALL
  SELECT 'Employee Education','employee-education','fa-graduation-cap',4,@pa,'Personnel Administration' UNION ALL
  SELECT 'Employee Document','employee-document','fa-folder-open',5,@pa,'Personnel Administration' UNION ALL
  SELECT 'Employee Mutation','employee-mutation','fa-exchange',6,@pa,'Personnel Administration' UNION ALL
  SELECT 'Work Schedule','work-schedule','fa-calendar',1,@time,'Time Management' UNION ALL
  SELECT 'Shift Schedule','shift-schedule','fa-clock-o',2,@time,'Time Management' UNION ALL
  SELECT 'Attendance','attendance','fa-check-square-o',3,@time,'Time Management' UNION ALL
  SELECT 'Overtime','overtime','fa-hourglass-half',4,@time,'Time Management' UNION ALL
  SELECT 'Leave Request','leave-request','fa-calendar-plus-o',5,@time,'Time Management' UNION ALL
  SELECT 'Leave Approval','leave-approval','fa-check',6,@time,'Time Management' UNION ALL
  SELECT 'Holiday Calendar','holiday-calendar','fa-calendar-times-o',7,@time,'Time Management' UNION ALL
  SELECT 'Payroll Component','payroll-component','fa-list-alt',1,@payroll,'Payroll' UNION ALL
  SELECT 'Salary Structure','salary-structure','fa-money',2,@payroll,'Payroll' UNION ALL
  SELECT 'Payroll Process','payroll-process','fa-cogs',3,@payroll,'Payroll' UNION ALL
  SELECT 'Payroll Posting','payroll-posting','fa-book',4,@payroll,'Payroll' UNION ALL
  SELECT 'Payslip','payslip','fa-file-pdf-o',5,@payroll,'Payroll' UNION ALL
  SELECT 'Payroll History','payroll-history','fa-history',6,@payroll,'Payroll' UNION ALL
  SELECT 'Manpower Planning','manpower-planning','fa-users',1,@recruit,'Recruitment' UNION ALL
  SELECT 'Job Vacancy','job-vacancy','fa-bullhorn',2,@recruit,'Recruitment' UNION ALL
  SELECT 'Applicant Data','applicant-data','fa-user-plus',3,@recruit,'Recruitment' UNION ALL
  SELECT 'Interview Schedule','interview-schedule','fa-calendar-check-o',4,@recruit,'Recruitment' UNION ALL
  SELECT 'Selection Result','selection-result','fa-check-circle',5,@recruit,'Recruitment' UNION ALL
  SELECT 'Hiring','hiring','fa-handshake-o',6,@recruit,'Recruitment' UNION ALL
  SELECT 'Training Catalog','training-catalog','fa-book',1,@training,'Training & Development' UNION ALL
  SELECT 'Training Plan','training-plan','fa-calendar',2,@training,'Training & Development' UNION ALL
  SELECT 'Training Registration','training-registration','fa-pencil-square-o',3,@training,'Training & Development' UNION ALL
  SELECT 'Training Result','training-result','fa-bar-chart',4,@training,'Training & Development' UNION ALL
  SELECT 'Certification','certification','fa-certificate',5,@training,'Training & Development' UNION ALL
  SELECT 'KPI Template','kpi-template','fa-sliders',1,@perf,'Performance Management' UNION ALL
  SELECT 'Employee KPI','employee-kpi','fa-line-chart',2,@perf,'Performance Management' UNION ALL
  SELECT 'Performance Appraisal','performance-appraisal','fa-star-half-o',3,@perf,'Performance Management' UNION ALL
  SELECT 'Appraisal Approval','appraisal-approval','fa-check-square',4,@perf,'Performance Management' UNION ALL
  SELECT 'Performance History','performance-history','fa-history',5,@perf,'Performance Management' UNION ALL
  SELECT 'My Profile','my-profile','fa-user',1,@ess,'Employee Self Service' UNION ALL
  SELECT 'My Attendance','my-attendance','fa-clock-o',2,@ess,'Employee Self Service' UNION ALL
  SELECT 'My Leave','my-leave','fa-calendar-minus-o',3,@ess,'Employee Self Service' UNION ALL
  SELECT 'My Payslip','my-payslip','fa-file-text-o',4,@ess,'Employee Self Service' UNION ALL
  SELECT 'My Request','my-request','fa-inbox',5,@ess,'Employee Self Service' UNION ALL
  SELECT 'Team Attendance','team-attendance','fa-users',1,@mss,'Manager Self Service' UNION ALL
  SELECT 'Team Leave Approval','team-leave-approval','fa-calendar-check-o',2,@mss,'Manager Self Service' UNION ALL
  SELECT 'Team Overtime Approval','team-overtime-approval','fa-hourglass',3,@mss,'Manager Self Service' UNION ALL
  SELECT 'Team Performance','team-performance','fa-line-chart',4,@mss,'Manager Self Service' UNION ALL
  SELECT 'Team Request Approval','team-request-approval','fa-check-square-o',5,@mss,'Manager Self Service' UNION ALL
  SELECT 'Employee Report','employee-report','fa-file-text-o',1,@reports,'HR Reports' UNION ALL
  SELECT 'Attendance Report','attendance-report','fa-file-text-o',2,@reports,'HR Reports' UNION ALL
  SELECT 'Overtime Report','overtime-report','fa-file-text-o',3,@reports,'HR Reports' UNION ALL
  SELECT 'Leave Report','leave-report','fa-file-text-o',4,@reports,'HR Reports' UNION ALL
  SELECT 'Payroll Report','payroll-report','fa-file-text-o',5,@reports,'HR Reports' UNION ALL
  SELECT 'Training Report','training-report','fa-file-text-o',6,@reports,'HR Reports' UNION ALL
  SELECT 'Performance Report','performance-report','fa-file-text-o',7,@reports,'HR Reports'
) v
WHERE NOT EXISTS (SELECT 1 FROM sys_menu m WHERE m.url=v.url);

INSERT INTO sys_menu_role (id_menu, group_level, read_act, insert_act, update_act, delete_act, import_act)
SELECT m.id, g.level, 'Y',
  CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator','hrd') THEN 'Y' ELSE 'N' END,
  CASE WHEN g.level IN ('admin','system_administrator') THEN 'Y' ELSE 'N' END,
  'N'
FROM sys_menu m
JOIN sys_group_users g ON g.level IN ('admin','system_administrator','manager_approver','auditor','hrd')
WHERE (
  m.id=@hrd_parent OR m.parent=@hrd_parent OR m.parent IN (@org,@pa,@time,@payroll,@recruit,@training,@perf,@ess,@mss,@reports)
)
AND NOT EXISTS (
  SELECT 1 FROM sys_menu_role r WHERE r.id_menu=m.id AND r.group_level=g.level
);
