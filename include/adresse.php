<?php
$zeilen=$db->db_abfrage("limmat_allg", "id, adresse, email", "id", false, false);
echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td height='4' colspan='2'><img src='../bilder/leer.gif' width='1' height='1'></td></tr><tr><td>".$zeilen[0][1]."</td><td align='right'><a href='../kontaktform/index.php' target='_blank'><img src='../bilder/index/adresse.gif' border='0'></a></td><tr></table>";
//$image = imagecreate(300,150); <a href='mailto:'".$zeilen[0][2]."' class='email'>".$zeilen[0][2]."</a>
//$farbe_body=imagecolorallocate($image,243,243,243); 
//$farbe_b = imagecolorallocate($image,10,36,106);
//imagestring ($image, 5,30, 70, "PHP3/4 - Die Befehlsreferenz", $farbe_b);
//header("Content-Type: image/gif");
//imagegif($image); 

//header("Content-type: image/jpeg");
//$bild = imagecreatefromjpeg("../bilder/index/adresse.jpg");
//$black = imagecolorallocate($bild, 0, 0, 0);
//imagestring($bild, 2, 10, 10, $text, $black);
//imagejpeg($bild, "../bilder/index/adresse.jpg", 100);
//imagedestroy($bild);

?>
