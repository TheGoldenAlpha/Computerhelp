<!DOCTYPE html>
<html>
  <head>
    <title><?php echo $_SESSION['REG']; ?></title>
    <meta http-equiv="Content-Type" content="text/html;">
    <script>
    function loaded() {
        window.setTimeout(CloseMe, 5000);
    }

    function CloseMe() {
        window.focus();
        window.close();
    }
    </script>    
  </head>
  <body onload="loaded();">
<?php
  session_start();
  error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED );
  date_default_timezone_set('Europe/Zurich');
  setlocale(LC_ALL, 'de_CH.utf8');
  include('../include/web_params.php');
  include('../admin/include/db_handler.php');
  $arg = urldecode($_GET['arg']);
  if (empty($arg)) exit;
  $message = mcrypt_decrypt(MCRYPT_RIJNDAEL_256,  mhash(MHASH_MD5,"tel24_".$_SERVER['SERVER_NAME']), $arg, MCRYPT_MODE_ECB);
  $message = str_replace("\x0","",$message);
  $parms = explode("&",$message);
  $id = explode("=",$parms[0]);
  $status = explode("=",$parms[1]);
  $db = new db_utils();
  $db->db_connect();
  $sql = "UPDATE calls SET status=".$status[1].", external=1 WHERE id=".$id[1];
  $res =   $db->dbquery($sql);
  if ($res) {
    echo "<div style='font-family: Arial; font-size:85px; font-weight:bold;color:red;'>Status geändert!";
    echo "</div></body></html>";
  }
?>
