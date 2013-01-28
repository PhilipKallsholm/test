<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Mina bets");

print("<img src='/pic/stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px; color: gray;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px;'>Lägg till spel</a>" : "") . "</h3>\n");

$res = mysql_query("SELECT bets.*, betting.name, bettingalts.alt FROM bets LEFT JOIN bettingalts ON bets.altid = bettingalts.id LEFT JOIN betting ON bets.betid = betting.id WHERE bets.userid = $CURUSER[id] ORDER BY bets.id DESC") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<i>Du har inga aktiva bets</i>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$bets = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE betid = $arr[betid]") or sqlerr(__FILE__, __LINE__);
	$bets = mysql_fetch_assoc($bets);

	$curbets = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE betid = $arr[betid] AND altid = $arr[altid]") or sqlerr(__FILE__, __LINE__);
	$curbets = mysql_fetch_assoc($curbets);

	$odds = ($bets["total"] + 1.5) / ($curbets["total"] + 1);
	$reward = $odds * $arr["bet"];

	print("<table style='width: 350px;'>\n");
	
	print("<tr><td class='colhead' style='width: 200px;'>Namn</td><td class='colhead' style='width: 30px;'>Odds</td><td class='colhead' style='text-align: right;'>Val</td></tr>\n");
	print("<tr><td>$arr[name]</td><td style='text-align: center;'>" . number_format($odds, 2) . "</td><td style='text-align: right;'>$arr[alt]</td></tr>\n");
	print("<tr><td colspan=3>Utbetalning <b>" . number_format($reward) . "</b>p varav insats <b>" . number_format($arr["bet"]) . "</b>p.</td></tr>\n");
	
	print("</table><br />\n");
}

foot();

?>