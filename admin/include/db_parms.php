<?php
  $this->ftp_host = "ftp.computerhelp.ch";
  $this->ftp_user= "computerhelp";
  $this->ftp_pass="640568";
  $this->ftp_root="/httpdocs/"; //always with trailing /

  $this->db_host = "localhost";
  $this->db_user = "cmstest_db";
  $this->db_pass = "0Yxcc3&6";
  $this->db = "cmstest";
  $this->sms_gw_u_p = "user=comphelp&pass=SMS24comp";
  
//echo $_SERVER['DOCUMENT_ROOT'];
//echo "§§§§";
//echo $_SERVER['SCRIPT_NAME'];
//exit;  
  if (strpos($_SERVER['DOCUMENT_ROOT'],"computerhelp.ch")!==false) {
    $this->ftp_path="/subdomains/cms-test";
    $this->ftp_user= "computerhelp";
    $this->ftp_pass="640568";
  } else
  if (strpos($_SERVER['SCRIPT_NAME'],"computerhelp")!==false || strpos($_SERVER['DOCUMENT_ROOT'],"terminus-24.ch")!==false) {
    $this->ftp_host = gethostbyaddr($_SERVER['SERVER_ADDR']);     //ONLY when not debugging
    if ($this->ftp_host=="terminus24") $this->ftp_host = $_SERVER['SERVER_NAME'];
    $this->ftp_user= "ftp_user";
    $this->ftp_pass="ftp_pass";
    $this->ftp_root="/cmstest/";
    ini_set( 'default_charset', 'UTF-8' );
  }
  
  $this->db_forum_admin="admin_forum_db";
  $this->db_forum_pswd="OJGhz0Tux9Qek)AK";
  $this->db_forum_ad_user="admin";
  $this->db_forum_ad_pwd="ad_2026_min";
//  echo "Host=".$this->ftp_host." user=".$this->ftp_user." pass=".$this->ftp_pass." path=".$this->ftp_root;
//  exit;
?>