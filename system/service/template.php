<?php
$service_template = '<?php
	//doc route
	$app->get(\'/'.$service_name.'/doc\',function() use ($db) {
		include "doc.php";
	});


	//login action
	$app->post(\'/'.$service_name.'/login\', function() use ($app,$db) {
		auth_data($app,$db,"'.$format_data.'");
	});

	//auth status
	$read_auth = ($db->fetch_single_row(\'sys_services\',\'nav_act\',\''.$service_name.'\')->read_auth=="Y")?$authenticate(\'xml\'):"noauth";
	//url route
	$app->get(\'/'.$service_name.'\',$read_auth, function() use ($app,$apiClass,$pg) {
		$data = $pg->query("'.$select_query.'");
		if ($data==true) {
		$response[\'status\'] = array();
		//meta data
		$response[\'meta\'][\'total-records\'] = $pg->total_record;
		$response[\'meta\'][\'current-records\'] = $pg->total_current_record;
		$response[\'meta\'][\'total-pages\'] = $pg->total_pages;
		$response[\'meta\'][\'current-page\'] = $pg->page;

		$response[\'results\'] = array();
		foreach ($data as $dt) {
		//status code
		$response[\'status\'][\'code\'] = 200;
		$response[\'status\'][\'message\'] = "Ok";
			'.$view_data_access.'
		//result data
		array_push($response[\'results\'],$row);

		}
		//paginations link
		$response[\'paginations\'] = array();
		$response[\'paginations\'][\'self\'] = $pg->api_current_uri($apiClass->uri_segment(0));
		$response[\'paginations\'][\'first\'] = $pg->api_first($apiClass->uri_segment(0));
		$response[\'paginations\'][\'prev\'] = $pg->api_prev($apiClass->uri_segment(0));
		$response[\'paginations\'][\'next\'] = $pg->api_next($apiClass->uri_segment(0));
		$response[\'paginations\'][\'last\'] = $pg->api_last($apiClass->uri_segment(0));
		
        echoResponse(200, $response,"'.$format_data.'");
		} else {
			$response[\'status\'][\'code\'] = 422;
			$response[\'status\'][\'message\'] = $pg->getErrorMessage();
			echoResponse(422, $response,"'.$format_data.'");
		}
	});

	$app->get(\'/'.$service_name.'/:id\',$read_auth, function($id) use ($app,$apiClass,$pg) {
	$data = $pg->query("'.$select_query_detail.'",array(\''.$primary_key.'\'=>$id));
	if ($data==true) {
		$response[\'status\'] = array();

		if ($data->rowCount()>0) {
			$response[\'results\'] = array();
			foreach ($data as $dt) {
			//status code
			$response[\'status\'][\'code\'] = 200;
			$response[\'status\'][\'message\'] = "Ok";
				'.$view_data_access.'
			//result data
			array_push($response[\'results\'],$row);
			}
	        echoResponse(200, $response,"'.$format_data.'");
		} else {
			$response[\'status\'][\'code\'] = 404;
            $response[\'status\']["message"] = "The requested resource doesn\'t exists";
            echoResponse(404, $response,"'.$format_data.'");
		}

	} else {
		$response[\'status\'][\'code\'] = 422;
		$response[\'status\'][\'message\'] = $pg->getErrorMessage();
		echoResponse(422, $response,"'.$format_data.'");
	}
	});

	//auth status
	$create_auth = ($db->fetch_single_row(\'sys_services\',\'nav_act\',\''.$service_name.'\')->create_auth=="Y")?$authenticate(\'xml\'):"noauth";
	//post '.$service_name.'
	$app->post(\'/'.$service_name.'\',$create_auth, function() use ($app,$db,$apiClass) {
	 		$app = \Slim\Slim::getInstance();
	 		$request = $app->request();

	 		'.$top_post_images.'
	 		'.$valid_array.'
	 		'.$data_posts.'

	 		$val = $apiClass->assert($validation);

	 		if (empty($apiClass->errors())) {
	 			'.$upload_post_images.'
	 			$in = $db->insert(\''.$main_table.'\',$data);

	 			if ($in==true) {
	 			$id = $db->last_insert_id();
	 			$response[\'status\'][\'code\'] = 201;
                $response[\'status\']["message"] = "'.$service_name.' created successfully";
                $response[\'status\']["id"] = $id;
                echoResponse(201, $response,"'.$format_data.'");
		 		} else {
					$response[\'status\'][\'code\'] = 422;
					$response[\'status\'][\'message\'] = $db->getErrorMessage();
					echoResponse(422, $response,"'.$format_data.'");
		 		}
	 		} else {
					$response[\'status\'][\'code\'] = 422;
					foreach ($apiClass->errors() as $error) {
						$response[\'status\'][\'message\'] = $error;	
					}
					echoResponse(422, $response,"'.$format_data.'");
	 		}

	});

	//auth status
	$update_auth = ($db->fetch_single_row(\'sys_services\',\'nav_act\',\''.$service_name.'\')->update_auth=="Y")?$authenticate(\'xml\'):"noauth";

	//update '.$service_name.'
	$app->put(\'/'.$service_name.'/:id\',$update_auth, function($id) use ($app,$db,$apiClass) {
	 		$app = \Slim\Slim::getInstance();
	 		$request = $app->request();
	 		
	 		$validation = array();
	 		$data = array();

	 		$data_stream = array();
			new lib\stream($data_stream);

	 		$_PUT = $data_stream[\'post\'];
			$_FILES = $data_stream[\'file\'];

	 		'.$data_put_updates.'

	 	      if (!empty($data)) {

	        if (!empty($validation)) {
	          $val = $apiClass->assert($validation);

	          if (empty($apiClass->errors())) {
	          	'.$upload_update_images.'
	            $up = $db->update("'.$main_table.'",$data,"'.$primary_key.'",$id);

	            if ($up==true) {
	              $response["status"]["code"] = 200;
	                      $response["status"]["message"] = ucwords("'.$service_name.'")." Updated successfully";
	                      echoResponse(200, $response,"'.$format_data.'");
	            } else {
	              $response["status"]["code"] = 422;
	              $response["status"]["message"] = $apiClass->pdo->getErrorMessage();
	              echoResponse(422, $response,"'.$format_data.'");
	            }
	          } else {
	              $response["status"]["code"] = 422;
	              foreach ($apiClass->errors() as $error) {
	                $response["status"]["message"] = $error;  
	              }
	              echoResponse(422, $response,"'.$format_data.'");
	          }
	        } else {
	            $up = $db->update("'.$main_table.'",$data,"$primary_key",$id);

	            if ($up==true) {
	              $response["status"]["code"] = 200;
	                      $response["status"]["message"] = ucwords("'.$service_name.'")." Updated successfully";
	                      echoResponse(200, $response,"'.$format_data.'");
	            } else {
	              $response["status"]["code"] = 422;
	              $response["status"]["message"] = $db->getErrorMessage();
	              echoResponse(422, $response,"'.$format_data.'");
	            }
	        }

	      } else {
	          $response["status"]["code"] = 422;
	                  $response["status"]["message"] = "Unprocessable Entity";
	                  echoResponse(422, $response,"'.$format_data.'");
	      }
	});


//auth status
$delete_auth = ($db->fetch_single_row(\'sys_services\',\'nav_act\',\''.$service_name.'\')->delete_auth=="Y")?$authenticate(\'xml\'):"noauth";

	//delete '.$service_name.'
	$app->delete(\'/'.$service_name.'/delete/:id\',$delete_auth, function($id) use ($app,$db,$apiClass) {
			$single_data = $db->fetch_single_row("'.$main_table.'","'.$primary_key.'",$id);
			'.$delete_file_images.'
	 		$up = $db->delete(\''.$main_table.'\',\''.$primary_key.'\',$id);

	 		if ($up==true) {
	 			$response[\'status\'][\'code\'] = 200;
                $response[\'status\']["message"] = "'.$service_name.' Deleted successfully";
                echoResponse(200, $response,"'.$format_data.'");
	 		} else {
				$response[\'status\'][\'code\'] = 422;
				$response[\'status\'][\'message\'] = $db->getErrorMessage();
				echoResponse(422, $response,"'.$format_data.'");
	 		}

	});

	//search '.$service_name.'
	$app->get(\'/'.$service_name.'/search/:search\',$read_auth, function($search) use ($app,$db,$pg,$apiClass) {
          $search_condition = $db->getRawWhereFilterForColumns($search, array('.$col_head.'));
          $search_condition = "where $search_condition";
	 	$data = $pg->query("'.$select_query.' $search_condition");
	 	if ($data==true) {
	 		$response[\'status\'] = array();
	 		$response[\'results\'] = array();
	 		foreach ($data as $dt) {
				//status code
				$response[\'status\'][\'code\'] = 200;
				$response[\'status\'][\'message\'] = "Ok";
				'.$view_data_access.'
				//result data
				array_push($response[\'results\'],$row);	 			
	 		}
	 				//paginations link
		$response[\'paginations\'] = array();
		$response[\'paginations\'][\'self\'] = $pg->api_current_uri($apiClass->uri_segment(0),$apiClass->uri_segment(2));
		$response[\'paginations\'][\'first\'] = $pg->api_first($apiClass->uri_segment(0),$apiClass->uri_segment(2));
		$response[\'paginations\'][\'prev\'] = $pg->api_prev($apiClass->uri_segment(0),$apiClass->uri_segment(2));
		$response[\'paginations\'][\'next\'] = $pg->api_next($apiClass->uri_segment(0),$apiClass->uri_segment(2));
		$response[\'paginations\'][\'last\'] = $pg->api_last($apiClass->uri_segment(0),$apiClass->uri_segment(2));
			echoResponse(200, $response,"'.$format_data.'");
	 	} else {
			$response[\'status\'][\'code\'] = 422;
			$response[\'status\'][\'message\'] = $db->getErrorMessage();
			echoResponse(422, $response,"'.$format_data.'");
	 	}
	});

	';
