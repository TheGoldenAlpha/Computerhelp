<?php
/*
  History: 10-oct-13 GC -> modified the submenu loop, which was bringing an empty row
           12-oct-13 GC -> modified menu_getPageID in order to get the pageID of a menu_ref without changing $_SESSION['pageID']
           20-apr-15 GC -> modified to handle protected pages via login
           13-jul-16 GC -> fixed: submenu not displayed when inactive
           27-feb-17 GC -> added menu exclusion via $SESSION variable in PROT field (Syntax ['var_name']  and $_SESSION['var_name'] = "no")
           21-apr-17 EC -> addded menu item to submenu for mobile line: 251-252
*/
  
/*
  Function to initialize menu arrays
*/
function menu_init($db) {
  // Read page layouts (when not done)
  if (empty($_SESSION['layouts'])) {
    $PageLayouts = array ("");
    $sql = "SELECT * FROM `page_layouts` order by `layout_name`";
    $PageLayouts =  $db->db_queryAll($sql);
    $_SESSION['layouts'] = $PageLayouts;
  }
  // Read Main menus (when not done) 
  if (!isset($_SESSION['mainmenu']) || !isset($_SESSION['submenu'])) {
    $MainMenus = array ("");
    $ItemsTable = array ("");
    $sql = "SELECT * FROM `menus` WHERE `id_sub`=0 ORDER BY sorting, menu_title";
    $menu_rows =  $db->db_queryAll($sql);
    $numrows = sizeof($menu_rows);
    for ($i=0;$i<$numrows;$i++)
    { $norm_menu = $db->get_norm_menu($menu_rows[$i]);
      $ItemsTable["ACT"]= trim($norm_menu["active"]);
      $ItemsTable["PGL"]= trim($norm_menu["page_layout"]);
      $ItemsTable["MID"]= trim($norm_menu["id_men"]);    
      $ItemsTable["SID"]= "";
      $ItemsTable["TITLE"]= trim($norm_menu["menu_title"]);
      $ItemsTable["LINK"]= trim($norm_menu["menu_link"]);
      $ItemsTable["NAME"]= trim($norm_menu["menu_name"]); //language specific
      $ItemsTable["DESC"]= trim($norm_menu["menu_description"]);  //language specific
      $ItemsTable["PARAM"]= "";              
      $ItemsTable["PROT"]= htmlspecialchars_decode(trim($norm_menu["protected"]),ENT_QUOTES);
      // New Version 3.5: look for ['...'] in the protected field, when found, replace with "". the ... contains is used to check a
      // param variable. when true, nothing changed else, the 'active' is turned off to avoid menu display
      $beg = strpos($ItemsTable["PROT"],"['");
      if ($beg!==false) {
        $end = strpos($ItemsTable["PROT"],"']",$beg);
        $set = substr($ItemsTable["PROT"],$beg+2,($end-2)-$beg);
        $ItemsTable["PROT"] = str_replace("['$set']","",$ItemsTable["PROT"]);
        if ($_SESSION["$set"]=="no") $ItemsTable["ACT"] = "0";
      }              
//      if (!empty($ItemsTable["NAME"])) $MainMenus[$i] =  $ItemsTable;
      $MainMenus[$i] =  $ItemsTable;
    }
    $MainMenusCnt = $numrows;
    $_SESSION['mainmenu'] = $MainMenus;
    
    $SubMenus = array();
    $SubSubMenus = array();
    $sql = "SELECT * FROM `menus` WHERE `id_sub`!=0 ORDER BY sorting, menu_title";
    $menu_rows =  $db->db_queryAll($sql);
    $numrows = sizeof($menu_rows);
    for ($i=0;$i<$MainMenusCnt;$i++) $SubMenus[$i][]=  array();
    for ($i=0;$i<$numrows;$i++)
    { $norm_menu = $db->get_norm_menu($menu_rows[$i]);
      $ItemsTable["ACT"]= trim($norm_menu["active"]);
      $ItemsTable["PGL"]= trim($norm_menu["page_layout"]);
      $ItemsTable["MID"]= trim($norm_menu["id_men"]);    
      $ItemsTable["SID"]= trim($norm_menu["id_sub"]);
      $ItemsTable["TITLE"]= strtolower(trim($norm_menu["menu_title"]));
      $ItemsTable["LINK"]= trim($norm_menu["menu_link"]);
      $ItemsTable["NAME"]= trim($norm_menu["menu_name"]); //language specific
      $ItemsTable["DESC"]= trim($norm_menu["menu_description"]);  //language specific
      $ItemsTable["PARAM"]= "";
      $ItemsTable["PROT"]= htmlspecialchars_decode(trim($norm_menu["protected"]),ENT_QUOTES);
      // New Version 3.5: look for ['...'] in the protected field, when found, replace with "". the ... contains is used to check a
      // param variable. when true, nothing changed else, the 'active' is turned off to avoid menu display
      $beg = strpos($ItemsTable["PROT"],"['");
      if ($beg!==false) {
        $end = strpos($ItemsTable["PROT"],"']",$beg);
        $set = substr($ItemsTable["PROT"],$beg+2,($end-2)-$beg);
        $ItemsTable["PROT"] = str_replace("['$set']","",$ItemsTable["PROT"]);
        if ($_SESSION["$set"]=="no") $ItemsTable["ACT"] = "0";
      }              
      // check if in submenus              
      $done = false;
      foreach($SubMenus as $sub)
        foreach ($sub as $obj) {
          if ($obj['MID']== $ItemsTable["SID"]) {
            array_push($SubSubMenus,$ItemsTable);
            $done=true;
          }
      }
      if (!$done) { // when item was NOT a subsubmenu, check as submenu
        $mmidx = 0;
        foreach($MainMenus as $obj) {
            if ($obj['MID']== $ItemsTable["SID"])
              array_push($SubMenus[$mmidx],$ItemsTable);
            $mmidx++;
        }
      }
    }
    // Check for unchanged main menus functions
    for ($i=0;$i<$MainMenusCnt;$i++)
    {
      if ($MainMenus[$i] ["PGL"][0]=="-") //when begins with "-"
      {
        if (count($SubMenus[$i])>0) // have a submenu
        {
          $tmp = (int) $MainMenus[$i] ["PGL"];
          $MainMenus[$i] ["PGL"] = (string)-$tmp; //replace negative to validate it
        }
        else // empty submenu
        {
           $MainMenus[$i] ["PGL"] = "0";
           $MainMenus[$i] ["TITLE"] ="";
           $MainMenus[$i] ["LINK"] ="";
           $MainMenus[$i] ["NAME"] ="";
           $MainMenus[$i] ["DESC"] ="";
        } 
      }
    }
    $_SESSION['submenu'] = $SubMenus;
    $_SESSION['subsubmenu'] = $SubSubMenus;
  }
}

/*
  Function to set page id and the pagelayoutID for the passed menu title
  When the argument is true (default), the $_SESSION['pageID'] is set, otherwise the ID is only returned
*/
function menu_getPageID($menuTitle, $setID=true) {
  $MainMenus = $_SESSION['mainmenu'];
  $SubMenus = $_SESSION['submenu'];
  $SubSubMenus = $_SESSION['subsubmenu'];
  $ret="";
  $prot="";
  $_SESSION['pageLayoutID'] = "";
  for ($i=0;$i<sizeof($MainMenus);$i++)
    if (strtolower($MainMenus[$i] ["TITLE"])==strtolower($menuTitle)) {
      $_SESSION['pageLayoutID']= $MainMenus[$i] ["PGL"];
      $prot = $MainMenus[$i] ["PROT"];
      $ret = $MainMenus[$i] ["MID"];
      break;
    }
  if (empty($ret)) { // when not found in main menus   
    for ($i=0;$i<sizeof($MainMenus);$i++)
      for ($j=0;$j<sizeof($SubMenus[$i]);$j++) 
        if (strtolower($SubMenus[$i][$j] ["TITLE"])==strtolower($menuTitle)) {
          $_SESSION['pageLayoutID']= $SubMenus[$i][$j] ["PGL"];
          $prot = $SubMenus[$i][$j] ["PROT"];
          $ret = $SubMenus[$i][$j] ["MID"];
          break(2);
      }  
  }
  if (empty($ret)) { // when not found in sub menus look in sub sub menus  
    for ($i=0;$i<sizeof($SubSubMenus);$i++)
      if (strtolower($SubSubMenus[$i] ["TITLE"])==strtolower($menuTitle)) {
        $_SESSION['pageLayoutID']= $SubSubMenus[$i] ["PGL"];
        $prot = $SubSubMenus[$i][$j] ["PROT"];
        $ret = $SubSubMenus[$i] ["MID"];
        break(1);
      }  
  }
  $_SESSION['protected'] = $prot;
  if ($setID)
    $_SESSION['pageID'] = $ret;
  return $ret;
}
/*
  Function to change the menu state
  When the argument is true (default), the visibility is set
*/
function menu_visibility($menuTitle, $visible=true) {
  $MainMenus = $_SESSION['mainmenu'];
  $SubMenus = $_SESSION['submenu'];
  $SubSubMenus = $_SESSION['subsubmenu'];
  for ($i=0;$i<sizeof($MainMenus);$i++)
    if ($MainMenus[$i] ["TITLE"]==strtolower($menuTitle)) {
      $MainMenus[$i] ["ACT"] = ($visible) ? "1" : "0";
      break;
    }
  if (empty($ret)) { // when not found in main menus   
    for ($i=0;$i<sizeof($MainMenus);$i++)
      for ($j=0;$j<sizeof($SubMenus[$i]);$j++) 
        if ($SubMenus[$i][$j] ["TITLE"]==strtolower($menuTitle)) {
          $SubMenus[$i] ["ACT"] = ($visible) ? "1" : "0";
          break(2);
      }  
  }
  if (empty($ret)) { // when not found in sub menus look in sub sub menus  
    for ($i=0;$i<sizeof($SubSubMenus);$i++)
      if ($SubSubMenus[$i] ["TITLE"]==strtolower($menuTitle)) {
          $SubSubMenus[$i] ["ACT"] = ($visible) ? "1" : "0";
        break(1);
      }  
  }
  // Refreh the saved items
  $_SESSION['mainmenu'] = $MainMenus;
  $_SESSION['submenu'] = $SubMenus;
  $_SESSION['subsubmenu'] = $SubSubMenus;
  return;
}

/*
  Function returning HTML code for Main menu
*/
function getHTMLmenu() {
  global $browser;
  $MainMenus = $_SESSION['mainmenu'];
  $SubMenus = $_SESSION['submenu'];
  $PageLayouts = $_SESSION['layouts'];
  $ret="";
  $ret .= ($browser->isMobile()) ? '<div id="nav_menu"><div id="dl-menu" class="dl-menuwrapper">' : '<div id="LimmatMenu">';
  // special for mobile: the Open Menu button
  if ($browser->isMobile()) {
    $ret .= '<button class="dl-trigger">Open Menu</button><ul class="dl-menu">';
  }
  // search last active menu
  $lastActMenu = -1;
  for ($i=0;$i<count($MainMenus);$i++)
  {
    if($MainMenus[$i] ["ACT"]==1 && !empty($MainMenus[$i] ["NAME"]))  // When active and there is a menu entry
      $lastActMenu = $i;
  }
  for ($i=0;$i<count($MainMenus);$i++)
  {
    $trailing = (count($SubMenus[$i])>1) ? "" : "</li>";  // for mobile menu: trailing only when no submenus
    if($MainMenus[$i] ["ACT"]==1 && !empty($MainMenus[$i] ["NAME"]))  // When active and there is a menu entry
    { 
      $ret .= ($browser->isMobile()) ? "" : "<ul>";  //öffnet den Themenblock
      if ($i==0)  // special for 1st menu
        $ret .= ($browser->isMobile()) ? "": " <li id='MainMenu1st'>";    //öffnet die Listeneinträge von Thema n
      else
      if ($i==$lastActMenu && $_SESSION['special_lastmenu']=="on")
        $ret .= ($browser->isMobile()) ? "" : " <li id='MainMenuLast'>";  //öffnet die Listeneinträge von Thema n
      else
        $ret .= ($browser->isMobile()) ? "" : " <li id='MainMenuNxt'>";  //öffnet die Listeneinträge von Thema n
      if (strtolower($MainMenus[$i]["LINK"])!="none") {  // when "none": no link
        if (empty($MainMenus[$i]["LINK"])) {  // when empty, use standard link: index.php?hmenu=TITLE
          $lnk = "index.php?hmenu=".$MainMenus[$i]["TITLE"];
        }
        else
          $lnk = $MainMenus[$i] ["LINK"];  // use what ever in LINK
        $selected = ($MainMenus[$i]["TITLE"]==$_GET['hmenu']) ? " hover" : "";
        $ret .= ($browser->isMobile()) ? "<li><a href='".$lnk."'>".$MainMenus[$i] ["NAME"]."</a>$trailing" : "<h3><a class='MainMenu $selected' href='".$lnk."'>".$MainMenus[$i] ["NAME"]."</a></h3>";    
      }
      else { // No link, name only
        $ret .= ($browser->isMobile()) ? "<li><a href='".$lnk."'>".$MainMenus[$i] ["NAME"]."</a>$trailing" : "<h3>".$MainMenus[$i] ["NAME"]."</h3>";    
      }
      if (count($SubMenus[$i])>1) { // When has submenus
        $ret .= ($browser->isMobile()) ? "<ul class='dl-submenu'>" : " <ul>";  // öffnet die Klappnavi von Thema n
        if ($browser->isMobile() && $lnk != null){  //for mobile if a menu with link has submenues add repeat mainmenu item in submenu
          $ret .= ($browser->isMobile()) ? "<li><a href='".$lnk."'>".$MainMenus[$i] ["NAME"]."</a></li>" : "<li><a class='SubMenu' href='".$lnk."'>".$MainMenus[$i] ["NAME"]."</a></li>";
        }
        for ($j=1;$j<=count($SubMenus[$i]);$j++)  // include submenus
          if($SubMenus[$i][$j]["ACT"]==1 && !empty($SubMenus[$i][$j] ["NAME"]))  // When active and there is a menu entry
          {
            if (strtolower($SubMenus[$i][$j]["LINK"])!="none") {  // when "none": no link
              if (empty($SubMenus[$i][$j]["LINK"])) {  // when empty, use standard link: index.php?hmenu=TITLE
                $lnk = "index.php?hmenu=".$SubMenus[$i][$j]["TITLE"];
              }
              else
                $lnk = $SubMenus[$i][$j] ["LINK"];  // use what ever in LINK
              $ret .= ($browser->isMobile()) ? "<li><a href='".$lnk."'>".$SubMenus[$i][$j] ["NAME"]."</a></li>" : "<li><a class='SubMenu' href='".$lnk."'>".$SubMenus[$i][$j] ["NAME"]."</a></li>";    
            }
            else { // No link, name only
              $ret .= ($browser->isMobile()) ? "<li>".$SubMenus[$i][$j] ["NAME"]."</li>" : "<li>".$SubMenus[$i][$j] ["NAME"]."</li>";
            }    
          }
        $ret .= ($browser->isMobile()) ? "</ul></li>" : " </ul>";  // schließt die Klappnavi von Thema n
      }
      if ($browser->isMobile()) {
        if ($trailing=="") $ret .= "</li>"; // close submenu when has
        $ret .= "</li>";  //schließt die Listeneinträge von Thema n
      } else {
        $ret .= "</li>";  //schließt die Listeneinträge von Thema n
        $ret .= "</ul>";  //schließt den Themenblock
      }
    }
  }
  $ret .= ($browser->isMobile()) ? "</div></div>" : "</div>";
  $ret .= '<div style="clear: both;"> </div>';
  return $ret;
}

?>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            