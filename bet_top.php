<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("StakeBet");

print("<img src='/pic/stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px; color: gray;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px;'>Lägg till spel</a>" : "") . "</h3>\n");

print("<h1>Topplistor</h1>\n");

switch ($_GET["list"])
{
	case 'w':
		$list = "w";
		$res = mysql_query("SELECT id, username, bets, bet_wincount, bet_win - bet_stakes AS winloose FROM users WHERE bet_win - bet_stakes > 0 ORDER BY bet_win - bet_stakes DESC, bets DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
		break;
	case 'l':
		$list = "l";
		$res = mysql_query("SELECT id, username, bets, bet_wincount, bet_win - bet_stakes AS winloose FROM users WHERE bet_win - bet_stakes < 0 ORDER BY bet_win - bet_stakes ASC, bets DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
		break;
	case 'r':
		$list = "r";
		$min_betcount = 20;
		$res = mysql_query("SELECT id, username, bets, bet_wincount, bet_win - bet_stakes AS winloose, bet_wincount / bets AS betratio FROM users WHERE bets >= $min_betcount ORDER BY betratio DESC, bets DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
		break;
	case 'b':
		$list = "b";
		$res = mysql_query("SELECT * FROM betting WHERE endedby != 0 ORDER BY bets DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
		break;
	case 's':
		$list = "s";
		$res = mysql_query("SELECT * FROM betting WHERE endedby != 0 ORDER BY stakes DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
		break;
	default:
		$list = "w";
		$res = mysql_query("SELECT id, username, bets, bet_wincount, bet_win - bet_stakes AS winloose FROM users WHERE bet_win - bet_stakes > 0 ORDER BY bet_win - bet_stakes DESC, bets DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
}

print("<h3><a href='bet_top.php?list=w'" . ($list == 'w' ? " style='color: gray;'" : "") . ">Vinnare</a> - <a href='bet_top.php?list=l'" . ($list == 'l' ? " style='color: gray;'" : "") . ">Förlorare</a> - <a href='bet_top.php?list=r'" . ($list == 'r' ? " style='color: gray;'" : "") . ">Vinstratio</a> - <a href='bet_top.php?list=b'" . ($list == 'b' ? " style='color: gray;'" : "") . ">Kuponger</a> - <a href='bet_top.php?list=s'" . ($list == 's' ? " style='color: gray;'" : "") . ">Omsättning</a></h3>\n");

if ($list == 'w' || $list == 'l' || $list == 'r')
{
	if ($list == 'r')
		print("<i>(Endast användare med minst 20 bets)</i><br />");
		
	print("<table><tr><td class='colhead'>Pos</td><td class='colhead'>Användare</td><td class='colhead'>Poäng +/-</td><td class='colhead'>Vinstratio</td></tr>\n");
	
	$i = 0;
	while ($arr = mysql_fetch_assoc($res))
	{
		if ($arr["bet_wincount"] > 0)
		{
			$ratio = $arr["bet_wincount"] / $arr["bets"];
			$ratio = number_format($ratio * 100, 1);
		}
		else
			$ratio = 0;

		print("<tr><td style='text-align: center;'>#" . (++$i) . "</td><td><a href=userdetails.php?id=$arr[id]>$arr[username]</a></td><td style='text-align: right; font-weight: bold;'>" . number_format($arr["winloose"]) . " p</td><td style='text-align: right;'>$ratio %</td></tr>\n");
	}

	print("</table>");
}
else
{
	print("<table><tr><td class='colhead'>Pos</td><td colspan=2 class='colhead'>Spel</td><td class='colhead'>Kuponger</td><td class='colhead'>Omsättning</td></tr>\n");
	
	$i = 0;
	while ($arr = mysql_fetch_assoc($res))
		print("<tr><td style='text-align: center;'>#" . (++$i) . "</td><td><a href='forums.php/viewtopic/$arr[topicid]'>$arr[name]</a> (" . date("Y", strtotime($arr["ends"])) . ")</td><td><i>$arr[descr]</i></td><td style='text-align: right;'>" . number_format($arr["bets"]) . " st</td><td style='text-align: right; font-weight: bold;'>" . number_format($arr["stakes"]) . " p</td></tr>\n");
		
	print("</table>\n");
}

foot();

?>