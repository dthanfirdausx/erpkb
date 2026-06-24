<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.native-language-switcher{padding:8px 8px 0 0}
.native-language-switcher .language-select-wrap{position:relative;display:inline-block}
.native-language-switcher .language-flag{display:none}
.native-language-switcher select{height:34px;min-width:162px;border-radius:18px;border:1px solid #2f80b7;background:#3c8dbc;color:#fff;font-size:12px;font-weight:600;padding:5px 28px 5px 14px;outline:none;appearance:none;-webkit-appearance:none;-moz-appearance:none;cursor:pointer}
.native-language-switcher select option{color:#334155;background:#fff}
.native-language-switcher:after{content:"\f107";font-family:FontAwesome;position:absolute;margin-left:-24px;margin-top:8px;color:#fff;pointer-events:none}
</style>
<header class="main-header">

        <!-- Logo -->
        <a href="<?=base_index();?>" class="logo"><b><?= shortTittle ?></b></a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
           <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" aria-label="<?=erp_h('top_nav_toggle_navigation', 'Toggle navigation');?>">
            <span class="sr-only"><?=erp_h('top_nav_toggle_navigation', 'Toggle navigation');?></span>
          </a>
          <!-- Navbar Right Menu -->

          <div class="navbar-custom-menu">

            <ul class="nav navbar-nav">

	            <li class="native-language-switcher">
	              <?php
	              $languageFlags = array(
	                'id' => '🇮🇩',
	                'en' => '🇺🇸',
	                'ko' => '🇰🇷',
	                'zh' => '🇨🇳',
	                'ja' => '🇯🇵'
	              );
	              $currentLanguage = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
	              $currentFlag = isset($languageFlags[$currentLanguage]) ? $languageFlags[$currentLanguage] : '🌐';
	              ?>
		              <form method="post" action="<?=base_admin();?>inc/set_language.php" id="native_language_form">
		                <input type="hidden" name="redirect_to" value="<?=htmlspecialchars($_SERVER['REQUEST_URI'],ENT_QUOTES,'UTF-8');?>">
		                <div class="language-select-wrap">
		                  <span class="language-flag" aria-hidden="true"><?=$currentFlag;?></span>
		                  <select name="language" aria-label="<?=erp_h('top_nav_language', 'Language');?>" onchange="document.getElementById('native_language_form').submit()">
		                    <?php
		                    foreach (erpkb_available_languages() as $code => $meta) {
		                      $selected = $currentLanguage === $code ? 'selected' : '';
		                      $flag = isset($languageFlags[$code]) ? $languageFlags[$code] : '🌐';
		                      echo '<option value="'.htmlspecialchars($code,ENT_QUOTES,'UTF-8').'" '.$selected.'>'.$flag.' '.htmlspecialchars($meta['label'],ENT_QUOTES,'UTF-8').'</option>';
		                    }
		                    ?>
	                  </select>
	                </div>
	              </form>
	            </li>
              
            <!--   Messages: style can be found in dropdown.less
            <li class="dropdown messages-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-envelope-o"></i>
                <span class="label label-success">4</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header">You have 4 messages</li>
                <li>
                  inner menu: contains the actual data
                  <ul class="menu">
                    <li>start message
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user2-160x160.jpg" class="img-circle" alt="User Image"/>
                        </div>
                        <h4>
                          Support Team
                          <small><i class="fa fa-clock-o"></i> 5 mins</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>end message
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user3-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          AdminLTE Design Team
                          <small><i class="fa fa-clock-o"></i> 2 hours</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user4-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          Developers
                          <small><i class="fa fa-clock-o"></i> Today</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user3-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          Sales Department
                          <small><i class="fa fa-clock-o"></i> Yesterday</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <div class="pull-left">
                          <img src="<?=base_admin();?>assets/dist/img/user4-128x128.jpg" class="img-circle" alt="user image"/>
                        </div>
                        <h4>
                          Reviewers
                          <small><i class="fa fa-clock-o"></i> 2 days</small>
                        </h4>
                        <p>Why not buy a new awesome theme?</p>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="footer"><a href="#">See All Messages</a></li>
              </ul>
            </li>
            Notifications: style can be found in dropdown.less
            <li class="dropdown notifications-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-bell-o"></i>
                <span class="label label-warning">10</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header">You have 10 notifications</li>
                <li>
                  inner menu: contains the actual data
                  <ul class="menu">
                    <li>
                      <a href="#">
                        <i class="fa fa-users text-aqua"></i> 5 new members joined today
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <i class="fa fa-warning text-yellow"></i> Very long description here that may not fit into the page and may cause design problems
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <i class="fa fa-users text-red"></i> 5 new members joined
                      </a>
                    </li>

                    <li>
                      <a href="#">
                        <i class="fa fa-shopping-cart text-green"></i> 25 sales made
                      </a>
                    </li>
                    <li>
                      <a href="#">
                        <i class="fa fa-user text-red"></i> You changed your username
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="footer"><a href="#">View all</a></li>
              </ul>
            </li>
            Tasks: style can be found in dropdown.less
            <li class="dropdown tasks-menu">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-flag-o"></i>
                <span class="label label-danger">9</span>
              </a>
              <ul class="dropdown-menu">
                <li class="header">You have 9 tasks</li>
                <li>
                  inner menu: contains the actual data
                  <ul class="menu">
                    <li>Task item
                      <a href="#">
                        <h3>
                          Design some buttons
                          <small class="pull-right">20%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-aqua" style="width: 20%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">20% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                    <li>Task item
                      <a href="#">
                        <h3>
                          Create a nice theme
                          <small class="pull-right">40%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-green" style="width: 40%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">40% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                    <li>Task item
                      <a href="#">
                        <h3>
                          Some task I need to do
                          <small class="pull-right">60%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-red" style="width: 60%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">60% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                    <li>Task item
                      <a href="#">
                        <h3>
                          Make beautiful transitions
                          <small class="pull-right">80%</small>
                        </h3>
                        <div class="progress xs">
                          <div class="progress-bar progress-bar-yellow" style="width: 80%" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                            <span class="sr-only">80% Complete</span>
                          </div>
                        </div>
                      </a>
                    </li>end task item
                  </ul>
                </li>
                <li class="footer">
                  <a href="#">View all tasks</a>
                </li>
              </ul>
            </li> -->
              <!-- User Account: style can be found in dropdown.less -->
            
              <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  <img src="<?=erpkb_user_photo_url($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->foto_user, 'back_profil_foto');?>" class="user-image" alt="<?=erp_h('left_nav_user_image', 'User Image');?>"/>
                  <span class="hidden-xs"><?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->first_name)?> <?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->last_name);?></span>
                </a>

                <ul class="dropdown-menu erpkb-user-menu-dropdown">
                  <!-- User image -->
                  <li class="user-header erpkb-user-dropdown-header">
                    <img src="<?=erpkb_user_photo_url($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->foto_user, 'back_profil_foto');?>" class="img-circle erpkb-user-dropdown-avatar" alt="<?=erp_h('left_nav_user_image', 'User Image');?>" />
                    <div class="erpkb-user-dropdown-name">
                      <?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->first_name)?> <?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->last_name);?>
                    </div>
                    <div class="erpkb-user-dropdown-role">
                      <?=$db->fetch_single_row('sys_group_users','level',$_SESSION['group_level'])->deskripsi?>
                    </div>
                    <div class="erpkb-user-dropdown-meta">
                      <?php $topBarCreated = $db->fetch_custom_single("SELECT MONTH(date_created) as bulan, YEAR(date_created) as tahun from sys_users where id=? ",array('id'=>$_SESSION['id_user'])); ?>
                      <?=erp_h('top_nav_member_since', 'Member since');?> <?=erp_e(erpkb_month_name($topBarCreated->bulan));?> <?=erp_e($topBarCreated->tahun);?>
                    </div>
                    <?php if (!empty($_SESSION['impersonator'])) { ?>
                    <div class="erpkb-login-as-badge"><?=erp_h('left_nav_login_as_by', 'Login as by');?> <?=htmlspecialchars($_SESSION['impersonator']['username'], ENT_QUOTES, 'UTF-8');?></div>
                    <?php } ?>
                  </li>
                  <!-- Menu Body -->
     <!--              <li class="user-body">
                    <div class="col-xs-4 text-center">
                      <a href="#">Followers</a>
                    </div>
                    <div class="col-xs-4 text-center">
                      <a href="#">Sales</a>
                    </div>
                    <div class="col-xs-4 text-center">
                      <a href="#">Friends</a>
                    </div>
                  </li> -->
                  <!-- Menu Footer-->
                   <li class="user-footer erpkb-user-dropdown-footer">
                                    <div class="erpkb-user-dropdown-actions">
                                        <a href="<?=base_index();?>profil" class="btn btn-default btn-flat"><?=erp_h('common_profile', 'Profile');?></a>
                                        <a href="<?=base_admin();?>logout.php" class="btn btn-default btn-flat"><?=erp_h('common_sign_out', 'Sign out');?></a>
                                    </div>
                                </li>


                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>

<div class="modal modal-danger" id="ucing" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog"> <div class="modal-content"><div class="modal-header"> <button type="button" class="close" data-dismiss="modal" aria-label="<?=erp_h('common_close', 'Close');?>"><span aria-hidden="true">×</span></button> <h4 class="modal-title"><?=erp_h('common_confirm', 'Confirm');?></h4> </div> <div class="modal-body"> <p> <i class="fa fa-info-circle fa-2x" style=" vertical-align: middle;margin-right:5px"></i> <span> <?=erp_h('modal_delete_confirm', 'Are you sure you want to delete this data?');?></span></p> </div> <div class="modal-footer"> <button type="button" id="delete" class="btn btn-danger"><?=erp_h('common_delete', 'Delete');?></button> <button type="button" class="btn btn-default" data-dismiss="modal"><?=erp_h('common_cancel', 'Cancel');?></button> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div><!-- /.modal -->
<div class="modal modal-warning" id="informasi" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"> <div class="modal-dialog"> <div class="modal-content"><div class="modal-header"> <h4 class="modal-title"><?=erp_h('common_info', 'Information');?></h4> </div> <div class="modal-body"> <p id="isi_informasi">
<?=erp_h('session_expired', 'Sorry, your login session has expired');?>
</p> </div> <div class="modal-footer"> <a href="<?=base_admin();?>" class="btn btn-outline pull-left"><?=erp_h('session_relogin', 'Click here to log in again');?></a> </div> </div><!-- /.modal-content --> </div><!-- /.modal-dialog --> </div><!-- /.modal -->
<style>
.navbar-nav>.user-menu>.dropdown-menu.erpkb-user-menu-dropdown {
  width: 310px;
  padding: 0;
  border: 0;
  border-radius: 10px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, .18);
  overflow: hidden;
  z-index: 10050;
}
.navbar-nav>.user-menu>.dropdown-menu.erpkb-user-menu-dropdown>li.user-header.erpkb-user-dropdown-header {
  height: auto;
  min-height: 0;
  padding: 18px 16px 14px;
  background: #fff !important;
  text-align: center;
  border-bottom: 1px solid #edf2f7;
}
.erpkb-user-dropdown-avatar {
  width: 86px !important;
  height: 86px !important;
  padding: 3px;
  background: #f8fafc;
  border: 2px solid #d8e1ec !important;
  object-fit: cover;
}
.erpkb-user-dropdown-name {
  margin-top: 10px;
  color: #1f2937;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.25;
}
.erpkb-user-dropdown-role {
  margin-top: 3px;
  color: #64748b;
  font-size: 12px;
  line-height: 1.3;
}
.erpkb-user-dropdown-meta {
  margin-top: 5px;
  color: #94a3b8;
  font-size: 11px;
  line-height: 1.3;
}
.erpkb-login-as-badge {
  display: inline-block;
  margin-top: 9px;
  padding: 4px 9px;
  border-radius: 999px;
  background: #f59e0b;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.2;
}
.navbar-nav>.user-menu>.dropdown-menu.erpkb-user-menu-dropdown>.user-footer.erpkb-user-dropdown-footer {
  padding: 12px;
  background: #f8fafc;
}
.erpkb-user-dropdown-actions {
  display: flex;
  gap: 10px;
  justify-content: space-between;
}
.erpkb-user-dropdown-actions .btn {
  flex: 1;
  border-radius: 6px !important;
}
</style>
<script>
$(document).on('click', '#btn_stop_login_as', function(e) {
  e.preventDefault();
  $.ajax({
    url: '<?=base_admin();?>modul/data_user/data_user_action.php?act=stop_login_as',
    type: 'POST',
    dataType: 'json',
    success: function(response) {
      var result = response[0] || {};
      if (result.status === 'good') {
        window.location.href = result.redirect || '<?=base_index();?>data-user';
        return;
      }
      alert(result.error_message || <?=erp_js('impersonation_stop_failed', 'Failed to return to admin user.');?>);
    },
    error: function(xhr) {
      alert(xhr.responseText || <?=erp_js('impersonation_stop_failed', 'Failed to return to admin user.');?>);
    }
  });
});
</script>
