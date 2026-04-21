<?php
$zeilen=$db->db_abfrage_nicht("limmat_news", "id, titel, text, datum, datum_von, datum_bis, pos_startseite, bild, mehr", "pos_startseite", "pos_startseite", "0");
$num = count($zeilen);

echo "<table width='100%'  border='0' cellspacing='6' cellpadding='0'><tr><td class='text' width='296' valign='top'>";
$stand = "0";

for($i=0;$i<=$num;$i++){
  $zeilen[$i][2] = addslashes($zeilen[$i][2]);
}

for($i=0;$i<=$num;$i++){
	if($zeilen[$i][6] == "1"){
  	echo "<table width='100%'  border='0' cellspacing='0' cellpadding='0' style='padding:5px;'><tr><td class='text'><strong>".nl2br($zeilen[$i][1])."</strong></td></tr>";
  
		if(strlen($zeilen[$i][2]) >= '500'){
  		$zeilen[$i][2] = substr($zeilen[$i][2], 0, 500)." ...";
  		echo "<tr><td class='text'>".nl2br($zeilen[$i][2])."</td></tr><tr><td class='text' align='right'><a href='index.php?hmenu=wwt&menu=wwt4&projektnews=".$zeilen[$i][0]."'>[+]</a></td></tr></tr></table>";
    }else{
  		echo "<tr><td class='text'>".nl2br($zeilen[$i][2])."</td></tr></tr></table>";
		}
  	$stand++;

		echo "<table width='100%'  border='0' cellspacing='0' cellpadding='0' style='padding:5px;'><tr><td class='text' height='20'></td></tr><tr><td class='text'><strong>".nl2br($zeilen[$i+1][1])."</strong></td></tr>";
  		
		if(strlen($zeilen[$i][2]) >= '500'){
  		$zeilen[$i][2] = substr($zeilen[$i+1][2], 0, 500)." ...";
  		echo "<tr><td class='text'>".nl2br($zeilen[$i][2])."</td></tr><tr><td class='text' align='right'><a href='index.php?hmenu=wwt&menu=wwt4&projektnews=".$zeilen[$i+1][0]."'>[+]</a></td></tr></tr></table>";
    }else{
  		echo "<tr><td class='text'>".nl2br($zeilen[$i+1][2])."</td></tr></tr></table>";
		}	
		$stand++;
		
	}else if($zeilen[$i][6] == "3"){
		if($stand >="2"){
		  echo "</td><td class='text' width='' valign='top'>";
		}
  	echo "<table width='100%'  border='0' cellspacing='0' cellpadding='0' style='padding:5px;'><tr><td class='text'><strong>".nl2br($zeilen[$i][1])."<br><br></strong></td></tr>";
  	
		if(!empty($zeilen[$i][7])){
  		$img = @imagecreatefromjpeg("../bilder/news/".$zeilen[$i][7]);
  		$imghoehe = imagesy($img);
  		$imgbreite= imagesx($img);
  		$dimension = $imghoehe*$imgbreite;
  		
			if($imghoehe > '150'){
  			$imgheight = '150';
  			$imgwidth = $imgbreite/($imghoehe/$imgheight);
			}else{
  			$imgheight = $imghoehe;
  			$imgwidth = $imgbreite;
			}
		  echo "<tr><td class='text' align='center'><img src='../bilder/news/".$zeilen[$i][7]."' width='".$imgwidth."' height='".$imgheight."'></td></tr>";
		}
		if(strlen($zeilen[$i][2]) >= '500'){
  		$zeilen[$i][2] = substr($zeilen[$i][2], 0, 500)." ...";
  		
  		echo "<tr><td class='text'>".nl2br($zeilen[$i][2])."</td></tr><tr><td class='text' align='right'><a href='index.php?hmenu=wwt&menu=wwt4&projektnews=".$zeilen[$i][0]."'>[+]</a></td></tr></tr></table>";
    }else{
  		echo "<tr><td class='text'>".nl2br($zeilen[$i][2])."</td></tr></tr></table>";
		}	
    $stand++;
	}
}//end for

echo "</td><td class='text'></td></tr></table>";
?>