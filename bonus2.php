<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

head("Bonus");

print("<img src='/pic/bonus.png' />\n");

print("<h3><a href='bonus.php'>Logg</a> / <a href='bonus2.php' style='color: gray;'>Shoppen</a></h3>\n");

print("<table style='width: 300px; margin-bottom: 10px; font-size: 11pt;'><tr><td>Antal bonuspoäng</td><td>" . number_format($CURUSER["seedbonus"], 0, ".", " ") . "</td></tr></table>\n");
print("<table style='width: 500px;'><tr><td class='colhead'>Rubrik</td><td class='colhead'>Beskrivning</td><td class='colhead'>Pris</td><td class='colhead' style='text-align: center;'>Köp</td></tr>\n");

$res = mysql_query("SELECT * FROM bonusshop ORDER BY points DESC, name ASC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	$disabled = false;
	if (get_user_class() < UC_POWER_USER || $CURUSER["seedbonus"] < $arr["points"] || $arr["row"] == 'crown' && $CURUSER["crown"] == 'yes')
		$disabled = true;
	
	print("<tr style='background-color: " . ($disabled ? "#ffcccc" : "#ccffcc") . ";'><td style='white-space: nowrap;'><b>$arr[name]</b></td><td>$arr[descr]</td><td><b>$arr[points]p</b></td><td><form method='post' action='takebonus.php'><input type='hidden' name='id' value='$arr[id]' /><input type='submit' value='Köp'" . ($disabled ? " disabled" : "") . " /></form></td></tr>\n");
}

print("</table>\n");

foot();

?>