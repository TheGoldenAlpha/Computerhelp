<?php
  session_start();
  error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED );
  include('../include/web_params.php');           // Get global parms
  if (empty($_SESSION['admlang']))
    $_SESSION['admlang'] = $_SESSION['lang'];
  
  if($_SESSION['logged_in'] == "logged_in"){  // when already logged_in
    if($_GET['destroy'] == true){ // destroy session when needed
      session_destroy();
    } else {
      include ("logged_in.php");  // else go on without re-login
    }
  }else{
    include ("logged_in_form.php"); // login form
  }
?>