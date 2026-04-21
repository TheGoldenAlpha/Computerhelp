<?php
@session_start();
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED );
date_default_timezone_set('Europe/Zurich');
include('include/Mobile_Detect.php');
global $browser;
$browser = new Mobile_Detect();
setlocale(LC_ALL, 'de_CH.utf8');
 $_SESSION['MOVIE'] = 1;  // Un-comment§ when intro is never needed
// $_SESSION['POPUP'] = "";
unset($_SESSION['admlang']);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function insertTable($db,$lev) {
  global $picts_ix;
  global $texts_ix;
  global $logins_ix;
  global $forms_ix;
  $mainTable= $db->getHeaderLayoutTable($lev);
  if (!empty($mainTable['header'])) {
    echo $mainTable['header'];
    for ($i=0;$i<$mainTable['nrRows'];$i++) {
      echo $mainTable['row'][$i];
      for ($j=0;$j<$mainTable['nrCols'][$i];$j++) {
        echo $mainTable['col'][$i][$j];
        // content
        if (stripos($mainTable['name'][$i][$j],"<table")!==false) // when table in table, recurse
          insertTable($db,$lev+1);
        else { // normal cell
          $tableName = $mainTable['name'][$i][$j];
          if (strpos($tableName,"video")!==false) {
            $nameIdx = substr($tableName,strpos($tableName,"video")+5);
            $tableName = "video";
          } 
          switch ($tableName) {
          case 'mainmenu': echo $db->getHTMLmainMenu(); break;
          case 'diashow': $db->getHTMLdiashow($db->pictures[0]['pic_text']); break;
          case 'minidiashow': $db->getHTMLminidiashow($db->pictures[0]['pic_text']); break;
          case 'gallery': $db->getHTMLgallery(); break;
          case 'video': echo $db->getHTMLvideo($nameIdx); break;
          case 'menubar': echo $db->getHTMLmenuRow(); break;
          case 'banner': echo $db->getHTMLbanner(); break;
          case 'pdf': echo $db->getHTMLpdf(); break;
          case 'marquee': echo $db->getHTMLmarquee(); break;
          case 'ovl_picture': echo $db->getHTMLovl_picts(); break;
          case 'picture': echo $db->getHTMLpicts(); break;
          case 'picture_ix': echo $db->getHTMLpicts($picts_ix++);break;
          case 'picture_1': echo $db->getHTMLpicts(0); break;
          case 'picture_2': echo $db->getHTMLpicts(1); break;
          case 'pagemenu': $db->getHTMLpagemenu(); break;
          case 'projects': $db->getHTMLprojects(); break;
          case 'list': $db->getHTMLlist(); break;
          case 'day_info': $db->getHTMLdayInfo(); break;
          case 'day_info_ro': $db->getHTMLdayInfo(true); break;
          case 'call_list': $db->getHTMLcallList(); break;
          case 'call_archive': $db->getHTMLcallArchive("extra=0"); break;
          case 'call_orders': $db->getHTMLcallArchive("extra=1"); break;
          case 'user_list': $db->getHTMLuserList(); break;
          case 'news': $db->getHTMLnews(); break;
          case 'h_news': $db->getHTMLnewsHome(); break;
          case 'h_esmeralda': $db->getHTMLesmeraldaHome(); break;
          case 'resultpage': $db->getHTMLresultPage(); break;
          case 'title': $db->getHTMLpageItem('title'); break;
          case 'text': $db->getHTMLpageItem('text'); break;
          case 'text_ix': $db->getHTMLpageItem('text',$texts_ix++); break;
          case 'login_ix': $db->getHTMLpageItem('login',$logins_ix++); break;
          case 'form_ix': $db->getHTMLpageItem('form',$forms_ix++); break;
          default: echo $mainTable['name'][$i][$j]; break;  
          }
          
        }
        echo "</td>";
      }
      echo "</tr>";
    }
    echo "</tbody></table>";
  }
}


/*
include_once('admin/Zend/Locale.php');
$locale = new Zend_Locale();
echo $locale->toString();
echo $locale->getLanguage();
*/
if (isset($_GET['reset'])) $reset = $_GET['reset'];
if (isset($_GET['lang'])) $lang = $_GET['lang'];
if (isset($_POST['lang'])) $lang = $_POST['lang'];
if (isset($_GET['hmenu'])) $hmenu = $_GET['hmenu'];
if (isset($_POST['hmenu'])) $hmenu = $_POST['hmenu'];

// Remove both lines when working in production
 if (!isset($_SESSION['lang'])) $_SESSION['lang']="de";
 $_SESSION['reset']="yes";

// Set language
if( isset($lang) ){
  $_SESSION['lang'] = $lang;
  // force reread of menus
  unset($_SESSION['mainmenu']);
  unset($_SESSION['submenu']);
}
if (empty($_SESSION['lang'])) {
  $_SESSION['lang'] = "en";
}

if (!empty($reset)) { // when reset page
  unset($_SESSION['mainmenu']);
  unset($_SESSION['submenu']);
  unset($_SESSION['layouts']);
  unset($_SESSION['forms']);
}

// Set current page
if( isset($hmenu) ){
  $_SESSION['page'] = strtolower($hmenu);
} else {
  $_SESSION['page'] = "";
}
if (empty($_SESSION['page'])) {
  $_SESSION['page'] = "home";
}
//if (file_exists('include/web_params.php')) echo "started";
//exit;
include('include/web_params.php');
include('admin/include/db_handler.php');
include('include/presentation.php');
if($_SESSION['has_smsAPI'] == "yes")
  include_once('include/smsAPI.php');  
$db = new presentation();
$db->db_connect();

/* Special handling for posted infos */
if (!empty($_POST['dayInfos'])) {
  // Get user id from session
  $auth = $_SESSION['auth'];
  $id = $_SESSION["$auth"]["id"];
  // get company_id of user
  $result = $db->db_query("SELECT company_id from clients where id=$id", "company_id");
  $company_id = $result[0]['company_id'];
  if (!empty($db->dbquery_singleField("SELECT id FROM dictums WHERE name LIKE '$company_id'","id")))
  {
    $db->dbquery("UPDATE dictums SET ".$db->getFieldName("text")."='".$_POST['dayInfos']."' WHERE name LIKE '$company_id'");
  }else {
    $db->dbquery("INSERT INTO dictums (name,".$db->getFieldName("text").") VALUES ('$company_id','".$_POST['dayInfos']."');");
  }
}
if (isset($_POST['listFilter']) && empty($_POST['listFilter'])) {
  unset($_GET['listFilter']);
  global $listFilter;
  $listFilter = "";
}

/* check for password login */
if (!empty($_SESSION['logins'])) {
  $login_forms = array_keys($_SESSION['logins']);
  for($i=0;$i<sizeof($login_forms);$i++) {
    $auth = $login_forms[$i];
    $login_fields = $_SESSION['logins'][$auth];
    $uid = "";
    $pwd = "";
    for($j=0;$j<sizeof($login_fields);$j++) {
      $login_field=explode(";",$login_fields[$j]);
      $field_name = $login_field[0]; 
      if (isset($_POST[$field_name])) { //perhaps a logon ?
        if ($login_field[1]=="text") $uid=$_POST[$login_field[0]];
        if ($login_field[1]=="password") {
          $pwd=$_POST[$login_field[0]];
          $pwd_name= $login_field[0]; 
        }
      }
    }
    if (!empty($uid) && !empty($pwd)) { // try to check
      $_SESSION[$auth] = $db->db_uid_login($uid,$pwd,$pwd_name,"clients");
      if(empty($_SESSION[$auth])) { // failed
        unset($_SESSION[$auth]);
        unset($_SESSION['auth']);
        $_SESSION['send_mail_msg']= $_SESSION['loginPromt'][$_SESSION['lang']];
      }
      else {
        $_SESSION['auth']=$auth;
        $_SESSION['send_mail_msg']="";
        $client = $db->dbquery_singleField("SELECT is_client FROM companies WHERE id=".$_SESSION['login_user']['company_id'],"is_client");
        $client = (empty(trim($client))) ? false : true;
        $_SESSION['login_user']['is_client'] = $client;
      }
    }
  }
}
// Special forms
if (($_SESSION['page']=="mitglieder_bereich" || $_SESSION['page']=="mitglieder_bereich_msf") && isset($_POST['OK']) ) {
  $rec_id = $_SESSION[$_SESSION['auth']]['id'];
  $user_id = $_SESSION[$_SESSION['auth']]['uid'];
  $formname = $_SESSION['auth'];
  $old = $_POST['old_pwd'];
  $new = $_POST['new_pwd'];
  $level2 = !empty($_POST['level2']);
  if (!$db->db_uid_psw_change($rec_id,$user_id,$old,$new,$level2,'clients'))
    $_GET['msg'] = "";
  else {
    unset($_SESSION[$formname]);
  }
}
if (($_SESSION['page']=="lieferant_bereich" || $_SESSION['page']=="lieferant_bereich_msf") && isset($_POST['OK']) ) {
  $rec_id = $_SESSION[$_SESSION['auth']]['id'];
  $user_id = $_SESSION[$_SESSION['auth']]['uid'];
  $formname = $_SESSION['auth'];
  $old = $_POST['old_pwd'];
  $new = $_POST['new_pwd'];
  $level2 = !empty($_POST['level2']);
  if (!$db->db_uid_psw_change($rec_id,$user_id,$old,$new,$level2,'clients'))
    $_GET['msg'] = "";
  else {
    unset($_SESSION[$formname]);
  }
}
/* check for usual forms */
if (!empty($_SESSION['forms'])) {
  $usual_forms = array_keys($_SESSION['forms']);
  for($i=0;$i<sizeof($usual_forms);$i++) {
    $formname = $usual_forms[$i];
    $form_fields = $_SESSION['forms'][$formname];
    for($j=0;$j<sizeof($form_fields);$j++) {
      $form_field=explode(";",$form_fields[$j]);
      $form_field_name = $form_field[0]; 
      $form_field_type = $form_field[1]; 
      if (isset($_POST[$form_field_name])) { //
        $_SESSION[$formname][$form_field_name]=$_POST[$form_field_name];
        if ($form_field_type=="checkbox")
          $_SESSION[$formname][$form_field_name]="1";    // replace whatever by 0 and 1
      }
      else {
        // special for checkboxes: when not checked, they are not posted: simulate a post of value 0!
        if ($form_field_type=="checkbox")
          $_SESSION[$formname][$form_field_name]="0";
        else
          unset($_SESSION[$formname][$form_field_name]);
      }
    }
    if (empty($_SESSION[$formname])) {
      unset($_SESSION[$formname]);
      $formname="";
      $_SESSION['res_page'] = "";
    }
  }
}
$scriptfile = strtolower(basename($_SERVER['REQUEST_URI']));
if ($_SERVER['REQUEST_URI'][strlen($_SERVER['REQUEST_URI'])-1]=="/") $scriptfile=""; //when default index
$isHome = ( ($scriptfile=="index.php" && empty($_SERVER['QUERY_STRING'])) || empty($scriptfile));

// Here, we look for "menu" or "banner" in the table layouts. When some found, we assume banner and menu to be part of layouts, else the "old" fix layout applies.
$fixed_layout = $db->getNrOfRowsContaining(" layout_struct like '%menubar</td>%' OR layout_struct like '%banner</td>%'","page_layouts")==0;

// for main menu system
include("menu/LimmatMenu.php");
menu_init($db);
$menuRow = getHTMLmenu();
global $picts_ix;
$picts_ix = 0;
global $texts_ix;
$texts_ix = 0;
global $reload;
global $pageReload;
global $has_msg;
global $keepPosition;
if ($reload==0) $reload = -1;

if ($_SESSION['page']=="logoff") {
  $login_forms = array_keys($_SESSION['logins']);
  for($i=0;$i<sizeof($login_forms);$i++) {
    $auth = $login_forms[$i];
    unset($_SESSION[$auth]);
  }
  menu_visibility("login",true);
  menu_visibility("logoff",false);
  $_SESSION['page'] = "home";
}

menu_getPageID($_SESSION['page']); // get session pageID and pageLayoutID
if (empty($_SESSION['pageID']) && empty($_SESSION['pageLayoutID'])) { // when no layout found, take "standard" as default
  $_SESSION['pageLayoutID']= $db->getLayoutID("standard");
}
if ($browser->isMobile()) {
  // checkc if a mobile layout exists for the given layout_id
  $layout = $db->dbquery_singleField("SELECT layout_name FROM page_layouts WHERE id=".$_SESSION['pageLayoutID'],"layout_name");
  $mobileLayout = $db->dbquery_singleField("SELECT id FROM page_layouts WHERE layout_name LIKE '".$layout."_mobile'","id");
  if (!empty($mobileLayout)) $_SESSION['pageLayoutID'] = $mobileLayout;  
}
if (!empty($_SESSION['protected'])) { // When protected page
  $protection = explode(";",$_SESSION['protected']);
  if (!empty($protection[1])) $parms = explode("=",$protection[1]);
  // check for valid login
  $allowed = false;
  if (!empty($_SESSION[$protection[0]])) $allowed = true;
  if ($allowed && !empty($parms[0])) { // check parameters
    $c_types = explode(",",$parms[1]);  //eventually many
    // special when all are allowed
    if ($c_types[0]!="*") {
    $ok = false;
    $sql = "SELECT ".$db->getFieldName("client_type")." FROM clients_types WHERE id=".$_SESSION[$protection[0]][client_type];
    $result = $db->dbquery_firstrow($sql);
    $client_type = strtolower($result[$db->getFieldName("client_type")]);
    for($c=0;$c<sizeof($c_types);$c++) {
      if ($client_type==strtolower(trim($c_types[$c]))) $ok = true;
    }
    }
    else
      $ok = true;
    $allowed = $ok;
  }
  if (!$allowed) {
    if (isset($_GET['use_login_pg']))
      $_SESSION['page']=$_GET['use_login_pg'];
    else
    if (isset($_POST['login_error']))
      $_SESSION['page']=$_POST['login_error'];
    else
    $_SESSION['page']="must_login";
    menu_getPageID($_SESSION['page']);
  }
  else { // Login sucessfull, update client last login, change state of menu
    $db->dbquery("update clients set last_login=CURRENT_TIMESTAMP where id=".$_SESSION[$_SESSION['auth']]['id']);
    menu_visibility("login",false);
    menu_visibility("logoff",true);
    $menuRow = getHTMLmenu();
  }
}

if (!empty($_SESSION['forms'])) { // must process form
  $posted_forms = array_keys($_SESSION['forms']);
  foreach ($posted_forms as $p) {
    $db->DBupdate($p);
    unset($_SESSION['forms'][$p]);
  }
}

// Read the diashow picture in array $darstellung->pictures, depending on page
$db->pictures= array();
$db->getPictures();
if (strtolower($_SESSION['has_backgrounds'])=="yes") {
  $db->getBackgrounds();
  $hasBackground = (sizeof($db->getHomeBackground())!=0);
  if (sizeof($db->backgrounds)==0) {
    if ($hasBackground)
      $db->backgrounds = $db->getHomeBackground();
  }
  else {
    $hasBackground = true;
  }
}
else
  $hasBackground = false;

$meta = $db->getMetaInfo();

?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo $_SESSION['REG'] ?></title>
    <meta http-equiv="Content-Type" content="text/html;">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo (!empty($meta[0]['metadescr_adm'])) ? $meta[0]['metadescr_adm'] : "" ?>">
    <meta name="keywords" content="<?php echo (!empty($meta[0]['metakeywords_adm'])) ? $meta[0]['metakeywords_adm'] : "" ?>">
    <meta name="author" content="Computer Help AG, Gilles Corsini">
    <meta name="reply-to" content="<?php $_SESSION['E-MAIL1']?>">
    
    <link rel="canonical" href="<?php echo $_SESSION['WWW'];?>" />
    <link rel="alternate" hreflang="de" href="<?php echo $_SESSION['WWW'];?>/" /> 
    <link rel="shortcut icon" type="image/x-icon" href="pictures/icons/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="colorbox/colorbox.css" />
    <link rel="stylesheet" type="text/css" href="css/default.css" />
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
<?php
  if ($hasBackground) {
?>
    <link rel="stylesheet" type="text/css" href="css/supersized.css" media="screen" />
<?php
  }
?>    
    <link rel="stylesheet" type="text/css" href="css/bgstretcher.css"media="all" />
    <link rel="stylesheet" type="text/css" href="css/CH_styles.css"media="all" />
    <script type="text/javascript" src="js/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="js/restive.min.js"></script>  
    <script type="text/javascript" src="colorbox/jquery.colorbox.js"></script>
    <script type="text/javascript" src="js/modernizr.custom.js"></script>
    <script type="text/javascript" src="js/jquery.dlmenu.min.js"></script>
    <script type="text/javascript" src="js/jquery-bgstretcher-3.3.1.js"></script>
<?php
/*
      //Examples of how to assign the ColorBox event to elements
      $(".ajax").colorbox();
      $(".inline").colorbox({inline:true, width:"50%"});
      //Example of preserving a JavaScript event for inline calls.
      $("#click").click(function(){ 
        $('#click').css({"background-color":"#f00", "color":"#fff", "cursor":"inherit"}).text("Open this window again and this message will still be here.");
        return false;
      });
 
*/    
?>  
<?php
  if ($hasBackground) {
?>
    <script type="text/javascript" src="js/supersized.3.2.7.js"></script>
<?php
  }
?>    
    <script type="text/javascript" src="js/AnchorPosition.js"></script>
    <script type="text/javascript" src="js/PopupWindow.js"></script>
    <script type="text/javascript" src="js/date.js"></script>
    <script type="text/javascript" src="js/CalendarPopup.js"></script>
    <script type="text/javascript">document.write(CalendarPopup_getStyles());</script>
    <script type="text/javascript" src="js/LoginHandler.js"></script>
    <script type="text/javascript" src="js/md5.min.js"></script>
    <script type="text/javascript" src="js/FormatDate.js"></script>
    <script type="text/javascript" src="js/sorttable.js"></script>
    <script type="text/javascript" src="js/dropdownHandler.js"></script>
    <script type="text/javascript" src="js/js.cookie.js"></script>
    <script type="text/javascript" src="js/jquery.touchSwipe.min.js"></script>
    <script type="text/javascript" src="js/image-scale.min.js"></script>
    
    <link rel="icon" href="//files.wbk.kreativmedia.ch/d5/ca/d5cad34e-bca2-4e9f-90c8-5b56c383d389.ico" type="image/x-icon" />     
    <script type="text/javascript">

    var cal1x = new CalendarPopup("calendardiv");
    cal1x.setTodayText("Heute");
    cal1x.setMonthAbbreviations("Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez");
    cal1x.setMonthNames("Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember");
    cal1x.setWeekStartDay(1);

    // Build old pwds array
    var oldPwds = {};
<?php
  if (isset($login_forms) && sizeof($login_forms)>0) {
    for ($s=0;$s<sizeof($login_forms);$s++) {
      echo "oldPwds['".$login_forms[$s]."'] ={};\r\n";
      echo "oldPwds['".$login_forms[$s]."']['uid'] ='".$_SESSION[$login_forms[$s]]["uid"]."';\r\n";
      echo "oldPwds['".$login_forms[$s]."']['pwd'] ='".$_SESSION[$login_forms[$s]]["pwd"]."';\r\n";
      echo "oldPwds['".$login_forms[$s]."']['fpwd'] ='".$_SESSION[$login_forms[$s]]["fpwd"]."';\r\n";
    }
  }
?>  
  
    var imgComments = [
<?php
  for($i=0; $i<sizeof($db->pictures);$i++){   // get the eventual comments
    $pict_comment = $db->cleanEscaped($db->pictures[$i]['pic_text']);
    $pict_comment = str_replace("<p>","",$pict_comment);
    $pict_comment = str_replace("</p>","",$pict_comment);
    if ($i==0) 
      echo "'$pict_comment'";
    else
      echo ",'$pict_comment'";
  }
?>      
    ];  
  
    function playSound(filename){   
      window.scrollTo(0,0); // always to top
      document.getElementById("sound").innerHTML='<audio autoplay="autoplay"><source src="' + filename + '.mp3" type="audio/mpeg" /><source src="' + filename + '.ogg" type="audio/ogg" /><embed hidden="true" autostart="true" loop="false" src="' + filename +'.mp3" /></audio>';
      }  
  
    function mycallbackfunction() {
      var index = jQuery('.bgs-current').index();
/*      
      diashowWidth = $(".diashow").find(".bgstretcher").width();
      diashowHeight = $(".diashow").find(".bgstretcher").height();
      diashowYpos = $(".diashow").find(".bgstretcher").offset();
      $(".diashow_table").width(diashowWidth);
      $(".diashow_table").height(diashowHeight);
      $(".item_flex-container").width(diashowWidth);
*/      
/*      
<?php
  if ($_SESSION['lowbanner_align']=="body") {
?>
      $('.lowbanner').width($("body").width());
<?php
  }
  else if ($_SESSION['lowbanner_align']=="content") { 
?>
/* GC: do nothing, adapt later when needed
      $('.lowbanner').width(diashowWidth);
      if ($("body").offset().left>0) {
        if ($(".content").offset().left>0)
          $(".lowbanner").css("left",($(".content").offset().left-$("body").offset().left)+"px");
        else
          $(".lowbanner").css("left",($("body").offset().left)+"px");
      }
      else
        $(".lowbanner").css("left",($(".content").offset().left)+"px");
*/
<?php
  }
?>
//      $('.lowbanner').css('left',(diashowYpos.left)+"px")
      $('#diashowComment').html(imgComments[index]);
      if ($('#overlay')!== null) {
        // BgStretcher is RELATIVE positioned into .diashow_table
        // .overlay is ABSOLUTE positioned into .diashow_table
        // Then we must find back the offsets of bgStretcher relative to .diashow_table in order to get an 100% overlaying match
        // In facts, the left-margin is the half of the difference between width of .diashow_table and bgStetcher 
        diaShowPos = $('.diashow').position();
        diaShowTablePos = $('.diashow_table').position();
        diaShowTableHeight = $('.diashow_table').height();
        diaShowTableWidth = $('.diashow_table').width();
        overlayedPos = $('.bgstretcher').position();
        overlayedOffset = $('.bgstretcher').offset();
        overlayedHeight = $('.bgstretcher').height();
        overlayedWidth = $('.bgstretcher').width();
        overlayMarginLeft = (parseInt(diaShowTableWidth) - parseInt(overlayedWidth))/2;
        $(".overlay").css("top",parseInt(diaShowPos)+"px"); 
        $(".overlay").css("margin-left",(overlayMarginLeft)+"px"); 
        $("#overlay").css("height",overlayedHeight+"px"); 
        $("#overlay").css("width",overlayedWidth+"px"); 
        $("#overlay").css("display","block"); 
      }
    }

    function ShowNote(e) {
        var evt = e || window.event,x,y;
        // gets note1 element
        var note1=document.getElementById('diashowComment');
        if (note1==null) return;
        if (note1.textContent=='') return;
        note1.style.visibility='visible';
    }

    function HideNote(e,invisible) {
        var evt = e || window.event,x,y;
        // gets note1 element
        var note1=document.getElementById('diashowComment');
        if (note1.textContent=='') return;
        if (note1.parentElement.classList[0]=="minidiashow_comment")
          note1.style.visibility='visible';
        else
          note1.style.visibility='hidden';
    }

    function clearPWs() {
      $("#UserID").val("");
      $("#paswd").val("");
      $("#kundenID").val("");
      $("#KdPswd").val("");
      $("#old_pwd").val("");
      $("#new_pwd").val("");
      $("#rep_pwd").val("");
    }
    
    function lng() {
      return "<?php echo $_SESSION['lang'];?>";
    }
    
    function createArray(length) {
      var arr = new Array(length || 0), i = length;
      if (arguments.length > 1) {
        var args = Array.prototype.slice.call(arguments, 1);
        while(i--) arr[length-1 - i] = createArray.apply(this, args);
      }
    return arr;
    }
    
    function change_img(el,pic_name,ext) {
      var elem = $(el)[0].children[0].id;
      var oldname = $("#"+elem).attr("src");
      var pos = oldname.lastIndexOf("/");
      var newname = oldname.substring(0,pos+1) + pic_name + lng() +"."+ ext;
      $("#"+elem).attr("src",newname);
    }

    function reloadChanged(el,tgt) {
      var dependentDd = $("select#"+tgt);  
      dependentDd.find('option').remove();
      ddArray = eval(tgt);
      for(i=0;i<ddArray.length;i++) {
        if (ddArray[i][3]==el.value) {
          dependentDd.end();
          strToAdd = '<option value="'+ddArray[i][0]+'">'+ddArray[i][1]+' '+ddArray[i][2]+'</option>';
          dependentDd.append(strToAdd);
        }
      }
    }

    function autosize(textarea) {
        $(textarea).height(1); // temporarily shrink textarea so that scrollHeight returns content height when content does not fill textarea
        $(textarea).height($(textarea).prop("scrollHeight"));
    }
    
    function cssDimensions(element) {
      var cn = element.cloneNode();
      var div = document.createElement('div');
      div.appendChild(cn);
      div.style.display = 'none';
      document.body.appendChild(div);
      var cs = window.getComputedStyle
        ? getComputedStyle(cn, null)
        : cn.currentStyle;
      var ret = { width: cs.width, height: cs.height };
      document.body.removeChild(div);
      return ret;
    }
    
    var started = false;

    $(function() {
      $( '#dl-menu' ).dlmenu();
    });

    function debouncer( func , timeout ) {
       var timeoutID , timeout = timeout || 200;
       return function () {
          var scope = this , args = arguments;
          clearTimeout( timeoutID );
          timeoutID = setTimeout( function () {
              func.apply( scope , Array.prototype.slice.call( args ) );
          } , timeout );
       }
    }


    $( window ).resize( debouncer( function ( e ) {
/*      
      diashowOffset = cssDimensions($(".diashow").get(0));
      if (diashowOffset.height=="auto") 
        diashowHeight = $(".diashow").find(".bgstretcher").height();
      else
        if (parseInt(diashowOffset.height)>=0) diashowHeight = parseInt(diashowOffset.height);
      mycallbackfunction(); // in oreder to place overlay when diashow starts
      diashowWidth = $(".diashow").find(".bgstretcher").width();
      diashowHeight = $(".diashow").find(".bgstretcher").height();
      $(".diashow_table").width(diashowWidth);
      $(".diashow_table").height(diashowHeight);
*/        
    } ) );
    
    $(document).ready(function(){
      // restive must be on top to know about sizes
      $('body').restive({
        breakpoints: ['240', '320', '480', '640', '960', '1024',  '1136', '1280', '3000'],
        classes: ['rp-240', 'rp-320', 'rp-480', 'rp-640', 'rp-960', 'rp-1024', 'rp-1136', 'rp-1280', 'rp-3000'],
        turbo_classes: 'is_mobile=mobi,is_phone=phone,is_tablet=tablet,is_landscape=landscape',
        onPhone: function(){},
        force_dip: true
      });
      // BgStretcher in order to place diashow
      // To test BgStretcher3, insert test1.php (in bgStretcher3 directory)    
      //
      // -------------------------------------
      // Diashow settings
      // -------------------------------------
           
      $('.diashow').bgStretcher({
        images: [
<?php
  $pict_path = $db->table_defs['pictures']['paths']['original'];  // take original path
  for($i=0; $i<sizeof($db->pictures);$i++){
    $pict_name = $db->pictures[$i]['pic_name'];
    if ($i==0) 
      echo "'$pict_path$pict_name'";
    else
      echo ",'$pict_path$pict_name'";
  }
?>
        ],
        sizes: [
<?php
  $pict_path = $db->table_defs['pictures']['paths']['original'];  // take original path
  for($i=0; $i<sizeof($db->pictures);$i++){
    $pict_name = $db->pictures[$i]['pic_name'];
    list($mywidth, $myheight, $mytype, $myattr) = getimagesize($pict_path.$pict_name);
    if ($i==0) 
      echo "'$mywidth,$myheight'";
    else
      echo ",'$mywidth,$myheight'";
  }
?>      
        ],
        links: [
<?php
  for($i=0; $i<sizeof($db->pictures);$i++){   // get the eventual links
    $pict_link = $db->pictures[$i]['pic_link'];
    if ($i==0)
      echo "'$pict_link'";
    else
      echo ",'$pict_link'";
  }
?>      
        ],
        slideDirection: 'N',          // N, S, W, E, (if superSlide - NW, NE, SW, SE)
        imageWidth: 960,
        imageHeight: 338,
        resizeProportionally: true,
        slideShowSpeed: 1000,
        nextSlideDelay: 6000,
        transitionEffect: 'fade',     // none, fade, simpleSlide, superSlide
        sequenceMode: 'normal',
        buttonPrev: '#prev',
        buttonNext: '#next',          // back, random
        pagination: '#nav',
        anchoring: 'left top',
        anchoringImg: 'left top',
        sliderCallbackFunc: mycallbackfunction
      });
      // Adapt container table size
      $(".diashow_table").width($(".diashow").find(".bgstretcher").width());
      $(".diashow_table").height($(".diashow").find(".bgstretcher").height());
      $(".diashow_comment").width($(".diashow_table").width());
//
// -------------------------------------
// miniDiashow settings
// -------------------------------------

    $('.minidiashow').bgStretcher({
      images: [
<?php
  $pict_path = $db->table_defs['pictures']['paths']['original'];  // take mid path
  for($i=0; $i<sizeof($db->pictures);$i++){
    $pict_name = $db->pictures[$i]['pic_name'];
    if ($i==0) 
      echo "'$pict_path$pict_name'";
    else
      echo ",'$pict_path$pict_name'";
  }
?>      
      ],
      sizes: [
<?php
  $pict_path = $db->table_defs['pictures']['paths']['original'];  // take original path
  for($i=0; $i<sizeof($db->pictures);$i++){
    $pict_name = $db->pictures[$i]['pic_name'];
    list($mywidth, $myheight, $mytype, $myattr) = getimagesize($pict_path.$pict_name);
    if ($i==0) 
      echo "'$mywidth,$myheight'";
    else
      echo ",'$mywidth,$myheight'";
  }
?>      
      ],
      links: [
<?php
  for($i=0; $i<sizeof($db->pictures);$i++){   // get the eventual links
    $pict_link = $db->pictures[$i]['pic_link'];
    if ($i==0)
      echo "'$pict_link'";
    else
      echo ",'$pict_link'";
  }
?>  
      ],
      slideDirection: 'N',          // N, S, W, E, (if superSlide - NW, NE, SW, SE)
      imageWidth: 648,
      imageHeight: 190,
      resizeProportionally: true,
      slideShowSpeed: 6000,
      nextSlideDelay: 6000,
      transitionEffect: 'none',
      sequenceMode: 'normal',
      buttonPrev: '#prev',
      buttonNext: '#next',
      pagination: '#nav',
      anchoring: 'left center',
      anchoringImg: 'left top',
      sliderCallbackFunc: mycallbackfunction
    });
/*    
      $(".minidiashow_table").width($(".minidiashow").find(".bgstretcher").width());
      $(".minidiashow_table").height($(".minidiashow").find(".bgstretcher").height());
<?php
  if (!empty($_GET['Ypos'])) {
    echo "window.scrollTo(0,".$_GET['Ypos'].");";
  }
?>
      navOffset = $('.banner').height();
      navOffset += 2;
      width1 = $('.banner').width();
      titleOffset = navOffset+0;
      // check when wrapping
      if ($(".item_flex-item").nodeName!=undefined) {
        if ($(".item_flex-item:last-child").offset().top > $(".item_text").offset().top) {
          $(".item_flex-item:last-child").css("margin-right","auto");
          $(".item_flex-item:last-child").css("margin-left","0");
        }
      }
      $("#mainbody").width(Restive.viewportW());
      $(".item_flex-container").width(Restive.viewportW());
      $(".mobi img.bannerlogoimg").width(Restive.viewportW());
<?php
  if ($_SESSION['lowbanner_align']=="body") {
?>
      $('.lowbanner').width($("body").width());
<?php
  }
  else if ($_SESSION['lowbanner_align']=="content") { 
?>
      $('.lowbanner').width($(".content").width());
      if ($("body").offset().left>0) {
        if ($(".content").offset().left>0)
          $(".lowbanner").css("left",($(".content").offset().left-$("body").offset().left)+"px");
        else
          $(".lowbanner").css("left",($("body").offset().left)+"px");
      }
      else
        $(".lowbanner").css("left",($(".content").offset().left)+"px");
<?php
  }
?>
/*      
      leftMargin = 99999; // large enough to be replaced
      menuHeight = $("#limmat_mainMenu").height();
      if ($(".diashow").get(0)!=null) {
        diashowOffset = cssDimensions($(".diashow").get(0));
        if (diashowOffset.height=="auto") 
          diashowHeight = $(".diashow").find(".bgstretcher").height();
        else
          if (parseInt(diashowOffset.height)>=0) diashowHeight = parseInt(diashowOffset.height);
        $('.item_text').css('top',(diashowHeight+20)+"px");
      }
      $(".item_flex-container").css("width",(width1)+"px");
      contentOffset = $(".content").height();
      textHeight = $('.item_text').height();
      if (textHeight>0) { // When has a text area, use its height and position to place the banner as absolute element
        textOffset = $('.item_text').offset();  
        $('.lowbanner').css('top',(textOffset.top+textHeight)+"px");
        if (textOffset.left<leftMargin) leftMargin = textOffset.left; 
      }
      else { // when no text part, look if a diashow
        if (diashowHeight>0) {
          diashowYpos = $(".diashow").find(".bgstretcher").offset();
          $('.lowbanner').css('top',(diashowYpos.top+diashowHeight)+"px");
          if (diashowYpos.left<leftMargin) leftMargin = diashowYpos.left; 
        }
        else {  // when no diashow, look if menu
          if (menuHeight>0) {
            menuYpos = $("#limmat_mainMenu").offset();
            $('.lowbanner').css('top',(menuYpos.top+menuHeight)+"px");
            if (menuYpos.left<leftMargin) leftMargin = menuYpos.left; 
          }
          else {  // at least a banner ?
            if (navOffset>0) {
              bannerYpos = $(".banner").offset();
              $('.lowbanner').css('top',(bannerYpos.top+navOffset)+"px");
              if (bannerYpos.left<leftMargin) leftMargin = bannerYpos.left; 
            }
            else {  // NOTHING ! place at top... 
              $('.lowbanner').css('top',(20)+"px");
              leftMargin = 0;
            }
          }
        }  
      }
      $('.lowbanner').css('width',(width1)+"px")
*/      
<?php  
  if (!$browser->isMobile()) {                 
?>
      if ($(".diashow").get(0)!=null)
        diashowWidth = $(".diashow").find(".bgstretcher").width();
      else
        diashowWidth = $('.banner').width();
      itemWidth = ($(".item_text")!=null) ? $(".item_text").css('width') : diashowWidth;
      $(".item_flex-container").width(itemWidth);
      $(".lowbanner").width(itemWidth);
//      $(".limmat_mainMenu").width(itemWidth);
//      $(".pictable").width(itemWidth);
      leftMargin = 9999; // large enough to be replaced
      bannerHeight = $('.banner').height();
      if (bannerHeight>0) {
        bannerYpos = $(".banner").offset().left-$("body").offset().left;
        if (bannerYpos<leftMargin) leftMargin = bannerYpos; 
        $('.lowbanner').css('left',(leftMargin)+"px")
      }
<?php  
  }
?>

<?php
  if ($_SESSION['has_overlay']) {
?>
      if ($(".diashow").get(0)==null) { // when no diashow, it will be used there
        overlayedOffset = parseInt($('.ovl_pict').css('top'));
        overlayedHeight = $('#overlayed').height();
        overlayedWidth = $('#overlayed').width();
        titleOffset = $('.banner').height();
        $(".overlay").css("top",(titleOffset-overlayedOffset-5)+"px"); 
        $("#overlay").css("height",overlayedHeight+"px"); 
        $("#overlay").css("width",overlayedWidth+"px"); 
        $("#overlay").css("display","block"); 
      } else {
        $("#overlay").css("display","none"); 
      }
      mycallbackfunction(); // in oreder to place overlay when diashow starts
<?php  
  }
  if ($browser->isMobile()) {     
?>
      if ($(".diashow").get(0)!=null)
        diashowWidth = $(".diashow").find(".bgstretcher").width();
      else
        diashowWidth = $('body').width();
      $("#KontaktForm").width(diashowWidth);
      $(".content").width(diashowWidth-20);
/*
      maxwidth = $('body').width()
      if ($("img.bannerlogoimg").nodeName != undefined)
        $("img.bannerlogoimg").imageScale();
      if ($('#column_1').nodeName != undefined) {
        $('#column_1').css('width',maxwidth+"px");
      }
      if ($('#column_2').nodeName != undefined) {
        $('#column_2').css('width',maxwidth+"px");
      }
      if ($('#column_3').nodeName != undefined) {
        $('#column_3').css('width',maxwidth+"px");
      }
      if ($('#KontaktForm').nodeName != undefined) {
        $('#KontaktForm').css('width',maxwidth+"px");
      }
      if ($('textarea').nodeName != undefined) {
      $('.mobi textarea').css('width',"90%");
      }
*/      
//      $('#nav_menu').css('top',(navOffset-40)+"px");     // special to have it full at top
//      screenBottom = Restive.viewportH()/Restive.getPixelRatio();
//      $(".lowbannertable").css({'top' : (screenBottom-30) + 'px'});
//      $('.diashow').css('width',width1+"px");
//      $('#nav_menu').css('top',navOffset+"px");

//      alert('Mobile is Active and Viewport is '+Restive.viewportW()+'px x '+Restive.viewportH()+'px');
//      alert('Mobile is width and height : '+$('body').width()+'px x '+$('body').height()+'px');
//      alert('Body Ratio = '+Restive.getPixelRatio()+" class="+$('body').attr('class'));
//      alert('Banner width and height : '+$('.banner').width()+'px x '+$('.banner').height()+'px');
//      alert('Top/Left of version : '+$('#cms_version').css('top')+$('#cms_version').css('left'));
//      alert('Diashow width and height : '+$('.diashow').width()+'px x '+$('.diashow').height()+'px');
//      alert('Screen w X h, viewport width, PixelsRatio and class: '+Restive.screenW()+'px X '+Restive.screenH()+'px, '+Restive.viewportW()+'px R='+Restive.getPixelRatio()+"C="+$('body').attr('class'));
//      alert('Content : '+$('.content').width());
//      alert('Nav offset : '+navOffset);
<?php
  }
?>
      //Examples of how to assign the ColorBox event to elements
      // $("a.gallery1").colorbox({rel:'gallery1'});

      colorboxwidth = (document.getElementById("mycolorbox")!=null) ? $("#mycolorbox").width() : "80%";
      $(".gallery1").colorbox({rel:'gallery1', innerWidth :"80%", current:"Bild {current} von {total}"});
      $(".inline").colorbox({inline:true, innerWidth :colorboxwidth});
      if (document.getElementById("txtCaptcha")!=null) GenCaptcha();
      $("#FMUserID").val("<?php echo $_SESSION['login_mitglied']['uid']; ?>");
      $("#FLUserID").val("<?php echo $_SESSION['login_lieferant']['uid']; ?>");
      $("#cboxContent").swipe( {
        swipe:function(event,direction,distance,duration,fingerCount,fingerData) {
          if (direction=="left")
            $.colorbox.prev();
          else if (direction=="right")
            $.colorbox.next();
        }
      });                       

      var t = setTimeout("clearPWs()",200);
<?php
  if ($_SESSION['page'] == "home") {
//    echo "  $('.content').css('background-color','transparent');";
  }
?>
// Init supersized
<?php
  if ($hasBackground) {
?>
      $('body').bgStretcher({
        images: [
<?php
  $pict_path = $db->table_defs['backgrounds']['paths']['original'];  // take original path 
  for($i=0; $i<sizeof($db->backgrounds);$i++){
    $pict_name = $db->backgrounds[$i]['name'];
    if ($i==0) 
      echo "'$pict_path$pict_name'";
    else
      echo ",'$pict_path$pict_name'";
  }
?>
        ],
        sizes: [
<?php
  $pict_path = $db->table_defs['backgrounds']['paths']['original'];  // take original path
  for($i=0; $i<sizeof($db->backgrounds);$i++){
    $pict_name = $db->pictures[$i]['name'];
    list($mywidth, $myheight, $mytype, $myattr) = getimagesize($pict_path.$pict_name);
    if ($i==0) 
      echo "'$mywidth,$myheight'";
    else
      echo ",'$mywidth,$myheight'";
  }
?>      
        ],
        links: [
<?php
  for($i=0; $i<sizeof($db->backgrounds);$i++){   // get the eventual links
    $pict_link = "#";
    if ($i==0)
      echo "'$pict_link'";
    else
      echo ",'$pict_link'";
  }
?>      
          ],
        slideDirection: 'N',          // N, S, W, E, (if superSlide - NW, NE, SW, SE)
        imageWidth: 960,
        imageHeight: 338,
        resizeProportionally: false,
        slideShowSpeed: 1000,
        nextSlideDelay: 6000,
        transitionEffect: 'fade'      // none, fade, simpleSlide, superSlide
      });
<?php
    }        
?>      

  });
    
  var newwin;

  var elementInDom = function( el ) {
    var element =  document.getElementById(el);
    if (typeof(element) != 'undefined' && element != null)
     return true;
    else {
    var element =  document.getElementsByName(el);
    if (typeof(element[0]) != 'undefined' && element[0] != null)
     return true;
    }
    return false;
  }

  function popup(url,name,eigenschaften) {
    newwin = window.open(url,name,eigenschaften);
    newwin.focus();
  }

  function pageReload(url,delay) {
    setTimeout(function(){window.location.href = 'index.php?hmenu='+url;},delay);
  }

  function redirectIdle(url,delay) {
    var t;
    var u;
    var d;
    u = url;
    d = delay;
    window.onload = resetTimer;
    window.onmousemove = resetTimer;
    window.onmousedown = resetTimer; // catches touchscreen presses
    window.onclick = resetTimer;     // catches touchpad clicks
    window.onscroll = resetTimer;    // catches scrolling with arrow keys
    window.onkeypress = resetTimer;
    
    function resetTimer() {
      clearTimeout(t);
      t = setTimeout(function(){window.location.href = ''+u+'';}, d);  // delay is in milliseconds
    }
  }

  // check the passed string to be a valid date via regex   ^(([0-2]?\d{1})|([3][0-1]{1}))[\/.-](([0-1]?[0-2])|([1-9]?))[\/.-](([1]{1}[9]{1}[9]{1}\d{1})|([2-9]{1}\d{3}))$
  function isInvalidDate(str) {
    var expr = new RegExp("^(0?[1-9]|[12][0-9]|3[01])[\\/.-](0?[1-9]|1[012])[\\/.-]((19|20)\\d\\d)$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed string to be a valid phone nr via regex
  function isInvalidPhone(str) {
    var expr = new RegExp("^([+]([0-9]{2}[1-9][0-9]{8,})?|[0][0-9]{8,})$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed string to be a valid Address nr (text nr) via regex
  function isInvalidAddress(str) {
    var expr = new RegExp("^[a-z A-Z]*[0-9.-]*$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed string to be a valid ZIPcity (ZIP (4digigts) space text) via regex
  //  var expr = new RegExp("^([A-Z]{1,2}[-])?[0-9]{4,}[ ]*[. a-zA-ZäöïëüÄË??ÖÜÉéÈèçÙùÀà-]*$");
  function isInvalidZIPcity(str) {
    var expr = new RegExp("^.*$");
  //  var expr = new RegExp("^[0-9]{4,}[ ]*[a-zA-Z]*$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed string to be a valid ZIPcity (ZIP (4digigts) space text) via regex
  function isInvalidText(str) {
    var expr = new RegExp("^.*$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed string to be a valid ZIPcity (ZIP (4digigts) space text) via regex
  function isInvalidInteger(str) {
    var expr = new RegExp("^[0-9]*$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed string to be a valid eMail address via regex
  function isInvalidEmail(str) {
    var expr = new RegExp("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$");
    if (str=="") return true;
    return !expr.test(str);
  }

  // check the passed input field and alert with passed message when empty
  function checkfieldnotempty(field,msg) {
    if (document.getElementsByName(field)[0].value.trim()=="") {
      document.getElementsByName(field)[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check the passed input field and alert with passed message when not a valid time
  function checktimefield(field,from,to,msg) {
    valid = true;
    if (document.getElementsByName(field)[0].value.trim()=="")
      valid = false;
    else {
      time = document.getElementsByName(field)[0].value.trim();
      timeArray = time.split(":");
      fromArray = from.split(":");
      toArray = to.split(":");
      hours = parseInt(timeArray[0]);
      mins  = parseInt(timeArray[1]);
      if (hours=="NaN" || mins=="NaN") valid=false; 
      if (valid) valid = (hours>=fromArray[0] && hours<=toArray[0]);
      if (valid) valid = (mins>=fromArray[1] && mins<=toArray[1]);
    }
    if (!valid) {
      document.getElementsByName(field)[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check the passed input field when the other passed field is not empty and alert with passed message when empty    
  function checkfieldwhen(field,testfield,msg) {
    if (document.getElementsByName(testfield)[0].value.trim()=="") return true; // when test field empty, doesnt matter
    if (document.getElementsByName(field)[0].value.trim()=="") {
      document.getElementsByName(field)[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check the passed input field when the other passed field is not empty and alert with passed message when empty    
  function checkintegerwhen(field,testfield,msg) {
    if (document.getElementsByName(testfield)[0].value.trim()=="") return true; // when test field empty, doesnt matter
    return checkinteger(field,msg,true);
  }

  // check the passed input field for an integer and alert with passed message when empty
  // it may also alert when empty, if flag is set
  function checkinteger(field,msg,flag) {
    var val = document.getElementsByName(field)[0].value;
    val = val.replace(/ /g,"");
    if (!flag && val=="") return true; //ok, when empty and flag not set
    if (isInvalidInteger(val)) {
      document.getElementsByName(field)[0].focus();
      lmsg = msg.replace("<BR>","\n");
      alert(lmsg);
      return false;
    }
    return true;
  }

  // check the input field named "name" and alert with passed message when empty
  function checkname(msg) {
    if (document.getElementsByName("name")[0].value.trim()=="") {
      document.getElementsByName("name")[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check the input field named "forname" and alert with passed message when empty
  function checkforname(msg) {
    if (document.getElementsByName("forname")[0].value.trim()=="") {
      document.getElementsByName("forname")[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check the input field named "fullname" and alert with passed message when empty
  function checkfullname(msg) {
    if (document.getElementsByName("fullname")[0].value.trim()=="") {
      document.getElementsByName("fullname")[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check the passed input field and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkdate(field,msg,flag) {
    var val = document.getElementsByName(field)[0].value;
    val = val.replace(/ /g,"");
    if (!flag && val=="") return true; //ok, when empty and flag not set
    if (isInvalidDate(val)) {
      document.getElementsByName(field)[0].focus();
      lmsg = msg.replace("<BR>","\n");
      alert(lmsg);
      return false;
    }
    return true;
  }

  // check the input field named "email" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkmail(msg,flag) {
    var email = document.getElementsByName("email")[0].value;
    email = email.replace(/ /g,"");
    if (!flag && email=="") return true; //ok, when empty and flag not set
    if (isInvalidEmail(email)) {
      document.getElementsByName("email")[0].focus();
      alert(msg);
      return false;
    }
    return true;
    
  }

  // check the input field named "email" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkfieldmail(field,msg,flag) {
    var email = document.getElementsByName(field)[0].value;
    email = email.replace(/ /g,"");
    if (!flag && email=="") return true; //ok, when empty and flag not set
    if (isInvalidEmail(email)) {
      document.getElementsByName(field)[0].focus();
      alert(msg);
      return false;
    }
    return true;
    
  }

  // check the input field named "address" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkaddress(msg,flag) {
    var addr = document.getElementsByName("address")[0].value;
    if (!flag && addr.replace(/ /g,"")=="") return true; //ok, when empty and flag not set
    if (isInvalidAddress(addr)) {
      document.getElementsByName("address")[0].focus();
      alert(msg);
      return false;
    }
    return true;
    
  }

  // check the input field named "ZIPcity" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkZIPcity(msg,flag) {
    var txt = document.getElementsByName("ZIPcity")[0].value;
    if (!flag && txt.replace(/ /g,"")=="") return true; //ok, when empty and flag not set
    if (isInvalidZIPcity(txt)) {
      document.getElementsByName("ZIPcity")[0].focus();
      alert(msg);
      return false;
    }
    return true;
    
  }

  // check the input field named "homeclub" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkhomeclub(msg,flag) {
    var txt = document.getElementsByName("homeclub")[0].value;
    if (!flag && txt.replace(/ /g,"")=="") return true; //ok, when empty and flag not set
    if (isInvalidText(txt)) {
      document.getElementsByName("homeclub")[0].focus();
      alert(msg);
      return false;
    }
    return true;
    
  }

  // check the input field named "handicap" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkhandicap(msg,flag) {
    var txt = document.getElementsByName("handicap")[0].value;
    if (!flag && txt.replace(/ /g,"")=="") return true; //ok, when empty and flag not set
    if (isInvalidText(txt)) {
      document.getElementsByName("handicap")[0].focus();
      alert(msg);
      return false;
    }
    return true;
    
  }

  // check the input field named "phone" and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkphone(msg,flag) {
    var phone = document.getElementsByName("phone")[0].value;
    phone = phone.replace(/ /g,"");
    if (!flag && phone=="") return true; //ok, when empty and flag not set
    if (isInvalidPhone(phone)) {
      document.getElementsByName("phone")[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // checks phone number of a field and alert with passed message when not correctly formated 
  // it may also alert when empty, if flag is set
  function checkfieldphone(field,msg,flag) {
    var phone = document.getElementsByName(field)[0].value;
    phone = phone.replace(/ /g,"");
    if (!flag && phone=="") return true; //ok, when empty and flag not set
    if (isInvalidPhone(phone)) {
      document.getElementsByName(field)[0].focus();
      alert(msg);
      return false;
    }
    return true;
  }

  // check if at least one tournament is checked and alert with passed message when not 
  // it may also alert when empty, if flag is set
  function checktournament(msg,flag) {
    count=0;
    count = parseInt(document.getElementsByName("nrspecials")[0].value)+parseInt(document.getElementsByName("nrfinals")[0].value);
    found = false;
    varname="";
    for (i=0;i<count;i++) {
      varname = "game"+String(i);
      if(elementInDom(varname)) {
        if(document.getElementById(varname).checked) found = true;
      }
    }
    if (count>0) {
      document.getElementById("game0").focus();
      if (!found) {
        alert(msg);
      }
    }
    return found;
  }

  function checkPasswordChange(type, isF, field1, field2, field3, msg1, msg2) {
    // first check old password
    oldpsw = document.getElementsByName(field1.name)[0].value;
    oldpsw = MD5(MD5(base64_encode(oldPwds[type]['uid']+oldpsw)));
    ok = (isF) ? (oldpsw==oldPwds[type]['fpwd']) : (oldpsw==oldPwds[type]['pwd']);
    if (!ok) {
      alert(msg1);
      return false;
    }
    if (document.getElementsByName(field2.name)[0].value!=document.getElementsByName(field3.name)[0].value) {
      alert(msg2);
      return false;
    }
    newpsw = document.getElementsByName(field2.name)[0].value;
    newpsw = MD5(MD5(base64_encode(oldPwds[type]['uid']+newpsw)));
    document.getElementsByName(field1.name)[0].value = oldpsw;
    document.getElementsByName(field2.name)[0].value = newpsw;
    document.getElementsByName(field3.name)[0].value = newpsw;
    return true;
  }

  function chgPsw(fld1,fld2,fld3) {
    uid = document.getElementsByName(fld1.name)[0].value;
    uid = uid.trim();
    psw = document.getElementsByName(fld2.name)[0].value;
    psw = psw.trim();
    if (uid+psw!="" && psw!="") {
      newpsw = MD5(MD5(base64_encode(uid+psw)));
      document.getElementsByName(fld3.name)[0].value = newpsw;
    }
    return true;
  }


  // Remove the spaces from the entered and generated code
  function removeSpaces(string){
      return string.split(' ').join('');
  }
       
  // Validate the Entered input against the generated security code function
  // An alert with msg1 is generated when captcha is empty or msg2 when captcha is not matching   
  function checkcaptcha(msg1,msg2){
    var str1 = removeSpaces(document.getElementById('txtCaptcha').value);
    var str2 = removeSpaces(document.getElementById('captcha_code').value);
    if (str2=="") {
      alert(msg1);
      return false;
    }
    if (str1 == str2){
      return true;   
    }else{
      alert(msg2);
      return false;
    }
  }
   
  // Generate a random input security code function  
  function GenCaptcha() {
    a = Math.ceil(Math.random() * 9)+ '';
    b = Math.ceil(Math.random() * 9)+ '';      
       
    code = a + "+" + b + "=";
    document.getElementById("txtCaptcha").value = eval(a)+eval(b);
    document.getElementById("txtCaptchaDiv").innerHTML = code;
    var x = elementInDom("fullname"); 
    if (elementInDom("fullname"))
      document.getElementsByName("fullname")[0].focus();
    else {  //when no fullname we have to choose between name and forname
            //when nothing specified, we assume name is before forname, but the one with tabindex=-1 is winning
      if (elementInDom("name")) document.getElementsByName("name")[0].focus();
      if (elementInDom("forname") && document.getElementsByName("forname")[0].tabIndex==-1) document.getElementsByName("forname")[0].focus();
    }
  }

  function setLanguage(lng) {
    document.getElementById("lang").value = lng;
    document.getElementById("setlang").submit();
  }
    </script>    
  </head>
  <body>
    <div id="mainbody" >
<?php
  if ($isHome && !isset($_SESSION['MOVIE'])) {
    $_SESSION['MOVIE'] = 1;
    echo "<body class='intro'>
            <div id='intro'>".$db->getHTMLvideo(0).
           "<input type='button' value='".$_SESSION['skip_intro'][$_SESSION['lang']]."' onclick='javascript:window.location.href=\"index.php\";'>
            </div></body></html>";
    exit;
  }

/* main structure: 

      In the fixed layout structure, all web pages are build on the same layout:
       ________________________
      |Banner                  |
      |________________________|
      |LimmatMenu              |
      |________________________|
      |content                 |
      |                        |
      |                        |
      |________________________|

      The whole table is named "master". The divers rows styles are "banner", "limmat_menu" and "content".
      the content table may contains a hierarchy of tables (done via insertTable function )
      
      In the independant menubar and banner, these are considerated as part of each individual page layout.
      Thus, the container is limited to "content":
    echo "<tr>
            <td><div class='banner'>
              <table class='banner_table'>
              <tr>
                <td class='bannerlogo'>".$db->getBannerLogo()."</td>
                <td><div  class='bannerlanguage'>".$db->getBannerLanguage()."</div></td>
                <td><div  class='bannergetlanguage'>".$db->getBannerLanguageSelect()."</div></td></tr>
              </table>
            </div></td>
          </tr>";
       ________________________
      |content                 |
      |                        |
      |                        |
      |________________________|
      
   */
  echo "<table class='master'>";
  if ($fixed_layout) {
    echo "<tr>
            <td class='banner'>
                <div class='bannerlogo'>".$db->getBannerLogo($browser->isMobile())."</div>
                <div  class='bannerlanguage'>".$db->getBannerLanguage()."</div>
                <div  class='bannergetlanguage'>".$db->getBannerLanguageSelect()."</div>
            </td>
          </tr>";
  }
  if ($_SESSION['show_version'] == "on" && !$_SESSION['versionInFooter']) {
    echo "<tr style='height:1px'><td><div id='cms_version'>Version:".$db->version."</div></td></tr>";  
  } 
  echo "<tr style='position: relative;'><td class='content'>";
  $level = 0;
  insertTable($db,$level);
  echo "</td>";
  echo "</tr>";
  // when lowbanner is NOT on, add a row containing a small 2 cols table for footer left and right
  if ($_SESSION['lowbanner']!=="on" && $_SESSION['has_lowbanner']=="yes") {
    echo "<tr><td class='footer_row'><table class='footer'>";
    echo "<tr><td class='single_line'></td></tr>";
    echo "<tr><td class='footer_left'>".$db->getCopyright()."</td>";
    echo "<td class='footer_right'>";
    if ($_SESSION['facebook']==true)
      echo $_SESSION['follow'][$_SESSION['lang']]."<a href='http://www.facebook.com'><img src='pictures/icons/faceb_icon.jpg' height='10px'/></a>";
    if ($_SESSION['twitter']==true)
      echo "&nbsp;<a href='http://www.twitter.com'><img src='pictures/icons/twitter_icon.jpg' height='10px'/></a>";
    echo "</td></tr></table></td></tr>"; // end of footer
  }
  echo "</table>"; //end of master
  // when lowmarquee is on, add a new table (may have full window width and 2 cols for footer left and right
  if ($_SESSION['lowmarquee']=="on") {
    echo "<table class='lowmarqueetable'><tr><td>";
    echo $db->getHTMLmarquee();
    echo "</td></tr></table>"; //end of low marquee
  }
  // when lowbanner is on, add a new table (may have full window width and 2 cols for footer left and right
  if ($_SESSION['lowbanner']=="on" && $_SESSION['has_lowbanner']=="yes") {
    echo "<div class='lowbanner'>";
    echo "<table class='lowbannertable'>";
    echo "<tr><td class='footer_left'>".$db->getCopyright()."</td>";
    echo "<td class='footer_middle'>";
    if ($_SESSION['show_version'] == "on" && $_SESSION['versionInFooter']) {
      echo "<div id='cms_version'>Version:".$db->version."</div>";  
    } 
    echo "</td><td class='footer_right'><div class='medias'>";
    if ($_SESSION['facebook']==true)
      echo $_SESSION['follow'][$_SESSION['lang']]."<a href='http://www.facebook.com'><img id='mediasIcon_1' src='pictures/icons/faceb_icon.jpg'/></a>";
    if ($_SESSION['twitter']==true)
      echo "&nbsp;<a href='http://www.twitter.com'><img id='mediasIcon_2' src='pictures/icons/twitter_icon.jpg' /></a>";
    echo "</div></td></tr></table>"; //end of footer banner
    echo "</div>";
  }
  
  if ($isHome && empty($_SESSION['POPUP'])) {
    $_SESSION['POPUP'] = 1;
    $homePicts = $db->getPicts();
?>
    <div style='display:none'>
<?php
    for ($cn=0;$cn<sizeof($homePicts);$cn++) {
      echo "<a class='group4' href='";
      echo $db->getHREFpicts($cn);
      echo "'></a>";
    }      
?>    
    </div>
    <script>
      jQuery('a.group4').colorbox({rel:'group4', slideshow:true, slideshowSpeed:4000, open:true});
    </script>
<?php          
  }
  if ($reload!=-1) {
    if (!empty($has_msg)) $reload = $_SESSION['msg_duration']; // x seconds when has a message
?>
    <SCRIPT type="text/JavaScript">
      function currentPageReload() {
        var position;
        var page;
<?php
        $url = "index.php?hmenu=".$_SESSION['page'];
?>
        page = "<?php echo $pageReload;?>";
        filter = "";
        if ($('#listFilter')[0]!=null)
          filter = $('#listFilter')[0].value;
        if (filter!="") filter = "&listFilter="+filter; 
        position = "&Ypos="+ parseInt(window.pageYOffset);
        if (page!="")
          window.location.href = 'index.php?hmenu=' + page + position + filter;
        else
          window.location.href = '<?php echo $url;?>' + position + filter;
      }  
      t = setTimeout(function(){ currentPageReload() },<?php echo $reload;?>);
      
    </SCRIPT>
  <?php 
    $pageReload = "";  
  }
  ?>   
    </div>
  <?php 
  if ($_SESSION['cookiesWarning']==true) {
  ?>   
    <div id="cookieModal" class="modal">
      <div class="modal-content">
        <span class="close-button" id="closeModal">&times;</span>
        <p>Diese Webseite benutzt keine Cookies | Questo sito web non utilizza cookie</p>
        <button id="okButton">OK</button>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var modalShown = localStorage.getItem("cookieModalShown");

      if (!modalShown) {
        var modal = document.getElementById("cookieModal");
        modal.style.display = "block";

        document.getElementById("okButton").onclick = function() {
          modal.style.display = "none";
          localStorage.setItem("cookieModalShown", "true"); // Mark as shown
        }
        document.getElementById("closeModal").onclick = function() {
          modal.style.display = "none";
          localStorage.setItem("cookieModalShown", "true"); // Mark as shown
        }
        window.onclick = function(event) {
          if (event.target == modal) {
              modal.style.display = "none";
              localStorage.setItem("cookieModalShown", "true"); // Mark as shown
          }
        }
      }
    });
    </script>
  <?php 
  }
  ?>   
  </body>
</html>
