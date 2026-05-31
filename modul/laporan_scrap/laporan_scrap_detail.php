<!-- Content Header (Page header) -->
                <section class="content-header">
                    <h1>Laporan Scrap</h1>
                   <ol class="breadcrumb">
                        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
                        <li><a href="<?=base_index();?>laporan-scrap">Laporan Scrap</a></li>
                        <li class="active">Detail Laporan Scrap</li>
                    </ol>
                </section>

                <!-- Main content -->
                <section class="content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-solid box-primary">
                            <div class="box-header">
                            <h3 class="box-title">Detail Laporan Scrap</h3>
                                <div class="box-tools pull-right">
                                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                                </div>
                            </div>

                    <div class="box-body">
                      <form class="form-horizontal">
                        
              <div class="form-group">
                <label for="Nomor" class="control-label col-lg-2">Nomor </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->nomor;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="No Scrap" class="control-label col-lg-2">No Scrap </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->no_scrap;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Tanggal Scrap" class="control-label col-lg-2">Tanggal Scrap </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->tgl_scrap;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
              <div class="form-group">
                <label for="Keterangan" class="control-label col-lg-2">Keterangan </label>
                <div class="col-lg-10">
                  <input type="text" disabled="" value="<?=$data_edit->keterangan;?>" class="form-control">
                </div>
              </div><!-- /.form-group -->
              
                <div class="form-group">
                  <label for="Status" class="control-label col-lg-2">Status </label>
                  <div class="col-lg-10">
                    <input type="text" disabled="" value="<?=$data_edit->status;?>" class="form-control">
                  </div>
                </div><!-- /.form-group -->

                <hr>

<h4>
    <i class="fa fa-recycle"></i>
    Detail Item Scrap
</h4>

<div class="table-responsive">

<table class="table table-bordered table-striped">

    <thead>

        <tr>

            <th width="5%">No</th>
            <th width="15%">No LP</th>
            <th width="15%">Kode Barang</th>
            <th width="25%">Nama Barang</th>
            <th width="10%">Qty Scrap</th>
            <th width="10%">Satuan</th>
            <th width="15%">Jenis Scrap</th>

        </tr>

    </thead>

    <tbody>

<?php

$no = 0;

$detail = $db->query("
    SELECT *
    FROM scrap_detail
    WHERE no_scrap = '".$data_edit->no_scrap."'
");

foreach($detail as $row){

$no++;

?>

<tr>

    <td class="text-center">
        <?=$no;?>
    </td>

    <td>
        <?=$row->no_laporan_produksi;?>
    </td>

    <td>
        <?=$row->kode_barang;?>
    </td>

    <td>
        <?=$row->nm_barang;?>
    </td>

    <td class="text-right">
        <?=number_format($row->qty_scrap,5);?>
    </td>

    <td>
        <?=$row->satuan;?>
    </td>

    <td>
        <?=$row->jenis_scrap;?>
    </td>

</tr>

<?php } ?>

    </tbody>

</table>

</div>

<hr>
                
                        
                      </form>
                      <a href="<?=base_index();?>laporan-scrap" class="btn btn-success "><i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?></a>

                        </div>
                      </div>
                    </div>
                </div>

                </section><!-- /.content -->
