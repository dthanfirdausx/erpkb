<?php
if (!function_exists('hr_t')) {
  function hr_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('hr_h')) {
  function hr_h($key, $fallback = '') { return htmlspecialchars((string) hr_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('hr_js')) {
  function hr_js($key, $fallback = '') { return json_encode(hr_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
if (session_status() === PHP_SESSION_NONE) session_start();

function mp_h($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mp_date($value)
{
  if (!$value || $value === '0000-00-00') return '-';
  return date('d M Y', strtotime($value));
}

function mp_text($value, $empty = '-')
{
  $value = trim((string) $value);
  return $value === '' ? $empty : $value;
}

function mp_status_class($status)
{
  $status = strtoupper((string) $status);
  if (in_array($status, array('ACTIVE', 'VERIFIED'), true)) return 'success';
  if (in_array($status, array('PROBATION', 'CONTRACT', 'PENDING'), true)) return 'warning';
  if (in_array($status, array('INACTIVE', 'TERMINATED', 'REJECTED', 'EXPIRED'), true)) return 'danger';
  return 'default';
}

$currentUserId = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
$employee = null;
$families = array();
$educations = array();
$documents = array();

if ($currentUserId > 0) {
  $employee = $db->fetch("SELECT e.*, u.username, u.email user_email, u.foto_user,
      d.nm_dept,
      jt.job_title_code, jt.job_title_name, jt.job_level, jt.job_family,
      cs.structure_code, cs.structure_name, cs.structure_type,
      cc.cost_center_name, pc.profit_center_name,
      m.employee_no manager_no, m.full_name manager_name
    FROM erp_employee_master e
    LEFT JOIN sys_users u ON u.id=e.user_id
    LEFT JOIN dept d ON d.kd_dept=e.department_code
    LEFT JOIN erp_job_title jt ON jt.id=e.job_title_id
    LEFT JOIN erp_company_structure cs ON cs.id=e.company_structure_id
    LEFT JOIN erp_cost_center cc ON cc.cost_center_code=e.cost_center_code
    LEFT JOIN erp_profit_center pc ON pc.profit_center_code=e.profit_center_code
    LEFT JOIN erp_employee_master m ON m.id=e.manager_employee_id
    WHERE e.user_id=? LIMIT 1", array($currentUserId));

  if ($employee) {
    $familyStmt = $db->query("SELECT relationship_type, family_name, gender, birth_date, phone, email, is_dependent, emergency_contact, status
      FROM erp_employee_family_data
      WHERE employee_id=?
      ORDER BY FIELD(relationship_type,'SPOUSE','CHILD','FATHER','MOTHER','SIBLING','GUARDIAN','OTHER'), family_name", array((int) $employee->id));
    $families = $familyStmt ? $familyStmt->fetchAll(PDO::FETCH_OBJ) : array();

    $educationStmt = $db->query("SELECT education_level, education_type, institution_name, major, graduation_year, gpa, highest_education, verified_status, status
      FROM erp_employee_education
      WHERE employee_id=?
      ORDER BY highest_education DESC, graduation_year DESC, institution_name", array((int) $employee->id));
    $educations = $educationStmt ? $educationStmt->fetchAll(PDO::FETCH_OBJ) : array();

    $documentStmt = $db->query("SELECT document_type, document_category, document_title, document_number, issue_date, expiry_date, file_ref, mandatory_document, verification_status, status
      FROM erp_employee_document
      WHERE employee_id=?
      ORDER BY document_category, document_type, document_title", array((int) $employee->id));
    $documents = $documentStmt ? $documentStmt->fetchAll(PDO::FETCH_OBJ) : array();
  }
}

$photoUrl = $employee ? erpkb_user_photo_url($employee->foto_user, 'back_profil_foto') : base_admin().'assets/dist/img/default-user-neutral.svg';
?>

<style>
  .ess-profile-hero {
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
    padding: 24px;
    border-radius: 22px;
    background: linear-gradient(135deg, #0f766e 0%, #0f5f7a 52%, #1e3a8a 100%);
    color: #fff;
    box-shadow: 0 18px 40px rgba(15, 76, 117, .23);
  }
  .ess-profile-hero:after {
    content: "";
    position: absolute;
    right: -70px;
    top: -95px;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.12);
  }
  .ess-profile-head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
  }
  .ess-profile-photo-wrap {
    position: relative;
    width: 116px;
    height: 116px;
    flex: 0 0 116px;
  }
  .ess-profile-photo {
    width: 116px;
    height: 116px;
    object-fit: cover;
    border-radius: 28px;
    background: #f8fafc;
    border: 4px solid rgba(255,255,255,.72);
    box-shadow: 0 12px 28px rgba(15,23,42,.22);
  }
  .ess-photo-btn {
    position: absolute;
    right: -4px;
    bottom: -4px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f59e0b;
    color: #fff;
    border: 3px solid #fff;
    box-shadow: 0 10px 18px rgba(15,23,42,.24);
    cursor: pointer;
  }
  .ess-profile-title h1 {
    margin: 0 0 7px;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.02em;
  }
  .ess-profile-title p {
    margin: 0;
    color: rgba(255,255,255,.83);
  }
  .ess-profile-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
  }
  .ess-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.15);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
  }
  .ess-card {
    border: 1px solid #e5edf5;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 10px 26px rgba(15,23,42,.055);
    margin-bottom: 18px;
  }
  .ess-card .box-header {
    padding: 17px 20px 10px;
    border-bottom: 0;
  }
  .ess-card .box-title {
    font-weight: 800;
    color: #0f172a;
  }
  .ess-card .box-body {
    padding: 16px 20px 20px;
  }
  .ess-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
  .ess-info-item {
    padding: 12px 13px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
  }
  .ess-info-label {
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 800;
    margin-bottom: 4px;
  }
  .ess-info-value {
    color: #0f172a;
    font-weight: 700;
    word-break: break-word;
  }
  .ess-stat {
    padding: 16px;
    border-radius: 18px;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid #e5edf5;
    min-height: 94px;
  }
  .ess-stat i {
    font-size: 20px;
    color: #0f766e;
    margin-bottom: 9px;
  }
  .ess-stat strong {
    display: block;
    font-size: 18px;
    margin-bottom: 2px;
  }
  .ess-tab-card .nav-tabs {
    padding: 0 16px;
    border-bottom-color: #e5edf5;
  }
  .ess-tab-card .nav-tabs > li > a {
    border-radius: 12px 12px 0 0;
    font-weight: 800;
    color: #475569;
  }
  .ess-empty {
    padding: 24px;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    color: #64748b;
    text-align: center;
    background: #f8fafc;
  }
  .ess-table > thead > tr > th {
    background: #f8fafc;
    color: #334155;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .03em;
  }
  .ess-profile-filter .form-group { margin-bottom: 10px; }
  #ess_profile_checklist_table th,
  #ess_profile_checklist_table td { font-size: 12px; vertical-align: middle !important; }
  .ess-upload-preview {
    display: flex;
    gap: 14px;
    align-items: center;
    padding: 12px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e5edf5;
    margin-bottom: 14px;
  }
  .ess-upload-preview img {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 18px;
    border: 2px solid #fff;
    box-shadow: 0 6px 18px rgba(15,23,42,.12);
  }
  @media (max-width: 767px) {
    .ess-profile-head { align-items: flex-start; }
    .ess-profile-title h1 { font-size: 23px; }
    .ess-info-grid { grid-template-columns: 1fr; }
  }
</style>

<section class="content-header">
  <h1><?=hr_h('hr_my_profile', 'My Profile');?> <small>Employee Self Service</small></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=hr_h('common_home', 'Home');?></a></li>
    <li>Employee Self Service</li>
    <li class="active"><?=hr_h('hr_my_profile', 'My Profile');?></li>
  </ol>
</section>

<section class="content">
  <?php if (!$employee): ?>
    <div class="alert alert-warning">
      <i class="fa fa-warning"></i>
      Data employee untuk user ini belum ditemukan. Pastikan user login sudah dihubungkan ke `erp_employee_master.user_id`.
    </div>
  <?php else: ?>
    <div class="ess-profile-hero">
      <div class="ess-profile-head">
        <div class="ess-profile-photo-wrap">
          <img id="essProfilePhoto" src="<?=mp_h($photoUrl);?>" class="ess-profile-photo" alt="Profile Photo">
          <button type="button" class="ess-photo-btn" id="btnChangePhoto" title="Ganti foto">
            <i class="fa fa-camera"></i>
          </button>
        </div>
        <div class="ess-profile-title">
          <h1><?=mp_h($employee->full_name);?></h1>
          <p><?=mp_h($employee->employee_no);?> &bull; <?=mp_h(mp_text($employee->job_title_name));?> &bull; <?=mp_h(mp_text($employee->nm_dept));?></p>
          <div class="ess-profile-badges">
            <span class="ess-pill"><i class="fa fa-id-badge"></i> <?=mp_h($employee->employment_status);?></span>
            <span class="ess-pill"><i class="fa fa-map-marker"></i> <?=mp_h($employee->work_location_type);?></span>
            <span class="ess-pill"><i class="fa fa-calendar-check-o"></i> Join <?=mp_h(mp_date($employee->hire_date));?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 col-sm-6">
        <div class="ess-stat"><i class="fa fa-building-o"></i><strong><?=mp_h(mp_text($employee->structure_name));?></strong><span class="text-muted"><?=mp_h(mp_text($employee->structure_type));?></span></div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="ess-stat"><i class="fa fa-sitemap"></i><strong><?=mp_h(mp_text($employee->cost_center_code));?></strong><span class="text-muted"><?=mp_h(mp_text($employee->cost_center_name));?></span></div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="ess-stat"><i class="fa fa-line-chart"></i><strong><?=mp_h(mp_text($employee->profit_center_code));?></strong><span class="text-muted"><?=mp_h(mp_text($employee->profit_center_name));?></span></div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="ess-stat"><i class="fa fa-user-circle-o"></i><strong><?=mp_h(mp_text($employee->manager_name));?></strong><span class="text-muted"><?=mp_h(mp_text($employee->manager_no));?></span></div>
      </div>
    </div>

    <div class="box ess-card">
      <div class="box-header"><h3 class="box-title"><i class="fa fa-check-square-o text-teal"></i> Profile Data Checklist</h3></div>
      <div class="box-body">
        <form class="form-horizontal ess-profile-filter" onsubmit="return false;">
          <div class="form-group">
            <label class="control-label col-md-2">Category</label>
            <div class="col-md-4">
              <select id="ess_profile_category" class="form-control select2">
                <option value="">All</option>
                <option value="Personal">Personal</option>
                <option value="Organization">Organization</option>
                <option value="Contact">Contact</option>
                <option value="Document"><?=hr_h('hr_document', 'Document');?></option>
              </select>
            </div>
            <div class="col-md-6">
              <button type="button" id="ess_profile_reset" class="btn btn-default"><i class="fa fa-refresh"></i> <?=hr_h('common_reset', 'Reset');?></button>
            </div>
          </div>
        </form>
        <div class="table-responsive">
          <table id="ess_profile_checklist_table" class="table table-bordered table-striped table-hover ess-table">
            <thead><tr><th>Category</th><th>Data</th><th><?=hr_h('common_status', 'Status');?></th><th>Value</th><th><?=hr_h('common_action', 'Action');?></th></tr></thead>
            <tbody>
              <tr><td>Personal</td><td>Identity</td><td><span class="label label-<?=mp_text($employee->identity_no,'')===''?'warning':'success';?>"><?=mp_text($employee->identity_no,'')===''?'Need Update':'Complete';?></span></td><td><?=mp_h(mp_text($employee->identity_no));?></td><td><button type="button" class="btn btn-info btn-xs ess-profile-detail" data-category="Personal" data-field="Identity" data-value="<?=mp_h(mp_text($employee->identity_no));?>"><i class="fa fa-eye"></i></button></td></tr>
              <tr><td>Contact</td><td>Email</td><td><span class="label label-<?=mp_text($employee->email ?: $employee->user_email,'')===''?'warning':'success';?>"><?=mp_text($employee->email ?: $employee->user_email,'')===''?'Need Update':'Complete';?></span></td><td><?=mp_h(mp_text($employee->email ?: $employee->user_email));?></td><td><button type="button" class="btn btn-info btn-xs ess-profile-detail" data-category="Contact" data-field="Email" data-value="<?=mp_h(mp_text($employee->email ?: $employee->user_email));?>"><i class="fa fa-eye"></i></button></td></tr>
              <tr><td>Contact</td><td>Phone</td><td><span class="label label-<?=mp_text($employee->phone,'')===''?'warning':'success';?>"><?=mp_text($employee->phone,'')===''?'Need Update':'Complete';?></span></td><td><?=mp_h(mp_text($employee->phone));?></td><td><button type="button" class="btn btn-info btn-xs ess-profile-detail" data-category="Contact" data-field="Phone" data-value="<?=mp_h(mp_text($employee->phone));?>"><i class="fa fa-eye"></i></button></td></tr>
              <tr><td>Organization</td><td><?=hr_h('hr_department', 'Department');?></td><td><span class="label label-success">Complete</span></td><td><?=mp_h(mp_text($employee->department_code));?> - <?=mp_h(mp_text($employee->nm_dept));?></td><td><button type="button" class="btn btn-info btn-xs ess-profile-detail" data-category="Organization" data-field="Department" data-value="<?=mp_h(mp_text($employee->department_code).' - '.mp_text($employee->nm_dept));?>"><i class="fa fa-eye"></i></button></td></tr>
              <tr><td><?=hr_h('hr_document', 'Document');?></td><td>Employee Documents</td><td><span class="label label-<?=count($documents)>0?'success':'warning';?>"><?=count($documents)>0?'Available':'Need Upload';?></span></td><td><?=number_format(count($documents),0,',','.');?> document(s)</td><td><button type="button" class="btn btn-info btn-xs ess-profile-detail" data-category="Document" data-field="Employee Documents" data-value="<?=number_format(count($documents),0,',','.');?> document(s)"><i class="fa fa-eye"></i></button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="box ess-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-user text-teal"></i> Data Personal</h3></div>
          <div class="box-body">
            <div class="ess-info-grid">
              <div class="ess-info-item"><div class="ess-info-label">Nama Lengkap</div><div class="ess-info-value"><?=mp_h($employee->full_name);?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Gender</div><div class="ess-info-value"><?=mp_h($employee->gender);?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Tempat / Tgl Lahir</div><div class="ess-info-value"><?=mp_h(mp_text($employee->birth_place));?> / <?=mp_h(mp_date($employee->birth_date));?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Status Pernikahan</div><div class="ess-info-value"><?=mp_h(mp_text($employee->marital_status));?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Identitas</div><div class="ess-info-value"><?=mp_h($employee->identity_type);?> - <?=mp_h(mp_text($employee->identity_no));?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">NPWP</div><div class="ess-info-value"><?=mp_h(mp_text($employee->tax_no));?></div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="box ess-card">
          <div class="box-header"><h3 class="box-title"><i class="fa fa-address-book text-teal"></i> Kontak & Emergency</h3></div>
          <div class="box-body">
            <div class="ess-info-grid">
              <div class="ess-info-item"><div class="ess-info-label">Email</div><div class="ess-info-value"><?=mp_h(mp_text($employee->email ?: $employee->user_email));?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Telepon</div><div class="ess-info-value"><?=mp_h(mp_text($employee->phone));?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Kota / Kode Pos</div><div class="ess-info-value"><?=mp_h(mp_text($employee->city));?> / <?=mp_h(mp_text($employee->postal_code));?></div></div>
              <div class="ess-info-item"><div class="ess-info-label">Kontak Darurat</div><div class="ess-info-value"><?=mp_h(mp_text($employee->emergency_contact_name));?> - <?=mp_h(mp_text($employee->emergency_contact_phone));?></div></div>
              <div class="ess-info-item" style="grid-column:1/-1;"><div class="ess-info-label">Alamat</div><div class="ess-info-value"><?=mp_h(mp_text($employee->address));?></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="box ess-card">
      <div class="box-header"><h3 class="box-title"><i class="fa fa-briefcase text-teal"></i> Assignment Organisasi</h3></div>
      <div class="box-body">
        <div class="ess-info-grid">
          <div class="ess-info-item"><div class="ess-info-label"><?=hr_h('hr_department', 'Department');?></div><div class="ess-info-value"><?=mp_h(mp_text($employee->department_code));?> - <?=mp_h(mp_text($employee->nm_dept));?></div></div>
          <div class="ess-info-item"><div class="ess-info-label"><?=hr_h('hr_job_title', 'Job Title');?></div><div class="ess-info-value"><?=mp_h(mp_text($employee->job_title_code));?> - <?=mp_h(mp_text($employee->job_title_name));?> (<?=mp_h(mp_text($employee->job_level));?>)</div></div>
          <div class="ess-info-item"><div class="ess-info-label">Employee Group</div><div class="ess-info-value"><?=mp_h($employee->employee_group);?> / <?=mp_h(mp_text($employee->employee_subgroup));?></div></div>
          <div class="ess-info-item"><div class="ess-info-label">Payroll Area / Pay Grade</div><div class="ess-info-value"><?=mp_h(mp_text($employee->payroll_area));?> / <?=mp_h(mp_text($employee->pay_grade));?></div></div>
          <div class="ess-info-item"><div class="ess-info-label"><?=hr_h('hr_shift', 'Shift');?></div><div class="ess-info-value"><?=mp_h(mp_text($employee->shift_code));?></div></div>
          <div class="ess-info-item"><div class="ess-info-label">Valid Period</div><div class="ess-info-value"><?=mp_h(mp_date($employee->valid_from));?> s/d <?=mp_h(mp_date($employee->valid_to));?></div></div>
        </div>
      </div>
    </div>

    <div class="box ess-card ess-tab-card">
      <div class="box-header"><h3 class="box-title"><i class="fa fa-folder-open text-teal"></i> Detail Employee</h3></div>
      <div class="box-body" style="padding-top:0;">
        <ul class="nav nav-tabs" role="tablist">
          <li class="active"><a href="#tabFamily" data-toggle="tab">Family</a></li>
          <li><a href="#tabEducation" data-toggle="tab">Education</a></li>
          <li><a href="#tabDocument" data-toggle="tab"><?=hr_h('hr_document', 'Document');?></a></li>
        </ul>
        <div class="tab-content" style="padding-top:16px;">
          <div class="tab-pane active" id="tabFamily">
            <?php if (count($families) === 0): ?>
              <div class="ess-empty">Belum ada data keluarga.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover ess-table">
                  <thead><tr><th>Relasi</th><th>Nama</th><th>Gender</th><th>Tgl Lahir</th><th>Phone</th><th>Dependent</th><th>Emergency</th><th><?=hr_h('common_status', 'Status');?></th></tr></thead>
                  <tbody>
                    <?php foreach ($families as $row): ?>
                      <tr>
                        <td><?=mp_h($row->relationship_type);?></td>
                        <td><strong><?=mp_h($row->family_name);?></strong></td>
                        <td><?=mp_h($row->gender);?></td>
                        <td><?=mp_h(mp_date($row->birth_date));?></td>
                        <td><?=mp_h(mp_text($row->phone));?></td>
                        <td><span class="label label-<?=($row->is_dependent === 'Y' ? 'success' : 'default');?>"><?=mp_h($row->is_dependent);?></span></td>
                        <td><span class="label label-<?=($row->emergency_contact === 'Y' ? 'warning' : 'default');?>"><?=mp_h($row->emergency_contact);?></span></td>
                        <td><span class="label label-<?=mp_status_class($row->status);?>"><?=mp_h($row->status);?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
          <div class="tab-pane" id="tabEducation">
            <?php if (count($educations) === 0): ?>
              <div class="ess-empty">Belum ada data pendidikan.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover ess-table">
                  <thead><tr><th>Level</th><th>Institusi</th><th>Jurusan</th><th>Tahun Lulus</th><th>GPA</th><th>Highest</th><th>Verified</th><th><?=hr_h('common_status', 'Status');?></th></tr></thead>
                  <tbody>
                    <?php foreach ($educations as $row): ?>
                      <tr>
                        <td><?=mp_h($row->education_level);?></td>
                        <td><strong><?=mp_h($row->institution_name);?></strong><br><small class="text-muted"><?=mp_h($row->education_type);?></small></td>
                        <td><?=mp_h(mp_text($row->major));?></td>
                        <td><?=mp_h(mp_text($row->graduation_year));?></td>
                        <td><?=mp_h(mp_text($row->gpa));?></td>
                        <td><span class="label label-<?=($row->highest_education === 'Y' ? 'success' : 'default');?>"><?=mp_h($row->highest_education);?></span></td>
                        <td><span class="label label-<?=mp_status_class($row->verified_status);?>"><?=mp_h($row->verified_status);?></span></td>
                        <td><span class="label label-<?=mp_status_class($row->status);?>"><?=mp_h($row->status);?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
          <div class="tab-pane" id="tabDocument">
            <?php if (count($documents) === 0): ?>
              <div class="ess-empty">Belum ada data dokumen.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-hover ess-table">
                  <thead><tr><th>Jenis</th><th>Judul</th><th>No Dokumen</th><th>Issue</th><th>Expired</th><th>Mandatory</th><th>Verified</th><th><?=hr_h('hr_file', 'File');?></th></tr></thead>
                  <tbody>
                    <?php foreach ($documents as $row): ?>
                      <tr>
                        <td><?=mp_h($row->document_type);?><br><small class="text-muted"><?=mp_h($row->document_category);?></small></td>
                        <td><strong><?=mp_h($row->document_title);?></strong></td>
                        <td><?=mp_h(mp_text($row->document_number));?></td>
                        <td><?=mp_h(mp_date($row->issue_date));?></td>
                        <td><?=mp_h(mp_date($row->expiry_date));?></td>
                        <td><span class="label label-<?=($row->mandatory_document === 'Y' ? 'warning' : 'default');?>"><?=mp_h($row->mandatory_document);?></span></td>
                        <td><span class="label label-<?=mp_status_class($row->verification_status);?>"><?=mp_h($row->verification_status);?></span></td>
                        <td>
                          <?php if (trim((string) $row->file_ref) !== ''): ?>
                            <a href="<?=base_url().mp_h(ltrim($row->file_ref, '/'));?>" target="_blank" class="btn btn-xs btn-default"><i class="fa fa-paperclip"></i> Open</a>
                          <?php else: ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalChangePhoto" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form id="formChangePhoto" enctype="multipart/form-data">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title"><i class="fa fa-camera"></i> Ganti Foto Profil</h4>
            </div>
            <div class="modal-body">
              <div class="ess-upload-preview">
                <img id="photoPreview" src="<?=mp_h($photoUrl);?>" alt="Preview">
                <div>
                  <strong>Upload foto terbaru</strong>
                  <p class="text-muted" style="margin:4px 0 0;">Format jpg, jpeg, png, gif, atau webp. Maksimal 3MB.</p>
                </div>
              </div>
              <div class="form-group">
                <label>File Foto <span class="text-danger">*</span></label>
                <input type="file" name="foto_user" id="foto_user" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
              </div>
              <div class="alert alert-danger" id="photoError" style="display:none;"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary" id="btnSavePhoto"><i class="fa fa-save"></i> Simpan Foto</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="modal fade" id="ess_profile_detail_modal" tabindex="-1" role="dialog">
      <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-primary"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-eye"></i> Profile Checklist Detail</h4></div>
        <div class="modal-body"><table class="table table-bordered table-condensed"><tbody id="ess_profile_detail_body"></tbody></table></div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
      </div></div>
    </div>
  <?php endif; ?>
</section>

<script>
$(function(){
  if ($.fn.select2) {
    $('#ess_profile_category').select2({width:'100%', allowClear:true});
  }
  var essProfileTable = $('#ess_profile_checklist_table').length ? $('#ess_profile_checklist_table').DataTable({
    pageLength: 10,
    dom: 'Bfrtip',
    buttons: [
      {extend:'excelHtml5', text:'<i class="fa fa-file-excel-o"></i> Export Excel', className:'btn btn-success btn-sm', title:'My Profile Checklist'},
      {extend:'print', text:'<i class="fa fa-print"></i> Print', className:'btn btn-default btn-sm', title:'My Profile Checklist'}
    ],
    columnDefs: [{targets:[4], orderable:false, searchable:false, className:'text-center'}]
  }) : null;
  $('#ess_profile_category').on('change', function(){
    if (essProfileTable) essProfileTable.column(0).search(this.value).draw();
  });
  $('#ess_profile_reset').on('click', function(){
    $('#ess_profile_category').val('').trigger('change');
    if (essProfileTable) essProfileTable.search('').columns().search('').draw();
  });
  $(document).on('click','.ess-profile-detail',function(){
    var b=$(this),rows=[['Category',b.data('category')],['Field',b.data('field')],['Value',b.data('value')]],html='';
    $.each(rows,function(_,r){html+='<tr><th style="width:34%;background:#f7f9fb">'+$('<div>').text(r[0]).html()+'</th><td>'+$('<div>').text(r[1]||'-').html()+'</td></tr>';});
    $('#ess_profile_detail_body').html(html);$('#ess_profile_detail_modal').modal('show');
  });
  $('#btnChangePhoto').on('click', function(){
    $('#photoError').hide().text('');
    $('#modalChangePhoto').modal({backdrop:'static', keyboard:false});
  });

  $('#foto_user').on('change', function(){
    var file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) {
      $('#photoError').text('Ukuran foto maksimal 3MB.').show();
      this.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e){ $('#photoPreview').attr('src', e.target.result); };
    reader.readAsDataURL(file);
  });

  $('#formChangePhoto').on('submit', function(e){
    e.preventDefault();
    $('#photoError').hide().text('');
    var formData = new FormData(this);
    $('#btnSavePhoto').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $.ajax({
      url: '<?=base_admin();?>modul/my_profile/my_profile_action.php?act=upload_photo',
      type: 'POST',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      dataType: 'json',
      success: function(resp){
        var row = $.isArray(resp) ? resp[0] : resp;
        if (row && row.status === 'good') {
          var photoUrl = row.photo_url + (row.photo_url.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now();
          $('#essProfilePhoto, #photoPreview, .user-image, .erpkb-user-dropdown-avatar, .user-panel img').attr('src', photoUrl);
          $('#modalChangePhoto').modal('hide');
          if (typeof toastr !== 'undefined') toastr.success(row.message || 'Foto profil berhasil diperbarui.');
        } else {
          $('#photoError').text((row && row.error_message) ? row.error_message : 'Foto gagal disimpan.').show();
        }
      },
      error: function(xhr){
        $('#photoError').text(xhr.responseText || 'Terjadi kesalahan saat upload foto.').show();
      },
      complete: function(){
        $('#btnSavePhoto').prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Foto');
      }
    });
  });
});
</script>
