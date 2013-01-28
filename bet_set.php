<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (!staff() && $CURUSER["betadmin"] == 'no')
	stderr("Fel", "Tillgång nekad.");

if ($_POST)
{
	$altid = 0 + $_POST["altid"];	
	
	$alt = mysql_query("SELECT bettingalts.*, COUNT(bets.id) AS total, SUM(bets.bet) AS bet FROM bettingalts LEFT JOIN bets ON bettingalts.id = bets.altid WHERE bettingalts.id = " . sqlesc($altid)) or sqlerr(__FILE__, __LINE__);
	$alt = mysql_fetch_assoc($alt);
	
	if (!$alt["betid"])
		stderr("Fel", "Felaktigt alternativ");

	$bet = mysql_query("SELECT betting.*, COUNT(bets.id) AS total, COALESCE(SUM(bets.bet), 0) AS bet FROM betting LEFT JOIN bets ON betting.id = bets.betid WHERE betting.id = $alt[betid]") or sqlerr(__FILE__, __LINE__);
	$bet = mysql_fetch_assoc($bet);

	$odds = ($bet["total"] + 1.5) / ($alt["total"] + 1);
	$rewards = round($odds * $alt["bet"]);
	$forumid = 4;
	$dt = get_date_time();
        
	mysql_query("INSERT INTO topics (name, forumid) VALUES (" . sqlesc($bet["name"]) . ", $forumid)") or sqlerr(__FILE__, __LINE__);
	$topicid = mysql_insert_id();
        
	$res = mysql_query("SELECT altid, userid, bet FROM bets WHERE betid = $alt[betid]") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		if ($alt["id"] == $arr["altid"])
		{
			$reward = round($arr["bet"] * $odds);
			
			$user = mysql_query("SELECT bet_record FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

			$subject = "Resultat: $bet[name]";
			$body = "Grattis! Ditt bet på spelet [i]{$bet["name"]}[/i] gav [b]" . number_format($odds, 2) . "[/b] gånger [b]" . number_format($arr["bet"]) . "[/b] poäng, alltså [b][u]" . number_format($reward) . "[/u][/b] poäng!\n\n[url=forums.php/viewtopic/$topicid][u]Länk till forumtråden[/u][/url]";

			$bonuscomment = "Du har inkasserat <b>" . number_format($reward) . "p</b> (<b>$bet[name]</b> gav <b>" . number_format($odds, 2) . "</b> gånger <b>" . number_format($arr["bet"]) . "</b> poäng)";
			mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($arr[userid], '$dt', " . sqlesc($bonuscomment) . ")") or sqlerr(__FILE__, __LINE__);

			mysql_query("UPDATE users SET seedbonus = seedbonus + $reward, bets = bets + 1, bet_stakes = bet_stakes + $arr[bet], bet_win = bet_win + $reward, bet_wincount = bet_wincount + 1" . ($user["bet_record"] < $reward ? ", bet_record = $reward" : "") . " WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($arr["userid"], $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
		}
		else
		{
			$subject = "Resultat: $bet[name]";
			$body = "Tyvärr gav inte ditt bet på spelet [i]{$bet["name"]}[/i] någon utbetalning denna gång.\n\n[url=forums.php/viewtopic/$topicid][u]Länk till forumtråden[/u][/url]";

			mysql_query("UPDATE users SET bets = bets + 1, bet_stakes = bet_stakes + $arr[bet] WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($arr["userid"], $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
		}
	}

	$body = "[b]{$bet["name"]}[/b] - [i]{$bet["descr"]}[/i]\n\nAntal satsade kuponger på spelet: [b]{$bet["total"]} st[/b]\nBonuspoäng i omsättning på spelet: [b]" . number_format($bet["bet"]) . "p[/b]\nVinnande alternativ: [b]{$alt["alt"]}[/b]\nSpelet avslutades av: [b]{$CURUSER["username"]}[/b]\n\n[b]Val och odds:[/b]";

	$res = mysql_query("SELECT * FROM bettingalts WHERE betid = $alt[betid] ORDER BY `order` ASC") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		$curalt = mysql_query("SELECT COUNT(*) AS total FROM bets WHERE altid = $arr[id]") or sqlerr(__FILE__, __LINE__);
		$curalt = mysql_fetch_assoc($curalt);

		$curodds = ($bet["total"] + 1.5) / ($curalt["total"] + 1);

		$body .= "\n[*] $arr[alt] x[b]" . number_format($curodds, 2) . "[/b]";
	}

	$body .= "\n\n[b]Topp 20 vinnare:[/b]\n";

	$res = mysql_query("SELECT userid, bet FROM bets WHERE altid = $alt[id] ORDER BY bet DESC LIMIT 20") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
		
		if ($user = mysql_fetch_assoc($user))
			$username = $user["username"];
		else
			$username = "[i]Borttagen[/i]";
		
		$reward = round($odds * $arr["bet"]);

		$body .= "[b]+" . number_format($reward) . "p[/b] till [url=/userdetails.php?id={$arr["userid"]}][u]{$username}[/u][/url] som satsade " . number_format($arr["bet"]) . "p\n";
	}

	$body .= "\n[b]Topp 20 förlorare:[/b]\n";

	$res = mysql_query("SELECT userid, bet FROM bets WHERE betid = $alt[betid] AND altid != $alt[id] ORDER BY bet DESC LIMIT 20") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

		if ($user = mysql_fetch_assoc($user))
			$username = $user["username"];
		else
			$username = "[i]Borttagen[/i]";

		$body .= "[url=/userdetails.php?id={$arr["userid"]}][u]{$username}[/u][/url] [b]-" . number_format($arr["bet"]) . "p[/b]\n";
	}

	mysql_query("INSERT INTO posts (topicid, added, body, body_orig) VALUES (" . implode(", ", array_map("sqlesc", array($topicid, $dt, $body, $body))) . ")") or sqlerr(__FILE__, __LINE__);
	$postid = mysql_insert_id();

	mysql_query("UPDATE topics SET lastpost = $postid WHERE id = $topicid") or sqlerr(__FILE__, __LINE__);
	mysql_query("UPDATE betting SET endedby = $CURUSER[id], bets = $bet[total], stakes = $bet[bet], winalt = " . sqlesc($alt["alt"]) . ", rewards = $rewards, topicid = $topicid WHERE id = $alt[betid]") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM bets WHERE betid = $alt[betid]") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM bettingalts WHERE betid = $alt[betid]") or sqlerr(__FILE__, __LINE__);
	
	stafflog("$CURUSER[username] avgjorde spelet $bet[name] ($bet[descr])");

	header("Location: forums.php/viewtopic/$topicid");
	die;
}

head();

$id = 0 + $_GET["id"];

$res = mysql_query("SELECT * FROM betting WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr)
	stderr("Fel", "Spelet hittades inte");
	
print("<img src='/pic/stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px;'>Lägg till spel</a>" : "") . "</h3>\n");
	
print("<form method='post' action='bet_set.php'><table style='min-width: 400px;'>\n");
print("<tr><td colspan=2 class='colhead'>$arr[name]" . ($arr["ends"] > get_date_time() ? " <span style='color: red;'>(Spelet fortfarande aktivt!)</span>" : "") . "<br /><i>$arr[descr]</i></td></tr>\n");

$res = mysql_query("SELECT * FROM bettingalts WHERE betid = " . sqlesc($id) . " ORDER BY `order` ASC") or sqlerr(__FILE__, __LINE__);

$alt = "<option value=0>(Välj resultat)</option>\n";

while ($arr = mysql_fetch_assoc($res))
	$alt .= "<option value=$arr[id]>$arr[alt]</option>\n";

print("<tr><td><select name='altid' style='margin-right: 20px;'>\n$alt\n</select></td><td style='text-align: right;'><input type='submit' value='Avgör' /></td></tr>\n");
print("</table></form>\n");

foot();

?>