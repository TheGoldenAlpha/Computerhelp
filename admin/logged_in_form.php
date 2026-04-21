<?php
session_start();
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED );

// per default, login into global db

include_once 'include/db_handler.php';
$db = new db_utils();
if (!empty($db->status['msg'])) {  // error somewhere
  echo "<p class='login_message'>".$db->status['msg']."</p>";
  include ("logged_in_form.php");
}

if (empty($_SESSION['lang'])) $_SESSION['lang']="en";         //default language is english !!! ATTENTION: uses always lower case
$descr = $db->get_descr('admin_settings', $_SESSION['lang']); // get an array of descriptions for the passed table and the passed language
?>

<html>
  <head> 
  <link rel="stylesheet" href="./include/css/CH_admin_styles.css" type="text/css" media="screen" />
    <title>ADMIN :: 
<?php
echo $_SESSION['REG'];      
?>    
    </title>
    <script>
    function sf(){document.anmeldung.user.focus();}
    </script>
  </head>
  <body class='login_body' onLoad=sf()>
    <?php
      echo "
      <form name='anmeldung' class='login_form' method='post' action='logged_in.php?startup=true'><table>
      <tr><td class='login_title' colspan='2'>Global ADMIN-Tool :: ".$_SESSION['REG']."</td></tr>
      <tr><td class='login_text'>".$descr['user_adm']." :</td><td><input type='text' name='user'></td></tr>
      <tr><td class='login_text'>".$descr['pass_adm']." :</td><td><input type='password' name='pass'></td></tr>
      <tr><td></td><td><input type='submit' name='anmelden' value='".$_SESSION['logon'][$_SESSION['lang']]."'></td></tr>
      </table></form>";
    ?>
  </body>
</html>