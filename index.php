<?php

require_once("globals.php");

dbconn(true);
loggedinorreturn();

if ($_GET["viewnews"])
{
	mysql_query("UPDATE users SET unreadnews = 0 WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
	header("Location: index.php#news");
}

if ($_GET["news"])
{
	$news = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 4, 100") or sqlerr(__FILE__, __LINE__);

	while ($new = mysql_fetch_assoc($news))
	{
		$posts = get_row_count("posts", "WHERE topicid = $new[topicid]") - 1;

		print("<div class='news' id='news$new[id]'>\n");
		print("<h2 id='nh$new[id]'>$new[subject]\n");
	
		if (get_user_class() >= UC_SYSOP)
			print("<span class='newsedit'><a class='jlink' onClick='editNews($new[id])'><img src='/pic/edit.png' /></a> <a class='jlink' onClick='delNews($new[id])'><img src='/pic/delete.png' /></a></span>");
		
		print("</h2>\n");
		
		print("<div id='nb$new[id]'>" . format_comment($new["body"]) . "</div>");
		print("<span class='newsfoot'>" . elapsed_time($new["added"]) . "</span>");
	
		print("<span class='newsfoot' style='float: right;'><a href='/forums.php/viewtopic/$new[topicid]/'>Diskutera ($posts)</a></span>\n");
		
		print("</div>\n");
	}
	die;
}

if ($_POST)
{
	if ($_GET["vote"])
	{
		$pollid = 0 + $_POST["pollid"];
		$vote = 0 + $_POST["pollanswer"];
		
		$poll = mysql_query("SELECT polls.id, polls.topicid, COUNT(pollanswers.id) AS votes FROM polls LEFT JOIN pollanswers ON polls.id = pollanswers.pollid WHERE polls.id = " . sqlesc($pollid)) or sqlerr(__FILE__, __LINE__);
		$poll = mysql_fetch_assoc($poll);
		
		if (!isset($_POST["pollanswer"]))
			jErr("Du måste välja ett alternativ");
		
		if (!$poll["id"])
			jErr("Alternativet finns inte");
			
		$voted = mysql_query("SELECT id FROM pollanswers WHERE pollid = $poll[id] AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
		
		if (mysql_num_rows($voted))
			jErr("Du har redan röstat på denna omröstning");
			
		mysql_query("INSERT INTO pollanswers (pollid, userid, voteid) VALUES($poll[id], $CURUSER[id], " . sqlesc($vote) . ")") or sqlerr(__FILE__, __LINE__);
		
		$poll["votes"] += 1;
		
		$alts = mysql_query("SELECT pollalternatives.id, pollalternatives.name, COUNT(pollanswers.id) AS votes FROM pollalternatives LEFT JOIN pollanswers ON pollalternatives.id = pollanswers.voteid WHERE pollalternatives.pollid = $poll[id] GROUP BY pollalternatives.id
							UNION
							SELECT pollalternatives.id, pollalternatives.name, COUNT(pollanswers.id) AS votes FROM pollalternatives RIGHT JOIN pollanswers ON pollalternatives.id = pollanswers.voteid WHERE pollanswers.pollid = $poll[id] AND pollalternatives.id IS NULL GROUP BY pollanswers.voteid
							ORDER BY votes DESC") or sqlerr(__FILE__, __LINE__);
		
		$i = 0;
		while ($alt = mysql_fetch_assoc($alts))
		{
			$p = round($alt["votes"] / $poll["votes"] * 100);
			
			if (!$alt["name"])
				$alt["name"] = "Vet ej";
			
			$return["results"] .= "<div class='pollanswer'" . ($i++ % 2 ? " style='background-color: #ededed;'" : "") . ">" . ($vote == $alt["id"] ? "<b>$alt[name]</b>" : "$alt[name]") . "<br /><img src='/pic/bar_left.gif' /><div class='pollres' id='r$altid' alt=$p></div><img src='/pic/bar_right.gif' /> $p %</div>";
		}
				
		$return["results"] .= "<p class='small' style='text-align: center; line-height: 1.5;'>$poll[votes] röster<br /><a href='forums.php/viewtopic/$poll[topicid]'>Gå till diskussionstråd &rsaquo;</a></p>";
		
		print(json_encode($return));
		die;
	}
}

head();

print("<div id='imdb'></div>\n");
print("<table><tr class='clear'><td colspan=2 style='padding: 0px 5px;'>\n");

Toppen();

print("<p style='text-align: center;'><a href='toppen.php'>Anpassa topplistor</a></p>\n");

print("<h2 style='margin: 10px 0px;' id='news'>Nyheter</h2>\n");

print("<div class='frame' id='newss'>\n");

if (get_user_class() >= UC_SYSOP)
{
	print("<form method='post' action='news.php?add=1' id='newsform'><input type='text' class='newssubject' name='subject' id='newssubject' value='Skapa nyhet...' />\n");
	print("<textarea class='newsbody' id='newsbody' name='body'></textarea><br /><input type='submit' id='addnews' value='Skapa' style='display: none;' /><span class='errormess' id='newserr' style='margin-left: 10px;'></span></form>\n");
}

$news = mysql_query("SELECT * FROM news ORDER BY id DESC LIMIT 4") or sqlerr(__FILE__, __LINE__);

while ($new = mysql_fetch_assoc($news))
{
	$posts = get_row_count("posts", "WHERE topicid = $new[topicid]") - 1;

	print("<div class='news' id='news$new[id]'>\n");
	print("<h3 id='nh$new[id]'>$new[subject]\n");
	
	if (get_user_class() >= UC_SYSOP)
		print("<span class='newsedit'><a class='jlink' onClick='editNews($new[id])'><img src='/pic/edit.png' /></a> <a class='jlink' onClick='delNews($new[id])'><img src='/pic/delete.png' /></a></span>");
		
	print("</h3>\n");
		
	print("<div id='nb$new[id]'>" . format_comment($new["body"]) . "</div>");
	print("<span class='newsfoot'>" . elapsed_time($new["added"]) . "</span>");
	
	print("<span class='newsfoot' style='float: right;'><a href='/forums.php/viewtopic/$new[topicid]/'>Diskutera ($posts)</a></span>\n");
		
	print("</div>\n");
}

print("<div id='oldnews'><p style='text-align: center;'><a class='jlink' id='shownews'>Visa alla</a></p></div>\n");

print("</div></td><td style='padding-top: 0px; vertical-align: top;'>\n");

print("<div class='frame'>\n");

$poll = mysql_query("SELECT * FROM polls ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
$poll = mysql_fetch_assoc($poll);

if ($poll)
{
	print("<h2><span class='bar' style='margin-right: 5px;'>&nbsp;</span>" . format_comment($poll["name"]) . "</h2>\n");

	$curans = mysql_query("SELECT voteid FROM pollanswers WHERE pollid = $poll[id] AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	$curans = mysql_fetch_assoc($curans);
}

if ($curans)
{
	$tot = mysql_query("SELECT COUNT(*) AS votes FROM pollanswers WHERE pollid = $poll[id]") or sqlerr(__FILE__, __LINE__);
	$tot = mysql_fetch_assoc($tot);
	
	$alts = mysql_query("SELECT pollalternatives.id, pollalternatives.name, COUNT(pollanswers.id) AS votes FROM pollalternatives LEFT JOIN pollanswers ON pollalternatives.id = pollanswers.voteid WHERE pollalternatives.pollid = $poll[id] GROUP BY pollalternatives.id
						UNION
						SELECT pollalternatives.id, pollalternatives.name, COUNT(pollanswers.id) AS votes FROM pollalternatives RIGHT JOIN pollanswers ON pollalternatives.id = pollanswers.voteid WHERE pollanswers.pollid = $poll[id] AND pollalternatives.id IS NULL GROUP BY pollanswers.voteid
						ORDER BY votes DESC") or sqlerr(__FILE__, __LINE__);
	
	$i = 0;
	while ($alt = mysql_fetch_assoc($alts))
	{
		$p = round($alt["votes"] / $tot["votes"] * 100);
		
		if (!$alt["name"])
			$alt["name"] = "Vet ej";
			
		print("<div style='margin: 5px 0px;" . ($i++ % 2 ? " background-color: #ededed;" : "") . "'>" . ((0 + $curans["voteid"]) == $alt["id"] ? "<b>$alt[name]</b>" : "$alt[name]") . "<br /><img src='/pic/bar_left.gif' style='vertical-align: baseline;' /><div class='pollres' style='width: " . ($p * 3) . "px;'></div><img src='/pic/bar_right.gif' style='vertical-align: baseline;' /> $p %</div>\n");
	}
		
	print("<p class='small' style='text-align: center; line-height: 1.5;'>$tot[votes] röster<br /><a href='forums.php/viewtopic/$poll[topicid]'>Gå till diskussionstråd &rsaquo;</a></p>\n");
}
elseif ($poll)
{
	print("<div id='poll'><form method='post' action='?vote=1' id='voteform'><input type='hidden' name='pollid' value=$poll[id] />\n");

	$alts = mysql_query("SELECT * FROM pollalternatives WHERE pollid = $poll[id]") or sqlerr(__FILE__, __LINE__);

	while ($alt = mysql_fetch_assoc($alts))
		print("<div" . ($i++ % 2 ? " style='background-color: #ededed;'" : "") . "><input type='radio' name='pollanswer' value=$alt[id] /> $alt[name]</div>\n");
	
	print("<br /><div" . ($i++ % 2 ? " style='background-color: #ededed;'" : "") . "><input type='radio' name='pollanswer' value=0 /> Vet ej (visa resultaten)</div>\n");
	print("<br /><input type='submit' value='Rösta' id='votesubmit' /><span class='errormess' id='pollerr' style='margin-left: 10px;'></span>\n");

	print("</form></div>\n");
}
else
	print("<i>Ingen omröstning</i>\n");

if (get_user_class() >= UC_SYSOP)
	print("<p align='center'><a href='polls.php'>Ny</a> - <a href='polls.php?edit=$poll[id]'>Ändra</a> - <a class='jlink' onClick='delPoll($poll[id])'>Radera</a></p>\n");
	
print("</div>\n");

$posts = mysql_query("SELECT MAX(posts.id) AS postid, posts.userid, MAX(posts.added) AS added, topics.id AS topicid, topics.name AS topicname, topics.forumid, overforums.name AS forumname FROM posts LEFT JOIN topics ON posts.topicid = topics.id LEFT JOIN overforums ON topics.forumid = overforums.id WHERE overforums.minclassread <= $CURUSER[class] GROUP BY topics.id ORDER BY added DESC LIMIT 7") or sqlerr(__FILE__, __LINE__);

print("<div class='frame' style='margin-top: 10px;'><h3>Senaste forumaktivitet</h3><table class='clear graymark' style='width: 100%;'>\n");

$i = 0;
while ($post = mysql_fetch_assoc($posts))
	print("<tr><td><a href='forums.php/viewtopic/$post[topicid]/last/?p$post[postid]'>" . htmlspecialchars(cutStr($post["topicname"], 31)) . "</a><br /><span class='sar'>&nbsp;</span><a href='forums.php/viewforum/$post[forumid]/'><span class='small'><i>$post[forumname]</i></span></a></td><td style='text-align: right; border-width: 0px 1px " . ($i == mysql_num_rows($posts) ? "1" : "0") . "px 0px;'>" . get_elapsed_time($post["added"]) . " sedan</td></tr>\n");
	
print("</table></div>\n");

print("<div class='frame' style='margin-top: 10px;'><h3>Aktuella bets</h3><table class='clear graymark' style='width: 100%;'>\n");

$bets = mysql_query("SELECT id, name, descr, ends FROM betting WHERE ends > '" . get_date_time() . "' AND endedby = 0 ORDER BY ends ASC LIMIT 7") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($bet = mysql_fetch_assoc($bets))
	print("<tr><td><a href='bet.php#b$bet[id]'>$bet[name]</a><br /><span class='sar'>&nbsp;</span><a href='bet.php?descr=" . urlencode($bet["descr"]) . "'><span class='small'><i>$bet[descr]</i></span></a></td><td style='text-align: right; border-width: 0px 1px " . ($i == mysql_num_rows($bets) ? "1" : "0") . "px 0px;'>" . mkprettytime(strtotime($bet["ends"]) - time()) . " kvar</td></tr>\n");

print("</table></div>\n");

if (get_user_class() >= UC_SYSOP)
{	
	$stats["members"] = get_row_count("users");

	$time = get_date_time(strtotime("-3 minutes"));
	$stats["online"] = get_row_count("users", "WHERE last_access > '$time'");

	$time = get_date_time(strtotime("-15 minutes"));
	$stats["active15"] = get_row_count("users", "WHERE last_access > '$time'");

	$stats["active24"] = get_row_count("users", "WHERE DATE(last_access) = CURDATE()");

	$stats["posts"] = get_row_count("posts");
	$stats["posts24"] = get_row_count("posts", "WHERE DATE(added) = CURDATE()");
	
	$stats["leechers"] = get_row_count("peers", "WHERE `left` > 0");
	$stats["seeders"] = get_row_count("peers", "WHERE `left` = 0");
	$stats["peers"] = $stats["leechers"] + $stats["seeders"];

	print("<div class='frame' style='margin-top: 10px;'><h3>Statistik</h3><table class='graymark' style='width: 100%;'>\n");
	print("<tr><td class='form'>Registrerade användare</td><td>" . number_format($stats["members"]) . "</td></tr>\n");
	print("<tr><td class='form'>Inloggade användare</td><td>" . number_format($stats["online"]) . "</td></tr>\n");
	print("<tr><td class='form'>Online senaste 15 min</td><td>" . number_format($stats["active15"]) . "</td></tr>\n");
	print("<tr><td class='form'>Online senaste 24 h</td><td>" . number_format($stats["active24"]) . "</td></tr>\n");
	print("<tr><td class='form'>Foruminlägg</td><td>" . number_format($stats["posts"]) . "</td></tr>\n");
	print("<tr><td class='form'>Foruminlägg idag</td><td>" . number_format($stats["posts24"]) . "</td></tr>\n");
	print("<tr><td class='form'>Leechare</td><td>" . number_format($stats["leechers"]) . "</td></tr>\n");
	print("<tr><td class='form'>Seedare</td><td>" . number_format($stats["seeders"]) . "</td></tr>\n");
	print("<tr><td class='form'>Peers</td><td>" . number_format($stats["peers"]) . "</td></tr>\n");

	print("</table></div>\n");
}
print("</td></tr></table>");

print("<p>");
?>
<!-- Start WEBSTAT kod -->
<script type="text/javascript" src="http://stats.webstat.se/assets/detectplugins_source.js"></script>
<script type="text/javascript" src="http://stats.webstat.se/assets/stat_isp2.php"></script> 
<script type="text/javascript">
<!--
var info="&plugins=" + (detectFlash()?"flash|":"") + (detectDirector()?"shockwave|":"") + (detectQuickTime()?"quicktime|":"") + (detectReal()?"realplayer|":"") + (detectWindowsMedia()?"windowsmedia|":"");
document.write("<" + "script src=\"http://stats.webstat.se/statCounter.asp?id=29211&size=" + screen.width + "x" + screen.height + "&depth=" + screen.colorDepth + "&referer=" + escape(document.referrer) + info + "&isp=" + info2+ "\"></" + "script>"); 
-->
</script>
<!-- Slut WEBSTAT kod -->
<?php
print("</p>\n");

foot();

?>