   <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar" style="background: white">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <div class="pull-left image">
              <img src="<?=base_url();?>upload/back_profil_foto/<?=$db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->foto_user?>" class="img-circle" alt="User Image" />
            </div>
            <div class="pull-left info">
              <p><?=ucwords($db->fetch_single_row('sys_users','id',$_SESSION['id_user'])->username)?></p>

              <a href="<?=base_index();?>profil"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
          </div>
         <!--  search form
         <form action="#" method="get" class="sidebar-form">
           <div class="input-group">
             <input type="text" name="q" class="form-control" placeholder="Search..."/>
             <span class="input-group-btn">
               <button type='submit' name='search' id='search-btn' class="btn btn-flat"><i class="fa fa-search"></i></button>
             </span>
           </div>
         </form>
         /.search form -->
          <!-- sidebar menu: : style can be found in sidebar.less -->
         <ul class="sidebar-menu modern-sidebar">
            <li class="header">MAIN NAVIGATION</li>
             <li class="<?=(uri_segment(1)=='')?'active':'';?>">
                            <a href="<?=base_index();?>">
                                <i class="fa fa-dashboard"></i> <span>Dashboard</span>
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
                                <i class="fa fa-sign-out"></i> <span>Logout</span>
                            </a>
                        </li>
           </ul>
        </section>
        <!-- /.sidebar -->
      </aside>
  <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">


