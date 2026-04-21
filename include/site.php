<?php
 
class site {
var $db_handler;
var $suchspalte;
var $suchwert;
var $projektnews;
var $ftpuser;

function site() {
	$this->db_handler = new db_handler();
	$this->ftpuser = "comput1_limmat";
} 
	 
function set_var($var_name, $wert) {
	$this->$var_name = $wert;
}
 
function anzeige($zeilen, $spalten, $art){
		$spalten = explode(', ',$spalten);
		$anz_spalten = count($spalten);
		$num = count($zeilen);
		
		if($art == "Projekte"){
  		$spalten_lokal_partner = "LN, name, link";
      $lp = $this->db_handler->db_abfrage_projekte2("SELECT ".$spalten_lokal_partner." FROM limmat_lokale_partner", $spalten_lokal_partner);
      $anz= count($lp);
      
      for($i=0;$i < $anz;$i++){
        $lokale_partner = array ($lp[$i]["LN"] => $lp[$i]["name"]);
        echo $lp[$i]["LN"];
      }
      
  		echo "<table width='100%' border='0' cellspacing='1' cellpadding='0'>
    		<tr><td class='texttitle'>Projekte</td></tr>
    		<tr><td bgcolor='#999999' height='1' class='text'><img src='/bilder/leer.gif' width='1' height='1'></td></tr>
    		<tr><td>";
  		
  		if( !isset($_GET['drucken'])){
  		  $this->projekte_suche_auswahl();
  		}
  		
  		echo "</td></tr><tr><td bgcolor='#999999' height='1' class='text'><img src='/bilder/leer.gif' width='1' height='1'></td></tr></table>";
		
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>";
			$heute = date("Y-m-d");
			
      for ($i=0; $i<$num;$i++){
  			if($i%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
  			//laufzeit formatieren
  			$monate=array("Januar","Februar","M&auml;rz","April","Mai","Juni","Juli","August", "September","Oktober","November","Dezember");
  			$datum_zerlegt = explode("-", $zeilen[$i][7]); 
  			$monat = $monate[$datum_zerlegt[1]-1];
  			$jahr = $datum_zerlegt[0];
  			$laufzeit = $monat." ".$jahr." - ";
  			$datum_zerlegt = explode("-", $zeilen[$i][8]); 
  			$monat = $monate[$datum_zerlegt[1]-1];
  			$jahr = $datum_zerlegt[0];
  			$laufzeit .= $monat." ".$jahr;
		    
  			//mehr zusammenstellen 
  			$mehr = "";
  			//(News betreffend Projekt vorhanden?)
  		    $sql = "select id from `limmat_news` where mehr like '%".$zeilen[$i][16]."%'";

  			$res = $this->mysqli_db_query($this->ftpuser, $sql);
  			$anz_news = mysqli_num_rows($res);
  			$news = array();
  			
        for($k=0; $k<$anz_news;$k++){	
  			 $news[$k] = $this->mysqli_result($res, $k, 'id');
  			}
			
  			if($anz_news>0){
    			for($j=0;$j<$anz_news;$j++){
            $mehr .= "<BR><a href=index.php?hmenu=wwt&menu=wwt4&projektnews=".$news[$j]."#".$news[$j].">News zu diesem Thema</a>";
    			}
  			}
			
        if($zeilen[$i][15] != ""){
    			$mehrpack = explode(";",$zeilen[$i][15]);
    			$anzmehr = count($mehrpack);	
    							
    			for($j=0;$j<$anzmehr;$j++){
    			 $mehr .= "<BR><a href='http://".$mehrpack[$j]."' target='_blank'>".$mehrpack[$j]."</a>";
    			}
  			}//fertig--->mehr zusammenstellen
			
        echo "<tr><td width='150' valign='top' class='spaltenbez' bgcolor='$bgcolor'>Land :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][0]."</td></tr>";
        
        if($zeilen[$i][1] != "" || $zeilen[$i][1] != 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Ort :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][1]."</td></tr>";
        }
        
        if($zeilen[$i][2] != "" || $zeilen[$i][2] != 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Zustiftung :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][2]."</td></tr>";
        }
        
        if($zeilen[$i][3] != "" || $zeilen[$i][3] != 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Projektname :</td><td class='text' bgcolor='$bgcolor'><strong>".$zeilen[$i][3]."</strong></td></tr>";
        }
        
        if($zeilen[$i][4] != ""){
          $lp_name = $this->db_handler->db_abfrage_projekte2("SELECT ".$spalten_lokal_partner." FROM limmat_lokale_partner WHERE LN='".$zeilen[$i][4]."'", $spalten_lokal_partner);
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Lokaler Partner :</td><td class='text' bgcolor='$bgcolor'>".$lp_name[0][1]."</td></tr>";
        }
        
        if($zeilen[$i][5] != "" || $zeilen[$i][5] != 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Projektart :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][5]."</td></tr>";
        }
        
        if($laufzeit != "" || $laufzeit != 0){
          echo "
          <tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Projekt Laufzeit :</td><td class='text' bgcolor='$bgcolor'>".$laufzeit."</td></tr>
          <tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Projektstatus :</td><td class='text' bgcolor='$bgcolor'>"; 
          if ($heute < $zeilen[$i][7] || $zeilen[$i][7] == '0000-00-00'){
            $status = "In Vorbereitung";
          }
          if (($heute >= $zeilen[$i][7] && $heute <= $zeilen[$i][8] && $zeilen[$i][7] != '0000-00-00') || ($zeilen[$i][8] == "0000-00-00" && $zeilen[$i][7] != '0000-00-00')){
            $status = "laufend";
          }
          if ($heute > $zeilen[$i][8] && $zeilen[$i][8] != "0000-00-00"){
            $status = "beendet";
          }
          echo "$status</td></tr>";
        }
        echo "<tr><td bgcolor='$bgcolor' height='5' colspan='2'><img src='/bilder/leer.gif' width='1' height='1'></td></tr>";
        
        if($zeilen[$i][9] > 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>J&auml;hrlich Beg&uuml;nstigte :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][9]."</td></tr>";
        }
        
        if($zeilen[$i][10] > 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Total Projekt :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][17]." ".number_format($zeilen[$i][10],0,".","'")."</td></tr>";
        }
        
        if($zeilen[$i][11] > 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Beitrag Limmat :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][17]." ".number_format($zeilen[$i][11],0,".","'")."</td></tr>";
        }
        
        if($zeilen[$i][12] > 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Beitrag Lokaler Partner :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][17]." ".number_format($zeilen[$i][12],0,".","'")."</td></tr>";
        }
        if($zeilen[$i][13] > 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Beitrag ODA :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][17]." ".number_format($zeilen[$i][13],0,".","'")."</td></tr>";
        }
        
        if($zeilen[$i][21] > 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Beitrag Andere :</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][17]." ".number_format($zeilen[$i][21],0,".","'")."</td></tr>";
        }
        
        echo "<tr><td bgcolor='$bgcolor' height='5' colspan='2'><img src='/bilder/leer.gif' width='1' height='1'></td></tr>";
        
        if($zeilen[$i][14] != "" || $zeilen[$i][14] != 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>Projektbeschreibung :";
        	if($zeilen[$i][20] <> ""){
            echo "<br /><br /><a href='../bilder/projektbilder/".$zeilen[$i][20]."' target='_blank'><img src='../bilder/projektbilder/klein/".$zeilen[$i][20]."' border='0'></a>";
        	}
        	echo "</td><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][14]."</td></tr>";
        }
        
        if($mehr != "" || $mehr != 0){
          echo "<tr><td valign='top' class='spaltenbez' bgcolor='$bgcolor'>mehr :</td><td class='text' bgcolor='$bgcolor'>".$mehr."</td></tr>";
        }
        echo "
        <tr>
          <td valign='top' align='right' class='spaltenbez' bgcolor='$bgcolor'></td>
          <td class='text' align='right' bgcolor='$bgcolor'><a href='#kopf'><img src='../bilder/up.gif' border='0'></a></td>
        </tr>
        <tr><td bgcolor='#ffffff' height='5' colspan='2'><img src='/bilder/leer.gif' width='1' height='1'></td></tr>
        <tr><td bgcolor='#999999' height='1' colspan='2'><img src='/bilder/leer.gif' width='1' height='1'></td></tr>
        <tr><td bgcolor='#ffffff' height='5' colspan='2'><img src='/bilder/leer.gif' width='1' height='1'></td></tr>";
      }
      echo "</table>";
    }
		
		if($art == "news" || $art == "news_einzel"){
      echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>";
      
      for ($i=0; $i<$num;$i++){
        $mehr = "";
        if($zeilen[$i][6] == $this->projektnews){$bgcolor = "#CCCCCC";}else if($i%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
        
        //mehr zusammenstellen
        if(!empty($zeilen[$i][5])){
          $mehrpack = explode(";",$zeilen[$i][5]);
          $anzmehr = count($mehrpack);	
          
          for($j=0;$j<$anzmehr;$j++){
            if(preg_match("=^[0-9]+$=i",$mehrpack[$j])){
              if($mehr==""){
                $mehr = "<a href='index.php?hmenu=projekte&menu=alle_projekte&suche=search&suchspalte=Projektnummer&swert=".$mehrpack[$j]."'>Projekt zu diesem Thema</a>";
              }else{
                $mehr .= "<BR><a href='index.php?hmenu=projekte&menu=alle_projekte&suche=search&suchspalte=Projektnummer&swert=".$mehrpack[$j]."'>Projekt zu diesem Thema</a>";
              }
            }else{
              if($mehr==""){
                $mehr = "<a href='http://".$mehrpack[$j]."' target='_blank'>".$mehrpack[$j]."</a>";
              }else{
                $mehr .= "<BR><a href='http://".$mehrpack[$j]."' target='_blank'>".$mehrpack[$j]."</a>";
              }
            }
          }
        }
        
        //fertig mehr zusammenstellen
        if( $zeilen[$i][3] < date("Y-m-d") && ($zeilen[$i][4] == "0000-00-00" || $zeilen[$i][4] >= date("Y-m-d") ) ){
          echo "<tr> <td width='150' rowspan='4' valign='top' bgcolor='$bgcolor' class='spaltenbez'><a name=".$zeilen[$i][6]."></a>";
          
          if(!empty($zeilen[$i][7])){
            if(!empty($zeilen[$i][8])){
              echo "<table width='100' border='0' cellspacing='0' cellpadding='5'><tr><td>
              <a href='../bilder/news/".$zeilen[$i][8]."' target='_blank'><img src='../bilder/news/".$zeilen[$i][7]."' border='0' width='210'></a></td></tr></table>";
            }else{
              echo "<table width='100' border='0' cellspacing='0' cellpadding='5'><tr><td>
              <a href='../bilder/news/original/".$zeilen[$i][7]."' target='_blank'><img src='../bilder/news/".$zeilen[$i][7]."' border='0' width='210'></a></td></tr></table>";
            }
          }
          
          echo "
            </td><td class='text' bgcolor='$bgcolor'><strong>".$zeilen[$i][0]."</strong></td></tr>
            <tr><td bgcolor='$bgcolor' height='10'>&nbsp;</td></tr>
            <tr><td class='text' bgcolor='$bgcolor'>".$zeilen[$i][1]."</td></tr>
            <tr><td bgcolor='$bgcolor' height='10'>&nbsp;</td></tr>
            <tr><td valign='top' align='right' class='spaltenbez' bgcolor='$bgcolor'>Datum : </td>
            <td class='text' bgcolor='$bgcolor'>".$this->datumformatieren($zeilen[$i][2])."</td></tr>";
          
          if($mehr != ""){
            echo "<tr><td valign='top' align='right' class='spaltenbez' bgcolor='$bgcolor'>mehr :</td><td bgcolor='$bgcolor' class='text'>".$mehr."</td></tr>";
          }
          
          echo "
            <tr> 
            <td valign='top' align='right' class='spaltenbez' bgcolor='$bgcolor'></td>
            <td class='text' align='right' bgcolor='$bgcolor'><a href='#kopf'><img src='../bilder/up.gif' border='0'></a></td>
            </tr>";
            
          if($art == "news_einzel"){
            echo '<tr><td  class="text"><a href="index.php?hmenu=wwt&menu=wwt4">Alle News ansehen</a></td></tr>';
          }
          
          echo "
            <tr><td bgcolor='#ffffff' height='5' colspan='2'><img src='../bilder/leer.gif' width='1' height='1'></td></tr>
            <tr><td bgcolor='#999999' height='1' colspan='2'><img src='../bilder/leer.gif' width='1' height='1'></td></tr>
            <tr><td bgcolor='#ffffff' height='5' colspan='2'><img src='../bilder/leer.gif' width='1' height='1'></td></tr>";
        }
      }
      echo "</table>";
    }//news
		
		
  //Limmat_filelinks
	if($art == "filelinks" || $art == "Startzeiten_Punkte" || $art == "jahresberichte" || $art == "programme"  || $art == "medienpraesenz" || $art == "publikationen" || $art == "sozialunternehmen" || $art == "sozialimpakt messen" || $art == "ausbildung" || $art == "entwicklungszusammenarbeit" || $art == "Im Dienste der Geldgeber" || $art == "solidaritaet" || $art == "lokale_partner_texte" || $art == "benefizkonzert_texte"){
		echo "<table width='100%'>";
		if($art == "Startzeiten_Punkte" ){
      echo "<tr><td colspan='5'><p class='texttitle'><br>Startzeiten und Resultate</p></td></tr>";
		}

		$c=0; 
		for ($i=0; $i<$num;$i++){
			if($zeilen[$i][3] == $art && $zeilen[$i][6] == '1'){
        echo "<tr><td valign='top' bgcolor='$bgcolor' class='filelinks'><table width='100%'><tr><td bgcolor='$bgcolor' class='texttitle' colspan='3'>".$zeilen[$i][5]."</td></tr><tr><td bgcolor='$bgcolor' class='text' colspan='3'>".$zeilen[$i][0]."</td></tr></table></td>";	
			}
		}

		for ($i=0; $i<$num;$i++){
		  if($c%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
			if($zeilen[$i][3] == $art && $zeilen[$i][6] != '1'){
				if($titel_gesetzt != true && $art != "Startzeiten_Punkte"){
  				$titel = "Artikel / Publikationen zu diesem Thema";
  				if($art == "jahresberichte"){$titel = "Jahresberichte";}
  				echo "<tr><td valign='top' bgcolor='$bgcolor' class='filelinks'><table width='100%'><tr><td bgcolor='$bgcolor' class='text' colspan='3'><b>$titel</b></td></tr></table></td>";	
  				$titel_gesetzt = true;
				} 
				
        $c++;
				if($zeilen[$i][1] == ""){
				$endung = strrchr ($zeilen[$i][2], ".");
					if($endung == ".pdf"){
            $icon ="pdf.gif";$dateigroesse = round(filesize("../filelinks/".$zeilen[$i][2])/1024000, 2)." MB";
					}else{$icon ="html.gif";$dateigroesse = "";}
				  echo "<tr><td valign='top' bgcolor='$bgcolor' class='filelinks'><table width='100%'><tr><td width='17'><img src='../bilder/".$icon."'>&nbsp;</td><td width='85%' bgcolor='$bgcolor' class='filelinks'><a href='../filelinks/".$zeilen[$i][2]."' target='_blank' class='filelinks'>".$zeilen[$i][5]."</a></td><td bgcolor='$bgcolor' class='filelinks' width='50'>".$dateigroesse."</td></tr><tr><td bgcolor='$bgcolor' class='text' colspan='3'>".$zeilen[$i][0]."</td></tr></table></td>";	
				}else{
				  echo "<tr><td valign='top' bgcolor='$bgcolor' class='filelinks'><table width='100%'><tr><td width='17'><img src='../bilder/html.gif'>&nbsp;</td><td width='85%' bgcolor='$bgcolor' class='filelinks'><a href='../".$zeilen[$i][1]."' target='_blank' class='filelinks'>".$zeilen[$i][5]."</a></td><td bgcolor='$bgcolor' class='filelinks' width='50'></td></tr><tr><td bgcolor='$bgcolor' class='text' colspan='3'>".$zeilen[$i][0]."</td></tr></table></td>";	
				}
			}
		}
		echo "</table>";
	}
	
  //#Limmat_filelinks
  //links (lokale partner)
	if($art == "links"){
  	echo "<table width='100%'>";
  	for ($i=0; $i<$num;$i++){
      if($i%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
			if($zeilen[$i][1] == ""){ 
        echo "<tr><td bgcolor='$bgcolor' class='text'>".$zeilen[$i][0]."</td></tr>";	
			}else {
        echo "<tr><td bgcolor='$bgcolor' class='text'><a href='".$zeilen[$i][1]."' target='_blank' class='filelinks'>".$zeilen[$i][0]."</a></td></tr>";	
			}
		}
		echo "</table>";
	}
		
	if($art == "lokale_partner"){
		echo "<table width='100%'>";
		for ($i=0; $i<$num;$i++){
  		if($i%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
  		$vorhanden = $this->db_handler->db_abfrage_projekte2("SELECT * FROM limmat_projekte WHERE Lokalpartnernummer=".$zeilen[$i][2], "Country, ort, zustiftung, Projektname, Lokalpartnernummer, projektart, projektstatus, projektbeginn, projektende, jaerlich_beguenstigte, TotalProjekt, Beitrag_Limmat, Beitrag_oertliche_partner, Beitrag_oda, Projektbeschreibung, mehr, Projektnummer, waehrung, projektbeginn, projektende");
			if($zeilen[$i][1] == ""){
        echo "<tr><td bgcolor='$bgcolor' class='text'>".$zeilen[$i][0]."</td>";
				if($vorhanden[0][0] <> ""){
				  echo "<td bgcolor='$bgcolor' width='132'><a href='index.php?hmenu=projekte&menu=alle_projekte&suche=search&suchspalte=Lokalpartnernummer&swert=".$zeilen[$i][2]."' class='filelinks'>Projekte</a></td>";
				}else{
				  echo "<td bgcolor='$bgcolor' width='132'></td>";
				}
        echo "</tr>";	
			}else {
			echo "<tr><td bgcolor='$bgcolor' class='text'><a href='".$zeilen[$i][1]."' target='_blank' class='filelinks'>".$zeilen[$i][0]." &nbsp;<img src='../bilder/web.gif' border='0'></a></td>";
				if($vorhanden[0][0] <> ""){
				echo "<td bgcolor='$bgcolor' width='132'><a href='index.php?hmenu=projekte&menu=alle_projekte&suche=search&suchspalte=Lokalpartnernummer&swert=".$zeilen[$i][2]."' class='filelinks'>Projekte</a></td>";
				}else{
				echo "<td bgcolor='$bgcolor' width='132'></td>";
				}
        echo "</tr>";
			}
		}
		echo "</table>";
	}
  //#links		
	if($art == "Rules and Fees" || $art == "Supported Projects" || $art == "Prizes" || $art == "Sponsors" || $art == "shirt"){
		echo "<table>";
		for ($i=0; $i<$num;$i++){
			if($zeilen[$i][2] == $art){
  			echo "<tr><td bgcolor='$bgcolor' class='texttitle'>".$zeilen[$i][0]."</td></tr><tr><td bgcolor='$bgcolor' class='text'>".$zeilen[$i][1]."<br></td></tr>";	
			}
		}
		echo "</table>";
	}
	
	if($art == "faq"){
		echo "<table width='100%'>";
		for ($i=0; $i<$num;$i++){
		  if($i%2==0){$bgcolor = "#ffffff";}else{$bgcolor = "#efefef";}
			echo "<tr><td bgcolor='$bgcolor' class='filelinks'><a name=$zeilen[$i][0]></a><strong><a href='index.php?hmenu=wws&menu=wws4&faq=".$zeilen[$i][0]."' class='filelinks'>".$zeilen[$i][1]."</a></strong></td></tr>";
			if($zeilen[$i][0] == $_GET['faq']){
  			 echo "<tr><td bgcolor='$bgcolor' class='text'>".$zeilen[$i][2]."<br></td></tr>";
			}
		}
		echo "</table>";
	}

  if($art == "Mainsponsors"){
		echo "<table border='0' width='100%'><tr><td width='50%' class='texttitle' colspan='3'>Sponsors</td></tr><tr><td height='1' bgcolor='#D4D4D4' colspan='3'></td></tr><tr><td height='5' bgcolor='#ffffff' colspan='3'></td></tr>";			
		
    for ($i=0; $i<$num;$i++){
		  $anzt = 0;
			for ($t=0; $t<3;$t++){
				if($t==0){
				  echo "<tr>";
				}
				$anzt++;
				echo "<td align='center'><table width='100'><tr><td class='text'>";			
				
        if($zeilen[$i+$t][2] != ""){
					if($zeilen[$i+$t][3] != ""){
					  echo "<a href='".$zeilen[$i+$t][3]."' class='menu' target='_blank'><img src='../bilder/sponsoren/".$zeilen[$i+$t][2]."' border='0'></a>";
					}else{
					  echo "<img src='../bilder/sponsoren/".$zeilen[$i+$t][2]."' border='0'>";
					}
				}
				
				echo "</td><tr><td valign='bottom' align='right' class='menu'>".$zeilen[$i+$t][0]."</td></tr><tr><td height='25' valign='bottom' align='right' class='menu'></td></tr></table></td>";	
        
				if($anzt==3){
  				echo "</tr>";
  				$i=$i+$anzt-1;
				}
			}
		}
		echo "</table>";
	}
	
	if($art == "Sponsors_2"){
		echo "<table border='0' width='100%'><tr><td width='50%' class='texttitle' colspan='3'>Co-Sponsors</td></tr><tr><td height='1' bgcolor='#D4D4D4' colspan='3'></td></tr><tr><td height='5' bgcolor='#ffffff' colspan='3'></td></tr>";			
		
    for ($i=0; $i<$num;$i++){
		  $anzt = 0;
			for ($t=0; $t<3;$t++){
				if($t==0){
				  echo "<tr>";
				}
				$anzt++;
				echo "<td align='center'><table width='100'><tr><td class='text'>";			
				
        if($zeilen[$i+$t][2] != ""){
					if($zeilen[$i+$t][3] != ""){
				    echo "<a href='".$zeilen[$i+$t][3]."' class='menu' target='_blank'><img src='../bilder/sponsoren/".$zeilen[$i+$t][2]."' border='0'></a>";
					}else{
            echo "<img src='../bilder/sponsoren/".$zeilen[$i+$t][2]."' border='0'>";
					}
				}
        echo "</td><tr><td valign='bottom' align='right' class='menu'>".$zeilen[$i+$t][0]."</td></tr><tr><td height='25' valign='bottom' align='right' class='menu'></td></tr></table>";

				echo "</td>";	
				if($anzt==3){
  				echo "</tr>";
  				$i=$i+$anzt-1;
				}
			}
		}
    echo "</table>";
  }
				
		//"es_Spiele"
		if($art == "es_Spiele"){
			//überschriften
			echo "<table border='0' width='100%'><tr><td colspan='".$anz_spalten."'class='texttitle'>Place and Dates</td></tr><tr><td colspan='".$anz_spalten."' class='texttitle'>Qualifications</td></tr><tr><tr><td bgcolor='$bgcolor' class='text'><strong>where</strong></td><td bgcolor='$bgcolor' class='text'><strong>when</strong></td><td bgcolor='$bgcolor' class='text'><strong>Result</strong></td></tr>";

			for ($i=0; $i<$num;$i++){//Mach Zeilenhintergrund abwechselnd
				if($i%2 == 0){	
				  $bgcolor = '#efefef';
				}else{
				  $bgcolor = '#ffffff';
				}
				echo "<tr>";
				for ($a=0; $a<$anz_spalten;$a++){
					if($zeilen[$i][3] == 0){
						if($a == 1){
						  echo "<td bgcolor='$bgcolor' class='text'>".$this->datumformatieren($zeilen[$i][$a])."</td>";
						}else if($a == 2 && $zeilen[$i][$a] != ""){
  						echo "<td bgcolor='$bgcolor' class='text'><a href='../esmeralda/results/".$zeilen[$i][$a]."' target='_blank'><img src='../bilder/html.gif' border='0'></a>";
							if($zeilen[$i][4] != ""){
                echo "<a href='../esmeralda/results/".$zeilen[$i][4]."' target='_blank'><img src='../bilder/html.gif' border='0'></a>";
							}
  						echo "</td>";
						}else if($a == 3 || $a == 4){
						}else{
              echo "<td bgcolor='$bgcolor' class='text'>".$zeilen[$i][$a]."</td>";
						}
					}else{
            echo "<td></td>";
					}
				}
				echo "</tr>";
			}
	
			echo "<tr><td colspan='".$anz_spalten."' class='texttitle'>Final</td></tr><tr><td bgcolor='$bgcolor' class='text'><strong>where</strong></td><td bgcolor='$bgcolor' class='text'><strong>when</strong></td><td bgcolor='$bgcolor' class='text'><strong>Result</strong></td></tr><tr>";
			$j=1;
			
			for ($i=0; $i<$num;$i++){//Mach Zeilenhintergrund abwechselnd
				if($j%2 == 0){	
				  $bgcolor = '#efefef';
				}else{
				  $bgcolor = '#ffffff';
				}
				
				for ($a=0; $a<$anz_spalten;$a++){
					if($zeilen[$i][3] == 1){
					$j++;
						if($a == 1){
						  echo "<td bgcolor='$bgcolor' class='text'>".$this->datumformatieren($zeilen[$i][$a])."</td>";
						}else if($a == 2 && $zeilen[$i][$a] != ""){
  						echo "<td bgcolor='$bgcolor' class='text'><a href='../esmeralda/results/".$zeilen[$i][$a]."' target='_blank'><img src='../bilder/html.gif' border='0'></a>";
							if($zeilen[$i][4] != ""){
                echo "<a href='../esmeralda/results/".$zeilen[$i][4]."' target='_blank'><img src='../bilder/html.gif' border='0'></a>";
							}
  						echo "</td>";
						}else if($a == 3 || $a == 4){
						}else{
						  echo "<td bgcolor='$bgcolor' class='text'>".$zeilen[$i][$a]."</td>";
						}
					}
				}
			}
      echo "</tr></table>";
    }
  }

  function suche($hmenu, $menu, $spalten, $anz_spalten){
    $spalten = explode(', ',$spalten);
    $anz_spalten = count($spalten);
    
    echo "<form name='suche' method='get'>";
    
    echo"
        <input type='hidden' name='hmenu' value='".$hmenu."'>
        <input type='hidden' name='menu' value='".$menu."'>
        <input type='hidden' name='suche' value='search'>
        <input type='hidden' name='suchspalte' value='Country'>
        
        Oder Suche nach Stichwort : &nbsp;&nbsp;<input type='text' name='swert' value='".$this->suchwert."' size='52'>
        <input type='submit' name='submit' value='Suchen'>
      </form>
      </p>";
  }
  
  function projekte_suche_auswahl(){
    $value = $this->suchwert;
    print_r($value);
    echo "<div id='suche' style='z-index:1; border: 0px none #000000;'>";
    
    $sql = "SELECT Country FROM limmat_projekte WHERE aktiv=1 GROUP BY Country ORDER BY Country";
    $res = $this->mysqli_db_query($this->ftpuser, $sql);
    $num = mysqli_num_rows($res);
    $zeilen = array();
    
    for ($i=0; $i<$num;$i++){	
      for ($j=0; $j<$num;$j++){
        $zeilen[$j] = $this->mysqli_result($res, $j, 'Country');
      }
    }
    
    $heute = date("Y");
    $num_datum = "6";
    $zeilen_datum = array();
    
    for ($j=0; $j<$num_datum;$j++){	
      $zeilen_datum[$j] = $heute-$j;//."-01-01"
    }	
    
    $sql_land = "SELECT zustiftung FROM limmat_projekte WHERE aktiv=1 group by zustiftung ORDER BY zustiftung";
    $res_land = $this->mysqli_db_query($this->ftpuser, $sql_land);
    $num_land = mysqli_num_rows($res_land);
    $zeilen_land = array();
    
    for ($k=0; $k<$num_land;$k++){	
      for ($x=0; $x<$num_land;$x++){
        $zeilen_land[$x] = $this->mysqli_result($res_land, $x, 'zustiftung');
      } 
    }	
    
    echo "<table><tr><td height='10' valign='top'>
      <form name='alle_anzeigen' method='post' action='index.php?hmenu=projekte&menu=alle_projekte'>
      <input type='submit' name='alle_anzeigen' value='Alle anzeigen'>
      </form></td>";
      
    echo "<td valign='top'>
    <form name='form1' method='post' action='index.php?hmenu=projekte&menu=alle_projekte&suche=auswahlsuche'>
    <select name='land'>
    <option value=''>Alle Länder</option>"; 
    
    for($i=0;$i<$num;$i++){
      echo "<option value='".$zeilen[$i]."' ".($zeilen[$i] == $_POST['land'] && $zeilen[$i] != ""?"selected":"").">".$zeilen[$i]."</option>\n";
    }
    
    echo "</select>
    </td>
    <td valign='top'>
    <select name='zustiftung'>
    <option value='' selected>Alle Zustiftungen</option>";
    
    for($i=0;$i<$num_land;$i++){
      echo "<option value='".$zeilen_land[$i]."' ".($zeilen_land[$i] == $_POST['zustiftung'] && $zeilen_land[$i] != ""?"selected":"").">".$zeilen_land[$i]."</option>\n";
    }
    
    echo "</select></td>
    <td valign='top'>
    <select name='datum'>
    <option value='' selected>Alle Jahre</option>";
    
    for($j=0;$j<$num_datum;$j++){
      $jahr = $heute - $j;
      echo "<option value='".$zeilen_datum[$j]."' ".($zeilen_datum[$j] == $_POST['datum'] && $zeilen_datum[$j] != ""?"selected":"").">".$jahr."</option>\n";
    }
    
    echo "        
                    </select>
                  </td>
                  <td valign='top'>
                <input type='submit' name='auswahlsuche' value='suchen'>
              </form>
            </p>
          </td>
        </tr>
      </table>";
    
    echo "
      <table>
        <tr>
          <td class='text' height='10'>";
    
    $this->suche('projekte', 'alle_projekte', 'Projektname, Projektbeschreibung', $anz_spalten);
    
    echo "
          </td>
        </tr>
      </table>
    </div>";
  }




  function datumformatieren($datum){
    $datum_formatiert = date("D, d.m.Y",strtotime($datum));
    return $datum_formatiert;
  }
}

?>