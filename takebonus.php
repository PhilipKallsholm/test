<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_POWER_USER)
	stderr("Fel", "Du måste vara lägst Power User för att kunna utnyttja bonussystemet");

$id = 0 + $_POST["id"];

$res = mysql_query("SELECT * FROM bonusshop WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr)
	stderr("Fel", "Alternativet finns inte");
	
$points = $arr["points"];

if ($CURUSER["seedbonus"] < $points)
	stderr("Fel", "Du har inte tillräckligt med poäng");
	
if ($arr["row"] == 'crown' && $CURUSER["crown"] == 'yes')
	stderr("Fel", "Du har redan en krona");
	
if (!$arr["page"] && $arr["row"] == 'downloaded' && $CURUSER["downloaded"] < 10737418240)
	$arr["value"] = 0;
	
if ($arr["page"])
{
	header("Location: $arr[page]");
	die;
}
elseif ($arr["row"])
{
	$log = "Köp av $arr[log]" . ($arr["row"] == 'freeleech' || $arr["row"] == 'doubleupload' ? " från " . get_date_time() . " till " . get_date_time(strtotime("$arr[value]")) : "") . " -<b>{$points}p</b>";
	
	if ($arr["type"] == 'date')
		mysql_query("UPDATE users SET seedbonus = seedbonus - $points, $arr[row] = '" . get_date_time(strtotime("$arr[value]")) . "' WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	else
		mysql_query("UPDATE users SET seedbonus = seedbonus - $points, $arr[row] = $arr[value] WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
		
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '" . get_date_time() . "',  " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
}
else
	stderr("Fel", "Alternativet finns inte");

header("Location: bonus.php");

?>