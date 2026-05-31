<?php
session_start();
include "../../inc/config.php";
session_check_json();

switch ($_GET["act"]) {

    case "get_satuan_packing":

$q = $db->query("SELECT satuan_packing FROM satuan_packing ORDER BY satuan_packing ASC");

$data = [];

foreach($q as $k){
    $data[] = [
        "satuan_packing" => $k->satuan_packing
    ];
}

echo json_encode($data);
break;
     
    case "in":
        // Generate nomor surat jalan otomatis
        $no_surat_jalan = "SJ-" . date('Ym') . "-" . str_pad(get_nomor('surat_jalan','id'), 4, '0', STR_PAD_LEFT);
        
        // Ambil data sales order
        $so = $db->fetch_single_row("sales_order", "id_sales_order", $_POST['id_sales_order']);
        
        $data = array(
            "no_surat_jalan" => $no_surat_jalan,
            "id_sales_order" => $_POST['id_sales_order'],
            "no_sales_order" => $so->no_sales_order,
            "tgl_surat_jalan" => $_POST['tgl_surat_jalan'],
            "kode_penerima" => $so->kode_penerima,
            "no_invoice" => $so->no_sales_invoice,
            "no_po" => $so->no_po,
            "alamat_pengiriman" => $_POST['alamat_pengiriman'],
            "sopir" => $_POST['sopir'],
            "attn" => $_POST['attn'],
            "no_kendaraan" => $_POST['no_kendaraan'],
            "no_polisi" => $so->no_polisi,
            "keterangan" => $_POST['keterangan'],
            "status" => 'draft',
            "created_by" => $_SESSION['username'],
            "created_date" => date('Y-m-d H:i:s')
        );
        
        $in = $db->insert("surat_jalan", $data);
        $surat_jalan_id = $db->last_insert_id();
        
        // Simpan detail barang dari sales order
        $no = 1;
        $total_qty = 0;  
        foreach ($_POST['id_detail'] as $key => $value) {
            $qty_kirim = $_POST['qty_kirim'][$key];
            $data_detail = array(
                'surat_jalan_id' => $surat_jalan_id,
                'id_sales_order_detail' => $value,
                'kode_barang' => $_POST['kode_barang'][$key],
                'nama_barang' => $_POST['nama_barang'][$key],
                'packing' => $_POST['packing'][$key],
                'satuan_packing' => $_POST['satuan_packing'][$key],
                'qty_kirim' => $qty_kirim,
                'satuan' => $_POST['satuan'][$key],
                'keterangan' => $_POST['keterangan_barang'][$key],
                'row_no' => $no
            );
            $db->insert("surat_jalan_detail", $data_detail);
            $total_qty += $qty_kirim;
            $no++;
        }
        
        // Update total qty di header
        $db->update("surat_jalan", array('total_qty' => $total_qty), "id", $surat_jalan_id);
        
        action_response($db->getErrorMessage());
        break;
        
    case "up":
        $data = array(
            "tgl_surat_jalan" => $_POST['tgl_surat_jalan'],
            "alamat_pengiriman" => $_POST['alamat_pengiriman'],
            "sopir" => $_POST['sopir'],
            "no_kendaraan" => $_POST['no_kendaraan'],
            "keterangan" => $_POST['keterangan'],
            "updated_by" => $_SESSION['username'],
            "updated_date" => date('Y-m-d H:i:s')
        );
        
        // Hapus detail lama
        $db->query("DELETE FROM surat_jalan_detail WHERE surat_jalan_id=?", array($_POST['id']));
        
        // Simpan detail baru
        $no = 1;
        $total_qty = 0;
        foreach ($_POST['id_detail'] as $key => $value) {
            $qty_kirim = $_POST['qty_kirim'][$key];
            $data_detail = array(
                'surat_jalan_id' => $_POST['id'],
                'id_sales_order_detail' => $value,
                'kode_barang' => $_POST['kode_barang'][$key],
                'nama_barang' => $_POST['nama_barang'][$key],
                'qty_order' => $_POST['qty_order'][$key],
                'qty_kirim' => $qty_kirim,
                'satuan' => $_POST['satuan'][$key],
                'keterangan' => $_POST['keterangan_barang'][$key],
                'row_no' => $no
            );
            $db->insert("surat_jalan_detail", $data_detail);
            $total_qty += $qty_kirim;
            $no++;
        }
        
        // Update total qty
        $data['total_qty'] = $total_qty;
        
        $up = $db->update("surat_jalan", $data, "id", $_POST['id']);
        action_response($db->getErrorMessage());
        break;
        
    case "update_status":
        $surat_jalan_id = $_POST['id'];
        $status = $_POST['status'];
        
        $data_update = array(
            'status' => $status,
            'tgl_kirim' => ($status == 'dikirim') ? date('Y-m-d H:i:s') : null,
            'tgl_terima' => ($status == 'diterima') ? date('Y-m-d H:i:s') : null,
            'updated_by' => $_SESSION['username'],
            'updated_date' => date('Y-m-d H:i:s')
        );
        
        if ($status == 'dikirim') {
            // Update qty_terima di sales_order_detail
            $q_details = $db->query("SELECT * FROM surat_jalan_detail WHERE surat_jalan_id=?", array($surat_jalan_id));
            foreach ($q_details as $detail) {
                // Tambahkan qty_terima
                $db->query("UPDATE sales_order_detail SET qty_terima = qty_terima + ? WHERE id_detail=?", 
                          array($detail->qty_kirim, $detail->id_sales_order_detail));
            }
            
            // Update status sales order jika semua sudah terkirim
            $surat_jalan = $db->fetch_single_row("surat_jalan", "id", $surat_jalan_id);
            $check_so = $db->query("SELECT sod.*, 
                                   (sod.qty - sod.qty_terima) as sisa
                                   FROM sales_order_detail sod
                                   WHERE sod.id_sales_order = ?", 
                                   array($surat_jalan->id_sales_order));
            
            $all_complete = true;
            foreach ($check_so as $so_detail) {
                if ($so_detail->sisa > 0) {
                    $all_complete = false;
                    break;
                }
            }
            
            if ($all_complete) {
                $db->update("sales_order", array('status' => 'completed'), "id_sales_order", $surat_jalan->id_sales_order);
            } else {
                $db->update("sales_order", array('status' => 'partial'), "id_sales_order", $surat_jalan->id_sales_order);
            }
        }
        
        if ($status == 'diterima') {
            $data_update['nama_penerima'] = $_POST['nama_penerima'];
            if (!empty($_FILES['tanda_tangan']['name'])) {
                // Upload tanda tangan
                $target_dir = "../../upload/tanda_tangan/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_name = time() . '_' . basename($_FILES["tanda_tangan"]["name"]);
                $target_file = $target_dir . $file_name;
                move_uploaded_file($_FILES["tanda_tangan"]["tmp_name"], $target_file);
                $data_update['tanda_tangan_penerima'] = "upload/tanda_tangan/" . $file_name;
            }
        }
        
        $db->update("surat_jalan", $data_update, "id", $surat_jalan_id);
        
        $res['status'] = "success";
        echo json_encode($res);
        break;
        
    case "show_detail":
        $id = $_POST['id'];
        ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Qty Order</th>
                    <th>Qty Kirim</th>
                    <th>Sisa</th>
                    <th>Satuan</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = $db->query("SELECT sjd.*, (sjd.qty_order - sjd.qty_kirim) as sisa
                                 FROM surat_jalan_detail sjd 
                                 WHERE sjd.surat_jalan_id=? ORDER BY sjd.row_no", array($id));
                $no = 1;
                $total_order = 0;
                $total_kirim = 0;
                foreach ($q as $k) {
                    $total_order += $k->qty_order;
                    $total_kirim += $k->qty_kirim;
                    echo "<tr>
                            <td>$no</td>
                            <td>$k->kode_barang</td>
                            <td>$k->nama_barang</td>
                            <td style='text-align: right'>".number_format($k->qty_order,4)."</td>
                            <td style='text-align: right'>".number_format($k->qty_kirim,4)."</td>
                            <td style='text-align: right'>".number_format($k->sisa,4)."</td>
                            <td>$k->satuan</td>
                            <td>$k->keterangan</td>
                          </tr>";
                    $no++;
                }
                ?>
                <tr>
                    <td colspan="3" style="text-align: center"><strong>TOTAL</strong></td>
                    <td style="text-align: right"><strong><?=number_format($total_order,4)?></strong></td>
                    <td style="text-align: right"><strong><?=number_format($total_kirim,4)?></strong></td>
                    <td style="text-align: right"><strong><?=number_format($total_order - $total_kirim,4)?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        <?php
        break;
        
    case "get_sales_order":
        $term = $_POST['term'];
        $q = $db->query("SELECT so.*, pen.nama as nama_penerima 
                         FROM sales_order so 
                         LEFT JOIN penerima pen ON so.kode_penerima = pen.kode_penerima
                         WHERE (so.no_sales_order LIKE ? OR so.no_po LIKE ? OR pen.nama LIKE ?)                         
                         LIMIT 10", array("%$term%", "%$term%", "%$term%")
                     );
        
        $data = array();
        foreach ($q as $row) {
           // print_r($row);
            // Cek apakah masih ada barang yang bisa dikirim
            $q_detail = $db->query("SELECT qty as sisa_total 
                                   FROM sales_order_detail 
                                   WHERE id_sales_order = ?", array($row->id_sales_order));
            $sisa = $q_detail->fetchColumn();
            
            if ($sisa > 0) {
                $data[] = array(
                    'id' => $row->id_sales_order,
                    'value' => $row->no_sales_order,
                    'label' => $row->no_sales_order . " - " . $row->nama_penerima . 
                               " (PO: " . $row->no_po . " | qty: " . number_format($sisa, 2) . ")",
                    'no_sales_order' => $row->no_sales_order,
                    'no_sales_invoice' => $row->no_sales_invoice,
                    'no_po' => $row->no_po,
                    'kode_penerima' => $row->kode_penerima,
                    'nama_penerima' => $row->nama_penerima,
                    'shipping_address' => $row->shipping_address,
                    'no_polisi' => $row->no_polisi,
                    'so_date' => $row->so_date
                );
            }
        }
        
        echo json_encode($data);
        break;
        
    case "get_detail_sales_order":
        $id_sales_order = $_POST['id_sales_order'];
        
        $q = $db->query("SELECT sod.*, b.nm_barang, b.satuan,
                         (sod.qty - sod.qty_terima) as sisa_qty
                         FROM sales_order_detail sod 
                         JOIN barang b ON sod.kd_barang = b.kd_barang 
                         WHERE sod.id_sales_order = ? ", array($id_sales_order));
        
        $details = array();
        foreach ($q as $row) {
            $details[] = array(
                'id_detail' => $row->id_detail,
                'kode_barang' => $row->kd_barang,
                'nama_barang' => $row->nm_barang,
                'qty_order' => $row->qty,
                'qty_terima' => $row->qty_terima,
                'sisa_qty' => $row->sisa_qty,
                'satuan' => $row->satuan,
                'price' => $row->price
            );
        }
        
        echo json_encode($details);
        break;
        
    case "delete":
        $db->delete("surat_jalan_detail", "surat_jalan_id", $_GET["id"]);
        $db->delete("surat_jalan", "id", $_GET["id"]);
        action_response($db->getErrorMessage());
        break;
        
    case "del_massal":
        $data_ids = $_REQUEST["data_ids"];
        $data_id_array = explode(",", $data_ids);
        if(!empty($data_id_array)) {
            foreach($data_id_array as $id) {
                $db->delete("surat_jalan_detail", "surat_jalan_id", $id);
                $db->delete("surat_jalan", "id", $id);
            }
        }
        action_response($db->getErrorMessage());
        break;
        
    default:
        break;
}
?>