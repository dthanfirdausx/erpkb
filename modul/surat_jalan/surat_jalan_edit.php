<?php




// ================= DETAIL =================
$q_detail = $db->query("
SELECT d.*, b.nm_barang, b.satuan
FROM surat_jalan_detail d
LEFT JOIN barang b ON b.kd_barang=d.kode
WHERE d.surat_jalan_id=?
ORDER BY d.row_no
", [$id]);
?>

<section class="content">
<div class="row">
<div class="col-lg-12">
<div class="box box-solid box-primary">
<div class="box-header">
<h3 class="box-title">Edit Surat Jalan</h3>
</div>

<div class="box-body">

<form id="input_surat_jalan" method="post"
action="<?=base_admin();?>modul/surat_jalan/surat_jalan_action.php?act=update"
class="form-horizontal">

<input type="hidden" name="id" value="<?= $data_edit->id ?>">
<input type="hidden" name="id_sales_order" value="<?= $data_edit->id_sales_order ?>">

<!-- ================= HEADER ================= -->

<div class="form-group">
<label class="control-label col-lg-2">No Surat Jalan</label>
<div class="col-lg-4">
<input type="text" class="form-control" value="<?= $data_edit->no_surat_jalan ?>" readonly>
</div>
</div>

<div class="form-group">
<label class="control-label col-lg-2">Tanggal</label>
<div class="col-lg-3">
<input type="date" name="tgl_surat_jalan" class="form-control"
value="<?= $data_edit->tgl_surat_jalan ?>">
</div>
</div>

<div class="form-group">
<label class="control-label col-lg-2">Penerima</label>
<div class="col-lg-10">
<input type="text" class="form-control" value="<?= $data_edit->nama_penerima ?>" readonly>
</div>
</div>

<div class="form-group">
<label class="control-label col-lg-2">Alamat</label>
<div class="col-lg-10">
<textarea name="alamat_pengiriman" class="form-control"><?= $data_edit->alamat_pengiriman ?></textarea>
</div>
</div>

<div class="form-group">
<label class="control-label col-lg-2">No Kendaraan</label>
<div class="col-lg-10">
<input type="text" name="no_kendaraan" class="form-control"
value="<?= $data_edit->no_kendaraan ?>">
</div>
</div>

<div class="form-group">
<label class="control-label col-lg-2">Attn</label>
<div class="col-lg-10">
<input type="text" name="attn" class="form-control"
value="<?= $data_edit->attn ?>">
</div>
</div>

<div class="form-group">
<label class="control-label col-lg-2">Keterangan</label>
<div class="col-lg-10">
<textarea name="keterangan" class="form-control"><?= $data_edit->keterangan ?></textarea>
</div>
</div>

<hr>

<!-- ================= DETAIL ================= -->

<div id="panel_barang">
<table class="table table-bordered">
<thead>
<tr>
<th>No</th>
<th>Kode</th>
<th>Nama</th>
<th>Packing</th>
<th>Satuan Packing</th>
<th>Qty Kirim</th>
<th>Satuan</th>
<th>Keterangan</th>
<th>Aksi</th>
</tr>
</thead>

<tbody id="isi_tabel">

<?php 
$no = 1;
foreach($q_detail as $d){ 
?>
<tr id="row_<?= $no ?>">

<td><?= $no ?></td>

<td>
<input type="text" class="form-control" value="<?= $d->kode ?>" readonly>
<input type="hidden" name="kode_barang[]" value="<?= $d->kode ?>">
<input type="hidden" name="id_detail[]" value="<?= $d->id ?>">
</td>

<td>
<input type="text" class="form-control" value="<?= $d->nm_barang ?>" readonly>
</td>

<td>
<input type="text" name="packing[]" class="form-control"
value="<?= $d->packing ?>">
</td>

<td>
<input type="text" name="satuan_packing[]" class="form-control"
value="<?= $d->satuan_packing ?>">
</td>

<td>
<input type="number" step="0.01" name="qty_kirim[]" 
class="form-control text-right"
value="<?= $d->qty_kirim ?>">
</td>

<td>
<input type="text" class="form-control" value="<?= $d->satuan ?>" readonly>
<input type="hidden" name="satuan[]" value="<?= $d->satuan ?>">
</td>

<td>
<input type="text" name="keterangan_barang[]" 
class="form-control"
value="<?= $d->keterangan ?>">
</td>

<td>
<button type="button" class="btn btn-danger btn-sm"
onclick="hapus_row(<?= $no ?>)">
<i class="fa fa-trash"></i>
</button>
</td>

</tr>
<?php $no++; } ?>

</tbody>
</table>
</div>

<div class="form-group">
<div class="col-lg-10 col-lg-offset-2">
<a href="<?=base_index();?>surat-jalan" class="btn btn-default">Kembali</a>
<button type="submit" class="btn btn-primary">Update</button>
</div>
</div>

</form>

</div>
</div>
</div>
</div>
</section>

<script>
function hapus_row(id){
    $("#row_"+id).remove();
}
</script>