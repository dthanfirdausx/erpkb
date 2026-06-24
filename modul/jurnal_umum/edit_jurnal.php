<?php
if (!function_exists('fin_t')) {
  function fin_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('fin_h')) {
  function fin_h($key, $fallback = '') { return htmlspecialchars((string) fin_t($key, $fallback), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('fin_js')) {
  function fin_js($key, $fallback = '') { return json_encode(fin_t($key, $fallback), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); }
}
include "../../inc/config.php";

$id = $_GET['id'];

$header = $db->fetch_single_row("jurnal_header","id",$id);

?> 

<form id="form_edit_jurnal"
      method="post"
      action="<?=base_admin();?>modul/jurnal_umum/jurnal_umum_action.php?act=update">

    <input type="hidden"
           name="id"
           value="<?= $header->id ?>">

    <div class="modal-header bg-primary">

        <button type="button"
                class="close"
                data-dismiss="modal">

            &times;

        </button>

        <h4 class="modal-title">
            Edit Jurnal Umum
        </h4>

    </div>

    <div class="modal-body">

        <div class="row">

            <div class="col-md-4">

                <label>No Jurnal</label>

                <input type="text"
                       name="no_jurnal"
                       value="<?= $header->no_jurnal ?>"
                       class="form-control"
                       readonly>

            </div>

            <div class="col-md-4">

                <label>Tanggal</label>

                <input type="text"
                       name="tgl_jurnal"
                       value="<?= $header->tgl_jurnal ?>"
                       class="form-control">

            </div>

            <div class="col-md-4">

                <label>No Bukti</label>

                <input type="text"
                       name="no_bukti"
                       value="<?= $header->no_bukti ?>"
                       class="form-control">

            </div>

        </div>

        <br>

        <div class="form-group">

            <label>Keterangan</label>

            <textarea name="ket"
                      class="form-control"><?= $header->ket ?></textarea>

        </div>
        <hr>

<h4>Detail Jurnal</h4>

<table class="table table-bordered" id="table_detail_edit">

    <thead>

        <tr>

            <th width="35%"><?=fin_h('finance_coa', 'COA');?></th>
            <th width="20%">Debet</th>
            <th width="20%">Kredit</th>
            <th width="15%">Valuta</th>

        </tr>

    </thead>

    <tbody>

<?php

$detail = $db->query("
    SELECT *
    FROM jurnal_detail
    WHERE id_header = '$id'
");

$total_debet  = 0;
$total_kredit = 0;

foreach($detail as $d){

    $total_debet  += $d->debet;
    $total_kredit += $d->kredit;

?>

        <tr>

            <td>

                <select name="no_rek[]"
                        class="form-control select2"
                        style="width:100%">

                    <option value="">Pilih COA</option>

                    <?php

                    foreach($db->fetch_all("rekening") as $r){

                        $selected = ($r->no_rek == $d->no_rek)
                                        ? 'selected'
                                        : '';

                        echo "

                            <option value='$r->no_rek' $selected>

                                $r->no_rek - $r->nama_rek

                            </option>

                        ";

                    }

                    ?>

                </select>

            </td>

            <td>

                <input type="text"
                       name="debet[]"
                       value="<?= $d->debet ?>"
                       class="form-control">

            </td>

            <td>

                <input type="text"
                       name="kredit[]"
                       value="<?= $d->kredit ?>"
                       class="form-control">

            </td>

            <td>

                <select name="valuta[]"
                        class="form-control select2">

                    <?php

                    foreach($db->query("select * from matauang group by jenis_valas") as $v){

                        $selected = ($v->jenis_valas == $d->valuta)
                                        ? 'selected'
                                        : '';

                        echo "

                            <option value='$v->jenis_valas' $selected>

                                $v->jenis_valas

                            </option>

                        ";

                    }

                    ?>

                </select>

            </td>

        </tr>

<?php

}

?>

    </tbody>

    <tfoot>

        <tr>

            <th align="right">
                TOTAL
            </th>

            <th>

                <input type="text"
                       class="form-control"
                       value="<?= number_format($total_debet,2) ?>"
                       readonly>

            </th>

            <th>

                <input type="text"
                       class="form-control"
                       value="<?= number_format($total_kredit,2) ?>"
                       readonly>

            </th>

            <th></th>

        </tr>

    </tfoot>

</table>

    </div>

    <div class="modal-footer">

        <button type="button"
                class="btn btn-default"
                data-dismiss="modal">

            <?=fin_h('common_close', 'Close');?>

        </button>

        <button type="submit"
                class="btn btn-primary">

            <i class="fa fa-save"></i>
            <?=fin_h('common_update', 'Update');?>

        </button>

    </div>

</form>