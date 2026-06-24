<?php
session_start();
include "../../inc/config.php";
session_check_json();
switch ($_GET["act"]) {

  case "detail_barang":

$no_ro = $_POST['no_ro'];

$header = $db->fetch_single_row(
    "ro",
    "no_ro",$no_ro
);

echo '<h4>No RO : '.$no_ro.'</h4>';

if($header->jenis_ro=="bom")
{

    echo '<h4>Barang Jadi</h4>';

    echo '<table class="table table-bordered">';
    echo '<tr>
            <th>Kode BJ</th>
            <th>Qty Order</th>
          </tr>';

    $bom = $db->query("
        SELECT *
        FROM ro_bom
        WHERE no_ro='$no_ro'
    ");

    foreach($bom as $b)
    {
        echo '<tr>
                <td>'.$b->kode_bom.'</td>
                <td>'.number_format($b->qty_order,2).'</td>
              </tr>';
    }

    echo '</table>';
}

echo '<h4>Detail Material</h4>';

echo '<table class="table table-bordered">';
echo '<tr>
        <th>No</th>
        <th>Kode</th>
        <th>Nama Barang</th>
        <th>Qty</th>
        <th>Keterangan</th>
      </tr>';

$no=1;

$detail = $db->query("
    SELECT rd.*,
           b.nm_barang,
           b.satuan
    FROM ro_detail rd
    LEFT JOIN barang b
        ON b.kd_barang=rd.kode
    WHERE rd.no_ro='$no_ro'
");

foreach($detail as $d)
{
    echo '<tr>
            <td>'.$no.'</td>
            <td>'.$d->kode.'</td>
            <td>'.$d->nm_barang.'</td>
            <td align="right">'.number_format($d->jumlah,5).'</td>
             <td>'.$d->ket.'</td>
          </tr>';

    $no++;
}

echo '</table>';

break;

  case "cari_bom":

    $term = isset($_POST['term']) ? $_POST['term'] : '';

    $query = $db->query("
        SELECT
            id,
            kodebj,
            nm_barang
        FROM bom
        WHERE
            kodebj LIKE '%$term%'
            OR nm_barang LIKE '%$term%'
        ORDER BY kodebj
        LIMIT 20
    ");

    $data = array();

    foreach($query as $row){

        $data[] = array(
            'id'        => $row->id,
            'kodebj'    => $row->kodebj,
            'nm_barang' => $row->nm_barang
        );

    }

    echo json_encode($data);

break;

  case 'load_bom':
  $id_bom = $_POST['id_bom'];
$qty    = $_POST['qty'];

$q = $db->query("
SELECT
    d.*,
    b.nm_barang,
    b.satuan
FROM bom_detail d
LEFT JOIN barang b
    ON b.kd_barang=d.kodebb
WHERE d.id_bom='$id_bom'
");

echo '

<table class="table table-bordered">

<tr>

<th>Kode</th>

<th>Nama</th>

<th>Qty BOM</th>

<th>Kebutuhan</th>

</tr>

';

foreach($q as $r){

$need =
$r->jumlah * $qty;

echo '

<tr>

<td>'.$r->kodebb.'</td>

<td>'.$r->nm_barang.'</td>

<td>'.$r->jumlah.'</td>

<td><b>'.$need.'</b></td>

</tr>

';

}

echo '</table>';

break;
    # code...
    break;

   case "detail_barang":
    ?>
    <table class="table">
       <thead>
         <tr>
           <th>No</th>
           <th>Kode</th>
           <th>Nama Barang</th>
           <th>Jumlah RO</th>
         </tr>
       </thead>
       <tbody>
         
       
    <?php
     $no_ro = $_POST['no_ro'];
     $q = $db->query("select b.kd_barang,b.nm_barang,r.jumlah from ro_detail r join barang b on b.kd_barang=r.kode
       where r.no_ro='$no_ro' ");
     $no=1;
     foreach ($q as $k) {
       echo "<tr>
        <td>$no</td>
        <td>$k->kd_barang</td>
        <td>$k->nm_barang</td>
        <td>$k->jumlah</td>
       </tr>";
       $no++;
     }
     ?>
     </tbody>
    </table>
     <?php
  break;

   case "update_jml_order":
    $id = $_POST['id'];
    $jml = $_POST['jml'];
    //echo "update ro_barang_temp set jml='$jml' where id='$id' ";
    $db->query("update ro_barang_temp set jml='$jml' where kd_barang='$id' and user='".$_SESSION['username']."'  "); 
     break;

   case "hapus_material":
     $id= $_POST['id'];
     $db->query("delete from ro_barang_temp where kd_barang='$id' and user='".$_SESSION['username']."' ");
     break; 

  case "get_temp_barang":
  $q = $db->query("select * from v_ro_barang where barang_temp='1' and user='".$_SESSION['username']."' ");
  foreach ($q as $k) {
    $jml_order = 1;
                        if ($k->jml!='') {
                         $jml_order = $k->jml;
                        }
    echo "<tr>
    <td><a style='cursor:pointer' class='btn btn-danger' onclick='hapus_material($k->id_bom)'><i class='fa fa-minus'></i></a></td>
    <td>$k->kd_barang</td>
    <td>$k->nm_barang</td>
    <td>$k->satuan</td>
     <td><input type='text' class='form-control' value='$jml_order' onkeyup='update_jml_order($k->id_bom,this.value)' ></td> 
    </tr>";
  }
    break;
  
  case "temp_barang":
  //echo "select * from ro_barang_temp where id='".$_POST['id']."'  ";
    $query = "";
    if ($_POST['cek']=='1') {
       $q = $db->query("select * from ro_barang_temp where kd_barang='".$_POST['id']."' and user='".$_SESSION['username']."'");
       if ($q->rowCount()==0) {
         $query = "insert into ro_barang_temp (kd_barang,user,jml) values('".$_POST['id']."','".$_SESSION['username']."','1') ";
          $db->query($query); 
       }       
    }else{
          $query = "delete from ro_barang_temp where kd_barang='".$_POST['id']."' and user='".$_SESSION['username']."' "; 
         $db->query($query); 
       }
     //  echo "$query";

  break;

  case "get_detail_bom":
  //$id = $_POST['id']; 
  $jml = 1;
  if ($_POST['jml']!='') {
    $jml = $_POST['jml'];
  }
  //echo "$id";
  ?>
  <label for="Kurs" class="control-label col-lg-2"> </label>
                 <div class="col-lg-10">
                   <table class="table">
                     <thead> 
                       <tr>
                         <th style="width:50px;text-align: center">
                           <a style="cursor: pointer;" onclick="add_baris()" ><i class="fa fa-plus"></i> </a>
                         </th>
                         <th>Kode Bahan Baku</th>                     
                         <th>Qty</th>                
                         <th>Lokasi</th>
                       </tr>
                     </thead>
                     <tbody id="isi_tabel">
                      <?php
                      $no=1;
                      $qq = $db->query("select b.id,b.kodebj,v.jml,v.nm_barang from v_ro_barang v join bom b on b.kodebj=v.kd_barang where barang_temp='1' and user='".$_SESSION['username']."' ");
                      foreach ($qq as $kk) {
                        // $jml = 1;
                        // if ($kk->jml!='') {
                        //  $jml = $kk->jml;
                        // }
                    //  echo "select * from bom_detail where id_hd='$kk->id' ";
                      $q = $db->query("select d.*,b.nm_barang from bom_detail d left join barang b on b.kd_barang=d.kodebb where d.id_bom='$kk->id'  "); 
                      foreach ($q as $k) {
                     
                      ?>
                       <tr id="baris_<?= $no ?>">
                         <td style="text-align: center"><a style="cursor: pointer;" onclick="hapus_baris('<?= $no ?>')" ><i class="fa fa-trash-o" style="font-size: 25px;"></i></a> </td>
                         <td><input type="text" value="<?= $k->kodebb." ".$k->nm_barang ?>" id="form_kode_1" placeholder="Kode Barang" onclick="cari_kode('<?= $no ?>')" class="form-control" name="kode[]"  >
                          <input type="hidden" value="<?= $k->kodebb ?>" name="kode_input[]" id="kode_input_1"> 
                         </td> 
                        
                         <td><input type="text"  id="form_qty_<?= $no ?>" value="<?= ($k->jumlah * $jml) ?>" class="form-control" name="jumlah[]" ></td>
                        
                         <td><input type="text" id="form_ket_1"  class="form-control" name="ket[]" ></td>
                       </tr>
                       <?php
                       $no++;
                     }
                   }
                       ?>
                     </tbody>
                   </table>
                 </div>
               <input type="hidden" id="jml" value="<?= $no ?>">
  <?php
    break;

   case "in":

    $nomor = get_nomor("ro","id");
    $no_ro = getNoRO(date("Y",strtotime($_POST["tgl_ro"])));

    $data = array(
        "nomor"      => $nomor,
        "no_ro"      => $no_ro,
        "tgl_ro"     => $_POST["tgl_ro"],
        "dept"       => $_POST["dept"],
        "name_ppc"   => $_POST["name_ppc"],
        "jenis_ro"   => $_POST["jenis_ro"],
        "tujuan"     => $_POST["tujuan"],
        "catatan"    => $_POST["catatan"]
    );

    $db->insert("ro",$data);

    $db->query("DELETE FROM ro_detail WHERE no_ro='$no_ro'");
    $db->query("DELETE FROM ro_bom WHERE no_ro='$no_ro'");
    $db->query("DELETE FROM ro_bom_detail WHERE no_ro='$no_ro'");

    $no = 1;

    /*
    |--------------------------------------------------------------------------
    | BOM MODE
    |--------------------------------------------------------------------------
    */

    if($_POST["jenis_ro"]=="bom")
    {

        if(isset($_POST['id_bom']) && is_array($_POST['id_bom']))
        {

            foreach($_POST['id_bom'] as $k=>$id_bom)
            {

                if(trim($id_bom)=='')
                    continue;

                $qty_order = isset($_POST['jml_bom'][$k])
                    ? floatval($_POST['jml_bom'][$k])
                    : 1;

                $headerBom = $db->fetch_single_row(
                    "bom",
                    "id",
                    $id_bom
                );

                if(!$headerBom)
                    continue;

                $db->insert("ro_bom",array(
                    "no_ro"        => $no_ro,
                    "id_bom"       => $id_bom,
                    "kode_bom"       => $headerBom->kodebj,
                    "qty_order"    => $qty_order,
                    "date_created" => date("Y-m-d H:i:s")
                ));

                $detailBom = $db->query("
                    SELECT *
                    FROM bom_detail
                    WHERE id_bom='".$id_bom."'
                ");

                foreach($detailBom as $bom)
                {

                    $qty_kebutuhan =
                        floatval($bom->jumlah) * $qty_order;

                    $db->insert("ro_bom_detail",array(
                        "no_ro"          => $no_ro,
                        "id_bom"         => $id_bom,
                        "kodebj"         => $headerBom->kodebj,
                        "kodebb"         => $bom->kodebb,
                        "qty_bom"        => $bom->jumlah,
                        "qty_kebutuhan"  => $qty_kebutuhan
                    ));

                    $db->insert("ro_detail",array(
                        "nomor"         => $nomor,
                        "no_ro"         => $no_ro,
                        "tgl_ro"        => $_POST["tgl_ro"],
                        "kode"          => $bom->kodebb,
                        "jumlah"        => $qty_kebutuhan,
                        "row_no"        => $no,
                        "ket"           => "Generate BOM ".$headerBom->kodebj,
                        "jenis_detail"  => "bom",
                        "id_bom"        => $id_bom
                    ));

                    $no++;
                }
            }
        }

        $qr = $db->query("
            SELECT *
            FROM ro_barang_temp
            WHERE user='".$_SESSION['username']."'
        ");

        foreach($qr as $kr)
        {

            $headerBom = $db->fetch_single_row(
                "bom",
                "id",$kr->kd_barang
            );

            if(!$headerBom)
                continue; 

            $db->insert("ro_bom",array(
                "no_ro"        => $no_ro,
                "id_bom"       => $kr->kd_barang,
                "kode_bom"       => $headerBom->kodebj,
                "qty_order"    => $kr->jml,
                "date_created" => date("Y-m-d H:i:s")
            ));

            $detailBom = $db->query("
                SELECT *
                FROM bom_detail
                WHERE id_bom='".$kr->kd_barang."'
            ");

            foreach($detailBom as $bom)
            {

                $qty_kebutuhan =
                    floatval($bom->jumlah) * floatval($kr->jml);

                $db->insert("ro_bom_detail",array(
                    "no_ro"          => $no_ro,
                    "id_bom"         => $kr->kd_barang,
                    "kodebj"         => $headerBom->kodebj,
                    "kodebb"         => $bom->kodebb,
                    "qty_bom"        => $bom->jumlah,
                    "qty_kebutuhan"  => $qty_kebutuhan
                ));

                $db->insert("ro_detail",array(
                    "nomor"         => $nomor,
                    "no_ro"         => $no_ro,
                    "tgl_ro"        => $_POST["tgl_ro"],
                    "kode"          => $bom->kodebb,
                    "jumlah"        => $qty_kebutuhan,
                    "row_no"        => $no,
                    "ket"           => "Generate BOM ".$headerBom->kodebj,
                    "jenis_detail"  => "bom",
                    "id_bom"        => $kr->kd_barang
                ));

                $no++;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MANUAL MODE
    |--------------------------------------------------------------------------
    */

    if($_POST["jenis_ro"]=="manual")
    {

        if(isset($_POST['kode_manual']) && is_array($_POST['kode_manual']))
        {

            foreach($_POST['kode_manual'] as $key=>$value)
            {

                $kode_manual = trim($_POST['kode_manual'][$key]);

                if($kode_manual=='')
                    continue;

                $qty_manual = isset($_POST['qty_manual'][$key])
                    ? $_POST['qty_manual'][$key]
                    : 0;

                $ket_manual = isset($_POST['ket_manual'][$key])
                    ? $_POST['ket_manual'][$key]
                    : '';

                $db->insert("ro_detail",array(
                    'nomor'         => $nomor,
                    'no_ro'         => $no_ro,
                    'tgl_ro'        => $_POST["tgl_ro"],
                    'kode'          => $kode_manual,
                    'jumlah'        => $qty_manual,
                    'row_no'        => $no,
                    'ket'           => $ket_manual,
                    'jenis_detail'  => 'manual',
                    'id_bom'        => NULL
                ));

                $no++;
            }
        }
    }

    $db->query("
        DELETE FROM ro_barang_temp
        WHERE user='".$_SESSION['username']."'
    ");

    action_response($db->getErrorMessage());

break;


  
  case "delete": 
    
    
    
    $db->delete("ro","id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("ro","",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
    $nomor = $_POST['nomor'];
    $no_ro = $_POST['no_ro']; 
    $data = array(
        "nomor"    => $nomor, 
        "no_ro"    => $no_ro, 
        "tgl_ro"   => $_POST["tgl_ro"],
       // "dept"     => $_POST["dept"],
        "name_ppc" => $_POST["name_ppc"],
      //  "id_bom"   => $_POST["id_bom"],
     //   "jml_brg_jadi" => $_POST["jml_brg_jadi"],
      //  "tujuan"   => $_POST["tujuan"],
        "catatan"  => $_POST["catatan"],
    );
   $up= $db->update("ro",$data,"no_ro",$no_ro);   

     $db->query("delete from ro_detail where no_ro='$no_ro' ");
   $no=1;
   foreach ($_POST['kode'] as $key => $value) { 
      $data_detail = array('nomor' => $nomor , 
                    'no_ro' => $no_ro,
                    'tgl_ro' => $_POST["tgl_ro"],
                    'kode' => $_POST['kode_input'][$key],
                    'jumlah' => ($_POST['jumlah'][$key]), 
                    'row_no' => $no, 
                    'ket' => $_POST['ket'][$key]
                  );
        $db->insert("ro_detail",$data_detail);  
       // print_r($data_detail);
      $no++;
   }
   
   
   

    
    
  //  $up = $db->update("ro",$data,"",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>