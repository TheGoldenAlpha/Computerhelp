<?php
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
  session_start();
//Beschreibung:
//Die Klasse db_handler beinhaltet alle Funtionen, welche Datenbankoperationen ausführen, oder deren Hilfsfunktionen
 
class db_utils {

  var $db;
  var $version;  
  var $status;  // empty when ok, else contains an error message
  var $db_lnk;  
  var $db_host;
  var $db_user;
  var $db_pass;
  var $ftp_host;
  var $ftp_user;
  var $ftp_pass;
  var $ftp_path;
  var $cur_table;
  var $table_defs;
  var $table_struct;
  var $pictures;
  var $backgrounds;
  var $iconsDir;
  var $sms_gw_u_p;
  var $last_ins_call;
  var $fields;
  var $log;
    
  public function db_utils() {
    self::__construct();
  }
  
  public function __construct() {
/*
  Versions' history:
  1.0 Initial release
  1.1 Add Upload for single big files
  1.2 Add CSS Edit support and Gallery
  1.3 Add PageMenus Selected Submenus
  1.4 Change picture filetype handling for "application-octet"
  1.5 Add table picts for pages pictures, place menu in page layout, add pictures in page layout
  1.6 Changed db text value save (mix ""  and '' allowed)
      16-oct-13 GC -> page menus are filtered by matching menu title (ref)
  1.7 Add FAQs and some small cosmetic changes
  1.8 Change language & footer handling: now using web_params SESSIONS parms. Change copyright field to handle HTML 
  1.9 Use configs in web_params.php to select the displayed tables in admin menu
  2.0 Add Marquees table and HTML element, languages selectable in web_params
  2.1 Fix problem on db_query(): when list is used, the spaces must be removed otherwise bad field name is used
      Menus: last menu witdth can use CSS own descriptor (option in web_params)
      Fix smtp error, set ssl, add ReturnToHome with delay and add e-mail in admin
  2.2 Support datestamped tables
  2.3 Support supersized background table
  2.4 Add ZEEV specifics
  2.5 Add GC tools for local admin and UTF-8 charset
  2.6 Add Ticket Duration
  2.7 Add Soundfile per user
  2.8 Add Restive and BGS3
  2.9 Fix problem single ' in text fields (not saved)) 
  3.0 New: returns Edit Achive in Archive
  3.1 New: Add butons for Mail acknowledge.
  3.2 Fixed re-arm timeout when entering text or moving mouse
  3.3 Fixed case when changing archiving status to something else than "erledigt": is_archived reset to 0 (attention: uses fixed id=2 to specify "erledigt"!)
  3.4 Use filter on automatic refresh, but reset it when new incoming call
  3.5 New feature: orders
  4.0 Unification cms and tel24 plus adaptivity
  4.0a Better sorting by Diapos
  4.0b New features for title line
  5.0  11.Feb.20 New PHP 7.3
  5.1 03-feb-26/GC: fix transparency bei mid and small picture duplication )line 2281
  5.5 26-Feb-26/GC: fixed divers layout problems
  6.0 17-Mar-26/GC: updated to PHP 8.3
*/
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED );
    include($_SESSION['DOCUMENT_ROOT']."/admin/include/db_parms.php");

    if ($this->db_connect())
      $this->version="6.0";
    else
      $this->version="---";
    $this->log = false;
    $this->pictures = array();
    $this->backgrounds = array();
    $this->iconsDir = "pictures/icons/";

    if (!isset($_SESSION['lang'])) $_SESSION['lang']="de";
    if (!empty($_SESSION['admlang'])) $_SESSION['lang'] = $_SESSION['admlang'];
    $lng1 = $_SESSION['languages'][1];
    $lng2 = $_SESSION['languages'][2];
    $lng3 = $_SESSION['languages'][3];
    $lng_ix = array_search($_SESSION['lang'],$_SESSION['languages']);

//    
// TABLES' DEFINITIONS    
//
    $this->table_defs = array(
      "admin_settings" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Administration settings",
                                                               $_SESSION['languages'][2]=>"Admin Einstellungen",
                                                               $_SESSION['languages'][3]=>"Administratore")),
                                "paths"=>array("original"=>"admin/picts/",
                                               "size1"=>"admin/picts/small/",
                                               "size2"=>"admin/picts/mid/",
                                               "width1"=>"150",
                                               "width2"=>"500",
                                               "docs"=>""),
                                "forbidden"=>"mehrere",
                                "rows_per_page"=>"10000",
                                "cols_per_page"=>"100",
                                "icon"=>"picts/icons/house.gif"),
      "page_layouts" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"layouts",
                                                             $_SESSION['languages'][2]=>"Seitengestaltung",
                                                             $_SESSION['languages'][3]=>"layouts")),
                            "paths"=>array("original"=>"",
                                           "size1"=>"",
                                           "size2"=>"",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/application_edit.gif"),
      "menus" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"menus",
                                                      $_SESSION['languages'][2]=>"Menüs",
                                                      $_SESSION['languages'][3]=>"menus")),
                            "paths"=>array("original"=>"",
                                           "size1"=>"",
                                           "size2"=>"",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/main_menu.jpg"),
      "page_menus" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Pages' menus",
                                                           $_SESSION['languages'][2]=>"Seitenmenüs",
                                                           $_SESSION['languages'][3]=>"Pages' menus")),
                            "paths"=>array("original"=>"",
                                           "size1"=>"",
                                           "size2"=>"",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/menu_icon.jpg"),
      "clients" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Clients",
                                                         $_SESSION['languages'][2]=>"Kunden",
                                                         $_SESSION['languages'][3]=>"Clients")),
                             "paths"=>array("original"=>"pictures/clients/",
                                           "size1"=>"pictures/clients/small/",
                                           "size2"=>"pictures/clients/mid/",
                                           "width1"=>"150",
                                           "width2"=>"500",
                                           "docs"=>"docs/clients/"),
                            "forbidden"=>"",
                            "rows_per_page"=>"100",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "clients_types" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Clients types",
                                                              $_SESSION['languages'][2]=>"Kundentypen",
                                                              $_SESSION['languages'][3]=>"Clients types")),
                               "paths"=>array("original"=>"pictures/clients/",
                                              "size1"=>"pictures/clients/small/",
                                              "size2"=>"pictures/clients/mid/",
                                              "width1"=>"150",
                                              "width2"=>"500",
                                              "docs"=>"docs/clients/"),
                               "forbidden"=>"",
                               "rows_per_page"=>"10000",
                               "cols_per_page"=>"100",
                               "icon"=>"picts/icons/Pictures-icon.png"),
      "status" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Status",
                                                       $_SESSION['languages'][2]=>"Status",
                                                       $_SESSION['languages'][3]=>"Status")),
                               "paths"=>array("original"=>"admin/picts/",
                                              "size1"=>"admin/picts/small/",
                                              "size2"=>"admin/picts/mid/",
                                              "width1"=>"150",
                                              "width2"=>"500",
                                              "docs"=>""),
                               "forbidden"=>"",
                               "rows_per_page"=>"10000",
                               "cols_per_page"=>"100",
                               "icon"=>"picts/icons/Pictures-icon.png"),
      "calls" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Calls",
                                                         $_SESSION['languages'][2]=>"Anrufe",
                                                         $_SESSION['languages'][3]=>"Appel")),
                             "paths"=>array("original"=>"pictures/calls/",
                                           "size1"=>"pictures/calls/small/",
                                           "size2"=>"pictures/calls/mid/",
                                           "width1"=>"150",
                                           "width2"=>"500",
                                           "docs"=>"docs/calls/"),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "companies" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Companies",
                                                         $_SESSION['languages'][2]=>"Firmen",
                                                         $_SESSION['languages'][3]=>"Entreprises")),
                             "paths"=>array("original"=>"pictures/companies/",
                                           "size1"=>"pictures/companies/small/",
                                           "size2"=>"pictures/companies/mid/",
                                           "width1"=>"150",
                                           "width2"=>"500",
                                           "docs"=>"docs/companies/"),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "picts" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Pictures",
                                                      $_SESSION['languages'][2]=>"Bilder",
                                                      $_SESSION['languages'][3]=>"Pictures")),
                            "paths"=>array("original"=>"pictures/picts/",
                                           "size1"=>"pictures/picts/small/",
                                           "size2"=>"pictures/picts/mid/",
                                           "width1"=>"150",
                                           "width2"=>"800",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-Canon-icon.png"),
      "backgrounds" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Backgrounds",
                                                      $_SESSION['languages'][2]=>"Hintergünde",
                                                      $_SESSION['languages'][3]=>"Backgrounds")),
                            "paths"=>array("original"=>"pictures/backgrounds/",
                                           "size1"=>"pictures/backgrounds/small/",
                                           "size2"=>"pictures/backgrounds/mid/",
                                           "width1"=>"150",
                                           "width2"=>"800",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/backgrounds.jpg"),
      "marquees" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Marquees",
                                                         $_SESSION['languages'][2]=>"Laufbänder",
                                                         $_SESSION['languages'][3]=>"Marquees")),
                          "paths"=>array("original"=>"pictures/marquees/",
                                         "size1"=>"pictures/marquees/small/",
                                         "size2"=>"pictures/marquees/mid/",
                                         "width1"=>"150",
                                         "width2"=>"800",
                                         "docs"=>""),
                          "forbidden"=>"",
                          "rows_per_page"=>"10000",
                          "cols_per_page"=>"100",
                          "icon"=>"picts/icons/marquees.png"),
      "pictures" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"diashows",
                                                         $_SESSION['languages'][2]=>"Diaporamas",
                                                         $_SESSION['languages'][3]=>"diashows")),
                            "paths"=>array("original"=>"pictures/diashows/",
                                           "size1"=>"pictures/diashows/small/",
                                           "size2"=>"pictures/diashows/mid/",
                                           "width1"=>"150",
                                           "width2"=>"1000",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "galleries" => array("desc"=> array($_SESSION['languages'][1]=>"Galleries",
                                           $_SESSION['languages'][2]=>"Galerien",
                                           $_SESSION['languages'][3]=>"Galleries"),
                            "paths"=>array("original"=>"pictures/galleries/",
                                           "size1"=>"pictures/galleries/small/",
                                           "size2"=>"pictures/galleries/mid/",
                                           "width1"=>"150",
                                           "width2"=>"800",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-Canon-icon.png"),
      "videos" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Videos",
                                                       $_SESSION['languages'][2]=>"Videos",
                                                       $_SESSION['languages'][3]=>"Videos")),
                            "paths"=>array("original"=>"pictures/videos/",
                                           "size1"=>"",
                                           "size2"=>"",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/videos.jpg"),
      "texts" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Texts",
                                                      $_SESSION['languages'][2]=>"Texte",
                                                      $_SESSION['languages'][3]=>"Texts")),
                       "paths"=>array("original"=>"",
                                      "size1"=>"",
                                      "size2"=>"",
                                      "width1"=>"",
                                      "width2"=>"",
                                      "docs"=>""),
                       "forbidden"=>"",
                       "rows_per_page"=>"10000",
                       "cols_per_page"=>"100",
                       "icon"=>"picts/icons/application_edit.gif"),
      "news" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"news",
                                                     $_SESSION['languages'][2]=>"News",
                                                     $_SESSION['languages'][3]=>"news")),
                           "paths"=>array("original"=>"pictures/news/",
                                           "size1"=>"pictures/news/small/",
                                           "size2"=>"pictures/news/mid/",
                                           "width1"=>"150",
                                           "width2"=>"280",
                                           "docs"=>"docs/news/"),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/newspapersmall.jpg"),
      "faqs" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"FAQs",
                                                     $_SESSION['languages'][2]=>"FAQs",
                                                     $_SESSION['languages'][3]=>"FAQs")),
                           "paths"=>array("original"=>"pictures/faqs/",
                                           "size1"=>"pictures/faqs/small/",
                                           "size2"=>"pictures/faqs/mid/",
                                           "width1"=>"150",
                                           "width2"=>"280",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/application_edit.gif"),
      "dictums" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Day Infos",
                                                        $_SESSION['languages'][2]=>"Tageinfos",
                                                        $_SESSION['languages'][3]=>"Day Infos")),
                         "paths"=>array("original"=>"",
                                        "size1"=>"",
                                        "size2"=>"",
                                        "width1"=>"",
                                        "width2"=>"",
                                        "docs"=>""),
                         "forbidden"=>"",
                         "rows_per_page"=>"10000",
                         "cols_per_page"=>"100",
                         "icon"=>"picts/icons/Pictures-icon.png"),
      "sponsors" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Sponsors",
                                                         $_SESSION['languages'][2]=>"Sponsoren",
                                                         $_SESSION['languages'][3]=>"Sponsors")),
                             "paths"=>array("original"=>"pictures/sponsors/",
                                           "size1"=>"pictures/sponsors/small/",
                                           "size2"=>"pictures/sponsors/mid/",
                                           "width1"=>"150",
                                           "width2"=>"500",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "regions" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Regions, Continents",
                                                        $_SESSION['languages'][2]=>"Regionen, Kontinente",
                                                        $_SESSION['languages'][3]=>"Regions, Continents")),
                             "paths"=>array("original"=>"",
                                           "size1"=>"",
                                           "size2"=>"",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "countries" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Countries",
                                                          $_SESSION['languages'][2]=>"Länder",
                                                          $_SESSION['languages'][3]=>"Countries")),
                             "paths"=>array("original"=>"",
                                           "size1"=>"",
                                           "size2"=>"",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>""),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/Pictures-icon.png"),
      "filelinks" => array("desc"=> $this->language(array($_SESSION['languages'][1]=>"Data Links",
                                                          $_SESSION['languages'][2]=>"Dateien Links",
                                                          $_SESSION['languages'][3]=>"Data Links")),
                             "paths"=>array("original"=>"filelinks/",
                                           "size1"=>"filelinks/",
                                           "size2"=>"filelinks/",
                                           "width1"=>"",
                                           "width2"=>"",
                                           "docs"=>"filelinks/"),
                            "forbidden"=>"",
                            "rows_per_page"=>"10000",
                            "cols_per_page"=>"100",
                            "icon"=>"picts/icons/page.gif")
      );
    if ($_SESSION['has_pagemenus'] == "no") unset($this->table_defs["page_menus"]);
    if ($_SESSION['has_diashows'] == "no") unset($this->table_defs["pictures"]);
    if ($_SESSION['has_picts'] == "no") unset($this->table_defs["picts"]);
    if ($_SESSION['has_backgrounds'] == "no") unset($this->table_defs["backgrounds"]);
    if ($_SESSION['has_marquees'] == "no") unset($this->table_defs["marquees"]);
    if ($_SESSION['has_galeries'] == "no") unset($this->table_defs["galleries"]);
    if ($_SESSION['has_videos'] == "no") unset($this->table_defs["videos"]);
    if ($_SESSION['has_news'] == "no") unset($this->table_defs["news"]);
    if ($_SESSION['has_links'] == "no") unset($this->table_defs["filelinks"]);
    if ($_SESSION['has_faqs'] == "no") unset($this->table_defs["faqs"]);
    if ($_SESSION['has_sponsors'] == "no") unset($this->table_defs["sponsors"]);
    if ($_SESSION['has_clients'] == "no") {
      unset($this->table_defs["clients"]);
      unset($this->table_defs["clients_types"]);
    }
    if ($_SESSION['has_countries'] == "no") {
      unset($this->table_defs["countries"]);  
      unset($this->table_defs["regions"]);  
    }
    if ($_SESSION['has_status'] == "no") unset($this->table_defs["status"]);
    if ($_SESSION['has_calls'] == "no") unset($this->table_defs["calls"]);
    if ($_SESSION['has_companies'] == "no") unset($this->table_defs["companies"]);
    if ($_SESSION['has_dictums'] == "no") unset($this->table_defs["dictums"]);
      
//    
// TABLES' STRUCTURES   
//
// used special dropbox fields
    $dropBox_menus= array("en"=>"SELECT DISTINCT id_men,menu_name1,id_sub FROM menus Order By sorting",
                          "de"=>"SELECT DISTINCT id_men,menu_name2,id_sub FROM menus Order By sorting",
                          "es"=>"SELECT DISTINCT id_men,menu_name3,id_sub FROM menus Order By sorting");
    $dropBox_menus_refs= "SELECT DISTINCT id_men,menu_title,id_sub FROM menus Order By sorting";
    $dropBox_menus_texts= "SELECT DISTINCT menu_title,menu_title,id_men FROM menus Order By sorting";
    $dropBox_layouts= "SELECT id,layout_name,id FROM page_layouts Order By layout_name";
    $dropBox_country= "SELECT id,".$this->getFieldName('name').",id FROM countries Order By ".$this->getFieldName('name');
    $dropBox_region= "SELECT id,".$this->getFieldName('region').",id FROM regions Order By ".$this->getFieldName('region');
    $dropBox_sponsor_type = "SELECT id,sponsor_type".$_SESSION['lang'].",id FROM sponsor_types Order By id";
    $dropBox_client_type = "SELECT id,client_type$lng_ix,id FROM clients_types Order By sorting,client_type$lng_ix";
    $dropBox_companies = "SELECT id, name, id FROM companies ORDER BY name ASC";
    $dropBox_receivers = "SELECT id, name_2, id FROM clients ORDER BY name_2 ASC";
    $dropBox_editors = "SELECT id, uid, uid FROM clients WHERE activ=1 ORDER BY name_2 ASC";
    $dropBox_status = "SELECT id, status$lng_ix,id FROM status ORDER BY sorting,status$lng_ix ASC";

    
// the tables    
    $this->table_struct = array(
      "admin_settings" => array("fields"=>array("id_adm", "datestamp", "name_adm", "forname_adm", 
                                                "user_adm", "pass_adm", "creds_adm", "email", 
                                                "copyright", "metadescr_adm", "metakeywords_adm", "bannerlogo_adm", 
                                                "bannerlogomob_adm", "bannerlng_adm", "bannerlngsel_adm", "ovl_pic", 
                                                "ovl_pic_mob", "firstrun"),
                                "types"=>array("id_adm", "datestamp","text", "text", 
                                               "text", "text", "text", "text", 
                                               "textarea", "textblock", "textblock", "file", 
                                               "file", "file", "textarea", "file", 
                                               "file", "hidden"),
                                "sizes"=>array("11", "11", "100", "100", 
                                               "100", "100", "6", "50", 
                                               "100", "05,100", "05,100", "50", 
                                               "50", "50", "100", "50", 
                                               "50", "1"),
                                "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Name", "Forname", 
                                                                                                 "Username", "Password", "Credentials", "E-Mail", 
                                                                                                 "Copyright", "Site description", "Site keywords", "Banner logo", 
                                                                                                 "Banner logo Mobile", "Banner language", "Banner lng select", "Overlay Picture", 
                                                                                                 "Overlay Picture Mobile", ""),
                                                                $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Name", "Vorname", 
                                                                                                 "Benutzername", "Passwort", "Benutzerrechte", "E-Mail", 
                                                                                                 "Copyright", "Website Beschreibung", "Site keywords", "Banner logo", 
                                                                                                 "Banner logo Mobile", "Banner language", "Banner lng select", "Overlay-Bild", 
                                                                                                 "Overlay-Bild Mobile", ""),
                                                                $_SESSION['languages'][3]=>array("ID", "Last change", "Name", "Forname", 
                                                                                                 "Username", "Password", "Credentials", "E-Mail", 
                                                                                                 "Copyright", "Site description", "Site keywords", "Banner logo", 
                                                                                                 "Banner logo Mobile", "Banner language", "Banner lng select", "Overlay Picture", 
                                                                                                 "Overlay Picture Mobile", ""))),
                                "defaults"=>array("","","","","",
                                                  "","","","","",
                                                  "","","","","",
                                                  "","","","","",
                                                  "",""),
                                "inlist"=>array("1", "1", "1", "1", 
                                                "1", "1", "1", "1",
                                                "1", "0", "0", "0",
                                                "0", "0", "0", "0",
                                                "0", "0"),
                                "chklng"=>array("","","","",
                                                "","","","",
                                                "","","","",
                                                "","","","",
                                                "",""),
                                "infos"=> $this->language(array($_SESSION['languages'][1]=>array("","","","",
                                                                                                 "","","","",
                                                                                                 "","","","",
                                                                                                 "","","","",
                                                                                                 "",""),
                                                                $_SESSION['languages'][2]=>array("","","","",
                                                                                                 "","","","",
                                                                                                 "","","","",
                                                                                                 "","","","",
                                                                                                 "",""),
                                                                $_SESSION['languages'][3]=>array("","","","",
                                                                                                 "","","","",
                                                                                                 "","","","",
                                                                                                 "","","","",
                                                                                                 "","")))),
      "page_layouts" => array("fields"=>array("id", "datestamp", "layout_name", "layout_struct"),
                              "types"=>array("id", "datestamp", "text","textarea"),
                              "sizes"=>array("11", "11", "30", "05,10"),
                              "labels"=> $this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Layout name", "layout structure"),
                                                               $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Seitengestaltungsname", "Gestaltung"),
                                                               $_SESSION['languages'][3]=>array("ID", "Last change", "Layout name", "layout structure"))),
                              "defaults"=>array("","","",""),
                              "inlist"=>array("1", "1", "1","0"),
                              "chklng"=>array("","","",""),
                              "infos"=> $this->language(array($_SESSION['languages'][1]=>array("", "", "", ""),
                                                              $_SESSION['languages'][2]=>array("", "", "", ""),
                                                              $_SESSION['languages'][3]=>array("", "", "", "")))),
      "menus" => array("fields"=>array("id_men", "datestamp", "id_sub", "protected", "menu_title", "menu_link", "page_layout",
                                       "active1", "menu_name1", "menu_description1", 
                                       "active2", "menu_name2", "menu_description2", 
                                       "active3", "menu_name3", "menu_description3", "sorting"),
                       "types"=>array("id", "datestamp", $dropBox_menus_refs, "text", "text", "text", $dropBox_layouts, 
                                      "checkbox", "text", "text", 
                                      "checkbox", "text", "text", 
                                      "checkbox", "text", "text", "text"),
                       "sizes"=>array("11", "11", "50", "100", "50", "50", "30", 
                                      "1", "100", "100", 
                                      "1", "100", "100", 
                                      "1", "100", "100", "3"),
                       "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Submenu from", "Protected", "Menu Reference", "Link", "Page layout",
                                                                                        "active $lng1", "Menu name $lng1", "Menu description $lng1",
                                                                                        "active $lng2", "Menu name $lng2", "Menu description $lng2",
                                                                                        "active $lng3", "Menu name $lng3", "Menu description $lng3", "Sort order"),
                                                       $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Submenü von", "Geschützt", "Menü Referenz", "Link", "Seitengestaltung",
                                                                                        "aktiv $lng1", "Menüsname $lng1", "Menüsbeschreibung $lng1",
                                                                                        "aktiv $lng2", "Menüsname $lng2", "Menüsbeschreibung $lng2",
                                                                                        "aktiv $lng3", "Menüsname $lng3", "Menüsbeschreibung $lng3", "Reihenfolge"),
                                                       $_SESSION['languages'][3]=>array("ID", "Last change", "Submenu from", "Protected", "Menu Reference", "Link", "Page layout", 
                                                                                        "activo $lng1", "Menu name $lng1", "Menu description $lng1", 
                                                                                        "activo $lng2", "Menu name $lng2", "Menu description $lng2", 
                                                                                        "activo $lng3", "Menu name $lng3", "Menu description $lng3", "Sort order"))),
                       "defaults"=>array("","","","","","","1","","","1","","","1","","",""),
                       "inlist"=>array("1", "1", "1", "1", "1", "1", "1", "1","1","1", "1","1","1", "1","1","1"),
                       "chklng"=>array("","","","","","","",
                                       $_SESSION['languages'][1],$_SESSION['languages'][1],$_SESSION['languages'][1],
                                       $_SESSION['languages'][2],$_SESSION['languages'][2],$_SESSION['languages'][2],
                                       $_SESSION['languages'][3],$_SESSION['languages'][3],$_SESSION['languages'][3],""),
                       "infos"=>$this->language(array($_SESSION['languages'][1]=>array("","", "", "Blank=not protected<BR>Required Login Form[;params]", "", "", "", "", "", "","", "", "","", "", "",""),
                                                      $_SESSION['languages'][2]=>array("","", "", "Blank=not protected<BR>Required Login Form[;params]", "", "", "", "", "", "","", "", "","", "", "",""),
                                                      $_SESSION['languages'][3]=>array("","", "", "Blank=not protected<BR>Required Login Form[;params]", "", "", "", "", "", "","", "", "","", "", "","")))),
      "page_menus" => array("fields"=>array("id", "datestamp", "page", "menu"),
                            "types"=>array("id", "datestamp", $dropBox_menus_refs, "textarea"),
                            "sizes"=>array("11", "11", "50", "05,50"),
                            "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Page menu for", "Menu text"),
                                                            $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Seitenmenü für", "Menü Text"),
                                                            $_SESSION['languages'][3]=>array("ID", "Last change", "Page menu for", "Menu text"))),
                            "defaults"=>array("","","",""),
                            "inlist"=>array("1", "1", "1", "1"),
                            "chklng"=>array("","","",""),
                            "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", 
                                                                                            "Use:<br/>?#first_menu#?<br/>?*selected_first_menu*?<br/>*#selected_menu#*<br/>
                                                                                            ##normal_menu##<br/>__submenu__<br/>*_selected_Submenü_*"),
                                                           $_SESSION['languages'][2]=>array("", "", "", 
                                                                                            "Beispiele:<br/>?#Erstes_Menü#?<br/>?*selektiertes_erstes_Menü*?<br/>
                                                                                            *#selektiertes_Menü#*<br/>##Menü##<br/>__Submenü__<br/>*_selektiertes_Submenü_*"),
                                                           $_SESSION['languages'][3]=>array("", "", "", 
                                                                                            "Use:<br/>?#first_menu#?<br/>?*selected_first_menu*?<br/>*#selected_menu#*<br/>
                                                                                            ##normal_menu##<br/>__submenu__<br/>*_selected_Submenü_*")))),
      "clients" => array("fields"=>array("id", "datestamp", "sorting", "client_type", "uid", "upwd", "fpwd", "name", "name_2", "address", "pobox", "zip", "city", "phone", "fax", 
                          "email", "email2", "vat", "opentime", "zeevintern", "partner_cond1", "partner_cond2", "logo", "link", "cnt_deposits", "last_login"),
                          "types"=>array("id", "datestamp", "text", $dropBox_client_type, "unique", "password", "password", "text", "text", "text", "text", "text", "text", "text", "text",
                          "text", "text", "text", "textblock", "text", "file", "file", "file", "file", "text", "datetime_ro"),
                          "sizes"=>array("11", "11", "10", "100", "20", "100", "100", "50", "50", "50", "20", "20", "50", "20", "20", 
                                         "50", "50", "20", "04,50", "30", "30", "30", "30", "30", "10", "11"),
                          "labels"=>$this->language(array(
                          $_SESSION['languages'][1]=>array("ID", "Last change", "Sort order", "Client type", "Login name", "Password", "PDF-Password", "Client name", "Client name2", "Address", "PO Box", "ZIP", "City", "Phone", "Fax", 
                                                           "E-Mail", "E-Mail2", "VAT", "Open Time", "ZEEV intern", "Partner Conditions 1", "Partner Conditions 2", "Logo", "Web link", "Nr deposits", "Last Login"),
                          $_SESSION['languages'][2]=>array("ID", "Letzte Ã„nderung", "Reihenfolg", "Kundenart", "Login-Name", "Passwort", "PDF-Passwort", "Name, Firma", "Name2, Firma2", "Adresse", "Postfach", "PLZ", "Ort", "Tel", "Fax", 
                                                           "E-Mail", "E-Mail2", "MWSt", "Ã–ffnungszeiten", "ZEEV intern", "Partner Konditionen 1", "Partner Konditionen 2", "Logo","Web link", "Anz. ZahlprÃ¤mien", "Last Login"),
                          $_SESSION['languages'][3]=>array("ID", "DerniÃ¨re modif", "SÃ©quence", "Client type", "Identifiant", "Mot-de-Passe", "Mot-de-Passe/PDF", "Nom de client", "Nom2 de client", "Adresse", "BoÃ®te postale", "No Postal", "Ville", "TÃ©lÃ©phone", "Fax", 
                                                           "E-Mail", "E-Mail2", "TVA", "Heures d'ouverture", "ZEEV interne", "Conditions partenaire 1", "Conditions partenaire 2", "Logo", "Lien web", "Nr dÃ©pot", "Dernier Login"))),
                          "defaults"=>array("","","","","","","","","","","","","","","",
                                            "","","","","","","","","","",""),
                          "inlist"=>array("1", "1", "0", "1", "1", "0", "0", "1", "0", "0", "0", "1", "1", "0", "0",
                                          "1", "0", "0", "0", "0", "0", "0", "0", "0", "0", "1"),
                          "chklng"=>array("","","","","","","","","","","","","","",
                                          "","","","","","","","","","",""),
                          "infos"=>$this->language(array(
                          $_SESSION['languages'][1]=>array("","","","","","","","","","","","","","","",
                                                           "","","","","","","","","","",""),
                          $_SESSION['languages'][2]=>array("","","","","","","","","","","","","","","",
                                                           "","","","","","","","","","",""),
                          $_SESSION['languages'][3]=>array("","","","","","","","","","","","","","","",
                                                           "","","","","","","","","","","")))),
      "clients_types" => array("fields"=>array("id", "datestamp", "sorting", "client_type1", "client_type2", "client_type3"),
                               "types"=>array("id", "datestamp", "text", "text", "text", "text"),
                               "sizes"=>array("11", "11", "6", "20", "20", "20"),
                               "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Sorting", "Client type ($lng1)", "Client type ($lng2)", "Client type ($lng3)"),
                                                               $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Reihenfolge", "Kundentyp ($lng1)", "Kundentyp ($lng2)", "Kundentyp ($lng3)"),
                                                               $_SESSION['languages'][3]=>array("ID", "Last change", "Sorting", "Client type ($lng1)", "Client type ($lng2)", "Client type ($lng3)"))),
                               "defaults"=>array("","","","","",""),
                               "inlist"=>array("1","1","1","1","1","1"),
                               "chklng"=>array("","","",$_SESSION['languages'][1], $_SESSION['languages'][2], $_SESSION['languages'][3]),
                               "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][2]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][3]=>array("", "", "", "", "", "")))),
      "status" => array("fields"=>array("id", "datestamp", "sorting", "status1", "status2", "status3"),
                               "types"=>array("id", "datestamp", "text", "text", "text", "text"),
                               "sizes"=>array("11", "11", "6", "20", "20", "20"),
                               "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Sorting", "Status ($lng1)", "Status ($lng2)", "Status ($lng3)"),
                                                               $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Reihenfolge", "Status ($lng1)", "Status ($lng2)", "Status ($lng3)"),
                                                               $_SESSION['languages'][3]=>array("ID", "Last change", "Sorting", "Status ($lng1)", "Status ($lng2)", "Status ($lng3)"))),
                               "defaults"=>array("","","","","",""),
                               "inlist"=>array("1","1","0","1","1","1"),
                               "chklng"=>array("","","",$_SESSION['languages'][1], $_SESSION['languages'][2], $_SESSION['languages'][3]),
                               "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][2]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][3]=>array("", "", "", "", "", "")))),
      "calls" => array("fields"=>array("id", "datestamp", "caller_name", "caller_phonenumber", 
                                       "receiver_company", "receiver_employee", "message", "status", "external", 
                                       "is_urgent", "is_archived", "email_cnt", "sms_cnt", 
                                       "timestamp", "userstamp", "ticket", "remark", "duration", "extra"),
                       "types"=>array("id", "datestamp", "text", "text", 
                                      $dropBox_companies, $dropBox_receivers, "textarea", $dropBox_status, "checkbox", 
                                      "checkbox", "checkbox", "text", "text", 
                                      "datestamp", $dropBox_editors, "text", "textarea", "time", "checkbox"),
                       "sizes"=>array("11", "11", "20", "20", 
                                      "11", "20", "200", "20", "1", 
                                      "1", "1", "11", "11",
                                      "11", "11", "11", "10,20", "5", "1"),
                       "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "datestamp", "Caller", "Phone", 
                                                                                        "Receiver Company", "Receiver", "Message", "Status", "Via Email", 
                                                                                        "urgent", "archived", "Sent emails", "Sent SMS", 
                                                                                        "Date and Time", "Created by", "Ticket Nr", "Remark", "Duration", "Order"),
                                                       $_SESSION['languages'][2]=>array("ID", "datestamp", "Anrufer", "Telefon", 
                                                                                        "Emfpänger Firma", "Empfänger", "Nachricht", "Status", "Via Email", 
                                                                                        "dringend", "archiviert", "gesendete emails", "gesendete SMS", 
                                                                                        "Datum und Zeit", "Erfasser", "Ticket Nr", "Bemerkung", "Zeitaufwand", "Order"),
                                                       $_SESSION['languages'][3]=>array("ID", "datestamp", "Caller", "Phone", 
                                                                                        "Receiver Company", "Receiver", "Message", "Status", "Via Email", 
                                                                                        "urgent", "archived", "Sent emails", "Sent SMS", 
                                                                                        "Date and Time", "Created by", "Ticket Nr", "Remark", "Duration", "Order"))),
                       "defaults"=>array("","","","",
                                         "","","","","",
                                         "","","","",
                                         "","","","","",""),
                       "inlist"=>array("1", "0", "1", "1", 
                                       "1", "1", "0", "1", "0", 
                                       "1", "1", "1", "1",
                                       "1", "1", "1", "0", "1", "0"),
                       "chklng"=>array("","","","","","","","","","","","","","","","",""),
                       "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "","","", "","","","","",""),
                                                      $_SESSION['languages'][2]=>array("", "", "Bsp. Max Muster", "","", "","", "", "", "","","","","",""),
                                                      $_SESSION['languages'][3]=>array("", "", "", "", "","", "","", "", "","","","","","")))),
      "companies" => array("fields"=>array("id", "name", "is_client"),
                          "types"=>array("id", "unique", "checkbox"),
                          "sizes"=>array("11", "40", "1"),
                          "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Name", "Is client"),
                                                          $_SESSION['languages'][2]=>array("ID", "Name", "Ist Mandant"),
                                                          $_SESSION['languages'][3]=>array("ID", "Name", "Est client"))),
                          "defaults"=>array("","",""),
                          "inlist"=>array("1", "1", "1"),
                          "chklng"=>array("","",""),
                          "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "Look at the German version."),
                                                         $_SESSION['languages'][2]=>array("", "", "Telefonzentrale hat Mandante. D.h. dieses Feld ist aktiviert ausser für Telefonzentralen."),
                                                         $_SESSION['languages'][3]=>array("", "", "Regarder la version allemande.")))),
      "picts" => array("fields"=>array("id", "datestamp", "menu_ref", "active", "sorting", "name", "link", "text"),
                       "types"=>array("id", "datestamp",$dropBox_menus_refs, "checkbox", "text", "file", "text", "textarea"),
                       "sizes"=>array("11", "11", "50", "1", "3", "50", "50", "05,03"),
                       "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Name", "Link", "Text"),
                                                       $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Menü Ref", "aktiv", "Reihenfolge", "Name", "Link", "Text"),
                                                       $_SESSION['languages'][3]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Name", "Link", "Text"))),
                       "defaults"=>array("", "","","","","","",""),
                       "inlist"=>array("1", "1", "1","1","1","1","1","1"),
                       "chklng"=>array("", "","","","","","",""),
                       "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", ""),
                                                      $_SESSION['languages'][2]=>array("", "", "", "", "", "", "", ""),
                                                      $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "")))),
      "backgrounds" => array("fields"=>array("id", "datestamp", "menu_ref", "active", "sorting", "name"),
                             "types"=>array("id", "datestamp",$dropBox_menus_refs, "checkbox", "text", "file"),
                             "sizes"=>array("11", "11", "50", "1", "3", "50"),
                             "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Name"),
                                                             $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Menü Ref", "aktiv", "Reihenfolge", "Name"),
                                                             $_SESSION['languages'][3]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Name"))),
                             "defaults"=>array("", "","","","",""),
                             "inlist"=>array("1", "1", "1","1","1","1"),
                             "chklng"=>array("", "","","","",""),
                             "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", ""),
                                                            $_SESSION['languages'][2]=>array("", "", "", "", "", ""),
                                                            $_SESSION['languages'][3]=>array("", "", "", "", "", "")))),
      "marquees" => array("fields"=>array("id", "datestamp", "menu_ref", "active", "sorting", "name", "link", "text"),
                          "types"=>array("id", "datestamp",$dropBox_menus_refs, "checkbox", "text", "file", "text", "textarea"),
                          "sizes"=>array("11", "11", "50", "1", "3", "50", "50", "05,03"),
                          "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Name", "Link", "Text"),
                                                          $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Menü Ref", "aktiv", "Reihenfolge", "Name", "Link", "Text"),
                                                          $_SESSION['languages'][3]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Name", "Link", "Text"))),
                          "defaults"=>array("","","","","","","",""),
                          "inlist"=>array("1","1","1","1","1","1","1","1"),
                          "chklng"=>array("","","","","","","",""),
                          "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", ""),
                                                         $_SESSION['languages'][2]=>array("", "", "", "", "", "", "", ""),
                                                         $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "")))),
      "pictures" => array("fields"=>array("id", "datestamp", "menu_ref", "active", "sorting", "pic_name", "pic_link", "pic_text"),
                          "types"=>array("id", "datestamp",$dropBox_menus_refs, "checkbox", "text", "file", "text", "textarea"),
                          "sizes"=>array("11", "11", "50", "1", "3", "50", "50", "05,03"),
                          "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Picture name", "Picture link", "Picture text"),
                                                          $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Menü Ref", "aktiv", "Reihenfolge", "Bildname", "Bild-link", "Bild-text"),
                                                          $_SESSION['languages'][3]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Picture name", "Picture link", "Picture text"))),
                          "defaults"=>array("","","","","","","",""),
                          "inlist"=>array("1","1","1","1","1","1","1","1"),
                          "chklng"=>array("","","","","","","",""),
                          "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", ""),
                                                         $_SESSION['languages'][2]=>array("", "", "", "", "", "", "", ""),
                                                         $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "")))),
      "galleries" => array("fields"=>array("id", "datestamp", "menu_ref", "active", "sorting", "pic_name", "pic_link", "pic_text"),
                           "types"=>array("id", "datestamp",$dropBox_menus_refs, "checkbox", "text", "file", "text", "textarea"),
                           "sizes"=>array("11", "11", "50", "1", "3", "50", "50", "05,03"),
                           "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Picture name", "Picture link", "Picture text"),
                                                           $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Menü Ref", "aktiv", "Reihenfolge", "Bildname", "Bild-link", "Bild-text"),
                                                           $_SESSION['languages'][3]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Picture name", "Picture link", "Picture text"))),
                           "defaults"=>array("","","","","","","",""),
                           "inlist"=>array("1","1","1","1","1","1","1","1"),
                           "chklng"=>array("","","","","","","",""),
                           "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", ""),
                                                          $_SESSION['languages'][2]=>array("", "", "", "", "", "", "", ""),
                                                          $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "")))),
      "videos" => array("fields"=>array("id", "datestamp", "menu_ref", "active", "sorting", "vid_name", "vid_link", "vid_text1", "vid_text2", "vid_text3"),
                        "types"=>array("id", "datestamp",$dropBox_menus_refs, "checkbox", "text", "file", "text", "text", "text", "text"),
                        "sizes"=>array("11", "11", "50", "1", "3", "50", "100", "100", "100", "100"),
                        "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Menu Ref", "active", "Sort order", "Video name", "Video link", "Video text ($lng1)", "Video text ($lng1)", "Video text ($lng1)"),
                                                        $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Menü Ref", "aktiv", "Reihenfolge", "Videoname", "Video-link", "Video-text ($lng2)", "Video-text ($lng2)", "Video-text ($lng2)"),
                                                        $_SESSION['languages'][3]=>array("ID", "Dernière modif", "Référence de menu", "activo", "Séquence", "Video name", "Video link", "Video text ($ln3)", "Video text ($lng3)", "Video text ($lng3)"))),
                        "defaults"=>array("","","","","","","","","",""),
                        "inlist"=>array("1","1","1","1","1","1","1","1","1","1"),
                        "chklng"=>array("","","","","","","",$_SESSION['languages'][1], $_SESSION['languages'][2], $_SESSION['languages'][3]),
                        "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", "", "", ""),
                                                       $_SESSION['languages'][2]=>array("", "", "", "", "", "", "", "", "", ""),
                                                       $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "", "", "")))),
      "texts" => array("fields"=>array("id", "datestamp", "active", "name", "sorting", "title1", "text1", "menu1", "title2", "text2", "menu2", "title3", "text3", "menu3"),
                       "types"=>array("id", "datestamp", "checkbox", $dropBox_menus_texts, "text", "text", "textarea", "text", "text", "textarea", "text", "text", "textarea", "text"),
                       "sizes"=>array("11", "11", "1", "100", "4", "100", "50", "100", "100", "50", "100", "100", "50", "100"),
                       "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "active", "name", "order", 
                                                                                        "title $lng1", "text $lng1", "menu $lng1", 
                                                                                        "title $lng2", "text $lng2", "menu $lng2", 
                                                                                        "title $lng3", "text $lng3", "menu $lng3"),
                                                       $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "aktiv", "Name", "Order", 
                                                                                        "Titel $lng1", "Text $lng1", "Menu $lng1", 
                                                                                        "Titel $lng2", "Text $lng2", "Menu $lng2", 
                                                                                        "Titel $lng3", "Text $lng3", "Menu $lng3"),
                                                       $_SESSION['languages'][3]=>array("ID", "Last change", "active", "name", "order", 
                                                                                        "title $lng1", "text $lng1", "menu $lng1", 
                                                                                        "title $lng2", "text $lng2", "menu $lng2", 
                                                                                        "title $lng3", "text $lng3", "menu $lng3"))),
                       "defaults"=>array("","","","","","","","","","","","",""),
                       "inlist"=>array("1", "1", "1", "1", "0", "1","1","1","1","1","1","1","1"),
                       "chklng"=>array("","","","","",$_SESSION['languages'][1],$_SESSION['languages'][1],$_SESSION['languages'][1],
                                                   $_SESSION['languages'][2],$_SESSION['languages'][2],$_SESSION['languages'][2],
                                                   $_SESSION['languages'][3],$_SESSION['languages'][3],$_SESSION['languages'][3]),
                       "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", "","", "", "","", ""),
                                                      $_SESSION['languages'][2]=>array("", "", "", "Menü beziehung", "", "", "", "","", "", "","", ""),
                                                      $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "","", "", "","", "")))),
      "news" => array("fields"=>array("id", "datestamp", "date", "date_from", "date_to", "sorting", "event", "newsletter", "esmeralda", "title1", "text1", "more1", 
                                      "title2", "text2", "more2", "title3", "text3", "more3", "pdf_file", "picts"),
                      "types"=>array("id", "datestamp", "date", "date", "date", "text", "checkbox", "checkbox", "checkbox", "text", 
                                     "textarea", "text", "text", "textarea", "text", "text", "textarea", "text", "file", "file"),
                      "sizes"=>array("11", "11", "11", "11", "11", "5", "1", "1", "1", "100", "05,100", "100", 
                                     "100", "05,100", "100", "100", "05,100", "100", "100", "100"),
                      "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Date", "Valid from", "Valid to", "Sorting", "Event", "Newsletter", "Esmeralda", 
                                                                                       "Title $lng1", "Text $lng1", "More $lng1", 
                                                                                       "Title $lng2", "Text $lng2", "More $lng2", 
                                                                                       "Title $lng3", "Text $lng3", "More $lng3", "PDF file", "Picture"),
                                                      $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Datum", "Gültig vom", "Gültig bis", "Reihenfolge", "Event", "Newsletter", "Esmeralda", 
                                                                                       "Titel $lng1", "Texte $lng1", "Mehr $lng1", 
                                                                                       "Titel $lng2", "Texte $lng2", "Mehr $lng2", 
                                                                                       "Titel $lng3", "Texte $lng3", "Mehr $lng3", "PDF Datei", "Bild"),
                                                      $_SESSION['languages'][3]=>array("ID", "Last change", "Date", "Valid from", "Valid to", "Sorting", "Event", "Newsletter", "Esmeralda", 
                                                                                       "Title $lng1", "Text $lng1", "More $lng1", 
                                                                                       "Title $lng2", "Text $lng2", "More $lng2", 
                                                                                       "Title $lng3", "Text $lng3", "More $lng3", "PDF file", "Pictures"))),
                      "defaults"=>array("","","","","","0","","","","","","","","","","","","","",""),
                      "inlist"=>array("1", "1", "1","1","1","1","1","1","1","1","1","1","1","1","1","1","1","1","1","1"),
                      "chklng"=>array("","","","","","","","","",$_SESSION['languages'][1],$_SESSION['languages'][1],$_SESSION['languages'][1],
                                                                 $_SESSION['languages'][2],$_SESSION['languages'][2],$_SESSION['languages'][2],
                                                                 $_SESSION['languages'][3],$_SESSION['languages'][3],$_SESSION['languages'][3],"",""),
                      "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "When not empty, is on Homepage and follow the sorting order", "", "", "",
                                                                                      "","","","","","","","","", 
                                                                                      "For PDFs, a link to open the document will be proposed",
                                                                                      "For images, use <BR> 
                                                                                      pictures/file.xxx for original size pictures<BR>
                                                                                      pictures/mid/file.xxx for mid size pictures<BR>
                                                                                      or pictures/small/file.xxx for thumb size pictures"),
                                                     $_SESSION['languages'][2]=>array("", "", "", "", "", "When not empty, is on Homepage and follow the sorting order", "", "", "",
                                                                                      "","","","","","","","","", 
                                                                                      "Für PDFs, ein link um das Dokument zu öffnen ist angezeigt",
                                                                                      "Für Bilder einfügen, geben Sie<BR> 
                                                                                       pictures/file.xxx für Bilder in original Grösse<BR>
                                                                                       pictures/mid/file.xxx für Bilder in mid Grösse
                                                                                       or pictures/small/file.xxx für Bilder in thumb  Grösse"),
                                                     $_SESSION['languages'][3]=>array("", "", "", "", "", "When not empty, is on Homepage and follow the sorting order", "", "", "",
                                                                                      "","","","","","","","","", 
                                                                                      "For PDFs, a link to open the document will be proposed",
                                                                                      "For images, use <BR> 
                                                                                      pictures/file.xxx for original size pictures<BR>
                                                                                      pictures/mid/file.xxx for mid size pictures<BR>
                                                                                      or pictures/small/file.xxx for thumb size pictures")))),
      "faqs" => array("fields"=>array("id", "datestamp", "title1", "text1", "title2", "text2", "title3", "text3","position"),
                      "types"=>array("id", "datestamp", "text", "textarea", "text", "textarea", "text", "textarea", "text"),
                      "sizes"=>array("11", "11", "200", "05,200", "200", "05,200", "200", "05,200","10"),
                      "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Question ($lng1)", "Answer ($lng1)", "Question ($lng2)", "Answer ($lng2)", "Question ($lng3)", "Answer ($lng3)", "Sort order"),
                                                      $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Frage ($lng1)", "Antwort ($lng1)", "Frage ($lng2)", "Antwort ($lng2)", "Frage ($lng3)", "Antwort ($lng3)", "Sort order"),
                                                      $_SESSION['languages'][3]=>array("ID", "Last change", "Question ($lng1)", "Answer ($lng1)", "Question ($lng2)", "Answer ($lng2)", "Question ($lng3)", "Answer ($lng3)", "Sort order"))),
                      "defaults"=>array("","","","","","","","",""),
                      "inlist"=>array("1", "1", "1","1","1","1","1","1","1"),
                      "chklng"=>array("","",$_SESSION['languages'][1],$_SESSION['languages'][1],
                                            $_SESSION['languages'][2],$_SESSION['languages'][2],
                                            $_SESSION['languages'][3],$_SESSION['languages'][3],""),
                      "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", "", "", ""),
                                                     $_SESSION['languages'][2]=>array("", "", "", "", "", "", "", ""),
                                                     $_SESSION['languages'][3]=>array("", "", "", "", "", "", "", "")))),
      "dictums" => array("fields"=>array("id", "datestamp", "name", "text1", "text2", "text3"),
                               "types"=>array("id", "datestamp", "text", "text", "text", "text"),
                               "sizes"=>array("11", "11", "20", "20", "20", "20"),
                               "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Name", "Text ($lng1)", "Text ($lng2)", "Text ($lng3)"),
                                                               $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Name", "Text ($lng1)", "Text ($lng2)", "Text ($lng3)"),
                                                               $_SESSION['languages'][3]=>array("ID", "Last change", "Name", "Text ($lng1)", "Text ($lng2)", "Text ($lng3)"))),
                               "defaults"=>array("","","","","",""),
                               "inlist"=>array("1","1","0","1","1","1"),
                               "chklng"=>array("","","",$_SESSION['languages'][1], $_SESSION['languages'][2], $_SESSION['languages'][3]),
                               "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][2]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][3]=>array("", "", "", "", "", "")))),
      "sponsors" => array("fields"=>array("id", "datestamp", "sorting", "name","sponsor_type", "pic_name","link"),
                          "types"=>array("id", "datestamp", "text", "text", $dropBox_sponsor_type, "file", "text"),
                          "sizes"=>array("11", "11", "3", "50", "20", "50", "200"),
                          "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Sort order", "Sponsor name", "Sponsor type", "Picture name", "Web link"),
                                                          $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Gestaltung", "Sponsor name", "Sponsorart", "Bild name","Web link"),
                                                          $_SESSION['languages'][3]=>array("ID", "Last change", "Sort order", "Sponsor name", "Sponsor type", "Picture name", "Web link"))),
                          "defaults"=>array("","","","","","",""),
                          "inlist"=>array("1", "1", "1", "1", "1", "1", "1"),
                          "chklng"=>array("","","","","","",""),
                          "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "","","",""),
                                                         $_SESSION['languages'][2]=>array("", "", "", "","","",""),
                                                         $_SESSION['languages'][3]=>array("", "", "", "","","","")))),
      "sponsors_types" => array("fields"=>array("id", "datestamp", "sorting", "sponsor_type1", "sponsor_type2", "sponsor_type3"),
                                "types"=>array("id", "datestamp", "text", "text", "text", "text"),
                                "sizes"=>array("11", "11", "6", "20", "20", "20"),
                                "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Sorting", "Client type ($lng1)", "Client type ($lng2)", "Client type ($lng3)"),
                                                                $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Reihenfolge", "Kundentyp ($lng1)", "Kundentyp ($lng2)", "Kundentyp ($lng3)"),
                                                                $_SESSION['languages'][3]=>array("ID", "Last change", "Sorting", "Client type ($lng1)", "Client type ($lng2)", "Client type ($lng3)"))),
                               "defaults"=>array("","","","","",""),
                               "inlist"=>array("1","1","0","1","1","1"),
                               "chklng"=>array("","","",$_SESSION['languages'][1],$_SESSION['languages'][1],
                                                        $_SESSION['languages'][2],$_SESSION['languages'][2],
                                                        $_SESSION['languages'][3],$_SESSION['languages'][3]),
                               "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][2]=>array("", "", "", "", "", ""),
                                                              $_SESSION['languages'][3]=>array("", "", "", "", "", "")))),
      "regions" => array("fields"=>array("id", "datestamp", "region1", "region2", "region3"),
                            "types"=>array("id", "datestamp", "text", "text", "text"),
                            "sizes"=>array("11", "11", "50", "50", "50"),
                            "labels"=>array($_SESSION['languages'][1]=>array("ID", "Last change", "Region ($lng1)", "Region ($lng2)", "Region ($lng3)"),
                                            $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Gebiet ($lng1)", "Gebiet ($lng2)", "Gebiet ($lng3)"),
                                            $_SESSION['languages'][3]=>array("ID", "Last change", "Region ($lng1)", "Region ($lng2)", "Region ($lng3)")),
                            "defaults"=>array("","","","",""),
                            "inlist"=>array("1", "1", "1","1","1"),
                            "chklng"=>array("","",$_SESSION['languages'][1],$_SESSION['languages'][2],$_SESSION['languages'][3]),
                            "infos"=>array($_SESSION['languages'][1]=>array("", "", "", "",""),
                                           $_SESSION['languages'][2]=>array("", "", "", "",""),
                                           $_SESSION['languages'][3]=>array("", "", "", "",""))),
      "countries" => array("fields"=>array("id", "datestamp", "name1", "name2", "name3","c_code","region_id"),
                           "types"=>array("id", "datestamp", "text", "text", "text", "text", $dropBox_region),
                           "sizes"=>array("11", "11", "50", "50", "50", "4", "11"),
                           "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Country ($lng1)", "Country ($lng2)", "Country ($lng3)","Country code","Region"),
                                                           $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Land ($lng1)", "Land ($lng2)", "Land ($lng3)","Landkode","Gebiet"),
                                                           $_SESSION['languages'][3]=>array("ID", "Last change", "Country ($lng1)", "Country ($lng2)", "Country ($lng3)","Country code","Region"))),
                           "defaults"=>array("","","","","","",""),
                           "inlist"=>array("1", "1", "1","1","1","1","1"),
                           "chklng"=>array("","",$_SESSION['languages'][1],$_SESSION['languages'][2],$_SESSION['languages'][3],"",""),
                           "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "", "", "","","",""),
                                                          $_SESSION['languages'][2]=>array("", "", "", "","","",""),
                                                          $_SESSION['languages'][3]=>array("", "", "", "","","","")))),
      "filelinks" => array("fields"=>array("id", "datestamp", "sorting", "menu_ref", "name",  "title1", "text1", "link1", "file1", 
                                           "title2", "text2", "link2", "file2",  
                                           "title3", "text3", "link3", "file3", "intro"),
                           "types"=>array("id", "datestamp", "text", $dropBox_menus_refs, "text", "text", "textarea", "text", "file", 
                                          "text", "textarea", "text", "file", 
                                          "text", "textarea", "text", "file", "text"),
                           "sizes"=>array("11", "11", "4", "50", "50", "200", "05,200", "200", "50", 
                                          "200", "05,200", "200", "50", 
                                          "200", "05,200", "200", "50", "4"),
                           "labels"=>$this->language(array($_SESSION['languages'][1]=>array("ID", "Last change", "Sort order", "Menu Ref", "name",
                                                                                            "title $lng1", "text $lng1", "link $lng1", "file $lng1", 
                                                                                            "title $lng2", "text $lng2", "link $lng2", "file $lng2", 
                                                                                            "title $lng3", "text $lng3", "link $lng3", "file $lng3", "Intro"),
                                                           $_SESSION['languages'][2]=>array("ID", "Letzte Änderung", "Reihenfolge", "Menü Ref", "Name", 
                                                                                            "Titel $lng1", "Text $lng1", "Link $lng1", "Datei $lng1", 
                                                                                            "Titel $lng2", "Text $lng2", "Link $lng2", "Datei $lng2", 
                                                                                            "Titel $lng3", "Text $lng3", "Link $lng3", "Datei $lng3", "Einleitung"),
                                                           $_SESSION['languages'][3]=>array("ID", "Last change", "Sort order", "Menu Ref", "name", 
                                                                                            "titulo $lng1", "texto $lng1", "link $lng1", "file $lng1", 
                                                                                            "titulo $lng2", "texto $lng2", "link $lng2", "file $lng2", 
                                                                                            "titulo $lng3", "texto $lng3", "link $lng3", "file $lng3", "Intro"))),
                           "defaults"=>array("","","","","", "","","","", "","","","", "","","","", ""),
                           "inlist"=>array("1", "1", "1", "1", "1", "1","1", "1", "1", "1","1", "1", "1", "1","1", "1", "1", "1"),
                           "chklng"=>array("","","","","",$_SESSION['languages'][1],$_SESSION['languages'][1],$_SESSION['languages'][1],$_SESSION['languages'][1],
                                                          $_SESSION['languages'][2],$_SESSION['languages'][2],$_SESSION['languages'][2],$_SESSION['languages'][2],
                                                          $_SESSION['languages'][3],$_SESSION['languages'][3],$_SESSION['languages'][3],$_SESSION['languages'][3], ""),
                           "infos"=>$this->language(array($_SESSION['languages'][1]=>array("", "","","","", "","","","", "","","","", "","","","", ""),
                                                          $_SESSION['languages'][2]=>array("", "","","","", "","","","", "","","","", "","","","", ""),
                                                          $_SESSION['languages'][3]=>array("", "","","","", "","","","", "","","","", "","","","", ""))))
      );
         
      // for specific customers, remove fields in table struct
      if ($_SESSION['is_ZEEV'] != "yes") {
        unset($this->table_struct["clients"]["fields"][8]);
        unset($this->table_struct["clients"]["fields"][16]);
        unset($this->table_struct["clients"]["fields"][18]);
        unset($this->table_struct["clients"]["fields"][19]);
        unset($this->table_struct["clients"]["fields"][20]);
        unset($this->table_struct["clients"]["fields"][21]);
        unset($this->table_struct["clients"]["fields"][23]);
        unset($this->table_struct["clients"]["fields"][24]);
        unset($this->table_struct["clients"]["fields"][25]);
        unset($this->table_struct["clients"]["fields"][26]);
        $this->table_struct["clients"]["fields"] = array_values($this->table_struct["clients"]["fields"]);

        unset($this->table_struct["clients"]["types"][8]);
        unset($this->table_struct["clients"]["types"][16]);
        unset($this->table_struct["clients"]["types"][18]);
        unset($this->table_struct["clients"]["types"][19]);
        unset($this->table_struct["clients"]["types"][20]);
        unset($this->table_struct["clients"]["types"][21]);
        unset($this->table_struct["clients"]["types"][23]);
        unset($this->table_struct["clients"]["types"][24]);
        unset($this->table_struct["clients"]["types"][25]);
        unset($this->table_struct["clients"]["types"][26]);
        $this->table_struct["clients"]["types"] = array_values($this->table_struct["clients"]["types"]);

        unset($this->table_struct["clients"]["sizes"][8]);
        unset($this->table_struct["clients"]["sizes"][16]);
        unset($this->table_struct["clients"]["sizes"][18]);
        unset($this->table_struct["clients"]["sizes"][19]);
        unset($this->table_struct["clients"]["sizes"][20]);
        unset($this->table_struct["clients"]["sizes"][21]);
        unset($this->table_struct["clients"]["sizes"][23]);
        unset($this->table_struct["clients"]["sizes"][24]);
        unset($this->table_struct["clients"]["sizes"][25]);
        unset($this->table_struct["clients"]["sizes"][26]);
        $this->table_struct["clients"]["sizes"] = array_values($this->table_struct["clients"]["sizes"]);

        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][8]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][16]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][18]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][19]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][20]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][21]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][23]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][24]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][25]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]][26]);
        if (isset( $this->table_struct["clients"]["labels"][$_SESSION['languages'][1]]))
          $this->table_struct["clients"]["labels"][$_SESSION['languages'][1]] = array_values($this->table_struct["clients"]["labels"][$_SESSION['languages'][1]]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][8]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][16]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][18]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][19]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][20]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][21]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][23]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][24]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][25]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]][26]);
        if (isset( $this->table_struct["clients"]["labels"][$_SESSION['languages'][2]]))
          $this->table_struct["clients"]["labels"][$_SESSION['languages'][2]] = array_values($this->table_struct["clients"]["labels"][$_SESSION['languages'][2]]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][8]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][16]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][18]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][19]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][20]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][21]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][23]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][24]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][25]);
        unset($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]][26]);
        if (isset( $this->table_struct["clients"]["labels"][$_SESSION['languages'][3]]))
          $this->table_struct["clients"]["labels"][$_SESSION['languages'][3]] = array_values($this->table_struct["clients"]["labels"][$_SESSION['languages'][3]]);

        unset($this->table_structs["field"]["defaults"][8]);
        unset($this->table_structs["field"]["defaults"][16]);
        unset($this->table_structs["field"]["defaults"][18]);
        unset($this->table_structs["field"]["defaults"][19]);
        unset($this->table_structs["field"]["defaults"][20]);
        unset($this->table_structs["field"]["defaults"][21]);
        unset($this->table_structs["field"]["defaults"][23]);
        unset($this->table_structs["field"]["defaults"][24]);
        unset($this->table_structs["field"]["defaults"][25]);
        unset($this->table_structs["field"]["defaults"][26]);
        $this->table_struct["clients"]["defaults"] = array_values($this->table_struct["clients"]["defaults"]);

        unset($this->table_struct["clients"]["inlist"][8]);
        unset($this->table_struct["clients"]["inlist"][16]);
        unset($this->table_struct["clients"]["inlist"][18]);
        unset($this->table_struct["clients"]["inlist"][19]);
        unset($this->table_struct["clients"]["inlist"][20]);
        unset($this->table_struct["clients"]["inlist"][21]);
        unset($this->table_struct["clients"]["inlist"][23]);
        unset($this->table_struct["clients"]["inlist"][24]);
        unset($this->table_struct["clients"]["inlist"][25]);
        unset($this->table_struct["clients"]["inlist"][26]);
        $this->table_struct["clients"]["inlist"] = array_values($this->table_struct["clients"]["inlist"]);

        unset($this->table_struct["clients"]["chklng"][8]);
        unset($this->table_struct["clients"]["chklng"][16]);
        unset($this->table_struct["clients"]["chklng"][18]);
        unset($this->table_struct["clients"]["chklng"][19]);
        unset($this->table_struct["clients"]["chklng"][20]);
        unset($this->table_struct["clients"]["chklng"][21]);
        unset($this->table_struct["clients"]["chklng"][23]);
        unset($this->table_struct["clients"]["chklng"][24]);
        unset($this->table_struct["clients"]["chklng"][25]);
        unset($this->table_struct["clients"]["chklng"][26]);
        $this->table_struct["clients"]["chklng"] = array_values($this->table_struct["clients"]["chklng"]);

        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][8]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][16]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][18]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][19]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][20]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][21]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][23]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][24]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][25]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]][26]);
        if (isset( $this->table_struct["clients"]["infos"][$_SESSION['languages'][1]]))
          $this->table_struct["clients"]["infos"][$_SESSION['languages'][1]] = array_values($this->table_struct["clients"]["infos"][$_SESSION['languages'][1]]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][8]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][16]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][18]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][19]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][20]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][21]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][23]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][24]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][25]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]][26]);
        if (isset( $this->table_struct["clients"]["infos"][$_SESSION['languages'][2]]))
          $this->table_struct["clients"]["infos"][$_SESSION['languages'][2]] = array_values($this->table_struct["clients"]["infos"][$_SESSION['languages'][2]]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][8]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][16]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][18]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][19]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][20]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][21]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][23]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][24]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][25]);
        unset($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]][26]);
        if (isset( $this->table_struct["clients"]["infos"][$_SESSION['languages'][3]]))
          $this->table_struct["clients"]["infos"][$_SESSION['languages'][3]] = array_values($this->table_struct["clients"]["infos"][$_SESSION['languages'][3]]);
      } 
      
         
      $this->table_sql = array(
        "admin_settings" => array("SELECT * FROM admin_settings", "", "name_adm,forname_adm"),  
        "menus" => array("SELECT * FROM menus", "", "CAST( sorting AS unsigned ),menu_title ASC, active$lng_ix DESC"),
        "page_menus" => array("SELECT * FROM page_menus", "SELECT page_menus.* FROM page_menus,menus where page_menus.page = menus.id_men", "menus.menu_title"),
        "page_layouts" => array("SELECT * FROM page_layouts", "", "layout_name"),
        "clients" => array("SELECT * FROM clients", "", "CAST(sorting AS unsigned), client_type, name, zip"),
        "clients_types" => array("SELECT * FROM clients_types ct", "", "CAST( ct.sorting AS unsigned ), ct.client_type$lng_ix"),
        "status" => array("SELECT * FROM status", "", "status$lng_ix,sorting"),
        "pictures" => array("SELECT p.*, m.menu_title FROM pictures p LEFT JOIN menus m ON p.menu_ref=m.id_men", "", "CAST( p.sorting AS unsigned ), m.menu_title, m.pic_name"),
        "news" => array("SELECT * FROM news", "", "CAST( sorting AS unsigned ), date desc"),
        "faqs" => array("SELECT * FROM faqs", "", "position"),
        "sponsors" => array("SELECT s.*, m.menu_title FROM sponsors s LEFT JOIN menus m ON s.menu_ref=m.id_men", "", "CAST( s.sorting AS unsigned ), s.sponsor_type desc, s.name"),
        "filelinks" => array("SELECT * FROM filelinks", "", "(SELECT menu_title FROM menus WHERE menus.id_men=filelinks.menu_ref),name,sorting"),
        "regions" => array("SELECT * FROM regions", "", $this->getFieldName("region")),
        "countries" => array("SELECT * FROM countries", "", $this->getFieldName("name")),
        "galleries" => array("SELECT g.*, m.menu_title FROM galleries g LEFT JOIN menus m ON g.menu_ref=m.id_men", "", "CAST( g.sorting AS unsigned ), g.menu_ref, g.pic_name"),
        "videos" => array("SELECT v.*, m.menu_title FROM videos v LEFT JOIN menus m ON v.menu_ref=m.id_men", "", "CAST( v.sorting AS unsigned ), v.menu_ref, v.vid_name"),
        "picts" => array("SELECT p.*, m.menu_title FROM picts p LEFT JOIN menus m ON p.menu_ref=m.id_men", "", "CAST( p.sorting AS unsigned ), p.menu_ref, p.active DESC, p.name"),
        "backgrounds" => array("SELECT b.*, m.menu_title FROM backgrounds b LEFT JOIN menus m ON b.menu_ref=m.id_men", "", "CAST( b.sorting AS unsigned ), m.menu_title, b.name"),
        "marquees" => array("SELECT ma.*, m.menu_title FROM marquees ma LEFT JOIN menus m ON ma.menu_ref=m.id_men", "", "CAST( ma.sorting AS unsigned ), ma.menu_ref, ma.name"),
        "calls" => array("SELECT * FROM calls", "", ""),
        "companies" => array("SELECT * FROM companies", "", "name"),
        "texts" => array("SELECT * FROM texts", "", "CAST( sorting AS unsigned ), active desc, name")
      );
      
      // use this if a given paths would have a vertical limitation
      $this->img_path_vertical_clamps = array(
      "" => "",
      "" => "");
      
      // check firstrun: when yes, create the directories (ALL) based on tables paths
      $firstrun = $this->dbquery_singleField("SELECT firstrun FROM admin_settings", "firstrun");
      if (!empty($firstrun)) {
        foreach($this->table_defs as $table) {
          if (!empty($table["paths"]["original"]))
            if (!file_exists($_SESSION['std_document_root']."/".$table["paths"]["original"]))
              mkdir($_SESSION['std_document_root']."/".$table["paths"]["original"]);
          if (!empty($table["paths"]["size1"]))
            if (!file_exists($_SESSION['std_document_root']."/".$table["paths"]["size1"]))
              mkdir($_SESSION['std_document_root']."/".$table["paths"]["size1"]);
          if (!empty($table["paths"]["size2"]))
            if (!file_exists($_SESSION['std_document_root']."/".$table["paths"]["size2"]))
              mkdir($_SESSION['std_document_root']."/".$table["paths"]["size2"]);
          if (!empty($table["paths"]["docs"]))
            if (!file_exists($_SESSION['std_document_root']."/".$table["paths"]["docs"]))
              mkdir($_SESSION['std_document_root']."/".$table["paths"]["docs"]);
        }
        // update first run to avoid create dirs later
        $id = $this->dbquery_singleField("SELECT id_adm FROM admin_settings", "id_adm");
        $this->dbquery("UPDATE admin_settings SET firstrun=0 WHERE id_adm=$id");
      }
      
  }
 
 /*
  This function returns an array from 1 up to 3 languages text strings depending on configs in web_params.php
  The passed $texts array contains always the three languages strings lng1 (en) lng2 (de) and lng3 (es)
  Returned is a 1 to 3 array
 */  
  function language($texts) {
    $return = array();
    if ($_SESSION['lng1'] == "on")
      $return[$_SESSION['languages'][1]]= $texts[$_SESSION['languages'][1]];
    if ($_SESSION['lng2'] == "on")
      $return[$_SESSION['languages'][2]]= $texts[$_SESSION['languages'][2]];
    if ($_SESSION['lng3'] == "on")
      $return[$_SESSION['languages'][3]]= $texts[$_SESSION['languages'][3]];
    return $return;
  }
      
  function db_setConnect($host,$user,$pswd) {
    $this->db_host = $host;
    $this->db_user = $user;
    $this->db_pass = $pass;
  }
  
  /*
     This function connect the user to the db
  */
  function db_connect() {
    $this->status = array('msg'=>"",'type'=>"info");
    $this->db_lnk = mysqli_connect($this->db_host, $this->db_user, $this->db_pass);
    if (mysqli_connect_errno()){
      $this->db_lnk = -1;
      $this->status = array('msg'=>$_SESSION['dbConnectFailed'][$_SESSION['lang']]." ".mysqli_connect_errno(),'type'=>"error");
    }
    mysqli_query($this->db_lnk,"SET NAMES utf8;");
    return empty($this->status['msg']);
  }

  function db_tren() {
    mysqli_close();
  }
      
  function get_var($varname){
    return $this->$varname;
  }
  
    /* old mysql_ functions that can't be directly converted in mysqli_ */
  function mysqli_db_query($db,$sql,$res) {
    $lnk = (isset($res)) ? $res : $this->db_lnk;
    if(mysqli_select_db( $res, $db))
     return mysqli_query($res,$sql);
    else
     return false;
  }
 
  function mysqli_result($res,$row=0,$col=0){ 
    $numrows = mysqli_num_rows($res); 
    if ($numrows && $row <= ($numrows-1) && $row >=0){
      if( !empty($fld) ) {
        mysqli_data_seek($res,$row);
        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
        if (isset($resrow[$col])){
          return $resrow[$col];
        }
      } else {
        $f = mysqli_fetch_array( $res );
        return $f[0];
      }
    }
    return false;
  }

  function mysqli_field_name($res,$ix=0) {
    $table_info = mysqli_fetch_field_direct($result, $ix);
//    $lenght = $table_info->length;
//    $name = $table_info->name;
//    $type = $table_info->type;
//    $flag = $table_info->flags;
//    $table = $table_info->table;
    return $table_info->name;
  }

  function mysqli_field_table($res,$ix=0) {
    $table_info = mysqli_fetch_field_direct($result, $ix);
    return $table_info->table;
  }



  /*
    This will return a simple list array (key, val) for the passed results array
    When a $results element has more tha two values, the first is used as key, the second as value and the other ignored
    When it has only one  value, this will aso used as key.
  */
  function makeList($results) {
    $keynames = array_keys($results[0]);
    $res = array();
    for($i=0;$i<sizeof($results);$i++) {
      if ($results[$i][$keynames[0]]!="") {
        if (sizeof($results[$i])==1)
          $res[$results[$i][$keynames[0]]] = $results[$i][$keynames[0]];
        else
          $res[$results[$i][$keynames[0]]] = $results[$i][$keynames[1]];
      }
    }
    return $res; 
  }
  
  /*
    Functions returns the content for the matching language:
    Passed: 3 strings, 1st is for first language (en)
                       2nd is for second language (de)
                       3rd is for third language (es)
  */
  function get_for_current_language($str_en, $str_de, $str_es){
    $result = "";
    switch ($_SESSION['lang']) {
      default:
      case $_SESSION['languages'][1]:  $result = $str_en; break;
      case $_SESSION['languages'][2]:  $result = $str_de; break;
      case $_SESSION['languages'][3]:  $result = $str_es; break;
    }
    return $result;
  }
  
  /*
  Returns the real name of the passed field base, depending of the language
  Per definition fieldbase1 for english, fieldbase2 for german and fielbase3 for spanish
  */
  /*
    Returns thr country_id for the passed string (language sensitive)
  */
  function getFieldName($str) {
    switch ($_SESSION['lang']) {
      default:
      case $_SESSION['languages'][1]:  return $str."1";
                  break;
      case $_SESSION['languages'][2]:  return $str."2";
                  break;
      case $_SESSION['languages'][3]:  return $str."3";
                  break;
    }
    return $str;
  }
  
/*
    Returns the language index (1,2 or 3) as integer
  */
  function getLanguageIdx() {
    return intval($this->getFieldName(""));
  }
  
/*
   Function to return a language normalized menu structure
*/  
  function get_norm_menu($menu_row){
    $result = array();
    foreach(array("id_men", "id_sub", "protected", "menu_title", "menu_link", "page_layout", "sorting") as $rowname) $result[$rowname] = $menu_row[$rowname];
    switch ($_SESSION['lang']) {
      default:
      case $_SESSION['languages'][1]:  foreach(array("active1", "menu_name1", "menu_description1") as $rowname) {
                    $normname = substr($rowname,0,strlen($rowname)-1);
                    $result[$normname] = $menu_row[$rowname];
                  }
                  break;
      case $_SESSION['languages'][2]:  foreach(array("active2", "menu_name2", "menu_description2") as $rowname) {
                    $normname = substr($rowname,0,strlen($rowname)-1);
                    $result[$normname] = $menu_row[$rowname];
                  }
                  break;
      case $_SESSION['languages'][3]:  foreach(array("active3", "menu_name3", "menu_description3") as $rowname) {
                    $normname = substr($rowname,0,strlen($rowname)-1);
                    $result[$normname] = $menu_row[$rowname];
                  }
                  break;
    }
    return $result;
  }

/*
  Get all the active pictures for the current pages
*/  
  function getPictures() {
  // Get matching pictures (when some)
  $wherePic = " WHERE active=1 AND menu_ref=".intval($_SESSION['pageID']);
  $sqlPic = "SELECT *,layout_name FROM pictures JOIN menus ON menus.id_men=pictures.menu_ref JOIN page_layouts ON page_layouts.id=menus.page_layout ".$wherePic. " ORDER BY pictures.sorting, pic_name";
  $this->pictures = $this->db_queryAll($sqlPic);
  }
  
/*
  Get all the active backgrounds for the current pages
*/  
  function getBackgrounds() {
  // Get matching pictures (when some)
  $wherePic = " WHERE active=1 AND menu_ref=".intval($_SESSION['pageID']);
  $sqlPic = "SELECT * FROM backgrounds JOIN menus ON menus.id_men=backgrounds.menu_ref ".$wherePic. " ORDER BY backgrounds.sorting, name";
  $this->backgrounds = $this->db_queryAll($sqlPic);
    if (empty($this->backgrounds)) {  // when none assigned to page, get the unassigned ones
      $sqlPic = "SELECT * FROM backgrounds WHERE active=1 and menu_ref=0 ORDER BY backgrounds.sorting, name";  
      $this->backgrounds = $this->db_queryAll($sqlPic);
    }
  }
  
/*
  Get active backgrounds for the home page (if some)
*/  
  function getHomeBackground() {
  // Get matching pictures (when some)
  $background = array();
  $wherePic = " WHERE active=1 AND menu_title LIKE 'home'";
  $sqlPic = "SELECT * FROM backgrounds JOIN menus ON menus.id_men=backgrounds.menu_ref ".$wherePic. " ORDER BY backgrounds.sorting, name";
  $background = $this->db_queryAll($sqlPic);
  return $background;
  }
  
/*
  Get all the active galleries for the current page
*/  
  function getGalleries() {
  // Get matching pictures (when some)
  $wherePic = " WHERE active=1 AND menu_ref=".intval($_SESSION['pageID']);
  $sqlPic = "SELECT * FROM galleries ".$wherePic. " ORDER BY sorting, pic_name";
  return $this->db_queryAll($sqlPic);
  }
  
/*
  Get all the active picts for the current page
*/  
  function getPicts() {
  // Get matching pictures (when some)
  $wherePic = " WHERE active=1 AND menu_ref=".intval($_SESSION['pageID']);
  $sqlPic = "SELECT * FROM picts ".$wherePic. " ORDER BY sorting, name";
  return $this->db_queryAll($sqlPic);
  }
  
  /*
    This functions returns the eventual diashow name (diashow or minidiashow) for the passed layout. When none, returns "" 
  */
  function getDiashowForLayout($layout) {
    $sqlcontent = $this->db_queryAll("SELECT layout_struct FROM page_layouts where layout_name LIKE '$layout'");
    $ret = "";
    if (sizeof($sqlcontent)>0) {
      if (stripos($sqlcontent[0]['layout_struct'],"minidiashow")!==false) 
        $ret = "minidiashow";
      else
      if (stripos($sqlcontent[0]['layout_struct'],"diashow")!==false) 
        $ret = "diashow";
    }
    return $ret;
  }

  function getLayoutID($name) {
    global $browser;
    if ($browser->isMobile())
      $temp = $this->db_query("SELECT id from page_layouts where layout_name LIKE '".$name."_mobile'", "id");
    if (empty($temp))
    $temp = $this->db_query("SELECT id from page_layouts where layout_name LIKE '".$name."'", "id");
    return $temp[0]['id'];
  }
  
  
  function getCopyright() {
    $temp = $this->db_query("SELECT copyright from admin_settings", "copyright");
    return $temp[0]['copyright'];
  }
  
  function getEmail() {
    $temp = $this->db_query("SELECT email from admin_settings", "email");
    if (!empty($temp))
      return $temp[0]['email'];
    else
      return "";
  }
  
  function getMetaInfo() {
    return $this->db_query("SELECT metadescr_adm, metakeywords_adm from admin_settings", "metadescr_adm, metakeywords_adm");
  }
  /*
  This function returns the bannerlogo as filename
  */
  function getOverlayPicture() {
    $fn = $this->db_query("SELECT ovl_pic from admin_settings", "ovl_pic");
    if (sizeof($fn)>0 && !empty($fn[0]['ovl_pic'])) {
      $str = $this->table_defs['admin_settings']['paths']['original'].$fn[0]['ovl_pic']; // get original version
    }
    else
      $str="";
    return $str;
  }
  
  /*
  This function returns the bannerlogo as <img class='bannerlogoimg' ...> field
  */
  function getBannerLogo($isMobile) {
    $field = ($isMobile) ? "bannerlogomob_adm" : "bannerlogo_adm";
    $fn = $this->db_query("SELECT $field from admin_settings", $field);
    if (sizeof($fn)>0 && !empty($fn[0][$field])) {
      if ($_SESSION['banner_to_home']=="on")
        $to_home = "onclick='javascript:window.location.href=\"index.php\";'";
      else
        $to_home = "";
      $str = "<img class='bannerlogoimg' src='".$this->table_defs['admin_settings']['paths']['original'].$fn[0][$field]."' $to_home />"; // get original version
    }
    else
      $str="";
    return $str;
  }
  
  /*
  This function returns the bannerlng as <img class='bannerlngimg' ...> field
  */
  function getBannerLanguage() {
    $fn = $this->db_query("SELECT bannerlng_adm from admin_settings", "bannerlng_adm");
    if (sizeof($fn)>0 && !empty($fn[0]['bannerlng_adm']))      
      $str = "<img class='bannerlngimg' src='".$this->table_defs['admin_settings']['paths']['original'].$fn[0]['bannerlng_adm']."' />"; // get original version
    else
      $str="";
    return $str;
  }
  
  /*
  This function returns the banner language select miniform as <div class='bannerlngselect' ...> field
  */
  function getBannerLanguageSelect() {
    $fn = $this->db_query("SELECT bannerlngsel_adm from admin_settings", "bannerlngsel_adm");
    $str = $fn[0]['bannerlngsel_adm']; // get original version
    $lngs = explode("[+][*][*][+]", $str);
    // select the good one
    $ret="";
    for ($i=0;$i<sizeof($lngs);$i++)
      if (strpos($lngs[$i],"\"selected\" value=\"".$_SESSION['lang']."\"")!==false) {
        $ret = $lngs[$i];
        break;  
      }
    if (empty($ret) && !empty($str)) {
      //when single form for all languages
      if (strpos($str,"<select")!==false) // When is a select input
        $ret = substr($str,0,strpos ($str,'"'.$_SESSION['lang'].'"')+4)." selected ".substr($str,strpos ($str,'"'.$_SESSION['lang'].'"')+4); // get current form and place selected on it
      else {
        if (strpos($str,"\"")!==false) // when has quotes, 
          $str = str_replace("&quot;","'",$str);
        $ret = $str;
      }
    }
    return $ret;
  }
  
  /*
    Returns the country_id for the passed string (language sensitive)
  */
  function getCountryID($str) {
    if (empty($str)) return "";
    $fn = $this->db_query("SELECT id from countries where ".$this->getFieldName("name")." LIKE ".$str, "id");
    return $fn[0]['id'];
  }
  
  /*
    Returns the country name for the passed id (language sensitive)
  */
  function getCountryName($str) {
    if (empty($str)) return "";
    $fn = $this->db_query("SELECT ".$this->getFieldName("name")." from countries where id = ".$str, $this->getFieldName("name"));
    return $fn[0][$this->getFieldName("name")];
  }
  
  function get_descr($table,$language){ //returns an array of fields with the descriptions of columns for passed table and language
    $names = array();
    $names = array_combine($this->table_struct[$table]['fields'],$this->table_struct[$table]['labels'][$language]);
    return $names;
  }
  
  /*
  This function returns the passed string after having removed all escaped chars
  */
  function cleanEscaped($src) {
   return str_replace(array("\t","\r","\n","\\"),"",$src); 
  }
  
  /*
    This function returns true when current date is in passed range. Possible formats: YYYY-MM-DD or MM-DD-YYYY
    When passed range "from" and "to" are 0000-00-00 or 00-00-0000 then, returns always true
    When only "from" is zero, returns true when current date <= "to"
    when only "to" is zero, returns true when current >= "from" 
  */
  function inDateRange($d_from, $d_to) {
    $from = (strpos($d_from,"-")==4) ? $d_from : substr($d_from,6,4)."-".substr($d_from,0,2)."-".substr($d_from,3,2);
    $to = (strpos($d_to,"-")==4) ? $d_to : substr($d_to,6,4)."-".substr($d_to,0,2)."-".substr($d_to,3,2);
    if (($from=="0000-00-00") && ($to=="0000-00-00" )) return true;
    if ($from=="0000-00-00") return strtotime(date("Y-m-d",time())) <= strtotime($to);
    if ($to=="0000-00-00") return strtotime(date("Y-m-d",time())) >= strtotime($from);
    return (strtotime(date("Y-m-d",time())) >= strtotime($from) && strtotime(date("Y-m-d",time())) <= strtotime($to));
  }
  
  /*
  This function returns the position of the first char in passed string, from passed position, which is not HTML tags or spaces
  */
  function skipHTMLtags($src,$pos) {
    $k= $pos;
    while ($k<strlen($src)) {
      if(!in_array($src[$k],array(' ','<','&'))) break;
      if ($src[$k]=='<') while ($src[$k]!='>') $k++;
      if ($src[$k]=='>') $k++;
      if ($src[$k]=='&') while ($src[$k]!=';') $k++;
      if ($src[$k]==';') $k++;
      if ($src[$k]==' ') $k++;
    }
   return $k; 
  }
  
  function dbquery($sql) {
    $db_selected = mysqli_select_db($this->db_lnk, $this->db);
    if (!$db_selected) {
      $this->status = array('msg'=>'Can\'t use '.$this->db.' : ' . mysqli_error($this->db_lnk),'type'=>"error");
      return $rows;
    }
    return mysqli_query($this->db_lnk, $sql);
  }
   
  function mysqldb_query($db, $sql) {
    $db_selected = mysqli_select_db($this->db_lnk, $db);
    if (!$db_selected) {
      $this->status = array('msg'=>'Can\'t use '.$this->db.' : ' . mysqli_error($this->db_lnk),'type'=>"error");
      return false;
    }
    return mysqli_query($this->db_lnk, $sql);
  }

  /*
  Returns the numbers of records that match the passed SQL query
  */
  function db_recCount($sql){
    $zeilen = array();//rückgabewert
    $num = 0;
    try {
      $res = $this->mysqldb_query($this->db, $sql);
      $num = mysqli_num_rows($res);
    }
    catch(Exception $e) {
      $this->status = array('msg'=>$e->getMessage(),'type'=>"error");
    }
    
    return $num;
  }

  /*
  Returns an array of columns values for the passed query using passed columns
  columns format may be a single string of columns' names separated by comma : "col1, col2, etc."
  or an array of strings representing the columns' names: array("col1","col2,etc.)
  */
  function db_query($sql, $columns){
    $zeilen = array();//rückgabewert
    if (!is_array($columns)) { // when simple string
      $tmpcols = str_replace(" ","",$columns);  // remove unwanted spaces
      $cols = explode(",", $tmpcols);
    }
    else
      $cols = $columns;
    $anz_spalten = count($cols);
    try {
      $res = $this->db_queryAll($sql,true);
      $num = sizeof($res);
      for ($i=0; $i<$num;$i++){  
        for ($j=0; $j<$anz_spalten;$j++){
          $zeilen[$i][$cols[$j]] = $res[$i][$cols[$j]];
        }
      }
    }
    catch(Exception $e) {
      $this->status = array('msg'=>$e->getMessage(),'type'=>"error");
    }
    
    return $zeilen;
  }
  // for coompatibility purpose
  function db_abfrage($sql, $spalten) {
    return $this->db_query($sql, $spalten);
  }

  
  /*
    This function returns an array of rows with an array of keys (collumns names and associated values.
  */
  function db_queryAll($sql,$fieldsNamesOnly=false){
    $rows = array();  //return array

    $db_selected = mysqli_select_db($this->db_lnk, $this->db);
    if (!$db_selected) {
      $this->status = array('msg'=>'Can\'t use '.$this->db.' : ' . mysqli_error($this->db_lnk),'type'=>"error");
      return $rows;
    }
    $res = mysqli_query($this->db_lnk,$sql);
    if ($res) {
      $i = 0;
      while ($row = mysqli_fetch_array($res)) {
        $tmp = array_keys($row);
        for ($j=0;$j<sizeof($tmp);$j++)
          if (!($fieldsNamesOnly && is_long($tmp[$j]))) 
          $row[$tmp[$j]] = trim($row[$tmp[$j]]);
          else
            unset($row[$tmp[$j]]);
        $rows[$i++] = $row;
      }
    }
    return $rows;
  }//end function db_queryAll
  
  function db_queryAllQualified($sql){
    $rows = array();  //return array
    $db_selected = mysqli_select_db($this->db_lnk, $this->db);
    if (!$db_selected) {
      $this->status = array('msg'=>'Can\'t use '.$this->db.' : ' . mysqli_error($this->db_lnk),'type'=>"error");
      return $rows;
    }
    $res = mysqli_query($this->db_lnk, $sql);
    $qualified_names = array();
    if ($res) {
      for ($i = 0; $i < mysqli_num_fields($res); ++$i) {
          $table = $this->mysqli_field_table($res, $i);
          $field = $this->mysqli_field_name($res, $i);
          array_push($qualified_names, "$table.$field");
      }
      $i = 0;
      while ($row = mysqli_fetch_row($res)) {
        $row = array_combine($qualified_names, $row);
        $rows[$i] = $row;
        $i++;
      }
    }
    return $rows;
  }//end function db_queryAll
  
  // this will execute the sql statement and return the first row as an array with colnames as keys
  function dbquery_firstrow($sql) {
    $res = $this->db_queryAll($sql);
    return $res[0];
  }
  
  // this will execute the sql statement and return the first row as an array with colnames as keys
  function dbquery_singleField($sql,$field) {
    $res = $this->db_queryAll($sql);
    if (!empty($res))
      return $res[0][$field];
    else
      return "";
  }
  
  function db_uid_psw_change($rec_id,$user_id,$old,$new,$isF,$table) {
    $pw_fld = ($isF) ? "fpwd" : "upwd";
    $sql = "update $table set $pw_fld='$new' where id=$rec_id and $pw_fld='$old'";
    $res = $this->mysqldb_query($this->db, $sql);
    if ($res===true)
      return true;
    else
      return false;
  }

/*
    This function returns an array of rows with an array of keys (collumns names and associated values when login passed, else empty.
  */
  function db_uid_login($userid,$pwd,$pwd_name,$table){
    $rows = array();  //return array
    $mysqli = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db);
    if (!$mysqli) {
      $this->status = array('msg'=>'Can\'t use '.$this->db.' : ' . mysqli_error($this->db_lnk),'type'=>"error");
      return $rows;
    }
//jGC: added $company_id
// Using prepared statements means that SQL injection is not possible. 
    if ($stmt = $mysqli->prepare("SELECT * FROM $table WHERE uid = ?;")) {
      $stmt->bind_param('s', $userid);  // Bind "$email" to parameter.
      $stmt->execute();    // Execute the prepared query.
      $stmt->store_result();
      // get variables from result.
      $stmt->bind_result($id, $datestamp, $sorting, $name, $name2, $address, $pobox, $zip, $city, $phone, $fax,
                         $uid, $upwd, $fpwd, $client_type, $email, $email2, $logo, $link, $vat, $opentime, 
                         $zeevintern, $partner_cond1, $partner_cond2, $cnt_deposits, $pw1, $pw2,$last_login);
      $stmt->fetch();
      $num_rows=$stmt->num_rows();
      $mysqli->close($stmt);
      if ($stmt->num_rows >= 1) {
        // Check if the password in the database matches
        // the password the user submitted.
        $cryptkey = md5(md5(date("Y-m-d")));
        $result = array();
        for($cnt=0;$cnt<$num_rows;$cnt++) {
          if (strtolower($pwd_name[0])=="f")
            $test = base64_encode($cryptkey.$fpwd);
          else
            $test = base64_encode($cryptkey.$upwd);
          if ($test == $pwd) {
          // Password is correct!
            $result = array(id=>$id, datestamp=>$datestamp, sorting=>$sorting, name=>$name, name2=>$name2, address=>$address, pobox=>$pobox, zip=>$zip, city=>$city, phone=>$phone, fax=>$fax, 
                            uid=>$uid, pwd=>$upwd, fpwd=>$fpwd, client_type=>$client_type, email=>$email, email2=>$email2, logo=>$logo, link=>$link, vat=>$vat, opentime=>$opentime, 
                            zeevintern=>$zeevintern, partner_cond1=>$partner_cond1, partner_cond2=>$partner_cond2, cnt_deposits=>$cnt_deposits, last_login=>$last_login);
          }
          $stmt->fetch();
        }
        $rows = $result;
      }
    }
    return $rows;
  }//end function db_uid_login
  
  /* function db_queryLng
  Returns an array of columns values for the passed query using passed columns
  columns format may be a single string of columns' names separated by comma : "col1, col2, etc."
  or an array of strings representing the columns' names: array("col1","col2,etc.)
  The passed parameter $lngFields contains an array of fields that are language specific. The function getFieldName($str)
  is used to set the $lngFields to the matching real field name value (a 1,2 or 3 is append to the original string).
  This string is used to replace the languageless field in the sql query string.
  */
  function db_queryLng($sql,$columns,$lngFields) {
    $tmpsql = $sql;
    $realFields = $lngFields;
    // first, we get the result in english
    for ($i=0;$i<sizeof($realFields);$i++)
      $realFields[$i]=$realFields[$i]."1";
    //replace columns languageless field's name with real name
    if (!is_array($columns)) // when simple string
      $cols = explode(",", $columns);
    else
      $cols = $columns;
    for ($i=0;$i<sizeof($cols);$i++)
      for ($j=0;$j<sizeof($lngFields);$j++)
        $cols[$i]=str_ireplace($lngFields[$j],$realFields[$j],$cols[$i]);
    //replace sql languageless field's name with real name
    for ($i=0;$i<sizeof($lngFields);$i++) {
      $tmpsql = str_ireplace($lngFields[$i],$realFields[$i],$tmpsql);
    }
    $result1 = $this->db_query($tmpsql,$cols);
    
    // now get for the current language (when not english)
    if ($_SESSION['lang']!="en") {
      $tmpsql = $sql;
      $realFields = $lngFields;
      for ($i=0;$i<sizeof($realFields);$i++)
        $realFields[$i]=$this->getFieldName($realFields[$i]);
      if (!is_array($columns)) // when simple string
        $cols = explode(",", $columns);
      else
        $cols = $columns;
      //replace columns languageless field's name with real name
      for ($i=0;$i<sizeof($cols);$i++)
        for ($j=0;$j<sizeof($lngFields);$j++)
          $cols[$i]=str_ireplace($lngFields[$j],$realFields[$j],$cols[$i]);
      //replace sql languageless field's name with real name
      for ($i=0;$i<sizeof($lngFields);$i++) {
        $tmpsql = str_ireplace($lngFields[$i],$realFields[$i],$tmpsql);
      }
      $result2 = $this->db_query($tmpsql,$cols);
      // verify that all response cols are filled, else use english results instead
      for ($i=0; $i<sizeof($result2);$i++) {
        foreach($cols as $c)
          if ($result2[$i] [$c] == "") {
            $result2 = $result1;
            break(2);
          }
      }
      return $result2;
    }
    return $result1;
  }
  
  /* to check if passed value is a md5 encoded string */
  function isValidMd5($md5 ='') {
    return strlen($md5) == 32 && ctype_xdigit($md5);
  }
  /* returns the encrypted value
  */
  function pwd_encrypt($value1,$value2) {
    /* Encrypt data */
    $enc = trim($value1).trim($value2);
    $encrypted = md5(md5(base64_encode($enc)));
    return $encrypted;
  }
  
  /*
    This function insert a new record in the passed table.
    It uses the passed columns and the passed values.
  */
  function insert($table, $columns, $values){
    $col_cnt = count($columns);
    if ($table=="clients") {  // special for clients: on insert, encrypt password
      if (array_search("upwd",$columns)!==false) { // when has a password column
        if (!(strlen($values[array_search("upwd",$columns)])==32 && ctype_xdigit($values[array_search("upwd",$columns)])))
        $values[array_search("upwd",$columns)] = $this->pwd_encrypt($values[array_search("uid",$columns)],$values[array_search("upwd",$columns)]);  
    }
      if (array_search("fpwd",$columns)!==false) { // when has a password column
        if (!(strlen($values[array_search("fpwd",$columns)])==32 && ctype_xdigit($values[array_search("fpwd",$columns)])))
          $values[array_search("fpwd",$columns)] = $this->pwd_encrypt($values[array_search("uid",$columns)],$values[array_search("fpwd",$columns)]);  
      }
    }
    for($i=1;$i< $col_cnt;$i++){
      if (isset($values[$i])) { // only when value exists
        if ($columns[$i]=="datestamp" && is_string($values[$i]) && $values[$i]=="datestamp") {
          $values[$i] = date("Y-m-d H:i:s");
        } 
        if (is_string($values[$i]))
          $value = (strpos($values[$i],'"')!==false) ? "'".$values[$i]."'" : '"'.$values[$i].'"'; // surrond with single or double quote (depends on content)
        else
          $value = $values[$i];
        if($i==$col_cnt-1){ // special for the last column: don't add , separator
          $insert_cols .="`".$columns[$i]."`";
          $insert_values .= $value;
        }else{
          $insert_cols .="`".$columns[$i]."`, ";
          $insert_values .= $value.", ";
        }
      }
    }
    // be sure cols and values to insert are not ending with , separator
    if ($insert_cols[strlen(trim($insert_cols))-1]==",") $insert_cols = substr($insert_cols,0,strlen(trim($insert_cols))-1);
    if ($insert_values[strlen(trim($insert_values))-1]==",") $insert_values = substr($insert_values,0,strlen(trim($insert_values))-1);
    $sql = "INSERT INTO `".$table."` (".$insert_cols.") VALUES (".$insert_values.")";
    $res = $this->mysqldb_query($this->db, $sql);
    if($res == 1){ 
      return array('msg'=>$_SESSION['RecSaved'][$_SESSION['lang']],'type'=>"info");
    }else {
      if (mysqli_errno($this->db_lnk)==1062) //  Duplicate
        return array('msg'=>$_SESSION['DupRec'][$_SESSION['lang']],'type'=>"error");
      else
        return array('msg'=>$_SESSION['RecNotSaved'][$_SESSION['lang']],'type'=>"error");
    }
  }
  
  /*
    This function update a record passed by id in the passed table.
    It uses the passed columns and the passed values.
  */
  function update($table, $columns, $values, $id){
    $col_cnt= count($columns);
    if ($table=="clients") {  // special for clients: on insert, encrypt password
      if (array_search("upwd",$columns)!==false) { // when has a password column
        if (!(strlen($values[array_search("upwd",$columns)])==32 && ctype_xdigit($values[array_search("upwd",$columns)])))
          $values[array_search("upwd",$columns)] = $this->pwd_encrypt($values[array_search("uid",$columns)],$values[array_search("upwd",$columns)]);  
      }
      if (array_search("fpwd",$columns)!==false) { // when has a password column
        if (!(strlen($values[array_search("fpwd",$columns)])==32 && ctype_xdigit($values[array_search("fpwd",$columns)])))
          $values[array_search("fpwd",$columns)] = $this->pwd_encrypt($values[array_search("uid",$columns)],$values[array_search("fpwd",$columns)]);  
      }
    }
    if ($table=="clients") {  // special for clients: on update, encrypt eventually not encrypted password (new)
      $col_pwd = array_search("upwd",$columns);
      if ($col_pwd !== false) {
        $pwd = $values[$col_pwd];
        if ($this->isValidMd5($pwd)===false) {
          $pwd = $this->pwd_encrypt($values[array_search("uid",$columns)],$pwd);
          $values[$col_pwd] = $pwd; 
        }
      }
    }
    for($i=1;$i<$col_cnt;$i++){
      if (isset($values[$i])) { // only when value exists
        $value = (empty($values[$i]) && $columns[$i]=="last_login") ? "skip" : "";
        if ($value!="skip") {
          if (is_string($values[$i]))
            if ($values[$i]==$columns[$i] && $columns[$i]=="datestamp")
              $value = "CURRENT_TIMESTAMP";
            else { 
              $value = $values[$i];
              if (strpos($value,"'")!==false)  // when containing ' , replace with HTML &#39;
                $value = str_replace( "'", "&#39;", $value);
              $value = "'".$value."'";
//              $value = (strpos($values[$i],'"')!==false) ? "'".$values[$i]."'" : '"'.$values[$i].'"'; // surrond with single or double quote (depends on content)
            }
          else
            $value = $values[$i];
          if($i==($col_cnt-1)){ // special for the last column: don't add , separator
            $update_set .="`".$columns[$i]."`=".$value."";
          }else{
            $update_set  .="`".$columns[$i]."`=".$value.", ";
          }
        }
      }
    }
    // be sure cols and values to insert are not ending with , separator
    if ($update_set[strlen(trim($update_set))-1]==",") $update_set = substr($update_set,0,strlen(trim($update_set))-1);
    $id_col = array_search("id",$columns);
    if ($id_col===false) $id_col=0; //default when column name "id" not found
    $sql = "UPDATE `".$table."` SET ".$update_set." WHERE ".$columns[$id_col]."='".$id."';";       // we assume, first col is always a unique index
    $res = $this->mysqldb_query($this->db, $sql);
    if($res == 1){ 
      return array('msg'=>$_SESSION['RecSaved'][$_SESSION['lang']],'type'=>"info");
    }else { 
      if (mysqli_errno($this->db_lnk)==1062) //  Duplicate
        return array('msg'=>$_SESSION['DupRec'][$_SESSION['lang']],'type'=>"error");
      else
        return array('msg'=>$_SESSION['RecNotSaved'][$_SESSION['lang']],'type'=>"error");
    }
  }
  
  /*
    This function delete the record passed by id and a fieldname in the passed table.
  */
  function delete($table, $id, $id_fieldname, $fieldsTypes){
    $pos=0;
    foreach($fieldsTypes as $fieldType) {
      if ($fieldType=="file" || $fieldType=="bild" || $fieldType=="multifile") {
        $name = $this->table_struct[$table]['fields'][$pos];
        $elem = $this->dbquery_singleField("SELECT $name from $table where id=$id",$name);
        $filename = $_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$table]['paths']['original'].$elem;
        unlink($filename);  
        $filename = $_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$table]['paths']['size1'].$elem;
        unlink($filename);
        $filename = $_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$table]['paths']['size2'].$elem;  
        unlink($filename);
      }
      $pos++;
    }
    $sql = "DELETE FROM `".$table."` WHERE ".$id_fieldname."='".$id."';";
    $res = $this->mysqldb_query($this->db, $sql);
    if($res == 1){ 
      return array('msg'=>$_SESSION['RecDeleted'][$_SESSION['lang']],'type'=>"info");
    }else { 
      return array('msg'=>$_SESSION['RecNotDeleted'][$_SESSION['lang']],'type'=>"error");
    }
  }
  
 
  /*
    This function checks a given table for the action to be in the forbidden specs
    When the table does not exists, the return is always true (forbidden)
    else, if action is found in forbidden specs, it returns true otherwise false
    The passed $display string is outputed when not empty
  */
  function table_forbidden_action($table, $action, $display){
    if(isset($this->table_defs[$table])) { // when table exists
      if (stristr($this->table_defs[$table]['forbidden'], $action)==false) { // when action not found in vorbidden def
        if (!empty($display)) echo $display;  //when something to dispaly, do it!
        return false;  
      }
      else {  // action found
        if (!empty($display)) echo $display;  //when something to dispaly, do it!
        return true;  
      }  
    }
    else { // when not exists, echo display (when not empty) and returns true (forbidden)
      if (!empty($display)) echo $display;  //when something to dispaly, do it!
      return true;  
    }
  }
 
  /*
    This function returns the values as an array filled with the current POST variable matching the $columns array and their corresponding types
  */
   function values($columns, $types){
    for($i=1;$i<count($columns);$i++){
      if (isset($columns[$i])) {
      if(substr($types[$i], 0, 4) == "file" || substr($types[$i], 0, 4) == "bild"){ // when a file or a picture
        if(($_POST[$columns[$i]."_delete"] == "on") || ($_POST['delete']=="delete")){  // when marked as delete
          $values[$i] = "";
          $ext = strtolower(strrchr( $_POST[$columns[$i]."_inhalt"] , "."));
          if(!in_array($ext,array(".jpg",".jpeg",".png",".gif"))){  
            // when not an image file
            if($this->table_defs[$_POST['table']]['paths']['original'] != "" && !empty($_POST[$columns[$i]."_inhalt"])){
              // remove eventual original
              unlink($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['original'].$_POST[$columns[$i]."_inhalt"]);
            }
            if($this->table_defs[$_POST['table']]['paths']['docs'] != "" && !empty($_POST[$columns[$i]."_inhalt"])){
              // remove eventual docs
              unlink($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['docs'].$_POST[$columns[$i]."_inhalt"]);
            }
          }
          else {
            // it is an image file
            if($this->table_defs[$_POST['table']]['paths']['original'] != ""){
            // remove eventual original
              unlink($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['original'].$_POST[$columns[$i]."_inhalt"]);
            }
            if($this->table_defs[$_POST['table']]['paths']['width1'] != false){
            // remove eventual size1
              unlink($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['size1'].$_POST[$columns[$i]."_inhalt"]);
            }
            if($this->table_defs[$_POST['table']]['paths']['width2'] != false){
            // remove eventual size2
              unlink($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['size2'].$_POST[$columns[$i]."_inhalt"]);
            }
          }
        } // end of delete
        else {
          //NEW UPLOAD: the uploaded file IS already in original path
          if (!empty($_SESSION['files'])) {
            $_FILES[$columns[$i]] = $_SESSION['files'][$columns[$i]];
          }
          if(strlen($_FILES[$columns[$i]]['name']) > 4){  //File uploaded? minimum filename size, assumind one char in . extension
            // File upload
            if ($_FILES[$columns[$i]]['type']== "application/octet-stream") {
              $ext = strtolower(strrchr( $_FILES[$columns[$i]]['name'] , "."));
              switch ($ext) {
                case ".jpg": $_FILES[$columns[$i]]['type'] = "image/jpeg"; break;
                case ".jpeg": $_FILES[$columns[$i]]['type'] = "image/pjpeg"; break;
                case ".gif": $_FILES[$columns[$i]]['type'] = "image/gif"; break;
                case ".png": $_FILES[$columns[$i]]['type'] = "image/png"; break;
              }  
            }
            if($_FILES[$columns[$i]]['type'] != "image/jpeg" && $_FILES[$columns[$i]]['type'] != "image/pjpeg" && $_FILES[$columns[$i]]['type'] != "image/gif" && $_FILES[$columns[$i]]['type'] != "image/png" ){      
              // when not an image file
              $uploadPath = $this->table_defs[$_POST['table']]['paths']['docs'];
              if (empty($uploadPath))  // when has no doc folder, place in original
                $uploadPath = $this->table_defs[$_POST['table']]['paths']['original'];
              if(!empty($uploadPath) && ($uploadPath!=$this->table_defs[$_POST['table']]['paths']['original'])){ //only when can be saved somewhere !
                // move from origal to wanted folder
                rename($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['original'].$_FILES[$columns[$i]]['name'],
                       $_SESSION['DOCUMENT_ROOT']."/".$uploadPath.$_FILES[$columns[$i]]['name']);
              }
            }
            else {
              // it is an image file: MUST have an original path AND IS IN ORIGINAL PAT: upload function no more used
              if($this->table_defs[$_POST['table']]['paths']['original'] != ""){
                // upload original
//                $this->upload($_FILES[$columns[$i]]['tmp_name'], $_FILES[$columns[$i]]['name'], $this->table_defs[$_POST['table']]['paths']['original'].$_FILES[$columns[$i]]['name'], $_FILES[$columns[$i]]['type']);
                // ATTENTION: can only resize when has an original
                if($this->table_defs[$_POST['table']]['paths']['width1'] != false){
                  // resize eventual size1
                  $this->bild_resize($this->table_defs[$_POST['table']]['paths']['original'], $_FILES[$columns[$i]]['name'], $this->table_defs[$_POST['table']]['paths']['size1'], $this->table_defs[$_POST['table']]['paths']['width1'], $_FILES[$columns[$i]]['type']);
                }
                if($this->table_defs[$_POST['table']]['paths']['width2'] != false){
                  // resize eventual size2
                  $this->bild_resize($this->table_defs[$_POST['table']]['paths']['original'], $_FILES[$columns[$i]]['name'], $this->table_defs[$_POST['table']]['paths']['size2'], $this->table_defs[$_POST['table']]['paths']['width2'], $_FILES[$columns[$i]]['type']);
                }
              }
              if ($_FILES[$columns[$i]]['type'] && $this->table_defs[$_POST['table']]['paths']['docs'] != "") {
                rename($_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$_POST['table']]['paths']['original'].$_FILES[$columns[$i]]['name'],
                       $_SESSION['DOCUMENT_ROOT']."/".$uploadPath.$_FILES[$columns[$i]]['name']);
              }
            }
            $values[$i]= $_FILES[$columns[$i]]['name'];
          }else { // when too small filename, empty name in db
            if (!empty($_FILES[$columns[$i]]['name']))
              $values[$i]= "";
          }
        } // end of file not marked for deletion
      } // end of file or picture
      else if($types[$i] == "checkbox"){   // when checkbox
        if($_POST[$columns[$i]] == "on"){
          $values[$i]= "1";
        }else{
          $values[$i]= "0";
        }
      }else if($types[$i] == "date"){  // when date
        $values[$i]= $this->format_date($_POST[$columns[$i]], "Y-m-d");
      }else if($types[$i] == "zeit"){   // when time
        $values[$i]= $_POST['stunde'].":".$_POST['minute'].":00";
      }else if($types[$i] == "hidden"){ // when hidden, only place column name in db
        $values[$i]= $columns[$i];
      }else if($types[$i] == "timestamp" || $types[$i] == "id"){ // when timestamp, supress values element: done automatically by db
        unset($values[$i]);
      }else if($types[$i] == "datestamp"){ // when datestamp, set value to datestamp update and insert will correctly handle it
        $values[$i] = "datestamp";
      }else if(substr($types[$i], 0, 4) == "wert" ){  // when value
        $values[$i] = substr($types[$i], 4);          // only place 4 first chars of type in db
      }else if(is_array($types[$i]) == true){ // when an Array
        $values[$i] = $types[$i][$_POST[$columns[$i]]][1];
      }else{ // for all other types, save as string between double quotes but (NEW 24 Sep 2013-GC) before, replace " by html euivalent &quot; and ' by 
        $values[$i] = $_POST[$columns[$i]];
        $values[$i] = trim($this->cleanEscaped($values[$i]));
        if ($types[$i]=="textarea" || $types[$i]=="textareaktml"){
          if (substr($values[$i],-13,13)=="<p>&nbsp;</p>")
            $values[$i] = str_replace("<p>&nbsp;</p>","",$values[$i]);
        } else {
          if (strpos($values[$i],'"')!==false)  // when containing " , replace with HTML &quot;
            $values[$i] = str_replace( '"', "&quot;", $values[$i]);
          if (strpos($values[$i],"'")!==false)  // when containing ' , replace with HTML &#39;
            $values[$i] = str_replace( "'", "&#39;", $values[$i]);
        }
      }
    }
    }
    $_SESSION['files']="";
    return $values;
  }
  
  /* Update Database based on a form
  */
  function DBupdate($form) {
    $values = $_SESSION[$form];
    $types = $_SESSION['forms'][$form];
    $table = $values['DBtable'];
    if (empty($table)) return "";
    // special: for calls, change time if this has been changed
    if ($table=="calls" && !empty($_POST['timeDisplay'])) {
      $oriTime = date("H:i", strtotime($_POST['timestamp']));
      if ($oriTime!=$_POST['timeDisplay'])
        $values['timestamp'] = str_replace($oriTime,$_POST['timeDisplay'],$values['timestamp']);  
    }
    if ($table=="calls" && !empty($_POST['editor']) && empty($values['userstamp'])) {
      $values['userstamp'] = $_SESSION['login_user']['id'];    
    }
    $DBrecid = $values['DBrecid'];
    $DBwhere = $values['DBwhere'];
    $recid = explode(",",$DBrecid);
    $sel_id_name = $recid[0];
    $sel_id_val = $values[$sel_id_name];
    if(!empty($sel_id_val)){
    $where = $sel_id_name."=".$sel_id_val." ".$DBwhere;
      if (isset($_POST['delete'])) { // want delete
        $sql = "DELETE FROM $table WHERE $where;";
      }
      else { // want update
        $sql = "UPDATE $table SET ";
        if ($table=="calls" && isset($_POST['is_archived']))
          if ($_POST['is_archived']=="1" && $_POST['status']!=2)  //when other than erledigt
            $values['is_archived']="0";
        if (isset($_POST['delete_upload'])) {
          $keyIdx = array_keys($this->table_struct[$table][types],"file");
          $uid = $values['id'];
          $filename = $this->dbquery_singleField("SELECT * FROM clients WHERE id=$uid;",$this->table_struct[$table][fields][$keyIdx[0]]);
          unlink($this->table_defs[$table]['paths']['original'].$uid."_".$filename);
          $values[$this->table_struct[$table][fields][$keyIdx[0]]] = "";
        }
        foreach ($types as $type) {
          $item = explode(";",$type);
          // look a little to the kind of data for some special purposes
          if (in_array($item[0],$this->table_struct[$table][fields])) {
            $keyIdx = array_keys($this->table_struct[$table][fields],$item[0]);
            $oriType = $this->table_struct[$table][types][$keyIdx[0]];
            if ($oriType=="file") {
              if (!empty($_FILES) && !empty($_FILES[$item[0]]['name'])) {
                $values[$item[0]] = $_FILES[$item[0]]['name'];
                // file has UID as leading
                copy($_FILES[$item[0]]['tmp_name'],$this->table_defs[$table]['paths']['original'].$values['id']."_".$_FILES[$item[0]]['name']);
              }
              else
              if (!isset($_POST['delete_upload'])) {
                $item[0]="";
                $items[1]="";
              }
            }
            if ($oriType!=$item[1]) { // handle special cases (reformat values)
              if ($oriType=="time") {
                $myVal = $values[$item[0]];
                if (strpos($myVal,":")===false && strpos($myVal,".")===false && is_numeric($myVal) && $myVal>=0 && $myVal<24) // no : no . and is numeric smaller then 24, greater-same 0
                  $values[$item[0]] = "$myVal:00";
                else  
                if (strpos($myVal,":")===false && strpos($myVal,".")!==false) { // no : but .
                  $parts = explode(".",$myVal); //separates units and decimal part
                  $mins = "0.".$parts[1];
                  $mins = $mins*60;
                  $mins = round($mins,0);
                  $values[$item[0]] = $parts[0].":".$mins;
                }
                else { // all other:
                  if (strtotime($myVal)===false) $myVal = "";
                }
              }
            }
          }
          
          // special for table clients: check if field is hidden and named upwd or fpwd. If yes, unhide.
          if (!empty($item[1]) && in_array($item[0],$this->table_struct[$table][fields]) && $item[1]=="hidden" && ($item[0]=="upwd" || $item[0]=="fpwd" || $item[0]=="userstamp" || $item[0]=="is_archived")) 
            $item[1] = "text";
          if (!empty($item[1]) && in_array($item[0],$this->table_struct[$table][fields]) && !($item[1]=="hidden" || $item[1]=="submit")) {
            $value = $values[$item[0]];
            if ($item[1]=="text" || $item[1]=="textarea") {
              if (strpos($value,"'")!==false)  // when containing ' , replace with HTML &#39;
                $value = str_replace( "'", "&#39;", $value);
            }
            $sql .= $item[0]."=".(($item[1]=="text" || $item[1]=="textarea" || $item[1]=="checkbox" || $item[1]=="file") ? "'".$value."'," : $value.",");
          }
        }
        // add evtl datestamp
        if ($_SESSION['has_datestamp']=="yes") {
          $sql .= "datestamp=CURRENT_TIMESTAMP,";
        }
        // must remove last ,
        $sql = substr($sql,0,strlen($sql)-1);
        $sql .= " WHERE $where";
      }
    }
    else {
      $sql = "INSERT INTO $table (";
      foreach ($types as &$type) {
        $item = explode(";",$type);
        // special for table clients: check if field is hidden and named upwd or fpwd or activ. If yes, unhide.
        if (!empty($item[1]) && in_array($item[0],$this->table_struct[$table][fields]) && $item[1]=="hidden" && ($item[0]=="upwd" || $item[0]=="fpwd" || $item[0]=="activ" || $item[0]=="userstamp" || $item[0]=="ticket")) {
          $item[1] = "text";
          $type = $item[0].";".$item[1];
        }
        if (!empty($item[1]) && in_array($item[0],$this->table_struct[$table][fields]) && !($item[1]=="hidden" || $item[1]=="submit"))
          $sql .= $item[0].",";
      }
      // special: for clients, add company
      if ($table=="clients") {
        $sql .= "company_id,";
      }
      // add evtl datestamp
      if ($_SESSION['has_datestamp']=="yes") {
        $sql .= "datestamp,";
      }
      // must remove last ,
      $sql = substr($sql,0,strlen($sql)-1);      
      $sql .= ") VALUES (";
      foreach ($types as $atype) {
        $item = explode(";",$atype);
        if (!empty($item[1]) && in_array($item[0],$this->table_struct[$table][fields]) && !($item[1]=="hidden" || $item[1]=="submit")) {
          if ($table=="calls" && $item[0]=="ticket" &&  $values[$item[0]]=="id")
            $sql .= "(SELECT auto_increment FROM information_schema.tables WHERE table_schema = '".$this->db."' AND table_name = 'calls'),";
          else {
            $value = $values[$item[0]];
            if ($item[1]=="text" || $item[1]=="textarea") {
              if (strpos($value,"'")!==false)  // when containing ' , replace with HTML &#39;
                $value = str_replace( "'", "&#39;", $value);
            }
            $sql .= (($item[1]=="text" || $item[1]=="textarea" || $item[1]=="checkbox" || $item[1]=="file") ? "'".$value."'," : $value.",");
          }
        }
      }
      // special: for clients, add company
      if ($table=="clients") {
        $sql .=  "'".$_SESSION['login_user']['company_id']."',";
      }
      // add evtl datestamp
      if ($_SESSION['has_datestamp']=="yes") {
        $sql .= "CURRENT_TIMESTAMP,";
      }
      // must remove last ,
      $sql = substr($sql,0,strlen($sql)-1);      
      $sql .= ")";
    }
    $rec = $this->db_queryAll($sql);
    if (mysqli_errno($this->db_lnk)!=0 && $log) {
      file_put_contents("docs/log.txt", mysqli_errno($this->db_lnk)." -> ".mysqli_error($this->db_lnk));  
    }
    if (isset($_POST['delete'])){
      if (mysqli_errno($this->db_lnk)==0)
        $_GET['msg']=$_SESSION['RecDeleted'][$_SESSION['lang']];
      else
        $_GET['msg']=$_SESSION['RecNotDeleted'][$_SESSION['lang']];
    }
    else {
    if (mysqli_errno($this->db_lnk)==0)
      $_GET['msg']=$_SESSION['RecSaved'][$_SESSION['lang']];
    else
        $_GET['msg']=$_SESSION['RecNotSaved'][$_SESSION['lang']];
    }
    if ($table=="calls" && empty($values['is_archived'])) {
      $this->last_ins_call = mysqli_insert_id($this->db_lnk);
    }
  }

  /*
    This function will copy the passed uploaded (via POST)) file as passed file_name into passed server path
    ATTENTION, path MUST be R/W enabled !!!
    In case of problems, the db_handler class variable $status will be filled up
  */
  function upload($file, $file_name, $serverpfad, $dateityp) {
    if (copy($file, $_SESSION['DOCUMENT_ROOT']."/".$serverpfad)) 
      $this->status = array('msg'=>$_SESSION['ftptransfertOK'][$_SESSION['lang']],'type'=>"info");
    else  
      $this->status = array('msg'=>$_SESSION['ftptransfertNOK'][$_SESSION['lang']],'type'=>"info");  
  }
  
  /*
    This function returns resize the passed original picture to the wanted image_width proportionally
    and save it into the passed image_path.
    The function supports the three formats: jpg, gif and png.
  */
  function bild_resize($original_img_path, $image_name, $resize_path, $image_width, $image_type){
    ini_set('memory_limit', '128M');

    if($image_type == "image/gif"){
      $i_img = @imagecreatefromgif($_SESSION['DOCUMENT_ROOT']."/".$original_img_path.$image_name);
    }else if($image_type == "image/jpeg" || $image_type == "image/pjpeg"){
      $i_img = @imagecreatefromjpeg($_SESSION['DOCUMENT_ROOT']."/".$original_img_path.$image_name);
    }else if($image_type == "image/png"){
      $i_img = @imagecreatefrompng($_SESSION['DOCUMENT_ROOT']."/".$original_img_path.$image_name);
    }
    $i_imgheight = imagesy($i_img);
    $i_imgwidth= imagesx($i_img);
    $t_dimension = $i_imgheight*$i_imgwidth;
    // new image width and height
    $t_imgwidth =  $image_width;
    $t_imgheight = $i_imgheight/($i_imgwidth/$t_imgwidth);
    
    if(!empty($this->img_path_vertical_clamps[$resize_path])) {
      if($t_imgheight > $this->img_path_vertical_clamps[$resize_path]){
        $t_imgheight = $this->img_path_vertical_clamps[$resize_path];
        $dim = $i_imgheight/($i_imgwidth/$t_imgwidth) / $t_imgheight;
        $t_imgwidth = $t_imgwidth / $dim;
      }
    }
    $thumb = imagecreatetruecolor($t_imgwidth, $t_imgheight);
    $transp = imagecolortransparent($i_img);
    imagecopyresampled($thumb, $i_img, 0, 0, 0, 0, $t_imgwidth, $t_imgheight, $i_imgwidth, $i_imgheight);
    if ($transp!=-1) {  // when has transparent
      imagecolortransparent($thumb, $transp);  
    }
    $filename=$_SESSION['DOCUMENT_ROOT']."/".$resize_path.$image_name;
    if($image_type == "image/gif"){
      @imagegif($thumb, $filename);
    }else if($image_type == "image/jpeg" || $image_type == "image/pjpeg"){
      @imagejpeg($thumb, $filename);
    }else if($image_type == "image/png"){
      @imagepng($thumb, $filename);
    }
  }
  
  /*
    This function returns true when mail correctly sent else false with global $error set
    
  */
  function smtpmailer($to, $from, $from_name, $subject, $body, $replyto = null, $HTML=true) { 
    global $error;
    $error = false;
    if($_SESSION['has_email']=="no") return true;  //simulate send OK
    $mail = new PHPMailer(true);  // create a new object
    $mail->IsSMTP(); // enable SMTP
    $mail->CharSet='UTF-8'; // enable SMTP
    $mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
    $mail->SMTPAuth = true;  // authentication enabled
    $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
    $mail->Host = 'einstein.sui-inter.net';
    $mail->Port = 465; 
    $mail->Username = $_SESSION['CHMUSER'];  
    $mail->Password = $_SESSION['CHMPWD'];           
    $error = !($mail->SetFrom($from, $from_name, 0));
    if (isset($replyto))
      $mail->AddReplyTo($replyto,$replyto);
    else
      $mail->AddReplyTo($from,$from_name);
    $mail->Subject = $subject;
    $mail->IsHTML($HTML);
    $tmpBody = $body;
    if ($HTML) {
      $tmpBody = str_replace("\r\n", "<BR />", $tmpBody);
      $tmpBody = str_replace("\n", "<BR />", $tmpBody);
      $tmpBody = str_replace("\r", "<BR />", $tmpBody);
      $tmpBody = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;>", $tmpBody);
    }
    $mail->Body = $tmpBody;
    if (!$error)
      $error = !($mail->AddAddress($to));
    $error=false;
    if($error || !$mail->Send()) {
      $error = 'Mail error: '.$mail->ErrorInfo; 
      return false;
    } else {
      $error = 'Message sent!';
      return true;
    }
  }

/**
 * Convert date/time format between `date()` and `strftime()`
 *
 * Timezone conversion is done for Unix. Windows users must exchange %z and %Z.
 *
 * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r
 * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
 *
 * @example Convert `%A, %B %e, %Y, %l:%M %P` to `l, F j, Y, g:i a`, and vice versa for "Saturday, March 10, 2001, 5:16 pm"
 * @link http://php.net/manual/en/function.strftime.php#96424
 *
 * @param string $format The format to parse.
 * @param string $syntax The format's syntax. Either 'strf' for `strtime()` or 'date' for `date()`.
 * @return bool|string Returns a string formatted according $syntax using the given $format or `false`.
 */

function date_format_to( $format, $syntax )
{
  // http://php.net/manual/en/function.strftime.php
  $strf_syntax = [
    // Day - no strf eq : S (created one called %O)
    '%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
    // Week - no date eq : %U, %W
    '%V',
    // Month - no strf eq : n, t
    '%B', '%m', '%b', '%-m',
    // Year - no strf eq : L; no date eq : %C, %g
    '%G', '%Y', '%y',
    // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
    '%P', '%p', '%l', '%I', '%H', '%M', '%S',
    // Timezone - no strf eq : e, I, P, Z
    '%z', '%Z',
    // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
    '%s'
  ];

  // http://php.net/manual/en/function.date.php
  $date_syntax = [
    'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
    'W',
    'F', 'm', 'M', 'n',
    'o', 'Y', 'y',
    'a', 'A', 'g', 'h', 'H', 'i', 's',
    'O', 'T',
    'U'
  ];

  switch ( $syntax ) {
    case 'date':
      $from = $strf_syntax;
      $to   = $date_syntax;
      break;

    case 'strf':
      $from = $date_syntax;
      $to   = $strf_syntax;
      break;

    default:
      return false;
  }

  $pattern = array_map(
    function ( $s ) {
      return '/(?<!\\\\|\%)' . $s . '/';
    },
    $from
  );

  return preg_replace( $pattern, $to, $format );
}

/**
 * Equivalent to `date_format_to( $format, 'date' )`
 *
 * @param string $strf_format A `strftime()` date/time format
 * @return string
 */

function strftime_format_to_date_format( $strf_format )
{
  return $this->date_format_to( $strf_format, 'date' );
}

/**
 * Equivalent to `convert_datetime_format_to( $format, 'strf' )`
 *
 * @param string $date_format A `date()` date/time format
 * @return string
 */

function date_format_to_strftime_format( $date_format )
{
  return $this->date_format_to( $date_format, 'strf' );
}/*
    This function returns formated passed date using passed format string
    default "d.m.y" will returns 13.02.13 for 13 February 2013
  */
  function format_date($date, $sFmt="d.m.Y"){  // 
    if (substr($date,0,10)=="0000-00-00" || substr($date,0,10)=="00-00-0000")
      if ($sFmt=="Y.m.d")
        return "0000-00-00";   //
       else  
       return "00-00-0000";     //  
    $date1 = strtotime($date);
    return (strftime($this->date_format_to_strftime_format($sFmt),$date1));
  }
  
function exists_in_array($value=null,$field=null,$array=null,$row=false) {
  $found = false;
  for ($i=0;$i<sizeof($array);$i++) {
    if ($array[$i]) {
      if ($array[$i][$field]==$value) {
        $found = ($row==false) ? true : $i;
        break;
      }
    } else {
      if (is_string($value))
        if (strval($array[$field])==$value) {
          $found = true;
          break;
        }
      else
        if ($array[$field]==$value) {
          $found = true;
          break;
        }
    }
  } 
  return $found;
}
function exists2_in_array($array=null,$value1=null,$field1=null,$value2=null,$field2=null,$row=false) {
  $found = false;
  for ($i=0;$i<sizeof($array);$i++) {
    if ($array[$i][$field1]==$value1) {
      if (!empty($value2) && !empty($field2)) {// two fields must match
        if ($array[$i][$field2]==$value2) {
          $found = ($row==false) ? true : $i;
          break;
        }
      }
      else {
        $found = ($row==false) ? true : $i;
        break;
      }
    }
  } 
  return $found;
}
// This returns the array index when the passed seed is found in passed array else false
function index_in_array($seed, $field, $array) {
  $found = false;
  for ($i=0;$i<sizeof($array);$i++)
    if (trim($array[$i][$field])==trim($seed)) {
      $found = $i;
      break;
    }
  return $found;  
}

function pos_in_array($seed,$array) {
  $pos = 0;
  $found = false;
  foreach($array as $elem) {
    if (in_array($elem,$seed)===false)
      $pos++;
    else 
      $found = $pos;
  }
  return $pos;
}
  
}//class db_utils
?>