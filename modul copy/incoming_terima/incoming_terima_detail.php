<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Transfer Produksi ke Gudang</h1>
    <ol class="breadcrumb">
        <li><a href="<?=base_index();?>"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="<?=base_index();?>incoming-terima">Transfer</a></li>
        <li class="active">Detail Transfer</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
    <div class="col-lg-12">
        <div class="box box-solid box-primary">
            <div class="box-header">
                <h3 class="box-title">Detail Transfer</h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-info btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    <button class="btn btn-info btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
                </div>
            </div>

<div class="box-body">
<form class="form-horizontal">

<!-- Nomor -->
<div class="form-group">
  <label class="control-label col-lg-2">Nomor</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?=$data_edit->no_terima;?>" class="form-control">
  </div>
</div>

<!-- No LPB -->
<div class="form-group">
  <label class="control-label col-lg-2">No LPB</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?=$data_edit->no_transfer;?>" class="form-control">
  </div>
</div>

<!-- Tanggal LPB -->
<div class="form-group">
  <label class="control-label col-lg-2">Tanggal LPB</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?= !empty($data_edit->tgl_lpb) ? tgl_indo($data_edit->tgl_lpb) : '-' ?>" class="form-control">
  </div>
</div>

<!-- Dari -->
<div class="form-group">
  <label class="control-label col-lg-2">Dari</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?=$data_edit->dari;?>" class="form-control">
  </div>
</div>

<!-- No SPB -->
<div class="form-group">
  <label class="control-label col-lg-2">No SPB</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?=$data_edit->no_terima;?>" class="form-control">
  </div>
</div>

<!-- Tanggal SPB -->
<div class="form-group">
  <label class="control-label col-lg-2">Tanggal SPB</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?= !empty($data_edit->tgl_spb) ? tgl_indo($data_edit->tgl_spb) : '-' ?>" class="form-control">
  </div>
</div>

<!-- Departemen -->
<div class="form-group">
  <label class="control-label col-lg-2">Departemen</label>
  <div class="col-lg-10">

    <?php
    $dept = json_decode($data_edit->dept, true);

    if(is_array($dept) && count($dept) > 0){
        foreach($dept as $d){
            echo "<input type='text' class='form-control' value='$d' disabled style='margin-bottom:5px'>";
        }
    } else {
        echo "<input type='text' class='form-control' value='".$data_edit->dept."' disabled>";
    }
    ?>

  </div>
</div>

<!-- Nama PPC -->
<div class="form-group">
  <label class="control-label col-lg-2">Nama PPC</label>
  <div class="col-lg-10">
    <input type="text" disabled value="<?=$data_edit->name_ppc;?>" class="form-control">
  </div>
</div>

<!-- Catatan -->
<div class="form-group">
  <label class="control-label col-lg-2">Catatan</label>
  <div class="col-lg-10">
    <textarea class="form-control" disabled><?=$data_edit->catatan;?></textarea>
  </div>
</div>

</form>

<a href="<?=base_index();?>incoming-terima" class="btn btn-success">
  <i class="fa fa-step-backward"></i> <?php echo $lang["back_button"];?>
</a>

</div>
</div>
</div>
</div>
</section>