<?php

//url parsing
function parse_path() {
  $path = array();
  if (isset($_SERVER['REQUEST_URI'])) {
    $request_path = explode('?', $_SERVER['REQUEST_URI']);
    $path['base'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
    $path['call_utf8'] = substr(urldecode($request_path[0]), strlen($path['base']) + 1);
    $path['call'] = $path['call_utf8'];
    if ($path['call'] === 'index.php') {
      $path['call'] = '';
    }
    $path['call_parts'] = explode('/', $path['call']);
    if (isset($path['call_parts'][0]) && $path['call_parts'][0] === 'index.php') {
      array_shift($path['call_parts']);
    }
    if (!isset($path['call_parts'][0]) || $path['call_parts'][0] !== '') {
      array_unshift($path['call_parts'], '');
    }
    if (isset($request_path[1]) && $request_path[1] !== '') {
      $path['query_utf8'] = urldecode($request_path[1]);
       $path['query'] = urldecode($request_path[1]);
    $vars = explode('&', $path['query']);
    foreach ($vars as $var) {
      $t = explode('=', $var);
      $path['query_vars'][$t[0]] = $t[1];
    }
    }
  }
return $path;
}


function uri_segment($segment)
{
    //url path
    $path = parse_path();
    if (isset($path['call_parts'][$segment])) {
        return $path['call_parts'][$segment];
    }
}

//simpan role url page user di array sesuai login session level
  $role_user=array();
  $role_act=array();
foreach ($db->query("select sys_menu.url from sys_menu inner join sys_menu_role on sys_menu.id=sys_menu_role.id_menu
    where sys_menu_role.group_level=? and sys_menu_role.read_act=?",array('sys_menu_role.group_level'=>$_SESSION['group_level'],'sys_menu_role.read_act'=>'Y')) as $role) {
  $role_user[]=$role->url;
}
//lebih detail detil crud role user
foreach ($db->query("select import_act,read_act,insert_act,update_act,delete_act from sys_menu inner join sys_menu_role on sys_menu.id=sys_menu_role.id_menu where sys_menu_role.group_level=? and sys_menu.url=?",array('sys_menu_role.group_level'=>$_SESSION['group_level'],'sys_menu.url'=>uri_segment(1))) as $role) {
  $role_act['up_act']=$role->update_act;
  $role_act['insert_act']=$role->insert_act;
  $role_act['del_act']=$role->delete_act;
  $role_act['import_act']=$role->import_act;
}


?>
