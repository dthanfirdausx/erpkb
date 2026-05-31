                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                       Group User Permission
            </h1>
                       <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>menu-management">Menu Management</a></li>
                        <li class="active">Group User Permission</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
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

foreach ($db->query("select sys_group_users.id, sys_group_users.level_name 
  from sys_users inner join sys_group_users 
on sys_users.group_level=sys_group_users.id
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
                            </div><!-- /.box -->
                        </div>
                    </div>
                </section><!-- /.content -->
  



<script type="text/javascript">
  
function read_act(id,cb) {
  check_act = '';
  if (cb.checked) {
     check_act = 'Y';
  } else {
    check_act = 'N';

  }
  $.ajax({

        type: "post",
        url: "<?=base_admin();?>modul/menu_management/menu_management_action.php?act=change_read",
        data: "role_id="+id+"&data_act="+check_act,
     //  enctype:  'multipart/form-data'
      success: function(data){

        console.log(data);
    }

  });
}

function insert_act(id,cb) {
  check_act = '';
  if (cb.checked) {
     check_act = 'Y';
  } else {
    check_act = 'N';

  }
  $.ajax({

        type: "post",
        url: "<?=base_admin();?>modul/menu_management/menu_management_action.php?act=change_insert",
        data: "role_id="+id+"&data_act="+check_act,
     //  enctype:  'multipart/form-data'
      success: function(data){

        console.log(data);
    }

  });
}

function update_act(id,cb) {
  check_act = '';
  if (cb.checked) {
     check_act = 'Y';
  } else {
    check_act = 'N';

  }
  $.ajax({

        type: "post",
        url: "<?=base_admin();?>modul/menu_management/menu_management_action.php?act=change_update",
        data: "role_id="+id+"&data_act="+check_act,
     //  enctype:  'multipart/form-data'
      success: function(data){

        console.log(data);
    }

  });
}

function delete_act(id,cb) {
  check_act = '';
  if (cb.checked) {
     check_act = 'Y';
  } else {
    check_act = 'N';

  }
  $.ajax({

        type: "post",
        url: "<?=base_admin();?>modul/menu_management/menu_management_action.php?act=change_delete",
        data: "role_id="+id+"&data_act="+check_act,
     //  enctype:  'multipart/form-data'
      success: function(data){

        console.log(data);
    }

  });
}


  </script>




