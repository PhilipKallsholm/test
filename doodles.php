<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Doodlar");

begin_frame("Doodlar");

$res = mysql_query("SELECT * FROM doodles ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
	print("<div style='background-color: #353535; border: 5px solid black; text-align: center;'><img src='/getdoodle.php/{$arr[name]}' /></div><p class='small' style='text-align: center;'><i>" . date("j F, Y", strtotime($arr["added"])) . " - $arr[text]</i></p>\n");
print("</div></div>");

foot();

?>