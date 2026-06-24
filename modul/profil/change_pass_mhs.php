<?php
$data_edit = $db->fetch_single_row('sys_users', 'id', $_SESSION['id_user']);
if (empty($_SESSION['change_password_csrf_token'])) {
  $_SESSION['change_password_csrf_token'] = bin2hex(random_bytes(32));
}
$displayName = trim(($data_edit->first_name ?: '').' '.($data_edit->last_name ?: ''));
if ($displayName === '') {
  $displayName = $data_edit->username;
}
$passwordLang = erp_lang_bundle(array(
  'password_change_title' => 'Change Password',
  'password_profile' => 'Profile',
  'password_update_account' => 'Update Account Password',
  'password_update_intro' => 'Use a strong password to keep ERP access, transactions, and operational data secure.',
  'password_change_card_title' => 'Change Password',
  'password_change_card_subtitle' => 'After the password is changed successfully, the system will ask you to sign in again.',
  'password_old' => 'Current Password',
  'password_new' => 'New Password',
  'password_confirm' => 'Repeat New Password',
  'password_show_old' => 'Show current password',
  'password_show_new' => 'Show new password',
  'password_show_confirm' => 'Show password confirmation',
  'password_hint_default' => 'Minimum 8 characters, use a combination of letters and numbers.',
  'password_save' => 'Save Password',
  'password_saving' => 'Saving...',
  'password_back_profile' => 'Back to Profile',
  'password_tips_title' => 'Safe Password Tips',
  'password_tip_minimum' => 'Use at least 8 characters.',
  'password_tip_mix' => 'Mix uppercase letters, lowercase letters, numbers, and symbols.',
  'password_tip_unique' => 'Do not use the same password as other applications.',
  'password_tip_private' => 'Do not share your password with other users.',
  'password_strength_empty' => 'Password has not been filled.',
  'password_strength_weak' => 'Password is still weak.',
  'password_strength_medium' => 'Password is acceptable, add more character variation.',
  'password_strength_strong' => 'Password is strong.',
  'password_strength_very_strong' => 'Password is very strong.',
  'password_required' => 'All password fields are required.',
  'password_min_length' => 'New password must be at least 8 characters.',
  'password_too_weak' => 'New password is too weak. Use a combination of letters and numbers.',
  'password_confirm_mismatch' => 'New password confirmation does not match.',
  'password_same_as_old' => 'New password cannot be the same as the old password.',
  'password_success_redirect' => 'Password changed successfully. You will be redirected to the login page.',
  'password_change_failed' => 'Password failed to change.',
  'password_server_unresponsive' => 'Server did not respond. Please try again shortly.'
));
?>

<style>
  .password-page {
    padding-bottom: 24px;
  }

  .password-hero {
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
    padding: 26px 28px;
    border-radius: 22px;
    background:
      radial-gradient(circle at top right, rgba(245, 158, 11, .22), transparent 18rem),
      linear-gradient(135deg, #0f766e, #115e59);
    color: #fff;
    box-shadow: 0 18px 42px rgba(15, 118, 110, .22);
  }

  .password-hero h1 {
    margin: 0 0 8px;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.03em;
  }

  .password-hero p {
    max-width: 680px;
    margin: 0;
    color: rgba(255, 255, 255, .82);
    line-height: 1.65;
  }

  .password-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 18px;
  }

  .password-card,
  .password-tips {
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 16px 38px rgba(15, 23, 42, .07);
  }

  .password-card {
    padding: 28px;
  }

  .password-tips {
    padding: 24px;
  }

  .password-card-title {
    margin: 0 0 4px;
    color: #0f172a;
    font-size: 20px;
    font-weight: 800;
  }

  .password-card-subtitle {
    margin: 0 0 24px;
    color: #64748b;
    line-height: 1.6;
  }

  .password-user-box {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 22px;
    padding: 14px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
  }

  .password-avatar {
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    background: #ccfbf1;
    color: #0f766e;
    font-weight: 800;
    font-size: 18px;
  }

  .password-user-name {
    color: #0f172a;
    font-weight: 800;
  }

  .password-user-meta {
    color: #64748b;
    font-size: 13px;
  }

  .password-field {
    margin-bottom: 18px;
  }

  .password-field label {
    display: block;
    margin-bottom: 8px;
    color: #334155;
    font-size: 13px;
    font-weight: 800;
  }

  .password-input-wrap {
    position: relative;
  }

  .password-field .form-control {
    height: 48px;
    padding-right: 46px;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    background: #f8fafc;
    box-shadow: none;
  }

  .password-field .form-control:focus {
    border-color: rgba(15, 118, 110, .58);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(20, 184, 166, .12);
  }

  .toggle-password {
    position: absolute;
    top: 50%;
    right: 10px;
    width: 32px;
    height: 32px;
    transform: translateY(-50%);
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #64748b;
  }

  .toggle-password:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .password-strength {
    height: 8px;
    overflow: hidden;
    margin-top: 10px;
    border-radius: 999px;
    background: #e2e8f0;
  }

  .password-strength span {
    display: block;
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: #ef4444;
    transition: width .2s ease, background .2s ease;
  }

  .password-hint {
    margin-top: 8px;
    color: #64748b;
    font-size: 12px;
  }

  .password-alert {
    display: none;
    margin-bottom: 18px;
    border: 0;
    border-radius: 16px;
    padding: 13px 15px;
  }

  .password-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 24px;
  }

  .btn-password-save {
    min-width: 170px;
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #0f766e, #115e59);
    color: #fff;
    font-weight: 800;
    box-shadow: 0 12px 26px rgba(15, 118, 110, .22);
  }

  .btn-password-save:hover,
  .btn-password-save:focus {
    color: #fff;
    filter: brightness(.98);
  }

  .btn-password-back {
    border-radius: 14px;
    font-weight: 700;
  }

  .password-tips h3 {
    margin: 0 0 12px;
    color: #0f172a;
    font-size: 17px;
    font-weight: 800;
  }

  .password-tips ul {
    margin: 0;
    padding-left: 18px;
    color: #475569;
    line-height: 1.8;
  }

  .password-tips li + li {
    margin-top: 7px;
  }

  @media (max-width: 900px) {
    .password-grid {
      grid-template-columns: 1fr;
    }

    .password-hero,
    .password-card,
    .password-tips {
      border-radius: 18px;
    }
  }

  @media (max-width: 520px) {
    .password-hero {
      padding: 22px;
    }

    .password-card {
      padding: 20px;
    }

    .password-actions .btn {
      width: 100%;
    }
  }
</style>

<section class="content-header">
  <h1><?=htmlspecialchars($passwordLang['password_change_title'], ENT_QUOTES, 'UTF-8');?></h1>
  <ol class="breadcrumb">
    <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> <?=erp_h('common_home', 'Home');?></a></li>
    <li><a href="<?=base_index();?>profil"><?=htmlspecialchars($passwordLang['password_profile'], ENT_QUOTES, 'UTF-8');?></a></li>
    <li class="active"><?=htmlspecialchars($passwordLang['password_change_title'], ENT_QUOTES, 'UTF-8');?></li>
  </ol>
</section>

<section class="content password-page">
  <div class="password-hero">
    <h1><?=htmlspecialchars($passwordLang['password_update_account'], ENT_QUOTES, 'UTF-8');?></h1>
    <p><?=htmlspecialchars($passwordLang['password_update_intro'], ENT_QUOTES, 'UTF-8');?></p>
  </div>

  <div class="password-grid">
    <div class="password-card">
      <h2 class="password-card-title"><?=htmlspecialchars($passwordLang['password_change_card_title'], ENT_QUOTES, 'UTF-8');?></h2>
      <p class="password-card-subtitle"><?=htmlspecialchars($passwordLang['password_change_card_subtitle'], ENT_QUOTES, 'UTF-8');?></p>

      <div class="password-user-box">
        <div class="password-avatar"><?=htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8');?></div>
        <div>
          <div class="password-user-name"><?=htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');?></div>
          <div class="password-user-meta">@<?=htmlspecialchars($data_edit->username, ENT_QUOTES, 'UTF-8');?></div>
        </div>
      </div>

      <div class="alert password-alert" id="password_alert" role="alert"></div>

      <form id="pass_up" method="post" action="<?=base_admin();?>modul/profil/change_password_action.php?act=up" autocomplete="off">
        <input type="hidden" name="id" value="<?=intval($_SESSION['id_user']);?>">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['change_password_csrf_token'], ENT_QUOTES, 'UTF-8');?>">

        <div class="password-field">
          <label for="password"><?=htmlspecialchars($passwordLang['password_old'], ENT_QUOTES, 'UTF-8');?></label>
          <div class="password-input-wrap">
            <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
            <button type="button" class="toggle-password" data-target="#password" aria-label="<?=htmlspecialchars($passwordLang['password_show_old'], ENT_QUOTES, 'UTF-8');?>"><i class="fa fa-eye"></i></button>
          </div>
        </div>

        <div class="password-field">
          <label for="password_baru"><?=htmlspecialchars($passwordLang['password_new'], ENT_QUOTES, 'UTF-8');?></label>
          <div class="password-input-wrap">
            <input type="password" id="password_baru" name="password_baru" class="form-control" autocomplete="new-password" required>
            <button type="button" class="toggle-password" data-target="#password_baru" aria-label="<?=htmlspecialchars($passwordLang['password_show_new'], ENT_QUOTES, 'UTF-8');?>"><i class="fa fa-eye"></i></button>
          </div>
          <div class="password-strength"><span id="password_strength_bar"></span></div>
          <div class="password-hint" id="password_strength_text"><?=htmlspecialchars($passwordLang['password_hint_default'], ENT_QUOTES, 'UTF-8');?></div>
        </div>

        <div class="password-field">
          <label for="password_confirm"><?=htmlspecialchars($passwordLang['password_confirm'], ENT_QUOTES, 'UTF-8');?></label>
          <div class="password-input-wrap">
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" autocomplete="new-password" required>
            <button type="button" class="toggle-password" data-target="#password_confirm" aria-label="<?=htmlspecialchars($passwordLang['password_show_confirm'], ENT_QUOTES, 'UTF-8');?>"><i class="fa fa-eye"></i></button>
          </div>
        </div>

        <div class="password-actions">
          <button type="submit" id="btn_password_save" class="btn btn-password-save"><i class="fa fa-lock"></i> <?=htmlspecialchars($passwordLang['password_save'], ENT_QUOTES, 'UTF-8');?></button>
          <button type="button" id="btn_password_loading" class="btn btn-password-save" style="display:none" disabled><i class="fa fa-spinner fa-spin"></i> <?=htmlspecialchars($passwordLang['password_saving'], ENT_QUOTES, 'UTF-8');?></button>
          <a href="<?=base_index();?>profil" class="btn btn-default btn-password-back"><i class="fa fa-arrow-left"></i> <?=htmlspecialchars($passwordLang['password_back_profile'], ENT_QUOTES, 'UTF-8');?></a>
        </div>
      </form>
    </div>

    <aside class="password-tips">
      <h3><?=htmlspecialchars($passwordLang['password_tips_title'], ENT_QUOTES, 'UTF-8');?></h3>
      <ul>
        <li><?=htmlspecialchars($passwordLang['password_tip_minimum'], ENT_QUOTES, 'UTF-8');?></li>
        <li><?=htmlspecialchars($passwordLang['password_tip_mix'], ENT_QUOTES, 'UTF-8');?></li>
        <li><?=htmlspecialchars($passwordLang['password_tip_unique'], ENT_QUOTES, 'UTF-8');?></li>
        <li><?=htmlspecialchars($passwordLang['password_tip_private'], ENT_QUOTES, 'UTF-8');?></li>
      </ul>
    </aside>
  </div>
</section>

<script>
  (function() {
    var PASSWORD_LANG = <?=json_encode($passwordLang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
    function showPasswordAlert(type, message) {
      var cls = type === 'success' ? 'alert-success' : 'alert-danger';
      $('#password_alert')
        .removeClass('alert-success alert-danger')
        .addClass(cls)
        .html(message)
        .stop(true, true)
        .fadeIn(150);
    }

    function setPasswordLoading(loading) {
      $('#btn_password_save').toggle(!loading).prop('disabled', loading);
      $('#btn_password_loading').toggle(loading);
    }

    function scorePassword(value) {
      var score = 0;
      if (value.length >= 8) score++;
      if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
      if (/\d/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value)) score++;
      return score;
    }

    function updateStrength() {
      var value = $('#password_baru').val();
      var score = scorePassword(value);
      var width = [0, 25, 50, 75, 100][score];
      var color = ['#ef4444', '#ef4444', '#f59e0b', '#14b8a6', '#0f766e'][score];
      var label = [PASSWORD_LANG.password_strength_empty, PASSWORD_LANG.password_strength_weak, PASSWORD_LANG.password_strength_medium, PASSWORD_LANG.password_strength_strong, PASSWORD_LANG.password_strength_very_strong][score];
      $('#password_strength_bar').css({ width: width + '%', background: color });
      $('#password_strength_text').text(label);
    }

    $('.toggle-password').on('click', function() {
      var target = $($(this).data('target'));
      var isPassword = target.attr('type') === 'password';
      target.attr('type', isPassword ? 'text' : 'password');
      $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    $('#password_baru').on('keyup change', updateStrength);

    $('#pass_up').on('submit', function(e) {
      e.preventDefault();
      var oldPassword = $('#password').val();
      var newPassword = $('#password_baru').val();
      var confirmPassword = $('#password_confirm').val();

      if (!oldPassword || !newPassword || !confirmPassword) {
        showPasswordAlert('error', PASSWORD_LANG.password_required);
        return false;
      }

      if (newPassword.length < 8) {
        showPasswordAlert('error', PASSWORD_LANG.password_min_length);
        return false;
      }

      if (scorePassword(newPassword) < 2) {
        showPasswordAlert('error', PASSWORD_LANG.password_too_weak);
        return false;
      }

      if (newPassword !== confirmPassword) {
        showPasswordAlert('error', PASSWORD_LANG.password_confirm_mismatch);
        return false;
      }

      if (oldPassword === newPassword) {
        showPasswordAlert('error', PASSWORD_LANG.password_same_as_old);
        return false;
      }

      setPasswordLoading(true);
      $.ajax({
        type: 'POST',
        url: $(this).attr('action'),
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
          if (res && res.status === 'success') {
            showPasswordAlert('success', res.message || PASSWORD_LANG.password_success_redirect);
            setTimeout(function() {
              window.location = '<?=base_url();?>login.php';
            }, 1200);
          } else {
            showPasswordAlert('error', (res && res.message) ? res.message : PASSWORD_LANG.password_change_failed);
            setPasswordLoading(false);
          }
        },
        error: function(xhr) {
          showPasswordAlert('error', xhr.responseText || PASSWORD_LANG.password_server_unresponsive);
          setPasswordLoading(false);
        }
      });
      return false;
    });

    updateStrength();
  })();
</script>
