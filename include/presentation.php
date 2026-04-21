<?php
/*
  History: 12-oct-13 GC -> add #DATALINKS_#page_ref#
                        -> add file type extensions icons for office and zip
           16-oct-13 GC -> projects filters country and subfoundations is respekting active flag
           20-oct-13 GC -> add EVENTLIST, changed news to handle it
           18-apr-15 GC -> add ##HOME##tttt##, to force reload of home page after tttt miliseconds
           28-feb-16 GC -> fix some bugs and add features JGC
*/  
class presentation extends db_utils{
  
  var $searchvalue;
  
    // 2/3/08-GC: add class parameter.
  function WriteCombo($vals, $name, $selected="", $first="", $class="",  $tabidx="", $OnFocusEvent="", $OnChangeEvent="", $OnBlurEvent=""){
    $res = "<select name='$name' id='$name' style='vertical-align:center' ";
    if (!empty($class)) $res .= "class='$class' ";
    if (!empty($tabidx)) $res .= "tabindex='$tabidx' ";
    if (!empty($OnFocusEvent)) $res .= "onfocus='$OnFocusEvent(\"$name\");' ";
    if (!empty($OnChangeEvent)) $res .= "onchange='$OnChangeEvent(\"$name\");' ";
    if (!empty($OnBlurEvent)) $res .= "onblur='$OnBlurEvent(\"$name\");' ";
    $res .= ">\n";
    if (!empty($selected))
    $selection=$selected;
    else
    $selection="";
    if (!empty($first)) {
     $res .= "<option value=''>$first</option>\n";
    }
    while(list($val, $opt)=each($vals)){
      $res .= "<option value=\"$val\"".
        ((($val==$selection) and !(is_numeric($val) and ($val==0) and ($selection=="")))?' selected':'').
        ">$opt</option>\n";
    }
    $res .= "</select>\n";

    return $res;
  }

  // 2/3/08-GC: add class parameter.
  function WriteComboDirect($vals, $name, $selected="", $class="",  $tabidx="", $OnFocusEvent="", $OnChangeEvent="", $OnBlurEvent=""){
    $res = "<select name='$name' id='$name' style='vertical-align:center' ";
    if (!empty($class)) $res .= "class='$class' ";
    if (!empty($tabidx)) $res .= "tabindex='$tabidx' ";
    if (!empty($OnFocusEvent)) $res .= "onfocus='$OnFocusEvent(\"$name\");' ";
    if (!empty($OnChangeEvent)) $res .= "onchange='$OnChangeEvent(\"$name\");' ";
    if (!empty($OnBlurEvent)) $res .= "onblur='$OnBlurEvent(\"$name\");' ";
    $res .= ">\n";
    if (!empty($selected))
    $selection=$selected;
    else
    $selection="";
    while(list($val, $opt)=each($vals)){
      $res .= "<option value=\"$opt\"";
      if ($opt==$selection) $res .=' selected';
      $res .= ">$opt</option>\n";
    }
    $res .= "</select>\n";

    return $res;
  }

  // 24/5/12-GC: add Visible, Hidden columns arrays.
  function WriteComboFlex($vals, $fields, $linked, $name, $selected="", $class="",  $tabidx="", $OnFocusEvent="", $OnChangeEvent="", $OnBlurEvent=""){
    $res = "<select name='$name' id='$name' style='vertical-align:left' ";
    if (!empty(trim($class))) $res .= "class='$class' ";
    if (!empty(trim($tabidx))) $res .= "tabindex='$tabidx' ";
    if (!empty(trim($OnFocusEvent))) $res .= "onfocus='$OnFocusEvent(\"$name\");' ";
    if (!empty(trim($OnChangeEvent))) {
      $OnChangeEvent = str_replace("'","\"",$OnChangeEvent);
      if (strpos($OnChangeEvent,";")===false) $OnChangeEvent .= "(\"$name\");";
      $res .= "onchange='$OnChangeEvent' ";
    }
    if (!empty(trim($OnBlurEvent))) $res .= "onblur='$OnBlurEvent(\"$name\");' ";
    $res .= ">\n";
    if (!empty($selected))
      $selection=$selected;
    else
      $selection="";
    $myFields = (!is_array($fields)) ? explode(",",$fields) : $fields;
    $myLink = (!empty(trim($linked))) ? $linked : "0";
    if (sizeof($myFields)==0) {    //normal process when no fields
      while(list($val, $opt)=each($vals)){
        $res .= "<option value=\"$val\"".((($val==$selection) and !(is_numeric($val) and ($val==0) and ($selection=="")))?' selected':'').">";
        $res .= ">$opt</option>\n";
        }
      }
      else {  //when fields set
        $maxlen = array(); // look for the columns lengths
        for ($i=0;$i<sizeof($myFields);$i++) {
          $tmplen=0;
          if ($separator[$i]==' ') {  // when single space as sep, then next field is merged with this one to compute length
            foreach($vals as $v1) {
              $str1= $v1[$myFields[$i]];
              $str1 = trim($str1);
              $str2= $v1[$myFields[$i+1]];
              $str2 = trim($str2);
              if (strlen($str1.$str2)>$tmplen) $tmplen = strlen($str1.$str2);
            }
            array_push($maxlen,$tmplen+1); // because of single space separator
            array_push($maxlen,0);
            $i++; //skip next col
          }
          else {
            foreach($vals as $v1) {
              $str1= $v1[$myFields[$i]];
              $str1 = trim($str1);
              if (strlen($str1)>$tmplen) $tmplen = strlen($str1);
            }
            array_push($maxlen,$tmplen);
          }
        }
        while(list($val, $opt)=each($vals)){
          if (array_search($name,$myFields)) {  // when return value i in field list
            $selectionValue = $opt[$name];    // in two steps because  trim($opt[$name]) would return an empty string
            $selectionValue = trim($selectionValue);
          }
          else {// takes the first field
            $selectionValue = $opt[$myFields[0]];  // in two steps because  trim($opt[$name]) would return an empty string
            $selectionValue = trim($selectionValue);
          }
          $res .= "<option value=\"".$selectionValue."\"".((($selectionValue==$selection) and !(is_numeric($selectionValue) and ($selectionValue==0) and ($selection=="")))?' selected':'').">";
          for ($i=0;$i<sizeof($myFields);$i++) {
            if ($separator[$i]==' ') {  // when single space as sep, then next field is merged with this one to compute length
              $str1= $opt[$myFields[$i]];
              $str1 = trim($str1);
              $str2= $opt[$myFields[$i+1]];
              $str1 .= " ".trim($str2);  // build merged fields
              $str1 .= str_repeat("&nbsp",$maxlen[$i]-strlen($str1));
              $res .= $str1."&nbsp";
              $i++; //skip next col 
             }
            else {
              $str1= $opt[$myFields[$i]];
              $str1 = trim($str1);
              $str1 .= str_repeat("&nbsp",$maxlen[$i]-strlen($str1));
              $res .= $str1."&nbsp";
            }
         }
         $res .= "</option>\n";
      }
    }
    $res .= "</select>\n";

    return $res;
  }

  // 2/3/08-GC: add class parameter.
  function WriteComboExt($vals, $name="", $selected="", $first="", $class="", $tabidx="", $state=false, $OnEvent=""){
    $res = "<select name='$name' id='$name' style='vertical-align:center' ";
    if (!empty($class)) $res .= "class='$class' ";
    if (!empty($tabidx)) $res .= "tabindex='$tabidx' ";
    if (!empty($OnEvent)) $res .= "$OnEvent ";
    if (!$state) $res = $res." DISABLED ";
    $res = $res.">\n";
    if (isset($selected))
    $selection=$selected;
    else
    $selection="";
    if (!empty($first)) {
     $res .= "<option value=''>$first</option>\n";
    }
    while(list($val, $opt)=each($vals)){
      $res .= "<option value=\"$val\"".
        ((($val==$selection) and !(is_numeric($val) and ($val==0) and ($selection=="")))?' selected':'').
        ">$opt</option>\n";
    }
    $res .= "</select>\n";

    return $res;
  }

  function WriteComboONCHANGE($vals=null, $name="", $selected="", $class="",  $tabidx="", $OnFocusEvent="", $procin="", $procout=""){
    $res = "<select name='$name' id='$name' style='vertical-align:center' ONMOUSEOVER='$procin' ONMOUSEOUT='$procout'>\n";
    if (!empty($class)) $res .= "class='$class' ";
    if (!empty($tabidx)) $res .= "tabindex='$tabidx' ";
    if (!empty($OnFocusEvent)) $res .= "onfocus='$OnFocusEvent(\"$name\");' ";
    if (isset($selected))
    $selection=$selected;
    else
    $selection="";
    while(list($val, $opt)=each($vals)){
      $res .= "<option value=\"$val\"".
        ((($val==$selection) and !(is_numeric($val) and ($val==0) and ($selection=="")))?' selected':'').
        ">$opt</option>\n";
    }
    $res .= "</select>\n";

    return $res;
  }

  function WriteLookupArray($valname, $titlename, $table){
    global $db;

    $ret = array();

    $reslt=dbquery("select $valname, $titlename from $table");
    while($row=dbnext($reslt)){
      $ret[$row[0]] = $row[1];
    }
    return $ret;
  }

  function WriteLookupCombo($name, $valname, $titlename, $table, $selected){
    return WriteCombo(WriteLookupArray($valname, $titlename, $table), $name, $selected);
  }

  // 24/5/12-GC: add Visible, Hidden columns arrays.
  function WriteSelectionFlex($vals, $fields, $separator, $name, $selected="", $class="",  $tabidx="", $OnFocusEvent="", $OnChangeEvent="", $OnBlurEvent=""){
    $res = "<select name='$name' style='vertical-align:left' ";
    if (!empty($class)) $res .= "class='$class' ";
    if (!empty($tabidx)) $res .= "tabindex='$tabidx' ";
    if (!empty($OnFocusEvent)) $res .= "onfocus='$OnFocusEvent(\"$name\");' ";
    if (!empty($OnChangeEvent)) $res .= "onchange='$OnChangeEvent(\"$name\");' ";
    if (!empty($OnBlurEvent)) $res .= "onblur='$OnBlurEvent(\"$name\");' ";
    $res .= ">\n";
    if (!empty($selected))
      $selection=$selected;
    else
      $selection="";
    if (sizeof($fields)==0) {    //normal process when no fields
      $res .= "<option value=\"$val\"".((($val==$selection) and !(is_numeric($val) and ($val==0) and ($selection=="")))?' selected':'').">";
      while(list($val, $opt)=each($vals)){
        $res .= ">$opt</option>\n";
      }
    }
    else {  //when fields set
      $maxlen = array(); // look for the columns lengths
      for ($i=0;$i<sizeof($fields);$i++) {
        $tmplen=0;
        if ($separator[$i]==' ') {  // when single space as sep, then next field is merged with this one to compute length
          foreach($vals as $v1) {
            $str1= $v1[$fields[$i]];
            $str1 = trim($str1);
            $str2= $v1[$fields[$i+1]];
            $str2 = trim($str2);
            if (strlen($str1.$str2)>$tmplen) $tmplen = strlen($str1.$str2);
          }
          array_push($maxlen,$tmplen+1); // because of single space separator
          array_push($maxlen,0);
          $i++; //skip next col
        }
        else {
          foreach($vals as $v1) {
            $str1= $v1[$fields[$i]];
            $str1 = trim($str1);
            if (strlen($str1)>$tmplen) $tmplen = strlen($str1);
          }
          array_push($maxlen,$tmplen);
        }
      }
      while(list($val, $opt)=each($vals)){
        if (array_search($name,$fields)) {  // when return value i in field list
          $selectionValue = $opt[$name];    // in two steps because  trim($opt[$name]) would return an empty string
          $selectionValue = trim($selectionValue);
        }
        else {// takes the first field
          $selectionValue = $opt[$fields[0]];  // in two steps because  trim($opt[$name]) would return an empty string
          $selectionValue = trim($selectionValue);
        }
        $res .= "<option value=\"".$selectionValue."\"".((($selectionValue==$selection) and !(is_numeric($selectionValue) and ($selectionValue==0) and ($selection=="")))?' selected':'').">";
        for ($i=0;$i<sizeof($fields);$i++) {
          if ($separator[$i]==' ') {  // when single space as sep, then next field is merged with this one to compute length
            $str1= $opt[$fields[$i]];
            $str1 = trim($str1);
            $str2= $opt[$fields[$i+1]];
            $str1 .= " ".trim($str2);  // build merged fields
            $str1 .= str_repeat("&nbsp",$maxlen[$i]-strlen($str1));
            $res .= $str1."&nbsp";
            $i++; //skip next col 
           }
          else {
            $str1= $opt[$fields[$i]];
            $str1 = trim($str1);
            $str1 .= str_repeat("&nbsp",$maxlen[$i]-strlen($str1));
            $res .= $str1."&nbsp";
          }
        }
        $res .= "</option>\n";
      }
    }
    $res .= "</select>\n";
    return $res;
  }

  /*
    This will returns the nr of rows in the passed table, containing the passed filter
  */
  function getNrOfRowsContaining($filter,$table) {
    $temp = $this->db_query("SELECT * from ".$table." where ".$filter, $this->table_struct[$table]['fields'][0]);  // only the id field is needed to count the records
    return sizeof($temp);
  }

  /*
  This function returns the table header (from <table til first <tr ) for the passed level (0=outermost))
  */
  function getHeader($level,$src) {
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $begin = stripos($src,"<table",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$level);
    if ($begin===false) return ""; //nothing found for this level
    $end = stripos($src,"<tr",$begin);
    if ($end===false) return ""; //nothing found for this level
   return substr($src,$begin,$end-$begin); 
  }
  
  /*
  This function returns the nr of rows in table (from <table til first <\table ) for the passed level (0=outermost))
  */
  function getNrRows($level,$src) {
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $begin = stripos($src,"<table",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$level);
    if ($begin===false) return 0; //nothing found for this level
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $end = strripos($src,"</table>",$pos);
      if ($end!==false) $pos=$end-strlen($src)-1;
      $cur++;
    } while ($cur<=$level);
    if ($end===false) return 0; //error: no matching end of table for this level, returns 0
    $tempo = substr($src,0,$end-6);
    $tempo = substr($tempo,$begin+6); // extract table block (without <table and </table>)
    $begin = stripos($tempo,"<table"); // search for an eventual table in table
    if ($begin!==false) { //when has one we must find the last </table> and remove the whole stuff from $tempo
      $end = strripos($tempo,"</table>");
      if ($end===false)  return 0; //table without end of table: layout error for this level
      $tempo = substr($tempo,0,$begin).substr($tempo,$end+8); // remove eventual nested table(s))
    }
    $count=0;
    $tempo = str_replace("<tr","<tr",$tempo,$count); 
   return $count;
  }
  
   /*
  This function returns the nr of cols in table (from <table til first <\table ) for the passed level (0=outermost))
  */
  function getNrCols($row,$level,$src) {
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $begin = stripos($src,"<table",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$level);
    if ($begin===false) return 0; //nothing found for this level
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $end = strripos($src,"</table>",$pos);
      if ($end!==false) $pos=$end-strlen($src)-1;
      $cur++;
    } while ($cur<=$level);
    if ($end===false) return 0; //error: no matching end of table for this level, returns 0
    $tempo = substr($src,0,$end-6);
    $tempo = substr($tempo,$begin+6); // extract table block (without <table and </table>)
    $begin = stripos($tempo,"<table"); // search for an eventual table in table
    if ($begin!==false) { //when has one we must find the last </table> and remove the whole stuff from $tempo
      $end = strripos($tempo,"</table>");
      if ($end===false)  return 0; //table without end of table: layout error for this level
      $tempo = substr($tempo,0,$begin).substr($tempo,$end+8); // remove eventual nested table(s))
    }
    // now we have a clean levex $level table without <table and </table>
    // move on the right row
    $cur = 0; // current index
    $pos = 0; // current start pos
    do {  // search the $idxl th occurence of <tr
      $begin = stripos($tempo,"<tr",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$row);
    if ($begin===false) return ""; //nothing found for this index
    $end = stripos($tempo,"</tr>",$begin);
    if ($end===false) return ""; //nothing found for this level
    $tempo = substr($tempo,$begin,$end-$begin+5); // Now we have the row
    $count=0;
    $tempo = str_replace("<td","<td",$tempo,$count); 
   return $count;
  }
  
  /*
  This function returns the row[index] content in table (from <tr til first > ) for the passed level (0=outermost))
  */
  function getRow($idx,$level,$src) {
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $begin = stripos($src,"<table",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$level);
    if ($begin===false) return ""; //nothing found for this level
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $end = strripos($src,"</table>",$pos);
      if ($end!==false) $pos=$end-strlen($src)-1;
      $cur++;
    } while ($cur<=$level);
    if ($end===false) return ""; //error: no matching end of table for this level, returns ""
    $tempo = substr($src,0,$end-6);
    $tempo = substr($tempo,$begin+6); // extract table block (without <table and </table>)
    $begin = stripos($tempo,"<table"); // search for an eventual table in table
    if ($begin!==false) { //when has one we must find the last </table> and remove the whole stuff from $tempo
      $end = strripos($tempo,"</table>");
      if ($end===false)  return ""; //table without end of table: layout error for this level
      $tempo = substr($tempo,0,$begin).substr($tempo,$end+8); // remove eventual nested table(s))
    }
    $cur = 0; // current index
    $pos = 0; // current start pos
    do {  // search the $idxl th occurence of <tr
      $begin = stripos($tempo,"<tr",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$idx);
    if ($begin===false) return ""; //nothing found for this index
    $end = stripos($tempo,">",$begin);
    if ($end===false) return ""; //nothing found for this level
   return substr($tempo,$begin,$end-$begin+1); 
  }
  
  /*
  This function returns the col[index] content in table (from <tr til first > ) for the passed level (0=outermost))
  */
  function getCol($row,$idx,$level,$src) {
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $begin = stripos($src,"<table",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$level);
    if ($begin===false) return ""; //nothing found for this level
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $end = strripos($src,"</table>",$pos);
      if ($end!==false) $pos=$end-strlen($src)-1;
      $cur++;
    } while ($cur<=$level);
    if ($end===false) return ""; //error: no matching end of table for this level, returns ""
    $tempo = substr($src,0,$end-6);
    $tempo = substr($tempo,$begin+6); // extract table block (without <table and </table>)
    $begin = stripos($tempo,"<table"); // search for an eventual table in table
    if ($begin!==false) { //when has one we must find the last </table> and remove the whole stuff from $tempo
      $end = strripos($tempo,"</table>");
      if ($end===false)  return ""; //table without end of table: layout error for this level
      $tempo = substr($tempo,0,$begin).substr($tempo,$end+8); // remove eventual nested table(s))
    }
    // now we have a clean levex $level table without <table and </table>
    // move on the right row
    $cur = 0; // current index
    $pos = 0; // current start pos
    do {  // search the $idxl th occurence of <tr
      $begin = stripos($tempo,"<tr",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$row);
    if ($begin===false) return ""; //nothing found for this index
    $end = stripos($tempo,"</tr>",$begin);
    if ($end===false) return ""; //nothing found for this level
    $tempo = substr($tempo,$begin,$end-$begin+5); // Now we have the row
    $cur = 0; // current index
    $pos = 0; // current start pos
    do {  // search the $idxl th occurence of <tr
      $begin = stripos($tempo,"<td",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$idx);
    if ($begin===false) return ""; //nothing found for this index
    $end = stripos($tempo,">",$begin);
    if ($end===false) return ""; //nothing found for this level
   return substr($tempo,$begin,$end-$begin+1); 
  }
  
  /*
  This function returns the name found as content of cell[$row,$col] in table (from <tr til first > ) for the passed level (0=outermost))
  */
  function getName($row,$col,$level,$src) {
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $begin = stripos($src,"<table",$pos);
      if ($begin!==false) $pos=$begin+1;
      $cur++;
    } while ($cur<=$level);
    if ($begin===false) return ""; //nothing found for this level
    $cur = 0; // current level
    $pos = 0; // current start pos
    do {  // search the $level th occurence of <table
      $end = strripos($src,"</table>",$pos);
      if ($end!==false) $pos=$end-strlen($src)-1;
      $cur++;
    } while ($cur<=$level);
    if ($end===false) return ""; //error: no matching end of table for this level, returns ""
    $tempo = substr($src,0,$end-6);
    $tempo = substr($tempo,$begin+6); // extract table block (without <table and </table>)
    $subbegin = stripos($tempo,"<table"); // search for an eventual table in table
    if ($subbegin!==false) { //when has one we must find the last </table> and remove the whole stuff from $tempo
      $subend = strripos($tempo,"</table>");
      if ($subend===false)  return ""; //table without end of table: layout error for this level
    }
    // now we know where the sub <table and </table> is placed
    // move on the right row
    $cur = 0; // current index
    $pos = 0; // current start pos
    do {  // search the $idxl th occurence of <tr
      $begin = stripos($tempo,"<tr",$pos);
      if ($begin!==false) {
        $pos=$begin+3;
        if ($subbegin===false || $begin<$subbegin || $begin>$subend) $cur++;
      } 
    } while ($cur<=$row && $begin!==false);
    if ($begin===false) return ""; //nothing found for this index
    $cur = 0; // current index
    $pos = $begin; // current start pos
    do {  // search the next occurence of </tr>
      $end = stripos($tempo,"</tr>",$pos);
      if ($end!==false) {
        $pos=$end+5;
        if ($subbegin===false || $end<$subbegin || $end>$subend) $cur++;
      } 
    } while ($cur==0 && $begin!==false);
    if ($end===false) return ""; //nothing found for this index
    $tempo = substr($tempo,$begin,$end-$begin+5); // Now we have the row
    $subbegin = stripos($tempo,"<table"); // search for an eventual table in table
    if ($subbegin!==false) { //when has one we must find the last </table> and remove the whole stuff from $tempo
      $subend = strripos($tempo,"</table>");
      if ($subend===false)  return ""; //table without end of table: layout error for this level
    }
    // move on the right col
    $cur = 0; // current index
    $pos = 0; // current start pos
    do {  // search the $idxl th occurence of <tr
      $begin = stripos($tempo,"<td",$pos);
      if ($begin!==false) {
        $pos=$begin+3;
        if ($subbegin===false || $begin<$subbegin || $begin>$subend) $cur++;
      } 
    } while ($cur<=$col && $begin!==false);
    if ($begin===false) return ""; //nothing found for this index
    $cur = 0; // current index
    $pos = $begin; // current start pos
    do {  // search the next occurence of </td>
      $end = stripos($tempo,"</td>",$pos);
      if ($end!==false) {
        $pos=$end+5;
        if ($subbegin===false || $end<$subbegin || $end>$subend) $cur++;
      } 
    } while ($cur==0 && $begin!==false);
    if ($end===false) return ""; //nothing found for this index
    $tempo = substr($tempo,$begin,$end-$begin+5); // Now we have the column
    $begin = stripos($tempo,">"); // get end of <td
    $end = strripos($tempo,"</td>"); // last </td>
    $tempo = substr($tempo,$begin+1,$end-$begin-1); // Now we have the column content
   return trim(html_entity_decode($tempo)); // remove evtl specials like &nbsp; etc.
  }
  
  /*
  This function use the content of session page layout for session pagelayout id and returns the html table header for the passed level (0 = outermost))
  */
  function getHeaderLayoutTable($level) {
    $srchtml = "";
    for ($i=0;$i<sizeof($_SESSION['layouts']);$i++)
      if ($_SESSION['layouts'][$i]['id']==$_SESSION['pageLayoutID'])
        $srchtml = $_SESSION['layouts'][$i]['layout_struct'];
    $srchtml = $this->cleanEscaped($srchtml);
    $ret= array('header' => "", 'nrRows' => "", 'row' => array(), 'nrCols' => array(), 'col' =>  array(), 'name' =>  array());
    $ret['header'] = $this->getHeader($level,$srchtml);
    $ret['nrRows'] = $this->getNrRows($level,$srchtml);
    for($i=0;$i<$ret['nrRows'];$i++) {
      $ret['nrCols'][$i] = $this->getNrCols($i,$level,$srchtml);
      $ret['row'][$i] = $this->getRow($i,$level,$srchtml);
      for($j=0;$j<$ret['nrCols'][$i];$j++) {
        $ret['col'][$i][$j] = $this->getCol($i,$j,$level,$srchtml);
        $ret['name'][$i][$j] = $this->getName($i,$j,$level,$srchtml);  
      }
    }
    
    return $ret;
  }
  
  function getHTMLdiashow($first) {
    $comment = $first;
    $comment = str_replace("<p>","",$comment);
    $comment = str_replace("</p>","",$comment);
    $comment = str_replace("\t","",$comment);
    $comment = str_replace("\r","",$comment);
    $comment = str_replace("\n","",$comment);
    echo '<table class="diashow_table" >';
    echo '<tr><td><div class="diashow"></div>';
    if ($_SESSION['has_overlay']=="yes") {
      $ovl_pic = $this->getOverlayPicture();  // get overlay
      if (!empty($ovl_pic)) { //when some overlay, create an invisible div
        echo "<div class='overlay'><img id='overlay' src='$ovl_pic' alt='' /></div>";
      }
    }
    echo '</td></tr>';
    // normal end of table
    echo '</table>';
    if ($_SESSION['diashowcomment']=="on")
      echo '<div class="diashow_comment" ><div id="diashowComment" class="diashow_cmt" style="visibility:visible">'.$comment.'</div></div>';
    if ($_SESSION['diashowbuttons']=="on") {  // when buttons on
      echo '<div class="diashow_buttons" >';
      if ($_SESSION['diashowbutton_prev']=="on" ) // when previous
        echo '<span class="diashow_prev"><span id="prev"><</span></span>';
      if ($_SESSION['diashownav']=="on") // when navigation
        echo '<span class="diashow_pager"><span id="nav">&nbsp;</span></span>';
      if ($_SESSION['diashowbutton_next']=="on" ) // when next
        echo '<span class="diashow_next"><span id="next">></span></span>';
      echo '</div>';
    }
    else // when no buttons, but perhaps navigation
    if ($_SESSION['minidiashownav']=="on") {
      echo '<div class="minidiashow_nav" >';
      if ($_SESSION['minidiashowbutton_prev']=="on" ) // when previous
        echo '<span class="minidiashow_prev"><span id="prev"><</span></span>';
      if ($_SESSION['minidiashownav']=="on") // when navigation
        echo '<span class="minidiashow_pager"><span id="nav">&nbsp;</span></span>';
      if ($_SESSION['minidiashowbutton_next']=="on" ) // when next
        echo '<span class="minidiashow_next"><span id="next">></span></span>';
      echo '</div>';
    }
  }
  
  function getHTMLminidiashow($first) {
    $comment = $first;
    $comment = str_replace("<p>","",$comment);
    $comment = str_replace("</p>","",$comment);
    $comment = str_replace("\t","",$comment);
    $comment = str_replace("\r","",$comment);
    $comment = str_replace("\n","",$comment);
    echo '<table class="minidiashow_table" >';
    echo '<tr><td><div class="minidiashow"></div>';
    echo '</td></tr>';
    // normal end of table
    echo '</table>';
    if ($_SESSION['minidiashowcomment']=="on")
      echo '<div class="minidiashow_comment" ><div id="diashowComment" class="minidiashow_cmt" style="visibility:visible">'.$comment.'</div></div>';
    if ($_SESSION['minidiashowbuttons']=="on") {  // when buttons on
      echo '<div class="minidiashow_buttons" >';
      if ($_SESSION['minidiashowbutton_prev']=="on" ) // when previous
        echo '<span class="minidiashow_prev"><span id="prev"><</span></span>';
      if ($_SESSION['minidiashownav']=="on") // when navigation
        echo '<span class="minidiashow_pager"><span id="nav">&nbsp;</span></span>';
      if ($_SESSION['minidiashowbutton_next']=="on" ) // when next
        echo '<span class="minidiashow_next"><span id="next">></span></span>';
      echo '</div>';
    }
    else // when no buttons, but perhaps navigation
    if ($_SESSION['minidiashownav']=="on") {
      echo '<div class="minidiashow_nav" >';
      if ($_SESSION['minidiashowbutton_prev']=="on" ) // when previous
        echo '<span class="minidiashow_prev"><span id="prev"><</span></span>';
      if ($_SESSION['minidiashownav']=="on") // when navigation
        echo '<span class="minidiashow_pager"><span id="nav">&nbsp;</span></span>';
      if ($_SESSION['minidiashowbutton_next']=="on" ) // when next
        echo '<span class="minidiashow_next"><span id="next">></span></span>';
      echo '</div>';
    }
  }

  function getHTMLgallery() {
    $galerie =  $this->getGalleries();
    if (sizeof ($galerie)!=0) {
      $infos = $this->getMenuInfo($_SESSION['page']);
      echo "<div class='mosaic'><div class='title'>".$infos['menuTXT']."</div>";
      echo "<table class='pictable'>";
      $horizIdx=0;
      $flex_item = "";
      for ($n=0;$n<sizeof($galerie);$n++) {
        if ($_SESSION['use_gallery_flex']==true && $horizIdx==0) {  // when flex grid wanted
          $flex_item = "class='gallery_flex-item'";
          $row = "<tr class='gallery_flex-container'>";
        } else
          $row = ($horizIdx==0) ? "<tr>" : "";
        echo $row;
        $next=$n+1;
        if ($next>=sizeof($galerie)) $next=0;
//        $pict_title = $_SESSION['gallery_title'] ? strip_tags($galerie[$n]['pic_text']) : "";
        // $pict_text has the real HTML content for the given gallery item
        $pict_text = $this->cleanEscaped($galerie[$n]['pic_text']);
        // when title is wanted, extract ONLY the firat line to have pure text only
        if ($_SESSION['gallery_title']) {
          $pict_title = strip_tags($galerie[$n]['pic_text']);
          
        }
        $gallery_text = "";
        if ($_SESSION['gallery_text']) {
          $gallery_text = "<td class='text ".$flex_item."'>$pict_text</td>";
        }
        $gallery_pic_HTML = "";
        if ($_SESSION['gallery_pic_HTML']) {
          $gallery_pic_HTML = "<td class='gallery_right ".$flex_item."'>$pict_text</td>";  
        }
        $MyPicture = $this->table_defs['galleries']['paths']['size2'].$galerie[$n]['pic_name'];
        $MyBigPicture = $this->table_defs['galleries']['paths']['original'].$galerie[$n]['pic_name'];
        $MyStyle = 'style="background-image: url(\''.$MyBigPicture.'\')"';
        if ($_SESSION['gallery_singleColorbox']==true) { // on click on gallery image, only this image is in colorbox
          if ($_SESSION['gallery_using_background']=="yes") {
            echo "$gallery_text<td $flex_item $MyStyle><a class='inline' href='#inline_content".$n."x' title='$pict_title'>
                  <div style='display:none;'>
                    <div id='inline_content".$n."x' style='padding:10px; background:#fff;'>
                      <table id='mycolorbox' >
                        <tr>
                          <td><img src='$MyBigPicture'/></td>
                          <td style='padding-left: 40px'>$pict_text</td>
                        </tr>
                      </table>
                    </div>
                  </div></td>";
          }
          else {
            if ($_SESSION['gallery_using_background']=="yes") {
              echo "$gallery_text<td $flex_item $MyStyle><a class='inline' href='#inline_content".$n."x' title='$pict_title'>
                    <div style='display:none;'>
                      <div id='inline_content".$n."x' style='padding:10px; background:#fff;'>
                        <table id='mycolorbox' >
                          <tr>
                            <td><img src='$MyBigPicture'/></td>
                            <td style='padding-left: 40px'>$pict_text</td>
                          </tr>
                        </table>
                      </div>
                  </div></td>";
            }
            else {
              echo "$gallery_text<td $flex_item><a class='inline' href='#inline_content".$n."x' title='$pict_title'>
                    <img class='image' src='".$MyPicture."' alt='$pict_title' /></a>";
              echo "<div style='display:none;'>
                      <div id='inline_content".$n."x' style='padding:10px; background:#fff;'>
                      <table id='mycolorbox' >
                        <tr>
                          <td><img src='$MyBigPicture'/></td>
                          <td style='padding-left: 40px'>$pict_text</td>
                        </tr>
                      </table>
                    </div>
                  </div></td>";
            }
          }
        } else { // on click on gallery image, all images are availabel as in colorbox
          if ($_SESSION['gallery_using_background']=="yes") 
            echo "$gallery_text<td $flex_item $MyStyle><a class='gallery1' href='$MyBigPicture' title='$pict_title'><div class='gallery_flex-item'></div></a></td>";
          else
            echo "$gallery_text<td $flex_item><a class='gallery1' href='$MyBigPicture' title='$pict_title'>
                  <img class='image' src='".$MyPicture."' alt='$pict_title' /></a></td>";
        }
        $horizIdx++;
        $row = ($horizIdx==$_SESSION['gallery_picts_per_line']) ? "</tr>" : "";
        $horizIdx = ($horizIdx==$_SESSION['gallery_picts_per_line']) ? 0 : $horizIdx;
        echo $row;
      }
      if ($row!="</tr>") echo "</tr>";
      echo "</table>";
      echo "</div>";
    }
  }

  /*
  This function returns an html string for picture from table picts for current page and do an overlay with picture given in admin settings
  Remark: only the FIRST picture for the invoked page is used
  */
  function getHTMLovl_picts() {
    $return = "";
    $picts = $this->getPicts(); // Normal picture
    $i = 0;
    if (isset($picts[$i])) { // when wanted picture exists
      if ($picts[$i]['link']!=="") // add href when some
        $return .= "<a href='".$this->table_defs['picts']['paths']['original'].$picts[$i]['link']."'>";
      $return .= "<div class='ovl_pict'><img id='overlayed' src='".$this->table_defs['picts']['paths']['original'].$picts[$i]['name']."' alt='".$picts[$i]['text']."' /></div>";
      $ovl_pic = $this->getOverlayPicture();  // get overlay
      if (!empty($ovl_pic)) { //when some overlay, create an invisible div
        $return .= "<div class='overlay'><img id='overlay' src='$ovl_pic' alt='' /></div>";
      }
//      $return .= "<img class='ovl_pict' src='".$this->table_defs['picts']['paths']['original'].$picts[$i]['name']."' alt='".$picts[$i]['text']."' />";
      if ($picts[$i]['link']!=="")
        $return .= "</a>";        // href terminator
   }
    return $return;
  }

    /*
  This function returns an href string for picture from table picts for current page 
  */
  function getHREFpicts($index) {
    $return = "";
    if (isset($index))
      $i = $index;
    else
      $i = 0;
    $picts = $this->getPicts();
    if (isset($picts[$i])) { // when wanted picture exists
      $return .= $this->table_defs['picts']['paths']['original'].$picts[$i]['name'];
    }
    return $return;
  }
/*
  This function returns an html string for picture from table picts for current page 
  */
  function getHTMLpicts($index) {
    $return = "";
    if (isset($index))
      $i = $index;
    else
      $i = 0;
    $picts = $this->getPicts();
    if (isset($picts[$i])) { // when wanted picture exists
      if ($picts[$i]['link']!=="") // add href when some
        $return .= "<a href='".$this->table_defs['picts']['paths']['original'].$picts[$i]['link']."'>";
      $return .= "<img class='pict' src='".$this->table_defs['picts']['paths']['original'].$picts[$i]['name']."' alt='".$picts[$i]['text']."' />";
      if ($picts[$i]['link']!=="")
        $return .= "</a>";        // href terminator
    }
    return $return;
  }
                // <div class='banner'>  </div>
  function getHTMLbanner() {
    $return = "";
    $return= "
            <table class='banner_table'>
            <tr><td rowspan='2' class='bannerlogo'>".$this->getBannerLogo($browser->isMobile())."</td><td class='bannerlanguage'>".$this->getBannerLanguage()."</td></tr>
            <tr><td class='bannergetlanguage'>".$this->getBannerLanguageSelect()."</td></tr>
            </table>
          ";
    return $return;    
  }

  function getHTMLmarquee() {
    $return = "";
    // USING CSS: Actually is not IR compatible 
    $return= "
            <div class='marquee fake'><span>
              <table>
              <tr>";
    $marquees = $this->db_queryAll("SELECT * FROM marquees WHERE (menu_ref=".$_SESSION['pageID']." OR menu_ref=0) AND active=1 ORDER BY sorting, name");
    if ($_SESSION['randomize_marquees']=="yes") shuffle($marquees);
    for ($i=0;$i<sizeof($marquees);$i++) {
      $link = $marquees[$i]['link']; 
      $srcImg = $this->table_defs["marquees"]["paths"]["original"].$marquees[$i]['name'];
      $return .= "<td><a href='$link' target='_blank'>
                    <img src='$srcImg' class='marquee_img'>
                  </a></td>
                  <td class='marquee_sep'>&nbsp;</td>";
    }
    $return .= "</tr></table></span></div>";
  /*
    // USING HTML <MARQUEE>
    $return= "
            <table class='marquee_height'>
              <tr>
                <td>                
                  <marquee class='marquee_row' scrolldelay='5' scrollamount='3'><table class='marquee_table'><tr>";
    $marquees = $this->db_queryAll("SELECT * FROM marquees WHERE menu_ref=".$_SESSION['pageID']." AND active=1 ORDER BY sorting");
    for ($i=0;$i<sizeof($marquees);$i++) {
      $link = $marquees[$i]['link']; 
      $srcImg = $this->table_defs["marquees"]["paths"]["size1"].$marquees[$i]['name'];
      $return .= "      <td class='marquee_sep'></td><td>
                    <a href='$link' target='_blank'>
                      <img src='$srcImg' class='marquee_img'>
                    </a>
                  </td>";
    }
    $return .= "
               </tr></table></marquee></td>
              </tr>
            </table>";
            */            
    return $return;    
  }

  function getHTMLmenuRow() {
    global $menuRow;
    $return = "";
    $return= "<div class= 'limmat_menu'>".$menuRow."</div>";
    return $return;    
  }

  function getHTMLvideo($idx=0) {
    $return = "";
    $temp = $this->db_query("SELECT * from videos where menu_ref=".$_SESSION['pageID']." order by sorting", $this->table_struct['videos']['fields']);
    if (sizeof($temp)>0 && $temp[$idx]['active']) { //when some active
      $vidName = "";
      if (!empty($temp[$idx]['vid_link']))
        $vidName = $temp[$idx]['vid_link'];
      else
        $vidName = $this->table_defs['videos']['paths']['original'].$temp[$idx]['vid_name'];
      if (strpos(strtolower($vidName),"www.youtube.com")!==false) {
         $return .= "<iframe width='100%' src='$vidName'></iframe>";
        
      }
      else {
        if (!empty($vidName)) {
          $ext = strtolower(strrchr($vidName , "."));
          // Sepcial case for apple plattforms: check when extension is not .mov if a .mov video exists
          // When it is the case, use directly .mov feature, else continue normally
          if ($ext!=".mov" && strpos($_SERVER['HTTP_USER_AGENT'],"Safari")!==false && strpos($_SERVER['HTTP_USER_AGENT'],"Chrome")===false) {
            $tmpName = str_replace("$ext",".mov",$vidName);
            if (file_exists($tmpName)) {
              $return .= "<div class='videoBox' id='".$_SESSION['page']."'>
                            <OBJECT CLASSID='clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B'
                             CODEBASE='http://www.apple.com/qtactivex/qtplugin.cab'
                             HEIGHT='100%'
                             WIDTH='100%'
                            >
                            <PARAM NAME='src' VALUE='$tmpName' >
                            <PARAM NAME='autoplay' value='true'>
                            <PARAM NAME='controller' value='true'>
                            <PARAM NAME='loop' value='false'>
                            <PARAM NAME='scale' value='Aspect'>
                            <EMBED
                             SRC='$tmpName'
                             HEIGHT='100%' WIDTH='100%'
                             AUTOPLAY='true'
                             CONTROLLER='true'
                             LOOP='false'
                             SCALE='Aspect'
                             TYPE='video/quicktime'
                             PLUGINSPAGE='http://www.apple.com/quicktime/download/'
                            /EMBED>
                            Um das Video zu schauen, sollten Sie Apple Quicktime plug-in (<a href='http://www.apple.com/quicktime/download/'>
                            http://www.apple.com/quicktime/download/</a>) nachuntenladen und installieren
                            </OBJECT>
                          </div>";
              return $return; 
            }
          }
          switch ($ext) {
            case ".mp4": $video = "type='video/mp4'"; break;
            case ".ogg": $video = "type='video/ogg'"; break;
            case ".webm": $video = "type='video/webm'"; break;
            case ".m4v": $video = "type='video/m4v'"; break;
            case ".mov": 

              $return .= "<div class='videoBox' id='".$_SESSION['page']."'>
                            <OBJECT CLASSID='clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B'
                             CODEBASE='http://www.apple.com/qtactivex/qtplugin.cab'
                             HEIGHT='100%'
                             WIDTH='100%'
                            >
                            <PARAM NAME='src' VALUE='$vidName' >
                            <PARAM NAME='autoplay' value='true'>
                            <PARAM NAME='controller' value='true'>
                            <PARAM NAME='loop' value='false'>
                            <PARAM NAME='scale' value='Aspect'>
                            <EMBED
                             SRC='$vidName'
                             HEIGHT='100%' WIDTH='100%'
                             AUTOPLAY='true'
                             CONTROLLER='true'
                             LOOP='false'
                             SCALE='Aspect'
                             TYPE='video/quicktime'
                             PLUGINSPAGE='http://www.apple.com/quicktime/download/'
                            /EMBED>
                            Um das Video zu schauen, sollten Sie Apple Quicktime plug-in (<a href='http://www.apple.com/quicktime/download/'>
                            http://www.apple.com/quicktime/download/</a>) nachuntenladen und installieren
                            </OBJECT>
                          </div>"; 
                          break;
            default : $video = "";
          }
    //      add in Apache-Datei httpd.conf 'AddType video/mp4 .mp4' -->
          if ($ext!=".mov") {
            $tmpName = str_replace("$ext",".webm",$vidName);
            $return .= "<video width='100%' controls autoplay>";
//            if (strpos($_SERVER['HTTP_USER_AGENT'],"Chrome")!==false)
              //$return .= "<source src='$tmpName' type='video/webm'>";  // add for chrome, because it always use the first video declared.
            $return .= "<source src='$vidName' $video>
                        Your browser does not support the video tag.</video>";
          }
        }
      }
    }
    return $return;
  }

  function getHTMLpdf() {
    $return = "";
    $temp = $this->db_query("SELECT * from filelinks where menu_ref=".$_SESSION['pageID']." order by sorting", $this->table_struct['filelinks']['fields']);
    if (sizeof($temp)>0) { //when some 
      $pdfName = "";
      $pdfName = $this->get_for_current_language($temp[0]['file1'],$temp[0]['file2'],$temp[0]['file3']);
      $ftype = strtolower(strrchr ($pdfName, "."));
      $file = $this->table_defs['filelinks']['paths']['original'].$pdfName;
      if (!empty($file) && $ftype==".pdf" && file_exists($file)) {
        echo "<object class='pdf_obj' type='application/pdf' data='$file?#zoom=85&scrollbar=0&toolbar=0&navpanes=0' id='pdf_content'>
              <p>Please install PDF reader.</p>
              </object>";
      }
    }
    return $return;
  }
  
/*
  Function to return the menu info in an array menuID, menuTXT (in the current language) and menuLNK for the passed menu title
*/
  function getMenuInfo($menuTitle) {
    if (empty($menuTitle)) return "";
    $MainMenus = $_SESSION['mainmenu'];
    $SubMenus = $_SESSION['submenu'];
    $SubSubMenus = $_SESSION['subsubmenu'];
    $ret= array('menuID'=>'', 'menuTXT'=>'', 'menuLNK'=>''); // to return "" when nothing found
    for ($i=0;$i<sizeof($MainMenus);$i++)
      if ($MainMenus[$i] ["TITLE"]==strtolower($menuTitle)) {
        $ret['menuID']= $MainMenus[$i] ["MID"];
        $ret['menuTXT']= $MainMenus[$i] ["NAME"];
        $ret['menuLNK']= $MainMenus[$i] ["LINK"];
        break;
      }
    if (empty($ret['menuTXT'])) { // when not found in main menus, try in the sub menus   
      for ($i=0;$i<sizeof($MainMenus);$i++)
        for ($j=0;$j<sizeof($SubMenus[$i]);$j++) 
          if ($SubMenus[$i][$j] ["TITLE"]==strtolower($menuTitle)) {
            $ret['menuID']= $SubMenus[$i][$j] ["MID"];
            $ret['menuTXT']= $SubMenus[$i][$j] ["NAME"];
            $ret['menuLNK']= $SubMenus[$i][$j] ["LINK"];
            break(2);
        }
    }  
    if (empty($ret['menuTXT'])) { // if stil not found in sub menus, try in the subsub menus   
      for ($i=0;$i<sizeof($SubSubMenus);$i++)
        if ($SubSubMenus[$i] ["TITLE"]==strtolower($menuTitle)) {
          $ret['menuID']= $SubSubMenus[$i] ["MID"];
          $ret['menuTXT']= $SubSubMenus[$i] ["NAME"];
          $ret['menuLNK']= $SubSubMenus[$i] ["LINK"];
          break(1);
      }  
    }
    return $ret;
  }

/*
    This will return a string with pagemenus items replaced by menu text and css formatting
    The second parameter is the style leading name (by default pg_menu) 
    When using another leading name, please be sure this is also defined in css
  */
  function getHTMLpagemenuItems($str, $pgtyp="pg_menu") {
    $temp = $str;
    while (strpos($temp,"__")!==false || strpos($temp,"##")!==false || strpos($temp,"*#")!==false || strpos($temp,"?#")!==false || strpos($temp,"?*")!==false || strpos($temp,"*_")!==false) { // as long as field to replace
      // look for selected menu item
      $start = strpos($temp,"*#");
      if ($start!==false) { // when found a selected item
        $end = strpos($temp, "#*", $start+2);
        $toreplace = substr($temp,$start+2,$end-$start-2);
        $menuInfo= $this->getMenuInfo($toreplace);
        $temp = substr_replace($temp,"<div class='".$pgtyp."_sel'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
      }
      // look for first menu item (used because top border not wanted)
      $start = strpos($temp,"?#");
      if ($start!==false) { // when found a selected item
        $end = strpos($temp, "#?", $start+2);
        $toreplace = substr($temp,$start+2,$end-$start-2);
        $menuInfo= $this->getMenuInfo($toreplace);
        $temp = substr_replace($temp,"<div class='".$pgtyp."' style='border:none'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
      }
      // look for first menu item but selected (used because top border not wanted)
      $start = strpos($temp,"?*");
      if ($start!==false) { // when found a selected item
        $end = strpos($temp, "*?", $start+2);
        $toreplace = substr($temp,$start+2,$end-$start-2);
        $menuInfo= $this->getMenuInfo($toreplace);
        $temp = substr_replace($temp,"<div class='".$pgtyp."_sel' style='border:none'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
      }
      // look for normal  menu item
      $start = strpos($temp,"##");
      if ($start!==false) { // when found a normal item
        $end = strpos($temp, "##", $start+2);
        $toreplace = substr($temp,$start+2,$end-$start-2);
        $menuInfo= $this->getMenuInfo($toreplace);
        $temp = substr_replace($temp,"<div class='".$pgtyp."'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
      }
      // look for a sub menu item
      $start = strpos($temp,"__");
      if ($start!==false) { // when found a selected item
        // remove eventual breakline
        $brk = strpos($temp,"<br",$start-10); // by looking a little bit before start
        if ($start-$brk<10) { // when got a breakline, remove it
          $end = strpos($temp,">",$brk); 
          $temp = substr_replace($temp,"",$brk,$end-$brk+1);
          // because $temp changed, reevaluate start position
          $start = strpos($temp,"__");
        }
        $end = strpos($temp, "__", $start+2);
        $toreplace = substr($temp,$start+2,$end-$start-2);
        $menuInfo= $this->getMenuInfo($toreplace);
        $temp = substr_replace($temp,"<div class='".$pgtyp."_sub'><a href='".$menuInfo['menuLNK']."'>&nbsp;<img class='".$pgtyp."_sub_img' src='pictures/icons/point_1.jpg'>&nbsp;".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
      }
      // look for a sub menu sel item
      $start = strpos($temp,"*_");
      if ($start!==false) { // when found a selected item
        // remove eventual breakline
        $brk = strpos($temp,"<br",$start-10); // by looking a little bit before start
        if ($start-$brk<10) { // when got a breakline, remove it
          $end = strpos($temp,">",$brk); 
          $temp = substr_replace($temp,"",$brk,$end-$brk+1);
          // because $temp changed, reevaluate start position
          $start = strpos($temp,"*_");
        }
        $end = strpos($temp, "_*", $start+2);
        $toreplace = substr($temp,$start+2,$end-$start-2);
        $menuInfo= $this->getMenuInfo($toreplace);
        $temp = substr_replace($temp,"<div class='".$pgtyp."_sub_sel'><a href='".$menuInfo['menuLNK']."'>&nbsp;<img class='".$pgtyp."_sub_img' src='pictures/icons/point_1.jpg'>&nbsp;".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
      }
    }
    return $temp;
  }

/*
  Function to echo the local page menu (for the current $_SESSION['pageID'] )
  Echo nothing when no pagemenu
*/
  function getHTMLpagemenu() {
    $temp = $this->db_query("SELECT menu from page_menus where page=".$_SESSION['pageID'], "menu");
    if (!empty($temp[0]['menu'])) { // when some page local menu
      $temp = $this->cleanEscaped($temp[0]['menu']);
      echo '<table style="width:100%"><tr><td>';
      $temp = $this->getHTMLpagemenuItems($temp);
/*
      while (strpos($temp,"__")!==false || strpos($temp,"##")!==false || strpos($temp,"*#")!==false || strpos($temp,"?#")!==false || 
             strpos($temp,"?*")!==false || strpos($temp,"*_")!==false || strpos($temp,"_#")!==false) { // as long as field to replace
        // look for selected menu item
        $start = strpos($temp,"*#");
        if ($start!==false) { // when found a selected item
          $end = strpos($temp, "#*", $start+2);
          $toreplace = substr($temp,$start+2,$end-$start-2);
          $menuInfo= $this->getMenuInfo($toreplace);
          $temp = substr_replace($temp,"<div class='pg_menu_sel'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
        }
        // look for first menu item (used because top border not wanted)
        $start = strpos($temp,"?#");
        if ($start!==false) { // when found a selected item
          $end = strpos($temp, "#?", $start+2);
          $toreplace = substr($temp,$start+2,$end-$start-2);
          $menuInfo= $this->getMenuInfo($toreplace);
          $temp = substr_replace($temp,"<div class='pg_menu' style='border:none'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
        }
        // look for first menu item but selected (used because top border not wanted)
        $start = strpos($temp,"?*");
        if ($start!==false) { // when found a selected item
          $end = strpos($temp, "*?", $start+2);
          $toreplace = substr($temp,$start+2,$end-$start-2);
          $menuInfo= $this->getMenuInfo($toreplace);
          $temp = substr_replace($temp,"<div class='pg_menu_sel' style='border:none'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
        }
        // look for normal  menu item
        $start = strpos($temp,"##");
        if ($start!==false) { // when found a normal item
          // Check special keywords
          if (strpos("##LNG##")==$start) {
            $temp = substr_replace($temp,$_SESSION['lang'],$start,strlen("##LNG##"));
          } else { // normal
            $end = strpos($temp, "##", $start+2);
            $toreplace = substr($temp,$start+2,$end-$start-2);
            $menuInfo= $this->getMenuInfo($toreplace);
            $temp = substr_replace($temp,"<div class='pg_menu'><a href='".$menuInfo['menuLNK']."'>".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
          }
        }
        // look for a sub menu item
        $start = strpos($temp,"__");
        if ($start!==false) { // when found a selected item
          // remove eventual breakline
          $brk = strpos($temp,"<br",$start-10); // by looking a little bit before start
          if ($start-$brk<10) { // when got a breakline, remove it
            $end = strpos($temp,">",$brk); 
            $temp = substr_replace($temp,"",$brk,$end-$brk+1);
            // because $temp changed, reevaluate start position
            $start = strpos($temp,"__");
          }
          $end = strpos($temp, "__", $start+2);
          $toreplace = substr($temp,$start+2,$end-$start-2);
          $menuInfo= $this->getMenuInfo($toreplace);
          $temp = substr_replace($temp,"<div class='pg_menu_sub'><a href='".$menuInfo['menuLNK']."'>&nbsp;<img class='pg_menu_sub_img' src='pictures/icons/point_1.jpg'>&nbsp;".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
        }
        // look for a sub menu sel item
        $start = strpos($temp,"*_");
        if ($start!==false) { // when found a selected item
          // remove eventual breakline
          $brk = strpos($temp,"<br",$start-10); // by looking a little bit before start
          if ($start-$brk<10) { // when got a breakline, remove it
            $end = strpos($temp,">",$brk); 
            $temp = substr_replace($temp,"",$brk,$end-$brk+1);
            // because $temp changed, reevaluate start position
            $start = strpos($temp,"*_");
          }
          $end = strpos($temp, "_*", $start+2);
          $toreplace = substr($temp,$start+2,$end-$start-2);
          $menuInfo= $this->getMenuInfo($toreplace);
          $temp = substr_replace($temp,"<div class='pg_menu_sub_sel'><a href='".$menuInfo['menuLNK']."'>&nbsp;<img class='pg_menu_sub_img' src='pictures/icons/point_1.jpg'>&nbsp;".$menuInfo['menuTXT']."</a></div>",$start,strlen($toreplace)+4);
        }
        // look for a Text CMD replacement
        $start = strpos($temp,"_#");
        if ($start!==false) { // when found a selected item
          $end = strpos($temp, "#_", $start+2);
          $toreplace = substr($temp,$start+2,$end-$start-2);
          $toreplace = "##".$toreplace."##";  // add # to have cmd syntax
          $temp = substr_replace($temp,"<div class='pg_menu_text'>".$this->processEditorCmds($toreplace)."</div",$start,strlen($toreplace));
        }
      }
*/
      echo $temp;
      echo '</td></tr></table>';
    }
  }
/*
  function returning the HTML code for Esmeralda circuits list with checkboxes
*/  
  function getHTMLes_circuit() {
    $temp = "<table>";
    $types = $this->db_queryAll($this->table_sql["tournaments_groups"][0]." order by ".$this->table_sql["tournaments_groups"][2]);
    $tournaments = $this->db_queryAll($this->table_sql["tournaments"][0]." order by ".$this->table_sql["tournaments"][2]);
    // Search the text for "Esmeralda finals"
    $finals =  $this->db_queryAll("select * from texts where name LIKE 'Esmeralda finals' AND active=1 order by sorting;");
    $finaltext = "<TD colspan='2' class='text_blue'>Final</td>"; //default
    if (!empty($finals)) $finaltext = "<TD colspan='2'>".$this->get_for_current_language($finals[0]['text1'],$finals[0]['text2'],$finals[0]['text3'])."</td>";
    $NrSpecials=0;
    $NrFinals=0;
    
    for ($k=0;$k<sizeof($types);$k++) { // main loop
      $type_text = $this->get_for_current_language($types[$k]['descr1_tug'],$types[$k]['descr2_tug'],$types[$k]['descr3_tug']);
      $temp .= "<TR><TD colspan='2' class='circuit_type'>".$type_text."</td></tr>";
      $final_displayed = false;
      for ($i=0; $i<sizeof($tournaments);$i++){ // second level loop: display matching tournaments
        if ($tournaments[$i]['type']==$types[$k]['id_tug']) {
          if($tournaments[$i]['final'] == 0){
            $NrSpecials++;
            $turnament_date= $this->format_date($tournaments[$i]['event_date'],"D, d.m.Y");
            $name_value = ereg_replace(" ", "", $tournaments[$i]['location']);
            $temp .= "<TR><TD class='text'><INPUT type=checkbox value=".$name_value." name=".$name_value." id='game".$i."'></TD><TD class='text'>".$turnament_date.": ".$tournaments[$i]['location']."</TD></TR>";
          }
          if($tournaments[$i]['final'] == 1){
            $NrFinals++;
            if (!$final_displayed) {
              $temp .= "<TR>".$finaltext."</tr>";
              $final_displayed = true;
            }
            $turnament_date= $this->format_date($tournaments[$i]['event_date'],"D, d.m.Y");
            $name_value = ereg_replace(" ", "", $tournaments[$i]['location']);
            $temp .= "<TR><TD class='text'><INPUT type=checkbox value=".$name_value." name=".$name_value." id='game".$i."'></TD><TD class='text'>".$turnament_date.": ".$tournaments[$i]['location']."</TD></TR>";
          }
        }
      }
    }
    $temp .= "<TR><TD><INPUT type='hidden' size='-1' value='".$NrSpecials."' name='nrspecials'> 
              <INPUT type='hidden' size='-1' value='".$NrFinals."' name='nrfinals'></TD></TR></table>";
    return $temp;
  }

  /*
  This function is using the PHPmail class to send an e-mail 
  */
  function sendMail() {
    $temp = $this->db_query("SELECT * from tournaments order by event_date", $this->table_struct['tournaments']['fields']);
    $num = count($temp);
    $Turnament = "";

    for ($i=0; $i<$num;$i++){
      $temp[$i]['location'] = $this->cleanEscaped($temp[$i]['location']);
      $temp[$i]['location'] = addslashes($temp[$i]['location']);
      $gamename = ereg_replace(" ", "", $temp[$i]['location']);  //GC: 27apr12 because name doesn't content spaces
      if(isset($_REQUEST[$gamename])){
        $Tournament .= $this->format_date($temp[$i]['event_date']).": ".$temp[$i]['location']."<BR>";
      }
    }
    // get from post:
    $Email = $_POST['email'];
    $FirstName = $_POST['forname'];
    $LastName = $_POST['name'];
    $Address = $_POST['address'];
    $City = $_POST['ZIPcity'];
    $Phone = $_POST['phone'];
    $HomeClub = $_POST['homeclub'];
    $Handicap = $_POST['handicap'];
    $PartnerFirstName = $_POST['firstname2'];
    $PartnerLastName = $_POST['lastname2'];
    $PartnerHomeClub = $_POST['homeclub2'];
    $PartnerHandicap = $_POST['handicap2'];
    $Comment = $_POST['comment'];
    
    $date    = date("d.m.Y");
    $to      =  $this->getEmail();
    if (empty($to)) $to = $_SESSION['E-MAIL3'];
    $subject = $_SESSION['es_register_mail'][$_SESSION['lang']];
    $from = $_POST['email'];
    $fromtext= $_SESSION['es_register_form'][$_SESSION['lang']];
    $replyto = "$Email";
    
    // use the text form es_mail to 
    $temp = $this->db_query("SELECT * from texts where LOWER(name) LIKE 'es_mail'", $this->table_struct['texts']['fields']);
    if (!empty($temp[0]['name']) && $temp[0]['active']==1) { // when items found
      switch ($_SESSION['lang']) {
        default:
        case $_SESSION['languages'][1]:  $message = $this->cleanEscaped($temp[0]['text1']); break;
        case $_SESSION['languages'][2]:  $message = $this->cleanEscaped($temp[0]['text2']); break;
        case $_SESSION['languages'][3]:  $message = $this->cleanEscaped($temp[0]['text3']); break;
      }
      $message = str_replace("##SERVERADDR##",$_SERVER['REMOTE_ADDR'],$message);
      $message = str_replace("##DATE##",$date,$message);
      $message = str_replace("##TOURNAMENTS##",$Tournament,$message);
      $message = str_replace("##FORNAME##",$FirstName,$message);
      $message = str_replace("##NAME##",$LastName,$message);
      $message = str_replace("##EMAIL##",$Email,$message);
      $message = str_replace("##ADDRESS##",$Address,$message);
      $message = str_replace("##ZIPCITY##",$City,$message);
      $message = str_replace("##PHONE##",$Phone,$message);
      $message = str_replace("##HOMECLUB##",$HomeClub,$message);
      $message = str_replace("##HANDICAP##",$Handicap,$message);
      $message = str_replace("##FORNAME2##",$PartnerFirstName,$message);
      $message = str_replace("##NAME2##",$PartnerLastName,$message);
      $message = str_replace("##HOMECLUB2##",$PartnerHomeClub,$message);
      $message = str_replace("##HANDICAP2##",$PartnerHandicap,$message);
      $message = str_replace("##COMMENT##",nl2br($Comment),$message);
    }
    else { // no specific page found, use default in english
      $message = "<table><tr><td class='text' colspan='2'>Mail sent from: ".$_SERVER['REMOTE_ADDR']." - on: ".$date."\r\n</td></tr>"
                ."<tr><td class='text' colspan='2'>--------------------------------------------------\r\n</td></tr>"
                ."<tr><td class='text' valign='top'>Tournament:</td><td class='text'>        $Tournament</td></tr>"
                ."<tr><td class='text' colspan='2'>--------------------------------------------------\r\n</td></tr>"    
                ."<tr><td class='text'>FirstName: </td><td class='text'>        $FirstName\r\n</td></tr>"
                ."<tr><td class='text'>LastName: </td><td class='text'>         $LastName\r\n</td></tr>"
                ."<tr><td class='text'>Email:   </td><td class='text'>          $Email\r\n</td></tr>"
                ."<tr><td class='text'>Address: </td><td class='text'>          $Address\r\n</td></tr>"
                ."<tr><td class='text'>City:  </td><td class='text'>            $City\r\n</td></tr>"
                ."<tr><td class='text'>Phone: </td><td class='text'>            $Phone\r\n</td></tr>"
                ."<tr><td class='text'>HomeClub: </td><td class='text'>         $HomeClub\r\n</td></tr>"
                ."<tr><td class='text'>Handicap: </td><td class='text'>         $Handicap\r\n</td></tr>"
                ."<tr><td class='text' colspan='2'>\r\n</td></tr>"
                ."<tr><td class='text' colspan='2'>My partner in this tournament :\r\n</td></tr>"
                ."<tr><td class='text'>FirstName:  </td><td class='text'>       $PartnerFirstName\r\n</td></tr>"
                ."<tr><td class='text'>LastName: </td><td class='text'>         $PartnerLastName\r\n</td></tr>"
                ."<tr><td class='text'>HomeClub: </td><td class='text'>         $PartnerHomeClub\r\n</td></tr>"
                ."<tr><td class='text'>Handicap: </td><td class='text'>         $PartnerHandicap\r\n</td></tr>"
                ."<tr><td class='text' colspan='2'>\r\n</td></tr>"
                ."<tr><td class='text' valign='top'>Comment: </td><td class='text'>".nl2br($Comment)."\r\n</td></tr></table>";
    }
    
    
    if ($message != strip_tags($message)) { // When HTML, place HTML Tag
      $message = "<HTML><BODY>".$message."</BODY></HTML>";
    }
    $done = $this->smtpmailer($to,$from,$fromtext,$subject,$message);
    if ($done===true)
      return $message;  //return the table when ok
    else
      return $done; // return false
    
  }
  
  /*
  This function is using the PHPmail class to send an e-mail 
  */
  function sendMailMsg($mailPage, $recipient = NULL, $fromArray = NULL) {
    // use the text form es_mail to 
    $temp = $this->db_query("SELECT * from texts where name LIKE '$mailPage'", $this->table_struct['texts']['fields']);
    if (!empty($temp[0]['name'])) { // when items found
      switch ($_SESSION['lang']) {
        default:
        case $_SESSION['languages'][1]:  $message = $this->cleanEscaped($temp[0]['text1']); break;
        case $_SESSION['languages'][2]:  $message = $this->cleanEscaped($temp[0]['text2']); break;
        case $_SESSION['languages'][3]:  $message = $this->cleanEscaped($temp[0]['text3']); break;
      }
      // replace all named fields with appropiate POS value
      $message = str_replace("##SERVERADDR##",$_SERVER['REMOTE_ADDR'],$message);
      $Email = "";
      if (!isset($fromArray)) {
        // get from post:
        foreach($_POST as $field => $value) {
          $message = str_ireplace("##$field##",$value,$message);
          if (stripos($field,"mail")!==false) $Email = $value;  // use the last field containing a "mail" in the label
        }
      } else {
        // get from passed array:
        foreach($fromArray as $field => $value) {
          $message = str_ireplace("##$field##",$value,$message);
          if (stripos($field,"mail")!==false) $Email = $value;
        }
      }
      if (empty($Email)) { // when still empty, use the currently logged in user's mail
        $Email =  $this->getEmail();
        if (empty($Email)) $Email = $_SESSION['E-MAIL3'];       
      }
      switch ($_SESSION['lang']) {
        default:
        case $_SESSION['languages'][1]:  $title = $this->cleanEscaped($temp[0]['title1']); break;
        case $_SESSION['languages'][2]:  $title = $this->cleanEscaped($temp[0]['title2']); break;
        case $_SESSION['languages'][3]:  $title = $this->cleanEscaped($temp[0]['title3']); break;
      }
      
      $date    = date("d.m.Y");
      $to      =  $this->getEmail();
      if (empty($to)) { // when still empty, use the currently logged in user's mail
        $to = $_SESSION['login_user']['email'];
        if (empty($to)) $to = $_SESSION['login_user']['email2']; //still empty? uses email2
        if (empty($to)) $to = $_SESSION['E-MAIL3']; //THE last chance !!!
      }      //if recipient
      if($recipient !== NULL)
        $to = $recipient;
        
      $subject = $title;
      $from    = $Email;
      $fromtext= $Email;
      $replyto = "$Email";
      $callid = $this->last_ins_call;
      if (!empty($fromArray)) {
        $callid = $fromArray['callid'];
      }
      $cmd="id=$callid&status=";
      $status = $this->db_queryAll($this->table_sql['status'][0]." ORDER BY ".$this->table_sql['status'][2]);
      if (stripos($message,"##status2ack##")!==false) {
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, mhash(MHASH_MD5,"tel24_".$_SERVER['SERVER_NAME']), $cmd."2", MCRYPT_MODE_ECB);
        $status_txt = $status[1]['status2'];
        $link = $_SESSION['stdWWW']."/apis/chg_status.php?arg=".urlencode($crypttext);
        $message = str_ireplace("##status2ack##",$link,$message); 
        $message = str_ireplace("##status2txt##",$status_txt,$message); 
      }
      if (stripos($message,"##status3ack##")!==false) {
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256,  mhash(MHASH_MD5,"tel24_".$_SERVER['SERVER_NAME']), $cmd."3", MCRYPT_MODE_ECB); 
        $status_txt = $status[2]['status2'];
        $link = $_SESSION['stdWWW']."/apis/chg_status.php?arg=".urlencode($crypttext);
        $message = str_ireplace("##status3ack##",$link,$message); 
        $message = str_ireplace("##status3txt##",$status_txt,$message); 
      }
      if ($message != strip_tags($message)) { // When HTML, place HTML Tag
        $message = "<HTML><BODY>".$message."</BODY></HTML>";
      }
      $done = $this->smtpmailer($to,$from,$fromtext,$subject,$message);
      if ($done===true) {
        if (isset($callid)) {
          // increment mail counter
          $_SESSION['callid'] = $callid;
          $upd = $this->dbquery("UPDATE calls SET email_cnt=email_cnt+1 WHERE id=$callid;");
          $addLog = ($_SESSION['extra_log']) ? " upd=$upd /"."UPDATE calls SET email_cnt=email_cnt+1 WHERE id=$callid;" : ""; 
          return $message.$addLog;  //return the table when ok
        }
        return $message; // return $message
      }
      else
        return $done; // return false
    }
    else
      return false;
  }
  
  function sendSMS($smsPage, $recipient, $fromArray = NULL) {
    if($_SESSION['has_smsAPI'] == "yes") {
      // use the text form es_mail to 
      $temp = $this->db_query("SELECT * from texts where name LIKE '$smsPage'", $this->table_struct['texts']['fields']);
      if (!empty($temp[0]['name'])) { // when items found
        switch ($_SESSION['lang']) {
          default:
          case $_SESSION['languages'][1]:  $message = $this->cleanEscaped($temp[0]['text1']); break;
          case $_SESSION['languages'][2]:  $message = $this->cleanEscaped($temp[0]['text2']); break;
          case $_SESSION['languages'][3]:  $message = $this->cleanEscaped($temp[0]['text3']); break;
        }
        // replace all named fields with appropiate POS value
        $message = str_replace("##SERVERADDR##",$_SERVER['REMOTE_ADDR'],$message);
        $Email = "";
        if (!isset($fromArray)) {
        // get from post:
        foreach($_POST as $field => $value) {
            if ($field=="timestamp") $value = substr($value,0,strlen($value)-3);
          $message = str_ireplace("##$field##",$value,$message);
            if (stripos($field,"mail")!==false) $Email = $value;
          }
        } else {
          // get from passed array:
          foreach($fromArray as $field => $value) {
            $message = str_ireplace("##$field##",$value,$message);
            if (stripos($field,"mail")!==false) $Email = $value;
          }
        }
        // clean recipient
        if(strpos($recipient, "+") !== false)
          $recipient = str_replace("+", "00", $recipient);
        $recipient = str_replace(" ", "", $recipient);
        // clean message: replace <p> with nothing and </p> with \r\n expect the last occurence
        $message = str_replace("<p>", "", $message);
        if (substr($message, strlen($message) - strlen("</p>")) == "</p>")
          $message = substr($message, 0, strlen($message) - strlen("</p>"));
        $message = str_replace("</p>", "\r\n", $message);
        $message = strip_tags($message);
        
        // restrict message size to 160 characters
        $message = substr($message, 0, $_SESSION['smsAPI_maxChar']);
        

        $s_postvars = $this->sms_gw_u_p."&sender=".urlencode($_SESSION['smsAPI_sender']);
        $s_postvars .= "&rcpt=".urlencode($recipient)."&msgbody=".urlencode($message);
        
        $h_status = inwHttpPOST('https://sms.inetworx.ch/smsapp/sendsms.php', $s_postvars, 'cleanteeth:axess2smsapp');
        if ($h_status) {
          // increment mail counter
          if (!isset($fromArray)) {
            $callid = $_SESSION['callid'];
          } else {
            $callid = $fromArray['callid'];
          }
          $upd = $this->dbquery("UPDATE calls SET sms_cnt=sms_cnt+1 WHERE id=$callid;");
          return $message."upd=$upd /"."UPDATE calls SET sms_cnt=sms_cnt+1 WHERE id=$callid;";  //return the table when ok
        }
        else
          return $h_status; // return false
      }
      else
        return false;
    } else
      return false;
  }
  
  /*
    This fuctions returns the HTML code to display the Worldmap flash unit
  */
  function getHTMLprojectWorldMap() {
    return "<div>
              <object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0' width='450' height='450'>
              <param name='movie' value='include/worldmap.swf'>
              <param name='quality' value='high'>
              <embed src='include/worldmap.swf' quality='high' pluginspage='http://www.macromedia.com/go/getflashplayer' type='application/x-shockwave-flash' width='450' height='450'></embed>
              </object>
            </div>";
  }
/*
  Returns a string containing HTML table list for sponsors of the given type ("Main sponsor", "sponsor" or "Co-sponsors) and SingleRow
*/  
  function getHTMLsponsorsPicts($sponsorType, $maxPerRow=1) {
    $myType = $sponsorType;
    if ($myType=="Main sponsor") $myType="2";
    else if ($myType=="sponsor") $myType="1";
    else if ($myType=="Co-sponsor") $myType="3";
    if ($myType=="2") $usedclass="class='mainsponsor";
    else if ($myType=="1" || $myType=="3") $usedclass="class='sponsor";
    else $usedclass="";
    $result = $this->db_query("SELECT * from sponsors where sponsor_type like '".$myType."' order by sorting,name", $this->table_struct['sponsors']['fields']);
    $list = "<table width='100%'>";
    for ($i=0;$i<sizeof($result);$i++) {
      $list .= "<tr>";
      $rest = sizeof($result) - $i;
      if ($rest>=$maxPerRow) {
        for ($cnt=0;$cnt<$maxPerRow;$cnt++)
          if ($i<sizeof($result)) {
            $list .= "<td ".$usedclass."'><a href='".$result[$i]['link']."'><img ".$usedclass."pict' src='".$this->table_defs['sponsors']['paths']['size2'].$result[$i]['pic_name']."'></a></td>";
            $i++;
          }
          $i--; // compensate the last increment because it will be done in loop again
      } else
      if ($rest==1) {
        $list .= "<td>&nbsp;</td><td ".$usedclass."'><a href='".$result[$i]['link']."'>
                  <img ".$usedclass."pict' src='".$this->table_defs['sponsors']['paths']['size2'].$result[$i]['pic_name']."'></a></td><td>&nbsp;</td>";
      } else {
        $list .= "<td ".$usedclass."'><a href='".$result[$i]['link']."'>
                  <img ".$usedclass."pict' src='".$this->table_defs['sponsors']['paths']['size2'].$result[$i]['pic_name']."'></a></td><td>&nbsp;</td>";
        $i++;
        $list .= "<td ".$usedclass."'><a href='".$result[$i]['link']."'>
                  <img ".$usedclass."pict' src='".$this->table_defs['sponsors']['paths']['size2'].$result[$i]['pic_name']."'></a></td>";
      }
      $list .= "</tr>";
    }
    $list .= "</table>";
    return $list;
  }
/*
  Returns a string containing HTML table for the given filter $filter applied on table filelinks
  Call example: this will list all datalinks entries for the current page
  getHTMLdataLink("name like ".$_SESSION['page'])
*/

  function getHTMLdataLink($filter) {
    $result = $this->db_query("SELECT distinct * from filelinks where $filter order by sorting", $this->table_struct['filelinks']['fields']);
    $list = "<table class='datalisttable'>";
    for ($i=0;$i<sizeof($result);$i++) {
      $usedclass = ($i%2) ? "class='listeven'" : "class='listodd'";
      $list .= "<tr ".$usedclass.">";
      $title = ""; $text = ""; $link = ""; $file = "";
      $title = $this->get_for_current_language($result[$i]['title1'],$result[$i]['title2'],$result[$i]['title3']);
      $text = $this->get_for_current_language($result[$i]['text1'],$result[$i]['text2'],$result[$i]['text3']);
      $link = $this->get_for_current_language($result[$i]['link1'],$result[$i]['link2'],$result[$i]['link3']);
      $file = $this->get_for_current_language($result[$i]['file1'],$result[$i]['file2'],$result[$i]['file3']);
      $ftype = strrchr ($file, ".");
      if ($ftype==".pdf") {
        $icon ="pdf.gif";
        $filesize = round(filesize($this->table_defs['filelinks']['paths']['original'].$file)/1024000, 2)." MB";
      } else if ($ftype==".html" || $ftype==".htm") {
        $icon ="html.png";
        $filesize = "";
      } else if ($ftype==".doc" || $ftype==".docx") {
        $icon ="word.png";
        $filesize = "";
      } else if ($ftype==".xls" || $ftype==".xlsx") {
        $icon ="excel.png";
        $filesize = "";
      } else if ($ftype==".ppt" || $ftype==".pptx") {
        $icon ="powerpoint.png";
        $filesize = "";
      } else if ($ftype==".zip" || $ftype==".arc") {
        $icon ="zip.png";
        $filesize = "";
      } else {
        $icon ="data.png";
        $filesize = "";
      }
      $list .= "<td><img class='datalisticon' src='".$this->iconsDir.$icon."'>&nbsp;</td>";
      $list .= "<td width='85%' class='filelinks'><a href='".$this->table_defs['filelinks']['paths']['original'].$file."' target='_blank' class='filelinks'>".$title."</a></td>
                <td class='filelinks' width='50'>".$filesize."</td></tr>";
      $list .= "<tr ".$usedclass."><td class='text' colspan='3'>".$text."</td></tr>";
    }
    $list .= "</table>";
    return $list;
  }

/*
  Returns a string containing HTML table with data from table FAQs
*/  
  function getHTMLfaqs() {
    $result = $this->db_query("SELECT distinct * from faqs order by position", $this->table_struct['faqs']['fields']);
    $list = "<table class='faqlisttable'>";
    for ($i=0;$i<sizeof($result);$i++) {
      $usedclass = ($i%2) ? "class='listeven'" : "class='listodd'";
      $question = ""; $answer = "";
      $question = $this->get_for_current_language($result[$i]['title1'],$result[$i]['title2'],$result[$i]['title3']);
      $answer = $this->get_for_current_language($result[$i]['text1'],$result[$i]['text2'],$result[$i]['text3']);
      $list .= "<tr $usedclass><td class='faq_question'><a href='index.php?".$_SERVER['QUERY_STRING']."&faq=faq[$i]' >".$question."</a></td></tr>";
      if($_GET['faq'] == "faq[$i]"){
        $list .= "<tr $usedclass><td class='text'>".$answer."</td></tr>";
      }
    }
    $list .= "</table>";
    return $list;
  }

  function getHTMLredirect($page,$delay = -1, $msg="") {
  //  if ($delay!=-1 && empty($msg)) return "";
    if (!empty($msg)) $delay = 3000;
    $tmp = "\r\n<script type='text/javascript'>\r\n";
    $tmp .= "\$(document).ready(function(){\r\n"; 
    $tmp .= "var url = 'index.php?hmenu=$page&Ypos=0';\r\n";
    if ($delay==-1)
      $tmp .= "\$(location).attr('href',url);\r\n";
    else
      $tmp .= "setTimeout(function(){window.location.href = ''+url+'';},$delay);\r\n";
    $tmp .= "})</script>\r\n";
    return $msg.$tmp;
  }

  function getHTMLredirectIdle($page,$delay = -1, $msg="") {
  //  if ($delay!=-1 && empty($msg)) return "";
    if (!empty($msg)) $delay = 3000;
    $tmp = "\r\n<script type='text/javascript'>\r\n";
    $tmp .= "\$(document).ready(function(){\r\n"; 
    $tmp .= "var url = 'index.php?hmenu=$page&Ypos=0';\r\n";
    if ($delay==-1)
      $tmp .= "\$(location).attr('href',url);\r\n";
    else
      $tmp .= "redirectIdle(url,$delay);\r\n";
    $tmp .= "})</script>\r\n";
    return $msg.$tmp;
  }

  function getHTMLuserList() {
    // Get user id from session
    $auth = $_SESSION['auth'];
    $id = $_SESSION["$auth"]["id"];
    // get company_id of user
    $sql = "SELECT company_id from clients where id=$id";
    $result = $this->db_query($sql, "company_id");
    $company_id = $result[0]['company_id'];
    $is_client = ( $_SESSION['login_user']['is_client']) ? true : false;
    if (!$is_client) $company_id = "%";
    
    echo $this->getHTMLtableWithLinksGC("clients", "", array('id', 'name', 'name_2', 'uid', 'email', 'phone'),
                "index.php?hmenu=edit_user", "company_id LIKE '$company_id'", "company_id,name,name_2 ASC", "sortable", $is_client);  
  }

  function getHTMLcallList() {
    // Get user id from session
    $auth = $_SESSION['auth'];
    $id = $_SESSION["$auth"]["id"];
    // get company_id of user
    $result = $this->db_query("SELECT company_id from clients where id=$id", "company_id");
    $company_id = $result[0]['company_id'];
    //get is_client
    $result = $this->db_query("SELECT is_client from companies where id=$company_id", "is_client");
    $is_client = $result[0]['is_client'];
    // archive all "erledigt" that are not from today
    $toArchive = $this->db_query("SELECT id FROM calls WHERE status=2 AND timestamp<'".date("Y-m-d")." 00:00:00' AND is_archived=0;", "id");
    if (!empty($toArchive)) {  // move them in archive
      for($i=0;$i<sizeof($toArchive);$i++) {
        $this->dbquery("UPDATE calls SET is_archived=1 WHERE id=".$toArchive[$i]['id']);
      }
    }
    // Check if has to resend mail or sms
    if (isset($_POST['callid']) && isset($_POST['resend_for'])) {
      $resendData = array();
      $thecall = $this->dbquery_firstrow("SELECT * FROM calls WHERE id=".$_POST['callid']);
      $resendData['subject'] = "Resend ".$_POST['resend_for'];
      $resendData['callid'] = $_POST['callid'];
      $resendData['caller_name'] = $thecall['caller_name'];
      $resendData['caller_phonenumber'] = $thecall['caller_phonenumber'];
      $resendData['message'] = $thecall['message'];
      $resendData['remark'] = $thecall['remark'];
      $resendData['status'] = $thecall['status'];
      $resendData['issued'] = substr($thecall['timestamp'],0,16);
      $resendData['editor'] = $this->dbquery_singleField("SELECT uid FROM clients WHERE id=".$thecall['userstamp'],"uid");
      $resendData['timestamp'] = date("Y-m-d H:i:s");
      $callerData = $this->dbquery_firstrow("SELECT phone,email FROM clients WHERE id=".$thecall['receiver_employee']." AND company_id=".$thecall['receiver_company']);
      if ($_POST['resend_for']=="mail") {
        $mailOK = $this->sendMailMsg("mail_resend", $callerData['email'], $resendData);
      }
      if ($_POST['resend_for']=="sms") {
        $smsOK = $this->sendSMS("sms_resend", $callerData['phone'], $resendData);
      }
    }
    
    $filter = "";
    if($is_client !== "0")
      $filter = "AND receiver_company=$company_id";
    
    global $listFilter;
    $listFilter = (isset($_GET['listFilter'])) ? $_GET['listFilter'] : "";
    if (empty($listFilter))
      $listFilter = (isset($_POST['listFilter'])) ? $_POST['listFilter'] : "";
    $listFilterLow = trim(strtolower($listFilter));
    // for the first phase, check the filter for date filter syntax (d.m.Y xxxx) xxx = other search string)
    // but date can be given as d. or .m (m is m, y or Y) or .y or .Y or d.m or m.y or m.Y or d.m.y or d.m.Y
    // to filter a date, a . is mandatory
    // rules are as follow:
    // if a space is found, listfilter is splitted by space(s) into $filterArray, else array $filterArray contains only one row.
    // The first filterArray row containing 1 or 2 . If more than 2, this row is not considerated as date filter.
    // All elements of a date-filterArray row splitted by . must contain digits. When not, this element will not be considerated as date filter  .
    $theFilter = array();
    if (!empty($listFilterLow)) {
      $filterExt = "";
      $theDateFilter = $this->getDateFilter($listFilterLow);
      $stringFilter = $theDateFilter[0];
      $dateFilter = $theDateFilter[1];
      $theTimeFilter = $this->getTimeFilter($stringFilter);
      $stringFilter = $theTimeFilter[0];
      $timeFilter = $theTimeFilter[1];
      if (!empty($dateFilter))
        $filterExt = " AND (DATE_FORMAT(timestamp,'%e.%c.%Y') LIKE '%$dateFilter%' OR DATE_FORMAT(timestamp,'%e.%c.%y') LIKE '%$dateFilter%' ";
      // check if must hang a check on time
      if (empty($timeFilter))
        $filterExt .= empty($filterExt) ? "" : ") "; // no, complete the ending ) (when filterExt not empty)
      else // has a time filter: must hangs after as AND
        $filterExt .= " AND (DATE_FORMAT(timestamp,'%k:%l') LIKE '%$timeFilter%')"; 
    } else {
      $stringFilter = "";
      $listFilter = "";
      $filterExt = "";
      $_SESSION['filter']="";
    }
    
    $in_days = $this->db_queryAll("SELECT DISTINCT DATE_FORMAT(timestamp,'%Y-%m-%d') as timestamp from calls WHERE is_archived=0 $filter $filterExt ORDER BY timestamp DESC;");
    // display the filterform
    echo $this->filterForm();
    // for the second phase, use calls fields for search
    if (!empty($listFilterLow)) {
      if (!empty($stringFilter)) {
        $beg = strpos($listFilterLow,":orders");
        if ($beg!==false) {
          // remove :orders from string filter...
          $strFiltBeg = substr($stringFilter,0,$beg);
          $strFiltEnd = (strlen($stringFilter)>strlen(":orders")) ? substr($stringFilter,$beg+strlen(":orders")-1,999) : "";
          $stringFilter = $strFiltBeg+$strFiltEnd;
          $filterExt .="AND extra=1 ";
          $listFilter = "";
        } 
        else
          $filterExt .= "AND ((lower(caller_name) LIKE '%$stringFilter%')
                         OR (lower(uid) LIKE '%$stringFilter%')  
                         OR ticket LIKE '%$stringFilter%'
                         OR caller_phonenumber LIKE '%$stringFilter%'
                         OR lower(convert(BINARY message using utf8)) LIKE '%$stringFilter%') ";
      }
    }
    for ($i=0;$i<sizeof($in_days);$i++) {
      $dayFilter = "AND DATE_FORMAT(timestamp,'%Y-%m-%d') LIKE '".$in_days[$i]['timestamp']."'";
      echo "<div class='day_title'>".date("l, d.m.Y", strtotime($in_days[$i]['timestamp']))."</div>";
      echo $this->getHTMLtableWithLinks("calls", 
                  array('timestamp#,Zeit,', 'userstamp', 'ticket', 'receiver_company:companies:id:name', 'receiver_employee#MA:clients:id:uid', 'caller_name',  
                        'caller_phonenumber', 'message', 'sms_cnt', 'email_cnt', 'status:status:id:status2'),
                  array('H:i', 'lookup_clients_uid:1', 'text:1', 'join:'.!$is_client, 'join:1', 'text:1', 
                        'text:1', 'text:1', 'resend:1', 'resend:1', 'join:1'),
                  "index.php?hmenu=edit_call", "is_archived=0 $dayFilter $filter $filterExt", "timestamp DESC", "sortable");
    }    
    $filter = "1";
    if($is_client !== "0")
      $filter = "receiver_company=$company_id";
    $lastID = $this->dbquery_singleField("SELECT max(id) maxid FROM `calls` WHERE $filter;","maxid");
    if(isset($_SESSION['lastID']) && $_SESSION['lastID']!=$lastID) { // play sound
      $uid = $_SESSION['login_user']['id'];
      $file = pathinfo($_SESSION['login_user']['partner_cond1'], PATHINFO_FILENAME);
      $lead = $uid."_";
      if (empty($file)) {
        $filename = $this->dbquery_singleField("SELECT * FROM clients WHERE id=$uid;","partner_cond1");
        $file = pathinfo($filename, PATHINFO_FILENAME);
        if (empty($file)) {// if still empty, take default
          $file="yabba";
          $lead="";
        }
      }
      if (isset($_GET['listFilter'])) {
        $_GET['listFilter']="";
        global $listFilter;
        $listFilter = "";  
      }  
      if (isset($_POST['listFilter'])) {
        $_POST['listFilter']="";
        global $listFilter;
        $listFilter = "";  
      }  
      echo "<script type='text/javascript'>playSound('".$this->table_defs["clients"]['paths']['original'].$lead.$file."');</script>";      
    }
    $_SESSION['lastID']=$lastID;
  }

  function getHTMLcallArchive($extra="") {
    // special extra handling:
    if (!empty($extra)) $extra = " AND $extra ";
    // Get user id from session
    $auth = $_SESSION['auth'];
    $id = $_SESSION["$auth"]["id"];
    // get company_id of user
    $result = $this->db_query("SELECT company_id from clients where id=$id", "company_id");
    $company_id = $result[0]['company_id'];
    //get is_client
    $result = $this->db_query("SELECT is_client from companies where id=$company_id", "is_client");
    $is_client = $result[0]['is_client'];
    
    $filter = "";
    if($is_client !== "0")
      $filter = "AND receiver_company=$company_id";
    
    if (isset($_SESSION['filter']) && isset($_POST['listFilter']) && empty($_POST['listFilter'])) $_SESSION['filter'] = "";
    global $listFilter;
    $listFilter = (isset($_GET['listFilter'])) ? $_GET['listFilter'] : "";
    if (empty($listFilter))
      $listFilter = (isset($_POST['listFilter'])) ? $_POST['listFilter'] : "";
    if (empty($_SESSION['filter']) && !empty($listFilter)) {
      $_SESSION['filter'] = $listFilter;
    }
    if (!empty($_SESSION['filter'])) {
      $listFilter = $_SESSION['filter'];
    }
    $listFilterLow = trim(strtolower($listFilter));
    // for the first phase, check the filter for date filter syntax (d.m.Y xxxx) xxx = other search string)
    // but date can be given as d. or .m (m is m, y or Y) or .y or .Y or d.m or m.y or m.Y or d.m.y or d.m.Y
    // to filter a date, a . is mandatory
    // rules are as follow:
    // if a space is found, listfilter is splitted by space(s) into $filterArray, else array $filterArray contains only one row.
    // The first filterArray row containing 1 or 2 . If more than 2, this row is not considerated as date filter.
    // All elements of a date-filterArray row splitted by . must contain digits. When not, this element will not be considerated as date filter  .
    $theFilter = array();
    if (!empty($listFilterLow)) {
      $theFilter = $this->getDateFilter($listFilterLow);
      $dateFilter = $theFilter[1];
      if (empty($dateFilter))
        $filterExt = "";
      else
        $filterExt = "AND (DATE_FORMAT(timestamp,'%e.%c.%Y') LIKE '%$dateFilter%' OR DATE_FORMAT(timestamp,'%e.%c.%y') LIKE '%$dateFilter%') ";
    } else {
      $filterExt = "";
    }
    $in_days = $this->db_queryAll("SELECT DISTINCT DATE_FORMAT(timestamp,'%Y-%m-%d') as timestamp from calls WHERE is_archived=1 $extra $filter $filterExt ORDER BY timestamp DESC;");
    // display the filterform
    echo $this->filterForm();
    // for the second phase, use calls fields for search
    if (!empty($listFilterLow)) {
      $strFilter = $this->getTimeFilter($theFilter[0]);
      $stringFilter = $strFilter[0];
      $timeFil = $strFilter[1];
      $timeFilter = empty($strFilter[1]) ? "" : " DATE_FORMAT(timestamp,'%k:%l') LIKE '%$timeFil%' ";
      if (!empty($stringFilter)) {
        $filterExt .= "AND (lower(caller_name) LIKE '%$stringFilter%' 
                       OR caller_phonenumber LIKE '%$stringFilter%'
                       OR lower(convert(BINARY message using utf8)) LIKE '%$stringFilter%' ";
        if (!empty($timeFilter))
          $filterExt .= "OR $timeFilter ";
        $filterExt .= ") ";
      } else {
        if (!empty($timeFilter))
          $filterExt .= "AND $timeFilter ";
      }
     } else
      $filterExt = "";
    for ($i=0;$i<sizeof($in_days);$i++) {
      $dayFilter = "AND DATE_FORMAT(timestamp,'%Y-%m-%d') LIKE '".$in_days[$i]['timestamp']."'";
      echo "<div class='day_title'>".date("l, d.m.Y", strtotime($in_days[$i]['timestamp']))."</div>";
      echo $this->getHTMLtableWithLinks("archive", 
                  array('timestamp#,Zeit,', 'userstamp', 'ticket', 'receiver_company:companies:id:name', 'receiver_employee#MA:clients:id:uid', 'caller_name',  
                        'caller_phonenumber', 'message', 'sms_cnt', 'email_cnt', 'status:status:id:status2'),
                  array('H:i', 'lookup_clients_uid:1', 'text:1', 'join:'.!$is_client, 'join:1', 'text:1', 
                        'text:1', 'text:1', 'resend:1', 'resend:1', 'join:1'),
                  "index.php?hmenu=edit_archive_call", "is_archived=1 $extra $dayFilter $filter $filterExt", "timestamp DESC", "sortable");
    }    
  }

// Fiter the passed lowcase string for a date filter. Returns an 2 rows array  row[0]: string filter, row[1]: date filter
// if a space is found, listfilter is splitted by space(s) into $filterArray, else array $filterArray contains only one row.
// The first filterArray row containing 1 or 2 . If more than 2, this row is not considerated as date filter.
// All elements of a date-filterArray row splitted by . must contain digits. When not, this element will not be considerated as date filter  .
  function getDateFilter($listFilterLow) {
    $ret = array("","");
    if (strpos($listFilterLow," ")!==false)
      $filterArray = explode(" ", $listFilterLow);
    else
      $filterArray[0] = $listFilterLow;
    $hasDateFilter = -1;
    for($i=0;$i<sizeof($filterArray);$i++) {  // scan through the filter elements
      $elems = explode(".",$filterArray[$i]);
      if(sizeof($elems)>1 && sizeof($elems)<4) { // maybe a date
        $allNums = true;
        for($k=0;$k<sizeof($elems);$k++)
          if(!empty($elems[$k]) && !is_numeric($elems[$k])) $allNums = false;
        if ($allNums) $hasDateFilter = $i; 
      } 
    }
    for($i=0;$i<sizeof($filterArray);$i++) {  // scan through the filter elements
      if ($hasDateFilter==$i)
        $ret[1] = $filterArray[$i];
      else
        $ret[0] .= " ".$filterArray[$i];
    }
    $ret[0] = trim($ret[0]);
    $ret[1] = trim($ret[1]);
    return $ret;
  }

// Fiter the passed lowcase string for a time filter. Returns a 2 rows array  row[0]: string filter, row[1]: time filter
// if a space is found, listfilter is splitted by space(s) into $filterArray, else array $filterArray contains only one row.
// The first filterArray row must contain only one : If not, this row is not considerated as time filter.
// All elements of a time-filterArray row splitted by . must contain digits. When not, this element will not be considerated as time filter  .
  function getTimeFilter($listFilterLow) {
    $ret = array("","");
    if (strpos($listFilterLow," ")!==false)
      $filterArray = explode(" ", $listFilterLow);
    else
      $filterArray[0] = $listFilterLow;
    $hasTimeFilter = -1;
    for($i=0;$i<sizeof($filterArray);$i++) {  // scan through the filter elements
      $elems = explode(":",$filterArray[$i]);
      if(sizeof($elems)==2) { // maybe a time
        $allNums = true;
        for($k=0;$k<sizeof($elems);$k++)
          if(!empty($elems[$k]) && !is_numeric($elems[$k])) $allNums = false;
        if ($allNums) $hasTimeFilter = $i; 
      } 
    }
    for($i=0;$i<sizeof($filterArray);$i++) {  // scan through the filter elements
      if ($hasTimeFilter==$i)
        $ret[1] = $filterArray[$i];
      else
        $ret[0] .= " ".$filterArray[$i];
    }
    $ret[0] = trim($ret[0]);
    $ret[1] = trim($ret[1]);
    return $ret;
  }

  function getHTMLcallList_GC() {
    // Get user id from session
    $auth = $_SESSION['auth'];
    $id = $_SESSION["$auth"]["id"];
    // get company_id of user
    $result = $this->db_query("SELECT company_id from clients where id=$id", "company_id");
    $company_id = $result[0]['company_id'];
    //get is_client
    $result = $this->db_query("SELECT is_client from companies where id=$company_id", "is_client");
    $is_client = $result[0]['is_client'];
    // archive all "erledigt" that are not from today
    $toArchive = $this->db_query("SELECT id FROM calls WHERE status=2 AND timestamp<'".date("Y-m-d")." 00:00:00' AND is_archived=0;", "id");
    if (!empty($toArchive)) {  // move them in archive
      for($i=0;$i<sizeof($toArchive);$i++) {
        $this->dbquery("UPDATE calls SET is_archived=1 WHERE id=".$toArchive[$i]['id']);
      }
    }
    // Check if has to resend mail or sms
    if (isset($_POST['callid']) && isset($_POST['resend_for'])) {
      $resendData = array();
      $thecall = $this->dbquery_firstrow("SELECT * FROM calls WHERE id=".$_POST['callid']);
      $resendData['subject'] = "Resend ".$_POST['resend_for'];
      $resendData['callid'] = $_POST['callid'];
      $resendData['caller_name'] = $thecall['caller_name'];
      $resendData['caller_phonenumber'] = $thecall['caller_phonenumber'];
      $resendData['message'] = $thecall['message'];
      $resendData['remark'] = $thecall['remark'];
      $resendData['issued'] = substr($thecall['timestamp'],0,16);
      $resendData['editor'] = $this->dbquery_singleField("SELECT uid FROM clients WHERE id=".$thecall['userstamp'],"uid");
      $resendData['timestamp'] = date("Y-m-d H:i:s");
      $callerData = $this->dbquery_firstrow("SELECT phone,email FROM clients WHERE id=".$thecall['receiver_employee']." AND company_id=".$thecall['receiver_company']);
      if ($_POST['resend_for']=="mail") {
        $mailOK = $this->sendMailMsg("mail_resend", $callerData['email'], $resendData);
      }
      if ($_POST['resend_for']=="sms") {
        $smsOK = $this->sendSMS("sms_resend", $callerData['phone'], $resendData);
      }
    }
    
    $filter = "";
    if($is_client !== "0")
      $filter = "AND receiver_company=$company_id";
    
    $listFilter = (isset($_POST['listFilter'])) ? $_POST['listFilter'] : "";
    $listFilterLow = strtolower($listFilter);
    if (!empty($listFilterLow)) {
      $filterExt = "AND (DATE_FORMAT(timestamp,'%d.%m.%Y %k:%i') LIKE '%$listFilterLow%' OR lower(caller_name) LIKE '%$listFilterLow%' 
                                                         OR caller_phonenumber LIKE '%$listFilterLow%' 
                                                         OR lower(convert(BINARY message using utf8)) LIKE '%$listFilterLow%') ";
    } else
      $filterExt = "";
    echo $this->getHTMLtableWithLinksGC("calls", "JOIN clients ON clients.id=receiver_employee JOIN status ON calls.status=status.id", array('calls_id', 'timestamp', 'caller_name', 'caller_phonenumber', 'message', 'SMS', 'E-mail', 'uid', 'status2'),
                "index.php?hmenu=edit_call", "is_archived=0 $filter $filterExt", "timestamp DESC", "sortable","",$is_client);
  }

  function getHTMLdayInfo($viewOnly=false) {
    // Get user id from session
    $auth = $_SESSION['auth'];
    $id = $_SESSION["$auth"]["id"];
    // get company_id of user
    $result = $this->db_query("SELECT company_id from clients where id=$id", "company_id");
    $company_id = $result[0]['company_id'];
    $dayInfo = $this->dbquery_singleField("SELECT ".$this->getFieldName("text")." from dictums where name=$company_id", $this->getFieldName("text"));
    if (!$viewOnly) {
      echo "<script type='text/javascript'>";
      echo "var changedDayInfos;
            function clearchanges() {
              changedDayInfos=false;
            }
            function setchanges() {
              changedDayInfos=true;
              var t = setTimeout('savechanges()',8000);
            }
            function savechanges() {
              if (changedDayInfos)
                document.getElementById('dayInfoForm').submit();
              var t = setTimeout('savechanges()',8000);
            }
            ";
      echo "</script>";
    }
    $dow = $this->format_date(date("l"),"l");
    echo "<table id='tab_dayInfo'><tr><td id='hd_dayInfo'>$dow, ".date("d.m.Y H:i")." Tagesinfos:</td></tr>";
    if (!$viewOnly) {
      $javascript = "onfocus='clearchanges();' onchange='setchanges();' onblur='savechanges();'";
      $RO = "";
    } else {
      $javascript = "";
      $RO = "READONLY";
    }
    echo "<tr><td id='dayInfo'><form id='dayInfoForm' action='' method='post'><textarea $RO id='dayInfos' name='dayInfos' rows='5' $javascript>$dayInfo</textarea></form></td></tr><tr><td id='ft_dayInfo'></td></tr></table>";
  }

  function filterForm() {
    // Process filter form
    $listForm = "";
    if ($_GET['hmenu']=="calls" || $_GET['hmenu']=="calls_archive") {
      if (isset($_SESSION['filter']) && isset($_POST['listFilter']) && empty($_POST['listFilter'])) $_SESSION['filter'] = "";
      global $listFilter;
      $listFilter = (isset($_GET['listFilter'])) ? $_GET['listFilter'] : "";
      if (empty($listFilter))
        $listFilter = (isset($_POST['listFilter'])) ? $_POST['listFilter'] : "";
      if (empty($_SESSION['filter']) && !empty($listFilter)) {
        $_SESSION['filter'] = $listFilter;
      }
      if (!empty($_SESSION['filter'])) {
        $listFilter = $_SESSION['filter'];
      }
      $listForm = "<table id='filtertab'><tr><td id='hd_filter'>Filter:</td>";
      $listForm .= "<td><form id='filterform' action='' method='post'><input type='input' id='listFilter' name='listFilter' value='$listFilter'></td><td><button onclick='this.form.submit();'>Anwenden</button><button onclick='document.getElementById(\"listFilter\").value=\"\";this.form.submit();'>X</button></td></form>";
      $listForm .= "</tr></table>";
    }
    return $listForm;
  }

/*
  Returns a string containing HTML rows list for $table for the given filter $filter 
  We assume it will be inserted into a table with for the named $cols (array) columns
  call example:
  getHTMLtableList("tournaments",array("location","event_date","result"),"type like 1","event_date")
*/  
  function getHTMLtableWithLinks($table,$cols,$fmts,$base_link,$filter,$sorting,$css_table_class) {
    $is_archive = false;
    $rowLink = "submitViaCallsForm";
    
    if($table == "archive") {
      $is_archive = true;
      $rowLink = "submitViaCallsForm";
      $table = "calls";
    }
    // Build the join, based on missing cols in the base table
    $join = "";
    $baseTableCols = $this->table_struct[$table]['fields'];
    $selectCols = $table.".".$baseTableCols[0];  // for thge base table, allways col[0]
    foreach($cols as $col) {  // column may contain header (evtl 3 languages)
      $field = explode(":",$col);   // Now: $field[0] = base table fieldname, $field[1] = joined table,
                                    // Now: $field[0] = base table fieldname, $field[1] = joined table,
                                    //      $field[2]= joined col, $field[3]= joined field
      $theSub = explode("#",$field[0]); // Just in case, there is a header       
      if (in_array($theSub[0],$baseTableCols)) // when not already joined, add joined table
        $selectCols .= ",".$theSub[0];
      if (!empty($field[1]) && $field[1]!=$table && strpos($join,$field[1])===false) {   
        $join .= " join ".$field[1]." ON ".$field[1].".".$field[2]." = ".$theSub[0];
      }
    }
    // If no filter has been specified: select all elements.
    if(empty($filter)) 
      $filter = "1";
    $idx = "";
    $result = $this->db_queryAllQualified("SELECT * FROM $table $join WHERE $filter ORDER BY $sorting");  //only fieldnames
    $list = "<table class='$css_table_class'>";
    $list .= "<thead>";
    $myCols = explode(",",$selectCols);
    // remove index (normally never displayed)
    unset($myCols[0]);
    $myCols = array_values($myCols); // re-order
    for($i=0;$i<sizeof($myCols);$i++) { // Build the header, based on $cols and $fmts
      $fmt = explode(":",$fmts[$i]);
      $myCol = $myCols[$i];
      if (!empty($fmt[1])){ // only when must display
        if (strtolower($fmt[0])=="text") {
          $fldix = array_search($myCol,$this->table_struct[$table]['fields']);
          if($fldix !== false)
            $header_value =  $this->table_struct[$table]['labels'][$_SESSION['lang']][$fldix];
          else { // look in the looked up table
            $sub = explode(".",$myCol);
            // again in main table, but without tablename head
            $fldix = array_search($sub[1],$this->table_struct[$table]['fields']);
            if($fldix !== false)
              $header_value =  $this->table_struct[$table]['labels'][$_SESSION['lang']][$fldix];
            else { // to have the looked up table in $sub[1] 
              $fldix = array_search($sub[1],$this->table_struct[$sub[0]]['fields']);  
              if($fldix !== false)
                $header_value =  $this->table_struct[$sub[0]]['labels'][$_SESSION['lang']][$fldix];
              else 
                $header_value = $myCol;
            }
          }
        }
        else
        if (strpos(strtolower($fmt[0]),"join")!==false) {
          // use the text in the base table
          // look if normal column name is available
          $sub = explode(":",$cols[$i]);
          $theSub = explode("#",$sub[0]);
          $fldix = array_search($theSub[0],$this->table_struct[$table]['fields']);
          if($fldix !== false)
            $header_value =  $this->table_struct[$table]['labels'][$_SESSION['lang']][$fldix];
          else { // search in lookup table
            $fldix = array_search($jfld,$this->table_struct[$sub[1]]['fields']);
            if($fldix !== false)
              $header_value =  $this->table_struct[$sub[1]]['labels'][$_SESSION['lang']][$fldix];
            else 
              $header_value = $myCol;
          }
        }
        else
        if (strpos(strtolower($fmt[0]),"lookup")!==false) {
          $sub = explode("_",$fmt[0]);  // to have the looked up table in $sub[1] 
          $jfld = str_replace($sub[1].".","",$myCol);
          // look if normal column name is available
          $fldix = array_search($myCol,$this->table_struct[$table]['fields']);
          if($fldix !== false)
            $header_value =  $this->table_struct[$table]['labels'][$_SESSION['lang']][$fldix];
          else { // search in lookup table
            $fldix = array_search($jfld,$this->table_struct[$sub[1]]['fields']);
            if($fldix !== false)
              $header_value =  $this->table_struct[$sub[1]]['labels'][$_SESSION['lang']][$fldix];
            else 
              $header_value = $myCol;
          }
        }
        else { // $fmt contains format itself
          // special case for time only
          if (strtolower($myCol)=="timestamp" && strlen($fmts[$i]<=4) && empty($theCol[1])) {
            $header_value = $_SESSION['time'][$_SESSION['lang']];
          }
          else {
            $fldix = array_search($myCol,$this->table_struct[$table]['fields']);
            if ($fldix!==false)  
              $header_value =  $this->table_struct[$table]['labels'][$_SESSION['lang']][$fldix];
            else
              $header_value = $myCol;
          }  
        }
        $theCol = explode("#",$cols[$i]);
        if (!empty($theCol[1])) { // When Header is passed as parameter. it superseeds all other settings
          $header = explode(":",$theCol[1]);
          $hdForLng = explode(",",$header[0]);
          if (sizeof($hdForLng)>1) { // when multilanguage set the correct one
            $header_value = $hdForLng[$this->getLanguageIdx()-1];
          }
          else
            $header_value = $hdForLng[0]; 
        }
        $list .= "<td class='col$myCol'>$header_value</td>";
      }
    }
    $list .= "</thead>";
    for ($i=0;$i<sizeof($result);$i++) {
      $list1 = "";
      $list2 = "";
      $rowclass_status = "";
      $class_status = str_replace(" ","",$result[$i]['status.status2']);
      $usedclass = ($i%2) ? "listeven" : "listodd";
      $class_status .= "_".$usedclass;
      $id = $result[$i]["calls.id"];
      for ($j=0; $j<sizeof($myCols); $j++) {
        $fmt = explode(":",$fmts[$j]);
        $myCol = $myCols[$j];
        if (!empty($fmt[1])){ // only when must display
          if (strtolower($fmt[0])=="text") {
            $className = "col".$myCol;
            $colName = "calls.".$myCol;
            $result_text = $result[$i][$colName];
            $hasRemark = "";  
            if ($myCol=="message" && !empty($result[$i][$table.".remark"])) {
              $hasRemark = "<span style='color:red;font-weight:bold;'>!</span>";  
            }
            $list2 .= "<td class='$className $class_status'>$hasRemark$result_text</td>";
          }
          else
          if (strpos(strtolower($fmt[0]),"join")!==false) {
            $sub = explode(":",$cols[$j]);
            $theSub = explode("#",$sub[0]);
            $className = "col".$theSub[0];
            $colName = $sub[1].".".$sub[3]; 
            $result_text = $result[$i][$colName];
            $list2 .= "<td class='$className $class_status'>$result_text</td>";
          }
          else
          if (strpos(strtolower($fmt[0]),"lookup")!==false) {
            $sub = explode("_",$fmt[0]);  // to have the looked up table in $sub[1]
            $className = "col".$myCol;
            $colName = "calls.".$myCol; 
            $result_ref = $result[$i][$colName];
            $result_text = $this->dbquery_singleField("SELECT ".$sub[2]." FROM ".$sub[1]." WHERE id=$result_ref",$sub[2]);
            $list2 .= "<td class='$className $class_status'>$result_text</td>";
          }
          else
          if (strpos(strtolower($fmt[0]),"resend")!==false) {
            $result_text = $result[$i][$myCol];
            if ($myCol=="sms_cnt") {
              $sms_cnt = $this->dbquery_singleField("SELECT sms_cnt FROM calls WHERE id=$id","sms_cnt");
              $list2 .= "<td class='colSMS $class_status' ><a href='javascript:resend($id,\"sms\");'><span style='float:left;'>$sms_cnt<img style='float:right;width:15px;height:15px;padding-left:5px;padding-top:2px;' src='pictures/icons/resendColor.png'></span></a></td>";
            }
            if ($myCol=="email_cnt") {
              $email_cnt = $this->dbquery_singleField("SELECT email_cnt FROM calls WHERE id=$id","email_cnt");
              $list2 .= "<td class='colEmail $class_status'><a href='javascript:resend($id,\"mail\");'><span style='float:left;'>$email_cnt<img style='float:right;width:15px;height:15px;padding-left:5px;padding-top:2px;' src='pictures/icons/resend.png'></span></a></td>";
            }
          }
          else { // $fmt contains format itself
            $className = "col".$myCol;
            $colName = $table.".".$myCol;
            $result_text = $result[$i][$colName];
            switch($myCol) {
              case "timestamp":
                    $fmtString = date($fmts[$j],strtotime($result_text));
                    $list2 .= "<td class='$className $class_status'>$fmtString</td>";
                    break;
            }
          }
        }
      }
      // look if active for table clients
      $rowclass_status = $result[$i]['status.status2'];
      $rowclass_status .= "_".$usedclass;
      if ($result[$i]['clients.activ']=="0") $rowclass_status .= " inactiv_user";
      $list1 = "<tr class='$rowclass_status' onclick='$rowLink($id);'>";
      $list = $list.$list1.$list2;
      $list .= "</tr>";
    }
    $list .= "</tbody></table>";
  //NEW GC 3.1.16 : add a form to pass parameters via POST instead GET
    $list .= "<form id='calls' name='calls' action='$base_link' method='POST' enctype='multipart/form-data' autocomplete='off'>";
    $list .= "<input id='id' name='id' style='visibility:hidden;' type='text' value=''>";
    $list .= "</form>"; 
    $list .= "<form id='resend' name='resend' action='".$_SESSION['REQUEST_URI']."' method='POST' enctype='multipart/form-data' autocomplete='off'>";
    $list .= "<input id='callid' name='callid' style='visibility:hidden;' type='text' value=''>";
    $list .= "<input id='resend_for' name='resend_for' style='visibility:hidden;' type='text' value=''>";
    $list .= "</form>"; 
    $list .= "<div id='sound'></div>"; 
    $list .= "<script type='text/javascript'>"; 
    $list .= "function submitViaCallsForm(theID) {"; 
    $list .= "document.getElementById('id').value=theID;"; 
    $list .= "document.getElementById('calls').submit();"; 
    $list .= "}\r\n"; 
    $list .= "function resend(callID,resendType) {\r\n";
    $list .= "document.getElementById('callid').value=callID;"; 
    $list .= "document.getElementById('resend_for').value=resendType;"; 
    $list .= "document.getElementById('resend').submit();"; 
    $list .= "}\r\n"; 
    $list .= "</script>";
    
  //NEW jGC 18.1.16 : add a form to pass parameters via POST instead GET in order to archive a call
    $list .= "<form id='archive_call' name='archive_call' action='index.php?hmenu=archive_call' method='POST' enctype='multipart/form-data' autocomplete='off'>";
    $list .= "<input id='id' name='id' type='text' style='visibility:hidden;' value='' />";  //value has to be upadted by javascript
    
    $list .= "</form>"; 
    $list .= "<script type='text/javascript'>"; 
    $list .= "function archiveViaCallsForm(event, theID) {"; 
    $list .= "event.stopPropagation();"; 
    $list .= "\$('#archive_call #id').val(theID);";
    $list .= "document.getElementById('archive_call').submit();"; 
    $list .= "}"; 
    $list .= "</script>";
    
    return $list;
  }
/*
  Returns a string containing HTML rows list for $table for the given filter $filter 
  We assume it will be inserteb into a table with for the named $cols (array) columns
  call example:
  getHTMLtableList("tournaments",array("location","event_date","result"),"type like 1","event_date")
*/  
  function getHTMLtableWithLinksGC($table, $join, $cols, $base_link, $filter, $sorting, $css_table_class,$is_client) {
  /*  if ($table = "calls") {
      date_default_timezone_set('Europe/Zurich');
      $current_date = date('Y-m-d', time());
      if ($current_date->format('N') == 2)
        $firs_monday = date('Y-m-d', strtotime('last monday', time()));
    }
  */
    $is_archive = false;
    
    if($table == "archive") {
      $is_archive = true;
      $table = "calls";
    }
    // If a join is used, extract join table name
    if(!empty($join)){
      $joinTable = array();
      $tmp1 = explode("JOIN ", $join);
      for($i=0;$i<sizeof($tmp1);$i++) {
        if (!empty($tmp1[$i])) {
          $tmp2 = explode("ON ", $tmp1[$i]);
          if (!empty($tmp2[0]))
            array_push($joinTable,trim($tmp2[0]));
    }
      }
    }
    // Create a copy of cols, inserting company_id when is_client is false
    $myCols[0] = $cols[0];
    $ix = 1;
    if (!$is_client)
      $myCols[$ix++] = "company_id";
    for ($ii=1;$ii<sizeof($cols);$ii++)
      $myCols[$ix++] = $cols[$ii];
    // Extract first element of $cols (contains the currently used name for the id)
    $idName = $myCols[0];
    // Process filter form
    $listForm = "";
    if ($_GET['hmenu']=="calls" || $_GET['hmenu']=="calls_archive") {
      if (isset($_SESSION['filter']) && isset($_POST['listFilter']) && empty($_POST['listFilter'])) $_SESSION['filter'] = "";
      global $listFilter;
      $listFilter = (isset($_GET['listFilter'])) ? $_GET['listFilter'] : "";
      if (empty($listFilter))
        $listFilter = (isset($_POST['listFilter'])) ? $_POST['listFilter'] : "";
      if (empty($_SESSION['filter']) && !empty($listFilter)) {
        $_SESSION['filter'] = $listFilter;
      }
      if (!empty($_SESSION['filter'])) {
        $listFilter = $_SESSION['filter'];
      }
      $listForm = "<table id='filtertab'><tr><td id='hd_filter'>Filter:</td>";
      $listForm .= "<td><form id='filterform' action='' method='post'><input type='input' id='listFilter' name='listFilter' value='$listFilter'></td><td><button onclick='this.form.submit();'>Anwenden</button><button onclick='document.getElementById(\"listFilter\").value=\"\";this.form.submit();'>X</button></td></form>";
      $listForm .= "</tr></table>";
    }
    echo $listForm;
    // If no filter has been specified: select all elements.
    if(empty($filter)) 
      $filter = "1";
    $idx = "";
    $result = $this->db_query("SELECT *,$table.id as $idName FROM $table $join WHERE $filter ORDER BY $sorting", $myCols);  //$this->table_struct[$table]['fields']
    $list = "<table class='$css_table_class'>";
    $list .= "<thead>";
    foreach ($myCols as $myCol)
      if (($myCol != 'id' && strpos($myCol, "_id") === false) || $myCol=="company_id"){
        $field = array_search($myCol,$this->table_struct[$table]['fields']);
        if($field !== false)
          $header_value = $this->table_struct[$table]['labels'][$_SESSION['lang']][$field];
        else
          foreach($joinTable as $joinT) {
            $field = array_search($myCol,$this->table_struct[$joinT]['fields']);
            if($field !== false) {
              $header_value = $this->table_struct[$joinT]['labels'][$_SESSION['lang']][$field];
              $header_value = str_replace("(de)", "",$header_value);        
              break;
            }
          } 
        if ($field == false) {
          $header_value = $myCol;
        }
        $firmStyle = ($myCol=="company_id" && ($_SESSION['login_user']['is_client'])) ? "style=\"display:block;visibility:hidden;width:0px;\"" : "";
        $list .= "<td class='col$myCol' $firmStyle>$header_value</td>";
      }
    $list .= "</thead>";
    for ($i=0;$i<sizeof($result);$i++) {
      $list1 = "";
      $list2 = "";
      $rowclass_status = "";
      $class_status = "";
      $usedclass = ($i%2) ? "listeven" : "listodd";
      $id = $result[$i][$idName];
      for ($j=0; $j<sizeof($myCols); $j++) {
        unset($vartype);
        $field = array_search($myCols[$j],$this->table_struct[$table]['fields']);
        if($field !== false)
          $vartype = $this->table_struct[$table]['types'][$field];
        else
          foreach($joinTable as $joinT) {
            $field = array_search($myCols[$j],$this->table_struct[$joinT]['fields']);
            if($field !== false) {
              $vartype = $this->table_struct[$joinT]['types'][$field];
              if ($myCols[$j]=="status2") $class_status="status_";
              break;
            }
          }
        if(isset($vartype) && $vartype != 'id' && (strpos($myCols[$j], "_id") === false || $myCols[$j]=="company_id")) {
          if ($myCols[$j]=="status2" && !empty($class_status)) {
            $result_name = $result[$i][$myCols[$j]];
            $result_name = str_replace(" ","",$result_name);
            $rowclass_status = $result_name;
            $class_status .= "'$result_name'";
          }
          $firmStyle = ($myCols[$j]=="company_id" && ($_SESSION['login_user']['is_client'])) ? "style=\"display:block;visibility:hidden;width:0px;\"" : "";
          $colClass = "col".$myCols[$j];
          $list2 .= "<td $firmStyle class='$colClass $class_status'>";
          if ( $vartype=="text" || $vartype=="textarea" || $vartype=="unique")
            $list2 .= $result[$i][$myCols[$j]];
          else if ( $vartype=="date" || $vartype=="datestamp" ) {
            $datefmt = ($vartype=="date") ? "D, d.m.Y" : "D, d.m.Y H:i";
            $list2 .= $this->format_date($result[$i][$myCols[$j]],$datefmt);
          } else if ( $vartype=="file" )
            $list2 .= "<a href='".$result[$i][$myCols[$j]]."'>".$result[$i][$myCols[$j]]."</a>";
          else if ( strtolower(substr($vartype,0,6))=="select" ) {
            $sql = $vartype;
            $inhalt = $this->db_queryAll($sql);
            if(!empty($inhalt)){
              $list2 .= "<select name='".$myCols[$j]."' DISABLED>";
              for($ii=0;$ii < sizeof($inhalt);$ii++){
                $list2 .= "<option value='".$inhalt[$ii][0]."' ".($inhalt[$ii][0] == $result[$i][$myCols[$j]]?"selected='selected'":"")." >".$inhalt[$ii][1]."</option>";
              }
              $list2 .= "</select>";
        }
      }
          $list2 .= "</td>";
        } else { // unknown var type
          if ($myCols[$j]=="SMS") {
            $sms_cnt = $this->dbquery_singleField("SELECT sms_cnt FROM calls WHERE id=$id","sms_cnt");
            $list2 .= "<td class='colSMS' ><a href='javascript:resend($id,\"sms\");'><span style='float:left;'>$sms_cnt<img style='float:right;width:15px;height:15px;padding-left:5px;padding-top:2px;' src='pictures/icons/resendColor.png'></span></a></td>";
    }
          if ($myCols[$j]=="E-mail") {
            $email_cnt = $this->dbquery_singleField("SELECT email_cnt FROM calls WHERE id=$id","email_cnt");
            $list2 .= "<td class='colEmail'><a href='javascript:resend($id,\"mail\");'><span style='float:left;'>$email_cnt<img style='float:right;width:15px;height:15px;padding-left:5px;padding-top:2px;' src='pictures/icons/resend.png'></span></a></td>";
          }
        }
      }
      // look if active for table clients
      $activ = "1";
      if ($table=="clients") {
        $activ = $this->dbquery_singleField("SELECT activ FROM clients WHERE id=".$result[$i][$idName].";","activ");
      } else if ($table=="calls") {
        $client_id = $this->dbquery_singleField("SELECT receiver_employee FROM calls WHERE id=".$result[$i][$idName].";","receiver_employee");
        $activ = $this->dbquery_singleField("SELECT activ FROM clients WHERE id=$client_id;","activ");
      }
      if ($activ=="0") $rowclass_status .= " inactiv_user";
      $list1 = "<tr class='$rowclass_status"."_"."$usedclass' onclick='submitViaCallsForm($id);'>";
      $list = $list.$list1.$list2;
      $list .= "</tr>";
    }
    $list .= "</tbody></table>";
  //NEW GC 3.1.16 : add a form to pass parameters via POST instead GET
    $list .= "<form id='calls' name='calls' action='$base_link' method='POST' enctype='multipart/form-data' autocomplete='off'>";
    $list .= "<input id='id' name='id' style='visibility:hidden;' type='text' value=''>";
    $list .= "</form>"; 
    $list .= "<form id='resend' name='resend' action='".$_SESSION['REQUEST_URI']."' method='POST' enctype='multipart/form-data' autocomplete='off'>";
    $list .= "<input id='callid' name='callid' style='visibility:hidden;' type='text' value=''>";
    $list .= "<input id='resend_for' name='resend_for' style='visibility:hidden;' type='text' value=''>";
    $list .= "</form>"; 
    $list .= "<script type='text/javascript'>"; 
    $list .= "function submitViaCallsForm(theID) {"; 
    $list .= "document.getElementById('id').value=theID;"; 
    $list .= "document.getElementById('calls').submit();"; 
    $list .= "}\r\n"; 
    $list .= "function resend(callID,resendType) {\r\n";
    $list .= "document.getElementById('callid').value=callID;"; 
    $list .= "document.getElementById('resend_for').value=resendType;"; 
    $list .= "document.getElementById('resend').submit();"; 
    $list .= "}\r\n"; 
    $list .= "</script>";
    
  //NEW jGC 18.1.16 : add a form to pass parameters via POST instead GET in order to archive a call
    $list .= "<form id='archive_call' name='archive_call' action='index.php?hmenu=archive_call' method='POST' enctype='multipart/form-data' autocomplete='off'>";
    $list .= "<input id='id' name='id' type='text' style='visibility:hidden;' value='' />";  //value has to be upadted by javascript
    
    $list .= "</form>"; 
    $list .= "<script type='text/javascript'>"; 
    $list .= "function archiveViaCallsForm(event, theID) {"; 
    $list .= "event.stopPropagation();"; 
    $list .= "\$('#archive_call #id').val(theID);";
    $list .= "document.getElementById('archive_call').submit();"; 
    $list .= "}"; 
    $list .= "</script>";
    
    return $list;
  }

/*
  Returns a string containing HTML table with data from table startlists
*/  
  function getHTMLstartlists() {
    $result = $this->db_query("SELECT distinct * from startlists order by name", $this->table_struct['startlists']['fields']);
    $list = "<table class='datalisttable'>";
    for ($i=0;$i<sizeof($result);$i++) {
      $usedclass = ($i%2) ? "class='listeven'" : "class='listodd'";
      $list .= "<tr ".$usedclass.">";
      $title = ""; $text = ""; $link = ""; $file = "";
      $title = $result[$i]['name'];
      $file = $result[$i]['res_file'];
      $ftype = strrchr ($file, ".");
      if ($ftype==".pdf") {
        $icon ="pdf.gif";
        $filesize = round(filesize($this->table_defs['startlists']['paths']['original'].$file)/1024000, 2)." MB";
      } else if ($ftype==".html" || $ftype==".htm") {
        $icon ="html.png";
        $filesize = "";
      } else if ($ftype==".doc" || $ftype==".docx") {
        $icon ="word.png";
        $filesize = "";
      } else if ($ftype==".xls" || $ftype==".xlsx") {
        $icon ="excel.png";
        $filesize = "";
      } else if ($ftype==".ppt" || $ftype==".pptx") {
        $icon ="powerpoint.png";
        $filesize = "";
      } else if ($ftype==".zip" || $ftype==".arc") {
        $icon ="zip.png";
        $filesize = "";
      } else {
        $icon ="data.png";
        $filesize = "";
      }
      $list .= "<td><img class='datalisticon' src='".$this->iconsDir.$icon."'>&nbsp;</td>";
      $list .= "<td width='85%' class='filelinks'><a href='".$this->table_defs['startlists']['paths']['original'].$file."' target='_blank' class='filelinks'>".$title."</a></td>
                <td class='filelinks' width='50'>".$filesize."</td></tr>";
    }
    $list .= "</table>";
    return $list;
  }
/*
  Returns a string containing HTML rows list for $table for the given filter $filter 
  We assume it will be inserteb into a table with for the named $cols (array) columns
  call example:
  getHTMLtableList("tournaments",array("location","event_date","result"),"type like 1","event_date")
*/  
  function getHTMLtableList($table,$cols, $filter, $sorting) {
    $result = $this->db_query("SELECT * from $table where $filter order by $sorting", $this->table_struct[$table]['fields']);
    $list = "";
    for ($i=0;$i<sizeof($result);$i++) {
      $usedclass = ($i%2) ? "class='listeven'" : "class='listodd'";
      $list .= "<tr ".$usedclass.">";
      foreach($cols as $mycol) {
        $vartype = $this->table_struct[$table]['types'][array_search($mycol,$this->table_struct[$table]['fields'])];
        if ( $vartype=="text" )
          $list .= "<td >".$result[$i][$mycol]."</td>";
        else if ( $vartype=="date" )
          $list .= "<td >".$this->format_date($result[$i][$mycol],"D, d.m.Y")."</td>";
        else if ( $vartype=="file" )
          $list .= "<td><a href='".$result[$i][$mycol]."'>".$result[$i][$mycol]."</td>";
      }
      $list .= "</tr>";
    }
    $list .= "</tbody></table>";
    return $list;
  }
/*
  Returns a string containing HTML rows list for tournaments of the given tug 
  We assume it will be inserteb into a table with 3 columns
*/  
  function getHTMLtournamentsList($tug) {
    $result = $this->db_query("SELECT * from tournaments where type like '".$tug."' order by event_date", $this->table_struct['tournaments']['fields']);
    $list = "";
    for ($i=0;$i<sizeof($result);$i++) {
      $usedclass = ($i%2) ? "class='listeven'" : "class='listodd'";
      $list .= "<tr ".$usedclass.">";
      $list .= "<td >".$result[$i]['location']."</td><td>".$this->format_date($result[$i]['event_date'],"D, d.m.Y")."</td><td><a href='".$result[$i]['result']."'>".$result[$i]['result']."</td>";
      $list .= "</tr>";
    }
    $list .= "</tbody></table>";
    return $list;
  }

/*
  Returns a string containing HTML rows list for local partners in project 
  
*/  
  function getHTMLlocalPartnersList() {
  //  $cols = $this->table_struct['local_partners']['fields'];
  //  array_push($cols,"projects.id");
  //  $result = $this->db_query("SELECT local_partners.*, projects.id from local_partners join projects on LN=local_partner_nr order by local_partners.name ASC", $cols);
    $result = $this->db_query("SELECT * from local_partners order by name", $this->table_struct['local_partners']['fields']);
    $list = "<table width='100%'><tbody>";
    for ($i=0;$i<sizeof($result);$i++) {
      $usedclass = ($i%2) ? "class='listeven'" : "class='listodd'";
      $list .= "<tr ".$usedclass.">";
      $project_id = $this->db_query("SELECT id from projects where local_partner_nr=".$result[$i]['LN'],"id");
      if (empty($result[$i]['link'])) // when no link
        $list .= "<td class='text'>".$result[$i]['name']."</td>"; // use name only
      else
        $list .= "<td><a href='".$result[$i]['link']."' target='_blank' class='filelinks'>".$result[$i]['name']." &nbsp;<img src='".$this->iconsDir."web.gif' border='0'></a></td>"; // use name only
      if (sizeof($project_id)>0) { // one or more projects exists: add projects link
        $list .= "<td width='132'><a href='index.php?hmenu=projekte&fieldsearch=search&field=local_partner_nr&field_value=".$result[$i]['LN']."' class='filelinks'>";
        $list .= $_SESSION['projects'][$_SESSION['lang']]."</a></td>";
      }
      else
        $list .= "<td width='132'>&nbsp;</td>";
      $list .= "</tr>";
    }
    $list .= "</tbody></table>";
    return $list;
  }

/*
  return the string obtained after replacement of the string range "from" "to" with "value" into the source string "source" 
*/    
  function range_replace($from, $to, $value, $source) {
    $str = $source;
    $endpos = strpos($str,$to)+strlen($to);
    $begpos = strripos(substr($str,0,strpos($str,$to)),$from);
    return substr($str,0,$begpos).$value.substr($str,$endpos);
  }

  function processForms($item,$text) {
    $buf = $text;
    $start = strpos($buf,"<form");    // begin of form in text
    $end = strpos($buf,"</form>");    // end of form in text
    while ($start!==false)  {  // do for all forms
      $tmp = substr($buf,$start,$end-$start);    // the currently content to analyse
      // before scanning the form, get name or id
      $tmpend = strpos($tmp,">");
      $tmphdr = substr($tmp,0,$tmpend+1);
      if (strpos($tmphdr,"id=")!==false) {  //Has an ID ?
        $ix = strpos($tmphdr,"id=")+4;
        $len = strpos($tmphdr,$tmphdr[$ix-1],$ix)-$ix;
        $form_id = substr($tmphdr,$ix,$len);
      }
      else
        $form_id =  "";
      if (strpos($tmphdr,"name=")!==false) {  //Has a name
        $ix = strpos($tmphdr,"name=")+6;
        $len = strpos($tmphdr,$tmphdr[$ix-1],$ix)-$ix;
        $form_name = substr($tmphdr,$ix,$len);
      }
      else
        $form_name =  "";
      if (!($form_name=="termin" || $form_id=="termin")) {  // for all forms, excepted termin
        $input_ix = strpos($tmp,"<input");      // search first input field
        if ($input_ix===false)  // when not found, look for select
          $input_ix = strpos($tmp,"<select");  
        if ($input_ix===false)  // when not found, look for textarea
          $input_ix = strpos($tmp,"<textarea");  
        while ($input_ix!==false) { // as long it has some input or text area, get these
          // extract input fields (also TEXTAREA)
          $fields = array();
          while ($input_ix!==false) {
            $fld_ix = strpos($tmp,"name=",$input_ix)+6;
            if (strpos($tmp,"name=",$input_ix)!==false&& ($fld_ix < strpos($tmp,">",$input_ix))) { // when name exists extract content
              $fld_len = strpos($tmp,$tmp[$fld_ix-1],$fld_ix)-$fld_ix;
              $fld_name = substr($tmp,$fld_ix,$fld_len);
            } else {  // check eventual id
              $fld_ix = strpos($tmp,"id=",$input_ix)+4;
              if (strpos($tmp,"id=",$input_ix)!==false&& ($fld_ix < strpos($tmp,">",$input_ix))) { // when id exists extract content
                $fld_len = strpos($tmp,$tmp[$fld_ix-1],$fld_ix)-$fld_ix;
                $fld_name = substr($tmp,$fld_ix,$fld_len);
              } else
                $fld_name = ""; // no name nor id
            }
            if (!empty($fld_name)) {
              $typ_ix = strpos($tmp,"type=",$input_ix)+6;
              if (strpos($tmp,"type=",$input_ix) !== false && ($typ_ix < strpos($tmp,">",$input_ix))) { // when type exists, extract content
                $typ_len = strpos($tmp,$tmp[$typ_ix-1],$typ_ix)-$typ_ix;
                $typ_name = substr($tmp,$typ_ix,$typ_len);
              } else
                $typ_name = "";
            }
            if (empty($typ_name)) { // when no input field found, look for textarea
              $fld_ix = strpos($tmp,"<textarea",$input_ix);
              if ($fld_ix !== false) { // when textarea exists, get name or id
                $fld_ix = strpos($tmp,"name=",$input_ix)+6;
                if (strpos($tmp,"name=",$input_ix)!==false) { // when name exists extract content
                  $fld_len = strpos($tmp,$tmp[$fld_ix-1],$fld_ix)-$fld_ix;
                  $fld_name = substr($tmp,$fld_ix,$fld_len);
                } else {  // check eventual id
                  $fld_ix = strpos($tmp,"id=",$input_ix)+4;
                  if (strpos($tmp,"id=",$input_ix)!==false) { // when id exists extract content
                    $fld_len = strpos($tmp,$tmp[$fld_ix-1],$fld_ix)-$fld_ix;
                    $fld_name = substr($tmp,$fld_ix,$fld_len);
                  } else
                    $fld_name = ""; // no name nor id
                }
                $typ_name = "textarea";
              } 
            }
            if (!in_array($fld_name.";".$typ_name,$fields)) // Push into list, when not already defined
              array_push($fields,$fld_name.";".$typ_name);
            // move to next input, select or textarea (the smalest one
            $input_ix1 = strpos($tmp,"<input",$fld_ix+$fld_len);
            $input_ix2 = strpos($tmp,"<select",$fld_ix+$fld_len);
            $input_ix3 = strpos($tmp,"<textarea",$fld_ix+$fld_len);
            if ($input_ix1!==false && $input_ix2!==false) { // the smalest when both exist
              if ($input_ix2<$input_ix1)
                $input_ix1 = $input_ix2;
            } else if ($input_ix2!==false) // means that ix1 not exists
                $input_ix1 = $input_ix2;
            // now compare ix1 with ix2
            if ($input_ix1!==false && $input_ix3!==false) { // the smallest when both exist
              if ($input_ix3<$input_ix1)
                $input_ix = $input_ix3;
              else
                $input_ix = $input_ix1;
            } else { //ix1 or ix2 or both ar undefined
              if ($input_ix3!==false)  // means that ix1 not exists
                $input_ix = $input_ix3;
              else
                $input_ix = $input_ix1;    // no more
            }
          }
          if (!empty($form_id))
            $_SESSION[$item."s"][$form_id] = $fields;
          else
          if (!empty($form_name))
            $_SESSION[$item."s"][$form_name] = $fields;
        }
      }
      $buf = substr_replace($buf,"",$start,$end-$start+7);  // remove processed form
      $start = strpos($buf,"<form");    // begin of form in text
      $end = strpos($buf,"</form>");    // end of form in text
    }
  }

/*
  Function to echo the local page passed item (for the current $_SESSION['page'] )
  Echo nothing when item unknown or empty when item not found
*/
  function getHTMLpageItem($item,$ix=0) {
    $temp = "";
    if ($item=='text' || $item=='login' || $item=='form') {
      if (!isset($ix))
        $i=0;
      else
        $i = $ix;
      $temp = $this->db_query("SELECT * from texts where name LIKE '".$_SESSION['page']."' order by sorting", $this->table_struct['texts']['fields']);
      if (!empty($temp[$i]['name']) && $temp[$i]['active']==1) { // when items found
        switch ($_SESSION['lang']) {
          default:
          case $_SESSION['languages'][1]:  $temp = $this->cleanEscaped($temp[$i]['text1']); break;
          case $_SESSION['languages'][2]:  $temp = $this->cleanEscaped($temp[$i]['text2']); break;
          case $_SESSION['languages'][3]:  $temp = $this->cleanEscaped($temp[$i]['text3']); break;
        }
        // Here, we can insert special hanling for a standard text
        //
        // We loops til no more ## found (all replacements done
        while (strpos($temp,"##")!==false) {
          $tmp1=$temp;
          $temp = $this->processEditorCmds($temp,false);
          if ($temp==$tmp1) { // No change, check for PageMenus items
            $temp = $this->getHTMLpagemenuItems($temp,"txt_menu");
            if ($temp==$tmp1) // No PageMenuItems
              break;
          }
        } // end of loop
        if ($item=="login" || $item=="form") { // special processing for login module: create global form(s) list with fields
          if (!isset($_SESSION[$item."s"])) $_SESSION[$item."s"] = array();
          $this->processForms($item,$temp);
        }
        // common processing: output $temp
        echo "<div class='item_".$item."'>".$temp."</div>";
      }
    }
    else if ($item=='title') {
      $temp = $this->db_query("SELECT * from texts where name LIKE '".$_SESSION['page']."'", $this->table_struct['texts']['fields']);
      if (!empty($temp[0]['name']) && $temp[0]['active']==1) { // when items found
         switch ($_SESSION['lang']) {
          default:
          case $_SESSION['languages'][1]:  $temp = $this->cleanEscaped($temp[0]['title1']); break;
          case $_SESSION['languages'][2]:  $temp = $this->cleanEscaped($temp[0]['title2']); break;
          case $_SESSION['languages'][3]:  $temp = $this->cleanEscaped($temp[0]['title3']); break;
         }
         // common processing: output $temp
         echo "<div class='item_".$item."'>".$temp."</div>";
        if ($_SESSION['line_in_title'] == "yes") {
          echo "</td><tr><td><div class='item2ln_".$item."'></div>";
        }
      }
    }
  }

/*
  Function to echo the page text content for the $_SESSION['res_page'] )
  Echo nothing when empty $_SESSION['res_page'] or not found
*/
  function getHTMLresultPage() {
    $temp = "";
    if (empty($_SESSION['res_page']))
      return $tmp;
      
    $temp = $this->db_queryAll("SELECT * from texts where name LIKE '".$_SESSION['res_page']."'");
    if (!empty($temp)) {
      switch ($_SESSION['lang']) {
        default:
        case $_SESSION['languages'][1]:  $temp = $this->cleanEscaped($temp[0]['text1']); break;
        case $_SESSION['languages'][2]:  $temp = $this->cleanEscaped($temp[0]['text2']); break;
        case $_SESSION['languages'][3]:  $temp = $this->cleanEscaped($temp[0]['text3']); break;
      }
      // common processing: output $temp
      echo "<div class='item_result'>".$temp."</div>";
    }
  }
  
  /* This fuction returns the table of wanted PDFs */
  function getHTMLlistOfPDFs($path,$level2=false) {
    global $client_type;
    global $hmenu;
    $temp = "";
    $current_page = $hmenu;
    $uid_type = ($client_type=="mitglieder") ? 1 : 2; // default is mitglieder
	  if ($_SESSION['is_ZEEV']=="yes") {
      if ($level2) {
        switch ($uid_type) {
          case 1:
            $file_uid = $_SESSION['login_mitglied_ms']['uid'];
          break;
          case 2:
            $file_uid = $_SESSION['login_lieferant_ms']['uid'];
          break;
        }
      }
      else {
        switch ($uid_type) {
          case 1:
            $file_uid = $_SESSION['login_mitglied']['uid'];
          break;
          case 2:
            $file_uid = $_SESSION['login_lieferant']['uid'];
          break;
        }
      }
    } else {
      switch ($uid_type) {
        case 1:
          $file_uid = $_SESSION['login_mitglied_ms']['uid'];
        break;
        case 2:
          $file_uid = $_SESSION['login_lieferant_ms']['uid'];
        break;
      }
    }
// locally used function to format the date
    function form_date($datum, $format){
      $_datum = explode("-",$datum);
      $tag = $_datum[2];
      $monat = $_datum[1];
      $jahr = $_datum[0];
      if($format == "ja/nein"){
        if($datum == "0000-00-00"){
        return $_SESSION['no'][$_SESSION['lang']];
        }else{
        return $_SESSION['yes'][$_SESSION['lang']];
        }
      }else if($format == "m Y"){
        $months = explode(",", str_replace("'","",$_SESSION['cal_months'][$_SESSION['lang']]));
        return $months[(date("n",mktime(0,0,0,$monat,$tag,$jahr)))-1]." ".date("Y",mktime(0,0,0,$monat,$tag,$jahr));
      }else{
        return date($format,mktime(0,0,0,$monat,$tag,$jahr));
      }
    }
    
    if(!isset($_GET['ansicht'])){
      $_GET['ansicht'] = "M";
    }
    if(!isset($_GET['sortierung']) || $_GET['sortierung'] == ""){
      $_GET['sortierung'] = "ab";
    }
    $current_ansicht = $_GET['ansicht'];

    if(!isset($_GET['datum']) || $_GET['datum'] == "" || empty($_GET['datum']) ){
      switch ($uid_type) {
        case 1:
          if($current_ansicht == "Z"){
            $_GET['datum'] = date("Y-m-d", mktime(0,0,0,date("m")-12,1,date("Y")));
          }else if($current_ansicht == "S"){
            $_GET['datum'] = date("Y-m-d", mktime(0,0,0,date("m")-24,1,date("Y")));
          }else if($current_ansicht == "R"){
            $_GET['datum'] = date("Y-m-d", mktime(0,0,0,date("m")-12,1,date("Y")));
          }else{
            $_GET['datum'] = date("Y-m-d", mktime(0,0,0,date("m")-3,1,date("Y")));
          }
        break;
        case 2: 
          if($current_ansicht == "S"){
            $_GET['datum'] = date("Y-m-d", mktime(0,0,0,date("m")-12,1,date("Y")));
          }else if($current_ansicht == "F"){
            $_GET['datum'] = date("Y-m-d", mktime(0,0,0,date("m")-3,1,date("Y")));
          }else{
            $_GET['datum'] == "0000-00-00";
          }
        break;
      }
    }
    $current_date = $_GET['datum'];

    if(!isset($_GET['sort_spalte'])){
      $_GET['sort_spalte'] = 2;
    }

    $dok_arten = array("M" => $_SESSION['doc_type_M'][$_SESSION['lang']], "S" => $_SESSION['doc_type_S'][$_SESSION['lang']],
                       "Z" => $_SESSION['doc_type_Z'][$_SESSION['lang']], "R" => $_SESSION['doc_type_R'][$_SESSION['lang']],
                       "F" => $_SESSION['doc_type_F'][$_SESSION['lang']]);

    $i=0;

    $pfad= $_SESSION['std_document_root']."/".$path;
    $verz=opendir ($pfad);
    while ($file=readdir($verz))
    {
      if (filetype($pfad."/".$file)!="dir"){
        $files[$i] = $file;
        $i++;
      }
    }
    closedir($verz);

    $anz_files = count($files);

    $u=0;
    for($i=0;$i<$anz_files;$i++)
    {
      $dokumente = explode("_-_", $files[$i]);
      $dokumente[5] = str_replace(".pdf", "", $dokumente[5]);
      $dokumente[4] = str_replace(".pdf", "", $dokumente[4]);

      if($_GET['abgeschlossene'] == "ja"){
        if($dokumente[0] > 0 && $dokumente[0] == $file_uid && $dokumente[1] == $current_ansicht && (str_replace("-", "", $dokumente[2])-str_replace("-", "", $current_date)+1 ) > 0 && str_replace("-", "", $dokumente[4]) > 0 ){
          $dokumente_mitglied[$u] = $dokumente;
          $u++;
          $anz_doctitelvars = count($dokumente);
        }
      }else{
        if(!isset($_GET['datum'])){
          if($current_ansicht == "R"){
            $r_jahr = 1;
          }else{
            $r_jahr = 0;
          }
        
          if($dokumente[0] > 0 && $dokumente[0] == $file_uid && $dokumente[1] == $current_ansicht && (str_replace("-", "", $dokumente[2])-str_replace("-", "", $current_date)+1 ) > 0 && $dokumente[2] >= date("Y-m-d", mktime(0,0,0,01,01,date("Y")-$r_jahr))){
            $dokumente_mitglied[$u] = $dokumente;
            $u++;
            $anz_doctitelvars = count($dokumente);
          }  
        }else if($current_date > "0000-00-00"){
          if($dokumente[0] > 0 && $dokumente[0] == $file_uid && $dokumente[1] == $current_ansicht && (str_replace("-", "", $dokumente[2])-str_replace("-", "", $current_date)+1 ) > 0){
            $dokumente_mitglied[$u] = $dokumente;
            $u++;
            $anz_doctitelvars = count($dokumente);
          }  
        }
      }
    } //end of for loop

    //Sortierung des Arrays
    // Vergleichsfunktion (needed for sortierung)
    function vergleich($wert_a, $wert_b) 
    {
      // Sortierung nach dem zweiten Wert des Array (Index: 1)
      $a = $wert_a[$_GET['sort_spalte']];
      $b = $wert_b[$_GET['sort_spalte']];
      if ($a == $b) 
        return 0;
      if($_GET['sortierung'] == "auf"){
        return ($a < $b) ? -1 : +1;
      }else if($_GET['sortierung'] == "ab" || !isset($_GET['sortierung']) || $_GET['sortierung'] == "gleich"){
        return ($a > $b) ? -1 : +1;
      }
    }
    
    if($_GET['sortierung'] == "auf"){
      for($s=0;$s<6;$s++){
        $sortierung_wert[$s] = "gleich";
      }
      $sortierung_wert[$_GET['sort_spalte']] = "ab";
    }else if($_GET['sortierung'] == "ab" || !isset($_GET['sortierung']) || $_GET['sortierung'] == "gleich"){
      for($s=0;$s<6;$s++){
        $sortierung_wert[$s] = "gleich";
      }
      $sortierung_wert[$_GET['sort_spalte']] = "auf";
    }
    $current_sort = $_GET['sortierung'];
    $current_sort_col = $_GET['sort_spalte'];
    
    usort($dokumente_mitglied, 'vergleich');

    $anz_docs = count($dokumente_mitglied);
    if($current_ansicht=="M"){
      $bgcolor_m="#FFFFFF";
      $bgcolor_f="#DDEAF8";
      $bgcolor_s="#DDEAF8";
      $bgcolor_z="#DDEAF8";
      $bgcolor_r="#DDEAF8";
    }else if($current_ansicht=="F"){
      $bgcolor_m="#DDEAF8";
      $bgcolor_f="#FFFFFF";
      $bgcolor_s="#DDEAF8";
      $bgcolor_z="#DDEAF8";
      $bgcolor_r="#DDEAF8";
    }else if($current_ansicht=="S"){
      $bgcolor_m="#DDEAF8";
      $bgcolor_f="#DDEAF8";
      $bgcolor_s="#FFFFFF";
      $bgcolor_z="#DDEAF8";
      $bgcolor_r="#DDEAF8";
    }else if($current_ansicht=="Z"){
      $bgcolor_m="#DDEAF8";
      $bgcolor_f="#DDEAF8";
      $bgcolor_s="#DDEAF8";
      $bgcolor_z="#FFFFFF";
      $bgcolor_r="#DDEAF8";
    }else if($current_ansicht=="R"){
      $bgcolor_m="#DDEAF8";
      $bgcolor_f="#DDEAF8";
      $bgcolor_s="#DDEAF8";
      $bgcolor_z="#DDEAF8";
      $bgcolor_r="#FFFFFF";
    }
    
    $temp .= "   
<table width='100%' border='0' cellpadding='5' cellspacing='1' bgcolor='#E9E9E9'>
  <tr>
    <td>
      <table width='100%' border='0'>
        <tr>";
    switch ($uid_type) {
      case 1:  if (!$level2) {
    $temp .= "   
          <td class='standardtextueberschrift' bgcolor='$bgcolor_m' width='25%' align='left'>&nbsp;<a href='?hmenu=$current_page&ansicht=M' class='standardtext_link'>".$dok_arten['M']."</a></td>
          <td class='standardtextueberschrift' bgcolor='$bgcolor_s' width='25%' align='left'>&nbsp;<a href='?hmenu=$current_page&ansicht=S' class='standardtext_link'>".$dok_arten['S']."</a></td>";
      } else {
    $temp .= "   
          <td class='standardtextueberschrift' bgcolor='$bgcolor_z' width='25%' align='left'>&nbsp;<a href='?hmenu=$current_page&ansicht=Z' class='standardtext_link'>".$dok_arten['Z']."</a></td>
          <td class='standardtextueberschrift' bgcolor='$bgcolor_r' width='25%' align='left'>&nbsp;<a href='?hmenu=$current_page&ansicht=R' class='standardtext_link'>".$dok_arten['R']."</a></td>";
      }
      break;
      case 2:
    $temp .= "   
          <td class='standardtextueberschrift' bgcolor='$bgcolor_f' width='25%' align='left'>&nbsp;<a href='?hmenu=$current_page&ansicht=F' class='standardtext_link'>".$dok_arten['F']."</a></td>
          <td class='standardtextueberschrift' bgcolor='$bgcolor_s' width='25%' align='left'>&nbsp;<a href='?hmenu=$current_page&ansicht=S' class='standardtext_link'>".$dok_arten['S']."</a></td>";
      break;
    }
    $temp .= "   
        </tr>
      </table>
      <table width='100%' cellpadding='5' cellspacing='1' class='msf_table'>
        <tr align='left' bgcolor='#F9F9F9' class='standardtext'>
          <td height='20' colspan='6' class='standardtext'>
            <form name='termin' enctype='multipart/form-data' method='post' action='?hmenu=$current_page&ansicht=$current_ansicht&sortierung=$current_sort&sort_spalte=$current_sort_col' style='display:inline'>
              <input type='hidden'  id='datum' size='10' name='datum'  value='22.10.2005'><A HREF='#' NAME='datum_anchor' ID='datum_anchor' onClick=\"cal1x.select(document.termin.datum,'datum_anchor','yyyy-MM-dd','?hmenu=$current_page&ansicht=".$_GET['ansicht']."&sortierung=ab&abgeschlossene=".$_GET['abgeschlossene']."', 'calendardiv', ''); return false;\" class='standardtext_link'>
              ".$_SESSION['cal_date'][$_SESSION['lang']]."</A>
              <DIV ID='calendardiv' STYLE='position:absolute;visibility:hidden;'></DIV>
            </form>
          </td>
        </tr>
        <tr align='right' bgcolor='#F9F9F9' class='standardtext'>
          <td class='standardtext'>&nbsp;</td>
          <td class='standardtext'><a href='?hmenu=$current_page&ansicht=$current_ansicht&sortierung=".$sortierung_wert[2]."&sort_spalte=2&abgeschlossene=".$_GET['abgeschlossene']."&datum=$current_date'><img src='".$this->iconsDir."pfeil_".$sortierung_wert[2].".gif' border='0'></a>&nbsp;&nbsp;&nbsp;".$dok_arten[$current_ansicht]."</td>
          <td class='standardtext'><a href='?hmenu=$current_page&ansicht=$current_ansicht&sortierung=".$sortierung_wert[3]."&sort_spalte=3&abgeschlossene=".$_GET['abgeschlossene']."&datum=$current_date'><img src='".$this->iconsDir."pfeil_".$sortierung_wert[3].".gif' border='0'></a>&nbsp;&nbsp;&nbsp;".$_SESSION['updated_on'][$_SESSION['lang']]."</td>";
  if (($uid_type==1 && $current_ansicht == "S") || $current_ansicht == "Z" || $current_ansicht == "R")
    $temp .= "<td class='standardtext'>&nbsp;</td>";
  else
    $temp .= "   
          <td class='standardtext'><a href='?hmenu=$current_page&ansicht=$current_ansicht&sortierung=".$sortierung_wert[4]."&sort_spalte=4&abgeschlossene=".$_GET['abgeschlossene']."&datum=$current_date'><img src='".$this->iconsDir."pfeil_".$sortierung_wert[4].".gif' border='0'></a>&nbsp;&nbsp;&nbsp;".$_SESSION['verrechnet'][$_SESSION['lang']]."</td>";
    $temp .= "   
          <td class='standardtext'>&nbsp;</td>
        </tr>
        <tr align='left' valign='top' bgcolor='#FFFFFF' class='standardtext'>";
    for($i=0;$i<$anz_docs;$i++){
      $datei = "";
      for($d=0;$d<count($dokumente_mitglied[$i]);$d++){
        if( $d+1 == count($dokumente_mitglied[$i])){
          $datei =  $datei.$dokumente_mitglied[$i][$d].".pdf";
        }else{
          $datei =  $datei.$dokumente_mitglied[$i][$d]."_-_";
        }
      }
      $temp .="
            <td bgcolor='#FFFFFF' class='standardtext' align='right'><a href='".$_SESSION['stdWWW']."/".$path."/".$datei."' target='_blank' class='standardtext_link'><img src='".$this->iconsDir."pdf.gif' border='0'> ".$_SESSION['open'][$_SESSION['lang']]."</a></td>";
      for($g=2;$g<$anz_doctitelvars-1;$g++){
        $temp .="
          <td bgcolor='#FFFFFF' class='standardtext' align='right'>";
        if($g == 2){
          if($current_ansicht=="R"){
            $_datum = explode("-",$dokumente_mitglied[$i][$g]);
            $tag = $_datum[2];
            $monat = $_datum[1];
            $jahr = $_datum[0];
            $dokumente_mitglied[$i][$g] = date("Y",mktime(0,0,0,$monat,$tag,$jahr));
          }
          if($current_ansicht=="S" || $current_ansicht=="R"){
            $dat_darst = "Y";
          }else{        
            $dat_darst = "m Y";
          }
          $temp .= form_date($dokumente_mitglied[$i][$g], $dat_darst);
        }else if($g == 4){
          if( ($uid_type==1 && $current_ansicht == "S") || $current_ansicht == "Z" || $current_ansicht == "R" ){
          }else{
            $temp .= form_date($dokumente_mitglied[$i][$g], "ja/nein");
          }
        }else if($g == 3 || $g > 4){
          $temp .= form_date($dokumente_mitglied[$i][$g], "d.m.Y");
        }else{
          echo $dokumente_mitglied[$i][$g];
        }
        $temp .="
          </td>";
        if($g==4){ 
          $temp .="
          <td bgcolor='#FFFFFF' class='standardtext' align='right'>
          <a href=\"javascript:popup('mfs_korrektur_mail.php?betreff1=".$dokumente_mitglied[$i][0]."&betreff2=".$dokumente_mitglied[$i][1]."&betreff3=".$dokumente_mitglied[$i][2]."&betreff4=".$dokumente_mitglied[$i][3]."&betreff5=".$dokumente_mitglied[$i][4]."&betreff6=".$dokumente_mitglied[$i][5]."&betreff=Differenz- Meldung: ".$dokumente_mitglied[$i][5]."&datei=".$datei."','Meldung','toolbar=0,menubar=0,location=0,status=0,scrollbars=1,resizable=1,width=850,height=500')\" class='standardtext_link'>".$_SESSION['melden'][$_SESSION['lang']]."</a>
          </td>
        </tr>";
        }
      }
    }
    $temp .="
      </table>
    </td>
  </tr>
  <tr>
    <td class='standardtextklein' bgcolor='#ffffff' align='right'>".$_SESSION['acrobat_reader'][$_SESSION['lang']].": <a href='https://get.adobe.com/reader/' target='_blank'><img src='".$this->iconsDir."pdf_download.gif' width='100' height='15' longdesc='https://get.adobe.com/reader/' border='0'></a></td>
  </tr>
</table>";
    return $temp;
  }

  function getIDvalue($fldname,$buf) {
    $start = strpos($buf,'"',strpos($buf,"value=",strpos($buf,$fldname)))+1;
    $end = strpos($buf,'"',strpos($buf,'"',strpos($buf,"value=",strpos($buf,$fldname)))+1);
    if ($end>=$start)
      return substr($buf,$start,$end-$start);
    else
      return "";
  }

  function getNameValue($fldname,$buf) {
    $start1 = strpos(strtolower($buf),"name=\"".strtolower($fldname)."\"");     // theorical start
    if ($start1!==false) { // as a such field
    $start2 = strrpos($buf,'<',$start1-strlen($buf));   // real begin of tag 
    $end1 = strpos($buf,'>',$start1);                   // real end of tag
      if (strpos(substr(strtolower($buf),$start2,$end1-$start2),"value=")===false) {    // not in tag range: must be a TEXTAREA or a LABEL BUTTON
        $end1 = strpos(strtolower($buf),"</textarea",$start2);
        $end2 = strpos(strtolower($buf),"</label",$start2);
        if ($end1!==false && $end2!==false) // when both exists, take the smallest one
          $end3 = ($end1<$end2) ? $end1 : $end2;
        else
        if ($end1!==false)  // when only one existe, take this one
          $end3 = $end1;
        else
          $end3 = $end2;
        if (strpos(strtolower(substr($buf,$start2,$end3-$start2)),"##fieldvalue##")!==false) 
          return "##FIELDVALUE##";
        else
      return ""; 
    }
    else {
        $valbeg = strpos(substr(strtolower($buf),$start2,$end1-$start2),"value=")+7;
        $valend = strpos(substr($buf,$start2+$valbeg,$end1-$start2),"\"");
        $valstr = substr($buf,$start2+$valbeg,$valend);
        if (strtolower($valstr)=="##fieldvalue##" && strpos(substr(strtolower($buf),$start2,$end1-$start2),"checkbox")!==false) {
          $valstr="checkbox"; // signal checkbox
        }
        return $valstr;  
      }
    }
    $end = strpos($buf,'"',$start);
    if ($end>=$start)
      return substr($buf,$start,$end-$start);
    else
      return "";
  }

  function replaceNameValue($fldname="",$val=null,$buf="",$checked=false, $type="") {
    $start1 = strpos(strtolower($buf),"name=\"".strtolower($fldname)."\"");     // theorical start
    if ($start1===false) return $buf; // not found, return unchanged
    $start2 = strrpos($buf,'<',$start1-strlen($buf));   // real begin of tag 
    $end1 = strpos($buf,'>',$start1);                   // real end of tag
    if (strpos(substr(strtolower($buf),$start2,$end1-$start2),"value=")===false) {    // not in tag range: must be a TEXTAREA
      if (strpos($buf,"value=",$start2)===false && strpos(strtolower($buf),"</textarea",$start2)===false && strpos(strtolower($buf),"</label",$start2)===false) return $buf;
      if (strpos($buf,"##FIELDVALUE##",$start2)>strpos(strtolower($buf),"</textarea",$start2) && strpos($buf,"##FIELDVALUE##",$start2)>strpos(strtolower($buf),"</label",$start2)) return $buf;
      if (strpos($buf,"##FIELDVALUE##",$start2)!==false) {
        $start = strpos($buf,"##FIELDVALUE##",$start2);
        $tmp = $buf;
        if ($type=="file" && empty($val))
          $val = $_SESSION['selection'][$_SESSION['lang']];
        $tmp = substr_replace( $tmp,$val,$start,strlen("##FIELDVALUE##"));
        return $tmp;
      } 
      else
        return $buf; 
    }
    else {
       $start = strpos(strtolower($buf),'"',strpos($buf,"value=",$start2))+1;  
    }
    $end = strpos($buf,'"',$start);
    if ($end>=$start) {
      $tmp = $buf;
      if ($checked) {
        $sel = (empty($val)) ? "" : " checked";
        $tmp = substr_replace( $tmp,$sel,$start+15,0);
      }
      $tmp = substr_replace( $tmp,$val,$start,$end-$start);
      return $tmp;
    }
    else
      return $buf;
  }
/*
  A FORM used to update/insert values in a database table.
  The Form should contains a hidden Field named DBtable that contains the Database table to handle.
  Another field, DBrecid is used to select the appropriate record. When this field is empty, an INSERT is assumed.
  The field DBwhere is used as additional filter (if defined, must be like "AND xxx=zzz AND/OR iii>zzz etc.")
  All concerned fields should be named like those from table.
  If the current VAL is containing the Cmd ##FIELDVALUE##, it will be replaced with the real table value
*/
  function replaceFieldsValues($temp) {
    // get the db values
    $mytemp = $temp;
    $DBrecid = $this->getIDvalue("DBrecid", $mytemp);
    $recid = explode(",",$DBrecid);
    $auth = $this->getIDvalue("auth", $mytemp);
    if (!empty($_SESSION[$auth]) && !empty($recid[0])) {  // only for authorized pages and has id to select
      if (!empty($_POST[$recid[0]]) || !empty($_GET[$recid[0]])) {  // when has a select id for DB via POST or GET
        $sel_id = $_POST[$recid[0]];
        if (empty($sel_id))
          $sel_id = $_GET[$recid[0]];
      }
      else  // use $session value
      $sel_id = $_SESSION[$auth][$recid[0]];
      $table =  $this->getIDvalue("DBtable", $mytemp);
      $where = $recid[1]."=".$sel_id." ".$this->getIDvalue("DBwhere", $mytemp);
      $sql = "SELECT * from $table WHERE $where";
      $rec = $this->db_queryAll($sql);
      $this->fields = array();
      foreach($this->table_struct[$table]["fields"] as $fld) {
        $val = $this->getNameValue($fld, $mytemp);
        $keyIdx = array_keys($this->table_struct[$table][fields],$fld);
        $type = $this->table_struct[$table]["types"][$keyIdx[0]];
        if (empty($type))  $type = "other";
        if ($val=="##FIELDVALUE##") {
          $mytemp = $this->replaceNameValue($fld, $rec[0][$fld], $mytemp, false, $type);
          $type = "text";
        }
        else if ($val=="checkbox") {
          $mytemp = $this->replaceNameValue($fld, $rec[0][$fld], $mytemp, true, $type);  
          $type = "checkbox";
        }
        else if ($val=="HH:MM") {
          $mytemp = $this->replaceNameValue($fld, substr($rec[0][$fld],0,5), $mytemp, false, $type);  
          $type = "time";
        }
        $this->fields[$fld] = array($rec[0][$fld],$type);
      }
    }
    $mytemp = str_replace("##FIELDVALUE##","",$mytemp);
    return $mytemp;  
  }

/* This function returns the newest date of any files found in the passed dir
*/
  function getNewestFiledate($arg) {
    $path = $arg;
    $date = 0;
    if ($path[0]!="/") $path = "/".$path;
    $verz=opendir ($_SESSION['std_document_root'].$path);
    while ($file=readdir($verz))
    {
      if (filetype($file)!="dir"){
        if (filemtime($_SESSION['std_document_root'].$path."/".$file)>$date)
          $date = filemtime($_SESSION['std_document_root'].$path."/".$file);
      }
    }
    closedir($verz);
    if ($date>0)
      return date("d.m.Y",$date);
    else
      return "";
  }

/*
This function is processing special editor commands. It returns the processed string
*/
  function processEditorCmds($temp,$clear=true) {
    $mytemp = $temp;
    if (strpos($mytemp,"##EMPTYIDPSW##")!==false) { // when special handling of EMPTYIDPSW (replace with defined text in current language
      $mytemp = str_replace("&quot;##EMPTYIDPSW##&quot;","'".$_SESSION['empty_id_psw'][$_SESSION['lang']]."'",$mytemp);
    } else
    if (strpos($mytemp,"##PRJ_MAP##")!==false) { // when special handling of ES MAIL
      $mytemp = str_replace("##PRJ_MAP##",$this->getHTMLprojectWorldMap(),$mytemp);
    } else
    if (strpos($mytemp,"##UID##")!==false) { // when special handling of ES MAIL
      $mytemp = str_replace("##UID##",$_SESSION[$_SESSION['auth']]['uid'],$mytemp);
    } else
    if (strpos($mytemp,"##TOURNAMENTS1##")!==false) { // when special handling of TOURNAMENTS
      $mytemp = $this->range_replace("</tbody>","##TOURNAMENTS1##",$this->getHTMLtournamentsList("1"),$mytemp);
    } else
    if (strpos($mytemp,"##TOURNAMENTS2##")!==false) { // when special handling of TOURNAMENTS
      $mytemp = $this->range_replace("</tbody>","##TOURNAMENTS2##",$this->getHTMLtournamentsList("2"),$mytemp);
    } else
    if (strpos($mytemp,"##TOURNAMENTS3##")!==false) { // when special handling of TOURNAMENTS
      $mytemp = $this->range_replace("</tbody>","##TOURNAMENTS3##",$this->getHTMLtournamentsList("3"),$mytemp);
    } else
    if (strpos($mytemp,"##TABLELIST##")!==false) { // when special handling of TOURNAMENTS
      $mytemp = $this->range_replace("</tbody>","##TABLELIST##",$this->getHTMLtableList("tournaments",array("location","event_date","result"),"type like 1","event_date"),$mytemp);
    } else
  /*
  Call example: this will list all datalinks entries for the current page
  getHTMLdataLink("name like '".$_SESSION['page']."'")
  */        
    if (strpos($mytemp,"##DATALINKS_CP##")!==false) { // when special handling of DATALINKS LIST current page
      $mytemp = str_replace("##DATALINKS_CP##",$this->getHTMLdataLink("menu_ref like '".$_SESSION['pageID']."'"),$mytemp);
    } else
    if (strpos($mytemp,"##DATALINKS_#")!==false) { // when special handling of DATALINKS LIST for passed page
      $argbeg = strpos($mytemp,"##DATALINKS_#");
      $argbeg += strlen("##DATALINKS_#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"#",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $argID = menu_getPageID($arg,false);
      $mytemp = substr($mytemp,0,$argbeg).substr($mytemp,$argend+1);
      $mytemp = substr_replace($mytemp,$this->getHTMLdataLink("menu_ref like '".$argID."'"),strpos($mytemp,"##DATALINKS_#"),13);
    } else
    if (strpos($mytemp,"##DATALINKS##")!==false) { // when special handling of DATALINKS LIST
      $mytemp = str_replace("##DATALINKS##",$this->getHTMLdataLink("name like 'publikationen'"),$mytemp);
    } else
    if (strpos($mytemp,"##MAINSPONSORS##")!==false) { // when special handling of MAIN SPONSORS PICTURES LIST
      $mytemp = str_replace("##MAINSPONSORS##",$this->getHTMLsponsorsPicts("Main sponsor"),$mytemp);
    } else
    if (strpos($mytemp,"##SPONSORS##")!==false) { // when special handling of SPONSORS PICTURES LIST
      $mytemp = str_replace("##SPONSORS##",$this->getHTMLsponsorsPicts("sponsor",3),$mytemp);
    } else
    if (strpos($mytemp,"##COSPONSORS##")!==false) { // when special handling of  CO-SPONSORS PICTURES LIST
      $mytemp = str_replace("##COSPONSORS##",$this->getHTMLsponsorsPicts("Co-sponsor",3),$mytemp);
    } else
    if (strpos($mytemp,"##FAQS##")!==false) { // when special handling of FAQs LIST
      $mytemp = str_replace("##FAQS##",$this->getHTMLfaqs(),$mytemp);
    } else
    if (strpos($mytemp,"##ES_MAIL##")!==false) { // when special handling of ES MAIL
      if ( isset($_POST['DoIt'])) {// only when formular posted
        global $error;
        $mailOK = $this->sendMail();
        if ($mailOK===true)
          $mytemp = str_replace("##ES_MAIL##",$mailOK,$mytemp);
        else { // Cannot send Mail, send the conten of text page named "bad_es_mail"
          $mytemp = $this->db_query("SELECT * from texts where LOWER(name) LIKE 'bad_es_mail'", $this->table_struct['texts']['fields']);
          if (!empty($mytemp[0]['name']) && $mytemp[0]['active']==1) { // when items found
            switch ($_SESSION['lang']) {
              default:
              case $_SESSION['languages'][1]:  $mytemp = $this->cleanEscaped($mytemp[0]['text1']); break;
              case $_SESSION['languages'][2]:  $mytemp = $this->cleanEscaped($mytemp[0]['text2']); break;
              case $_SESSION['languages'][3]:  $mytemp = $this->cleanEscaped($mytemp[0]['text3']); break;
            }
          }
        }
      }
    } else
    //jGC: send email and SMS (SMS only if urgent) very similar to ##SEND_MAIL##
    if (strpos($mytemp, "##SEND_NOTIFICATION##")!==false){
      $delimeter = "##SEND_NOTIFICATION##";
      $delimeter_length = strlen($delimeter);
      if ( isset($_POST['DoIt'])) {// only when formular posted
        // extract name of mail: Syntax ##SEND_NOTIFICATION##formname;table(field)=post(field)##
        global $error;
        global $reload;
        $reload = -1;
        $startpos = strpos($mytemp, $delimeter);
        $endpos = strpos($mytemp, "##", $startpos+$delimeter_length);
        //jGC: added the recipient as additional and optional parameter; 
        $params = substr($mytemp, $startpos + $delimeter_length, $endpos - ($startpos+$delimeter_length));
        if (strpos($params, ";") !== false) {
          $paramsExploded = explode(";", $params);
          $mailPage = $paramsExploded[0];
          $recipientParam = $paramsExploded[1];
          $recipientParam = explode("=", $recipientParam);
          //
          $startpos = strpos($recipientParam[0], "(");
          $endpos = strpos($recipientParam[0], ")", $startpos + 1);
          $table1 = substr($recipientParam[0], 0, $startpos);
          $table1Col = substr($recipientParam[0], $startpos + 1, $endpos - ($startpos + 1));
          //
          $startpos = strpos($recipientParam[1], "(");
          $endpos = strpos($recipientParam[1], ")", $startpos + 1);
          $table2 = substr($recipientParam[1], 0, $startpos);
          $table2Col = substr($recipientParam[1], $startpos + 1, $endpos - ($startpos + 1));
          
          if ($table1 === "post")
            $val = $_POST[$table1Col];
          else {
            $table = $table1;
            $tableCol = $table1Col;          
          }
            
          if ($table2 === "post")
            $val = $_POST[$table2Col];
          else{
            $table = $table2;
            $tableCol = $table2Col;
          }
        
          $sql = "SELECT * FROM $table WHERE $tableCol=$val";
          $res = $this->db_query($sql, $this->table_struct[$table]['fields']);
          
          $mailOK = $this->sendMailMsg($mailPage, $res[0]['email']);
          if($_POST['is_urgent'] == "1") {
            $smsPage = str_replace("mail", "sms", $mailPage);
            $smsOK = $this->sendSMS($smsPage, $res[0]['phone']);
          }

        } else {
          $mailPage = $params;
          $mailOK = $this->sendMailMsg($mailPage);        
        }
        if (strpos($mytemp,"##HOME##",$endpos)!==false) { // when HOME handling of SEND_MAIL
          $startpos2 = strpos($mytemp,"##HOME##",$endpos)+8;
          $endpos2 = strpos($mytemp,"##",$startpos2);
          $reload = 0;
          if ($endpos2!==false) {  // when has timeout
            $reload = substr($mytemp,$startpos2,$endpos2-$startpos2);
            $endpos = $endpos2-$startpos2+2;
            $mytemp2 = substr($mytemp, 0, $startpos2).substr($mytemp, $startpos2+$endpos);
            $mytemp = $mytemp2;
            // remove timeout
          }
          // remove home
          $mytemp = str_replace("##HOME##","",$mytemp);
        }
        if ($mailOK===true) {   // mail ok  : give the content of text page named "mail_ok"
          $mailtemp = $this->db_query("SELECT * from texts where LOWER(name) LIKE 'mail_ok'", $this->table_struct['texts']['fields']);
          if (!empty($mailtemp[0]['name'])) { // when items found
            switch ($_SESSION['lang']) {
              default:
              case $_SESSION['languages'][1]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text1']); break;
              case $_SESSION['languages'][2]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text2']); break;
              case $_SESSION['languages'][3]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text3']); break;
            }
          }
          else
            $mailtemp = "";
          $mailtemp .= $mailOK; // Hangs the contactformular report
        }
        else { // Cannot send Mail, send the content of text page named "mail_failed"
          $mailtemp = $this->db_query("SELECT * from texts where LOWER(name) LIKE 'mail_failed'", $this->table_struct['texts']['fields']);
          if (!empty($mailtemp[0]['name'])) { // when items found
            switch ($_SESSION['lang']) {
              default:
              case $_SESSION['languages'][1]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text1']); break;
              case $_SESSION['languages'][2]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text2']); break;
              case $_SESSION['languages'][3]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text3']); break;
            }
          }
          else
            $mailtemp = "";
          $mailtemp = $error."<BR/>".$mailtemp;
        }
        // Replace ##SEND_MAIL##formname## whith the resulting message
        $mytemp = str_replace("$delimeter$params##",$mailtemp,$mytemp);
      }    
    } else
    if (strpos($mytemp,"##SEND_MAIL##")!==false) { // when special handling of SEND_MAIL
      $startpos = strpos($mytemp,"##SEND_MAIL##");
      $endpos = strpos($mytemp,"##",$startpos+13);
      $mailPage=substr($mytemp,$startpos+13,$endpos-($startpos+13));
      $_SESSION['send_mail_msg'] = ""; 
      if ( isset($_POST['OK']) || isset($_POST['DoIt'])) {// only when formular posted
        // extract name of mail: Syntax ##SEND_MAIL##formname##
        global $error;
        global $reload;
        $reload = -1;
        $mailOK = $this->sendMailMsg($mailPage);
        if ($mailOK!==false) {   // mail ok  : give the content of text page named "mail_ok" (when defined)
          $mailtemp = $this->db_queryAll("SELECT * from texts where LOWER(name) LIKE 'mail_ok'");
          if (!empty($mailtemp)) { // when items found
            switch ($_SESSION['lang']) {
              default:
              case $_SESSION['languages'][1]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text1']); break;
              case $_SESSION['languages'][2]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text2']); break;
              case $_SESSION['languages'][3]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text3']); break;
            }
          }
          else
            $mailtemp = "";
          if (strpos($mytemp,"##MSG")===false) {// when no ##MSG## comming below else keep message from mailOK
            if ($mailOK!==true) {  // when has HTML to insert
              if (strpos($mailtemp,"##MAILFORM##")!==false) // when can replace, replace
                $mailtemp = str_ireplace("##MAILFORM##",$mailOK,$mailtemp);
            }
          }
          if (strpos($mailtemp,"##")!==false) { // when some commands into,
            // get from post:
            foreach($_POST as $field => $value) {
              $mailtemp = str_ireplace("##$field##",$value,$mailtemp);
            }
          }
        }
        else // an error by sending mail
        { $mailtemp = $this->db_queryAll("SELECT * from texts where LOWER(name) LIKE 'mail_failed'");
          if (!empty($mailtemp)) { // when items found
            switch ($_SESSION['lang']) {
              default:
              case $_SESSION['languages'][1]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text1']); break;
              case $_SESSION['languages'][2]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text2']); break;
              case $_SESSION['languages'][3]:  $mailtemp = $this->cleanEscaped($mailtemp[0]['text3']); break;
            }
          }
          else
            $mailtemp = "";
          if (strpos($mailtemp,"##MAILERROR##")!==false) // when can replace, replace
            $mailtemp = str_ireplace("##MAILERROR##",$error,$mailtemp);
        }
        if (strpos($mytemp,"##HOME##",$endpos)!==false) { // when HOME handling of SEND_MAIL
          $startpos2 = strpos($mytemp,"##HOME##",$endpos)+8;
          $endpos2 = strpos($mytemp,"##",$startpos2);
          global $reload;
          global $pageReload;
          $reload = substr($mytemp,$startpos2,$endpos2-$startpos2);
          $mytemp2 = $mytemp;
          $mytemp = substr($mytemp2, 0, $startpos2);
          $mytemp = $mytemp.substr($mytemp2, $endpos2+2);
          $mytemp = str_replace("##HOME##","",$mytemp);
          $pageReload = "home";
        }
        // supress HTML and BODY (when some)
        $mailtemp = str_ireplace("<HTML>","",$mailtemp);
        $mailtemp = str_ireplace("</HTML>","",$mailtemp);
        $mailtemp = str_ireplace("<BODY>","",$mailtemp);
        $mailtemp = str_ireplace("</BODY>","",$mailtemp);
        
        if (strpos($mytemp,"##MSG")!=false) { // when has ##MSG## the status will be written there and nothing is returned
          $_SESSION['send_mail_msg'] = $mailtemp;
          $mailtemp = "";
        } 
        // Replace ##SEND_MAIL##formname## whith the resulting message
        $mytemp = str_replace("##SEND_MAIL##$mailPage##",$mailtemp,$mytemp);
      }
      else { // when nothing to send, replace cmd by ""
        $mytemp = str_replace("##SEND_MAIL##$mailPage##","",$mytemp);
      }
    } else
    if (strpos($mytemp,"##ES_CIRCUITS##")!==false) { // when special handling of ES CIRCUIT list with checkboxes
      $mytemp = str_replace("##ES_CIRCUITS##",$this->getHTMLes_circuit(),$mytemp);
    } else
    if (strpos($mytemp,"##ES_STARTLIST##")!==false) { // when special handling of ES STARTLIST list with checkboxes
      $mytemp = str_replace("##ES_STARTLIST##",$this->getHTMLstartlists(),$mytemp);
    } else
    if (strpos($mytemp,"##FIRM_NAME#")!==false) { // when special handling of ES CIRCUIT list with checkboxes
      $argbeg = strpos($mytemp,"##FIRM_NAME#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##FIRM_NAME#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"##",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $len = $argend-$argbeg1+2;
      $mytemp = substr_replace($mytemp,$_SESSION[$arg]['name'],strpos($mytemp,"##FIRM_NAME#"),$len);
    } else
    if (strpos($mytemp,"##UID_NR#")!==false) { // when special handling of ES CIRCUIT list with checkboxes
      $argbeg = strpos($mytemp,"##UID_NR#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##UID_NR#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"##",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $len = $argend-$argbeg1+2;
      $mytemp = substr_replace($mytemp,$_SESSION[$arg]['uid'],strpos($mytemp,"##UID_NR#"),$len);
    } else
    if (strpos($mytemp,"##LNG##")!==false) { // when special handling of ES CIRCUIT list with checkboxes
      $mytemp = str_replace("##LNG##",$_SESSION['lang'],$mytemp);
    } else
    if (strpos($mytemp,"##LOCALPARTNERSLIST##")!==false) { // when special handling of LOCALPARTNERSLIST list with checkboxes
      $mytemp = str_replace("##LOCALPARTNERSLIST##",$this->getHTMLlocalPartnersList(),$mytemp);
    } else
    if (strpos($mytemp,"##EVENTSLIST##")!==false) { // when special handling of EVENTSLIST list with checkboxes
      $mytemp = str_replace("##EVENTSLIST##",$this->getHTMLnews(true),$mytemp);
    } else
    if (strpos($mytemp,"##ESMERALDANEWS##")!==false) { // when special handling of ESMERALDANEWS list 
      $mytemp = str_replace("##ESMERALDANEWS##",$this->getHTMLesmeraldaNews(),$mytemp);
    } else
    if (strpos($mytemp,"##ESMERALDAREGISTER##")!==false) { // when special handling of ESMERALDAREGISTER list 
      $mytemp = str_replace("##ESMERALDAREGISTER##",$this->getHTMLesmeralda_register(),$mytemp);
    } else
    if (strpos($mytemp,"##ESMERALDASPONSORS##")!==false) { // when special handling of ESMERALDAREGISTER list
      $mytemp = str_replace("##ESMERALDASPONSORS##",$this->getHTMLesmeraldaSponsors(),$mytemp);
    } else
    if (strpos($mytemp,"##_FILES_#")!==false) { // when special handling of DATALINKS LIST for passed page
      $argbeg = strpos($mytemp,"##_FILES_#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##_FILES_#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"##",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $len = $argend-$argbeg1+2;
      $mytemp = substr_replace($mytemp,$this->getHTMLlistOfPDFs($arg,true),strpos($mytemp,"##_FILES_#"),$len);
    } else
    if (strpos($mytemp,"##_FILES1_#")!==false) { // when special handling of DATALINKS LIST for passed page
      $argbeg = strpos($mytemp,"##_FILES1_#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##_FILES1_#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"##",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $len = $argend-$argbeg1+2;
      $mytemp = substr_replace($mytemp,$this->getHTMLlistOfPDFs($arg,false),strpos($mytemp,"##_FILES1_#"),$len);
    } else
    if (strpos($mytemp,"##LASTUPDATE#")!==false) { // when special handling of DATALINKS LIST for passed page
      $argbeg = strpos($mytemp,"##LASTUPDATE#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##LASTUPDATE#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"##",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $len = $argend-$argbeg1+2;
      $mytemp = substr_replace($mytemp,$this->getNewestFiledate($arg),strpos($mytemp,"##LASTUPDATE#"),$len);
    } else
    if (strpos($mytemp,"##MSG##")!==false) { // when special handling of MESSAGE in text 
      $msg = $_GET['msg'];
      global $has_msg;
      $has_msg = $msg;
      if (!empty($_SESSION['send_mail_msg']))
        $msg .= " ".$_SESSION['send_mail_msg'];
      $_SESSION['send_mail_msg'] = "";  
      $mytemp = str_replace("##MSG##",$msg,$mytemp);
    } else
    if (strpos($mytemp,"##MSG_REDIR#")!==false) { // when special handling of MESSAGE in text 
      $msg = $_GET['msg'];
      global $has_msg;
      $has_msg = $msg;
      if (!empty($_SESSION['send_mail_msg']))
        $msg .= " ".$_SESSION['send_mail_msg'];
      $_SESSION['send_mail_msg'] = "";  
      $argbeg = strpos($mytemp,"##MSG_REDIR#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##MSG_REDIR#"); // position of 1st char of argument (page ref)
      $argend1 = strpos($mytemp,"#",$argbeg);
      $arg1 = substr($mytemp,$argbeg,$argend1-$argbeg); //extract argument1
      $argbeg2 = $argend1+1;
      $argend = strpos($mytemp,"##",$argbeg2);
      $arg2 = substr($mytemp,$argbeg2,$argend-$argbeg2); //extract argument
      $len = $argend-$argbeg+strlen("##MSG_REDIR#")+2;
      $mytemp = substr_replace($mytemp,$this->getHTMLredirect($arg1,$arg2,$msg),strpos($mytemp,"##MSG_REDIR#"),$len);
    } else
    if (strpos($mytemp,"##REDIRECT#")!==false) { // when there is a redirect (SYNTAX ##REDIRECT#page#delay##  page is hmenu name and delay in ms
      $argbeg = strpos($mytemp,"##REDIRECT#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##REDIRECT#"); // position of 1st char of argument (page ref)
      $argend1 = strpos($mytemp,"#",$argbeg);
      $arg1 = substr($mytemp,$argbeg,$argend1-$argbeg); //extract argument1
      $argbeg2 = $argend1+1;
      $argend = strpos($mytemp,"##",$argbeg2);
      $arg2 = substr($mytemp,$argbeg2,$argend-$argbeg2); //extract argument
      $len = $argend-$argbeg+strlen("##REDIRECT#")+2;
      $mytemp = substr_replace($mytemp,$this->getHTMLredirect($arg1,$arg2),strpos($mytemp,"##REDIRECT#"),$len);
    } else
    if (strpos($mytemp,"##REDIRECT_IDLE#")!==false) { // when there is a redirect (SYNTAX ##REDIRECT_IDLE#page#delay##  page is hmenu name and delay in ms
      $argbeg = strpos($mytemp,"##REDIRECT_IDLE#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##REDIRECT_IDLE#"); // position of 1st char of argument (page ref)
      $argend1 = strpos($mytemp,"#",$argbeg);
      $arg1 = substr($mytemp,$argbeg,$argend1-$argbeg); //extract argument1
      $argbeg2 = $argend1+1;
      $argend = strpos($mytemp,"##",$argbeg2);
      $arg2 = substr($mytemp,$argbeg2,$argend-$argbeg2); //extract argument
      $len = $argend-$argbeg+strlen("##REDIRECT_IDLE#")+2;
      $mytemp = substr_replace($mytemp,$this->getHTMLredirectIdle($arg1,$arg2),strpos($mytemp,"##REDIRECT_IDLE#"),$len);
    } else
    if (strpos($mytemp,"##PG_RELOAD#")!==false) { // when special handling of PG_RELOAD for passed page
      $argbeg = strpos($mytemp,"##PG_RELOAD#");
      $argbeg1 = $argbeg;
      $argbeg += strlen("##PG_RELOAD#"); // position of 1st char of argument (page ref)
      $argend = strpos($mytemp,"##",$argbeg);
      $arg = substr($mytemp,$argbeg,$argend-$argbeg); //extract argument
      $len = $argend-$argbeg1+2;
      global $reload;
      global $keepPosition;
      $keepPosition = true;
      $reload = $arg;
      $mytemp = substr_replace($mytemp,"",strpos($mytemp,"##PG_RELOAD#"),$len);
    } else
    if (strpos($mytemp,"##DROPDOWN#")!==false) { // when there is a dropdown
      $mytemp = $this->createDropDown($mytemp);
    } else
    if (strpos($mytemp,"##FIELDVALUE##")!==false) { // when special handling of Form with FIELDVALUES (ALL)
      $mytemp = $this->replaceFieldsValues($mytemp);
    } else
    if (strpos($mytemp,"##COMBO#")!==false) { // when special handling of COMBO in text (must be after FIELDVALUES because it can then use it
      $argbeg = strpos($mytemp,"##COMBO#");
      $arglen = strlen("##COMBO#");
      $argend = strpos($mytemp,"##",$argbeg+$arglen);
      $arg = substr($mytemp,$argbeg+$arglen,$argend-$argbeg-$arglen); //extract argument
      $combo = $this->getHTMLcombo($arg);
      $mytemp = substr_replace($mytemp,$combo,$argbeg,$argend-$argbeg+2);
    } else
    if (strpos($mytemp,"##LOOKUP#")!==false) { // when special handling of COMBO in text (must be after FIELDVALUES because it can then use it
      $argbeg = strpos($mytemp,"##LOOKUP#");
      $arglen = strlen("##LOOKUP#");
      $argend = strpos($mytemp,"##",$argbeg+$arglen);
      $arg = substr($mytemp,$argbeg+$arglen,$argend-$argbeg-$arglen); //extract argument
      $combo = $this->getHTMLlookup($arg);
      $mytemp = substr_replace($mytemp,$combo,$argbeg,$argend-$argbeg+2);
    } else
    if ($clear && strpos($mytemp,"##")!==false) // no cmd found: supress ## tag (only when clear flag set)
      $mytemp = str_replace("##", "", $mytemp);
    return $mytemp;
  }

  function getHTMLlookup($arg) {
    $return = "";
    $arg1 = str_replace("&#39;","'",$arg);
    $args = explode("#",$arg1);  /*  args[0] is lookup name
                                    args[1] is table
                                    args[2] is filter
                                    args[3] is invisible linked column index
                                    args[4] is optional HTML like READONLY 
                                    args[5] is optional class
                                */
    foreach($this->fields as $key=>$val) { 
      if ($key!="id" && strpos($args[2],$key)!==false) $args[2] = str_replace($key,$val[0],$args[2]);
    }
    // get the lookup
    $value = $this->dbquery_singleField("SELECT ".$args[3]." FROM ".$args[1]." WHERE ".$args[2].";",$args[3]);
    //replace $editor with the current one
    $editor = empty($value) ? $_SESSION['login_user']['uid'] : $value;
    $return = "<INPUT id='".$args[0]."' name='".$args[0]."' ".$args[4]." class='".$args[5]."' value='$editor'>";                                                            
    return $return;
  }

  function getHTMLcombo($arg) {
    $return = "";
    $arg1 = str_replace("&#39;","'",$arg);
    $args = explode("#",$arg1);  /*  args[0] is combo name
                                    args[1] is table
                                    args[2] is filter
                                    args[3] is invisible linked column index
                                    args[4] is optional sorting
                                    args[5] is optional class
                                    args[6] is optional tabindex
                                    args[7] is optional onFocusEvent
                                    args[8] is optional onChangeEvent
                                    args[9] is optional onBlurEvent
                                */
    // create the javascript arrays with unfiltred datas
    $return .= "\r\n<script type='text/javascript'>\r\n";
    if ($args[1]=="companies")
      $all = $this->db_queryAll("SELECT * FROM ".$args[1]." ".$args[4],false);
    else if ($args[1]=="clients") 
      $all = $this->db_queryAll("SELECT ".$args[3].",company_id FROM ".$args[1]." WHERE activ=1 ".$args[4].";",false);
    $nrRows = sizeof($all);
    $nrCols = sizeof($all[0])/2;
    $return .= "var ".$args[0]." = createArray($nrRows,$nrCols);\r\n";
    for($row=0;$row<sizeof($all);$row++) {
      foreach($all[$row] as $key=>$val) {
        if (is_numeric($key))
          $return .= "  ".$args[0]."[$row][$key] = '$val';\r\n";
      }
    }
    $return .= "</script>\r\n";

    $is_client = ( $_SESSION['login_user']['is_client']) ? "1" : "0";
    //replace cur_company with the current one, wildcard when not client
    $cur_comp = ($is_client=="1") ? $_SESSION['login_user']['company_id'] : "%";
    $args[2] = str_replace("cur_company",$cur_comp,$args[2]); // replace if is in filter
    //replace receiver_company with the current one
    $rec_comp = $this->fields['receiver_company'][0];
    if (empty($rec_comp)) $rec_comp = $_SESSION['login_user']['company_id'];
    //replace cur_employee with the current one, wildcard when not client
    $rec_empl = $this->fields['receiver_employee'][0];
    if (empty($rec_empl)) $rec_empl = $_SESSION['login_user']['id'];
    $args[2] = str_replace("cur_employee",$rec_empl,$args[2]); // replace if is in filter
    // check receiver company and modify accordingly
    $real_comp = $this->dbquery_singleField("SELECT company_id FROM clients where id=$rec_empl;","company_id");
    if (!empty($real_comp)) $rec_comp = $real_comp;
    // set current combo value
    $args[2] = str_replace("receiver_company",$rec_comp,$args[2]); // replace if is in filter
    $selected = (strpos($args[0],"receiver_company")!==false) ? $rec_comp : "";                              
    $selected = (strpos($args[0],"receiver_employee")!==false) ? $rec_empl : $selected;                              
    $selected = (strpos($args[0],"status")!==false) ? $this->fields['status'][0] : $selected;                              
    $res = $this->db_queryAll("SELECT ".$args[3]." FROM ".$args[1]." WHERE ".$args[2].$args[4],true);
    $return .= $this->WriteComboFlex($res,$args[3],0,$args[0],$selected,$args[5],$args[6],$args[7],$args[8],$args[9]);
    return $return;
  }
  function getHTMLcombo1($arg) {
    $arg1 = str_replace("&#39;","'",$arg);
    $args = explode("#",$arg1);  /*  args[0] is combo name
                                    args[1] is table
                                    args[2] is filter
                                    args[3] is invisible linked column index
                                    args[4] is optional sorting
                                    args[5] is optional class
                                    args[6] is optional tabindex
                                    args[7] is optional onFocusEvent
                                    args[8] is optional onChangeEvent
                                    args[9] is optional onBlurEvent
                                */
    //replace cur_company with the current one, wildcard when not client
    $cur_comp = ( $_SESSION['login_user']['is_client']) ? $_SESSION['login_user']['company_id'] : "%";
    $args[2] = str_replace("cur_company",$cur_comp,$args[2]); // replace if is in filter
    //replace receiver_company with the current one
    $rec_comp = (strpos($args[2],"receiver_company")!==false) ? $this->fields['receiver_company'][0] : "";
    if (empty($rec_comp)) $rec_comp = $_SESSION['login_user']['company_id'];
    if (!empty($_GET['receiver_company'])) $rec_comp = $_GET['receiver_company']; // superseed the current value
    $args[2] = str_replace("receiver_company",$rec_comp,$args[2]); // replace if is in filter
    //replace cur_company with the current one, wildcard when not client
    $is_client = ( $_SESSION['login_user']['is_client']) ? "1" : "0";
    $args[2] = str_replace("cur_client",$rec_comp,$args[2]); // replace if is in filter
    //replace cur_employee with the current one, wildcard when not client
    $rec_empl = (!empty($_GET['receiver_company']))? "" : $this->fields['receiver_employee'][0];
    $args[2] = str_replace("cur_employee",$rec_comp,$args[2]); // replace if is in filter
    // set current combo value
    $selected = (strpos($args[0],"receiver_company")!==false) ? $rec_comp : "";                              
    $selected = (strpos($args[0],"receiver_employee")!==false) ? $this->fields['receiver_employee'][0] : $selected;                              
    $res = $this->db_queryAll("SELECT ".$args[3]." FROM ".$args[1]." WHERE ".$args[2].$args[4],true);
    return $this->WriteComboFlex($res,$args[3],0,$args[0],$rec_empl,$args[5],$args[6],$args[7],$args[8],$args[9]);
  }
  /* Creates the dropdown depending on
  Format in Framework: ##DROPDOWN#DOMid;table(id,value,otherParams,...);filer##
  Format of Filter (optional): IF(target:statement):action1,ELSE:action2
  Possible values for action: exclude_self, exclude_others    // self refers to the value the user has in the same field
  example:
  ##DROPDOWN#receiver_company;companies(id,name);## //No filter
  ##DROPDOWN#receiver_company;companies(id,name);IF(self:is_client=0):exlude_self,ELSE:exclude_others##
  ##DROPDOWN#receiver_company;companies(value:id,display:name);IF(*companies(is_client)=0):companies(id)<>+clients(company_id),ELSE:companies(id)=+clients(company_id)##
  ##DROPDOWN#receiver_eomployee;clients(value:id,display:name, name_2, rel:company_id);##
  A star (*) creates a query with a join.
  A plus (+) replaces "table(field)" with the value from the database.
  */
  function createDropDown($text){
    $dropdownStartTag = "##DROPDOWN#";
    $start = strpos($text, $dropdownStartTag) + strlen($dropdownStartTag); //start of arguments
    $end = strpos($text, "##", $start);
    //extract all arguments
    $dropdownText = substr($text, $start, $end-$start); //IMPORTANT: variable is used again at the end of this function
    //separate in DOMid, target and filter
    $dropdownTextPartitioned = explode(";", $dropdownText);
    
    //extract id of DOM element
    $DOMid = $dropdownTextPartitioned[0];
    
    //extract table and fields from target
    $target = $this->extractTableAndFields($dropdownTextPartitioned[1]);
    $table = $target["table"];
    $fields = $target["fields"];
    $value = $target["value"];
    $display = $target["display"];
    $rel = $target["rel"];

    // process filter if not empty
    $extractedFilter = $dropdownTextPartitioned[2];
    if (!empty($extractedFilter)) {
      // Get user id from session for later
      $auth = $_SESSION['auth'];
      $id = $_SESSION["$auth"]["id"];

      $startNextArg = strpos($extractedFilter, "(");
      $endNextArg = strpos($extractedFilter, "):");
      //extract statement from filter
      $statement = substr($extractedFilter, $startNextArg + 1, $endNextArg-($startNextArg+1));
      $explodeTag = $this->getLogicalOperator($statement);
      $statementValues = explode($explodeTag, $statement);

      //process statement
      foreach ($statementValues as $sv) {
        if (strpos($sv, "*") !== false) { //value requires a join          
          $tmp = $this->extractTableAndFields(substr($sv, 1));
          $tmpTable = $tmp["table"];
          $tmpFields = $tmp["fields"];
          
          $sql = "SELECT $tmpFields FROM clients JOIN $tmpTable ON clients.company_id=$tmpTable.id WHERE clients.id=$id";
                   
        } else if (!ctype_digit($sv)) {
          $tmp = $this->extractTableAndFields($sv);
          $tmpTable = $tmp["table"];
          $tmpFields = $tmp["fields"];

          $sql = "SELECT $tmpFields FROM $tmpTable WHERE clients.id=$id";  
        } else {
          break;
        }
        $dbResult = $this->db_query($sql, "$tmpFields");
        //replace part of statement with value from database 
        $statementValues = array_replace($statementValues, array_fill_keys(array_keys($statementValues, $sv), $dbResult[0]["$tmpFields"]));
      }
      
      // extract action1 from filter
      $startNextArg = strpos($extractedFilter, ":", $endNextArg);
      $endNextArg = strpos($extractedFilter, ",", $startNextArg);
      $action1 = substr($extractedFilter, $startNextArg + 1, $endNextArg-($startNextArg+1));
      
      //extract action2 from filter
      $startNextArg = strpos($extractedFilter, ":", $endNextArg);
      $action2 = substr($extractedFilter, $startNextArg + 1);    
      
      //select the correct action depending on the statement from the filter
      $action = "";    
      switch ($explodeTag) {
        case "!=": ($statementValues[0] != $statementValues[1]) ? $action = $action1 : $action = $action2; break;
        case "<>": ($statementValues[0] != $statementValues[1]) ? $action = $action1 : $action = $action2; break;
        case "<=": ($statementValues[0] <= $statementValues[1]) ? $action = $action1 : $action = $action2; break;
        case ">=": ($statementValues[0] >= $statementValues[1]) ? $action = $action1 : $action = $action2; break;
        case "<": ($statementValues[0] < $statementValues[1]) ? $action = $action1 : $action = $action2; break;
        case ">": ($statementValues[0] > $statementValues[1]) ? $action = $action1 : $action = $action2; break;
        case "=": ($statementValues[0] == $statementValues[1]) ? $action = $action1 : $action = $action2; break;
      }
   
      $explodeTag = $this->getLogicalOperator($action);
      $actionPartitioned = explode($explodeTag, $action);
      foreach ($actionPartitioned as $part) {
        if (strpos($part, "+") !== false) {
          $tmp = $this->extractTableAndFields(substr($part, 1));
          $tmpTable = $tmp["table"];
          $tmpFields = $tmp["fields"];

          $sql = "SELECT $tmpFields FROM $tmpTable WHERE clients.id=$id";
          $dbResult = $this->db_query($sql, "$tmpFields");
          $action = str_replace($part, $dbResult[0]["$tmpFields"], $action);
        } else {
          $tmp = $this->extractTableAndFields($part);
          $tmpTable = $tmp["table"];
          $tmpFields = $tmp["fields"];

          $action = str_replace($part, "$tmpTable.$tmpFields", $action);          
        }
      }
      if($explodeTag == "!=") //replace != with <> for the mySQL query.
        $action = str_replace("!=", "<>", $action);

      $filter = $action;            
    } else {
      $filter = "1"; //If no filter was defined, all elements from the table are taken.
    }
    
    if ($dropdownText==="") {
      $dropdownText = $dropdownStartTag; // in case there is an error while extracting arguments: remove the dropdown tag to continue
    }
    $sql = "SELECT $fields FROM `$table` WHERE $filter";
    $tmp = $this->db_query($sql, $fields);
    $numRows = count($tmp);
    
    // Create the dropdown
    $dropdown = "<select id='$DOMid' name='$DOMid' class='dropdown'>";
//      $dropdown .= "<option class='option' value=''>Bitte auswählen</option>";      
    for ($i=0; $i<$numRows; $i++){
      $optionText = "";
      foreach ($display as $d) {
        $optionText .= $tmp[$i][$d]." ";  
      }
      
      if(empty($rel))
        $optionRel = "";
      else
        $optionRel = "rel='".$tmp[$i][$rel]."'";
      $dropdown .= "<option class='option' value='".$tmp[$i][$value]."'".$optionRel.">".$optionText."</option>";      
    }
    $dropdown .= "</select>";
    
    //replace content from framework with created dropdown
    $text = str_replace($dropdownStartTag.$dropdownText."##", $dropdown, $text);
    
    if (strpos($text, "##DROPDOWN#")!==false) { // if there is another dropdown, start process again.
      $text = $this->createDropDown($text);
    }
    return $text;
  }
  
  /* Extracts table and fields name from a specific structure
  structure: table(value:field1,display:field2,field3,...rel:field4)
  Required Tags: value (only one element allowed) & display (multiple elements allowed)
  Optional Tags: rel (if depending on another dropdown; only one element allowed)
  example: clients(value:id,display:name, name_2, rel:company_id)
  returns an array with table name and all fields as ONE string.
  */
  function extractTableAndFields($text) {
    //define tags
    $vTag = "value:";
    $dTag = "display:";
    $rTag = "rel:";
    
    //define return values
    $table = "";
    $fields = "";
    $value = "";
    $display = "";
    $rel = "";
    
    $startNextArg = strpos($text, "(");
    $endNextArg = strpos($text, ")");
    //extract table name
    $table = substr($text, 0, $startNextArg);
    //extract fields name with tags (will be removed later)
    $fields = substr($text, $startNextArg+1, $endNextArg-($startNextArg+1));
    
    //extract value tag
    $startNextArg = strpos($fields, $vTag) + strlen($vTag); //added length of tag since it doesn't interest us.
    $endNextArg = strpos($fields, $dTag, $startNextArg);    
    $value = substr($fields, $startNextArg, $endNextArg-$startNextArg);
    $value = str_replace(",", "", $value);
    //$value = array_filter(explode(",", $value));
    
    // extract display tag
    $startNextArg = $endNextArg + strlen($dTag);
    if(strpos($fields, $rTag) === false) {
      $display = substr($fields, $startNextArg);
      $display = array_filter(explode(",", $display));
    } else {
      $endNextArg = strpos($fields, $rTag, $startNextArg);
      $display = substr($fields, $startNextArg, $endNextArg-$startNextArg);
      $display = array_filter(explode(",", $display));
  
      //extract rel tag
      $startNextArg = $endNextArg + strlen($rTag);
      $rel = substr($fields, $startNextArg);
      $rel = str_replace(",", "", $rel);
      //$rel = array_filter(explode(",", $rel));
      
      //remove rel tag from fields
      $fields = str_replace($rTag, "", $fields);
    }
    //remove value and display tag from fields
    $fields = str_replace($vTag, "", $fields);
    $fields = str_replace($dTag, "", $fields);    
    
    return array("table"=>$table, "fields" => $fields, "value" => $value, "display" => $display, "rel" => $rel);
  } 
  
  function getLogicalOperator($text) {
    if(strpos($text, "!=") !== false)
      $tag = "!=";
    else if(strpos($text, "<>") !== false)
      $tag = "<>";
    else if(strpos($text, "<=") !== false)
      $tag = "<=";
    else if(strpos($text, ">=") !== false)
      $tag = ">=";
    else if (strpos($text, "<") !== false)
      $tag = "<";
    else if (strpos($text, ">") !== false)
      $tag = ">";
    else if (strpos($text, "=") !== false)
      $tag = "=";
    return $tag;
  }
  
  /* this function returns the cleaned HTML code in the current language for the passed named record of the text table
  */
  function getHTMLtext($rec) {
    $temp = "";
    $temp = $this->db_query("SELECT * from texts where name LIKE '".$rec."'", $this->table_struct['texts']['fields']);
    if (!empty($temp[0]['name']) && $temp[0]['active']==1) { // when items found
      switch ($_SESSION['lang']) {
        default:
        case $_SESSION['languages'][1]:  $temp = $this->cleanEscaped($temp[0]['text1']); break;
        case $_SESSION['languages'][2]:  $temp = $this->cleanEscaped($temp[0]['text2']); break;
        case $_SESSION['languages'][3]:  $temp = $this->cleanEscaped($temp[0]['text3']); break;
      }
    } else
      $temp = "";
    // Here, we can insert special hanling for a standard text
    //
    $temp = $this->processEditorCmds($temp);
    return $temp;
  }

  function getNewsPageRef() {
    // We must search the page ref containing the news, for this, search the layouts containing "news"
    $newslayouts = $this->db_queryAll("SELECT * FROM page_layouts where layout_struct like '%>%news</td>%'");
    // now we should skip layout named "h_layout" (when some) and take the menu ref of the first that doesn't have h_layout  
    for($i=0;$i<sizeof($newslayouts);$i++) 
      if (strpos($newslayouts[$i]['layout_struct'],"h_news")===false) {
        $newslayoutid = $newslayouts[$i]['id'];
        break;
      }
    // now we search the menu with this layout
    $newsmenu = $this->db_queryAll("SELECT * FROM menus WHERE page_layout=".$newslayoutid);
    if (sizeof($newsmenu)==1)
      return $newsmenu[0]['menu_title'];
    else
      return $this->getMenuRefForPgLayout("news"); // current default
  }
/*
  Function to echo the news on the home page
  Echo nothing 
*/
  function getHTMLnewsHome() {
    if ($_SESSION['page']=='home') { // when home, filter using on_home
      $temp = $this->db_query("SELECT * from news where sorting<>'' order by sorting, date desc ", $this->table_struct['news']['fields']);
      echo "<table class='news_home'>";
    }
    else { // all
      $temp = $this->db_query("SELECT * from news order by CASE sorting WHEN '' THEN '99999' END, date desc", $this->table_struct['news']['fields']);
      echo "<table class='news'>";
    }
    $count = sizeof($temp);
    $pos = "0";
    // rewrite texts when slash inside
    for($i=0;$i<$count;$i++){
      $temp[$i]['text1'] = addslashes($temp[$i]['text1']);
      $temp[$i]['text2'] = addslashes($temp[$i]['text2']);
      $temp[$i]['text3'] = addslashes($temp[$i]['text3']);
    }
    // for all news
    for($i=0;$i<$count;$i++){
      $title = $this->get_for_current_language($temp[$i]['title1'],$temp[$i]['title2'],$temp[$i]['title3']);
      $text = $this->get_for_current_language($temp[$i]['text1'],$temp[$i]['text2'],$temp[$i]['text3']);
      if(($temp[$i]['sorting'] <> "") && $this->inDateRange($temp[$i]['date_from'],$temp[$i]['date_to'])){
        echo "<tr><td class='news_left_col'><table width='auto' class='news_left'><tr><td class='news_title'>".nl2br($title)."</td>";
        $pictstring = "";
        if (!empty($temp[$i]['picts'])) { // when has picture
          $pictstring = "<a target='_blank' href='".$this->table_defs['news']['paths']['original'].$temp[$i]['picts']."'><img class='news_home_pict' align='left' src='".$this->table_defs['news']['paths']['size1'].$temp[$i]['picts']."'/></a>";
        }
        if(strlen($text) >= '500'){
          $news_text = substr($text, 0, 500)." ...";
          $news_text = $this->cleanEscaped($news_text);
          if (!empty($pictstring)) { //when picture, place after leading <p> or <br>
            $newsbeg = $this->skipHTMLtags($news_text,0);
            $news_text = substr($news_text,0,$newsbeg).$pictstring.substr($news_text,$newsbeg,strlen($news_text)-$newsbeg);
          }  
          echo "<tr><td class='text'>".$news_text."</td></tr><tr><td class='text' align='right'><a href='index.php?hmenu=".$this->getNewsPageRef()."&projektnews=".$temp[$i]['id']."'>[+]</a></td></tr></table></td>";
        }else{
          $news_text =$text;
          echo "<tr><td class='text'>".$news_text."</td></tr></table></td>";
        }
        $stand++;
        if ($i+1<$count) { // when has a right side
          $title = $this->get_for_current_language($temp[$i+1]['title1'],$temp[$i+1]['title2'],$temp[$i+1]['title3']);
          $text = $this->get_for_current_language($temp[$i+1]['text1'],$temp[$i+1]['text2'],$temp[$i+1]['text3']);
          echo "<td style='vertical-align:top;'><table width='auto' class='news_right'><tr><td class='news_title'>".nl2br($title)."</td></tr>";
            
          $pictstring = "";
          if (!empty($temp[$i+1]['picts'])) { // when has picture
            $pictstring = "<a target='_blank' href='".$this->table_defs['news']['paths']['original'].$temp[$i+1]['picts']."'><img class='news_home_pict' align='left' src='".$this->table_defs['news']['paths']['size1'].$temp[$i+1]['picts']."'/></a>";
          }
         if(strlen($text) >= '500'){
            $news_text = substr($text, 0, 500)." ...";
            $news_text = $this->cleanEscaped($news_text);
            if (!empty($pictstring)) { //when picture, place after leading <p> or <br>
              $newsbeg = $this->skipHTMLtags($news_text,0);
              $news_text = substr($news_text,0,$newsbeg).$pictstring.substr($news_text,$newsbeg,strlen($news_text)-$newsbeg);
            }  
            echo "<tr><td class='text'>".$news_text."</td></tr><tr><td class='text' align='right'><a href='index.php?hmenu=".$this->getNewsPageRef()."&projektnews=".$temp[$i+1]['id']."'>[+]</a></td></tr></table></td>";
          }else{
            $news_text =$text;
            echo "<tr><td class='text'>".$news_text."</td></tr></table></td>";
          }
          $i++;
          $stand++;
        }
       echo "</tr>"; 
      }
    }//end for

    echo "</table>";
    
  }

/*
  Function to echo the news on the current page
  Echo nothing 
*/
  function getHTMLnews($events=false) {
    echo "<div id='top'></div>";
    if (isset($_GET['projektnews'])) { // when aspecific news, filter it
      $temp = $this->db_query("SELECT * from news where id=".$_GET['projektnews']." order by sorting, date desc ", $this->table_struct['news']['fields']);
      echo "<table class='news'>";
    }
    else { // all (or events)
      $where = $events==false ? "" : "where event=1 ";
      $temp = $this->db_query("SELECT * from news ".$where."order by CASE sorting WHEN '' THEN '99999' END, date desc", $this->table_struct['news']['fields']);
      echo "<table class='news'>";
    }
    $count = sizeof($temp);
    $pos = "0";
    // rewrite texts when slash inside
    for($i=0;$i<$count;$i++){
      $temp[$i]['text1'] = addslashes($temp[$i]['text1']);
      $temp[$i]['text2'] = addslashes($temp[$i]['text2']);
      $temp[$i]['text3'] = addslashes($temp[$i]['text3']);
    }
    // for all news
    $news_class='news_odd';
    for($i=0;$i<$count;$i++){
      $title = $this->get_for_current_language($temp[$i]['title1'],$temp[$i]['title2'],$temp[$i]['title3']);
      $text = $this->get_for_current_language($temp[$i]['text1'],$temp[$i]['text2'],$temp[$i]['text3']);
      if (!empty($text) && $this->inDateRange($temp[$i]['date_from'],$temp[$i]['date_to'])) { // only when some text
        if ($news_class=='news_even') // toggle background
          $news_class='news_odd';
        else
          $news_class='news_even';
        echo "<tr><td style='vertical-align:top;'><table width='auto' class='".$news_class."'><tr><td colspan='2' class='news_title'>".nl2br($title)."</td>";
        $pictstring = "";
        if (!empty($temp[$i]['picts'])) { // when has picture 
          $pictstring = "<a target='_blank' href='".$this->table_defs['news']['paths']['original'].$temp[$i]['picts']."'><img class='news_pict' src='".$this->table_defs['news']['paths']['size2'].$temp[$i]['picts']."'/></a>";
        }
        if (!empty($temp[$i]['pdf_file'])) { // when has pdf file
          $pictstring = "<a class='news_pict_icon' target='_blank' href='".$this->table_defs['news']['paths']['docs'].$temp[$i]['pdf_file']."'>".$_SESSION['openfile'][$_SESSION['lang']]."<BR />
          <BR><img class='datalisticon' src='".$this->iconsDir."pdf.gif"."'></a>";
        }
        $dow_array = explode(",",$_SESSION['cal_wdays'][$_SESSION['lang']]);  
        $dow = str_replace("'","",$dow_array[date("w",strtotime($temp[$i]['date']))]);
        $news_text = $this->cleanEscaped($text);
        echo "<tr>";
        if ($_SESSION['picture_column']!="off") // when picture column required
          echo "<td class='news_pict_col'>".$pictstring."</td>";
        echo "<td class='news_text'>".$news_text."</td></tr>";
        echo "<tr>";
        if ($_SESSION['picture_column']!="off") // when picture column required
          echo "  <td>&nbsp;</td>";
        echo "  <td class='news_date'";
        if ($_SESSION['picture_column']=="off" && $temp[$i]['date'] == "1970-01-01") // when picture column and date undefined
          echo " style='color: #d9d9d9;'";
        echo ">".$_SESSION['date'][$_SESSION['lang']].": $dow. ".$this->format_date($temp[$i]['date'])."
                <div style='float:right'><a href='#top'><img src='".$this->iconsDir."up.gif'/></a></div></td>
              </tr>"; 
        echo "</table></td>";
        echo "</tr>";
      }
    }//end for
    echo "</table>";
  }

/*
  Function to return the HTML code to display the main menu
*/
  function getHTMLmainMenu() {
    $return = "<table class='limmat_mainMenu'>
                <tr >
                  <td class= 'limmat_menu'>";
    $return .= getHTMLmenu();            
    $return .= "</td></tr></table>";
    return $return;
  }  
  
/*
  This will returns the HTML code for Esmeralda register logo and button
*/  
  function getHTMLesmeralda_register() {
    return "<div align='center'><br><br><a href='index.php?hmenu=wwt46'><img src='".$this->iconsDir."esmeraldacolor.gif' width='118' height='130' border='0'></a><br><br>
            <form name='esmeraldaReg' method='post' action='index.php?hmenu=wwt46'>
            <input type='submit' name='Submit' value='".$_SESSION['es_register'][$_SESSION['lang']]."'>
            </form>
            </div>";
  }

/*
  Function to echo the esmeralda News )
*/
  function getHTMLesmeraldaNews() {
    $return = "";
    $temp = $this->db_query("SELECT * from news where esmeralda=1 order by sorting, date desc ", $this->table_struct['news']['fields']);
    $num = sizeof($temp);
    // rewrite texts when slash inside
    for($i=0;$i<$count;$i++){
      $temp[$i]['text1'] = addslashes($temp[$i]['text1']);
      $temp[$i]['text2'] = addslashes($temp[$i]['text2']);
      $temp[$i]['text3'] = addslashes($temp[$i]['text3']);
    }
    $return .= "<table width='100%'>";
    for($i=0;$i<=$num;$i++){
      if($this->inDateRange($temp[$i]['date_from'],$temp[$i]['date_to'])){
        $title = $this->get_for_current_language($temp[$i]['title1'],$temp[$i]['title2'],$temp[$i]['title3']);
        $text = $this->get_for_current_language($temp[$i]['text1'],$temp[$i]['text2'],$temp[$i]['text3']);
        $pictstring = "";
        if (!empty($temp[$i]['picts'])) { // when has picture
          $pictstring = "<a target='_blank' href='".$this->table_defs['news']['paths']['original'].$temp[$i]['picts']."'><img class='news_home_pict' align='left' src='".$this->table_defs['news']['paths']['size1'].$temp[$i]['picts']."'/></a>";
        }
        if (!empty($text)) { // only when some text
          $return .= "<tr><td style='width:auto' class='news_title'>".$title."</td></tr>";
          if(strlen($this->cleanEscaped($text)) >= 500000){
            $news_text = substr($this->cleanEscaped($text), 0, 500)." ...";
            if (!empty($pictstring)) { //when picture, place after leading <p> or <br>
              $newsbeg = $this->skipHTMLtags($news_text,0);
              $news_text = substr($news_text,0,$newsbeg).$pictstring.substr($news_text,$newsbeg,strlen($news_text)-$newsbeg);
            }  
            $return .= "<tr><td style='width:auto' class='text'>".$news_text."</td></tr><tr><td  style='width:80%' class='text' align='right'><a href='index.php?hmenu=news&projektnews=".$temp[$i]['id']."'>[+]</a></td></tr>";
          }else{
            $news_text =$text;
            $news_text = $this->cleanEscaped($news_text);
            $return .= "<tr><td style='width:auto' class='text'>".$news_text."</td></tr>";
          }
        }
      }
    }
    $return .= "</table>";
    return $return;
  }

/*
  Function to echo the esmeralda News )
*/
  function getHTMLesmeraldaSponsors() {
    $sponsors = $this->getHTMLtext('sponsors');
    if (empty($sponsors)) { // when empty, get from sponsors table with 3 columns
                            // when only one on a row, it will be placed in the middle, when two, on both sides
      $temp = $this->db_query("SELECT * from sponsors where sponsor_type=2 order by sorting,name", $this->table_struct['sponsors']['fields']);
      $num = sizeof($temp);
      if ($num>0) { // when some to display
        $sponsors = "<table width='100%'><tr><td width='33%'>&nbsp;<BR/></td><td width='33%'>&nbsp;<BR/></td><td>&nbsp;<BR/></td></tr>";
        $i = 0;
        while ($i<$num) {
          if ($temp[$i]['sorting']<100) { // standard layout
            if ($i+2<$num) { // when enough for one row
              $sponsors .= "<tr><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['pic_name']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td>";
              $sponsors .= "<td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i+1]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i+1]['pic_name']."'/></a></td>";
              $sponsors .= "<td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i+2]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i+2]['pic_name']."'/></a></td></tr>";
              $i += 3;
            } else
            if ($i+1<$num) { // when only two for one row
              $sponsors .= "<tr><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td><td>&nbsp;</td>";
              $sponsors .= "<td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i+1]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i+1]['pic_name']."'/></a></td></tr>";
              $i += 2;
            } else { // is a single one
              $sponsors .= "<tr><td>&nbsp;</td><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['pic_name']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td><td>&nbsp;</td>";
              $i++;
            }
          } else { // in the middle: one over the other layout
            $sponsors .= "<tr><td>&nbsp;</td><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['pic_name']."'>
                          <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td><td>&nbsp;</td>";
            $i++;
          }
        }
      }
      $sponsors .= "";
    } 
    return $sponsors;
  }
  
/*
  Function to echo the esmeralda home page content )
  The predefined page looks like:
  -------------------------
  |                        |
  |   NEWS                 |
  |   ...       Registr    |
  |    (70%)      (30%)    |
  |                        |
  |   Evtl.:Startlist      |
  |        (100%)                |
  |                        |
  |  Evtl:Sponsors (100%)  |
  ----------------------- --
*/
  function getHTMLesmeraldaHome() {
    echo "<table width='100%'><tr><td>";
    echo $this->getHTMLesmeraldaNews();
    echo "</td><td style='width:30%'>";
    echo $this->getHTMLesmeralda_register();
    echo "</td></tr></table>";
// here come the eventual start lists
    echo "<table width='100%'><tr><td>
          <BR/></td></tr>
          <tr><td>";
    echo $this->getHTMLtext('startlist');
    echo "</td></tr></table>";          
// here come the eventual sponsors
    echo "<table width='100%'><tr><td>
          <BR/></td></tr>
          <tr><td>";
    echo $this->getHTMLesmeraldaSponsors();
    echo "</td></tr></table>";          
  }
  
/*
  Function to echo the esmeralda home page content )
*/
  function getHTMLesmeraldaHomeOld() {
    $temp = $this->db_query("SELECT * from news where esmeralda=1 order by sorting, date desc ", $this->table_struct['news']['fields']);
    $num = sizeof($temp);
    // rewrite texts when slash inside
    for($i=0;$i<$count;$i++){
      $temp[$i]['text1'] = addslashes($temp[$i]['text1']);
      $temp[$i]['text2'] = addslashes($temp[$i]['text2']);
      $temp[$i]['text3'] = addslashes($temp[$i]['text3']);
    }
    echo "<table width='100%'><tr><td><table>";
    for($i=0;$i<=$num;$i++){
      if('1' == "1"){
        $title = $this->get_for_current_language($temp[$i]['title1'],$temp[$i]['title2'],$temp[$i]['title3']);
        $text = $this->get_for_current_language($temp[$i]['text1'],$temp[$i]['text2'],$temp[$i]['text3']);
        $pictstring = "";
        if (!empty($temp[$i]['picts'])) { // when has picture
          $pictstring = "<a target='_blank' href='".$this->table_defs['news']['paths']['original'].$temp[$i]['picts']."'><img class='news_home_pict' align='left' src='".$this->table_defs['news']['paths']['size1'].$temp[$i]['picts']."'/></a>";
        }
        if (!empty($text)) { // only when some text
          echo "<tr><td style='width:auto' class='news_title'>".$title."</td></tr>";
          if(strlen($text) >= 300){
            $news_text = substr($text, 0, 300)." ...";
            $news_text = $this->cleanEscaped($news_text);
            $newsRef = $this->getMenuRefForPgLayout("news");
            if (!empty($pictstring)) { //when picture, place after leading <p> or <br>
              $newsbeg = $this->skipHTMLtags($news_text,0);
              $news_text = substr($news_text,0,$newsbeg).$pictstring.substr($news_text,$newsbeg,strlen($news_text)-$newsbeg);
            }  
            echo "<tr><td style='width:auto' class='text'>".$news_text."</td></tr><tr><td  style='width:80%' class='text' align='right'><a href='index.php?hmenu=".$newsRef."&projektnews=".$temp[$i]['id']."'>[+]</a></td></tr>";
          }else{
            $news_text =$text;
            $news_text = $this->cleanEscaped($news_text);
            echo "<tr><td style='width:auto' class='text'>".$news_text."</td></tr>";
          }
        }
      }
    }
    echo "</table></td><td style='width:30%'>";
    echo $this->getHTMLesmeralda_register();
    echo "</td></tr></table>";
// here come the eventual start lists
    echo "<table width='100%'><tr><td>
          <BR/></td></tr>
          <tr><td>";
    echo $this->getHTMLtext('startlist');
    echo "</td></tr></table>";          
// here come the eventual sponsors
    echo "<table width='100%'><tr><td>
          <BR/></td></tr>
          <tr><td>";
    $sponsors = $this->getHTMLtext('sponsors');
    if (empty($sponsors)) { // when empty, get from sponsors table with 3 columns
                            // when only one on a row, it will be placed in the middle, when two, on both sides
      $temp = $this->db_query("SELECT * from sponsors where sponsor_type=2 order by sorting,name", $this->table_struct['sponsors']['fields']);
      $num = sizeof($temp);
      if ($num>0) { // when some to display
        $sponsors = "<table width='100%'><tr><td width='33%'>&nbsp;<BR/></td><td width='33%'>&nbsp;<BR/></td><td>&nbsp;<BR/></td></tr>";
        $i = 0;
        while ($i<$num) {
          if ($temp[$i]['sorting']<100) { // standard layout
            if ($i+2<$num) { // when enough for one row
              $sponsors .= "<tr><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['pic_name']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td>";
              $sponsors .= "<td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i+1]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i+1]['pic_name']."'/></a></td>";
              $sponsors .= "<td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i+2]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i+2]['pic_name']."'/></a></td></tr>";
              $i += 3;
            } else
            if ($i+1<$num) { // when only two for one row
              $sponsors .= "<tr><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td><td>&nbsp;</td>";
              $sponsors .= "<td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i+1]['picts']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i+1]['pic_name']."'/></a></td></tr>";
              $i += 2;
            } else { // is a single one
              $sponsors .= "<tr><td>&nbsp;</td><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['pic_name']."'>
                            <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td><td>&nbsp;</td>";
              $i++;
            }
          } else { // in the middle: one over the other layout
            $sponsors .= "<tr><td>&nbsp;</td><td><a target='_blank' href='".$this->table_defs['sponsors']['paths']['original'].$temp[$i]['pic_name']."'>
                          <img class='sponsor_pict' align='center' src='".$this->table_defs['sponsors']['paths']['size2'].$temp[$i]['pic_name']."'/></a></td><td>&nbsp;</td>";
            $i++;
          }
        }
      }
      
    } 
    echo $sponsors;
    echo "</td></tr></table>";          
  }
  
  /*
    Function to return the HTML code for the project search form
  */
  function project_search_form($countryID) {
    
    // get in for selected language
//    $results = $this->db_queryLng("SELECT DISTINCT b.country_id, a.name FROM countries a JOIN projects b ON a.id=b.country_id ORDER BY a.name",array("b.country_id","a.name"),array("name"));
    $results = $this->db_query("SELECT countries.id, countries.".$this->getFieldName(name)." as name,countries.id FROM countries, projects 
                                where projects.country_id=countries.id and projects.active=1 Order By ".$this->getFieldName(name),array("id","name"));
    $countries_list = $this->makeList($results);                                                                   
    $today = date("Y");
    $num_years = "6";
    $years_list = array();
    $theCountryID = intval($countryID);
    if (empty($theCountryID) && !empty($_POST['country'])) { // eventually get from POST (search keyval for given text)
      $theCountryID = intval($_POST['country']);
    }
    
    for ($j=0; $j<$num_years;$j++){  
      $years_list[$today-$j] = $today-$j;//."-01-01"
    }  
    $results = $this->db_query("SELECT DISTINCT donation, donation FROM projects where active=1 ORDER BY donation", array("donation","donation"));
    $donations_list = $this->makeList($results);                                                                   
    
    $str = "<table><tr><td>
            <form name='show_all' method='post' action='".$_SERVER['PHP_SELF']."?hmenu=projekte'>
            <input type='submit' name='all' value='".$_SESSION['show_all'][$_SESSION['lang']]."'>
            </form></td>";
      
    $str .= "<td>
              <form name='filter' method='post' action='".$_SERVER['REQUEST_URI']."'>";
              
    $str .= "&nbsp;&nbsp;".$this->WriteCombo($countries_list, "country", $theCountryID, $this->get_for_current_language("All countries", "Alle Länder","All countries"), $first="","",  "1");                              
    $str .= "</td><td>";
    $str .= "&nbsp;&nbsp;".$this->WriteCombo($donations_list, "donation", $_POST['donation'], $this->get_for_current_language("All contributors", "Alle Zustiftungen","All contributors"), $first="","",  "1");                              
    
    $str .= "<td>";
    $str .= "&nbsp;&nbsp;".$this->WriteCombo($years_list, "year", $_POST['year'], $this->get_for_current_language("All years", "Alle Jahre","All years"), $first="","",  "1");                              
   
    $str .= "</td>
             <td>
               &nbsp;&nbsp;<input type='submit' name='itemsearch' value='".$_SESSION['Search'][$_SESSION['lang']]."'>
             </td>
          </form>
        </tr>
      </table>";
    $str .= "
      <table>
        <tr>
          <form name='searchbykeyword' method='post' action='".$_SERVER['REQUEST_URI']."'>
            <td class='text' height='10'>
            ".$_SESSION['SearchByKey'][$_SESSION['lang']].": &nbsp;&nbsp;<input type='text' name='criteria' value='".$this->searchvalue."' size='52'>
              <input type='submit' name='SearchByKey' value='".$_SESSION['Search'][$_SESSION['lang']]."'>
            </td>
          </form>
        </tr>
      </table>";
    return $str;    
  }
 
/*
  Function to echo get and display the projects page content
*/
  function getHTMLprojects() {

    if (isset($_POST['itemsearch'])) $search = "itemsearch";
    if (isset($_POST['SearchByKey'])) $search = "SearchByKey";
    if (!empty($_POST['country'])) {
      $countryid = $_POST['country'];
//      $countryid = $this->db_query("SELECT id FROM countries where INSTR(name1,'$country')>0 OR INSTR(name2,'$country')>0 OR INSTR(name3,'$country')>0",array("id"));
//      $countryid = $countryid[0]['id'];
    }
    else if (!empty($_GET['suche']) && !empty($_GET['suchspalte']) && strtolower($_GET['suchspalte'])=='country') {
      $country = $_GET['swert'];
      $search = "itemsearch";
      $countryid = $this->db_query("SELECT id FROM countries where INSTR(name1,'$country')>0 OR INSTR(name2,'$country')>0 OR INSTR(name3,'$country')>0",array("id"));
      $countryid = $countryid[0]['id'];
    }
    else if (!empty($_GET['suche']) && !empty($_GET['suchspalte']) && strtolower($_GET['suchspalte'])=='c_code') {
      $search = "itemsearch";
      $countryid = $this->db_query("SELECT id FROM countries where c_code='".$_GET['swert']."'",array("id"));
      $countryid = $countryid[0]['id'];
    }
    else
      $countryid="";
    $year = $_POST['year'];
    $donation = $_POST['donation'];
    $criteria = $_POST['criteria'];
    if (empty($criteria) && !empty($_GET['criteria'])) {
      $criteria = $_GET['criteria'];
      $search = "SearchByKey";      
    }
    if (isset($_POST["all"]) || ($countryid=="" && $year=="" && $donation=="" && $criteria=="" )) { // when all, remove all filters
      $search = "";
      $country = "";
      $donation = "";
      $year = "";
      $criteria = "";
    }
    $this->searchvalue = $criteria;
    // look for GET params
    if (isset($_GET['fieldsearch'])) { // yes, search for a field
      $search = "fieldSearch";
      $field = $_GET['field'];
      $value = $_GET['field_value'];
    }
    
    $join = " join countries on country_id=countries.id join regions on countries.region_id=regions.id join local_partners on local_partner_nr=LN ";
    $joinedArray = array_merge($this->table_struct['regions']['fields'],$this->table_struct['countries']['fields'],$this->table_struct['local_partners']['fields']);
    
    $search_fields = $this->table_struct['projects']['fields']; // all fields
    $search_fields[array_search("region_id", $search_fields)]="projects.region_id"; // replace because of join on countries
    $search_fields = array_filter($search_fields, function ($v) { return $v != 'id'; });  // remove id
    if ($_SESSION['lang']=="es") { // when spanish, remove english and german
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_name1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_name2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'local_partner1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'local_partner2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_descr1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_descr2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_type1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_type2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'more1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'more2'; });  // remove
      $select = "SELECT *,name3,region3 "; 
      $order = "name3"; 
    } else
    if ($_SESSION['lang']=="de") { // when german, remove english and spanish
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_name1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_name3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'local_partner1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'local_partner3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_descr1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_descr3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_type1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_type3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'more1'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'more3'; });  // remove 
      $select = "SELECT *,name2,region2 "; 
      $order = "name2"; 
    } else { // english, remove german and spanish
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_name2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_name3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'local_partner2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'local_partner3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_descr2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_descr3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_type2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'project_type3'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'more2'; });  // remove 
      $search_fields = array_filter($search_fields, function ($v) { return $v != 'more3'; });  // remove 
      $select = "SELECT *,name1,region1 "; 
      $order = "name1"; 
    }

    if($search == "itemsearch"){
      if(empty($countryid) && !empty($year) && !empty($donation)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND donation ='".$donation."' AND (YEAR(project_beg)  <= '".$year."' && project_beg != '0000-00-00') && ((YEAR(project_end)  >= '".$year."' || project_end = '0000-00-00') &&  project_beg != '0000-00-00')";
      }
      if(empty($countryid) && empty($year) && !empty($donation)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND donation ='".$donation."'";
      }
      if(empty($year) && !empty($countryid) && !empty($donation)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND country_id ='".$countryid."' AND donation ='".$donation."'";
      }
      if(empty($year) && empty($donation) && !empty($countryid)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND country_id ='".$countryid."'";
      }
      
      if(empty($donation) && !empty($year) && !empty($countryid)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND country_id ='".$countryid."' AND (YEAR(project_beg)  <= '".$year."' && project_beg != '0000-00-00') && ((YEAR(project_end)  >= '".$year."' || project_end = '0000-00-00') &&  project_beg != '0000-00-00')";
      }
      if(empty($donation) && empty($countryid) && !empty($year)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND (YEAR(project_beg)  <= '".$year."' && project_beg != '0000-00-00') && ((YEAR(project_end)  >= '".$year."' || project_end = '0000-00-00') &&  project_beg != '0000-00-00')";
      }
      if(!empty($countryid) && !empty($donation) && !empty($year)){
        $sql = $select." FROM projects ".$join."WHERE active=1 AND donation ='".$donation."' AND country_id ='".$countryid."' AND (YEAR(project_beg)  <= '".$year."' && project_beg != '0000-00-00') && ((YEAR(project_end)  >= '".$year."' || project_end = '0000-00-00') &&  project_beg != '0000-00-00')";
      }
      if(empty($countryid) && empty($donation) && empty($year)){
        $sql = $select." FROM projects ".$join."WHERE active=1";
      }
      
      $sql .= " ORDER BY project_beg DESC ";
      
      $temp = $this->db_query($sql, $this->table_struct['projects']['fields']);
      
    }else if($search == "SearchByKey"){  // search following criteria
      $i = 1;
      foreach($search_fields as $field){
        if($i == 1){
          $search_str .= "( "; 
        }
        
        if(is_numeric($criteria) ){
          $search_str .= $field." = ".$criteria."";
        } else {
          $search_str .= $field." LIKE '%".$criteria."%'";
        }
        
        if($i != sizeof($search_fields)){
          $search_str .= " OR ";
          $i++;
        } else {
          $search_str .= " ) ";
        }
      }
      
      $temp = $this->db_query($select." FROM projects ".$join."WHERE ".$search_str." ORDER BY project_beg DESC",array_merge($this->table_struct['projects']['fields'],$joinedArray));
    }else if($search == "fieldSearch"){  // search a specific field
      $temp = $this->db_query($select." FROM projects ".$join."WHERE ".$field." LIKE '".$value."' ORDER BY project_beg DESC",array_merge($this->table_struct['projects']['fields'],$joinedArray));
    }else{ // no search mask
      $temp = $this->db_query($select." FROM projects ".$join." WHERE active=1 ORDER BY ".$order.", project_beg DESC",array_merge($this->table_struct['projects']['fields'],$joinedArray));
    }
    // display the projects 
    echo "<table width='100%' border='0' cellspacing='1' cellpadding='0'>
      <tr><td class='texttitle'>".$_SESSION['projects'][$_SESSION['lang']]."</td><td><a name='kopf'></a></td></tr>
      <tr><td bgcolor='#999999' height='1' class='text'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr>
      <tr><td>";
    
    if( !isset($_GET['print'])){
      echo $this->project_search_form($countryid);
    }
    
    echo "</td></tr><tr><td bgcolor='#999999' height='1' class='text'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr></table>";
  
    echo "<table border='0' cellpadding='0' cellspacing='0'>";
    $heute = date("Y-m-d");
    
    for ($i=0; $i<sizeof($temp);$i++){
      if($i%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
      //laufzeit formatieren
      $monate = $_SESSION['cal_months'][$_SESSION['lang']];
      $monate = str_replace("'","",$monate);
      $monate = explode(",",$monate);
      $datum_zerlegt = explode("-", $temp[$i]['project_beg']); 
      $monat = $monate[$datum_zerlegt[1]-1];
      $jahr = $datum_zerlegt[0];
      $laufzeit = $monat." ".$jahr." - ";
      $datum_zerlegt = explode("-", $temp[$i]['project_end']); 
      $monat = $monate[$datum_zerlegt[1]-1];
      $jahr = $datum_zerlegt[0];
      $laufzeit .= $monat." ".$jahr;
      $locpartnerid = $temp[$i]['local_partner_nr'];
      
      //mehr zusammenstellen 
      $more = "";
      //(News betreffend Projekt vorhanden?)
      $sql = "select id from news` where ".$this->getFieldName("more")." like '%".$temp[$i][$this->getFieldName("more")]."%'";
      $news = $this->db_queryAll($sql);
      if(sizeof($news)>0){
        for($j=0;$j<sizeof($news);$j++){
          $mehr .= "<BR><a href=index.php?hmenu=wwt&menu=wwt4&projektnews=".$news[$j]."#".$news[$j].">".$_SESSION['project_news'][$_SESSION['lang']]."</a>";
        }
      }
    
      if($temp[$i][$this->getFieldName("more")] != ""){
        $mehrpack = explode(";",$temp[$i][15]);
        $anzmehr = count($mehrpack);  
                
        for($j=0;$j<$anzmehr;$j++){
         $more .= "<BR><a href='http://".$mehrpack[$j]."' target='_blank'>".$mehrpack[$j]."</a>";
        }
      }//fertig--->mehr zusammenstellen
    
      echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_country'][$_SESSION['lang']]."</td>
            <td class='text' bgcolor='$bgcolor'>".$this->getCountryName($temp[$i]['country_id'])."</td></tr>";
      
      if(!empty($temp[$i]['location'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_location'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$temp[$i]['location']."</td></tr>";
      }
      
      if(!empty($temp[$i]['donation'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_donation'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$temp[$i]['donation']."</td></tr>";
      }
      
      if(!empty($temp[$i][$this->getFieldName("project_name")])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_name'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'><strong>".$temp[$i][$this->getFieldName("project_name")]."</strong></td></tr>";
      }
      
      if(!empty($temp[$i][$this->getFieldName("local_partner")])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_locpartn'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$temp[$i][$this->getFieldName("local_partner")]."</td></tr>";
      }
      else {
        $localpartner = $this->db_query("SELECT name FROM local_partners where id=$locpartnerid",array("name"));
        $localpartner = $localpartner[0]['name'];
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_locpartn'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$localpartner."</td></tr>";
      }
      
      if(!empty($temp[$i][$this->getFieldName("project_type")])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_type'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'>".$temp[$i][$this->getFieldName("project_type")]."</td></tr>";
      }
      
      if(!empty($laufzeit)){
        echo "
        <tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_run'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$laufzeit."</td></tr>
        <tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_status'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>"; 
        if ($heute < $temp[$i][7] || $temp[$i][7] == '0000-00-00'){
          $status = $_SESSION['project_status_prep'][$_SESSION['lang']];
        }
        if (($heute >= $temp[$i][7] && $heute <= $temp[$i][8] && $temp[$i][7] != '0000-00-00') || ($temp[$i][8] == "0000-00-00" && $temp[$i][7] != '0000-00-00')){
          $status = $_SESSION['project_status_run'][$_SESSION['lang']];
        }
        if ($heute > $temp[$i][8] && $temp[$i][8] != "0000-00-00"){
          $status = $_SESSION['project_status_term'][$_SESSION['lang']];
        }
        echo "$status</td></tr>";
      }
      echo "<tr><td bgcolor='$bgcolor' height='5' colspan='2'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr>";
      
      if(!empty($temp[$i]['year_beneficiary'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_benef'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$temp[$i]['year_beneficiary']."</td></tr>";
      }
      
      if(!empty($temp[$i]['project_total'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_tot'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'>".$temp[$i]['currency']." ".number_format($temp[$i]['project_total'],0,".","'")."</td></tr>";
      }
      
      if(!empty($temp[$i]['contrib_limmat'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_limmat'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'>".$temp[$i]['currency']." ".number_format($temp[$i]['contrib_limmat'],0,".","'")."</td></tr>";
      }
      
      if(!empty($temp[$i]['contrib_local_partner'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_loc'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'>".$temp[$i]['currency']." ".number_format($temp[$i]['contrib_local_partner'],0,".","'")."</td></tr>";
      }
      if(!empty($temp[$i]['contrib_oda'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_oda'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'>".$temp[$i]['currency']." ".number_format($temp[$i]['contrib_oda'],0,".","'")."</td></tr>";
      }
      
      if(!empty($temp[$i]['contrib_others'])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_others'][$_SESSION['lang']]."</td>
              <td class='text' bgcolor='$bgcolor'>".$temp[$i]['currency']." ".number_format($temp[$i]['contrib_others'],0,".","'")."</td></tr>";
      }
      
      echo "<tr><td bgcolor='$bgcolor' height='5' colspan='2'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr>";
      
      if(!empty($temp[$i][$this->getFieldName("project_descr")])){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_descr'][$_SESSION['lang']]."";
        if(!empty($temp[$i]['pict'])){
          echo "<br /><br /><a href='".$this->table_defs['projects']['paths']['original'].$temp[$i]['pict']."' target='_blank'>
                <img src='".$this->table_defs['projects']['paths']['size1'].$temp[$i]['pict']."' border='0'></a>";
        }
        echo "</td><td class='text' bgcolor='$bgcolor'>".$temp[$i][$this->getFieldName("project_descr")]."</td></tr>";
      }
      
      if(!empty($more)){
        echo "<tr><td valign='top' class='project_col_desc' bgcolor='$bgcolor'>".$_SESSION['project_more'][$_SESSION['lang']]."</td><td class='text' bgcolor='$bgcolor'>".$more."</td></tr>";
      }
      echo "
      <tr>
        <td valign='top' class='project_col_desc' bgcolor='$bgcolor'></td>
        <td class='text' align='right' bgcolor='$bgcolor'><a href='#kopf'><img src='".$this->iconsDir."up.gif' border='0'></a></td>
      </tr>
      <tr><td bgcolor='#ffffff' height='5' colspan='2'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr>
      <tr><td bgcolor='#999999' height='1' colspan='2'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr>
      <tr><td bgcolor='#ffffff' height='5' colspan='2'><img src='".$this->iconsDir."leer.gif' width='1' height='1'></td></tr>";
    }
    echo "</table>";
 } 
    
}
?>
