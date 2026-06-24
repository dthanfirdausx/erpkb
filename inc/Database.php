<?php
/**
 * PDO mysql database helper class
 *
 * @author wildantea <wildannudin@gmail.com>
 * @copyright june 2013
 */
class Database {

    protected $pdo;
    private $datasec = array();
    private $ctrl_dir = array();
    private $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    private $old_offset = 0;
    private $error_message = '';

    private function recordAuditOperation($operation, $table, $data = array(), $condition = '')
    {
        if ($table === 'log_aktifitas') {
            return;
        }

        $sensitiveKeys = array(
            'password', 'passwd', 'pass', 'pwd', 'token', 'secret',
            'authorization', 'cookie', 'session', 'file', 'foto', 'gambar'
        );
        $safeData = array();

        foreach ((array) $data as $key => $value) {
            if (count($safeData) >= 8 || !is_scalar($value)) {
                continue;
            }

            $normalizedKey = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($normalizedKey, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $safeData[$key] = '[disensor]';
                continue;
            }

            $safeValue = preg_replace('/\s+/', ' ', trim((string) $value));
            $safeData[$key] = substr($safeValue, 0, 100);
        }

        if (!isset($GLOBALS['_module_audit_operations'])) {
            $GLOBALS['_module_audit_operations'] = array();
        }

        $GLOBALS['_module_audit_operations'][] = array(
            'operation' => strtolower($operation),
            'table' => preg_replace('/[^a-zA-Z0-9_\.]/', '', (string) $table),
            'data' => $safeData,
            'condition' => substr(preg_replace('/\s+/', ' ', trim((string) $condition)), 0, 150)
        );
    }

    private function normalizeDetailTransaksiData($table, $data)
    {
        if (strtolower((string) $table) !== 'detail_transaksi' || !is_array($data)) {
            return $data;
        }

        if (empty($data['destination_material_code']) && !empty($data['kd_barang'])) {
            $data['destination_material_code'] = $data['kd_barang'];
        }

        $direction = isset($data['direction']) ? strtoupper((string) $data['direction']) : '';
        if ($direction === 'IN') {
            if (empty($data['destination_storage_location_id']) && !empty($data['storage_location_id'])) {
                $data['destination_storage_location_id'] = $data['storage_location_id'];
            }
            if (empty($data['destination_storage_bin_id']) && !empty($data['storage_bin_id'])) {
                $data['destination_storage_bin_id'] = $data['storage_bin_id'];
            }
            if (empty($data['destination_stock_type']) && !empty($data['stock_type'])) {
                $data['destination_stock_type'] = $data['stock_type'];
            }
        }

        return $data;
    }

    public function __construct($hostname,$port_number,$username_db,$password_db,$db_name)
    {
        try {
        $dsn = strpos($hostname, '/') === 0
            ? "mysql:unix_socket=".$hostname.";dbname=".$db_name
            : "mysql:host=".$hostname.";dbname=".$db_name.";port=".$port_number;
        $this->pdo = new PDO($dsn, $username_db, $password_db );
        $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        }
        catch( PDOException $e ) {
            echo "error ". $e->getMessage();
        }
    }

    /**
    * custom query , joining multiple table, aritmathic etc
    * @param  string $sql  custom query
    * @param  array $data associative array
    * @return array  recordset
    */
    public function query( $sql,$data=null) {
        if ($data!==null) {
        $dat=array_values($data);
        }
        $sel = $this->pdo->prepare( $sql );
        
        try{ 
            if ($data!==null) {
                $sel->execute($dat);
            } else {
               $sel->execute();
            }
            if (preg_match('/^\s*(insert\s+into|update|delete\s+from)\s+[`"]?([a-zA-Z0-9_\.]+)/i', $sql, $auditMatch)) {
                $operation = stripos($auditMatch[1], 'insert') === 0
                    ? 'insert'
                    : (stripos($auditMatch[1], 'delete') === 0 ? 'delete' : 'update');
                $this->recordAuditOperation($operation, $auditMatch[2], (array) $data);
            }
            $sel->setFetchMode( PDO::FETCH_OBJ );
            return $sel;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        }

    }

   /**
     * updateMulti mapper array
     * @param  array $update_value      array recordset 
     * @param  array $primary_key_value primary key value recordset
     * @return array                    array recordset
     */
    public function mapper($update_value,$primary_key_value) {
      $index=0;
      foreach ($update_value as $value) {
      $primary_value = $primary_key_value[$index];
      $append[] = array_map(function ($str) use($primary_value) { return "WHEN '".$primary_value."' THEN '$str'"; }, $value);
      $index++;
      }
      return $append;
    }

    /**
     * [updateMulti update bulk sql]
     * @param  [string] $table_name    table name
     * @param  array $update_value        array recordset with key value column_name and value of record
     * @param  string $primary_key_name   primary key column_name
     * @param  array $primary_key_value primary key value (single array )
     * @return boolean update query
     */

    public function updateMulti($table_name,$update_value,$primary_key_name,$primary_key_value) {

        $data_mapper_update = $this->mapper($update_value,$primary_key_value);
        $data_mapper = array_keys($data_mapper_update[0]);

          $collection = [];

        foreach ($data_mapper as $key) {
          $collection[] = "$key = (CASE $primary_key_name ".implode(' ', array_unique(
              // `array_column` will give you all values under `$key`
              array_column($data_mapper_update, $key)
          ))." END)";
        }
        $primary_key_quote = sprintf("'%s'", implode("','", $primary_key_value ) );
        $query = "UPDATE $table_name SET ".implode(",", $collection)." WHERE $primary_key_name IN ($primary_key_quote)";
        try{ 
            $this->query($query);
            return true;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        } 
    }

     /**
     * insert multiple row at once
     * @param  [type] $table      table name
     * @param  [type] $array_data multi array 
     * @return [type]             boolen 
     */
    public function insertMulti($table_name,$values) {
        $column_name = array_keys($values[0]);
        $column_name = implode(',',$column_name);
    
        $value_data = array();
        foreach ($values as $data => $val) {
        
        $value_data[] = '("'.implode('","',array_values($val)).'")';
        }
        $string_value = implode(",",$value_data);
    
        $sql = "INSERT INTO $table_name ($column_name) VALUES ".$string_value;
        $this->query($sql);
    }
    
        /**
     * [getErrorMessage return string throw exception
     * @return string return string error
     */
    function getErrorMessage() {
        return $this->error_message;
    }

    /**
     * [setErrorMessage set error message]
     * @param [type] $error [description]
     */
    function setErrorMessage($error) {
        $this->error_message = $error;
    }

    /**
    * fetch only one row
    * @param  string $table table name
    * @param  string $col condition column
    * @param  string $val value column
    * @return array recordset
    */
    public function fetch_single_row($table,$col,$val)
    {
        $nilai=array($val);
        $sel = $this->pdo->prepare("SELECT * FROM $table WHERE $col=?");
        $sel->execute($nilai);
        $sel->setFetchMode( PDO::FETCH_OBJ );
        $obj = $sel->fetch();
        return $obj;
    }

      public function fetch($sql,$data=null)
    {
        if ($data!==null) {
        $dat=array_values($data);
        }
        $sel = $this->pdo->prepare( $sql );
        try{ 
            if ($data!==null) {
                $sel->execute($dat);
            } else {
               $sel->execute();
            }
            $sel->setFetchMode( PDO::FETCH_OBJ );
            $obj = $sel->fetch();
            return $obj;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        }
    }


    public function fetch_custom_single($sql,$data=null)
    {
        if ($data!==null) {
        $dat=array_values($data);
        }
        $sel = $this->pdo->prepare( $sql );
        try{ 
            if ($data!==null) {
                $sel->execute($dat);
            } else {
               $sel->execute();
            }
            $sel->setFetchMode( PDO::FETCH_OBJ );
            $obj = $sel->fetch();
            return $obj;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        }
    }

    /**
    * fetch all data
    * @param  string $table table name
    * @return array recordset
    */
    public function fetch_all($table)
    {
        $sel = $this->pdo->prepare("SELECT * FROM $table");
        $sel->execute();
        $sel->setFetchMode( PDO::FETCH_OBJ );
        return $sel;
    }
    /**
    * fetch multiple row
    * @param  string $table table name
    * @param  array $dat specific column selection
    * @return array recordset
    */
    public function fetch_col($table,$dat)
    {
        if( $dat !== null )
        $cols= array_values( $dat );
        $col=implode(', ', $cols);
        $sel = $this->pdo->prepare("SELECT $col from $table");
        $sel->execute();
        $sel->setFetchMode( PDO::FETCH_OBJ );
        return $sel;
    }

    /**
    * fetch row with condition
    * @param  string $table table name
    * @param  array $col which columns name would be select
    * @param  array $where what column will be the condition
    * @return array recordset
    */
    public function fetch_multi_row($table,$col,$where)
    {

        $data = array_values( $where );
        //grab keys
        $cols=array_keys($where);
        $colum=implode(', ', $col);
        foreach ($cols as $key) {
          $keys=$key."=?";
          $mark[]=$keys;
        }

        $jum=count($where);
        if ($jum>1) {
            $im=implode('? and  ', $mark);
             $sel = $this->pdo->prepare("SELECT $colum from $table WHERE $im");
        } else {
          $im=implode('', $mark);
             $sel = $this->pdo->prepare("SELECT $colum from $table WHERE $im");
        }
        $sel->execute( $data );
        $sel->setFetchMode( PDO::FETCH_OBJ );
        return  $sel;
    }

    /**
    * check if there is exist data
    * @param  string $table table name
    * @param  array $dat array list of data to find
    * @return true or false
    */
    public function check_exist($table,$dat) {

        $data = array_values( $dat );
       //grab keys
        $cols=array_keys($dat);
        $col=implode(', ', $cols);

        foreach ($cols as $key) {
          $keys=$key."=?";
          $mark[]=$keys;
        }

        $jum=count($dat);
        if ($jum>1) {
            $im=implode(' and  ', $mark);
             $sel = $this->pdo->prepare("SELECT $col from $table WHERE $im");
        } else {
          $im=implode('', $mark);

             $sel = $this->pdo->prepare("SELECT $col from $table WHERE $im");
        }
        $sel->execute( $data );
        $sel->setFetchMode( PDO::FETCH_OBJ );
        $jum=$sel->rowCount();
        if ($jum>0) {
            return true;
        } else {
            return false;
        }
    }

       public function check_exist_data($table,$dat) {
        $data = array_values( $dat );
             //grab keys
              $cols=array_keys($dat);
              $col=implode(', ', $cols);

              foreach ($cols as $key) {
                $keys=$key."=?";
                $mark[]=$keys;
              }

              $jum=count($dat);
              if ($jum>1) {
                  $im=implode(' and  ', $mark);
                   $sel = $this->pdo->prepare("SELECT * from $table WHERE $im");
              } else {
                $im=implode('', $mark);
                   $sel = $this->pdo->prepare("SELECT * from $table WHERE $im");
              }
              $sel->execute( $data );
              $sel->setFetchMode( PDO::FETCH_OBJ );
              $obj = $sel->fetch();
              return $obj;
    }
    /**
    * search data
    * @param  string $table table name
    * @param  array $col   column name
    * @param  array $where where condition
    * @return array recordset
    */
    public function search($table,$col,$where) {
        $data = array_values( $where );
        foreach ($data as $key) {
           $val = '%'.$key.'%';
           $value[]=$val;
        }
       //grab keys
        $cols=array_keys($where);
        $colum=implode(', ', $col);

        foreach ($cols as $key) {
          $keys=$key." LIKE ?";
          $mark[]=$keys;
        }
        $jum=count($where);
        if ($jum>1) {
            $im=implode(' OR  ', $mark);
             $sel = $this->pdo->prepare("SELECT $colum from $table WHERE $im");
        } else {
          $im=implode('', $mark);
             $sel = $this->pdo->prepare("SELECT $colum from $table WHERE $im");
        }

        $sel->execute($value);
        $sel->setFetchMode( PDO::FETCH_OBJ );
        return  $sel;
    }
    /**
    * insert data to table
    * @param  string $table table name
    * @param  array $dat   associative array 'column_name'=>'val'
    */
    public function insert($table,$dat) {

        $dat = $this->normalizeDetailTransaksiData($table, $dat);
        if( $dat !== null )
        $dat = array_filter($dat);
        $data = array_values( $dat );
        //grab keys
        $cols=array_keys($dat);
        $col=implode(', ', $cols);

        //grab values and change it value
        $mark=array();
        foreach ($data as $key) {
          $keys='?';
          $mark[]=$keys;
        }
        $im=implode(', ', $mark);
       // echo "INSERT INTO $table ($col) values ($im) ";
        $ins = $this->pdo->prepare("INSERT INTO $table ($col) values ($im)");
        try{
            $ins->execute( $data );
            $this->recordAuditOperation('insert', $table, $dat);
            return true;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        } 
    }

        public function last_insert_id()
    {
        return $this->pdo->lastInsertId();
    }

    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }


    /**
    * update record
    * @param  string $table table name
    * @param  array $dat   associative array 'col'=>'val'
    * @param  string $id    primary key column name
    * @param  int $val   key value
    */
    public function update($table,$dat,$id,$val,$filter="no") {
        $dat = $this->normalizeDetailTransaksiData($table, $dat);
        if( $dat !== null )
        if ($filter=="yes") {
          $dat = array_filter($dat);
        }
        $data = array_values( $dat );
        array_push($data,$val);
        //grab keys
        $cols=array_keys($dat);
        $mark=array();
        foreach ($cols as $col) {
        $mark[]=$col."=?";
        }
        $im=implode(', ', $mark);
        $ins = $this->pdo->prepare("UPDATE $table SET $im where $id=?");
        try{
            $ins->execute( $data );
            $this->recordAuditOperation('update', $table, $dat, $id.'='.$val);
            return true;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        } 

    }

    /**
    * delete record
    * @param  string $table table name
    * @param  string $where column name for condition (commonly primay key column name)
    * @param   int $id   key value
    */
    public function delete( $table, $where,$id ) {
        $data = array( $id );
        $sel = $this->pdo->prepare("Delete from $table where $where=?" );
        try{
            $sel->execute( $data );
            $this->recordAuditOperation('delete', $table, array(), $where.'='.$id);
            return true;
        } 
        catch(PDOException $exception){ 
            $this->setErrorMessage($exception->getMessage());
            return false;
        } 
    }




    function get_settings($name){
    if(is_file("settings.cfg")) $file = "settings.cfg";
    else if (is_file("inc/settings.cfg")) $file = "inc/settings.cfg";
    else return "";

    $con = file_get_contents($file);
    $patt = '/'.$name.'.*=(.*)/i';
    preg_match($patt, $con, $match);
    return $match[1];
    }

    function set_settings($name, $value){
        if(is_file("settings.cfg")) $file = "settings.cfg";
        else if (is_file("inc/settings.cfg")) $file = "inc/settings.cfg";
        else return "";

        $con = file_get_contents($file);

        $lines = explode("\n", $con);
        $settings = "";
        foreach($lines as $line){
            $line = trim($line);
            if($line != ""){
                $setting = explode("=", $line, 2);
                if(count($setting)==2){
                    $var = trim($setting[0]);
                    $val = trim($setting[1]);
                    if(stripos($var,$name)===0){
                        $settings .= trim($name)."=".trim($value)."\n";
                    }
                    else $settings .= $line."\n";
                }
            }
        }
        file_put_contents($file, $settings);
    }

    function check_tb_exist()
    {
       return $this->query("show tables like 'sys_modul'")->rowCount();
    }


    //write file
     function buat_file($file,$isi)
     {
        $fp=fopen($file,'w');
        if(!$fp)return 0;
        fwrite($fp, $isi);
        fclose($fp);return 1;

     }
     //hapus directory
    function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir) || is_link($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!$this->deleteDirectory($dir . "/" . $item)) {
        chmod($dir . "/" . $item, 0777);
        if (!$this->deleteDirectory($dir . "/" . $item)) return false;
        };}return rmdir($dir);
    }



    //selected active menu
    public function terpilih($nav,$group_id)
    {
      $pilih="";
      //  $mod = $this->fetch_single_row('sys_menu','nav_act',$nav);
        if ($nav!='') {
             $menu = $this->query("select * from sys_menu where url=?",array('url'=>$nav));

        foreach ($menu as $men) {

              $id_group[] = $group_id;
           if ($men->parent!=0) {
               $data = $this->fetch_single_row('sys_menu','id',$men->parent);


            if ($group_id==$men->parent || $data->parent==$group_id ) {



             $pilih='active';
            }  else {
                 $pilih="";
            }

           } else {
                       $data = $this->fetch_single_row('sys_menu','id',$men->parent);


            if ($group_id==$men->parent) {


             $pilih='active';
            }  else {
                 $pilih="";
            }
           }



       }
         }



        return $pilih;
    }
	     // Menu builder function, parentId 0 is the root
	    function menuLabel($item)
	    {
	        $language = isset($_SESSION['language']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_SESSION['language'])) : 'en';
	        $allowed = array('id', 'en', 'ko', 'zh', 'ja');
	        if (!in_array($language, $allowed)) {
	            $language = 'en';
	        }
	        $languageField = 'page_name_'.$language;
	        if (isset($item[$languageField]) && trim((string)$item[$languageField]) !== '') {
	            return $item[$languageField];
	        }
	        if (isset($item['page_name_en']) && trim((string)$item['page_name_en']) !== '') {
	            return $item['page_name_en'];
	        }
	        return isset($item['page_name']) ? $item['page_name'] : '';
	    }

	    function buildMenu($url,$parent, $menu)
	    {
       $html = "";
       if (isset($menu['parents'][$parent]))
       {
           foreach ($menu['parents'][$parent] as $itemId)
           {

              if(!isset($menu['parents'][$itemId]))
              {
                 $html .= "<li ";
                 $html .=($url==$menu['items'][$itemId]['url'])?'class="active"':'';
                 $html.=">
                   <a href='".base_index().$menu['items'][$itemId]['url']."'>";
                 if($menu['items'][$itemId]['icon']!='')
                  {
                    $html.="<i class='fa ".$menu['items'][$itemId]['icon']."'></i>";
                  } else {
                    $html.="<i class='fa fa-square'></i>";
                  }
	                  $html.=htmlspecialchars(ucwords($this->menuLabel($menu['items'][$itemId])), ENT_QUOTES, 'UTF-8')."</a></li>";
	              }

              if(isset($menu['parents'][$itemId]))
              {



$html .= "<li class='treeview ".$this->terpilih($url,$menu['items'][$itemId]['id']);

     $html.="'><a href='#'>";
                 if($menu['items'][$itemId]['icon']!='')
                  {
                    $html.="<i class='fa ".$menu['items'][$itemId]['icon']."'></i>";
                  } else {
                    $html.="<i class='fa fa-circle-o'></i>";
                  }
	                  $html.="<span>".htmlspecialchars(ucwords($this->menuLabel($menu['items'][$itemId])), ENT_QUOTES, 'UTF-8')."</span>
	                                    <i class='fa fa-angle-left pull-right'></i>
	                                </a>";
$html .="<ul class='treeview-menu'>";
$html .=$this->buildMenu($url,$itemId, $menu);
$html .= "</ul></li>";
              }
           }

       }
       return $html;
    }

    //obj to array
    function convert_obj_to_array($obj)
    {
        if (is_object($obj)) $obj = (array)$obj;
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = $this->convert_obj_to_array($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }


    //search function
    public function getRawWhereFilterForColumns($filter, $search_columns)
    {
        $filter=addslashes($filter);
      $search_terms = explode(' ', $filter);
      $search_condition = "";

      for ($i = 0; $i < count($search_terms); $i++) {
        $term = $search_terms[$i];

        for ($j = 0; $j < count($search_columns); $j++) {
          if ($j == 0) $search_condition .= "(";
          $search_field_name = $search_columns[$j];
          $search_condition .= "$search_field_name LIKE '%" . $term . "%'";
          if ($j + 1 < count($search_columns)) $search_condition .= " OR ";
          if ($j + 1 == count($search_columns)) $search_condition .= ")";
        }
        if ($i + 1 < count($search_terms)) $search_condition .= " AND ";
      }
      return $search_condition;
    }


    /**
     * upload image if image width more than 1200 then upload and compress to 1200, otherwise just upload it
     * @param  [type] $ext               [description]
     * @param  [type] $uploadedfile      [description]
     * @param  [type] $path              [description]
     * @param  [type] $actual_image_name [description]
     * @return [type]                    [description]
     */
    public function upload_image_custom($ext,$uploadedfile,$path,$actual_image_name) {
      $image_size = getimagesize($uploadedfile);
      if ($image_size[0]>=1200) {
          $this->compressImage($ext,$uploadedfile,$path,$actual_image_name,1200);
      } else {
          $file = $uploadedfile;
          $path = $path.$actual_image_name;
          copy($uploadedfile, $path);
      }
      
    }

    public function upload_file($uploadedfile,$path,$actual_image_name) {
         $file = $uploadedfile;
          $path = $path.$actual_image_name;
          copy($uploadedfile, $path);
    }


    function compressImage($ext,$uploadedfile,$path,$actual_image_name,$newwidth,$tinggi=null)
        {


        if($ext=="image/jpeg" || $ext=="image/jpg" )
        {

        $src = imagecreatefromjpeg($uploadedfile);
        }
        else if($ext=="image/png")
        {
        $src = @imagecreatefrompng($uploadedfile);
        }
        else if($ext=="image/gif")
        {
        $src = imagecreatefromgif($uploadedfile);
        }
        else
        {
         
        $src = imagecreatefrombmp($uploadedfile);
        }

        list($width,$height)=getimagesize($uploadedfile);
        if ($tinggi!=null) {
            $newheight=$tinggi;
        } else {
            $newheight=($height/$width)*$newwidth;
        }

        $tmp=imagecreatetruecolor($newwidth,$newheight);
        imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);
        $filename = $path.$actual_image_name; //PixelSize_TimeStamp.jpg
        imagejpeg($tmp,$filename,100);
        imagedestroy($tmp);
        return $filename;
        }

         public function __destruct() {
    $this->pdo = null;
    }

     function get_dir($dir) {
      $modul_dir = explode(DIRECTORY_SEPARATOR, $dir);
     array_pop($modul_dir);
     array_pop($modul_dir);

     $modul_dir = implode(DIRECTORY_SEPARATOR, $modul_dir);
     return $modul_dir.DIRECTORY_SEPARATOR."modul".DIRECTORY_SEPARATOR;
  }

  /**
   * get uniqure name from filename
   * @param  string $file_name filename
   * @return string            new unique filename
   */
  public function unique_name($file_name) {
        $filename = $file_name;
        $filename = preg_replace("#[^a-z.0-9]#i", "", $filename); 
        $ex = explode(".", $filename); // split filename
        $fileExt = end($ex); // ekstensi akhir
        $filename = time().rand().".".$fileExt;//rename nama file';
        return $filename;
  }

  function add_dir($name) {
    $name = str_replace("\\", "/", $name);
    $fr = "\x50\x4b\x03\x04";
    $fr .= "\x0a\x00";
    $fr .= "\x00\x00";
    $fr .= "\x00\x00";
    $fr .= "\x00\x00\x00\x00";
    $fr .= pack("V",0);
    $fr .= pack("V",0);
    $fr .= pack("V",0);
    $fr .= pack("v", strlen($name) );
    $fr .= pack("v", 0 );
    $fr .= $name;
    $fr .= pack("V",$crc);
    $fr .= pack("V",$c_len);
    $fr .= pack("V",$unc_len);
    $this -> datasec[] = $fr;
    $new_offset = strlen(implode("", $this->datasec));
    $cdrec = "\x50\x4b\x01\x02";
    $cdrec .="\x00\x00";
    $cdrec .="\x0a\x00";
    $cdrec .="\x00\x00";
    $cdrec .="\x00\x00";
    $cdrec .="\x00\x00\x00\x00";
    $cdrec .= pack("V",0);
    $cdrec .= pack("V",0);
    $cdrec .= pack("V",0);
    $cdrec .= pack("v", strlen($name) );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 );
    $ext = "\x00\x00\x10\x00";
    $ext = "\xff\xff\xff\xff";
    $cdrec .= pack("V", 16 );
    $cdrec .= pack("V", $this -> old_offset );
    $this -> old_offset = $new_offset;
    $cdrec .= $name;
    $this -> ctrl_dir[] = $cdrec;
    }
    function add_file($data, $name)
    {
    $name = str_replace("\\", "/", $name);
    $fr = "\x50\x4b\x03\x04";
    $fr .= "\x14\x00";
    $fr .= "\x00\x00";
    $fr .= "\x08\x00";
    $fr .= "\x00\x00\x00\x00";
    $unc_len = strlen($data);
    $crc = crc32($data);
    $zdata = gzcompress($data);
    $zdata = substr( substr($zdata, 0, strlen($zdata) - 4), 2);
    $c_len = strlen($zdata);
    $fr .= pack("V",$crc);
    $fr .= pack("V",$c_len);
    $fr .= pack("V",$unc_len);
    $fr .= pack("v", strlen($name) );
    $fr .= pack("v", 0 );
    $fr .= $name;
    $fr .= $zdata;
    $fr .= pack("V",$crc);
    $fr .= pack("V",$c_len);
    $fr .= pack("V",$unc_len);
    $this -> datasec[] = $fr;
    $new_offset = strlen(implode("", $this->datasec));
    $cdrec = "\x50\x4b\x01\x02";
    $cdrec .="\x00\x00";
    $cdrec .="\x14\x00";
    $cdrec .="\x00\x00";
    $cdrec .="\x08\x00";
    $cdrec .="\x00\x00\x00\x00";
    $cdrec .= pack("V",$crc);
    $cdrec .= pack("V",$c_len);
    $cdrec .= pack("V",$unc_len);
    $cdrec .= pack("v", strlen($name) );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("v", 0 );
    $cdrec .= pack("V", 32 );
    $cdrec .= pack("V", $this -> old_offset );
    $this -> old_offset = $new_offset;
    $cdrec .= $name;
    $this -> ctrl_dir[] = $cdrec;
    }
    function file() {
    $data = implode("", $this -> datasec);
    $ctrldir = implode("", $this -> ctrl_dir);
    return
    $data.
    $ctrldir.
    $this -> eof_ctrl_dir.
    pack("v", sizeof($this -> ctrl_dir)).
    pack("v", sizeof($this -> ctrl_dir)).
    pack("V", strlen($ctrldir)).
    pack("V", strlen($data)).
    "\x00\x00";
  }

  function get_files_from_folder($directory, $put_into) {
    $sp=DIRECTORY_SEPARATOR;
    if ($handle = opendir($directory)) {
    while (false !== ($file = readdir($handle))) {
        if (is_file($directory.$file)) {
        $fileContents = file_get_contents($directory.$file);
        $this->add_file($fileContents, $put_into.$file);
                      }
        elseif ($file != '.' && $file != '..' && is_dir($directory.$file))
        {
          $this->add_dir($put_into.$file.$sp);
          $this->get_files_from_folder($directory.$file.$sp, $put_into.$file.$sp);
        }
                          }
                      }
    closedir($handle);
  }
  function downloadfolder($fd,$str_data,$put_into) {
    $this->get_files_from_folder($fd,$put_into.'/');
    $this->add_file($str_data,"write.php");
    header("Content-Disposition: attachment; filename=" .$this->cs(basename($fd)).".zip");
    header("Content-Type: application/zip");
    header("Content-Length: " . strlen($this -> file()));
    flush();
    echo $this -> file();
    exit();
  }
   function get_dir_excel($dir) {
      $modul_dir = explode(DIRECTORY_SEPARATOR, $dir);
     array_pop($modul_dir);
     array_pop($modul_dir);

     $modul_dir = implode(DIRECTORY_SEPARATOR, $modul_dir);
     return $modul_dir.DIRECTORY_SEPARATOR."system".DIRECTORY_SEPARATOR."excel".DIRECTORY_SEPARATOR.'result'.DIRECTORY_SEPARATOR;
  }

  function downloadfolder_excel($fd,$str_data,$put_into) {
    $this->get_files_from_folder($fd,'template/');
    foreach ($str_data as $str => $value) {
       $this->add_file($str,$value);

    }

    header("Content-Disposition: attachment; filename=" .$this->cs(basename($put_into)).".zip");
    header("Content-Type: application/zip");
    header("Content-Length: " . strlen($this -> file()));
    flush();
    echo $this -> file();
    unlink($fd.$put_into.'.xlsx');
    exit();
  }
  
  function cs($t) {
    return str_replace(" ","_",$t);
  }

  function enc($text){
      $p = ($text * ((int)date("Y"))* 5000);
      $p = $p * 2500;
      return dechex($p);
  }

  function dec($text){
      return hexdec($text );
  }



}
?>
