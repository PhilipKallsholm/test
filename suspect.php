<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_GET)
{
	$userid = 0 + $_GET["userid"];
	$type = $_GET["type"] == 'snatch' ? "snatch" : "link";
	$table = $_GET["type"] == 'snatch' ? "snatched" : "snatched_links";
	
	$res = mysql_query("SELECT id FROM $table WHERE userid = $userid ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if (!$arr)
		stderr("Fel", "Användaren finns inte registrerad");
		
	$snatchid = $arr["id"];
	
	$res = mysql_query("SELECT id FROM suspected WHERE userid = $userid AND type = '$type'") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if ($arr)
		mysql_query("UPDATE suspected SET snatchid = $snatchid WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
	else
		mysql_query("INSERT INTO suspected (userid, snatchid, type) VALUES($userid, $snatchid, '$type')") or sqlerr(__FILE__, __LINE__);
	
	header("Location: suspect.php");
	die;
}

head("Misstänkta användare");

print("<h1>Misstänkta användare <span style='color: red; font-style: italic;'>beta</span></h1>\n");

print("<div class='frame' style='width: 800px;'>\n");
print("<h1>Skuma leechers</h1>\n");
print("<p>Registrerar användare på <b>ej fria torrents</b> 12 timmar efter påbörjad nedladdning men som <b>aldrig laddat klart</b> och <b>inte längre finns som leecher</b>. Detta kan vid upprepade tillfällen tyda på att användaren raderat stats.swepiracy.org från trackerlistan en bit in i nedladdningen och därmed inte registrerar nedladdad trafik som leecher. <b>Det kan dock också bero på att användaren avslutat nedladdningen, \"cross-seedar\" eller enbart laddat ned en del av materialet.</b></p>\n");
print("<h3 style='text-align: center; color: red;'>Misstänkta egenskaper:</h3>\n");
print("<ul style='padding-left: 20px; font-size: 9pt;'><li>Väldigt låga värden under <b>nedladdat</b> och <b>spenderad tid</b></li><li>Inaktiv användare (User med dåliga stats)</li><li>Kraftigt rödmarkerad (= aktuell peer har blivit borttagen pga timeout, möjligen pga borttagning av stats.swepiracy.org)</li><li>Hög procentsats under \"händelser\" (händelsernas procentuella andel vad gäller nedladdningar av ej fria torrents)</li></ul>\n");
print("<h2 style='text-align: center; color: red;'>Titta efter mönster och <u>kontakta</u> misstänkt användare</h2>\n");

$date = get_date_time(strtotime("-12 hours"));
$res = mysql_query("SELECT users.id, users.username, users.class, users.uploaded, users.downloaded, COUNT(*) AS count, MAX(snatched.id) AS last_snatch FROM snatched INNER JOIN users ON snatched.userid = users.id INNER JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.done = '0000-00-00 00:00:00' AND snatched.added != '0000-00-00 00:00:00' AND snatched.added < '$date' AND snatched.download = 'yes' AND (SELECT id FROM peers WHERE fid = snatched.torrentid AND uid = snatched.userid AND `left` > 0 LIMIT 1) IS NULL AND torrents.freeleech != 'yes' AND torrents.owner != users.id AND torrents.size > snatched.downloaded AND users.class < 7 GROUP BY userid HAVING `count` > 1 ORDER BY count DESC") or sqlerr(__FILE__, __LINE__);

print("<table><tr><td class='colhead'>Användarnamn</td><td class='colhead'>Uppladdat</td><td class='colhead'>Nedladdat</td><td class='colhead'>Ratio</td><td class='colhead'>Klass</td><td class='colhead'>Händelser</td><td class='colhead'>X</td></tr>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$sus = mysql_query("SELECT snatchid FROM suspected WHERE userid = $arr[id] AND type = 'snatch'") or sqlerr(__FILE__, __LINE__);
	
	if ($sus = mysql_fetch_assoc($sus))
		if ($sus["snatchid"] >= $arr["last_snatch"])
			continue;

	if ($arr["downloaded"])
	{
		$ratio = number_format($arr["uploaded"] / $arr["downloaded"], 3);
		$ratio = "<font color=" . get_ratio_color($ratio) . ">$ratio</font>";
	}
	else
		if ($arr["uploaded"] > 0)
			$ratio = "Inf.";
		else
			$ratio = "---";

	$uploaded = mksize($arr["uploaded"]);
	$downloaded =  mksize($arr["downloaded"]);
	
	$snatches = mysql_query("SELECT snatched.id FROM snatched INNER JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.userid = $arr[id] AND snatched.download = 'yes' AND torrents.freeleech != 'yes'") or sqlerr(__FILE__, __LINE__);
	$snatches = mysql_num_rows($snatches);

	print("<tr class='main' style='font-size: 9pt;'><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a>" . usericons($arr["id"]) . "</td><td>$uploaded</td><td>$downloaded</td><td>$ratio</td><td>" . get_user_class_name($arr["class"]) . "</td><td style='text-align: center; color: red;'><b>" . number_format($arr["count"]) . "</b> (" . round($arr["count"] / $snatches * 100) . " %)</td><td><a href='?type=snatch&amp;userid=$arr[id]'>X</a></td></tr>\n");
	print("<tr class='clear'><td colspan=7><table style='margin-bottom: 10px;'><tr><td class='colhead'>Länk</td><td class='colhead'>Nedladdad</td><td class='colhead'>Uppl.</td><td class='colhead'>Nedl.</td><td class='colhead'>Leechtid</td><td class='colhead'>Seedtid</td><td class='colhead'>Totalt spenderad tid</td></tr>");
	
	$links = mysql_query("SELECT snatched.*, torrents.name, torrents.added AS tadded, torrents.size, torrents.freeleech FROM snatched INNER JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.userid = $arr[id] AND snatched.done = '0000-00-00 00:00:00' AND snatched.added != '0000-00-00 00:00:00' AND snatched.added < '$date' AND snatched.download = 'yes' AND (SELECT id FROM peers WHERE fid = snatched.torrentid AND uid = snatched.userid AND `left` > 0 LIMIT 1) IS NULL AND torrents.freeleech != 'yes' AND torrents.owner != snatched.userid AND torrents.size > snatched.downloaded ORDER BY snatched.added DESC") or sqlerr(__FILE__, __LINE__);
	
	while ($link = mysql_fetch_assoc($links))
	{
		$procdown = round(($link["downloaded"] / $link["size"]) * 100, 1);
		
		print("<tr class='small'" . ($link["timedout"] == 'yes' ? " style='background-color: red;'" : ($link["freeleech"] != 'yes' ? " style='background-color: #ffcccc;'" : "")) . "><td><a href='details.php?id=$link[torrentid]' title='$link[name]'>" . cutStr($link["name"], 40) . "</a></td><td style='white-space: nowrap;'>$link[added]<br />(" . get_time(strtotime($link["added"]) - strtotime($link["tadded"])) . " efter uppladdning)</td><td>" . mksize($link["uploaded"]) . "</td><td>" . mksize($link["downloaded"]) . "<br />($procdown %)</td><td>" . mkprettytime($link["leechtime"]) . "</td><td>" . mkprettytime($link["seedtime"]) . "</td><td>" . mkprettytime($link["timespent"]) . "</td></tr>\n");
	}
	
	print("</table></tr>\n");
}

print("</table>\n");
print("</div><br />\n");


print("<div class='frame' style='width: 600px;'>\n");
print("<h1>Skuma länk-klickare</h1>\n");
print("<p>Registrerar användare som klickat på <b>ej fria</b> magnetlänkar men som <b>aldrig registrerats som leecher eller seeder</b>. Detta kan vid upprepade tillfällen tyda på att användaren är snabb med att radera stats.swepiracy.org från trackerlistan och därmed aldrig hinner registreras. <b>Det kan dock också bero på att torrenten är död eller att användaren aldrig startat nedladdningen/uppladdningen.</b></p>\n");
print("<h3 style='text-align: center; color: red;'>Misstänkta egenskaper:</h3>\n");
print("<ul style='padding-left: 20px; font-size: 9pt;'><li>Inaktiv användare (User med dåliga stats)</li><li>Nedladdad kort tid efter uppladdning (torrenten är då inte död)</li><li>Hög procentsats under \"händelser\" (händelsernas procentuella andel vad gäller nedladdningar av ej fria torrents)</li></ul>\n");
print("<h2 style='text-align: center; color: red;'>Titta efter mönster och <u>kontakta</u> misstänkt användare</h2>\n");

$res = mysql_query("SELECT users.id, users.username, users.class, users.uploaded, users.downloaded, COUNT(*) AS count, MAX(snatched_links.id) AS last_snatch FROM snatched_links INNER JOIN users ON snatched_links.userid = users.id INNER JOIN torrents ON snatched_links.torrentid = torrents.id WHERE snatched_links.seeded != 'yes' AND (SELECT id FROM snatched WHERE torrentid = snatched_links.torrentid AND userid = snatched_links.userid) IS NULL AND torrents.owner != users.id AND torrents.freeleech != 'yes' AND users.class < 7 GROUP BY userid HAVING `count` > 1 ORDER BY count DESC") or sqlerr(__FILE__, __LINE__);

print("<table><tr><td class='colhead'>Användarnamn</td><td class='colhead'>Uppladdat</td><td class='colhead'>Nedladdat</td><td class='colhead'>Ratio</td><td class='colhead'>Klass</td><td class='colhead'>Händelser</td><td class='colhead'>X</td></tr>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$sus = mysql_query("SELECT snatchid FROM suspected WHERE userid = $arr[id] AND type = 'link'") or sqlerr(__FILE__, __LINE__);
	
	if ($sus = mysql_fetch_assoc($sus))
		if ($sus["snatchid"] >= $arr["last_snatch"])
			continue;
			
	if ($arr["downloaded"])
	{
		$ratio = number_format($arr["uploaded"] / $arr["downloaded"], 3);
		$ratio = "<font color=" . get_ratio_color($ratio) . ">$ratio</font>";
	}
	else
		if ($arr["uploaded"] > 0)
			$ratio = "Inf.";
		else
			$ratio = "---";

	$uploaded = mksize($arr["uploaded"]);
	$downloaded =  mksize($arr["downloaded"]);
	
	$snatches = mysql_query("SELECT snatched_links.id FROM snatched_links INNER JOIN torrents ON snatched_links.torrentid = torrents.id WHERE snatched_links.userid = $arr[id] AND torrents.freeleech != 'yes'") or sqlerr(__FILE__, __LINE__);
	$snatches = mysql_num_rows($snatches);

	print("<tr class='main' style='font-size: 9pt;'><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a>" . usericons($arr["id"]) . "</td><td>$uploaded</td><td>$downloaded</td><td>$ratio</td><td>" . get_user_class_name($arr["class"]) . "</td><td style='text-align: center; color: red;'><b>" . number_format($arr["count"]) . "</b> (" . round($arr["count"] / $snatches * 100) . " %)</td><td><a href='?type=link&amp;userid=$arr[id]'>X</a></td></tr>\n");
	print("<tr class='clear'><td colspan=6><table style='margin-bottom: 10px;'><tr><td class='colhead'>Länk</td><td class='colhead'>Nedladdad</td></tr>");
	
	$links = mysql_query("SELECT snatched_links.*, torrents.name, torrents.added AS tadded, torrents.freeleech FROM snatched_links INNER JOIN torrents ON snatched_links.torrentid = torrents.id WHERE snatched_links.userid = $arr[id] AND snatched_links.seeded != 'yes' AND (SELECT id FROM snatched WHERE torrentid = snatched_links.torrentid AND userid = snatched_links.userid) IS NULL AND torrents.owner != snatched_links.userid AND torrents.freeleech != 'yes' ORDER BY snatched_links.added DESC") or sqlerr(__FILE__, __LINE__);
	
	while ($link = mysql_fetch_assoc($links))
		print("<tr class='small'" . ($link["freeleech"] != 'yes' ? " style='background-color: #ffcccc;'" : "") . "><td><a href='details.php?id=$link[torrentid]'>" . cutStr($link["name"], 40) . "</a></td><td style='white-space: nowrap;'>$link[added]<br />(" . get_time(strtotime($link["added"]) - strtotime($link["tadded"])) . " efter uppladdning)</td></tr>");
		
	print("</table></tr>\n");
}

print("</table>\n");
print("</div>\n");

foot();
?>