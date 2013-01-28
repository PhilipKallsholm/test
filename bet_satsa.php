<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_POWER_USER)
	stderr("Fel", "Du måste vara lägst Power User för att kunna använda bettingsystemet");

$id = 0 + $_GET["id"];
$dt = get_date_time();

$alt = mysql_query("SELECT * FROM bettingalts WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$alt = mysql_fetch_assoc($alt);

if (!$alt)
	stderr("Fel", "Spelet hittades inte");

$bet = mysql_query("SELECT * FROM betting WHERE id = $alt[betid]") or sqlerr(__FILE__, __LINE__);
$bet = mysql_fetch_assoc($bet);

if ($bet["ends"] < $dt)
	stderr("Fel", "Spelet har redan börjat.");

if ($_POST)
{
	$staked = mysql_query("SELECT id FROM bets WHERE userid = $CURUSER[id] AND betid = $bet[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($staked))
		stderr("Fel", "Du har redan satsat på spelet");
	
	$stake = round($_POST["stake"]);

	if ($stake < 1)
		stderr("Fel", "Du måste satsa något");
		
	if ($stake > $CURUSER["seedbonus"])
		stderr("Fel", "Du har inte tillräckligt med poäng");
		
  	mysql_query("INSERT INTO bets (betid, altid, userid, bet) VALUES($bet[id], $alt[id], $CURUSER[id], $stake)") or sqlerr(__FILE__, __LINE__);

	$bonuscomment = "Köp av kupong till <b>$bet[name]</b> ($alt[alt]) -<b>" . number_format($stake) . "p</b>";
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '$dt', " . sqlesc($bonuscomment) . ")") or sqlerr(__FILE__, __LINE__);

	mysql_query("UPDATE users SET seedbonus = seedbonus - $stake WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

  	header("Location: bet_kup.php");
}

head("Satsa");

print("<img src='/pic/stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px;'>Lägg till spel</a>" : "") . "</h3>\n");

$bets = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE betid = $alt[betid]") or sqlerr(__FILE__, __LINE__);
$bets = mysql_fetch_assoc($bets);

$curbet = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE betid = $alt[betid] AND altid = $alt[id]") or sqlerr(__FILE__, __LINE__);
$curbet = mysql_fetch_assoc($curbet);

$odds = ($bets["total"] + 1.5) / ($curbet["total"] + 1);

print("<br />");
print("<form method='post' action='bet_satsa.php?id=$id'><table>\n");
print("<tr><td class='colhead'>Namn</td><td class='colhead'>Mitt spel</td><td class='colhead'>Odds</td><td class='colhead'>Satsning</td><td class='colhead'></td></tr>\n");
print("<tr><td>$bet[name]</td><td style='text-align: center;'>$alt[alt]</td><td style='text-align: center;'>" . number_format($odds, 2) . "</td><td><input type='text' name='stake' size=3 maxlength=10 /></td><td><input type='submit' value='Genomför' /></td></tr>\n");
print("</table></form>\n");

foot();

?>