<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	$username = trim($_POST["username"]);
	
	if (!$username)
		stderr("Fel", "Du måste ange ett användarnamn");
	
	$res = mysql_query("SELECT * FROM bonusshop WHERE page = 'mygive10gb2.php'") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if ($CURUSER["seedbonus"] < $arr["points"])
		stderr("Fel", "Du har inte tillräckligt med poäng");
		
	$user = mysql_query("SELECT id, downloaded FROM users WHERE username LIKE " . sqlesc($username)) or sqlerr(__FILE__, __LINE__);
	$user = mysql_fetch_assoc($user);
	
	if (!$user)
		stderr("Fel", "Användaren finns inte");
		
	if ($user["id"] == $CURUSER["id"])
		stderr("Fel", "Du kan inte köpa bort egen trafik");
		
	if ($user["downloaded"] < 10737418240)
		$arr["value"] = 0;
		
	$log = "Köp av {$arr["log"]} till {$username} -<b>{$arr["points"]}p</b>";
	mysql_query("UPDATE users SET seedbonus = seedbonus - $arr[points] WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '" . get_date_time() . "', " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
	
	$log = "<b>{$CURUSER["username"]}</b> har spenderat <b>{$arr["points"]}p</b> på att ge dig {$arr["log"]}";
	mysql_query("UPDATE users SET $arr[row] = $arr[value] WHERE id = $user[id]") or sqlerr(__FILE__, __LINE__);
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($user[id], '" . get_date_time() . "', " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
	
	header("Location: bonus.php");
}

head("Köp bort trafik");

print("<img src='/pic/bonus.png' /><br /><br />\n");

begin_frame("Köp bort trafik");

print("<form method='post' action='mygive10gb2.php'><b>Användarnamn:</b> <input type='text' name='username' style='width: 200px;' /> <input type='submit' value='Köp' /></form>\n");

print("</div></div>\n");
foot();

?>