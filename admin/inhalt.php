<?php
/*
  History: 10-oct-13 GC -> modified upload call. Use now an idx to locate the correct file (up to 3 for one form)
           12-oct-13 GC -> add upload extension html in documents types
           28-mar-17 GC -> added to up 5 uploaders
*/
/* Must occur BEFORE any output */
if (isset($_GET['table']) && $_GET['table']=="exit") {
  session_unset();
  $_SESSION = array ();
  ob_clean();
  include('index.php');
  exit();
}
  
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <link href="include/css/CH_admin_styles.css" rel="stylesheet" type="text/css" media="all" />
    <meta http-equiv="Content-Type" content="text/html;"/>
    <title>ADMIN :: 
<?php
  if (isset($_GET['table']) && $_GET['table']=="exit") {
    session_unset();
    $_SESSION = array ();
    ob_clean();
    include('index.php');
    exit();
  }
echo $_SESSION['REG'];
$pre_form_name="" ;
if (isset($_GET['table'])) { // try to pre-build formname
  $pre_form_name = "_";
//  if (isset($_GET['mask']))
//    $pre_form_name = $_GET['mask'].$pre_form_name;  // can be insert_tablename or update_tablename
//  else
    $pre_form_name = "update_"; // default to update
  $pre_form_name = $pre_form_name.$_GET['table'];
}
?>    
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <script type="text/javascript" src="ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="ckeditor/config.js"></script>
    <script type="text/javascript" src="include/js/date.js"></script>
    <script type="text/javascript" src="include/js/AnchorPosition.js"></script>
    <script type="text/javascript" src="include/js/PopupWindow.js"></script>
    <script type="text/javascript" src="include/js/CalendarPopup.js"></script>
    <script type="text/javascript" src="../colorbox/jquery1.8.min.js"></script>
    <script type="text/javascript" src="include/js/plupload.full.js"></script>
    <script type="text/javascript">
    document.write(CalendarPopup_getStyles());
    // Custom example logic
    // we can upload up to 3 fields on a page, else add new instances
    $(function() {
      var iCount1, iCount2, iCount3, iCount4, iCount5;
      var uploader1 = new plupload.Uploader({
        runtimes : 'html5,flash',
        browse_button : 'pickfiles1',
        max_file_size : '600mb',
        chunk_size : '4mb',
        url : 'include/upload.php?idx=1',
        flash_swf_url : 'include/js/plupload.flash.swf',
        filters : [
          {title : "Image files", extensions : "jpg,jpeg,gif,png,bmp,tif"},
          {title : "audio files", extensions : "mp3,mp4,mpg,mpeg,avi,mov,qt,wav,wma,wmv"},
          {title : "Doc files", extensions : "html,pdf,doc,docx,xls,xlsx,ppt,pptx,txt"},
          {title : "Zip files", extensions : "zip"}
        ]
      });
      var uploader2 = new plupload.Uploader({
        runtimes : 'html5,flash',
        browse_button : 'pickfiles2',
        max_file_size : '600mb',
        chunk_size : '4mb',
        url : 'include/upload.php?idx=2',
        flash_swf_url : 'include/js/plupload.flash.swf',
        filters : [
          {title : "Image files", extensions : "jpg,jpeg,gif,png,bmp,tif"},
          {title : "audio files", extensions : "mp3,mp4,mpg,mpeg,avi,mov,qt,wav,wma,wmv"},
          {title : "Doc files", extensions : "html,pdf,doc,docx,xls,xlsx,ppt,pptx,txt"},
          {title : "Zip files", extensions : "zip"}
        ]
      });
      var uploader3 = new plupload.Uploader({
        runtimes : 'html5,flash',
        browse_button : 'pickfiles3',
        max_file_size : '600mb',
        chunk_size : '4mb',
        url : 'include/upload.php?idx=3',
        flash_swf_url : 'include/js/plupload.flash.swf',
        filters : [
          {title : "Image files", extensions : "jpg,jpeg,gif,png,bmp,tif"},
          {title : "audio files", extensions : "mp3,mp4,mpg,mpeg,avi,mov,qt,wav,wma,wmv"},
          {title : "Doc files", extensions : "html,pdf,doc,docx,xls,xlsx,ppt,pptx,txt"},
          {title : "Zip files", extensions : "zip"}
        ]
      });
      var uploader4 = new plupload.Uploader({
        runtimes : 'html5,flash',
        browse_button : 'pickfiles4',
        max_file_size : '600mb',
        chunk_size : '4mb',
        url : 'include/upload.php?idx=4',
        flash_swf_url : 'include/js/plupload.flash.swf',
        filters : [
          {title : "Image files", extensions : "jpg,jpeg,gif,png,bmp,tif"},
          {title : "audio files", extensions : "mp3,mp4,mpg,mpeg,avi,mov,qt,wav,wma,wmv"},
          {title : "Doc files", extensions : "html,pdf,doc,docx,xls,xlsx,ppt,pptx,txt"},
          {title : "Zip files", extensions : "zip"}
        ]
      });
      var uploader5 = new plupload.Uploader({
        runtimes : 'html5,flash',
        browse_button : 'pickfiles5',
        max_file_size : '600mb',
        chunk_size : '4mb',
        url : 'include/upload.php?idx=5',
        flash_swf_url : 'include/js/plupload.flash.swf',
        filters : [
          {title : "Image files", extensions : "jpg,jpeg,gif,png,bmp,tif"},
          {title : "audio files", extensions : "mp3,mp4,mpg,mpeg,avi,mov,qt,wav,wma,wmv"},
          {title : "Doc files", extensions : "html,pdf,doc,docx,xls,xlsx,ppt,pptx,txt"},
          {title : "Zip files", extensions : "zip"}
        ]
      });

      uploader1.bind('Init', function(up, params) {
        $('#filelist1').html("<div></div>");
        iCount1=0;
      });
      uploader2.bind('Init', function(up, params) {
        $('#filelist2').html("<div></div>");
        iCount2=0;
      });
      uploader3.bind('Init', function(up, params) {
        $('#filelist3').html("<div></div>");
        iCount3=0;
      });
      uploader4.bind('Init', function(up, params) {
        $('#filelist4').html("<div></div>");
        iCount4=0;
      });
      uploader5.bind('Init', function(up, params) {
        $('#filelist5').html("<div></div>");
        iCount5=0;
      });

      $('#maskTopBtn').click(function(e) {
        if (iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
      } else {
          if (iCount1>0) uploader1.start();
          else if (iCount2>0) uploader2.start();
          else if (iCount3>0) uploader3.start();
          else if (iCount4>0) uploader4.start();
          else if (iCount5>0) uploader5.start();
          e.preventDefault();
        }
      });
      
      $('#uploadfiles').click(function(e) {
        if (iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
      } else {
          if (iCount1>0) uploader1.start();
          else if (iCount2>0) uploader2.start();
          else if (iCount3>0) uploader3.start();
          else if (iCount4>0) uploader4.start();
          else if (iCount5>0) uploader5.start();
          e.preventDefault();
        }
      });

      uploader1.init();
      uploader2.init();
      uploader3.init();
      uploader4.init();
      uploader5.init();

      uploader1.bind('FilesAdded', function(up, files) {
        $.each(files, function(i, file) {
          if (iCount1>=1) {
            $('#filelist1').html("<div></div>");
            iCount1=0;
          }
          $('#filelist1').append(
            '<div id="' + file.id + '">' +
            file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
            iCount1=iCount1+1;
        });
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader2.bind('FilesAdded', function(up, files) {
        $.each(files, function(i, file) {
          if (iCount2>=1) {
            $('#filelist2').html("<div></div>");
            iCount2=0;
          }
          $('#filelist2').append(
            '<div id="' + file.id + '">' +
            file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
            iCount2=iCount2+1;
        });
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader3.bind('FilesAdded', function(up, files) {
        $.each(files, function(i, file) {
          if (iCount3>=1) {
            $('#filelist3').html("<div></div>");
            iCount3=0;
          }
          $('#filelist3').append(
            '<div id="' + file.id + '">' +
            file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
            iCount3=iCount3+1;
        });
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader4.bind('FilesAdded', function(up, files) {
        $.each(files, function(i, file) {
          if (iCount4>=1) {
            $('#filelist4').html("<div></div>");
            iCount4=0;
          }
          $('#filelist4').append(
            '<div id="' + file.id + '">' +
            file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
            iCount4=iCount4+1;
        });
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader5.bind('FilesAdded', function(up, files) {
        $.each(files, function(i, file) {
          if (iCount5>=1) {
            $('#filelist5').html("<div></div>");
            iCount5=0;
          }
          $('#filelist5').append(
            '<div id="' + file.id + '">' +
            file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
            iCount5=iCount5+1;
        });
        up.refresh(); // Reposition Flash/Silverlight
      });

      uploader1.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
      });
      uploader2.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
      });
      uploader3.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
      });
      uploader4.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
      });
      uploader5.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
      });

      uploader1.bind('Error', function(up, err) {
        $('#filelist1').append("<div>Error: " + err.code +
          ", Message: " + err.message +
          (err.file ? ", File: " + err.file.name : "") +
          "</div>"
          );
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader2.bind('Error', function(up, err) {
        $('#filelist2').append("<div>Error: " + err.code +
          ", Message: " + err.message +
          (err.file ? ", File: " + err.file.name : "") +
          "</div>"
          );
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader3.bind('Error', function(up, err) {
        $('#filelist3').append("<div>Error: " + err.code +
          ", Message: " + err.message +
          (err.file ? ", File: " + err.file.name : "") +
          "</div>"
          );
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader4.bind('Error', function(up, err) {
        $('#filelist4').append("<div>Error: " + err.code +
          ", Message: " + err.message +
          (err.file ? ", File: " + err.file.name : "") +
          "</div>"
          );
        up.refresh(); // Reposition Flash/Silverlight
      });
      uploader5.bind('Error', function(up, err) {
        $('#filelist5').append("<div>Error: " + err.code +
          ", Message: " + err.message +
          (err.file ? ", File: " + err.file.name : "") +
          "</div>"
          );
        up.refresh(); // Reposition Flash/Silverlight
      });

      uploader1.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
        iCount1=iCount1-1;
        if(iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
        }
        if (iCount2>0) uploader2.start();
        else if (iCount3>0) uploader3.start();
        else if (iCount4>0) uploader4.start();
        else if (iCount5>0) uploader5.start();
      });
      uploader2.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
        iCount2=iCount2-1;
        if(iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
        }
        if (iCount3>0) uploader3.start();
        else if (iCount4>0) uploader4.start();
        else if (iCount5>0) uploader5.start();
      });
      uploader3.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
        iCount3=iCount3-1;
        if(iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
        }
        if (iCount4>0) uploader4.start();
        else if (iCount5>0) uploader5.start();
      });
      uploader4.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
        iCount4=iCount4-1;
        if(iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
        }
        if (iCount5>0) uploader5.start();
      });
      uploader5.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
        iCount5=iCount5-1;
        if(iCount1==0 && iCount2==0 && iCount3==0 && iCount4==0 && iCount5==0) {
<?php
if(!empty($pre_form_name)) 
  echo "document.forms['".$pre_form_name."'].submit();";            
?>          
        }
      });
      $('.glyphicon-eye-open').mouseover(function(){
        $('#passupwd')[0].type = 'text';
        $('#passfpwd')[0].type = 'text';
      });
      $('.glyphicon-eye-open').mouseout(function(){
        $('#passupwd')[0].type = 'password';
        $('#passfpwd')[0].type = 'password';
      });
      $('.glyphicon-eye-open').click(function(){
        if (toggle) {
          $('#passupwd')[0].type = 'text';
          $('#passfpwd')[0].type = 'text';
        } else {
          $('#passupwd')[0].type = 'password';
          $('#passfpwd')[0].type = 'password';
        }
        toggle = !toggle;
      });
      $('.glyphicon-eye-open').mouseup(function(){
        $('#passupwd')[0].type = 'password';
        $('#passfpwd')[0].type = 'password';
      });
    });
    </script>
<?php

  if( isset($_POST['admlang']) ){
    $_SESSION['admlang'] = $_POST['admlang'];
    $meldung = array('msg'=>$_SESSION['lang_chg'][$_SESSION['admlang']],'type'=>"info");
  }
  
  if(!isset($_GET['table'])){
    $_GET['table'] = "";
  }
  $_SESSION['sid'] = session_id();
  
  include_once 'include/presentation.php';
  $presentation = new presentation();
  if (!$presentation->db_connect()) {
    echo "<p class='login_message'>".$db->status['msg']."</p>";
    include ("index.php"); // re-enter
    exit;
  }
  $presentation->calendar($_GET['table']);

?>  
  </head>
  <body class='admin_body'>
  <div class='admin_header'>
  <?php echo $_SESSION['admin_header'][$_SESSION['lang']].$_SESSION['REG']; ?> 
  </div>
<?php

  
  if(isset($_POST['csseditcontent'])){  // save new css content
    $csseditcontent = stripslashes($_POST['csseditcontent']);
    $meldung = array('msg'=>"",'type'=>"info");
    $fp = fopen($_SESSION['DOCUMENT_ROOT']."/css/CH_styles.css","w");
    if ($fp===false) 
      $meldung = array('msg'=>"Error opening file in write mode!",'type'=>"info");
    else
      if (fputs($fp,$csseditcontent)==false)
        $meldung = array('msg'=>"Save error!",'type'=>"info");
      else {
        if (fclose($fp)===false)
          $meldung = array('msg'=>"Error closing file!",'type'=>"info");
        else
          $meldung = array('msg'=>$_SESSION['Saved'][$_SESSION['lang']].".",'type'=>"info");
      }
  } 
  
  if (empty($meldung['msg'])) {
    if (!empty($db->status['msg']))
      $meldung = $db->status;
    $db->status = array('msg'=>"",'type'=>"info");
  }

  if (!empty($_POST['table'])) {
    $welche_werte = $presentation->table_struct[$_POST['table']]['fields'];
    $welche_feldtypen = $presentation->table_struct[$_POST['table']]['types'];
    $values = $presentation->values($welche_werte, $welche_feldtypen);
  }
  
  if( isset($_POST['insert']) && $presentation->table_forbidden_action($_POST['table'], "insert", false) == false ){
    $meldung = $presentation->insert($_POST['table'], $welche_werte, $values);
  }else if( isset($_POST['update']) && $presentation->table_forbidden_action($_POST['table'], "update", false) == false ){
    $meldung = $presentation->update($_POST['table'], $welche_werte, $values, $_POST['id']);
  }else if( isset($_POST['delete']) && $presentation->table_forbidden_action($_POST['table'], "delete", false) == false ){
    $meldung = $presentation->delete($_POST['table'], $_POST['id'], $_POST['id_name'],$welche_feldtypen);
  }else if( isset($_POST['insert']) ||  isset($_POST['update']) ||  isset($_POST['deletes']) ){
    echo "Fehlende Rechte";
  }
  
  $sprache = strtolower($_SESSION['lang']);
  $selected = "background: none repeat 0 0 lightblue; padding: 2px;";
  echo "<table width='100%' border='0'><tr><td valign='top' rowspan='2' width='10%'>";
  echo "<form id='form_sprache' name='form_sprache' method='post' action='logged_in.php'>
    <table class='language'>
      <tr>";
  if ($_SESSION['lng1']=="on")      
    echo  "<td style='".($sprache == $_SESSION['languages'][1]?$selected:"")."'>
          <input type='submit' name='admlang' value='".$_SESSION['languages'][1]."' 
           style='background-image:url(\"picts/icons/".$_SESSION['languages'][1].".png\"); 
                  background-repeat: no-repeat; 
                  background-position: center; 
                  color: transparent;'/>&nbsp;&nbsp;
          </td>";
  if ($_SESSION['lng2']=="on")      
    echo  "<td style='".($sprache == $_SESSION['languages'][2]?$selected:"")."'>
          <input type='submit' name='admlang' value='".$_SESSION['languages'][2]."' 
           style='background-image:url(\"picts/icons/".$_SESSION['languages'][2].".png\"); 
                  background-repeat: no-repeat; 
                  background-position: center; 
                  color: transparent;'/>&nbsp;&nbsp;
          </td>";
  if ($_SESSION['lng3']=="on")      
    echo  "<td style='".($sprache == $_SESSION['languages'][3]?$selected:"")."'>
          <input type='submit' name='admlang' value='".$_SESSION['languages'][3]."' 
           style='background-image:url(\"picts/icons/".$_SESSION['languages'][3].".png\"); 
                  background-repeat: no-repeat; 
                  background-position: center; 
                  color: transparent;'/>&nbsp;&nbsp;
          </td>";
  echo  "<td>&nbsp;&nbsp;</td>
        <td>Database ".$presentation->db." version ".$presentation->version."</td>
      </tr> 
    </table>
  </form>";
/*
   Alternate solution: a dropdown box
   
       <label>
      <select name='lang'>
        <option value='de' ".($sprache == "de"?"selected":"").">Deutsch</option>
        <option value='en' ".($sprache == "en"?"selected":"").">English</option>
        <option value='es' ".($sprache == "es"?"selected":"").">Espagnol</option>
      </select><input name='anzeigen' type='submit' value='>>' />
    </label>

*/  
  $presentation->table_list();
  echo "</td><td class='meldung' height='10'><span class='text'>Info:</span> <span class='".$meldung['type']."'>".$meldung['msg']."</span></td></tr><tr><td valign='top'>";

  $welche_spalte = $_GET['table']."_spalten";
  $welche_bezeichnungen = $_GET['table']."_spalten_bezeichnungen";
  $welche_feldtypen = $_GET['table']."_spalten_feldtypen";
  $welche_startwerte = $_GET['table']."_startwerte";
  $welche_infos = $_GET['table']."_infos";
  $welche_listenanzeige = $_GET['table']."_listenanzeige";
  
  //#############################  MASKEN
  if( isset($_GET['mask']) && $_GET['table'] <> "" && $_GET['formname'] <> "" && $_GET['action'] <> "" ){
    $presentation->mask($_GET['table'], $presentation->table_struct[$_GET['table']]['fields'], 
                         $presentation->table_struct[$_GET['table']]['labels'][$_SESSION['lang']], 
                         $presentation->table_struct[$_GET['table']]['types'], 
                         $presentation->table_struct[$_GET['table']]['infos'][$_SESSION['lang']], 
                         $presentation->table_struct[$_GET['table']]['defaults'], 
                         $_GET['mask'], $_GET['formname'], $_GET['action'], $_GET['id']);
  }
  
  //#############################  LISTEN
  if( isset($_GET['liste']) && $_GET['table'] <> "" ){
    $presentation->listing($_GET['table'], $presentation->table_struct[$_GET['table']]['fields'], 
                           $presentation->table_struct[$_GET['table']]['labels'][$_SESSION['lang']], 
                           $presentation->table_struct[$_GET['table']]['types'], 
                           $presentation->table_struct[$_GET['table']]['inlist'], $_GET['id'], $_GET['suchwert'], 
                           $presentation->table_defs[$_GET['table']]['cols_per_page'], 
                           $presentation->table_defs[$_GET['table']]['paths']);
  }

  if(isset($_GET['cssedit'])){
    $presentation->cssedit();
  }
  
  if(isset($_GET['tools']) || isset($_POST['toolsbtn1']) || isset($_POST['toolsbtn2'])){
    $presentation->tools();
  }
  
  echo "</td></tr></table>";
?>
  </body>
</html>