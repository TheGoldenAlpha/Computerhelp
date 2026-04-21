<?php
if(isset($menu)){
$zeilen=$db->db_abfrage_frei("SELECT text FROM limmat_spruch WHERE seite='".$menu."'", "1");
}else if(!isset($hmenu)){
$zeilen=$db->db_abfrage_frei("SELECT text FROM limmat_spruch WHERE seite='home'", "1");
}else{
$zeilen=$db->db_abfrage_frei("SELECT text FROM limmat_spruch WHERE seite='".$hmenu."'", "1");
}
echo $zeilen[0][0];

?>
