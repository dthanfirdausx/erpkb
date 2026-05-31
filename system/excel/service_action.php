<?php
session_start();
include "../../inc/config.php";
require_once('../../inc/lib/pclzip.lib.php');

session_check_adm();
switch ($_GET['act']) {
	case 'delete_write':
		unlink('../../modul/write.php');
		break;
	case 'import':

            if (!preg_match("/.(zip)$/i", $_FILES["page_file"]["name"]) ) {

				echo "Make Sure Your File is zip archive";

				exit();

			} else {

			$zip = new PclZip($_FILES["page_file"]["tmp_name"]);
			if (($list = $zip->listContent()) == 0) {
			   die("Error : ".$zip->errorInfo(true));
			}

			foreach ($list as $ls) {
				$filename[] = $ls['filename'];
			}
			if (!in_array('write.php', $filename)) {
			  	echo "Please choose the right Page/module File";
			} else {
				if ($zip->extract(PCLZIP_OPT_PATH, '../../modul/') == 0) {
			    	echo "error while extract, try to change your module directory permission with chmod 777";
				} else {
			    	echo "good";
				}
			}

	}


		break;
	case 'in':
		$nav_act = strtolower(str_replace(" ", "_", $_POST['page_name']));
		$data = array(
			'page_name'=>$_POST['page_name'],
			'nav_act'=>$nav_act,
			'modul_id'=>$_POST['modul_id']
			);
		$in = $db->insert('sys_menu',$data);
		if ($in=true) {
			echo "good";
		} else {
			return false;
		}
		break;
	case 'back':

		$string = '
		<?php
		session_start();
		include "../inc/config.php";
		';
		$data = $db->query("select * from sys_group_users");

		$string .= '$data = array(
		';
		foreach ($data as $isi) {
			$string.= "array(
				'level' => '$isi->level',
				'level_name' => '$isi->level_name',
				'deskripsi' => '$isi->deskripsi'
				),
				";

		}

		$string .=');

		//check group exist
				foreach ($data as $dt) {
			$check = $db->check_exist("sys_group_users",array("level" => $dt["level"]));
			if ($check==false) {
				$data = array(
					"level" => $dt["level"],
					"level_name" => $dt["level_name"],
					"deskripsi" => $dt["deskripsi"]
					);
				$db->insert("sys_group_users",$data);
			}
		}
		';

		$data = $db->query("select * from sys_menu where nav_act=?",array('nav_act'=>$_GET['page']));

		foreach ($data as $dt) {
			$menu_query = $db->convert_obj_to_array($dt);
		}

		unset($menu_query['id']);



		$menu_column = implode(array_keys($menu_query), ",");

		$menu_val = implode("','", array_values($menu_query));
		$menu_val = "'".$menu_val."'";

		$string .= '
		//insert page
		$db->query("insert into sys_menu ('.$menu_column.') values('.$menu_val.')");';

		$string .= '
		$last_id = $db->last_insert_id();
		';

		if ($dt->parent_name!="") {
			$get_parent = $db->fetch_single_row('sys_menu','parent_name',$dt->parent_name);
				$string.= '
				$get_parent_id = $db->fetch_single_row("sys_menu","page_name","'.$get_parent->parent_name.'")->id;
				$db->update("sys_menu",array("parent" => $get_parent_id),"id",$last_id);
				';

		}

		$string .='//insert menu role
		';

		$data = $db->query("select group_level,read_act,insert_act,update_act,delete_act
			from sys_menu_role inner join sys_menu  on sys_menu_role.id_menu=sys_menu.id where sys_menu.nav_act='".$_GET['page']."'");

		$string .= '$data = array(
		';
		foreach ($data as $isi) {
			$string.= "array(
				'id_menu' => ".'$last_id'.",
				'group_level' => '$isi->group_level',
				'read_act' => '$isi->read_act',
				'insert_act' => '$isi->insert_act',
				'update_act' => '$isi->update_act',
				'delete_act' => '$isi->delete_act'
				),
				";
		}

		$string .=');
		//insert to menu role
		foreach ($data as $dt) {
			$db->insert("sys_menu_role",$dt);
		}

		?>';

		$dir_to_download = $db->get_dir(getcwd()).$_GET['page'].DIRECTORY_SEPARATOR;
		$db->downloadfolder($dir_to_download,$string,$_GET['page']);
		break;
	case 'delete':
	$check_type = $db->fetch_single_row('sys_services','id',$_GET['id']);
		if (file_exists(SITE_ROOT."/api/services/".$db->fetch_single_row('sys_services','id',$_GET['id'])->nav_act)) {
			$db->deleteDirectory(SITE_ROOT."/api/services/".$db->fetch_single_row('sys_services','id',$_GET['id'])->nav_act);
		}
		$db->delete('sys_services','id',$_GET['id']);

		break;
	case 'up':

	if ($_POST['parent']!=0) {
		$parent_name = $db->fetch_single_row('sys_menu','id',$_POST['parent'])->page_name;
	} else {
		$parent_name = "";
	}

		$data = array(
			'page_name'=>$_POST['page_name'],
			'urutan_menu'=>$_POST['urutan_menu'],
			'parent_name' => $parent_name,
			'icon'=>$_POST['icon'],
			'parent'=>$_POST['parent']
			);
		$up = $db->update('sys_menu',$data,'id',$_POST['id']);
		if ($up=true) {
			echo "good";
		} else {
			return false;
		}
		break;
	default:
		# code...
		break;
}

?>
