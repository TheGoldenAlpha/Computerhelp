<?php
/*
  History: 19-oct-13 GC -> add tools menu
           19-oct-13 GC -> add tools button1: Refresh small/mid pictures
*/

  $_SESSION['domain'] = "computerhelp.ch";
  $http = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on") ? "https://" : "http://";
  $_SESSION['WWW']= $http.$_SESSION['domain'];
  $_SESSION['REG']="Computerhelp";
  $_SESSION['root_folder']="cmstest";
  $_SESSION['cookiesWarning'] = false;
// jGC: SMS API
// in smsAPI
  $_SESSION['has_smsAPI'] = "no";
  $_SESSION['smsAPI_sender'] = "xxx";  
  $_SESSION['smsAPI_maxChar'] = 160;     // 1 SMS max (1*160)
// Tables config
  $_SESSION['has_pagemenus'] = "yes";  
  $_SESSION['has_marquees'] = "yes";  
  $_SESSION['randomize_marquees'] = "yes";  
  $_SESSION['has_diashows'] = "yes";  
  $_SESSION['has_galeries'] = "yes";  
  $_SESSION['has_picts'] = "yes";
  $_SESSION['has_backgrounds'] = "yes";
  $_SESSION['has_videos'] = "no";  
  $_SESSION['has_news'] = "yes";  
  $_SESSION['has_links'] = "yes";  
  $_SESSION['has_faqs'] = "no";  
  $_SESSION['has_sponsors'] = "no";  
  $_SESSION['has_countries'] = "no";  
  $_SESSION['has_clients'] = "yes";  
  $_SESSION['has_contracts'] = "no";  
  $_SESSION['has_users'] = "no";  
  $_SESSION['has_status'] = "no";  
  $_SESSION['has_calls'] = "no";  
  $_SESSION['has_companies'] = "no";  
  $_SESSION['has_dictums'] = "no";  
  $_SESSION['has_locations'] = "no";  
  $_SESSION['has_countries'] = "no";  
  $_SESSION['has_datestamp'] = "no";  
  $_SESSION['has_footer'] = "no";  
// Firm specifics
  $_SESSION['is_ZEEV'] = "no";
  $_SESSION['clients_has_company'] = "no";
// Languages config  
  $_SESSION['languages'] = array(1=>'en',2=>'de',3=>'fr',4=>'it');   //in the order: language 1, 2 and 3
  if (empty($_SESSION['lang']))
    $_SESSION['lang'] = $_SESSION['languages'][2];
  $_SESSION['lng1'] = "off";  // lng1 is "en"
  $_SESSION['lng2'] = "on";   // lng2 is "de"
  $_SESSION['lng3'] = "off";  // lng3 is "en"
  $_SESSION['lng4'] = "off";  // lng3 is "en"
// show current verion nr
  $_SESSION['show_version'] = "on";
  $_SESSION['versionInFooter'] = true;  
// banner link to home  
  $_SESSION['banner_to_home'] = "on";
// low marquee
  $_SESSION['lowmarquee'] = "off";
// lowbanner  
  $_SESSION['has_lowbanner'] = "yes";
  $_SESSION['lowbanner'] = "on";
  $_SESSION['lowbanner_align'] = "content";  //accept body or content
// Has Banner in Layout
  $_SESSION['banner_in_layout'] = "no";
  $_SESSION['banner_lng_in_layout'] = "no";
// Has fixed line in title
  $_SESSION['line_in_title'] = "no";
// News
  $_SESSION['picture_column'] = "off";
// diashow
  $_SESSION['diashowcomment'] = "off";
  $_SESSION['diashowbuttons'] = "off";
  $_SESSION['diashowbutton_prev'] = "off";
  $_SESSION['diashowbutton_next'] = "off";
  $_SESSION['diashownav'] = "off";
  $_SESSION['dishowlinkself'] = "yes";  //EC: switch for bgstretcher.js to use either standard procedure or targt="_self", standard procedure on "off" and target="_self" on "yes".
  // minidiashow  
  $_SESSION['minidiashowcomment'] = "on";
  $_SESSION['minidiashowbuttons'] = "on";
  $_SESSION['minidiashowbutton_prev'] = "on";
  $_SESSION['minidiashowbutton_next'] = "on";
  $_SESSION['minidiashownav'] = "off";
  $_SESSION['stdWWW'] = $_SESSION['WWW'];
// Special last menu
  $_SESSION['special_lastmenu'] = "off";  
// Official Site Emails
  $_SESSION['EMAIL_NEWSLETTER']="newsletter@computerhelp.ch";
  $_SESSION['E-MAIL1']="fh@computerhelp.ch";
  $_SESSION['E-MAIL2']="fh@computerhelp.ch";
  $_SESSION['E-MAIL3']="fh@computerhelp.ch";  //esmeralda@limmat.org
  $_SESSION['E-MAIL4']="fh@computerhelp.ch";
  $_SESSION['CHMUSER']="xmail@computerhelp.ch";     // Mail username
  $_SESSION['CHMPWD']="~100pxFw";               // Mail password
// Document server path    
  $_SESSION['WWW'] = $http.$_SESSION['WWW'];
  $_SESSION['DOCUMENT_ROOT']=$_SERVER['DOCUMENT_ROOT'];
  $_SESSION['std_document_root'] = $_SESSION['DOCUMENT_ROOT'];
  $_SESSION['std_document_ext'] = "";
  if (strpos($_SESSION['DOCUMENT_ROOT'],"subdomains")!==false) {
    $_SESSION['stdWWW'] = $http.$_SERVER['HTTP_HOST'];
    $_SESSION['EMAIL_NEWSLETTER']="gc@computerhelp.ch";
    $_SESSION['E-MAIL1']="gc@computerhelp.ch";
    $_SESSION['E-MAIL2']="gc@computerhelp.ch";
    $_SESSION['E-MAIL3']="gc@computerhelp.ch";
    $_SESSION['E-MAIL4']="client_type@computerhelp.ch";
    $_SESSION['CHMUSER']="xmail@computerhelp.ch"; // CH Mail username
    $_SESSION['CHMPWD']="~100pxFw";               // CH Mail password
  } else 
  if (strpos($_SERVER['DOCUMENT_ROOT'],"computerhelp.ch")!==false) { // when in computerhelp.ch as subdir
    $_SESSION['stdWWW'] = $http.$_SERVER['HTTP_HOST'];
    $_SESSION['EMAIL_NEWSLETTER']="gc@computerhelp.ch";
    $_SESSION['E-MAIL1']="gc@computerhelp.ch";
    $_SESSION['E-MAIL2']="gc@computerhelp.ch";
    $_SESSION['E-MAIL3']="gc@computerhelp.ch";
    $_SESSION['E-MAIL4']="client_type@computerhelp.ch";
    $_SESSION['CHMUSER']="xmail@computerhelp.ch"; // CH Mail username
    $_SESSION['CHMPWD']="~100pxFw";               // CH Mail password
  } else
  if (strpos($_SERVER['SCRIPT_NAME'],"computerhelp")!==false || strpos($_SERVER['SERVER_NAME'],"mywire.org")!==false) {  // Re-define vars when local debug
    $_SESSION['EMAIL_NEWSLETTER']="gc@computerhelp.ch";
    $_SESSION['E-MAIL1']="fh@computerhelp.ch";
    $_SESSION['E-MAIL2']="fh@computerhelp.ch";
    $_SESSION['E-MAIL3']="fh@computerhelp.ch";
    $_SESSION['E-MAIL4']="client_type@computerhelp.ch";
    $_SESSION['CHMUSER']="xmail@computerhelp.ch"; // CH Mail username
    $_SESSION['CHMPWD']="~100pxFw";               // CH Mail password
    ini_set( 'default_charset', 'UTF-8' );
    $server_name = gethostbyaddr($_SERVER['SERVER_ADDR']);
    if (strpos($server_name,".") !== false)
      $server_name = substr($server_name,0,strpos($server_name,"."));
    if ($server_name=="terminus24") { // when on chweblive
      $_SESSION['WWW']=$http.$_SERVER['SERVER_NAME']."/".$_SESSION['root_folder'];
      $_SESSION['stdWWW'] = $_SESSION['WWW'];
      $_SESSION['DOCUMENT_ROOT']=$_SERVER['DOCUMENT_ROOT']."/".$_SESSION['root_folder'];
    } else {
      $_SESSION['WWW']=$http.$server_name."/computerhelp/".$_SESSION['root_folder'];
      $_SESSION['stdWWW'] = $_SESSION['WWW'];
      $_SESSION['DOCUMENT_ROOT']=$_SERVER['DOCUMENT_ROOT']."/computerhelp/".$_SESSION['root_folder'];
    }
    $_SESSION['DOCUMENT_ROOT'] = str_replace("//","/",$_SESSION['DOCUMENT_ROOT']);
    $_SESSION['std_document_root'] = $_SESSION['DOCUMENT_ROOT'];
    $_SESSION['EMAIL_NEWSLETTER']="gc@computerhelp.ch";
    $_SESSION['E-MAIL1']="fh@computerhelp.ch";
    $_SESSION['E-MAIL2']="fh@computerhelp.ch";
    $_SESSION['E-MAIL3']="fh@computerhelp.ch";
    $_SESSION['E-MAIL4']="fh@computerhelp.ch";
  }
//echo $_SESSION['stdWWW'];
//echo "§§§";
//echo $_SESSION['DOCUMENT_ROOT'];
//exit;

  // divers pseudo-constants used anywhere
  $_SESSION['has_overlay'] = "no";
  $_SESSION['has_email'] = "yes";
  $_SESSION['has_orders'] = "no";
  $_SESSION['extra_log'] = false;
  $_SESSION['gallery_singleColorbox'] = false;
  $_SESSION['use_gallery_flex'] = true;
  $_SESSION['gallery_title'] = true;
  $_SESSION['gallery_text'] = false;
  $_SESSION['gallery_pic_HTML'] = true;
  include('../include/Mobile_Detect.php');
  $browser = new Mobile_Detect();
  // Galleries
  if ($browser->isTablet()) 
    $_SESSION['gallery_picts_per_line'] = 4;
  else
    $_SESSION['gallery_picts_per_line'] = 4;
  $_SESSION['gallery_using_background'] = "yes";
  $_SESSION['facebook'] = false;
  $_SESSION['twitter'] = false;
  $_SESSION['msg_duration'] = 2000; // duration in ms
  $_SESSION['expiry_delay'] = 7;
  
$_SESSION['empty_id_psw'][$_SESSION['languages'][1]] = 'Blank user ID and/or password are not allowed';
  $_SESSION['empty_id_psw'][$_SESSION['languages'][2]] = 'Leeres Login-ID und/oder Passwort unzulässig';
  $_SESSION['empty_id_psw'][$_SESSION['languages'][3]] = 'ID et/ou Mot-de-Passe ne peuvent être vides';
  $_SESSION['empty_id_psw'][$_SESSION['languages'][4]] = 'Vuoto ID e/o password non valida';
  
  $_SESSION['follow'][$_SESSION['languages'][1]] = 'Follow us on: ';
  $_SESSION['follow'][$_SESSION['languages'][2]] = 'Folgen Sie uns auf: ';
  $_SESSION['follow'][$_SESSION['languages'][3]] = 'Suivez-nous sur: ';
  $_SESSION['follow'][$_SESSION['languages'][4]] = 'Seguici su: ';
  
  $_SESSION['logon'][$_SESSION['languages'][1]] = 'log-in';
  $_SESSION['logon'][$_SESSION['languages'][2]] = 'anmelden';
  $_SESSION['logon'][$_SESSION['languages'][3]] = 'log-in';
  $_SESSION['logon'][$_SESSION['languages'][4]] = 'log-in';
 
  $_SESSION['tools'][$_SESSION['languages'][1]] = 'Tools';
  $_SESSION['tools'][$_SESSION['languages'][2]] = 'Werkzeuge';
  $_SESSION['tools'][$_SESSION['languages'][3]] = 'Outils';
  $_SESSION['tools'][$_SESSION['languages'][4]] = 'Strumenti';
 
  $_SESSION['ToolsTxt1'][$_SESSION['languages'][1]] = 'You can re-generate all pictures for a specific (or more) folder';
  $_SESSION['ToolsTxt1'][$_SESSION['languages'][2]] = 'Sie können Bilder von ein bestimmtes (oder mehreren) Verzeichnis regenerieren';
  $_SESSION['ToolsTxt1'][$_SESSION['languages'][3]] = 'Vous pouvez régénérer les images à partir d\'un (ou plusieurs) répertoire particulier ';
  $_SESSION['ToolsTxt1'][$_SESSION['languages'][4]] = 'È possibile rigenerare le immagini da un particolare (o più) di directory';
 
  $_SESSION['ToolsBtn1'][$_SESSION['languages'][1]] = 'Refresh pictures';
  $_SESSION['ToolsBtn1'][$_SESSION['languages'][2]] = 'Bilder auffrischen';
  $_SESSION['ToolsBtn1'][$_SESSION['languages'][3]] = 'Actualiser les photos';
  $_SESSION['ToolsBtn1'][$_SESSION['languages'][4]] = 'immagini aggiorna';
 
  $_SESSION['NoFolderFound'][$_SESSION['languages'][1]] = 'No folder found.';
  $_SESSION['NoFolderFound'][$_SESSION['languages'][2]] = 'Kein Verzeichnis gefunden.';
  $_SESSION['NoFolderFound'][$_SESSION['languages'][3]] = 'Pas de répertoire trouvé.';
  $_SESSION['NoFolderFound'][$_SESSION['languages'][4]] = 'Nessuna directory trovato.';
 
  $_SESSION['doit'][$_SESSION['languages'][1]] = 'Do it';
  $_SESSION['doit'][$_SESSION['languages'][2]] = 'Ausführen';
  $_SESSION['doit'][$_SESSION['languages'][3]] = 'Exécuter';
  $_SESSION['doit'][$_SESSION['languages'][4]] = 'Corsa';
 
  $_SESSION['yes'][$_SESSION['languages'][1]] = 'Yes';
  $_SESSION['yes'][$_SESSION['languages'][2]] = 'Ja';
  $_SESSION['yes'][$_SESSION['languages'][3]] = 'Oui';
  $_SESSION['yes'][$_SESSION['languages'][4]] = 'Si';
  
  $_SESSION['no'][$_SESSION['languages'][1]] = 'No';
  $_SESSION['no'][$_SESSION['languages'][2]] = 'Nein';
  $_SESSION['no'][$_SESSION['languages'][3]] = 'Non';
  $_SESSION['no'][$_SESSION['languages'][4]] = 'No';
  
  $_SESSION['es_register'][$_SESSION['languages'][1]] = 'Register';
  $_SESSION['es_register'][$_SESSION['languages'][2]] = 'Anmeldung';
  $_SESSION['es_register'][$_SESSION['languages'][3]] = 'Enregistrer';
  $_SESSION['es_register'][$_SESSION['languages'][4]] = 'Applicazione';
 
  $_SESSION['skip_intro'][$_SESSION['languages'][1]] = 'Skip intro';
  $_SESSION['skip_intro'][$_SESSION['languages'][2]] = 'Überspringen';
  $_SESSION['skip_intro'][$_SESSION['languages'][3]] = 'Passer l\'intro';
  $_SESSION['skip_intro'][$_SESSION['languages'][4]] = 'Salto intro';
 
  $_SESSION['es_register_mail'][$_SESSION['languages'][1]] = 'Esmeralda Cup Registration';
  $_SESSION['es_register_mail'][$_SESSION['languages'][2]] = 'Esmeralda Cup Anmeldung';
  $_SESSION['es_register_mail'][$_SESSION['languages'][3]] = 'Esmeralda Cup Registration';
  $_SESSION['es_register_mail'][$_SESSION['languages'][4]] = 'Esmeralda Cup Registration';
 
  $_SESSION['es_register_form'][$_SESSION['languages'][1]] = 'Registration Form';
  $_SESSION['es_register_form'][$_SESSION['languages'][2]] = 'Anmeldungsformular';
  $_SESSION['es_register_form'][$_SESSION['languages'][3]] = 'Registration Form';
  $_SESSION['es_register_form'][$_SESSION['languages'][4]] = 'Registration Form';
 
  $_SESSION['register_mail'][$_SESSION['languages'][1]] = 'Registration';
  $_SESSION['register_mail'][$_SESSION['languages'][2]] = 'Kontaktaufnahme';
  $_SESSION['register_mail'][$_SESSION['languages'][3]] = 'Registration';
  $_SESSION['register_mail'][$_SESSION['languages'][4]] = 'Registration';
 
  $_SESSION['register_form'][$_SESSION['languages'][1]] = 'Contact Form';
  $_SESSION['register_form'][$_SESSION['languages'][2]] = 'Kontaktformular';
  $_SESSION['register_form'][$_SESSION['languages'][3]] = 'Formulaire de contact';
  $_SESSION['register_form'][$_SESSION['languages'][4]] = 'Contatto';

  $_SESSION['projects'][$_SESSION['languages'][1]] = 'Projects';
  $_SESSION['projects'][$_SESSION['languages'][2]] = 'Projekte';
  $_SESSION['projects'][$_SESSION['languages'][3]] = 'Projects';
  $_SESSION['projects'][$_SESSION['languages'][4]] = 'Projects';
 
  $_SESSION['loginPromt'][$_SESSION['languages'][1]] = 'You are not logged-in. Please enter a valid userID and a valid password.';
  $_SESSION['loginPromt'][$_SESSION['languages'][2]] = 'Sie sind nicht eingeloggt. Bitte geben Sie ein gültiges Login ein.';
  $_SESSION['loginPromt'][$_SESSION['languages'][3]] = 'Vous n\'êtes pas connecté. Veuillez vous annoncer.';
  $_SESSION['loginPromt'][$_SESSION['languages'][4]] = 'Non sei loggato. Si prega di inserire un accesso valido una.';
 
  $_SESSION['admin_header'][$_SESSION['languages'][1]] = 'Admin tools for ';
  $_SESSION['admin_header'][$_SESSION['languages'][2]] = 'Administration für ';
  $_SESSION['admin_header'][$_SESSION['languages'][3]] = 'Administration pour ';
  $_SESSION['admin_header'][$_SESSION['languages'][4]] = 'Amministrazione per ';
 
  $_SESSION['ftplogin'][$_SESSION['languages'][1]] = 'Cannot log into FTP server';
  $_SESSION['ftplogin'][$_SESSION['languages'][2]] = 'Cannot log into FTP server';
  $_SESSION['ftplogin'][$_SESSION['languages'][3]] = 'Cannot log into FTP server';
  $_SESSION['ftplogin'][$_SESSION['languages'][4]] = 'Cannot log into FTP server';
 
  $_SESSION['ftpopen'][$_SESSION['languages'][1]] = 'Cannot open FTP session';
  $_SESSION['ftpopen'][$_SESSION['languages'][2]] = 'Cannot open FTP session';
  $_SESSION['ftpopen'][$_SESSION['languages'][3]] = 'Cannot open FTP session';
  $_SESSION['ftpopen'][$_SESSION['languages'][4]] = 'Cannot open FTP session';
 
  $_SESSION['ftptransfertOK'][$_SESSION['languages'][1]] = 'Data uploaded';
  $_SESSION['ftptransfertOK'][$_SESSION['languages'][2]] = 'Data uploaded';
  $_SESSION['ftptransfertOK'][$_SESSION['languages'][3]] = 'Data uploaded';
  $_SESSION['ftptransfertOK'][$_SESSION['languages'][4]] = 'Data uploaded';
 
  $_SESSION['ftptransfertNOK'][$_SESSION['languages'][1]] = 'Cannot upload data';
  $_SESSION['ftptransfertNOK'][$_SESSION['languages'][2]] = 'Cannot upload data';
  $_SESSION['ftptransfertNOK'][$_SESSION['languages'][3]] = 'Cannot upload data';
  $_SESSION['ftptransfertNOK'][$_SESSION['languages'][4]] = 'Cannot upload data';
 
  $_SESSION['lang_chg'][$_SESSION['languages'][1]] = 'Language is now english';
  $_SESSION['lang_chg'][$_SESSION['languages'][2]] = 'Die Sprache wurde umgestellt auf Deutsch';
  $_SESSION['lang_chg'][$_SESSION['languages'][3]] = 'Le language est à présent le français';
  $_SESSION['lang_chg'][$_SESSION['languages'][4]] = 'Il linguaggio è stato cambiato in italiano';
 
  $_SESSION['pickFile'][$_SESSION['languages'][1]] = 'Browse...';
  $_SESSION['pickFile'][$_SESSION['languages'][2]] = 'Durchsuchen...';
  $_SESSION['pickFile'][$_SESSION['languages'][3]] = 'Rechercher...';
  $_SESSION['pickFile'][$_SESSION['languages'][4]] = 'Ricerca...';
 
  $_SESSION['Saved'][$_SESSION['languages'][1]] = 'saved';
  $_SESSION['Saved'][$_SESSION['languages'][2]] = 'gespeichert';
  $_SESSION['Saved'][$_SESSION['languages'][3]] = 'enregistré';
  $_SESSION['Saved'][$_SESSION['languages'][4]] = 'memorizzata';
  
  $_SESSION['RecNotFound'][$_SESSION['languages'][1]] = 'Record not found.';
  $_SESSION['RecNotFound'][$_SESSION['languages'][2]] = 'Datensatz nicht gefunden.';
  $_SESSION['RecNotFound'][$_SESSION['languages'][3]] = 'Enregistrement pas trouvé.';
  $_SESSION['RecNotFound'][$_SESSION['languages'][4]] = 'Registrazione non trovato.';
 
  $_SESSION['RecSaved'][$_SESSION['languages'][1]] = 'Record saved';
  $_SESSION['RecSaved'][$_SESSION['languages'][2]] = 'Rekord gespeichert';
  $_SESSION['RecSaved'][$_SESSION['languages'][3]] = 'Enregistrement sauvé';
  $_SESSION['RecSaved'][$_SESSION['languages'][4]] = 'Registrazione salvato';
  
  $_SESSION['RecModif'][$_SESSION['languages'][1]] = 'Last change:';
  $_SESSION['RecModif'][$_SESSION['languages'][2]] = 'Letzte Änderung:';
  $_SESSION['RecModif'][$_SESSION['languages'][3]] = 'Dernière Mise-à-Jour:';
  $_SESSION['RecModif'][$_SESSION['languages'][4]] = 'Ultimo aggiornamento:';
  
  $_SESSION['RecDeleted'][$_SESSION['languages'][1]] = 'Record saved';
  $_SESSION['RecDeleted'][$_SESSION['languages'][2]] = 'Rekord gelöscht';
  $_SESSION['RecDeleted'][$_SESSION['languages'][3]] = 'Enregistrement effacé';
  $_SESSION['RecDeleted'][$_SESSION['languages'][4]] = 'Registrazione cancellato';
  
  $_SESSION['RecNotSaved'][$_SESSION['languages'][1]] = 'NOT saved';
  $_SESSION['RecNotSaved'][$_SESSION['languages'][2]] = 'NICHT gespeichert';
  $_SESSION['RecNotSaved'][$_SESSION['languages'][3]] = 'PAS sauvé';
  $_SESSION['RecNotSaved'][$_SESSION['languages'][4]] = 'NON salvato';
  
  $_SESSION['DupRec'][$_SESSION['languages'][1]] = 'NOT saved: already exists!';
  $_SESSION['DupRec'][$_SESSION['languages'][2]] = 'NICHT gespeichert: existiert schon!';
  $_SESSION['DupRec'][$_SESSION['languages'][3]] = 'PAS sauvé: existe déjà!';
  $_SESSION['DupRec'][$_SESSION['languages'][4]] = 'NON salvato: esiste già!';
  
  $_SESSION['Search'][$_SESSION['languages'][1]] = 'search';
  $_SESSION['Search'][$_SESSION['languages'][2]] = 'suche';
  $_SESSION['Search'][$_SESSION['languages'][3]] = 'recherche';
  $_SESSION['Search'][$_SESSION['languages'][4]] = 'ricerca';
  
  $_SESSION['SearchByKey'][$_SESSION['languages'][1]] = 'Or search by keyword';
  $_SESSION['SearchByKey'][$_SESSION['languages'][2]] = 'Oder Suche nach Stichwort';
  $_SESSION['SearchByKey'][$_SESSION['languages'][3]] = 'Ou Recherche par mots clés';
  $_SESSION['SearchByKey'][$_SESSION['languages'][4]] = 'O ricerca per parola chiave';

  $_SESSION['SaveBtn'][$_SESSION['languages'][1]] = 'save';
  $_SESSION['SaveBtn'][$_SESSION['languages'][2]] = 'speichern';
  $_SESSION['SaveBtn'][$_SESSION['languages'][3]] = 'sauver';
  $_SESSION['SaveBtn'][$_SESSION['languages'][4]] = 'salvato';
  
  $_SESSION['DelBtn'][$_SESSION['languages'][1]] = 'delete';
  $_SESSION['DelBtn'][$_SESSION['languages'][2]] = 'löschen';
  $_SESSION['DelBtn'][$_SESSION['languages'][3]] = 'supprimer';
  $_SESSION['DelBtn'][$_SESSION['languages'][4]] = 'cancellato';
  
  $_SESSION['remove'][$_SESSION['languages'][1]] = 'remove';
  $_SESSION['remove'][$_SESSION['languages'][2]] = 'entfernen';
  $_SESSION['remove'][$_SESSION['languages'][3]] = 'retirer';
  $_SESSION['remove'][$_SESSION['languages'][4]] = 'rimuovere';
  
  $_SESSION['RecCount'][$_SESSION['languages'][1]] = 'entries';
  $_SESSION['RecCount'][$_SESSION['languages'][2]] = 'Einträge';
  $_SESSION['RecCount'][$_SESSION['languages'][3]] = 'Entrées';
  $_SESSION['RecCount'][$_SESSION['languages'][4]] = 'Iscrizioni';
  
  $_SESSION['RecInsert'][$_SESSION['languages'][1]] = 'insert';
  $_SESSION['RecInsert'][$_SESSION['languages'][2]] = 'erfassen';
  $_SESSION['RecInsert'][$_SESSION['languages'][3]] = 'inserer';
  $_SESSION['RecInsert'][$_SESSION['languages'][4]] = 'cattura';
  
  $_SESSION['RecEdit'][$_SESSION['languages'][1]] = 'edit';
  $_SESSION['RecEdit'][$_SESSION['languages'][2]] = 'ändern';
  $_SESSION['RecEdit'][$_SESSION['languages'][3]] = 'editer';
  $_SESSION['RecEdit'][$_SESSION['languages'][4]] = 'cambiamento';
  
  $_SESSION['BackLink'][$_SESSION['languages'][1]] = 'back';
  $_SESSION['BackLink'][$_SESSION['languages'][2]] = 'zurück';
  $_SESSION['BackLink'][$_SESSION['languages'][3]] = 'retour';
  $_SESSION['BackLink'][$_SESSION['languages'][4]] = 'ritorno';
  
  $_SESSION['FwdLink'][$_SESSION['languages'][1]] = 'next';
  $_SESSION['FwdLink'][$_SESSION['languages'][2]] = 'weiter';
  $_SESSION['FwdLink'][$_SESSION['languages'][3]] = 'continuer';
  $_SESSION['FwdLink'][$_SESSION['languages'][4]] = 'continuare';
  
  $_SESSION['Show'][$_SESSION['languages'][1]] = 'show';
  $_SESSION['Show'][$_SESSION['languages'][2]] = 'zeige';
  $_SESSION['Show'][$_SESSION['languages'][3]] = 'montrer';
  $_SESSION['Show'][$_SESSION['languages'][4]] = 'mostrare';
  
  $_SESSION['CheckOn'][$_SESSION['languages'][1]] = 'on';
  $_SESSION['CheckOn'][$_SESSION['languages'][2]] = 'ein';
  $_SESSION['CheckOn'][$_SESSION['languages'][3]] = 'marche';
  $_SESSION['CheckOn'][$_SESSION['languages'][4]] = 'on';
  
  $_SESSION['CheckOff'][$_SESSION['languages'][1]] = 'off';
  $_SESSION['CheckOff'][$_SESSION['languages'][2]] = 'aus';
  $_SESSION['CheckOff'][$_SESSION['languages'][3]] = 'arrêt';
  $_SESSION['CheckOff'][$_SESSION['languages'][4]] = 'off';
  
  $_SESSION['TextOK'][$_SESSION['languages'][1]] = 'text ok';
  $_SESSION['TextOK'][$_SESSION['languages'][2]] = 'Text vorhanden';
  $_SESSION['TextOK'][$_SESSION['languages'][3]] = 'Texte disponible';
  $_SESSION['TextOK'][$_SESSION['languages'][4]] = 'Testo disponibile';
  
  $_SESSION['of'][$_SESSION['languages'][1]] = 'of';
  $_SESSION['of'][$_SESSION['languages'][2]] = 'von';
  $_SESSION['of'][$_SESSION['languages'][3]] = 'de';
  $_SESSION['of'][$_SESSION['languages'][4]] = 'da';
  
  $_SESSION['date'][$_SESSION['languages'][1]] = 'Date';
  $_SESSION['date'][$_SESSION['languages'][2]] = 'Datum';
  $_SESSION['date'][$_SESSION['languages'][3]] = 'Date';
  $_SESSION['date'][$_SESSION['languages'][4]] = 'Data';
  
  $_SESSION['calendar'][$_SESSION['languages'][1]] = 'calendar';
  $_SESSION['calendar'][$_SESSION['languages'][2]] = 'Kalender';
  $_SESSION['calendar'][$_SESSION['languages'][3]] = 'calendrier';
  $_SESSION['calendar'][$_SESSION['languages'][4]] = 'calendario';
  
  $_SESSION['cal_close'][$_SESSION['languages'][1]] = "'close'";
  $_SESSION['cal_close'][$_SESSION['languages'][2]] = "schliessen";
  $_SESSION['cal_close'][$_SESSION['languages'][3]] = "fermer";
  $_SESSION['cal_close'][$_SESSION['languages'][4]] = "vicino";
  
  $_SESSION['cal_today'][$_SESSION['languages'][1]] = "'Today'";
  $_SESSION['cal_today'][$_SESSION['languages'][2]] = "Heute";
  $_SESSION['cal_today'][$_SESSION['languages'][3]] = "Aujourd'hui";
  $_SESSION['cal_today'][$_SESSION['languages'][4]] = "Oggi";
  
  $_SESSION['cal_wdays'][$_SESSION['languages'][1]] = "'Su','Mo','Tu','We','Th','Fr','Sa'";
  $_SESSION['cal_wdays'][$_SESSION['languages'][2]] = "'So','Mo','Di','Mi','Do','Fr','Sa'";
  $_SESSION['cal_wdays'][$_SESSION['languages'][3]] = "'Di','Lu','Ma','Me','Je','Ve','Sa'";
  $_SESSION['cal_wdays'][$_SESSION['languages'][4]] = "'Do','Lu','Ma','Me','Gi','Ve','Sa'";
  
  $_SESSION['cal_wdays3'][$_SESSION['languages'][1]] = "'Sun','Mon','Tue','Wed','Thu','Fri','Sat'";
  $_SESSION['cal_wdays3'][$_SESSION['languages'][2]] = "'Son','Mon','Die','Mit','Don','Fre','Sam'";
  $_SESSION['cal_wdays3'][$_SESSION['languages'][3]] = "'Dim','Lun','Mar','Mer','Jeu','Ven','Sam'";
  $_SESSION['cal_wdays3'][$_SESSION['languages'][4]] = "'Dom','Lun','Mar','Mer','Gio','Ven','Sab'";
  
  $_SESSION['cal_months3'][$_SESSION['languages'][1]] = "'jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'";
  $_SESSION['cal_months3'][$_SESSION['languages'][2]] = "'Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'";
  $_SESSION['cal_months3'][$_SESSION['languages'][3]] = "'jan','fev','mar','avr','mai','jui','jul','aoû','sep','oct','nov','dec'";
  $_SESSION['cal_months3'][$_SESSION['languages'][4]] = "'gen','feb','mar','apr','mag','giu','lug','ago','set','ott','nov','dic'";
  
  $_SESSION['cal_months'][$_SESSION['languages'][1]] = "'january','february','march','april','may','june','july','august','september','october','november','december'";
  $_SESSION['cal_months'][$_SESSION['languages'][2]] = "'Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'";
  $_SESSION['cal_months'][$_SESSION['languages'][3]] = "'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'";
  $_SESSION['cal_months'][$_SESSION['languages'][4]] = "'gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'";
  
  $_SESSION['show_all'][$_SESSION['languages'][1]] = 'Show all';
  $_SESSION['show_all'][$_SESSION['languages'][2]] = 'Alle anzeigen';
  $_SESSION['show_all'][$_SESSION['languages'][3]] = 'Montrer tout';
  $_SESSION['show_all'][$_SESSION['languages'][4]] = 'Visualizza tutto';
  
  $_SESSION['project_news'][$_SESSION['languages'][1]] = 'News for this theme';
  $_SESSION['project_news'][$_SESSION['languages'][2]] = 'News zu diesem Thema';
  $_SESSION['project_news'][$_SESSION['languages'][3]] = 'Nouvelles sur ce sujet';
  $_SESSION['project_news'][$_SESSION['languages'][4]] = 'News su questo argomento';
  
  $_SESSION['project_country'][$_SESSION['languages'][1]] = 'Country :';
  $_SESSION['project_country'][$_SESSION['languages'][2]] = 'Land :';
  $_SESSION['project_country'][$_SESSION['languages'][3]] = 'Pays :';
  $_SESSION['project_country'][$_SESSION['languages'][4]] = 'Paese :';
  
  $_SESSION['project_location'][$_SESSION['languages'][1]] = 'Location :';
  $_SESSION['project_location'][$_SESSION['languages'][2]] = 'Ort :';
  $_SESSION['project_location'][$_SESSION['languages'][3]] = 'Ville :';
  $_SESSION['project_location'][$_SESSION['languages'][4]] = 'Città :';
  
  $_SESSION['project_donation'][$_SESSION['languages'][1]] = 'Subfoundation :';
  $_SESSION['project_donation'][$_SESSION['languages'][2]] = 'Zustiftung :';
  $_SESSION['project_donation'][$_SESSION['languages'][3]] = 'Donation :';
  $_SESSION['project_donation'][$_SESSION['languages'][4]] = 'Donazione :';
  
  $_SESSION['project_name'][$_SESSION['languages'][1]] = 'Project Name :';
  $_SESSION['project_name'][$_SESSION['languages'][2]] = 'Projektname :';
  $_SESSION['project_name'][$_SESSION['languages'][3]] = 'Project Name :';
  $_SESSION['project_name'][$_SESSION['languages'][4]] = 'Nombre del proyecto :';
  
  $_SESSION['project_locpartn'][$_SESSION['languages'][1]] = 'Local Partner :';
  $_SESSION['project_locpartn'][$_SESSION['languages'][2]] = 'Lokaler Partner :';
  $_SESSION['project_locpartn'][$_SESSION['languages'][3]] = 'Local Partner :';
  $_SESSION['project_locpartn'][$_SESSION['languages'][4]] = 'Socio local :';
  
  $_SESSION['project_type'][$_SESSION['languages'][1]] = 'Kind of Project :';
  $_SESSION['project_type'][$_SESSION['languages'][2]] = 'Projektart :';
  $_SESSION['project_type'][$_SESSION['languages'][3]] = 'Kind of Project :';
  $_SESSION['project_type'][$_SESSION['languages'][4]] = 'Tipo de proyecto :';
  
  $_SESSION['project_run'][$_SESSION['languages'][1]] = 'Project Runtime :';
  $_SESSION['project_run'][$_SESSION['languages'][2]] = 'Projekt Laufzeit :';
  $_SESSION['project_run'][$_SESSION['languages'][3]] = 'Project Runtime :';
  $_SESSION['project_run'][$_SESSION['languages'][4]] = 'Duración del proyecto :';
  
  $_SESSION['project_status'][$_SESSION['languages'][1]] = 'Status :';
  $_SESSION['project_status'][$_SESSION['languages'][2]] = 'Projektstatus :';
  $_SESSION['project_status'][$_SESSION['languages'][3]] = 'Status :';
  $_SESSION['project_status'][$_SESSION['languages'][4]] = 'Situación :';
  
  $_SESSION['project_status_prep'][$_SESSION['languages'][1]] = 'In preparation';
  $_SESSION['project_status_prep'][$_SESSION['languages'][2]] = 'In Vorbereitung';
  $_SESSION['project_status_prep'][$_SESSION['languages'][3]] = 'In preparation';
  $_SESSION['project_status_prep'][$_SESSION['languages'][4]] = 'In preparation';
  
  $_SESSION['project_status_run'][$_SESSION['languages'][1]] = 'running';
  $_SESSION['project_status_run'][$_SESSION['languages'][2]] = 'laufend';
  $_SESSION['project_status_run'][$_SESSION['languages'][3]] = 'running';
  $_SESSION['project_status_run'][$_SESSION['languages'][4]] = 'running';
  
  $_SESSION['project_status_term'][$_SESSION['languages'][1]] = 'terminated';
  $_SESSION['project_status_term'][$_SESSION['languages'][2]] = 'beendet';
  $_SESSION['project_status_term'][$_SESSION['languages'][3]] = 'terminated';
  $_SESSION['project_status_term'][$_SESSION['languages'][4]] = 'terminated';
  
  $_SESSION['project_benef'][$_SESSION['languages'][1]] = 'Yearly beneficiaries :';
  $_SESSION['project_benef'][$_SESSION['languages'][2]] = 'Jährlich Begünstigte :';
  $_SESSION['project_benef'][$_SESSION['languages'][3]] = 'Yearly beneficiaries :';
  $_SESSION['project_benef'][$_SESSION['languages'][4]] = 'Beneficiarios anual :';
  
  $_SESSION['project_tot'][$_SESSION['languages'][1]] = 'Total Budget :';
  $_SESSION['project_tot'][$_SESSION['languages'][2]] = 'Total Projekt :';
  $_SESSION['project_tot'][$_SESSION['languages'][3]] = 'Total Budget :';
  $_SESSION['project_tot'][$_SESSION['languages'][4]] = 'Presupuesto total :';
  
  $_SESSION['project_limmat'][$_SESSION['languages'][1]] = 'Limmat Contribution :';
  $_SESSION['project_limmat'][$_SESSION['languages'][2]] = 'Beitrag Limmat :';
  $_SESSION['project_limmat'][$_SESSION['languages'][3]] = 'Limmat Contribution :';
  $_SESSION['project_limmat'][$_SESSION['languages'][4]] = 'Contribución Limmat :';
  
  $_SESSION['project_loc'][$_SESSION['languages'][1]] = 'Local Contribution :';
  $_SESSION['project_loc'][$_SESSION['languages'][2]] = 'Beitrag Lokaler Partner :';
  $_SESSION['project_loc'][$_SESSION['languages'][3]] = 'Local Contribution :';
  $_SESSION['project_loc'][$_SESSION['languages'][4]] = 'Contribución local :';
  
  $_SESSION['project_oda'][$_SESSION['languages'][1]] = 'Contribution ODAs :';
  $_SESSION['project_oda'][$_SESSION['languages'][2]] = 'Beitrag ODA :';
  $_SESSION['project_oda'][$_SESSION['languages'][3]] = 'Contribution ODAs :';
  $_SESSION['project_oda'][$_SESSION['languages'][4]] = 'Contribución ODAs :';
  
  $_SESSION['project_others'][$_SESSION['languages'][1]] = 'Contribution others :';
  $_SESSION['project_others'][$_SESSION['languages'][2]] = 'Beitrag Andere :';
  $_SESSION['project_others'][$_SESSION['languages'][3]] = 'Contribution others :';
  $_SESSION['project_others'][$_SESSION['languages'][4]] = 'Contribución otro :';
  
  $_SESSION['project_descr'][$_SESSION['languages'][1]] = 'Project Description :';
  $_SESSION['project_descr'][$_SESSION['languages'][2]] = 'Projektbeschreibung :';
  $_SESSION['project_descr'][$_SESSION['languages'][3]] = 'Project Description :';
  $_SESSION['project_descr'][$_SESSION['languages'][4]] = 'Descripción del proyecto :';
  
  $_SESSION['project_more'][$_SESSION['languages'][1]] = 'more :';
  $_SESSION['project_more'][$_SESSION['languages'][2]] = 'mehr :';
  $_SESSION['project_more'][$_SESSION['languages'][3]] = 'plus :';
  $_SESSION['project_more'][$_SESSION['languages'][4]] = 'piu :';
  
  $_SESSION['sendOK'][$_SESSION['languages'][1]] = 'Thanks, your message has been sent.';
  $_SESSION['sendOK'][$_SESSION['languages'][2]] = 'Danke, Ihre Meldung wurde gesendet.';
  $_SESSION['sendOK'][$_SESSION['languages'][3]] = 'Merci, votre message a été envoyé.';
  $_SESSION['sendOK'][$_SESSION['languages'][4]] = 'Grazie, il tuo messaggio è stato inviato';
  
  $_SESSION['sendFAILED'][$_SESSION['languages'][1]] = 'Error - Your message could not been sent! Please, try once again.';
  $_SESSION['sendFAILED'][$_SESSION['languages'][2]] = 'Fehler - Meldung konnte nicht gesendet werden! Bitte versuchen Sie es nochmal.';
  $_SESSION['sendFAILED'][$_SESSION['languages'][3]] = 'Erreur - Votre message n\'a pas été envoyé! Veuillez essayer encore une fois.';
  $_SESSION['sendFAILED'][$_SESSION['languages'][4]] = 'Errore - Il messaggio non è stato inviato! Riprova ancora una volta.';

  $_SESSION['doc_type_M'][$_SESSION['languages'][1]] = 'Months report zedev';
  $_SESSION['doc_type_M'][$_SESSION['languages'][2]] = 'Monatsauszug zedev';
  $_SESSION['doc_type_M'][$_SESSION['languages'][3]] = 'Relevé mensuel zedev';
  $_SESSION['doc_type_M'][$_SESSION['languages'][4]] = 'Estratto conto mensile zedev';
  
  $_SESSION['doc_type_F'][$_SESSION['languages'][1]] = 'Billing journal zedev';
  $_SESSION['doc_type_F'][$_SESSION['languages'][2]] = 'Fakturajournal zedev';
  $_SESSION['doc_type_F'][$_SESSION['languages'][3]] = 'Journal de facture zedev';
  $_SESSION['doc_type_F'][$_SESSION['languages'][4]] = 'giornal di fattura zedev';
  
  $_SESSION['doc_type_S'][$_SESSION['languages'][1]] = 'Statistic zedev';
  $_SESSION['doc_type_S'][$_SESSION['languages'][2]] = 'Statistik zedev';
  $_SESSION['doc_type_S'][$_SESSION['languages'][3]] = 'Statistiques zedev';
  $_SESSION['doc_type_S'][$_SESSION['languages'][4]] = 'Statistica zedev';
  
  $_SESSION['doc_type_Z'][$_SESSION['languages'][1]] = 'Moneyback zedev';
  $_SESSION['doc_type_Z'][$_SESSION['languages'][2]] = 'Zahlprämien zedev';
  $_SESSION['doc_type_Z'][$_SESSION['languages'][3]] = 'Primes de paiement zedev'; 
  $_SESSION['doc_type_Z'][$_SESSION['languages'][4]] = 'Rimborso zedev';
  
  $_SESSION['doc_type_R'][$_SESSION['languages'][2]] = 'Bonification zeev';
  $_SESSION['doc_type_R'][$_SESSION['languages'][2]] = 'Rückvergütungen zeev';
  $_SESSION['doc_type_R'][$_SESSION['languages'][3]] = 'Bonifications zeev';
  $_SESSION['doc_type_R'][$_SESSION['languages'][4]] = 'Bonificazione zeev';
  
  $_SESSION['cal_date'][$_SESSION['languages'][1]] = 'Calendar`s date';
  $_SESSION['cal_date'][$_SESSION['languages'][2]] = 'Kalender- Datum : Klicken Sie hier um Files älteren Datums anzuzeigen.';
  $_SESSION['cal_date'][$_SESSION['languages'][3]] = 'Date de calendrier';
  $_SESSION['cal_date'][$_SESSION['languages'][4]] = 'Date di calendario';
  
  $_SESSION['updated_on'][$_SESSION['languages'][1]] = 'updated on';
  $_SESSION['updated_on'][$_SESSION['languages'][2]] = 'aktualisiert am';
  $_SESSION['updated_on'][$_SESSION['languages'][3]] = 'mise à jour:';
  $_SESSION['updated_on'][$_SESSION['languages'][4]] = 'aggiornamento a';
  
  $_SESSION['verrechnet'][$_SESSION['languages'][1]] = 'billed YES / NO';
  $_SESSION['verrechnet'][$_SESSION['languages'][2]] = 'verrechnet JA / NEIN';
  $_SESSION['verrechnet'][$_SESSION['languages'][3]] = 'compensation OUI/NON';
  $_SESSION['verrechnet'][$_SESSION['languages'][4]] = 'carica  SI / NO';
  
  $_SESSION['open'][$_SESSION['languages'][1]] = 'open';
  $_SESSION['open'][$_SESSION['languages'][2]] = 'öffnen';
  $_SESSION['open'][$_SESSION['languages'][3]] = 'ouvrir';
  $_SESSION['open'][$_SESSION['languages'][4]] = 'aperto';
  
  $_SESSION['melden'][$_SESSION['languages'][1]] = 'announce';
  $_SESSION['melden'][$_SESSION['languages'][2]] = 'melden';
  $_SESSION['melden'][$_SESSION['languages'][3]] = 'annoncer';
  $_SESSION['melden'][$_SESSION['languages'][4]] = 'annunci';
  
  $_SESSION['acrobat_reader'][$_SESSION['languages'][1]] = 'In order to display the dokument,  Adobe reader is needed. You can use this link to download the program.';
  $_SESSION['acrobat_reader'][$_SESSION['languages'][2]] = 'Zum Anzeigen der Dokumente benötigen Sie den aktuellen Acrobat Reader von Adobe. Dieses Programm können Sie über folgenden Link herunterladen.';
  $_SESSION['acrobat_reader'][$_SESSION['languages'][3]] = 'Pour pouvoir regarder le document, il vous faut la version actuelle d&amp;Acrobat Reader de Adobe. Vous pouvez le télécharger par le lien (Download) suivant. ';
  $_SESSION['acrobat_reader'][$_SESSION['languages'][4]] = 'Per visualizzare il documento, è necessario la versione corrente di & amp; Adobe Acrobat Reader. È possibile scaricare tramite il link (download) di seguito.';
  
  $_SESSION['wiederholen'][$_SESSION['languages'][1]] = 'repeat';
  $_SESSION['wiederholen'][$_SESSION['languages'][2]] = 'wiederholen';
  $_SESSION['wiederholen'][$_SESSION['languages'][3]] = 'répéter';
  $_SESSION['wiederholen'][$_SESSION['languages'][4]] = 'replicare';
  
  $_SESSION['change'][$_SESSION['languages'][1]] = 'edit';
  $_SESSION['change'][$_SESSION['languages'][2]] = 'ändern';
  $_SESSION['change'][$_SESSION['languages'][3]] = 'modifier';
  $_SESSION['change'][$_SESSION['languages'][4]] = 'mutare';
  
  $_SESSION['openfile'][$_SESSION['languages'][1]] = 'Click here to open the file or to download it';
  $_SESSION['openfile'][$_SESSION['languages'][2]] = 'Clicken Sie hier um die Datei zu öffnen oder nachuntenladen';
  $_SESSION['openfile'][$_SESSION['languages'][3]] = 'Cliquer içí pour ouvrir ou télécharger le fichier';
  $_SESSION['openfile'][$_SESSION['languages'][4]] = 'Clicca qui per aprire o scaricare il file';
  
  $_SESSION['mfs_dm'][$_SESSION['languages'][1]] = 'Unmatch Message';
  $_SESSION['mfs_dm'][$_SESSION['languages'][2]] = 'Differenz- Meldung';
  $_SESSION['mfs_dm'][$_SESSION['languages'][3]] = 'Message de différences';
  $_SESSION['mfs_dm'][$_SESSION['languages'][4]] = 'Messagio de Differenza';

  $_SESSION['mfs_obj'][$_SESSION['languages'][1]] = 'Subject';
  $_SESSION['mfs_obj'][$_SESSION['languages'][2]] = 'Betreff';
  $_SESSION['mfs_obj'][$_SESSION['languages'][3]] = 'Objet';
  $_SESSION['mfs_obj'][$_SESSION['languages'][4]] = 'Quanto riquarda';

  $_SESSION['mfs_msg'][$_SESSION['languages'][1]] = 'Message';
  $_SESSION['mfs_msg'][$_SESSION['languages'][2]] = 'Meldung';
  $_SESSION['mfs_msg'][$_SESSION['languages'][3]] = 'Message';
  $_SESSION['mfs_msg'][$_SESSION['languages'][4]] = 'Messagio';

  $_SESSION['mfs_lief'][$_SESSION['languages'][1]] = 'Seller';
  $_SESSION['mfs_lief'][$_SESSION['languages'][2]] = 'Lieferant';
  $_SESSION['mfs_lief'][$_SESSION['languages'][3]] = 'Fournisseur';
  $_SESSION['mfs_lief'][$_SESSION['languages'][4]] = 'Fornitore';

  $_SESSION['mfs_rnr'][$_SESSION['languages'][1]] = 'Bill Nr./Text';
  $_SESSION['mfs_rnr'][$_SESSION['languages'][2]] = 'Rechnungs-Nr./Text';
  $_SESSION['mfs_rnr'][$_SESSION['languages'][3]] = 'No de Facture/Texte';
  $_SESSION['mfs_rnr'][$_SESSION['languages'][4]] = 'No. Fattura/Testo';

  $_SESSION['mfs_unb'][$_SESSION['languages'][1]] = 'Unjustified CHF';
  $_SESSION['mfs_unb'][$_SESSION['languages'][2]] = 'Unberechtigt CHF';
  $_SESSION['mfs_unb'][$_SESSION['languages'][3]] = 'Non-justifiée CHF';
  $_SESSION['mfs_unb'][$_SESSION['languages'][4]] = 'Ingiustificato CHF';

  $_SESSION['mfs_naus'][$_SESSION['languages'][1]] = "Unmentioned CHF";
  $_SESSION['mfs_naus'][$_SESSION['languages'][2]] = "Nicht aufgeführt CHF";
  $_SESSION['mfs_naus'][$_SESSION['languages'][3]] = "Non-mentionnée CHF";
  $_SESSION['mfs_naus'][$_SESSION['languages'][4]] = "non nell'elenco CHF";

  $_SESSION['mfs_mwst'][$_SESSION['languages'][1]] = "VAT amount CHF";
  $_SESSION['mfs_mwst'][$_SESSION['languages'][2]] = "MWSt-Betrag CHF";
  $_SESSION['mfs_mwst'][$_SESSION['languages'][3]] = "Montant TVA CHF";
  $_SESSION['mfs_mwst'][$_SESSION['languages'][4]] = "Importo dell'IVA";

  $_SESSION['mfs_begr'][$_SESSION['languages'][1]] = "Reason";
  $_SESSION['mfs_begr'][$_SESSION['languages'][2]] = "Begründung";
  $_SESSION['mfs_begr'][$_SESSION['languages'][3]] = "Justification";
  $_SESSION['mfs_begr'][$_SESSION['languages'][4]] = "Giustificazione";

  $_SESSION['mfs_abs'][$_SESSION['languages'][1]] = "send message";
  $_SESSION['mfs_abs'][$_SESSION['languages'][2]] = "Meldung absenden";
  $_SESSION['mfs_abs'][$_SESSION['languages'][3]] = "envoyer le message";
  $_SESSION['mfs_abs'][$_SESSION['languages'][4]] = "inviare il messaggio";

  $_SESSION['mfs_ins'][$_SESSION['languages'][1]] = "insert a message";
  $_SESSION['mfs_ins'][$_SESSION['languages'][2]] = "Einfügen einer Meldungspossition";
  $_SESSION['mfs_ins'][$_SESSION['languages'][3]] = "Insertion d'un élément de notification";
  $_SESSION['mfs_ins'][$_SESSION['languages'][4]] = "Inserimento di una voce di notifica";

  $_SESSION['mfs_inmsg'][$_SESSION['languages'][1]] = "Insert into the message";
  $_SESSION['mfs_inmsg'][$_SESSION['languages'][2]] = "In Meldung einfügen";
  $_SESSION['mfs_inmsg'][$_SESSION['languages'][3]] = "Insérer dans le message";
  $_SESSION['mfs_inmsg'][$_SESSION['languages'][4]] = "Inserire nel messaggio";

?>
