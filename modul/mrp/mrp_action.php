<?php
session_start();
include "../../inc/config.php";
include "../../inc/excel/php-excel-reader/excel_reader2.php";
include "../../inc/excel/SpreadsheetReader.php"; 
session_check_json();
switch ($_GET["act"]) {

  case 'detail_bahan':
  $q = $db->query("select mm.* from mrp m join mrpmaterial mm on mm.no_order=m.no_order where m.id='".$_POST['id']."' ");
  ?>
  <table class="table">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Order</th>
                      <th>Kode</th>
                      <th>Color</th>
                      <th>Width</th>
                      <th>qty</th>
                      <th>supplier</th>
                      <th>tipe</th>
                      <th>Po</th>
                      <th>price</th>
                      <th>amount</th>
                      <th>currency</th>
                      <th>rate</th>
                      <th>price usd</th>
                      <th>amount usd</th>
                      <th>Ket</th>
                    </tr>
                  </thead>
                  <tbody> 
  <?php
  $i=1;
  foreach ($q as $data) {
   echo "<tr>
                            <td>$i</td>
                            <td>".$data->no_order."</td>
                            <td>".$data->kode."</td>
                            <td>".$data->color."</td>
                            <td>".$data->width."</td>
                            <td>".$data->qty_gross."</td>
                            <td>".$data->supplier."</td>
                            <td>".$data->tipe."</td>
                            <td>".$data->po."</t
                            <td>".$data->price."</td>
                            <td>".$data->amount."</td>
                            <td>".$data->currency."</td>
                            <td>".$data->rate."</td>
                            <td>".$data->price_usd."</td>
                            <td>".$data->amount_usd."</td>
                            <td>".$data->kk."</td>
                          </tr>";
                          $i++;
  }
  echo "</tbody></table>";
    break;
  
  case 'upload_file':
   move_uploaded_file($_FILES['file']['tmp_name'], "../../upload/".$_FILES['file']['name']);
   $Reader = new SpreadsheetReader("../../upload/".$_FILES['file']['name']); 
   $Sheets = $Reader->Sheets(); 
   $db->query("delete from mrpmaterial where no_order =? ",array($_POST['order']));
   $res = array();
   $res['tabel'] = "";
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
      if ($i==0) {
        foreach ($r as $key => $value) {
           $kol[$key] = $value;
        }
      }else{
        foreach ($r as $k => $v) {
          $data[$kol[$k]] = $v;          
        } 
        $db->insert("mrpmaterial",$data);
        $res['tabel'] .= "<tr>
                            <td>$i</td>
                            <td>".$data['no_order']."</td>
                            <td>".$data['kode']."</td>
                            <td>".$data['color']."</td>
                            <td>".$data['width']."</td>
                            <td>".$data['qty_gross']."</td>
                            <td>".$data['supplier']."</td>
                            <td>".$data['tipe']."</td>
                            <td>".$data['po']."</t
                            <td>".$data['price']."</td>
                            <td>".$data['amount']."</td>
                            <td>".$data['currency']."</td>
                            <td>".$data['rate']."</td>
                            <td>".$data['price_usd']."</td>
                            <td>".$data['amount_usd']."</td>
                            <td>".$data['ket']."</td>
                          </tr>";
        
       // echo $db->getErrorMessage();
        //print_r($data);
      }
      
      $i++;
    }
    //$isi = implode(",", $datax);
  }
    
  }
  echo json_encode($res);
    break;

  case "in":
    
  
  
  
  $data = array(
       "no_order" => $_POST["no_order"],
      "style" => $_POST["style"],
      "order_qty" => $_POST["order_qty"],
      "term" => $_POST["term"],
      "delivery" => $_POST["delivery"],
      "receipt" => $_POST["receipt"],
      "po" => $_POST["po"],
      "buyer" => $_POST["buyer"],
  );
  
  
  
   
    $in = $db->insert("mrp",$data);
    
    
    action_response($db->getErrorMessage());
    break;
  case "delete":
    
    
    
    $db->delete("mrp","Id",$_GET["id"]);
    action_response($db->getErrorMessage());
    break;
   case "del_massal":
    $data_ids = $_REQUEST["data_ids"];
    $data_id_array = explode(",", $data_ids);
    if(!empty($data_id_array)) {
        foreach($data_id_array as $id) {
          $db->delete("mrp","Id",$id);
         }
    }
    action_response($db->getErrorMessage());
    break;
  case "up":
    
   $data = array(
       "no_order" => $_POST["no_order"],
      "style" => $_POST["style"],
      "order_qty" => $_POST["order_qty"],
      "term" => $_POST["term"],
      "delivery" => $_POST["delivery"],
      "receipt" => $_POST["receipt"],
      "po" => $_POST["po"],
      "buyer" => $_POST["buyer"],
   );
   
   
   

    
    
    $up = $db->update("mrp",$data,"Id",$_POST["id"]);
    
    action_response($db->getErrorMessage());
    break;
  default:
    # code...
    break;
}

?>