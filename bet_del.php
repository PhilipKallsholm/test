<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

$id = 0 + $_GET["id"];

$bets = mysql_query("SELECT bets.*, betting.name, betting.descr FROM bets LEFT JOIN betting ON bets.betid = betting.id WHERE bets.betid = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);

while ($bet = mysql_fetch_assoc($bets))
{
	$bonuscomment = "Återbetalning av kupong till <b>$bet[name]</b> +<b>" . number_format($bet["bet"]) . "p</b>";
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($bet[userid], '" . get_date_time() . "', " . sqlesc($bonuscomment) . ")") or sqlerr(__FILE__, __LINE__);
	
	mysql_query("UPDATE users SET seedbonus = seedbonus + $bet[bet] WHERE id = $bet[userid]") or sqlerr(__FILE__, __LINE__);
}

mysql_query("DELETE FROM bets WHERE betid = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
mysql_query("DELETE FROM betting WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
mysql_query("DELETE FROM bettingalts WHERE betid = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);

stafflog("$CURUSER[username] tog bort spelet $bet[name] ($bet[descr])");

header("Location: bet.php");

?>