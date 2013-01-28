<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_POWER_USER)
	stderr("Fel", "Du måste vara lägst Power User för att kunna se topplistorna");

function usertable($res, $frame_caption)
{
	global $CURUSER, $time_online_start;
	
	begin_frame($frame_caption, 800);

	print("<table class='graymark'>\n");
	print("<tr><td class='colhead'>Rank</td>");
	print("<td class='colhead'>Användare</td>");
	print("<td class='colhead'>Uppladdat</td>");
	print("<td class='colhead'>Snitthastighet</td>");
	print("<td class='colhead'>Nedladdat</td>");
	print("<td class='colhead'>Snitthastighet</td>");
	print("<td class='colhead' style='text-align: right;'>Ratio</td>");
	print("<td class='colhead'>Bonus</td>");
	print("<td class='colhead'>Aktivitet</td>");
	print("<td class='colhead'>Inlägg</td></tr>\n");
	
	$date = "2013-01-08 12:45:00";
	
	$num = 1;
	while ($arr = mysql_fetch_assoc($res))
	{
		$row = mysql_query("SELECT username, added, uploaded, downloaded, hidetraffic, (SELECT SUM(uploaded) FROM snatched WHERE userid = users.id AND added > '$date') / real_peertime AS upspeed, (SELECT SUM(downloaded) FROM snatched WHERE userid = users.id AND added > '$date') / real_leechtime AS downspeed, seedbonus, time_online / (CASE WHEN TIMESTAMPDIFF(HOUR, added, NOW()) >= 24 THEN ((UNIX_TIMESTAMP() - (CASE WHEN UNIX_TIMESTAMP(added) < $time_online_start THEN $time_online_start ELSE UNIX_TIMESTAMP(added) END)) / (3600 * 24)) ELSE 1 END) AS timeonline, (SELECT COUNT(*) FROM posts WHERE userid = users.id) AS posts FROM users WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
		$a = mysql_fetch_assoc($row);
	
		if ($a["downloaded"])
		{
			$ratio = $a["uploaded"] / $a["downloaded"];
			$color = get_ratio_color($ratio);
			$ratio = number_format($ratio, 2);
			
			if ($color)
				$ratio = "<span style='color: $color;'>$ratio</span>";
		}
		else
			$ratio = "Inf.";

		print("<tr class='clear main line'><td style='text-align: center;'>" . $num++ . "</td><td>" . (!staff() && $a["hidetraffic"] == 'yes' ? "<i>Anonym</i>" : "<a href='userdetails.php?id=" . $arr["id"] . "'>$a[username]</a>") . "</td><td style='text-align: right;'>" . mksize($a["uploaded"]) . "</td><td style='text-align: right;'>" . mksize($a["upspeed"]) . "/s</td><td style='text-align: right;'>" . mksize($a["downloaded"]) . "</td><td style='text-align: right;'>" . mksize($a["downspeed"]) . "/s</td><td style='text-align: right;'>$ratio</td><td style='text-align: right;'>" . number_format($a["seedbonus"]) . "</td><td style='text-align: right;'>" . mkprettytime($a["timeonline"]) . "</td><td style='text-align: right;'>" . number_format($a["posts"]) . "</td></tr>\n");
	}
	
	print("</table>\n");
	print("</div></div><br />\n");
}

function _torrenttable($res, $frame_caption)
{
	begin_frame($frame_caption, 700);

	print("<table class='graymark'>\n");
	print("<tr><td class='colhead'>Rank</td>");
	print("<td class='colhead'>Releasenamn</td>");
	print("<td class='colhead' style='text-align: right;'>DL</td>");
	print("<td class='colhead' style='text-align: right;'>Data</td>");
	print("<td class='colhead' style='text-align: right;'>Se.</td>");
	print("<td class='colhead' style='text-align: right;'>Le.</td>");
	print("<td class='colhead' style='text-align: right;'>To.</td>");
	print("<td class='colhead' style='text-align: right;'>Ratio</td></tr>\n");

	$char = 60;
	$num = 1;
	while ($a = mysql_fetch_assoc($res))
	{
		if ($a["leechers"])
		{
			$r = $a["seeders"] / $a["leechers"];
			$ratio = "<span style='color: " . get_ratio_color($r) . ";'>" . number_format($r, 2) . "</span>";
		}
		else
			$ratio = "Inf.";

		print("<tr class='clear main line'><td style='text-align: center;'>" . $num++ . "</td><td><a href='details.php?id=" . $a["id"] . "&amp;hit=1'>" . CutName($a["name"], $char) . "</a></td><td style='text-align: right;'>" . number_format($a["times_completed"]) . "</td><td style='text-align: right;'>" . mksize($a["data"]) . "</td><td style='text-align: right;'>" . number_format($a["seeders"]) . "</td><td style='text-align: right;'>" . number_format($a["leechers"]) . "</td><td style='text-align: right;'>" . number_format($a["leechers"] + $a["seeders"]) . "</td><td style='text-align: right;'>$ratio</td></tr>\n");
	}
	
	print("</table>\n");
	print("</div></div><br />\n");
}

function countriestable($res, $frame_caption, $what)
{
	begin_frame($frame_caption, 600);

	print("<table class='graymark'>\n");
	print("<tr><td class='colhead'>Rank</td>");
	print("<td class='colhead'>Landskap</td>");
	print("<td class='colhead' style='text-align: right;'>Anv.</td></tr>\n");

  	$num = 1;
	while ($a = mysql_fetch_assoc($res))
	{
		if ($what == "Users")
			$value = number_format($a["num"]);
		elseif ($what == "Uploaded")
			$value = mksize($a["ul"]);
		elseif ($what == "Average")
			$value = mksize($a["ul_avg"]);
		elseif ($what == "Ratio")
			$value = number_format($a["r"],2);

		print("<tr class='clear main line'><td style='text-align: center;'>" . $num++ . "</td><td><img src='/pic/countries/$a[pic]' title='$a[name]' style='vertical-align: middle; margin-right: 5px;' /><b>$a[name]</b></td><td style='text-align: right;'>$value</td></tr>\n");
	}
	
	print("</table>\n");
	print("</div></div><br />\n");
}


function peerstable($res, $frame_caption)
{
	begin_frame($frame_caption, 600);

	print("<table class='graymark'>\n");
	print("<tr><td class='colhead'>Rank</td>");
	print("<td class='colhead'>Användarnamn</td>");
	print("<td class='colhead'>Upphastighet</td>");
	print("<td class='colhead'>Nedhastighet</td></tr>\n");

	$num = 1;
	while ($a = mysql_fetch_assoc($res))
		print("<tr class='clear main line'><td style='text-align: center;'>" . $num++ . "</td><td>" . (!staff() && $a["hidetraffic"] == "yes" ? "<i>Anonym</i>" : "<a href='userdetails.php?id=" . $a["userid"] . "'>$a[username]</a>") . "</td><td style='text-align: right;'>" . mksize($a["uprate"]) . "/s</td><td style='text-align: right;'>" . mksize($a["downrate"]) . "/s</td></tr>\n");

	print("</table>");
	print("</div></div><br />\n");
}

function uptimetable($res, $frame_caption)
{
	begin_frame($frame_caption, 600);

	print("<table class='graymark'>\n");
	print("<tr><td class='colhead'>Rank</td>");
	print("<td class='colhead'>Användarnamn</td>");
	print("<td class='colhead'>Ansluten</td>");
	print("<td class='colhead'>Medelhastighet</td></tr>\n");

 	$num = 1;
	while ($a = mysql_fetch_assoc($res))
	{
		$uptime = mkprettytime($a["uptime"]);
		
		if (!$a["downloaded"] || !$a["left"])
			$rate = mksize($a["uploaded"] / $a["uptime"]);
		else
			$rate = mksize($a["downloaded"] / $a["uptime"]);

		print("<tr class='clear main line'><td style='text-align: center;'>" . $num++ . "</td><td>" . (!staff() && $a["hidetraffic"] == 'yes' ? "<i>Anonym</i>" : "<a href='userdetails.php?id=" . $a["userid"] . "'>$a[username]</a>") . "</td><td style='text-align: right;'>$uptime</td><td style='text-align: right;'>" . $rate . "/s</td></tr>\n");
	}

	print("</table>\n");
	print("</div></div><br />\n");
}


head("Topp 10");

$type = 0 + $_GET["type"];
if (!in_array($type, array(1, 2, 3, 4, 5, 6, 7)))
	$type = 1;

$limit = 0 + $_GET["lim"];
$subtype = htmlspecialchars($_GET["subtype"]);

print("<p style='text-align: center;'>"  .
	($type == 1 && !$limit ? "<span style='color: gray; font-size: 9pt; font-weight: bold;'>Användare</span>" : "<a href='topten.php?type=1'>Användare</a>") .	" | " .
 	($type == 4 && !$limit ? "<span style='color: gray; font-size: 9pt; font-weight: bold;'>Peers</span>" : "<a href='topten.php?type=4'>Peers</a>") . " | " .
	($type == 2 && !$limit ? "<span style='color: gray; font-size: 9pt; font-weight: bold;'>Länkar</span>" : "<a href='topten.php?type=2'>Länkar</a>") . " | " .
	($type == 3 && !$limit ? "<span style='color: gray; font-size: 9pt; font-weight: bold;'>Länder</span>" : "<a href='topten.php?type=3'>Länder</a>") . "</p>\n");

$pu = get_user_class() >= UC_POWER_USER;

if (!$pu)
	$limit = 10;

if ($type == 1)
{
	$date = "2013-01-08 12:45:00";
	$mainquery = "SELECT id as userid, username, added, uploaded, downloaded, hidetraffic, (SELECT SUM(uploaded) FROM snatched WHERE userid = users.id AND added > '$date') / real_peertime AS upspeed, (SELECT SUM(downloaded) FROM snatched WHERE userid = users.id AND added > '$date') / real_leechtime AS downspeed, seedbonus, time_online / (CASE WHEN TIMESTAMPDIFF(HOUR, added, NOW()) >= 24 THEN ((UNIX_TIMESTAMP() - (CASE WHEN UNIX_TIMESTAMP(added) < $time_online_start THEN $time_online_start	ELSE UNIX_TIMESTAMP(added) END)) / (3600 * 24)) ELSE 1 END) AS timeonline, (SELECT COUNT(*) FROM posts WHERE userid = users.id) AS posts FROM users WHERE enabled = 'yes'";

	if (!$limit || $limit > 250)
		$limit = 10;

	if ($limit == 10 || $subtype == "ul")
	{
		$mainquery = "SELECT id FROM users WHERE enabled = 'yes'";
		$order = "uploaded DESC";
		$r = mysql_query("$mainquery ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit uppladdare" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=ul'>Topp 100</a>]</span>" : ""));
	}

	if ($limit == 10 || $subtype == "dl")
	{
		$mainquery = "SELECT id FROM users WHERE enabled = 'yes'";
		$order = "downloaded DESC";
		$r = mysql_query("$mainquery ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit nedladdare" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=dl'>Topp 100</a>]</span>" : ""));
	}

	if ($limit == 10 || $subtype == "bsh")
	{
		$mainquery = "SELECT id FROM users WHERE enabled = 'yes'";
		$order = "uploaded / downloaded DESC, uploaded DESC";
		$extrawhere = " AND downloaded > 1073741824";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit bästa delare <span class='small'>(med minst 1 GB nedladdat)</span>" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=bsh'>Topp 100</a>]</span>" : ""));
	}

	if ($limit == 10 || $subtype == "wsh")
	{
		$mainquery = "SELECT id FROM users WHERE enabled = 'yes'";
		$order = "uploaded / downloaded ASC, downloaded DESC";
		$extrawhere = " AND downloaded > 1073741824";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit sämsta delare <span class='small'>(med minst 1 GB nedladdat)</span>" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=wsh'>Topp 100</a>]</span>" : ""));
	}
	
	if ($limit == 10 || $subtype == "bp")
	{
		$mainquery = "SELECT id FROM users WHERE enabled = 'yes'";
		$order = "seedbonus DESC";
		$r = mysql_query($mainquery . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit bonuspoäng" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=bp'>Topp 100</a>]</span>" : ""));
	}
	
	if ($limit == 10 || $subtype == "ac")
	{
		$mainquery = "SELECT id FROM users WHERE enabled = 'yes'";
		$order = "time_online / (CASE WHEN TIMESTAMPDIFF(HOUR, added, NOW()) >= 24 THEN ((UNIX_TIMESTAMP() - (CASE WHEN UNIX_TIMESTAMP(added) < $time_online_start THEN $time_online_start	ELSE UNIX_TIMESTAMP(added) END)) / (3600 * 24)) ELSE 1 END) DESC";
		$r = mysql_query($mainquery . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit aktivitet" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=ac'>Topp 100</a>]</span>" : ""));
	}
	
	if ($limit == 10 || $subtype == "fp")
	{
		$mainquery = "SELECT userid AS id FROM posts WHERE userid != 0 GROUP BY userid";
		$order = "COUNT(*) DESC";
		$r = mysql_query($mainquery . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		usertable($r, "Topp $limit foruminlägg" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=1&amp;lim=100&amp;subtype=fp'>Topp 100</a>]</span>" : ""));
	}
}
elseif ($type == 2)
{
	$mainquery = "SELECT id, name, size, times_completed, (SELECT SUM(downloaded) FROM snatched WHERE torrentid = torrents.id) AS data, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0) AS seeders, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0) AS leechers, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id) AS peers FROM torrents";

	if (!$limit || $limit > 50)
		$limit = 10;

	if ($limit == 10 || $subtype == "act")
	{
		$order = "peers DESC, seeders DESC, torrents.added ASC";
		$extrawhere = "";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		_torrenttable($r, "Topp $limit mest aktiva länkar" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=2&amp;lim=25&amp;subtype=act'>Topp 25</a>] - [<a href='topten.php?type=2&amp;lim=50&amp;subtype=act'>Topp 50</a>]</span>" : ""));
	}
	
	if ($limit == 10 || $subtype == "sna")
	{
		$order = "times_completed DESC, torrents.added ASC";
		$extrawhere = "";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		_torrenttable($r, "Topp $limit mest nedladdade länkar" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=2&amp;lim=25&amp;subtype=sna'>Topp 25</a>] - [<a href='topten.php?type=2&amp;lim=50&amp;subtype=sna'>Topp 50</a>]</span>" : ""));
	}
	  
	if ($limit == 10 || $subtype == "mdt")
	{
		$order = "data DESC";
		$extrawhere = "";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		_torrenttable($r, "Topp $limit mest dataöverförda länkar" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=2&amp;lim=25&amp;subtype=mdt'>Topp 25</a>] - [<a href='topten.php?type=2&amp;lim=50&amp;subtype=mdt'>Topp 50</a>]</span>" : ""));
	}
}
elseif ($type == 3)
{
	$mainquery = "SELECT countries.name, countries.pic, COUNT(users.id) AS num, SUM(users.uploaded) AS ul, SUM(users.uploaded)/COUNT(users.id) AS ul_avg, SUM(users.uploaded)/SUM(users.downloaded) AS r FROM countries LEFT JOIN users ON users.country = countries.id WHERE users.enabled = 'yes' GROUP BY countries.id";

	if (!$limit || $limit > 25)
		$limit = 10;

	if ($limit == 10 || $subtype == "us")
	{
		$order = "num DESC";
		$extrawhere = "";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		countriestable($r, "Topp $limit landskap <span class='small'>(användare)</span>" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=3&amp;lim=25&amp;subtype=us'>Topp 25</a>]</span>" : ""), "Users");
	}

	if ($limit == 10 || $subtype == "ul")
	{
		$order = "ul DESC";
		$extrawhere = "";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);

		countriestable($r, "Topp $limit landskap <span class='small'>(totalt uppladdat)</span>" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=3&amp;lim=25&amp;subtype=ul'>Topp 25</a>]</span>" : ""), "Uploaded");
	}

	if ($limit == 10 || $subtype == "avg")
	{
		$order = "num DESC";
		$extrawhere = " HAVING SUM(users.uploaded) > 1099511627776 AND COUNT(users.id) >= 100";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);

		countriestable($r, "Topp $limit landskap <span class='small'>(genomsnittligt totalt uppladdat)</span>" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=3&amp;lim=25&amp;subtype=avg'>Topp 25</a>]</span>" : ""), "Average");
	}

	if ($limit == 10 || $subtype == "r")
	{
		$order = "r DESC";
		$extrawhere = " HAVING SUM(users.uploaded) > 1099511627776 AND SUM(users.downloaded) > 1099511627776 AND COUNT(users.id) >= 100";
		$r = mysql_query($mainquery . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		countriestable($r, "Topp $limit landskap <span class='small'>(ratio med minimum 1 TB nedladdat)</span>" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=3&amp;lim=25&amp;subtype=r'>Topp 25</a>]</span>" : ""), "Ratio");
	}
}
elseif ($type == 4)
{
	$mainquery1 = "SELECT peers.uid AS userid, users.username, users.hidetraffic, SUM(peers.upspeed) AS uprate, SUM(peers.downspeed) AS downrate FROM peers LEFT JOIN users ON peers.uid = users.id GROUP BY peers.uid";
	$mainquery2 = "SELECT peers.uid AS userid, users.username, users.hidetraffic, peers.uploaded, peers.downloaded, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(peers.started)) AS uptime FROM peers LEFT JOIN users ON peers.uid = users.id";

	if (!$limit || $limit > 250)
		$limit = 10;

	if ($limit == 10 || $subtype == "ul")
	{
		$order = "uprate DESC";
		$extrawhere = "";
		$r = mysql_query($mainquery1 . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);

		peerstable($r, "Topp $limit snabbaste uppladdare" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=4&amp;lim=100&amp;subtype=ul'>Topp 100</a>]</span>" : ""));
	}

	if ($limit == 10 || $subtype == "dl")
	{
		$order = "downrate DESC";
		$extrawhere = "";
		$r = mysql_query($mainquery1 . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		peerstable($r, "Topp $limit snabbaste nedladdare" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=4&amp;lim=100&amp;subtype=dl'>Topp 100</a>]</span>" : ""));
	}
		
	if ($limit == 10 || $subtype == "uptimeseeders")
	{
		$order = "uptime DESC";
		$extrawhere = " WHERE peers.left = 0 GROUP BY peers.uid";
		$r = mysql_query($mainquery2 . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		uptimetable($r, "Topp $limit längsta seedare" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=4&amp;lim=100&amp;subtype=uptimeseeders'>Topp 100</a>]</span>" : ""));
	}

	if ($limit == 10 || $subtype == "uptimeleechers")
	{
		$order = "uptime DESC";
		$extrawhere = " WHERE peers.left > 0 GROUP BY peers.uid";
		$r = mysql_query($mainquery2 . $extrawhere . " ORDER BY $order LIMIT $limit") or sqlerr(__FILE__, __LINE__);
		
		uptimetable($r, "Topp $limit längsta leechare" . ($limit == 10 && $pu ? " <span class='small'> - [<a href='topten.php?type=4&amp;lim=100&amp;subtype=uptimeleechers'>Topp 100</a>]</span>" : ""));
	}
}

print("<p class='small' style='text-align: center;'>Beräkningen började 2012-11-03</p>");

foot();
?>