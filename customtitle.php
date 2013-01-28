<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	$title = trim($_POST["title"]);
	
	if (!$title)
		stderr("Fel", "Du måste ange en titel");

	$res = mysql_query("SELECT points, log FROM bonusshop WHERE row = 'title'") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	$log = "Köp av $arr[log] (" . htmlspecialchars($title) . ") -<b>{$arr["points"]}p</b>";
	
	mysql_query("UPDATE users SET seedbonus = seedbonus - $arr[points], title = " . sqlesc($title) . " WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '" . get_date_time() . "', " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
	
	header("Location: bonus.php");
	die;
}

head("Köp titel");

print("<img src='bonus.png' /><br /><br />\n");

begin_frame("Köp titel");

print("<form method='post' action='customtitle.php'><b>Titel:</b> <input type='hidden' name='art' value='title' /><input type='text' name='title' size=32 maxlength=32 /> <input type='submit' value='Köp' /></form>\n");
print("</div></div>\n");

foot();

?>