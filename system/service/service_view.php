
                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Manage Menu
                    </h1>
                       <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>menu">Menu</a></li>
                        <li class="active">Menu List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                           <!-- Custom Tabs -->
              <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                  <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">Service Name</a></li>
                  <li class=""><a href="#tab_2" data-toggle="tab" aria-expanded="false">User Permission</a></li>
                </ul>
                <div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
           
                            <div class="box">
                                <div class="box-header">
                                  <a href="<?=base_index();?>page-service/tambah" class="btn btn-primary btn-flat"><i class="fa fa-plus"></i> <?=$lang['add_button'];?></a>
                                      <a href="<?=base_index();?>page-service/import" class="btn btn-primary btn-flat"><i class="fa fa-cloud-upload"></i> Import Page</a>
                                </div><!-- /.box-header -->
                                <div class="box-body table-responsive">
                                    <table id="dtb_manual" class="table table-bordered table-striped">
                                   <thead>
                                     <tr>
                          <th>Name</th>
                          <th>URL</th>
                          <th>Doc</th>
                         
                          <th><?=$lang['action'];?></th>

                        </tr>
                                      </thead>
                                        <tbody>
                                         <?php
      $dtb=$db->query("select * from sys_services ");

      foreach ($dtb as $isi) {
        ?><tr id="line_<?=$isi->id;?>">
<td><?=$isi->page_name;?></td>
<td><a target='_blank' href='<?=base_url()."api/".$isi->url;?>'><?=base_url()."api/".$isi->url;?></a>
</td>
<td><a target='_blank'  href='<?=base_url()."api/".$isi->url;?>/doc'><?=base_url()."api/".$isi->url;?>/doc</a>
</td>

        <td>

       <a href="<?=base_index();?>page/edit/<?=$isi->id;?>" class="btn btn-primary btn-flat"><i class="fa fa-pencil"></i></a>
      <button class="btn btn-danger hapus btn-flat" data-uri="<?=base_admin();?>system/service/service_action.php" data-id="<?=$isi->id;?>"><i class="fa fa-trash-o"></i></button>
    <a href="<?=base_admin();?>system/page/page_action.php?act=back&page=<?=$isi->nav_act;?>" class="btn btn-success btn-flat"><i class="fa fa-download"></i></a>

        </td>
        </tr>
        <?php

      }
      ?>
                                        </tbody>
                                    </table>
                                </div><!-- /.box-body -->
                            </div><!-- /.box -->
                  </div><!-- /.tab-pane -->
                  <div class="tab-pane" id="tab_2">
                     <div class="box">
                                <div class="box-header">
                                  <h3 class="box-title">&nbsp;</h3>
                                </div><!-- /.box-header -->

<form method="get" class="form-horizontal" action="">
                      <div class="form-group">
                        <label for="Menu" class="control-label col-lg-2">Group Users</label>
                        <div class="col-lg-4">
                            <select name="user" id="id_po_select" data-placeholder="Pilih User" class="form-control chzn-select" tabindex="2">
                        <option value="">Choose Group User</option>
                          <?php 

foreach ($db->query("select sys_group_users.id, sys_group_users.level_name from sys_users inner join sys_group_users 
on sys_users.group_level=sys_group_users.level
group by sys_group_users.id") as $isi) {

                  if (intval($_GET['user'])==$isi->id) {
                     echo "<option value='$isi->id' selected>$isi->level_name</option>";
                  } else {
                     echo "<option value='$isi->id'>$isi->level_name</option>";
                  }
                 
               } ?>

                  
                  </select>
                        </div>
                      </div><!-- /.form-group -->

 <label for="Menu" class="control-label col-lg-2">&nbsp;</label>
                        <div class="col-lg-10">
<button style="margin-top:10px;margin-bottom:10px" class="btn btn-primary">Show Menu</button>
</div>
</form>

                                <div class="box-body table-responsive">
          
<?php if (isset($_GET['user'])) {
  
?>       
<h3>Check The Checkbox To Give Permission</h3>
<table id="dtb" class="table table-bordered table-condensed table-hover table-striped">
                      <thead>
                        <tr>
                        <th style="width:20px">No</th>
                          <th>Menu </th>
                          <th>Group User</th>
                          <th>Read</th>
                           <th>Input</th>
                            <th>Edit</th>
                             <th>Delete</th>
                         
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
      $dtb=$db->query("select sys_menu.type_menu,sys_menu_role.read_act,sys_menu_role.insert_act,sys_menu_role.update_act,sys_menu_role.delete_act, sys_menu.page_name,sys_menu.urutan_menu,sys_group_users.level_name,sys_menu_role.id from sys_menu_role inner join sys_menu on sys_menu_role.id_menu=sys_menu.id inner join sys_group_users on sys_menu_role.group_level=sys_group_users.level where sys_group_users.id=? order by urutan_menu asc",array('sys_group_users.id'=>$_GET
        ['user']));
      $i=1;
      foreach ($dtb as $isi) {
        ?><tr id="line_<?=$isi->id;?>">
        <td>
        <?=$i;?></td>
        <td><?=$isi->page_name;?></td>
        <td><?=$isi->level_name;?></td>
        <?php
        if($isi->type_menu=='main')
        {
            ?>
            <td>
              <div class="checkbox">
              <div class="checkbox checkbox-primary">
                        <input class="styled styled-primary" type="checkbox" value="option1" onclick="read_act(<?=$isi->id;?>,this)" <?=($isi->read_act=='Y')?'checked=""':'';?>>
                        <label for="checkbox2">
                            &nbsp;
                        </label>
                    </div>
                          </div>
            </td>
            <td colspan="3">Main Menu</td>
<?php
        } else {



        ?>
        <td>
        <div class="checkbox">
          <div class="checkbox checkbox-primary">
                        <input class="styled styled-primary" type="checkbox" value="option1" onclick="read_act(<?=$isi->id;?>,this)" <?=($isi->read_act=='Y')?'checked=""':'';?>>
                        <label for="checkbox2">
                            &nbsp;
                        </label>
                    </div>
                          </div>
        </td>
          <td>
          <div class="checkbox">
           <div class="checkbox checkbox-primary">
                        <input class="styled styled-primary" type="checkbox" value="option1" onclick="insert_act(<?=$isi->id;?>,this)" <?=($isi->insert_act=='Y')?'checked=""':'';?>>
                        <label for="checkbox2">
                            &nbsp;
                        </label>
                    </div>
                          </div>
      </td>
            <td>   <div class="checkbox">
            <div class="checkbox checkbox-primary">
                          <input class="styled styled-primary" type="checkbox" value="option1" onclick="update_act(<?=$isi->id;?>,this)" <?=($isi->update_act=='Y')?'checked=""':'';?>>
                        <label for="checkbox2">
                            &nbsp;
                        </label>
                    </div>
                          </div></td>
              <td>   <div class="checkbox">
              <div class="checkbox checkbox-primary">
                         <input class="styled styled-primary" type="checkbox" value="option1" onclick="delete_act(<?=$isi->id;?>,this)" <?=($isi->delete_act=='Y')?'checked=""':'';?>>
                        <label for="checkbox2">
                            &nbsp;
                        </label>
                    </div>
                          </div></td>
                          <?php 
                          }
                          ?>
        </tr>
        <?php
        $i++;
      }
      ?>
                   </tbody>
                    </table>
<?php 

}  

?>

                                </div><!-- /.box-body -->
                  </div><!-- /.tab-pane -->
                </div><!-- /.tab-content -->
              </div><!-- nav-tabs-custom -->

                        </div>
                    </div>

                </section><!-- /.content -->
