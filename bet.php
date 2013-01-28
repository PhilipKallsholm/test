<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

head("StakeBet");

print("<img src='/pic/stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px; color: gray;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px;'>Lägg till spel</a>" : "") . "</h3>\n");

$res = mysql_query("SELECT descr FROM betting WHERE ends > '" . get_date_time() . "' AND endedby = 0 GROUP BY descr ORDER BY descr ASC") or sqlerr(__FILE__, __LINE__);

$series[] = "Alla";

while ($arr = mysql_fetch_assoc($res))
	$series[] = $arr["descr"];

foreach ($series AS $serie)
	print("<a style='margin: 0px 5px;" . ($serie == urldecode($_GET["descr"]) || !$_GET["descr"] && $serie == 'Alla' ? " color: gray;" : "") . "' href='?descr=" . urlencode($serie) . "'>$serie</a>");

$serie = urldecode($_GET["descr"]);

$res = mysql_query("SELECT * FROM betting WHERE endedby = 0" . (get_user_class() < UC_MODERATOR && $CURUSER["betadmin"] == 'no' ? " AND ends > '" . get_date_time() . "'" : "") . ($serie && $serie != 'Alla' ? " AND descr = " . sqlesc($serie) : "") . " ORDER BY ends ASC");

if (!mysql_num_rows($res))
	print("<br /><br /><i>För tillfället är alla bets stängda</i>\n");

$days1 = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
$days2 = array("Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag");

while ($arr = mysql_fetch_assoc($res))
{
	$now = get_date_time();

	if (date("Y-m-d", strtotime($arr["ends"])) != $temp)
	{
		$day = str_replace($days1, $days2, date("l", strtotime($arr["ends"])));
		print("<div style='width: 400px; margin: 10px auto;'><h2 style='text-align: left; font-style: italic;'>$day</h2></div>\n");
	}
	
	$bet = mysql_query("SELECT altid, bet FROM bets WHERE betid = $arr[id] AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	$bet = mysql_fetch_assoc($bet);

	print("<table style='width: 400px;'>\n");
	print("<tr><td class='colhead' id='b$arr[id]'>$arr[name]" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? " [<a href='addbet.php?id=$arr[id]'>Ändra</a>] [<a href='bet_del.php?id=$arr[id]'>Radera</a>] [<a href='bet_set.php?id=$arr[id]'>Avgör</a>]" : "") . "<br /><i>$arr[descr]</i></td></tr>\n");
	print("<tr><td" . ($arr["ends"] < $now ? " style='background-color: #ffcccc;'" : "") . "><table" . ($arr["ends"] < $now ? " style='background-color: #ffcccc;'" : "") . ">");

	$bets = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE betid = $arr[id]") or sqlerr(__FILE__, __LINE__);
	$bets = mysql_fetch_assoc($bets);
	
	$alts = mysql_query("SELECT * FROM bettingalts WHERE betid = $arr[id] ORDER BY `order` ASC") or sqlerr(__FILE__, __LINE__);

	$i = 0;
	while ($alt = mysql_fetch_assoc($alts))
	{
		$curbets = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE betid = $arr[id] AND altid = $alt[id]") or sqlerr(__FILE__, __LINE__);
		$curbets = mysql_fetch_assoc($curbets);

		$odds = ($bets["total"] + 1.5) / ($curbets["total"] + 1);

		print("<tr class='clear'" . ($bet["altid"] == $alt["id"] ? " style='background-color: #ccffcc;'" : ($i % 2 ? " style='background-color: #ededed;'" : "")) . "><td style='padding: 2px; width: 200px;'>$alt[alt]" . ($bet["bet"] && $bet["altid"] == $alt["id"] ? "<span class='small' style='margin-left: 10px;'><b>$bet[bet]p</b></span>" : "") . "</td><td style='padding: 2px;'>" . ($bet ? "<span style='color: gray; font-weight: bold;'>" . number_format($odds, 2) . "</span>" : "<a href='bet_satsa.php?id=$alt[id]'>" . number_format($odds, 2) . "</a>") . "</td></tr>\n");
		$i++;
	}

	print("</table></td></tr>\n");
	print("<tr><td class='small'>");

	if ($arr["ends"] < $now)
		print("Detta spel stängde för nya odds: <b>$arr[ends]</b>. Tid sedan: <b>" . mkprettytime(time() - strtotime($arr["ends"])) . "</b>");
	else
		print("Detta spel stänger för nya odds: <b>$arr[ends]</b>. Tid kvar: <b>" . mkprettytime(strtotime($arr["ends"]) - time()) . "</b>");
		
	print("</td></tr></table><br />\n");

	$temp = date("Y-m-d", strtotime($arr["ends"]));
}

foot();

?>