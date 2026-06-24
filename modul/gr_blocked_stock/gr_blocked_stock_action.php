<?php

if (!function_exists('wh_t')) {
  function wh_t($key, $fallback = '') { return lang_text($key, $fallback); }
}
if (!function_exists('wh_h')) {
  function wh_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
}
error_reporting(0);
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php";
require_once "../../inc/accounting_journal.php";
require_once "../../inc/gr_reversal.php";
function cek_valid_tgl($tgl)
{
   $t = explode("-", $tgl);
   if (count($t)>1) {
      return true;
   }else{
    return false;
   }
}
function konversi_tgl($tgl)
{
   $t = explode(" ", $tgl);
   $tg = array('Jan' => '01', 
               'Feb' => '02',
               'Mar' => '03',
               'Apr' => '04',
               'May' => '05',
               'Jun' => '06',
               'Jul' => '07',
               'Aug' => '08',
               'Sep' => '09',
               'Oct' => '10',
               'Nov' => '11',
               'Dec' => '12');
    $tgl = $t[2]."-".$tg[$t[1]]."-".$t[0];
    return $tgl;
}

function konversi_dokpab($jenis_dokpab)
{
  $data = array('23' => 'BC 2.3', 
                '27' => 'BC 2.7',
                '40' => 'BC 4.0',
                '262' => 'BC 2.6.2',);
  return $data[$jenis_dokpab];
}

function cek_kode($kode,$nm_barang,$satuan)
{
  global $db;
  $q = $db->query("select * from barang where kd_barang='$kode' ");
  if ($q->rowCount()==0) {
    $data = array('kd_barang' => $kode , 
                  'nm_barang' => $nm_barang,
                  'kd_kategori' => 'K01',
                  'satuan' => $satuan,
                  'status' => '1');
    $db->insert("barang",$data); 
  }
  //return $data[$kode];
}

function gr_blocked_rollback_response($message)
{
  global $db;
  $db->query('ROLLBACK');
  action_response($message);
}

session_check_json();
switch ($_GET["act"]) { 

  case "reversal":
    $result = gr_perform_full_reversal(
      $_POST['id'],
      isset($_POST['reason']) ? $_POST['reason'] : '',
      isset($_POST['reversal_date']) ? $_POST['reversal_date'] : date('Y-m-d')
    );
    if ($result['status'] === 'good') {
      action_response('', $result['data']);
    }
    action_response($result['message']);
    break;

 case "upload_excel":
  action_response('Import Excel legacy GR Blocked Stock dikunci. Gunakan form GR Blocked Stock terbaru agar stock layer, dokumen material, dan jurnal tetap konsisten.');
  break;
  // echo "xxxxx";
  // // error_reporting(E_ALL);
  //  // print_r($_FILES);
  //  die();
  unlink($_FILES['fileupload']['tmp_name'], "../../upload/import_data/".$_FILES['fileupload']['name']);
   move_uploaded_file($_FILES['fileupload']['tmp_name'], "../../upload/import_data/".$_FILES['fileupload']['name']);
   $Reader = new SpreadsheetReader("../../upload/import_data/".$_FILES['fileupload']['name']); 
  $Sheets = $Reader->Sheets(); 
  $data = array();
  $no=1;
  //echo "<pre>"; 
  $sukses=0;
  $duplikat=0;
  $val = array(); 
  $ada_data = 0;
  $db->query("truncate import_pemasukan_temp"); 
  $q = $db->query("show columns from import_pemasukan_temp");
  foreach ($q as $k) {
     $kol[] = $k->Field;
  }
  unset($kol[0]);
  unset($kol[35]);
  $datax = array(); 
  $kolom = implode(',', $kol);
  $query_insert = "insert into import_pemasukan_temp ($kolom) values ";
 // echo $query_insert;
  // $db->fetch_custom("delete from tb_remun where bulan='".$_POST['bulan_upload']."' and tahun='".$_POST['tahun_upload']."' and untuk='blu' and ket_cair='".$_POST['ket_upload']."' and ket='".$_POST['ket_kategori']."' ");
  //echo "<pre>";
 // print_r($Sheets);
 // $dat  = array();
  $error_tgl = array('tgl_invoice' => 0,
                     'tgl_aju'     => 0,
                     'tgl_dokpab'  => 0,
                     'tgl_bpb2'    => 0);
  $data_detail  = array();
  $error = 0;
  $sukses = 0;
  $simpan_detail = true; 
   $duplikat_no = "";
  $sukses_upload = "";
  $error = 0;
  $sukses = 0;
 // $data_detail  = array();
   foreach ($Sheets as $Index => $Name)
  {
    //echo "$Index,";
    $Reader->ChangeSheet($Index);
    if ($Index==0) {
      $mulai = false;
    $i=0;
   // $dat = array();
    foreach ($Reader as $r)  
    {
      if ($i>0) {
        $dat = array();
        $x=0; 
       foreach ($r as $kk => $vv) {  
           if ($r[0]!='') {
             if (!cek_valid_tgl($r[5])) {
                $error_tgl['tgl_invoice'] = 1;
                 $simpan_detail = false;  
                $error++;
              }
              if (!cek_valid_tgl($r[8]) && $_POST['tujuan']=='1' ) {
                //echo "x ";
                $error_tgl['tgl_aju'] = 1;
                 $simpan_detail = false; 
                $error++; 
              }
               if (!cek_valid_tgl($r[12])) {
                $error_tgl['tgl_dokpab'] = 1;
                 $simpan_detail = false; 
                $error++;
              }
               if (!cek_valid_tgl($r[21])) {
                $error_tgl['tgl_bpb2'] = 1;
                 $simpan_detail = false; 
                $error++;
              }
              $dat[$x] = "'".$vv."'"; 
               $x++;
           }
         
       }
       //print_r($dat);  
        if (count($dat)>1) { 
        $datax[] = "(".implode(",", $dat).")"; 
       }
       
      // echo "$data <br>";
      // print_r($dat);
      }
      $i++;
    }
    $isi = implode(",", $datax);
  }
    
  }
  $query_insert .= $isi;
  //echo "$query_insert";
  $db->query($query_insert); 
  // echo $db->getErrorMessage();
  //echo "<pre>";
  //echo "$query_insert";
  if ($_POST['tujuan']=='1') {
     $q = $db->query("select * from import_pemasukan_temp group by no_aju order by tgl_bpb ");
   }elseif ($_POST['tujuan']!='1') {  
       $q = $db->query("select * from import_pemasukan_temp group by no_dokpab having no_dokpab!='' order by tgl_dokpab");
   } 

 
foreach ($q as $k) {
   $data_pemasukan = (array)$k; 
 
  if ($_POST['tujuan']=='1') {
     $nomor = get_nomor('pemasukan','id');
    $no_bpb = getNoBPB(date("Y",strtotime($data_pemasukan['tgl_dokpab'])));
   }elseif ($_POST['tujuan']!='1') {  
     $nomor = get_nomor('pemasukan_baru','id'); 
     $no_bpb = getNoBPB(date("Y",strtotime($data_pemasukan['tgl_dokpab'])));
   } 
 
  unset($data_pemasukan['tgl_bpb2']);
 unset($data_pemasukan['tgl_bpb2']);
  unset($data_pemasukan['id']);
  unset($data_pemasukan['no_bpb2']);
  unset($data_pemasukan['kode']);
  unset($data_pemasukan['jumlah']); 
  unset($data_pemasukan['harga']);
  unset($data_pemasukan['valuta2']);
  unset($data_pemasukan['nilai']);
  unset($data_pemasukan['ket']); 
  unset($data_pemasukan['berat']);
  unset($data_pemasukan['satuan']);
  unset($data_pemasukan['kurs2']);
  unset($data_pemasukan['row_no']);

  unset($data_pemasukan['kd_kategori']); 
  $data_pemasukan['no_bpb'] = $no_bpb; 
  $data_pemasukan['date_created'] = date("Y-m-d H:i:s");  
  $data_pemasukan['nomor'] = $nomor;
  $data_pemasukan['migrasi'] = '14';
  $data_pemasukan['tgl_bpb'] = $data_pemasukan['tgl_dokpab'];
  $data_pemasukan['pemasok'] = $data_pemasukan['penerima'];
   unset($data_pemasukan['penerima']); 
   unset($data_pemasukan['uraian']);
  if ($_POST['tujuan']=='1') {
    $qc = $db->query("select id,no_bpb from pemasukan where no_dokpab='".$data_pemasukan['no_dokpab']."' 
    and year(tgl_bpb)='".date("Y",strtotime($data_pemasukan['tgl_bpb']))."'  ");
  }elseif ($_POST['tujuan']!='1') {
    $qc = $db->query("select id,no_bpb from pemasukan_baru where no_dokpab='".$data_pemasukan['no_dokpab']."'
    and year(tgl_bpb)='".date("Y",strtotime($data_pemasukan['tgl_bpb']))."'  ");
  } 
//var_dump($simpan_detail); 
 if ($simpan_detail) { 

  if ($qc->rowCount()==0) {
     if ($_POST['tujuan']=='1') {
       $simpan_pemasukan = $db->insert("pemasukan",$data_pemasukan);

      
     }elseif ($_POST['tujuan']!='1') {  
       $data_pemasukan['lokasi'] = $_POST['tujuan'];
       $simpan_pemasukan = $db->insert("pemasukan_baru",$data_pemasukan);
      // echo "xx ";
       
     }
    // echo $db->getErrorMessage();
     
    if ($simpan_pemasukan) {
      //echo $db->getErrorMessage();
       $sukses_upload .= $data_pemasukan['no_dokpab'].", ";
       $sukses++;
    }else{
       $simpan_detail = false;
    }
    
  }else{
     foreach ($qc as $kc) {
       $id_pemasukan = $kc->id;
       $no_bpb_lama = $kc->no_bpb;
     }
    if ($_POST['tujuan']=='1') { 
       $simpan_pemasukan = $db->update("pemasukan",$data_pemasukan,"id",$id_pemasukan);
       $db->query("delete from pemasukan_detail where no_bpb='$no_bpb_lama' ");
       $db->query("delete from stock_incoming where no_ref='$no_bpb_lama' ");
     }elseif ($_POST['tujuan']!='1') {  
       $data_pemasukan['lokasi'] = $_POST['tujuan'];
       $simpan_pemasukan =  $db->update("pemasukan_baru",$data_pemasukan,"id",$id_pemasukan);
       $db->query("delete from pemasukan_baru_detail where no_bpb='$no_bpb_lama' ");  
     }

    if ($simpan_pemasukan) {
      //echo $db->getErrorMessage();
        $duplikat_no .= $data_pemasukan['no_dokpab'].", ";
         $error++;
    }else{
       //echo $db->getErrorMessage()."<br>";
       $simpan_detail = false;
    }

    
  }  
  //print_r($data_pemasukan); 
 
    $no=1;
    if ($_POST['tujuan']=='1') {
        $qq = $db->query("select kode,jumlah,harga,valuta,nilai,berat from import_pemasukan_temp where no_aju='$k->no_aju'");
     }elseif ($_POST['tujuan']!='1') {  
        $qq = $db->query("select kode,jumlah,harga,valuta,nilai,berat from import_pemasukan_temp where no_dokpab='$k->no_dokpab'"); 
     }
 
    foreach ($qq as $kk) {
      $data_pemasukan_detail  = array('tgl_bpb' => $data_pemasukan['tgl_bpb'] , 
                                      'no_bpb' => $no_bpb ,
                                      'nomor' => $nomor ,
                                      'kode' => $kk->kode ,
                                      'jumlah' => $kk->jumlah ,
                                      'harga' => $kk->harga ,
                                      'valuta' => $kk->valuta,
                                      'nilai' => $kk->nilai,
                                      'berat' => $kk->berat,
                                      'migrasi'=> '14' ,
                                      'lokasi' => $_POST['tujuan'], 
                                      'no_urut' => $no , 
                                     // 'kd_kategori' => $k->kd_kategori 
                                  ); 
       $data_stock = array(
                     'kd_barang' => $kk->kode,
                     'no_aju' => $k->no_aju,
                     'tgl_aju' => $k->tgl_aju, 
                     'tgl_masuk' => $k->tgl_bpb,
                     'jenis_dokpab' => $k->jenis_dokpab,
                     'tgl_dokpab' => $k->tgl_aju,
                     'jumlah' => $kk->jumlah,
                     'harga' => $kk->harga,
                     'nilai' => $kk->nilai,
                     'valuta' => $kk->valuta,
                     'status' => '10',
                     'no_urut' => $no,
                     'no_ref' => $no_bpb,
                     'migrasi' => '14' 
                     );
      if ($_POST['tujuan']=='1') {
        $db->insert("pemasukan_detail",$data_pemasukan_detail); 
        echo $db->getErrorMessage();
      //  $db->insert("stock_incoming",$data_stock); 
       }elseif ($_POST['tujuan']!='1') {   
         $db->insert("pemasukan_baru_detail",$data_pemasukan_detail);
         // echo $db->getErrorMessage(); 
         //$db->insert("stock_incoming",$data_stock); 
       }
      
      
       $no++;
    } 
  }else{
 
  }
  

}
$res['error'] = $error;
$res['error_tgl'] = $error_tgl;
$res['sukses'] = $sukses;
$res['pesan_sukses'] = "<label class='label label-success'>No Dokumen yang sukses di Insert</label><br>".$sukses_upload."<br></p>"; 
$res['pesan_gagal'] = "<label class='label label-warning'>No Dokumen Sudah ada dan berhasil di Update</label><br>".$duplikat_no."<br></p>"; 
echo json_encode($res);

 
  break;

  case "get_po_header":

    $no_po = $_POST['no_po'];

    $q = $db->query("
        SELECT 
            seller_code,
            currency
        FROM purchase_order 
        WHERE purchase_order_no = '$no_po'
    ");

    $data = $q->fetch();

    echo json_encode($data);

  break;

  case "get_po_form":
    header('Content-Type: application/json; charset=utf-8');
    $noPo = isset($_POST['no_po']) ? trim($_POST['no_po']) : '';
    $header = $db->query("SELECT id,purchase_order_no,seller_code,currency FROM purchase_order WHERE purchase_order_no=? AND UPPER(COALESCE(status,'')) NOT IN ('CLOSE','CLOSED','CANCEL','CANCELED','CANCELLED','VOID') LIMIT 1", array('purchase_order_no'=>$noPo))->fetch();
    if (!$header) {
      echo json_encode(array('status'=>'error','message'=>'Purchase Order tidak ditemukan.'));
      break;
    }
    $items = array();
    foreach ($db->query("SELECT id AS id_po_detail,kode_barang,nama_barang,unit,qty,COALESCE(received_qty,0) AS received_qty,(COALESCE(qty,0)-COALESCE(received_qty,0)) AS open_qty,harga FROM purchase_order_detail WHERE (id_po=? OR po_no=?) AND COALESCE(qty,0)>COALESCE(received_qty,0) ORDER BY id", array('id_po'=>$header->id, 'po_no'=>$header->purchase_order_no)) as $item) {
      $items[] = array(
        'id_po_detail'=>$item->id_po_detail,
        'po_item_no'=>count($items)+1,
        'kode_barang'=>$item->kode_barang,
        'nama_barang'=>$item->nama_barang,
        'unit'=>$item->unit,
        'open_qty'=>$item->open_qty,
        'harga'=>$item->harga,
      );
    }
    echo json_encode(array('status'=>'good','header'=>$header,'items'=>$items));
    break;

  case "get_po":
    ?>

    <div class="col-lg-12">
      <table class="table table-bordered table-striped" style="font-size:12px">

        <thead>
          <tr>
            <th width="50" class="text-center">
              <a style="cursor:pointer" onclick="add_baris()">
                <i class="fa fa-plus"></i>
              </a>
            </th>
            <th width="300">Kode Barang</th>
            <th width="80">Unit</th>
            <th width="100"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th>
            <th width="120">Harga</th>
            <th width="120">Nilai</th>
            <th width="100">Berat</th>
            <th width="100">Lot Number</th>
            <th width="150">Lokasi</th>
          </tr>
        </thead>

        <tbody id="isi_tabel">

    <?php
    $no = 1;

    $sql = " 
    SELECT 
        d.kode_barang,
        d.nama_barang,
        d.unit,
        d.qty,
        d.harga,
        d.amount
    FROM purchase_order_detail d
    left JOIN purchase_order h ON h.purchase_order_no = d.po_no
    WHERE h.purchase_order_no = '".$_POST['no_po']."'
    ";

    $q = $db->query($sql);

    foreach ($q as $row):
    ?>

          <tr id="baris_<?= $no ?>">

            <!-- DELETE -->
            <td class="text-center">
              <a style="cursor:pointer" onclick="hapus_baris('<?= $no ?>')">
                <i class="fa fa-trash-o" style="font-size:18px;"></i>
              </a>
            </td>

            <!-- KODE BARANG -->
            <td>
              <input type="text"
                     class="form-control"
                     id="form_kode_<?= $no ?>"
                     value="<?= $row->kode_barang . ' ' . $row->nama_barang ?>"
                     name="kode[]"
                     readonly>

              <input type="hidden"
                     name="kode_input[]"
                     id="kode_input_<?= $no ?>"
                     value="<?= $row->kode_barang ?>">
            </td>

            <!-- UNIT -->
            <td>
              <input type="text"
                     class="form-control"
                     name="unit[]"
                     id="form_unit_<?= $no ?>"
                     value="<?= $row->unit ?>"
                     readonly>
            </td>

            <!-- QTY -->
            <td>
              <input type="number"
                     class="form-control qty"
                     name="jumlah[]"
                     id="form_qty_<?= $no ?>"
                     value="<?= $row->qty ?>">
            </td>

            <!-- HARGA -->
            <td>
              <input type="number"
                     class="form-control harga"
                     name="harga[]"
                     id="form_harga_<?= $no ?>"
                     value="<?= $row->harga ?>">
            </td>

            <!-- NILAI -->
            <td>
              <input type="text"
                     class="form-control total"
                     name="nilai[]"
                     id="form_nilai_<?= $no ?>"
                     value="<?= $row->harga * $row->qty ?>"
                     readonly>
            </td>

            <!-- BERAT -->
            <td>
              <input type="number"
                     class="form-control"
                     name="berat[]"
                     id="form_berat_<?= $no ?>">
            </td>
             <!-- BERAT -->
            <td>
              <input type="text"
                     class="form-control"
                     name="lot_no[]"
                     id="form_lot_<?= $no ?>">
            </td>

            <!-- LOKASI -->
            <td>
              <input type="text"
                     class="form-control"
                     name="lokasi[]"
                     id="form_lokasi_<?= $no ?>">
            </td>

          </tr>

    <?php
    $no++;
    endforeach;
    ?>

        </tbody>
      </table>
    </div>

    <input type="hidden" id="jml" value="<?= $no ?>">

    <?php
    break;
   

   case "upload_tpb":
   action_response('Import TPB legacy GR Blocked Stock dikunci. Gunakan form GR Blocked Stock terbaru agar stock layer, dokumen material, dan jurnal tetap konsisten.');
   break;
  // error_reporting(0);
   move_uploaded_file($_FILES['file_tpb']['tmp_name'], "../../upload/".$_FILES['file_tpb']['name']);
   $Reader = new SpreadsheetReader("../../upload/".$_FILES['file_tpb']['name']); 
  $Sheets = $Reader->Sheets();
  $data = array();
  $no=1;
  //echo "<pre>";
  $sukses=0;
  $duplikat=0;
  $val = array();
  $ada_data = 0;
  // $db->fetch_custom("delete from tb_remun where bulan='".$_POST['bulan_upload']."' and tahun='".$_POST['tahun_upload']."' and untuk='blu' and ket_cair='".$_POST['ket_upload']."' and ket='".$_POST['ket_kategori']."' ");
  echo "<pre>";
  $dat  = array();
  $data_detail  = array();
   foreach ($Sheets as $Index => $Name)
  {
   //  echo "$Index,";
    $Reader->ChangeSheet($Index);
    if ($Index==0) {
      $mulai = false;
    $i=0;
    $dat = array();
    foreach ($Reader as $r)  
    {
      if ($i>0) {
       // print_r($r);
        $no_aju    = $r[0];
        $no_daftar = $r[103];
        $tgl_dokpab = konversi_tgl($r[117]); 
        $data = array('no_aju'    => $no_aju,
                      'no_bpb'    => $no_aju,
                      'no_dokpab' => $no_daftar, 
                      'tgl_aju' => $tgl_dokpab, 
                      'tgl_dokpab' => $tgl_dokpab,
                      'tgl_bpb'    => $tgl_dokpab,
                      'jenis_dokpab' => konversi_dokpab($r[5]),
                      'kd_catdet'  => 'CAD-006',
                      'pemasok' => 'S0000060',
                      'valuta' => 'USD',
                      'status' => '1',
                      'catatan' => 'Disubkontrakkan');
        $dat[$no_aju] = $data;
        //print_r($data);
      }
      $i++;
      }
    }
    elseif ($Index==7) {
      $i=0;
      foreach ($Reader as $r)  
     {
       //print_r($r);
      if ($i>0) {
        if ($r[3]=='380') {
          $no_invoice = $r[4];
          $tgl_invoice = konversi_tgl($r[5]);
        //  $no_do = konversi_tgl($r[5]);
          $dat[$r[0]]['no_invoice'] = $no_invoice;
          $dat[$r[0]]['tgl_invoice'] = $tgl_invoice;
         // $dat[$r[0]]['no_do'] = $tgl_invoice;
        }elseif ($r[3]=='640') {
          $dat[$r[0]]['no_do'] = $r[4];
         // $dat[$r[0]]['tgl_invoice'] = $tgl_invoice;
        }
      }
      $i++;
     }
    }
     elseif ($Index==4) {
      $i=0;
      foreach ($Reader as $rr)  
      {
        if ($i>0) { 
         //  print_r($rr);
         
          if ($r[16]=='0' || $r[16]=='') {
            $nilai = 0;
          }else{
             $nilai =  $rr[11]/$rr[16];
          }
           cek_kode($rr[20],$rr[42],$rr[27]);
           $data_detail[$rr[0]]['kode'] = $rr[20];
           $data_detail[$rr[0]]['tgl_bpb'] = $dat[$rr[0]]['tgl_bpb'];
           $data_detail[$rr[0]]['no_bpb'] = $dat[$rr[0]]['no_bpb'];
           $data_detail[$rr[0]]['berat'] = $rr[32];
           $data_detail[$rr[0]]['tgl_aju'] = $dat[$rr[0]]['tgl_bpb'];
           $data_detail[$rr[0]]['tgl_masuk'] = $dat[$rr[0]]['tgl_bpb'];
           $data_detail[$rr[0]]['jenis_dokpab'] = $dat[$rr[0]]['jenis_dokpab'];
           $data_detail[$rr[0]]['no_dokpab'] = $dat[$r[0]]['no_dokpab'];
           $data_detail[$rr[0]]['jumlah'] = $rr[16];
           $data_detail[$rr[0]]['no_urut'] = $i;
           $data_detail[$rr[0]]['status'] = '1';
           $data_detail[$rr[0]]['nilai'] = $rr[11];
          // $data_detail[$rr[0]]['nilai'] = $rr[11];
           $data_detail[$rr[0]]['harga'] = $nilai;
           $data_detail[$rr[0]]['unit'] = $rr[27];

        }
        $i++;
      }
     }
   
  }
   $no=0;
   foreach ($dat as $key => $value) {
     $qc = $db->query("select id from pemasukan  where no_aju='$key' ");
     if ($qc->rowCount()==0) {
        $value['no_bpb'] = getNoBPB(date("Y",strtotime($value['tgl_dokpab'])));
        $data_detail[$key]['no_bpb'] = getNoBPB(date("Y",strtotime($value['tgl_dokpab'])));
        $db->insert("pemasukan",$value); 
     }else{
       foreach ($qc as $k) {
        $value['no_bpb'] = getNoBPB(date("Y",strtotime($value['tgl_dokpab'])));
        $data_detail[$key]['no_bpb'] = getNoBPB(date("Y",strtotime($value['tgl_dokpab'])));
        $db->update("pemasukan",$value,"id",$k->id);
       }       
     }
     $no++;
   } 
   $no=0;
   foreach ($data_detail as $key => $value) { 
   // print_r($value);
    $qc = $db->query("select id from pemasukan_detail  where no_bpb='".$data_detail[$key]['no_bpb']."' and kode='".$data_detail[$key]['kode']."' ");
     if ($qc->rowCount()==0) {
       $qcc = $db->query("select id from stock_incoming where kd_barang='".$data_detail[$key]['kode']."' and no_ref='".$data_detail[$key]['no_bpb']."' ");
        $data_stock  = array('kd_barang' => $data_detail[$key]['kode'] , 
                                'tgl_aju' => $data_detail[$key]['tgl_aju'] ,
                                'tgl_masuk' => $data_detail[$key]['tgl_masuk'] ,  
                                'jenis_dokpab' => $data_detail[$key]['jenis_dokpab'] ,
                                'no_dokpab' => $data_detail[$key]['no_dokpab'] ,
                                'jumlah' => $data_detail[$key]['jumlah'] ,  
                                'harga' => $data_detail[$key]['harga'] , 
                                'nilai' => $data_detail[$key]['nilai'] ,
                                'status' =>  '10',
                                'no_urut' => $data_detail[$key]['no_urut'] , 
                                'no_ref' => $data_detail[$key]['no_bpb'] , 
                                'status_import' => '1' );
         if ($qcc->rowCount()==0) {
           
           $db->insert("stock_incoming",$data_stock);
         }else{
           foreach ($qcc as $kc) {
             $db->update("stock_incoming",$data_stock,"id",$kc->id);
           }
         }
        $db->insert("pemasukan_detail",$value);  
     }else{
       foreach ($qc as $k) {
        $qcc = $db->query("select id from stock_incoming where kd_barang='".$data_detail[$key]['kode']."' and no_ref='".$data_detail[$key]['no_bpb']."' ");
        $data_stock  = array('kd_barang' => $data_detail[$key]['kode'] , 
                                'tgl_aju' => $data_detail[$key]['tgl_aju'] ,
                                'tgl_masuk' => $data_detail[$key]['tgl_masuk'] ,  
                                'jenis_dokpab' => $data_detail[$key]['jenis_dokpab'] ,
                                'no_dokpab' => $data_detail[$key]['no_dokpab'] ,
                                'jumlah' => $data_detail[$key]['jumlah'] ,  
                                'harga' => $data_detail[$key]['harga'] , 
                                'nilai' => $data_detail[$key]['nilai'] ,
                                'status' =>  '10',
                                'no_urut' => $data_detail[$key]['no_urut'] , 
                                'no_ref' => $data_detail[$key]['no_bpb'] , 
                                'status_import' => '1' ); 
         if ($qcc->rowCount()==0) {
            
           $db->insert("stock_incoming",$data_stock);
         }else{
           foreach ($qcc as $kc) {
             $db->update("stock_incoming",$data_stock,"id",$kc->id);
           }
         } 
        $db->update("pemasukan_detail",$value,"id",$k->id);
       }       
     }
     $no++;
   }  
 // print_r($data_detail);
  header("location:".base_url()."index.php/gr-blocked-stock");
  break;

   case "get_unit":
    $kd_barang = $_POST['kd_barang'];
    $q = $db->query("select kd_barang,nm_barang,satuan from barang where kd_barang='$kd_barang' ");
    $res = array();
   foreach ($q as $k) {
      echo "$k->satuan";
   }
 //  echo json_encode($res);
     break;

  case "cari_kode":
    $kode = trim($_POST['term']);
    $q = $db->query("select id, kd_barang,nm_barang from barang where kd_barang like '%$kode%' or nm_barang like '%$kode%' limit 5 ");
    $res = array();
   foreach ($q as $k) {
      $h['kd_barang'] = $k->kd_barang;
      $h['nm_barang'] = $k->nm_barang;
      $h['id_barang'] = $k->id;
      $res[] = $h;
   }
   echo json_encode($res);
    break;

  case "cari_kode_bom": 
    $kode = trim($_POST['term']);
    $q = $db->query("select barang.id, barang.kd_barang,barang.nm_barang,b.kodebj from barang right join 
bom b on b.kodebj=barang.kd_barang where barang.kd_barang is not null and  barang.kd_barang like '%$kode%' or barang.nm_barang like '%$kode%' limit 5 ");
    $res = array();
   foreach ($q as $k) {
      $h['kd_barang'] = $k->kd_barang;
      $h['nm_barang'] = $k->nm_barang;
      $h['id_barang'] = $k->id;
      $res[] = $h;
   }
   echo json_encode($res);
    break;

  case "in":
    
 // echo "<pre>";
	  $_POST['move_code'] = '103';
	  $_POST['stock_type'] = 'BLOCKED';
	  $postingDate = isset($_POST['posting_date']) ? $_POST['posting_date'] : (isset($_POST['tgl_bpb']) ? $_POST['tgl_bpb'] : '');
  $documentDate = isset($_POST['document_date']) ? $_POST['document_date'] : $postingDate;
  $requiredHeader = array(
    'document_date' => 'Document Date',
    'posting_date' => 'Posting Date',
    'stock_type' => 'Stock Type',
    'nopo' => 'Purchase Order',
    'pemasok' => 'Vendor',
    'plant_id' => 'Plant',
    'storage_location_id' => 'Storage Location',
    'no_do' => 'Delivery Note / Surat Jalan',
    'jenisbcmasuk_jenis_dokumen' => 'Jenis Dokumen BC',
    'no_aju' => 'Nomor Aju',
    'tgl_aju' => 'Tanggal Aju',
    'no_dokpab' => 'Nomor Pendaftaran',
    'tgl_dokpab' => 'Tanggal Pendaftaran'
  );
  foreach ($requiredHeader as $field => $label) {
    if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
      action_response($label.' wajib diisi.');
    }
  }
  if (empty($_POST['kode']) || !is_array($_POST['kode'])) {
    action_response('Item Purchase Order belum dipilih.');
  }
  foreach ($_POST['kode'] as $key => $value) {
    $lineNo = $key + 1;
    $qty = isset($_POST['jumlah'][$key]) ? floatval($_POST['jumlah'][$key]) : 0;
    $storageBinId = isset($_POST['storage_bin_id'][$key]) ? trim((string) $_POST['storage_bin_id'][$key]) : '';
    $customsItemNo = isset($_POST['customs_item_no'][$key]) ? trim((string) $_POST['customs_item_no'][$key]) : '';
    $customsQty = isset($_POST['customs_qty'][$key]) ? floatval($_POST['customs_qty'][$key]) : 0;
    $customsUom = isset($_POST['customs_uom'][$key]) ? trim((string) $_POST['customs_uom'][$key]) : '';
    $customsValue = isset($_POST['customs_value'][$key]) ? floatval($_POST['customs_value'][$key]) : 0;
    if ($qty <= 0) action_response('GR Qty item '.$lineNo.' wajib lebih dari nol.');
    if ($storageBinId === '') action_response('Storage Bin item '.$lineNo.' wajib diisi.');
    if ($customsItemNo === '') action_response('Item Pabean item '.$lineNo.' wajib diisi.');
    if ($customsQty <= 0) action_response('Qty Pabean item '.$lineNo.' wajib lebih dari nol.');
    if ($customsUom === '') action_response('Sat. Pabean item '.$lineNo.' wajib diisi.');
    if ($customsValue <= 0) action_response('Nilai Pabean item '.$lineNo.' wajib lebih dari nol.');
  }
  $thn = date("Y",strtotime($postingDate));
  $no_bpb = getNoBPB($thn);
 // echo "$no_bpb";
  $nomor = get_nomor("pemasukan","id");
  $data = array(
      "no_bpb" => $no_bpb,
      "nomor" => $nomor,
      "tgl_bpb" => $postingDate,
      "document_date" => $documentDate,
      "posting_date" => $postingDate,
      "nopo" => $_POST["nopo"],
      "plant_id" => $_POST["plant_id"],
      "storage_location_id" => $_POST["storage_location_id"],
      "stock_type" => 'BLOCKED',
      "pemasok" => $_POST["pemasok"],
      "no_invoice" => $_POST["no_invoice"],
      "tgl_invoice" => $_POST["tgl_invoice"],
      "no_do" => $_POST["no_do"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
      "kantor_pabean" => $_POST["kantor_pabean"],
      "negara_asal" => $_POST["negara_asal"],
      "customs_status" => $_POST["customs_status"],
      "catatan" => $_POST["catatan"],
      "jenis_dokpab" => $_POST["jenisbcmasuk_jenis_dokumen"],
      "kd_catdet" => $_POST["kd_catdet"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"], 
      "efaktur" => $_POST["efaktur"],
      "tgl_efaktur" => $_POST["tgl_efaktur"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
      "no_kontrak" => $_POST["no_kontrak"],
      "tgl_kontrak" => $_POST["tgl_kontrak"],
       'userid' => $_SESSION['username'],
  );
   foreach (array('tgl_efaktur','tgl_invoice','tgl_kontrak') as $optionalDate) {
     if (!isset($data[$optionalDate]) || $data[$optionalDate] === '') unset($data[$optionalDate]);
   }
   if (isset($_POST['no_bpb_lama'])) { 
      $data['ref_reversal'] = $_POST['no_bpb_lama'];
      $data['status'] = $_POST['POSTED']; 
   }
$db->query('START TRANSACTION');
if (!$db->insert("pemasukan",$data)) {
  gr_blocked_rollback_response($db->getErrorMessage());
}
//echo $db->getErrorMessage();
 // print_r($_SESSION); 
 // echo $_SESSION['username'];
// print_r($data)
 simpan_log("Input Dokumen ".$_POST["jenisbcmasuk_jenis_dokumen"]." dengan No Dokpab ".$_POST["no_dokpab"]." No Aju ".$_POST["no_aju"],$_SESSION['username']);
  
 if ($db->query("delete from pemasukan_detail where no_bpb=?", array($no_bpb)) === false) {
   gr_blocked_rollback_response($db->getErrorMessage());
 }
   $no=1;
   $accountingItems = array();
   foreach ($_POST['kode'] as $key => $value) {
       $barang = att_barang($_POST['kode_input'][$key]);
       $idPoDetail = isset($_POST['id_po_detail'][$key]) ? intval($_POST['id_po_detail'][$key]) : null;
       $storageBinId = !empty($_POST['storage_bin_id'][$key]) ? intval($_POST['storage_bin_id'][$key]) : null;
       $storageBin = $storageBinId ? $db->fetch_single_row('erp_storage_bin','id',$storageBinId) : null;
       $locationCode = $storageBin ? $storageBin->bin_code : '';
      $data_detail = array('nomor' => $nomor,
                    'id_po_detail' => $idPoDetail,
                    'no_bpb' => $no_bpb,
                    'tgl_bpb' => $postingDate,
                    'kode' => $_POST['kode_input'][$key],
                    'jumlah' => $_POST['jumlah'][$key],
                    'harga' => $_POST['harga'][$key],
                    'valuta' => $_POST['valuta'],
                    'nilai' => $_POST['nilai'][$key],
                    'unit' => $_POST['unit'][$key],
                    'berat' => isset($_POST['net_weight'][$key]) ? $_POST['net_weight'][$key] : 0,
                    'lot_no' => isset($_POST['lot_no'][$key]) ? $_POST['lot_no'][$key] : '',
                    'no_urut' => $no,
                    'customs_item_no' => isset($_POST['customs_item_no'][$key]) ? $_POST['customs_item_no'][$key] : $no,
                    'hs_code' => isset($_POST['hs_code'][$key]) ? $_POST['hs_code'][$key] : '',
                    'customs_qty' => isset($_POST['customs_qty'][$key]) ? $_POST['customs_qty'][$key] : $_POST['jumlah'][$key],
                    'customs_uom' => isset($_POST['customs_uom'][$key]) ? $_POST['customs_uom'][$key] : $_POST['unit'][$key],
                    'customs_value' => isset($_POST['customs_value'][$key]) ? $_POST['customs_value'][$key] : $_POST['nilai'][$key],
                    'net_weight' => isset($_POST['net_weight'][$key]) ? $_POST['net_weight'][$key] : 0,
                    'gross_weight' => isset($_POST['gross_weight'][$key]) ? $_POST['gross_weight'][$key] : 0,
                    'package_type' => isset($_POST['package_type'][$key]) ? $_POST['package_type'][$key] : '',
                    'package_qty' => isset($_POST['package_qty'][$key]) ? $_POST['package_qty'][$key] : 0,
                    'origin_country' => isset($_POST['origin_country'][$key]) ? $_POST['origin_country'][$key] : $_POST['negara_asal'],
                    'no_aju' => $_POST['no_aju'],
                    'tgl_aju' => $_POST['tgl_aju'],
                    'tgl_masuk' => $_POST['tgl_aju'],
                    'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
                    'no_dokpab' => $_POST['no_dokpab'],
                    'tgl_dokpab' => $_POST['tgl_dokpab'],
                    'lokasi' => 'GUDANG',
                    'storage_bin_id' => $storageBinId,
                    'userid' => $_SESSION['username']
                  );
    //  print_r($data_detail);
      // update_stock($_POST['jumlah'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']);
        $detailSaved = $db->insert("pemasukan_detail",$data_detail);
        if (!$detailSaved) {
          gr_blocked_rollback_response($db->getErrorMessage());
        }
        $id_detail = $db->last_insert_id();
        $accountingItems[] = array(
          'kode' => $_POST['kode_input'][$key],
          'amount' => $_POST['nilai'][$key],
          'valuta' => $_POST['valuta'],
          'kurs' => $_POST['kurs']
        );

        // 🔥 STOCK LAYER (INBOUND)
	if (!$db->insert("stock_layer", [
	    'kode'         => $_POST['kode_input'][$key],
	    'qty_masuk'    => $_POST['jumlah'][$key],
	    'qty_sisa'     => $_POST['jumlah'][$key],
	    'lokasi'       => 'GUDANG',
	    'stock_type'   => 'BLOCKED',
    'plant_id'     => $_POST['plant_id'],
    'storage_location_id' => $_POST['storage_location_id'],
    'storage_bin_id' => $storageBinId,
    'no_aju'       => $_POST['no_aju'],
    'no_dokpab'    => $_POST['no_dokpab'],
    'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],

    'ref_table'    => 'pemasukan_detail',
    'ref_id'       => $id_detail,

    'no_bpb'       => $no_bpb,
    'tgl_masuk'    => $postingDate,
    'created_at'   => date("Y-m-d H:i:s")
])) {
  gr_blocked_rollback_response($db->getErrorMessage());
}
       // 🔥 INSERT KE DETAIL_TRANSAKSI (SAP STYLE)
$data_transaksi = array(
    "no_ref"        => $no_bpb,
    "id_pemasukan"  => $nomor,
    "no_aju"        => $_POST['no_aju'],
    "no_dokpab"     => $_POST['no_dokpab'],
	    "move_code"     => '103', // GR Blocked Stock
    "no_urut"       => $no,
    "posisi"        => 'GUDANG',
    "qty"           => $_POST['jumlah'][$key], // positif
    "id_bagian"     => 1, // bisa disesuaikan (gudang)
    "price"         => $_POST['harga'][$key],
    "weight"        => isset($_POST['net_weight'][$key]) ? $_POST['net_weight'][$key] : 0,
    "kd_barang"     => $_POST['kode_input'][$key],
    "lokasi"        => 'GUDANG',
    "document_date" => $documentDate,
    "posting_date"  => $postingDate,
    "user"          => $_SESSION['username'],
    "is_produksi"   => '0', // karena ini dari PO, bukan produksi
    "direction"     => 'IN',
    "ref_type"      => 'PURCHASE_ORDER',
    "ref_id"        => $idPoDetail,
    "id_po_detail"  => $idPoDetail,
    "uom"           => $_POST['unit'][$key],
    "amount"        => $_POST['nilai'][$key],
    "no_bpb"        => $no_bpb,
    "plant_id"      => $_POST['plant_id'],
    "storage_location_id" => $_POST['storage_location_id'],
    "storage_bin_id" => $storageBinId,
    "stock_type"    => 'BLOCKED',
    "destination_storage_location_id" => $_POST['storage_location_id'],
    "destination_storage_bin_id" => $storageBinId,
    "destination_stock_type" => 'BLOCKED',
    "destination_material_code" => $_POST['kode_input'][$key],
    "created_by"    => $_SESSION['username'],
    "remark"        => 'GR Blocked Stock dari PO '.$_POST['nopo']
);

if (!$db->insert("detail_transaksi", $data_transaksi)) {
  gr_blocked_rollback_response($db->getErrorMessage());
}

      if ($idPoDetail) {
        $updatePo = $db->query("UPDATE purchase_order_detail SET received_qty=received_qty+? WHERE id=? AND received_qty+?<=qty", array('qty'=>$_POST['jumlah'][$key], 'id'=>$idPoDetail, 'qty_check'=>$_POST['jumlah'][$key]));
        if ($updatePo === false) {
          gr_blocked_rollback_response($db->getErrorMessage());
        }
        if ($updatePo->rowCount() < 1) {
          gr_blocked_rollback_response('GR Qty item '.$no.' melebihi outstanding PO atau detail PO tidak valid.');
        }
      }

      $no++;
   }

    $journalResult = accounting_post_auto_journal(
      'pembelian',
      $_POST['jenisbcmasuk_jenis_dokumen'],
      $accountingItems,
      array(
        'no_bukti' => $no_bpb,
        'tgl_jurnal' => $postingDate,
        'ket' => 'GR Blocked Stock '.$no_bpb.' PO '.$_POST['nopo'].' No Aju '.$_POST['no_aju'],
        'valuta' => $_POST['valuta'],
        'kurs' => $_POST['kurs']
      )
    );
    if ($journalResult !== true) gr_blocked_rollback_response($journalResult);


  //  $in = $db->insert("pemasukan",$data);
    
    
    $db->query('COMMIT');
    action_response('');
    break;
  
  case "show_detail": 
  $id = $_POST['id'];
  ?>
  <table class="table">
    <thead>
      <tr>
        <th><?=wh_h(wh_t('table_no', 'No'));?></th>
        <th>No BPB</th> 
        <th>Tanggal</th>
        <th>Kode Barang</th>
        <th style="text-align: center;">Unit</th>
        <th style="text-align: center;"><?=wh_h(wh_t('warehouse_qty', 'Qty'));?></th>
        <th style="text-align: center;">Harga</th>
        <th style="text-align: center;">Nilai</th>
        <th style="text-align: center;">Berat</th>
        <th style="text-align: center;">Lokasi</th>
      </tr>
    </thead>
    <tbody>
  <?php
  $qty=0;
  $harga =0;
  $nilai=0;
  $berat =0;
  $q = $db->query("select d.*,ba.nm_barang from pemasukan_detail d 
    join pemasukan b on b.no_bpb=d.no_bpb
    join barang ba on ba.kd_barang=d.kode where b.id='$id' ");
  $no=1;
  foreach ($q as $k) { 
    echo "<tr>
            <td>$no</td>
            <td>$k->no_bpb</td>
            <td>$k->tgl_bpb</td>
            <td>$k->kode , $k->nm_barang</td>
            <td>$k->unit</td>
            <td style='text-align: right'>".number_format($k->jumlah,4)."</td>
            <td style='text-align: right'>".number_format($k->harga,4)."</td>
            <td style='text-align: right'>".number_format($k->nilai,4)."</td>
            <td style='text-align: right'>".number_format($k->berat,4)."</td>
            <td>$k->lokasi</td>
          </tr>";
          $qty = $qty +$k->jumlah;
          $harga = $harga + $k->harga;
          $nilai = $nilai + $k->nilai;
          $berat = $berat + $k->berat;
          $no++;
  }
  ?>
    <tr>
      <td colspan="5" style="text-align: center">Jumlah</td>
      <td style="text-align: right"><?= number_format($qty,4) ?></td>
      <td style="text-align: right"><?= number_format($harga,4)  ?></td>
      <td style="text-align: right"><?= number_format($nilai,4)  ?></td>
      <td style="text-align: right"><?= number_format($berat,4)  ?></td> 
    </tr>
      </tbody>
  </table>
  <?php
    break;

  case "delete":

   $q = $db->query("select pemasukan.no_aju, b.id, pemasukan.no_dokpab,pemasukan_detail.kode,pemasukan.jenis_dokpab,pemasukan_detail.jumlah from   pemasukan_detail join pemasukan  on pemasukan.no_bpb=pemasukan_detail.no_bpb
   join barang b on b.kd_barang=pemasukan_detail.kode   where pemasukan.id='".$_GET["id"]."'");
   $jenis_dokpab = "";
   $no_dokpab = "";
   $no_aju = "";
   foreach ($q as $k) {
       update_stock($k->jumlah,'minus',$k->jenis_dokpab,'1',$k->id,$_SESSION['username']);
       $jenis_dokpab = $k->jenis_dokpab;
       $no_dokpab = $k->no_dokpab;
       $no_aju = $k->no_aju;
   }  
   simpan_log("Hapus Dokumen $jenis_dokpab dengan No Dokpab $no_dokpab No Aju $no_aju ",$_SESSION['username']);
    
    
    $db->query("delete pemasukan_detail from  pemasukan_detail join pemasukan 
                on pemasukan.no_bpb=pemasukan_detail.no_bpb
                 where pemasukan.id='".$_GET["id"]."'  "); 
     $db->delete("pemasukan","id",$_GET["id"]); 
     action_response($db->getErrorMessage()); 
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("pemasukan","no_bpb",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  
  case "hapus_detail":
    $kode   = $_POST['kode'];
    $no_bpb = $_POST['no_bpb'];
    $jumlah = $_POST['jumlah'];
    $jenis_dokpab = $_POST['jenis_dokpab'];  
    $barang = att_barang($_POST['kode']);
    $db->query("delete from pemasukan_detail where kode='$kode' and no_bpb='$no_bpb' ");
     update_stock($jumlah,'minus',$jenis_dokpab,'1',$barang->id,$_SESSION['username']);
    # code...
    break; 

  case "up":
    
   $data = array(
      "no_bpb" => $_POST["no_bpb"],
      "tgl_bpb" => $_POST["tgl_bpb"],
      "nopo" => $_POST["nopo"],
      "pemasok" => $_POST["pemasok"],
      "no_invoice" => $_POST["no_invoice"],
      "tgl_invoice" => $_POST["tgl_invoice"],
      "no_do" => $_POST["no_do"],
      "no_dokpab" => $_POST["no_dokpab"],
      "tgl_dokpab" => $_POST["tgl_dokpab"],
      "catatan" => $_POST["catatan"],
      "jenis_dokpab" => $_POST["jenisbcmasuk_jenis_dokumen"],
      "kd_catdet" => $_POST["kd_catdet"],
      "no_aju" => $_POST["no_aju"],
      "tgl_aju" => $_POST["tgl_aju"],
      "efaktur" => $_POST["efaktur"],
      "tgl_efaktur" => $_POST["tgl_efaktur"],
      "valuta" => $_POST["valuta"],
      "kurs" => $_POST["kurs"],
      'userid' => $_SESSION['username'],
   );
   if ($_POST["tgl_efaktur"]=='') {
     unset($data['tgl_efaktur']);
   }

   simpan_log("Update Dokumen ".$_POST["jenisbcmasuk_jenis_dokumen"]." dengan No Dokpab ".$_POST["no_dokpab"]." No Aju ".$_POST["no_aju"],$_SESSION['username']);

  // $nomor = get_nomor("pemasukan","id");
   $up = $db->update("pemasukan",$data,"no_bpb",$_POST["id"]);
   // $db->query("delete from pemasukan_detail where no_bpb='".$_POST["no_bpb"]."' ");
   // $db->query("delete from stock_incoming where no_ref='".$_POST["no_bpb"]."' "); 
   $no=1;
   foreach ($_POST['kode'] as $key => $value) {
      

      $barang = att_barang($_POST['kode_input'][$key]); 
      if (isset($_POST['jumlah_lama'][$key])) {
         update_stock($_POST['jumlah_lama'][$key],'minus',$_POST["jenis_dokpab_lama"],'1',$barang->id,$_SESSION['username']);
      }
      update_stock($_POST['jumlah'][$key],'plus',$_POST["jenisbcmasuk_jenis_dokumen"],'1',$barang->id,$_SESSION['username']); 
      if ($_POST['berat'][$key]=='') { 
        $_POST['berat'][$key] = NULL;
      }
      $data_detail = array(
                    'nomor' => $_POST['nomor'] , 
                    'no_bpb' => $_POST["no_bpb"], 
                    'tgl_bpb' => $_POST["tgl_bpb"],
                    'kode' => $_POST['kode_input'][$key],
                    'jumlah' => $_POST['jumlah'][$key],
                    'harga' => $_POST['harga'][$key],
                    'valuta' => $_POST['valuta'],
                    'nilai' => $_POST['nilai'][$key],
                    'unit' => $_POST['unit'][$key],
                    'berat' => $_POST['berat'][$key],
                    'no_urut' => $no,
                    'no_aju' => $_POST['no_aju'],
                    'tgl_aju' => $_POST['tgl_aju'],
                    'tgl_masuk' => $_POST['tgl_aju'],
                    'jenis_dokpab' => $_POST['jenisbcmasuk_jenis_dokumen'],
                    'no_dokpab' => $_POST['no_dokpab'],
                    'tgl_dokpab' => $_POST['tgl_dokpab'],
                    'lokasi' => $_POST['lokasi'][$key]
                   );
        $qd = $db->query("select id,kode from pemasukan_detail where 
        kode='".$_POST['kode_input'][$key]."' and no_bpb='".$_POST["no_bpb"]."' ");
        //jika data sudah tersedia
        if ($qd->rowCount()>0) {
            foreach ($qd as $kd) {
               $db->update("pemasukan_detail",$data_detail,"id",$kd->id); 
            }
        }else{
            $db->insert("pemasukan_detail",$data_detail); 
        }
       
       // $db->insert("stock_incoming",$data_stock_incoming);  
       // print_r($data_detail);
      $no++;
   }
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>
