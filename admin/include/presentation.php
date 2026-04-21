<?php
/*
  History: 19-oct-13 GC -> add tools menu
*/  
class presentation extends db_utils{
  
  function presentation(){
    $this->db_utils();
    $this->cur_table = $_GET['table'];
    $this->page = "logged_in.php";
  }
  
  function mask($table, $columns, $labels, $types, $infos, $defaults, $action, $form_name, $method, $id){
    $nr_cols = count($columns);
    $_SESSION['files']= array();
    $fcounter = 1;      
    
    if($action == "insert"){
      $button_text = $_SESSION['SaveBtn'][$_SESSION['lang']];
      $form_action = $_SERVER['HTTP_REFERER']."&id=".$id."#".$id;
    }else if($action == "update"){
      $button_text =  $_SESSION['SaveBtn'][$_SESSION['lang']];
      $form_action = $_SERVER['HTTP_REFERER']."&id=".$id."#".$id;
    }else if($action == "update_one"){
      $button_text =  $_SESSION['SaveBtn'][$_SESSION['lang']];
      $form_action = "logged_in.php?liste&table=".$table;
    }else if($action == "delete"){
      $button_text =  $_SESSION['DelBtn'][$_SESSION['lang']];
      $form_action = $_SERVER['HTTP_REFERER']."&id=".$id."#".$id;
    }
    
    if( substr($action, 0, 6) == "update" || $action == "delete"){
      $where = " WHERE ".$columns[0]."='".$id."'";
      $sql = "SELECT * FROM ".$table." ".$where;
      $inhalt = $this->db_query($sql, $columns);
    }
    else if (substr($action, 0, 6) == "insert")
      $inhalt = array(array_combine($columns, $defaults));
    
    if( ($action == "insert" && 
         $this->table_forbidden_action($_GET['table'], "insert", false) == true) || 
        ($action != "insert" && 
         $this->table_forbidden_action($_GET['table'], "fremd", false) == true && $inhalt[0][$columns[1]] != $_SESSION['userid']) ){
      $this->status = array('msg'=>$_SESSION['RecNotFound'][$_SESSION['lang']],'type'=>"error");
    }else{
      echo "<form id='".$form_name."' name='".$form_name."' method='".$method."' action='".$form_action."' enctype='multipart/form-data' autocomplete='off'>
      <table><tr><td colspan='2' class='text' style='vertical-align:bottom;'>";
      echo "<span class='titel'>".$this->table_defs[$table]['desc'][$_SESSION['lang']]."</span>";
      if ($_SESSION['has_datestamp']=="yes")
        echo str_repeat("&nbsp;",10).$_SESSION['RecModif'][$_SESSION['lang']]."&nbsp;".$this->format_date(substr($inhalt[0][$columns[1]],0,10),"d-m-Y");
      echo "</td></tr>
              <tr>
                <td class='titel' colspan='2'>
                  <a href='".$_SERVER['HTTP_REFERER']."' class='aktions_link'>".$_SESSION['BackLink'][$_SESSION['lang']]."</a>&nbsp;
                  <button id='maskTopBtn' name='".substr($action, 0, 6)."' >$button_text</button>
                </td>
            </tr>";
      for($i=1 ;$i < $nr_cols; $i++){
        if( !($types[$i] == "hidden" || $types[$i] == "datestamp") && substr($types[$i], 0, 4) != "wert" ){
          // only when language activated
          if ((empty($this->table_struct[$table]['chklng'][$i])) || 
              ($this->table_struct[$table]['chklng'][$i]==$_SESSION['languages'][1] && $_SESSION['lng1']=="on") ||
              ($this->table_struct[$table]['chklng'][$i]==$_SESSION['languages'][2] && $_SESSION['lng2']=="on") ||
              ($this->table_struct[$table]['chklng'][$i]==$_SESSION['languages'][3] && $_SESSION['lng3']=="on")) {
            echo "<tr><td class='ueberschrift'>".$labels[$i]."</td><td class='text'>";
            $this->entry_field($inhalt[0][$columns[$i]], $columns[$i], $types[$i], $action, $this->table_struct[$table]['sizes'][$i], "", $fcounter);
            if(substr($types[$i], 0, 4) == "file" || substr($types[$i], 0, 4) == "bild") {
              $_SESSION['files'][$columns[$i]]= array();
              $fcounter++;
            }
            echo "</td><td><span class='info'>";
            if( $types[$i] == "textareaktml" ){
              echo "Einfache Zeilenumbrüche: Shift + Enter<br />";
            }
            echo $infos[$i]."</span></td></tr>";
          }
        }
      }
      echo "<tr><td>&nbsp;</td><td>
      <input type='hidden' name='table' value='".$table."'>
      <input type='hidden' name='id' value='".$inhalt[0][$columns[0]]."'>
      <input type='hidden' name='id_name' value='".$columns[0]."'>
      <input type='hidden' name='".substr($action, 0, 6)."' value='true'>
      <input type='hidden' name='".substr($action, 0, 6)."' value='".$button_text."' />
      <a id='uploadfiles' href='#'><button class='button'>$button_text</button></a></td></tr>";
      echo "</table></form>";
    }
  }
  
  function entry_field($wert, $name, $type, $action, $size, $javascript, $fcounter = 1){
    if($action == "delete"){
      $readonly = " readonly='true'";
    }else{
      $readonly = "";
    }
    
    if($type == "text" || $type == "unique"){  // Einzeiliges Textfeld
      if($wert <> ""){
        echo "<input type='text' name='".$name."' value= '".str_replace("'", chr(146), $wert)."'".$readonly." size='".$size."' ".$javascript.">";
      }else{
        echo "<input type='text' name='".$name."' size='".$size."' ".$javascript." >";
      }
    }else if($type == "password"){ // Password 
      if($wert <> ""){
        echo "<input type='password' name='".$name."' id='pass$name' value= '".str_replace("'", chr(146), $wert)."'".$readonly." size='".$size."' ".$javascript."><img class='glyphicon-eye-open' /></div>";
      }else{
        echo "<input type='password' name='".$name."' size='".$size."' ".$javascript." >";
      }
    }else if($type == "datetime_ro"){ // Date-Time field, read-only
      if($wert <> ""){
        echo "<input type='text' name='".$name."' value= '".$this->format_date($wert, "d.m.Y h:i")."' DISABLED size='16' >";
      }
    }else if($type == "date"){ // Datums Feld (Mit Kalenderfunktion)
      if($wert <> ""){
        echo "<input type='text' name='".$name."' value= '".$this->format_date($wert, "d.m.Y")."'".$readonly." size='11' ".$javascript.">";
      echo "<A HREF='#' NAME='".$name."datestartancor' ID='".$name."datestartancor' onClick=\"".$name."cal1x.select(document.update_".$this->cur_table.".".$name.",'".$name."datestartancor','dd.MM.yyyy', '".$name."_div'); return false;\" class='aktions_link' style='vertical-align:middle;'>&nbsp;".$_SESSION['calendar'][$_SESSION['lang']]."</A>
                <DIV ID='".$name."_div' STYLE='z-index:99;position:absolute;visibility:hidden;'></DIV>";
      }else{
        echo "<input type='text' name='".$name."' size='20' ".$javascript.">";
      }
    }else if($type == "zeit"){  // Zeitfeld
      echo "<select name='stunde' id='stunde'>";
      
      for($t=0;$t<25;$t++){
        if ($t<10){ $t = "0".$t;}
        
        if($t == substr($wert, 0, 2)){
          echo "<option value='$t' selected>$t</option>";
        }else{
          echo "<option value='$t'>$t</option>";
        }
      }
      echo "</select>:<select name='minute' id='minute'>";
      for($t=0;$t<60;$t=$t+5){
        if ($t<10){ $t = "0".$t;}
        
        if($t == substr($wert, 3, 2)){
          echo "<option value='$t' selected>$t</option>";
        }else{
          echo "<option value='$t'>$t</option>";
        }
      }
      echo "</select>";
    }else if(substr($type, 0, 8) == "textarea"){  // Rich text area
      if( strchr($size, ",") <> ""){}else{
        $size = "05,50";
      }
      if($wert <> ""){
        echo '<textarea class="ckeditor" cols="'.substr($size, 3, 5).'" id="'.$name.'" name="'.$name.'" rows="'.substr($size, 0, 2).'" '.$javascript.'>'.htmlentities($wert, ENT_QUOTES, "UTF-8").'</textarea>';
      }else{
        echo '<textarea class="ckeditor" cols="'.substr($size, 3, 5).'" id="'.$name.'" name="'.$name.'" rows="'.substr($size, 0, 2).'" '.$javascript.'></textarea>';
      }
    }else if(substr($type, 0, 9) == "textblock"){  // Mehrzeiliges Textfeld
      if( strchr($size, ",") <> ""){ // when "multi-size format (rows,cols)
        $tb_size = explode(",",$size);
      }else{  // only one dimension given: assumed width
        $tb_size = explode(",","05,".$size);
      }
      if($wert <> ""){
        echo '<textarea cols="'.$tb_size[1].'" id="'.$name.'" name="'.$name.'" rows="'.$tb_size[0].'" '.$javascript.'>'.$wert.'</textarea>';
      }else{
        echo '<textarea cols="'.$tb_size[1].'" id="'.$name.'" name="'.$name.'" rows="'.$tb_size[0].'" '.$javascript.'></textarea>';
      }
    }else if(substr($type, 0, 4) == "file" || substr($type, 0, 4) == "bild"){ // Datei-Auswahl
      $_SESSION['upload_dir'] = $_SESSION['DOCUMENT_ROOT']."/".$this->table_defs[$this->cur_table]['paths']['original'];
      if($wert <> ""){
        $endung = strtolower(strrchr($wert, "."));
        
        if(strcasecmp(".gif", $endung) == 0 || strcasecmp(".jpg", $endung) == 0 || strcasecmp(".jpeg", $endung) == 0 || strcasecmp(".png", $endung) == 0){
          echo "<img src='../".$this->table_defs[$this->cur_table]['paths']['size2'].$wert."' border='0'><br />";
        }
//        echo "<input type='file' name='".$name."' value='".$wert."'><br /><input type='text' name='".$name."_inhalt' value='".$wert."' size='20'>";
        echo "<input type='text' READ-ONLY name='".$name."_inhalt' value='".$wert."' size='$size'>";
        
        if(substr($action, 0, 6) == "update"){
          echo "&nbsp;".$_SESSION['remove'][$_SESSION['lang']]." <input type='checkbox' name='".$name."_delete'>";
        }
      }	
//      }else{
//        echo "<input type='hidden' name='".$name."' >";
        echo "<div id='filelist$fcounter'></div>
                <br />
                <a id='pickfiles$fcounter' href='#'><button class='button'>".$_SESSION['pickFile'][$_SESSION['lang']]."</button></a>";
    }else if($type == "multifile"){ // Mehrere Files hochladen
      $werte = explode(",",$wert);
      echo "<input type='file' name='".$name."' ".$javascript."><div class='div_per_dropdown_admin' id='div_per_dropdown_admin'><table><tr>";
      
      for($c=0;$c<count($werte)-1;$c++){
        echo "<td>";
        $endung = strrchr( $werte[$c] , ".");
        
        if(strcasecmp(".gif", $endung) == 0 || strcasecmp(".jpg", $endung) == 0 || strcasecmp(".jpeg", $endung) == 0 || strcasecmp(".png", $endung) == 0){
          echo "<a href='".$this->$this->table_defs[$this->table]['paths']['original'].$werte[$c]."' target='_blank'><img src='".$this->table_defs[$this->table]['paths']['size2'].$werte[$c]."' border='0'></a>";
        }else{
          echo $werte[$c];
        }
        
        if(substr($action, 0, 6) == "update"){
          echo "<br /><input type='checkbox' name='".$name.$c."_delete'>&nbsp;".$_SESSION['remove'][$_SESSION['lang']];
        }
        echo "</td>";
      }
      echo "</tr></table></div><input type='hidden' name='".$name."_werte' value='".$wert."'>";
    }else if($type == "checkbox"){ // Checkbox
      echo "<input type='checkbox' name='".$name."' ".($wert == 1 ? 'checked':'')." ".$javascript." />";
    }else if(is_array($type) == true){ // Array
      echo "<select name='".$name.$a."' ".$javascript.">";
      
      for($a=0;$a < count($type);$a++){
        echo "<option value='".$a."' ".($wert == $type[$a][1]?"selected='selected'":"")." >".$type[$a][1]."</option>";
      }
      echo "</select>";
    }else if(substr($type, 0, 6) == "SELECT"){ // SQL Abfrage
        $sql = $type;
        $inhalt = $this->db_queryAll($sql);
        if(!empty($inhalt)){
          echo "<select name='".$name."' ".$javascript.">";
          if ($this->cur_table!="projects") echo "<option value='NULL'>&nbsp;</option>";
          for($i=0;$i < sizeof($inhalt);$i++){
            echo "<option value='".$inhalt[$i][0]."' ".($inhalt[$i][0] == $wert?"selected='selected'":"")." >".$inhalt[$i][1]."</option>";
          }
          echo "</select>";
        }
      
    }
  }
/*
  Used to display a list of rows contained in a table
*/
  function listing($table, $columns, $labels, $types, $inlist, $id, $search_val, $nr_cols_per_row, $paths){
    if($this->table_sql[$table][1] != "" && !isset($_GET['extraliste']) ){
      $extraliste = 1;
    }else{
      $extraliste = $_GET['extraliste'];
    }
    
    $ausgaben = 0;
    $anz_hidden = 0;
    $where = "";
    $where_isset = false;
    $andklammer = false;
    $join = "";
    $t1 = "";
    $t2 = "";
    
    $chklng = $this->table_struct[$table]["chklng"];
    
    for($h=0;$h < count($types);$h++){
      if($types[$h] == "hidden" || substr($types[$h], 0, 4) == "wert" || $inlist[$h] == 0 || (!empty($chklng[$h]) && $chklng[$h]!= $_SESSION["lang"])){
        $anz_hidden++;
      }
    }
    $nr_cols_tot = count($columns);
    $nr_cols = $nr_cols_tot - $anz_hidden -1;
    
    //Abfragen der Daten
    if($extraliste == 1 && $this->table_sql[$table][1] != ""){
      $sql = $this->table_sql[$table][1];
      $where_isset = true;
    }else{
      $sql = $this->table_sql[$table][0];
    }
    
    // Damit die Suche funktioniert muss hier noch was mit tabellen_verbote; fremd gemacht werden, das nur noch einmal WHERE in der SQL vorkommt.
    if($this->table_forbidden_action($table, "fremd", false) == false){
      // do nothing special when fremd not forbidden
    }else{ // when a fremd table
      if ($this->table_forbidden_action($table, "mehrere", false) == false) {
        // only when mehrere not forbidden
        if($where_isset == true){ // when already there, must add AND
          $where .= " AND ".$columns[1]."=".$_SESSION['userid']." ";
        }else{
            $where = " WHERE ".$columns[1]."=".$_SESSION['userid']." ";
            $where_isset = true;
        }
      }
    }
    
    if($search_val <> ""){
      if (stripos($sql,"JOIN")!==false) { // when has join, extract the two shortnames (if none, then table names)
        $join = ".";
        $t = explode(" ",substr($sql,stripos($sql,"FROM")+5,stripos($sql,"LEFT")-(stripos($sql,"FROM")+6)));
        $t1 = (sizeof($t)>1) ? $t[1] : $t[0]; // select table name or shortname  
        $t = explode(" ",substr($sql,stripos($sql,"LEFT")+5,stripos($sql,"ON")-(stripos($sql,"LEFT")+6)));
        $t2 = (sizeof($t)>1) ? $t[1] : $t[0]; // select table name or shortname
        $t = substr($sql,stripos($sql,"SELECT")+7,stripos($sql,"FROM")-(stripos($sql,"SELECT")+8));
        $t=explode(",",str_replace(" ","",$t));
      }
      if($where_isset == true){
        $where .= " AND (";
        $andklammer = true;
      }else{
        $where = " WHERE ";
        $where_isset = true;
      }
      
      foreach ($this->table_struct[$table]['fields'] as $field) {
        $where .= "LOWER(".$t1.$join.$field.") like LOWER('%".$search_val."%') OR ";
      }
      if (!empty($join)) {
        foreach ($t as $joined) {
          if (substr($joined,0,2)==$t2.$join)
            $where .= "LOWER(".$joined.") like LOWER('%".$search_val."%') OR ";
        }
      }
      $where = substr($where,0,strlen($where)-4);
    }
    
    if( $andklammer == true){
      $where .= ")";
    }
    $sql .= $where;
    
    if(isset($_GET['sort'])){
      if(isset($_GET['desc'])){
        $sql .=" ORDER BY ".$_GET['sort']." desc";
      }else{
        $sql .=" ORDER BY ".$_GET['sort'];
      }
    }else if($this->table_sql[$table][2] != ""){
      $sql .=" ORDER BY ".$this->table_sql[$table][2];
    }
    
    
    mysqli_select_db($this->db_lnk,$this->db);
    $Query = mysqli_query($this->db_lnk, $sql);
    $anz_e_abfrage = mysqli_num_rows($Query);
    
    if($this->table_forbidden_action($table, "fremd", "") == false){
      $anz_e_tab = $this->mysqli_result(mysqli_query($this->db_lnk, "SELECT COUNT(*) AS anzahl FROM ".$table), 0, "anzahl");
    }else{ // special for fremd: use userid when mehrere not forbidden
      if ($this->table_forbidden_action($table, "mehrere", false) == false) // only when many rows
        $anz_e_tab = $this->mysqli_result(mysqli_query($this->db_lnk, "SELECT COUNT(*) AS anzahl FROM ".$table." WHERE ".$columns[1]."='".$_SESSION['userid']."'"), 0, "anzahl");
      else
        $anz_e_tab = $anz_e_abfrage;
    }
    
    if(!isset($_GET['anz_e']) || $_GET['anz_e'] == 0){
      $_GET['anz_e'] = $this->table_defs[$table]['rows_per_page']; //Einträge pro Seite
      $_GET['von_e'] = 0;
    }
    
    if( !isset($_GET['von_e']) || $_GET['von_e'] < 0 ){
      $_GET['von_e'] = 0;
    }
    
    if(($_GET['anz_e']+$_GET['von_e']) > $anz_e_tab){
      $_GET['anz_e'] = $anz_e_tab - $_GET['von_e'];
    }
    
    $sql .=" LIMIT ".$_GET['von_e'].",".$_GET['anz_e'];
    
    $inhalt = $this->db_query($sql, $columns);
    $anz_inhalt = count($inhalt);
    
    if($this->table_forbidden_action($table, "mehrere", "") == true){ // when vorbidden to list, open single mask form
      $current_id = $inhalt[0][$columns[0]];
      $current_action = 'update_one';
      $current_form = "update_".$table;
      if (!isset($current_id)) {
        $current_action = "insert";
        $current_form = "insert_".$table;
      } 
      $this->mask($_GET['table'], $columns, $labels, $types, $this->table_struct[$table]['infos'][$_SESSION['lang']], 
                  $this->table_struct[$table]['defaults'], $current_action, $current_form, "post", $inhalt[0][$columns[0]]);
    }else{
      echo "<table border='0'><tr><td colspan='8' class='text'>";
      echo "<span class='titel'>".$this->table_defs[$table]['desc'][$_SESSION['lang']]."</span>";
//        foreach ($this->table_struct[$table]['labels'][$_SESSION['lang']] as $label)
//          echo "<span class='titel'>".$label."</span>";
      
      echo "&nbsp;(".$anz_e_tab." ".$_SESSION['RecCount'][$_SESSION['lang']].")</td></tr><tr><td class='text' valign='bottom'>";
      
      $this->table_forbidden_action($table, "insert", "<form id='insert' name='insert' method='get' action='' style='display:inline'>
        <input type='hidden' name='mask' value='insert' />
        <input type='hidden' name='table' value='".$table."' />
        <input type='hidden' name='formname' value='update_".$table."' />
        <input type='hidden' name='action' value='post' />
        <input type='submit' name='insert' value='".$_SESSION['RecInsert'][$_SESSION['lang']]."' />
        </form>");
          
      echo "</td><td class='text' valign='bottom'><a href='".$this->page."?liste&table=".$table."&von_e=".($_GET['von_e']-$_GET['anz_e'])."&anz_e=".$_GET['anz_e'];
      if( isset($_GET['suchwert']) ){
        echo "&suchwert=".$_GET['suchwert'];
      }
      echo "' class='aktions_link'>".$_SESSION['BackLink'][$_SESSION['lang']]."</a></td><td class='text' width='60' align='middle' valign='bottom'>".($_GET['von_e']+1)." - ".($_GET['von_e']+$anz_inhalt)."</td><td class='text' valign='bottom'>";
      
      if( ($_GET['von_e']+$anz_inhalt) < $anz_e_abfrage ){
        echo "<a href='".$this->page."?liste&table=".$table."&von_e=".($_GET['von_e']+$_GET['anz_e'])."&anz_e=".$_GET['anz_e'];
        if( isset($_GET['suchwert']) ){
          echo "&suchwert=".$_GET['suchwert'];
        }
        echo "' class='aktions_link'>".$_SESSION['FwdLink'][$_SESSION['lang']]."</a>";
      }else{
        echo "<strong>".$_SESSION['FwdLink'][$_SESSION['lang']]."</strong>";
      }
        
      echo "</td><td class='text' valign='bottom'>".$_SESSION['Show'][$_SESSION['lang']].": </td><td class='text' valign='bottom'>
      <form name='von_e_anz_e' action='' method='GET' style='display:inline'>";
      
      $this->entry_field($_GET['anz_e'], "anz_e", "text", $action, 3, "");
      
      echo "<input type='hidden' name='table' value='".$table."'>
            <input name='suchwert' type='hidden' size='30' value='".$search_val."'/>
            <input type='hidden' name='liste'> 
            <input type='submit' value='>'></form></td>
            <td class='text' valign='bottom'> ".$_SESSION['of'][$_SESSION['lang']]." ".$anz_e_abfrage."</td><td class='text' valign='bottom'>";
      
      $this->search($search_val, $table);
      echo "</td><td class='text'>";
      
      if($this->table_sql[$table][1] != ""){
        echo "Aktuelle Termine Jahr: ";
        if($extraliste == 1){
          echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&extraliste=0' class='aktions_link'>ein</a>";
        }else{
          echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&extraliste=1' class='aktions_link'>aus</a>";
        }
      }
      echo "</td></tr></table><table width='100%'><tr><td class='ueberschrift_3'>&nbsp;</td>";

      // heading
      for($i=1 ;$i < $nr_cols_tot; $i++){
        if($types[$i] != "hidden"  && $inlist[$i] != 0 && !(substr($types[$i], 0, 4) == "wert" || (substr($types[$i], 0, 9)=="datestamp" && $_SESSION['has_datestamp']=="no")) ){
          // only when language activated and language is the one selected
          if (empty($chklng[$i]) || $chklng[$i]== $_SESSION["lang"]) {
            echo "<td class='ueberschrift_2' nowrap='nowrap'><a href='".$this->page."?liste&table=".$table."&sort=".$columns[$i]."";
            if(isset($_GET['suchwert'])){ echo "&suchwert=".$_GET['suchwert'];}
            if(isset($_GET['desc'])){
              echo "' class='ueberschrift_2'>".$labels[$i]."&nbsp;&nbsp;<img src='picts/icons/pfeil_down.gif'"; 
            }else{
              echo "&desc' class='ueberschrift_2'>".$labels[$i]."&nbsp;&nbsp;<img src='picts/icons/pfeil_up.gif";
            }
            echo "' border='0'></a></td>";
            $ausgaben++;
          }
        }
        if( ( $ausgaben == $nr_cols_per_row || $ausgaben == (2*$nr_cols_per_row) || $ausgaben == (3*$nr_cols_per_row) || $ausgaben == (4*$nr_cols_per_row) || $ausgaben == (5*$nr_cols_per_row) || $ausgaben == (6*$nr_cols_per_row) || $ausgaben == (7*$nr_cols_per_row)|| $ausgaben == (8*$nr_cols_per_row) ) && $letzter_ausgabenstand < $ausgaben){
          echo "<td class='ueberschrift_3'>&nbsp;</td></tr><tr bgcolor='".$bgcolor."'><td class='ueberschrift_3'>&nbsp;</td>";
          $letzter_ausgabenstand = $ausgaben;
        }
        if($ausgaben >= $nr_cols && ($ausgaben % $nr_cols_per_row != 0) && $nr_cols_per_row < $nr_cols ){
          for($r=0;$r < ($nr_cols_per_row - ($ausgaben % $nr_cols_per_row));$r++){
            echo "<td class='ueberschrift_2'>&nbsp;</td>";
          }
        }
      }
      echo "<td class='ueberschrift_3'>&nbsp;</td></tr>";

      for($i=0 ;$i < $anz_inhalt; $i++){
        $ausgaben=0;
        $letzter_ausgabenstand = 0;
        if( $_GET['id'] == $inhalt[$i][$columns[0]] ){ $bgcolor='#66CCFF'; }else if($i%2 == 0){ $bgcolor='#F1F1F1';}else{ $bgcolor='#F9F9F9';}
        
        echo "<tr bgcolor='".$bgcolor."'><td valign='top' class='aktions_link'>";
        $this->table_forbidden_action($table, "update", "<a href='".$this->page."?mask=update&table=".$table."&formname=update_".$table."&action=post&id=".$inhalt[$i][$columns[0]]."' class='aktions_link'>".$_SESSION['RecEdit'][$_SESSION['lang']]."</a>");
        echo "<a name='".$inhalt[$i][$columns[0]]."'></td>";
        
        for($j=1 ;$j < $nr_cols_tot; $j++){
          if($types[$j] != "hidden"  && $inlist[$j] != 0 && !(substr($types[$j], 0, 4) == "wert" || (substr($types[$j], 0, 9)=="datestamp" && $_SESSION['has_datestamp']=="no")) ){
          // only when language activated and language is the one selected
            if (empty($chklng[$j]) || $chklng[$j]== $_SESSION["lang"]) {
              $ausgaben++;
              echo "<td valign='top' class='text'>";
              if($types[$j] == "date"){
                echo $this->format_date($inhalt[$i][$columns[$j]], "d.m.Y");
              }else if($types[$j] == "datestamp"){
                echo $this->format_date($inhalt[$i][$columns[$j]], "d-m-Y");
              }else if((substr($types[$j], 0, 8) == "textarea" || substr($types[$j], 0, 9) == "textblock") && !empty($inhalt[$i][$columns[$j]])){
                echo $_SESSION['TextOK'][$_SESSION['lang']];
              }else if($types[$j] == "checkbox"){
                if($inhalt[$i][$columns[$j]] == 1 || $inhalt[$i][$columns[$j]] == '1'){ 
                  echo $_SESSION['CheckOn'][$_SESSION['lang']];
                }else{ 
                  echo $_SESSION['CheckOff'][$_SESSION['lang']]; 
                };
              }else if($types[$j] == "bild"){
                echo '<img src="'.$paths[0].'/'.$inhalt[$i][$columns[$j]].'" alt="" width="100" />';
              }else if(substr($types[$j], 0, 6) == "SELECT"){
                $dropBoxContent = $this->db_queryAll($types[$j]);  // get dropbox content
                if (!empty($dropBoxContent)) {
                  // hangs the first row as blank
                  $blank = array(array("0"=>"0","1"=>"","2"=>"0","3"=>"0"));
                  $dropBoxContent = array_merge($blank,$dropBoxContent);
                  $dropBoxText = ""; 
                  foreach($dropBoxContent as $db)
                    if ($db[0]==$inhalt[$i][$columns[$j]]) $dropBoxText = $db[1];  //display text for matching column 2
                  echo $dropBoxText;
                }else {
                  echo "";
                }
              } else {
                echo strip_tags($inhalt[$i][$columns[$j]]);
//                echo utf8_encode($inhalt[$i][$columns[$j]]);
              }
              echo "</td>";
            }
          }
          
          if( ( $ausgaben == $nr_cols_per_row || $ausgaben == (2*$nr_cols_per_row) || $ausgaben == (3*$nr_cols_per_row) || $ausgaben == (4*$nr_cols_per_row) || $ausgaben == (5*$nr_cols_per_row) || $ausgaben == (6*$nr_cols_per_row) || $ausgaben == (7*$nr_cols_per_row)|| $ausgaben == (8*$nr_cols_per_row) ) && $letzter_ausgabenstand < $ausgaben){
            echo "<td class='text'>&nbsp;</td></tr><tr bgcolor='".$bgcolor."'><td></td>";
            $letzter_ausgabenstand = $ausgaben;
          }
          
          if($ausgaben >= $nr_cols && ($ausgaben % $nr_cols_per_row != 0) && $nr_cols_per_row < $nr_cols ){
            for($r=0;$r < ($nr_cols_per_row - ($ausgaben % $nr_cols_per_row));$r++){
              echo "<td class='text'>&nbsp;</td>";
            }
          }
        }
        echo "<td class='aktions_link'>";
        $this->table_forbidden_action($table, "delete", "<a href='".$this->page."?mask=delete&table=".$table."&formname=update_".$table."&action=post&id=".$inhalt[$i][$columns[0]]."' class='aktions_link'>".$_SESSION['DelBtn'][$_SESSION['lang']]."</a>");
        echo "</td></tr>";
      }
      echo "</table>";
    }
  }
  
/*
  This function returns a table of style table_list
  Content is defined by the list of table_defs and is using the current language
  Each row content is of style menu.
  At the end of the table, a row with Exit is automatically generated 
*/
  function table_list(){
    echo "<table class='outer_table_list' ><tr><td><table class='table_list' >";
    foreach ($this->table_defs as $key => $table) {
      echo "<tr><td class='menu'><a href='".$this->page."?liste&table=".$key."'><img class='menu_img' src='".$table['icon']."' /><span class='menu_text'>".$table['desc'][$_SESSION['lang']].
      "</span></a></td></tr>";
    }
    echo "<tr><td class='menu'><a href='".$this->page."?tools'><img class='menu_img' src='picts/icons/tools.jpg' /><span class='menu_text'>".$_SESSION['tools'][$_SESSION['lang']]."</span></a></td></tr>";
    if ($_SESSION['superadmin']==true)
      echo "<tr><td class='menu'><a href='".$this->page."?cssedit'><img class='menu_img' src='picts/icons/administrator.jpg' /><span class='menu_text'>Update CSS</span></a></td></tr>";
    echo "<tr><td class='menu'><a href='".$this->page."?liste&table=exit'><img class='menu_img' src='picts/icons/cancel.gif' /><span class='menu_text'>LOGOUT</span></a></td></tr>";
    echo "</table></td></tr></table>";
  }
   
  function search($search_val, $table){
    echo "
      <form id='search' name='search' method='GET' action='".$this->page."?liste&table=".$table."' style='display:inline'>
        <input name='suchwert' type='text' size='30' value='".$search_val."'/>
        <input name='table' type='hidden' size='30' value='".$table."' />
        <input name='liste' type='hidden' size='30' />
        <input type='submit' name='Submit' value='".$_SESSION['Search'][$_SESSION['lang']]."' />
      </form>";
  }
  
  function calendar($table){
    $name = array();
    
     $i=0;
    foreach ($this->table_struct[$table]['types'] as $type) {
      if ($type=='date') {
        array_push($name,$this->table_struct[$table]['fields'][$i]);
      }
      $i++;
    }
    if (count($name)>0) { // when some calendars in form
//      echo "function init_calendars() {\r\n";
      for($a=0;$a < count($name); $a++){
        echo "<script>\r\n";
        echo "var ".$name[$a]."cal1x = new CalendarPopup(\"".$name[$a]."_div\");\r\n";
        echo "    ".$name[$a]."cal1x.setCloseText(".$_SESSION['cal_close'][$_SESSION['lang']].");\r\n";
        echo "    ".$name[$a]."cal1x.setTodayText(".$_SESSION['cal_today'][$_SESSION['lang']].");\r\n";
        echo "    ".$name[$a]."cal1x.setDayHeaders(".$_SESSION['cal_wdays'][$_SESSION['lang']].");\r\n";
        echo "    ".$name[$a]."cal1x.setMonthAbbreviations(".$_SESSION['cal_months3'][$_SESSION['lang']].");\r\n";
        echo "    ".$name[$a]."cal1x.setMonthNames(".$_SESSION['cal_months'][$_SESSION['lang']].");\r\n";
        echo "    ".$name[$a]."cal1x.setWeekStartDay(1);\r\n";
        echo "</script>\r\n";
      }
//      echo "}";   
    }
  }

/*
  Used to present the css content of CH_style.css file (assumed in document_root/css)
*/  
  function cssedit(){
    echo "<table border='0' cellpadding='0' cellspacing='0' style='margin-top:30px'>
          <tr>
            <td>
              <form action='".$_SERVER["PHP_SELF"]."' method='post'>
                  <textarea rows='40' cols='120' name='csseditcontent'>";
    readfile($_SESSION['DOCUMENT_ROOT']."/css/CH_styles.css");
    echo          "</textarea><BR />
              <input type='submit' value='".$_SESSION['SaveBtn'][$_SESSION['lang']]."'> 
              </form>";
    echo "</td></tr></table>";
  }
  
/*
  Used to present the tools form (it may have divers functions buttons)
*/  
  function tools(){
    function not_is_dir($f) {
      return !is_dir($f); 
    }
   echo "<table border='0' cellpadding='0' cellspacing='0' style='margin-top:30px'>
          <tr>
            <td>
              <form name='toolsaction' action='".$_SERVER["PHP_SELF"]."' method='post'>
                <table width='100%' border='0' cellpadding='10'><tr>
                  <td class='text'>".$_SESSION['ToolsTxt1'][$_SESSION['lang']]."</td>
                  <td><input name='toolsbtn1' id='toolsbtn1' type='submit' value='".$_SESSION['ToolsBtn1'][$_SESSION['lang']]."'></td>";
//  echo "        </tr>
//                <tr>
//                  <td class='text'>".$_SESSION['ToolsTxt2'][$_SESSION['lang']]."</td>
//                  <td><input name='toolsbtn2' id='toolsbtn2' type='submit' value='".$_SESSION['doit'][$_SESSION['lang']]."'></td>";
  echo "        </tr></table>  
              </form>";
    if (isset($_POST['toolsbtn1'])) {  // Btn1 = refresh pictures
      //scan all possible directories
      $dirlist = array();
      foreach($this->table_defs as $key => $cur_table) {
        if ((!empty($cur_table['paths']['size1']) && !empty($cur_table['paths']['width1'])) || (!empty($cur_table['paths']['size2']) && !empty($cur_table['paths']['width2'])))
          array_push($dirlist,array($key, $cur_table['paths']['original']));
      }
      $proceed = false;
      foreach($dirlist as $dir)
        if (isset($_POST[$dir[1]]))
          $proceed = true;
      if ($proceed) { // show progress dir by dir and picture by picture 
        echo "<table border='0' cellpadding='0' cellspacing='0' style='margin-top:30px'><tr><td class='text'>";
        foreach($dirlist as $dir)
          if (isset($_POST[$dir[1]]))
            {
              echo "For <B>".$dir[1]."</B>:<BR/>";
              $files = array_filter(glob($_SESSION['DOCUMENT_ROOT']."/".$dir[1].'*'), "not_is_dir");
              foreach ($files as $file) {
                $fname = basename($file);
                $ext = strtolower(strrchr($fname , "."));
                switch ($ext) {
                  case ".jpg": $ftype = "image/jpeg"; break;
                  case ".jpeg": $ftype = "image/pjpeg"; break;
                  case ".gif": $ftype = "image/gif"; break;
                  case ".png": $ftype = "image/png"; break;
                }  
                if ((!empty($this->table_defs[$dir[0]]['paths']['size1'])) && (!empty($this->table_defs[$dir[0]]['paths']['width1']))) {
                  //when has a target dir
                  $this->bild_resize($this->table_defs[$dir[0]]['paths']['original'], $fname, $this->table_defs[$dir[0]]['paths']['size1'], $this->table_defs[$dir[0]]['paths']['width1'], $ftype);
                  echo "File ".$this->table_defs[$dir[0]]['paths']['size1'].$fname." refreshed<BR/>";
                }
                if ((!empty($this->table_defs[$dir[0]]['paths']['size2'])) && (!empty($this->table_defs[$dir[0]]['paths']['width2']))) {
                  //when has a target dir
                  $this->bild_resize($this->table_defs[$dir[0]]['paths']['original'], $fname, $this->table_defs[$dir[0]]['paths']['size2'], $this->table_defs[$dir[0]]['paths']['width2'], $ftype);
                  echo "File ".$this->table_defs[$dir[0]]['paths']['size2'].$fname." refreshed<BR/>";
                }
              }
            }
        echo "</td></tr></table>";
      }
      else {  // nothing to do, show the list
        echo "<table border='0' cellpadding='0' cellspacing='0' style='margin-top:30px'>
            <tr>
              <td>
                <form action='".$_SERVER["PHP_SELF"]."' method='post'>
                  <table width='100%' border='0' cellpadding='10'>";
        if (sizeof($dirlist)>0) {
          for ($i=0;$i<sizeof($dirlist);$i++) {
            echo "  <tr><td><input type='checkbox' name='".$dirlist[$i][1]."'></td><td class='text'>".$dirlist[$i][1]."</td></tr>";
          }
          echo "    <td>&nbsp;</td><td><input name='toolsbtn1' id='toolsbtn1' type='submit' value='".$_SESSION['doit'][$_SESSION['lang']]."'></td>";
        }
        else {
          echo "   <tr><td class='text'></td>".$_SESSION['NoFolderFound'][$_SESSION['lang']]."</tr>";
        }
        echo "      </table>  
                </form>";
        echo "</td></tr></table>";
      }              
    }
    if (isset($_POST['toolsbtn2'])) {  // Btn2 = updates all passwords using MD5(MD5(base64_encode(uid+psw)))
      echo "Begin...\r\n";
      $clients = $this->db_queryAll("SELECT id, uid, pwd1, pwd2 FROM  clients");
      for ($i=0;$i<sizeof($clients);$i++) {
        $id = trim($clients[$i]['id']);
        $uid = trim($clients[$i]['uid']);
        $pwd1 = trim($clients[$i]['pwd1']);
        $name1 = $uid.$pwd1;
        $new1 = md5(md5(base64_encode($name1)));
        $pwd2 = trim($clients[$i]['pwd2']);
        $name2 = $uid.$pwd2;
        $new2 = md5(md5(base64_encode($name2)));
        $this->dbquery("UPDATE clients SET upwd='$new1', fpwd='$new2' where id=$id");
      }
      echo "Done!\r\n";
    }
  }
}
?>