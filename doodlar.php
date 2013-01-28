<?
ob_start("ob_gzhandler");
require "include/bittorrent.php";
dbconn();
loggedinorreturn();
stdhead("Doodlar");
begin_main_frame();

$res = mysql_query("SELECT * FROM logos ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);

print("<table class=bottom><tr><td align=center class=clear>");

while ($arr = mysql_fetch_assoc($res)) {

print("<br /><br /><table cellspacing=0 cellpadding=0 style='background-color: #353535;'><tr><td align=center style='border: solid #000000 5px;'><img src=getlogo.php/{$arr[picname]}.png /></td></tr></table><font size=1><i>" . date("j F, Y", sql_timestamp_to_unix_timestamp($arr["added"])) . " - " . $arr[text] . "</i></font>");

}

print("</td></tr></table></td></tr></table>");

stdfoot();

?>
