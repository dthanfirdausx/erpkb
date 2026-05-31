<?php

// ======================================
// STATUS SALES ORDER
// ====================================== 
$status_so = $db->fetch("
    SELECT *
    FROM v_sales_status
    WHERE id_sales_order = '$data_edit->id_sales_order'
");

// ======================================
// DATA PRODUKSI
// ======================================
$q_produksi = $db->query("
SELECT
    b.id_produksi,
    b.no_bpb,
    b.tgl_bpb,

    d.kode,
    br.nm_barang,

    d.jumlah,
    d.qty_ng

FROM brgjadi b

INNER JOIN brgjadi_detail d
    ON b.id_produksi = d.id_produksi

LEFT JOIN barang br
    ON d.kode = br.kd_barang

WHERE b.no_sales_order = '$data_edit->no_sales_order'

ORDER BY b.tgl_bpb ASC
");

// ======================================
// DATA SURAT JALAN
// ======================================
$q_sj = $db->query("
SELECT
    sj.id,
    sj.no_surat_jalan,
    sj.tgl_surat_jalan,
    sj.status,

    d.kode_barang,
    d.nama_barang,

    d.qty_order,
    d.qty_kirim,
    d.satuan

FROM surat_jalan sj

INNER JOIN surat_jalan_detail d
    ON sj.id = d.surat_jalan_id

WHERE sj.no_sales_order = '$data_edit->no_sales_order'

AND sj.status != 'dibatalkan'

ORDER BY sj.tgl_surat_jalan ASC
");
?>

<!-- ====================================== -->
<!-- STATUS SALES ORDER -->
<!-- ====================================== -->

<div class="col-lg-12">

<div class="box box-solid box-info">

    <div class="box-header">

        <h3 class="box-title">

            <i class="fa fa-info-circle"></i>

            Status Sales Order

        </h3>

    </div>

    <div class="box-body">

        <!-- ====================================== -->
        <!-- SUMMARY -->
        <!-- ====================================== -->

        <div class="row">

            <div class="col-md-3">

                <label>Status SO</label><br>

                <?php

                $label = "label-default";

                switch($status_so->status_so){

                    case "BELUM PRODUKSI":
                        $label = "label-default";
                    break;

                    case "PRODUKSI BELUM FULL":
                        $label = "label-warning";
                    break;

                    case "PROSES PRODUKSI":
                        $label = "label-primary";
                    break;

                    case "DIKIRIM SEBAGIAN":
                        $label = "label-info";
                    break;

                    case "SUDAH DIKIRIM":
                        $label = "label-success";
                    break;
                }

                ?>

                <span class="label <?= $label ?>"
                      style="font-size:14px">

                    <?= $status_so->status_so ?>

                </span>

            </div>

            <div class="col-md-3">

                <label>Qty SO</label>

                <input type="text"
                       class="form-control"
                       readonly
                       value="<?= number_format($status_so->qty_so,4) ?>">

            </div>

            <div class="col-md-3">

                <label>Qty Produksi</label>

                <input type="text"
                       class="form-control"
                       readonly
                       value="<?= number_format($status_so->qty_produksi,4) ?>">

            </div>

            <div class="col-md-3">

                <label>Qty Delivery</label>

                <input type="text"
                       class="form-control"
                       readonly
                       value="<?= number_format($status_so->qty_kirim,4) ?>">

            </div>

        </div>

        <hr>

        <!-- ====================================== -->
        <!-- INFORMASI PRODUKSI -->
        <!-- ====================================== -->

        <?php if($q_produksi->rowCount() > 0){ ?>

        <div class="row">

            <div class="col-md-12">

                <h4>
                    <i class="fa fa-industry"></i>
                    Informasi Produksi
                </h4>

                <table class="table table-bordered table-striped">

                    <thead>

                        <tr>

                            <th>No Produksi</th>
                            <th>Tanggal</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Qty OK</th>
                            <th>Qty NG</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach($q_produksi as $prd){ ?>

                        <tr>

                            <td>

                              <!--   <a target="_blank"
                                   href="<?=base_index();?>lp-barang-jadi/detail/<?= $prd->id_produksi ?>"> -->

                                   <?= $prd->no_bpb ?>

                            <!--     </a> -->

                            </td>

                            <td><?= tgl_indo($prd->tgl_bpb) ?></td>

                            <td><?= $prd->kode ?></td>

                            <td><?= $prd->nm_barang ?></td>

                            <td style="text-align:right">
                                <?= number_format($prd->jumlah,4) ?>
                            </td>

                            <td style="text-align:right">
                                <?= number_format($prd->qty_ng,4) ?>
                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

        <?php } ?>

        <!-- ====================================== -->
        <!-- INFORMASI SURAT JALAN -->
        <!-- ====================================== -->

        <?php if($q_sj->rowCount() > 0){ ?>

        <div class="row">

            <div class="col-md-12">

                <h4>
                    <i class="fa fa-truck"></i>
                    Informasi Delivery / Surat Jalan
                </h4>

                <table class="table table-bordered table-striped">

                    <thead>

                        <tr>

                            <th>No Surat Jalan</th>
                            <th>Tanggal</th>
                           
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                          
                            <th>Qty Kirim</th>
                            <th>Satuan</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach($q_sj as $sj){ ?>

                        <?php

                        $label_sj = "label-default";

                        switch($sj->status){

                            case "draft":
                                $label_sj = "label-default";
                            break;

                            case "dikirim":
                                $label_sj = "label-primary";
                            break;

                            case "diterima":
                                $label_sj = "label-success";
                            break;

                            case "dibatalkan":
                                $label_sj = "label-danger";
                            break;
                        }

                        ?>

                        <tr>

                            <td>

                               <!--  <a target="_blank"
                                   href="<?=base_index();?>surat-jalan/detail/<?= $sj->id ?>"> -->

                                   <?= $sj->no_surat_jalan ?>

                              <!--   </a> -->

                            </td>

                            <td>
                                <?= tgl_indo($sj->tgl_surat_jalan) ?>
                            </td>

                         

                            <td><?= $sj->kode_barang ?></td>

                            <td><?= $sj->nama_barang ?></td>

                         

                            <td style="text-align:right">
                                <?= number_format($sj->qty_kirim,4) ?>
                            </td>

                            <td><?= $sj->satuan ?></td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

        <?php } ?>

    </div>

</div>

</div>