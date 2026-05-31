
                <!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>
                        Manage User Group
                    </h1>
                    <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>group-user">User Group</a></li>
                        <li class="active">User Group List</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="box">
                                <div class="box-header">
 
                                              <form action="" method="get" class="form_cari">
                        <div class="input-group col-lg-6">
                 <span class="input-group-btn">
        <button class="btn btn-default" type="button">Search Group !</button>
      </span>
                            <input type="text" name="q" class="form-control" placeholder="Search..."/>
                            <span class="input-group-btn">
                                <button type="submit" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i></button>
                            </span>
                        </div>
                    </form>   
                                </div><!-- /.box-header -->
                                <div class="box-body table-responsive">

                                    <table  class="table table-bordered table-striped">
                                   <thead>
                                     <tr>
                           <th style="width:25px" align="center">No</th>
                          <th>Level</th>
<th>Description</th>

                          <th>Action</th>
                         
                        </tr>
                                      </thead>
                                        <tbody>
                                         <?php 

      $limit =10;
        $search ="";
        if (isset($_GET["q"])) {
          $search_condition = $db->getRawWhereFilterForColumns
          ($_GET["q"], array("level", "deskripsi"));
          $search = "where $search_condition";
        }

      $dtb=$pg->query("select level,sys_group_users.level_name,sys_group_users.deskripsi,sys_group_users.id from sys_group_users  $search");
       $no=$pg->Num($limit);
        $count=$pg->Num($limit);
      if ($dtb->rowCount()<1) {
        echo "<tr><td colspan='4'>No matching records found</td></tr>";
      }
      foreach ($dtb as $isi) {
        ?><tr id="line_<?=$isi->id;?>"><td align="center"><?=$no;?></td><td><?=$isi->level_name;?></td>
<td><?=$isi->deskripsi;?></td>
<td>
<?php
if ($isi->level!='admin') {
  ?>
<a href="<?=base_index();?>group-user/detail/<?=$isi->id;?>" class="btn btn-success btn-flat"><i class="fa fa-eye"></i></a> <a href="<?=base_index();?>group-user/edit/<?=$isi->id;?>" class="btn btn-primary btn-flat"><i class="fa fa-pencil"></i></a> <button class="btn btn-danger btn-flat hapus" data-uri="<?=base_admin();?>modul/group_user/group_user_action.php" data-id="<?=$isi->id;?>"><i class="fa fa-trash-o"></i> </button>
<?php
}
?>
 </td></tr>
        <?php
        $no++;
      }
      ?>
                                        </tbody>
                                    </table>
                                    <div class="col-xs-6" style="margin-top:10px">
    Showing <?=$count;?> to <?=$no-1;?> of <?=$pg->total_record;?> entries
                        
                        </div>

                        <div class="col-xs-6">
                          
                                    <?php
                                  if (isset($_GET['q'])) {
$pg->url=base_index()."group-user?q=".$_GET['q']."&page=";
                                  }
                                    $pg->setParameter(array(
                                      'range'=>$limit,
                                      ));
                                      ?>

                                    <div class="dataTables_paginate paging_bootstrap">
                                    <ul class="pagination">
                                    <?=$pg->create();?>
                                    </ul>
                                    </div>
                        </div>
                                </div><!-- /.box-body -->
                            </div><!-- /.box -->
                        </div>
                    </div>
      <a href="<?=base_index();?>group-user/tambah" class="btn btn-primary"><i class="fa fa-plus"></i> Add New</a>
         
                </section><!-- /.content -->

