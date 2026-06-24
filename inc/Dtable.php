<?php
//error_reporting(0);
/*
 * Script:    DataTables PDO server-side script for PHP and MySQL
 * CopyLeft: March 2016 - wildantea, wildantea.com
 * Email : wildannudin@gmail.com
 */
class DTable extends Database
{
    private $total_filtered;
    private $record_total;
    public $offset;
    public $data = array();
    public $request;
    public $search_request;
    public $is_numbering = 0;
    public $primary_key;
    public $order_by="";
    public $order_by_custom="";
    public $order_type="";
    public $group_by="";
    public $disable_search=array();
    public $callback = array();
    public $debug = 0;

    public $debug_sql="";

     function __construct($host,$port,$db_username,$db_password,$db_name)
    {
        parent::__construct($host,$port,$db_username,$db_password,$db_name);
    }

    //filter data
    public function get_column($col)
    {
        $col = array_diff($col, $this->disable_search);
        foreach ($col as $key) {
            $keys   = $key . " LIKE ?";
            $mark[] = $keys;
        }

        $im = implode(' OR  ', $mark);
        return $im;
    }

    public function get_value($col, $value)
    {
        $col = array_diff($col, $this->disable_search);
        foreach ($col as $key) {
            $val      = '%' . $value . '%';
            $result[] = $val;
        }

        return $result;
    }


    public function result_data($sql,$prepare_data=null)
    {
        $result = $this->query($sql,$prepare_data);
        if ($this->getErrorMessage()!="") {
            $this->set_callback(array('error_data' => $this->getErrorMessage(),'query_detail_result' => $sql));
        } else {
           return $result;
        }



    }

    public function set_total_record($sql,$prepare_data=null)
    {

        if ($this->group_by!="") {
            $jml_data = $this->query($sql,$prepare_data)->rowCount();
        } else {
            $sql_for_counting = stristr($sql, 'from');
            //$sql_for_counting = str_replace("having", "where", $sql_for_counting);

            $jml_datas = $this->fetch_custom_single("select count(*) as jml ".$sql_for_counting,$prepare_data);
            $jml_data = $jml_datas->jml;
        }

        if ($this->getErrorMessage()!="") {
            $this->set_callback(array('error_data' => $this->getErrorMessage(),'query_detail_total' => "select count(*) as jml ".$sql_for_counting));
        } else {
            //total filtered default
            $this->record_total = $jml_data;
        }


    }


    public function set_total_filtered($sql,$prepare_data=null)
    {
        //echo $sql;
        if ($this->group_by!="") {
            $jml_data = $this->query($sql,$prepare_data)->rowCount();
        } else {
            $sql_for_counting = stristr($sql, 'from');
            //$sql_for_counting = str_replace("having", "where", $sql_for_counting);

            $jml_datas = $this->fetch_custom_single("select count(*) as jml ".$sql_for_counting,$prepare_data);
            $jml_data = $jml_datas->jml;
        }

        if ($this->getErrorMessage()!="") {
            $this->set_callback(array('error_data' => $this->getErrorMessage(),'query_detail_filter' => "select count(*) as jml ".$sql_for_counting));
        } else {
            //total filtered default
            $this->total_filtered = $jml_data;
        }


    }


    public function join_value($search_value,$where_data=null)
    {

        if ($where_data!=null) {
            $where_data = array_values($where_data);
        } else {
            $where_data = array();
        }
        $res = array_merge($where_data,$search_value);
        return $res;
    }


    //create numbering column
    public function number($number)
    {
        $requestData   = $_REQUEST['start']+$number;
        return $requestData;

    }

    public function set_numbering_status($status) {
         $this->is_numbering = $status;
    }

    public function get_numbering_status()
    {
        return $this->is_numbering;
    }

    public function set_order_by_custom($val)
    {
        $this->order_by_custom = $val;
    }
    public function set_order_by($val)
    {
        $this->order_by = $val;
    }
    public function get_order_by_custom()
    {
        return $this->order_by_custom;
    }

    public function get_order_by()
    {
        return $this->order_by;
    }



    public function set_order_type($val)
    {
        $this->order_type = $val;
    }


    //custom query datatable
    public function get_custom($sql, $columns,$prepare_data=null)
    {

        if ($prepare_data!==null) {
        $prepare_data=array_values($prepare_data);
        }




        //all data request
        $requestData   = $_REQUEST;
        $this->request = $requestData;


             $offset       = $requestData['start'];
             $offsets      = $offset ? $offset : 0;
             $this->offset = $offsets;




             if ($requestData['draw']==1) {

                if (isset($this->order_by_custom) && $this->order_by_custom!='') {
                    $do_order = $this->order_by_custom;
                    $do_order_type = "";
                    //echo "xx";
                } else {
                   //  echo "string";
                     $do_order = "ORDER BY ".$this->order_by;
                     $do_order_type = $this->order_type;
                }


             } /*elseif ($requestData['start']>0) {

                 $do_order = $this->order_by;
                 $do_order_type = $this->order_type;
             } elseif ($requestData['start']==0 && $requestData['order'][0]['column']==0) {
                $do_order = $this->order_by;
                 $do_order_type = $this->order_type;
             }*/
              else {
                if (!isset($requestData['order'][0]['column']) || !isset($requestData['order'][0]['dir'])) {
                    $do_order = $this->order_by!='' ? "ORDER BY ".$this->order_by : "";
                    $do_order_type = $this->order_type!='' ? $this->order_type : "asc";
                } else {
                    $order_column = (int)$requestData['order'][0]['column'];
                    $order_dir = strtolower($requestData['order'][0]['dir']) == 'desc' ? 'desc' : 'asc';
                    if ($this->is_numbering==true && $order_column!=0) {
                        $column_index = $order_column - 1;
                    } else {
                        $column_index = $order_column;
                    }
                    if (isset($columns[$column_index]) && $columns[$column_index]!="") {
                        $do_order = "ORDER BY ".$columns[$column_index];
                        $do_order_type = $order_dir;
                    } else {
                        $do_order = $this->order_by!='' ? "ORDER BY ".$this->order_by : "";
                        $do_order_type = $this->order_type!='' ? $this->order_type : "asc";
                    }
                }

             }

            // $this->set_callback(array('do_order' =>$do_order,'order_type' => $do_order_type));
//echo $do_order;
        if (!empty($requestData['search']['value'])) {

            $this->search_request  = $requestData['search']['value'];
            $this->set_total_record($sql." ".$this->group_by, $prepare_data);


            $after_remove = preg_replace('#\((([^()]+|(?R))*)\)#', "", $sql);

            if (strpos(strtolower($after_remove), "where")) {
                $condition = "and";
            } elseif (strpos(strtolower($after_remove), "having")) {
                $condition = "and";
            } else {
                $condition = "where";
            }

              //get search value
            $search_value = $this->get_value($columns, $this->search_request);

            //join search with where data and extract where data value
            $join = $this->join_value($search_value,$prepare_data);


            $sql = $sql;
            $sql .= " $condition (" . $this->get_column($columns).")";

       /*     echo $sql;
            print_r($join);*/
            $sql .= " ".$this->group_by." ".$do_order." ".$do_order_type.
            //set total filtered
            $this->set_total_filtered($sql,$join);

            if($requestData['length']<0) {
                $length = "";
            } else {
                $length = " LIMIT " . $requestData['start'].",".$requestData['length'];
            }
            $sql .= ' '.$length;


            $result = $this->result_data($sql,$join);


        } else {


            $sql .= $this->group_by." ".$do_order." ".$do_order_type;

            if ($this->debug==1) {
                $this->set_callback(array('detail_sql_total' => $sql));
            }


            $this->set_total_record($sql,$prepare_data);

            $this->set_total_filtered($sql,$prepare_data);

         /*   if ($orderBy!=$this->order_by && $orderByType!=$this->order_type) {

            }*/




            //$result = $sql;

            if($requestData['length']<0) {
                $length = "";
            } else {
                $length = " LIMIT " . $requestData['start'].",".$requestData['length'];
            }
            $sql .= $length;

            $result = $this->result_data($sql,$prepare_data);

        }

        //$data = $this->table_data($result,$columns);
        //
        return $result;
    }


    public function get_offset()
    {
        return $this->offset;
    }

    public function set_sql_debug($debug) {
        $this->debug_sql = $debug;
    }
    public function get_sql_debug() {
        return $this->debug_sql;
    }

    public function set_callback($callback) {
        $this->callback = $callback;
    }
    public function get_callback() {
        return $this->callback;
    }

    public function set_debug($debug) {
        $this->debug = $debug;
    }


    public function create_data()
    {
        $data      = $this->data;
        $json_data = array(
            "draw" => intval($this->request['draw']),
            "recordsTotal" => intval($this->record_total),
            "recordsFiltered" => intval($this->total_filtered),
            "data" => $data // total data array
        );
        if (!empty($this->get_callback())) {
            $json_data = array_merge($this->get_callback(),$json_data);
        }
        echo json_encode($json_data);
        // send data as json format
    }

    public function set_data($data)
    {
        $this->data = $data;
    }

}

?>
