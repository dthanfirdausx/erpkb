<?php
if (!function_exists('left_nav_t')) {
  function left_nav_t($key, $fallback = '')
  {
    return lang_text('left_nav_' . $key, $fallback);
  }
}

if (!function_exists('left_nav_h')) {
  function left_nav_h($key, $fallback = '')
  {
    return htmlspecialchars(left_nav_t($key, $fallback), ENT_QUOTES, 'UTF-8');
  }
}
?>
   <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar" style="background: white">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <div class="pull-left image">
              <img src="<?=erpkb_user_photo_url($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->foto_user, 'back_profil_foto');?>" class="img-circle" alt="<?=left_nav_h('user_image', 'User Image');?>" />
            </div>
            <div class="pull-left info">
              <p><?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->username)?></p>

              <a href="<?=base_index();?>profil"><i class="fa fa-circle text-success"></i> <?=left_nav_h('online', 'Online');?></a>
            </div>
          </div>
          <?php if (!empty($_SESSION['impersonator'])) { ?>
          <div class="erpkb-sidebar-login-as">
            <div class="erpkb-sidebar-login-as-label">
              <i class="fa fa-user-secret"></i>
              <?=left_nav_h('login_as_by', 'Login As oleh');?> <?=htmlspecialchars($_SESSION['impersonator']['username'], ENT_QUOTES, 'UTF-8');?>
            </div>
            <button type="button" id="btn_stop_login_as" class="btn btn-warning btn-block erpkb-sidebar-back-admin">
              <i class="fa fa-undo"></i> <?=left_nav_h('back_to', 'Back to');?> <?=htmlspecialchars($_SESSION['impersonator']['username'], ENT_QUOTES, 'UTF-8');?>
            </button>
          </div>
          <?php } ?>
          <style>
            .erpkb-sidebar-login-as {
              margin: 0 10px 12px;
              padding: 10px;
              border-radius: 10px;
              background: #fff7ed;
              border: 1px solid #fed7aa;
              box-shadow: 0 4px 12px rgba(15, 23, 42, .06);
            }
            .erpkb-sidebar-login-as-label {
              margin-bottom: 8px;
              color: #9a3412;
              font-size: 12px;
              font-weight: 700;
              line-height: 1.3;
            }
            .erpkb-sidebar-login-as-label i {
              margin-right: 5px;
            }
            .erpkb-sidebar-back-admin {
              border-radius: 7px !important;
              font-weight: 700;
              text-align: left;
              white-space: normal;
            }
          </style>
         <!--  search form
         <form action="#" method="get" class="sidebar-form">
           <div class="input-group">
             <input type="text" name="q" class="form-control" placeholder="<?=left_nav_h('search_placeholder', 'Search...');?>"/>
             <span class="input-group-btn">
               <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i></button>
             </span>
           </div>
         </form>
         /.search form -->
          <!-- sidebar menu: : style can be found in sidebar.less -->
         <ul class="sidebar-menu modern-sidebar">
            <li class="header"><?=left_nav_h('main_navigation', 'MAIN NAVIGATION');?></li>
             <li class="<?=(uri_segment(1)=='')?'active':'';?>">
                            <a href="<?=base_index();?>">
                                <i class="fa fa-dashboard"></i> <span><?=left_nav_h('dashboard', 'Dashboard');?></span>
                            </a>
                        </li>
<?php

               //   }
// Select all entries from the menu table
$result=$db->query("select sys_menu.*,sys_menu_role.read_act,sys_menu_role.insert_act,sys_menu_role.update_act,sys_menu_role.delete_act,sys_menu_role.group_level from sys_menu
left join sys_menu_role on sys_menu.id=sys_menu_role.id_menu
where sys_menu_role.group_level=? and sys_menu_role.read_act=? and tampil=? ORDER BY parent, urutan_menu",array('sys_menu_role.group_level'=>$_SESSION['group_level'],'sys_menu_role.read_act'=>'Y','tampil'=>'Y'));


// Create a multidimensional array to list items and parents
$menu = array(
    'items' => array(),
    'parents' => array()
);
// Builds the array lists with data from the menu table
foreach ($result as $items) {

  $items = $db->convert_obj_to_array($items);

      // Creates entry into items array with current menu item id ie.
    $menu['items'][$items['id']] = $items;
    // Creates entry into parents array. Parents array contains a list of all items with children
    $menu['parents'][$items['parent']][] = $items['id'];
}
/*echo "<pre>";
print_r($menu);*/
echo $db->buildMenu(uri_segment(1),0, $menu);
?>
          <li>
                            <a href="<?=base_admin();?>logout.php">
                                <i class="fa fa-sign-out"></i> <span><?=left_nav_h('logout', 'Logout');?></span>
                            </a>
                        </li>
           </ul>
        </section>
        <!-- /.sidebar -->
      </aside>
  <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">


