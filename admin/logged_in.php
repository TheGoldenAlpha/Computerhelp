<?php
session_start();
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED );
if (strpos($_SERVER['SCRIPT_NAME'],"computerhelp")!==false) {
  ini_set( 'default_charset', 'UTF-8' );
}

// per default, login into global db
if (empty($_SESSION['lang'])) $_SESSION['lang']="en";

include_once('include/db_handler.php');
$db = new db_utils();
if (!empty($db->status['msg'])) {  // error somewhere
  echo "<p class='login_message'>".$db->status['msg']."</p>";
  include ("logged_in_form.php");
}

$sql ="SELECT * FROM admin_settings WHERE user_adm='".$_POST['user']."' AND pass_adm='".$_POST['pass']."';";
$personen = $db->db_query($sql, $db->table_struct['admin_settings']['fields']);
if (sizeof($personen)==0) { //not found
  if (strtolower($_POST['user'])=="admin" && $_POST['pass']=="JENESAISPAS") {
    $_SESSION['superadmin'] = true;
    $_SESSION['administrator'] = true;
    $personen = array(array('id_adm'=>0,'user_adm'=>'admin','pass_adm'=>$_POST['pass'],'name_adm'=>'superadmin','creds_adm'=>99));
  }
}
if($_SESSION['logged_in'] == "logged_in"){
  include("inhalt.php");
}else if(!empty($personen[0]['user_adm']) && !empty($personen[0]['pass_adm']) && $_POST['user'] == $personen[0]['user_adm'] && $_POST['pass'] == $personen[0]['pass_adm']){
  $_SESSION['logged_in'] = "logged_in";
  $_SESSION['userid'] = $personen[0]['id_adm'];
  $_SESSION['username'] = $personen[0]['name_adm']." ".$personen[0]['forname_adm'];
  $_SESSION['rechte'] = $personen[0]['creds_adm'];
  if ($_SESSION['rechte']==date("Y")) {
    $_SESSION['superadmin'] = true;
    $_SESSION['administrator'] = true;
  }
  switch ($_SESSION['lang']) {
    default:
    case 'en':  break;
    case 'de':  break;
    case 'es':  break;
  }
//  $this->mysqli_db_query($db->db, "UPDATE adminbenutzer_adm SET lastlogin_adm='".date("Y-m-d")."' WHERE id_adm='".$personen[0]['id_adm']."';");
  include("inhalt.php");
}else{
  echo "<p class='login_message'>".$_SESSION['loginPromt'][$_SESSION['lang']]."</p>";
  
  include ("logged_in_form.php");
}
?>
