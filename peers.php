<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$id = 0 + $_GET["id"];

function maketable($res, $peer = true)
{
	global $id;

	$ret = "<table style='width: 100%;'><tr><td class='colhead' style='text-align: center;'>Typ</td><td class='colhead'>Namn</td><td class='colhead' style='text-align: center;'>Storlek</td><td class='colhead' style='text-align: right;'>S</td><td class='colhead' style='text-align: right;'>L</td>" . ($peer ? "<td class='colhead' style='text-align: center;'>Uppl.</td><td class='colhead' style='text-align: center;'>Nedl.</td><td class='colhead' style='text-align: center;'>Ratio</td>" : "") . "</tr>\n";

	while ($arr = mysql_fetch_assoc($res))
	{
		if ($arr["downloaded"])
		{
			$ratio = number_format($arr["uploaded"] / $arr["downloaded"], 3);
			$ratio = "<span style='color: " . get_ratio_color($ratio) . ";'>$ratio</span>";
		}
		else
			if ($arr["uploaded"])
				$ratio = "Inf.";
			else
				$ratio = "---";
		
		$now = time();
		
		if ($peer)
			$time = "Ansluten " . get_elapsed_time_all($now, strtotime($arr["started"]));
		else
			$time = $arr["added"] . " (" . get_elapsed_time($arr["added"]) . " sedan)";
			
		$char = 50;
		$catimage = $arr["image"];
		$catname = $arr["catname"];
		$size = str_replace(" ", "<br />", mksize($arr["size"]));
		$uploaded = str_replace(" ", "<br />", mksize($arr["uploaded"]));
		$downloaded = str_replace(" ", "<br />", mksize($arr["downloaded"]));
	
		$ret .= "<tr" . ($arr["freeleech"] ? " style='background-color: #CCFFCC;'" : "") . "><td style='width: 40px; padding: 0px;'><img src='$catimage' alt='$catname' /></td><td><a href='details.php?id=$arr[id]&amp;hit=1' title='$arr[name]'>" . CutName($arr["name"], $char) . "</a><br /><span class='small'>$time</span></td><td style='text-align: center;'>$size</td><td style='text-align: right;'>$arr[seeders]</td><td style='text-align: right;'>$arr[leechers]</td>" . ($peer ? "<td style='text-align: center;'>$uploaded</td><td style='text-align: center;'>$downloaded</td><td style='text-align: center;'>$ratio</td>" : "") . "</tr>\n";
	}
	$ret .= "</table>\n";
	return $ret;
}

$user = mysql_query("SELECT * FROM users WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$user = mysql_fetch_assoc($user);

if (!$user)
	die("Användaren finns inte");
	
if ($user["hidetraffic"] == 'no' || $user["id"] == $CURUSER["id"] || get_user_class() >= UC_MODERATOR)
	$viewprof = true;

if ($_GET["new"])
{
	$r = mysql_query("SELECT torrents.id, torrents.name, torrents.category, torrents.size, torrents.added, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0) AS leechers, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0) AS seeders, torrents.freeleech, categories.name AS catname, categories.image FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE owner = $id" . (get_user_class() < UC_MODERATOR && $CURUSER["id"] != $id ? " AND anonymous = 'no'" : "") . " AND req = 0 ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);
	print(maketable($r, false));
}
elseif ($_GET["old"])
{
	$r = mysql_query("SELECT torrents.id, torrents.name, torrents.category, torrents.size, torrents.added, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0) AS leechers, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0) AS seeders, torrents.freeleech, categories.name AS catname, categories.image FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE owner = $id" . (get_user_class() < UC_MODERATOR && $CURUSER["id"] != $id ? " AND anonymous = 'no'" : "") . " AND req = 2 ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);
	print(maketable($r, false));
} 
elseif ($_GET["requests"])
{
	$r = mysql_query("SELECT torrents.id, torrents.name, torrents.category, torrents.size, torrents.added, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0) AS leechers, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0) AS seeders, torrents.freeleech, categories.name AS catname, categories.image FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE owner = $id" . (get_user_class() < UC_MODERATOR && $CURUSER["id"] != $id ? " AND anonymous = 'no'" : "") . " AND req = 1 ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);
	print(maketable($r, false));
}
elseif ($_GET["seeding"])
{
	if (!$viewprof)
		die("Tillgång nekad");
		
	$r = mysql_query("SELECT torrents.id, torrents.name, torrents.category, torrents.size, torrents.added, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0) AS leechers, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0) AS seeders, torrents.freeleech, categories.name AS catname, categories.image, peers.downloaded, peers.uploaded, peers.started FROM peers LEFT JOIN torrents ON peers.fid = torrents.id LEFT JOIN categories ON torrents.category = categories.id WHERE peers.uid = $id AND `left` = 0 AND active = 1 ORDER BY peers.started DESC") or sqlerr(__FILE__, __LINE__);
	print(maketable($r));
}
elseif ($_GET["leeching"])
{
	if (!$viewprof)
		die("Tillgång nekad");

	$r = mysql_query("SELECT torrents.id, torrents.name, torrents.category, torrents.size, torrents.added, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0) AS leechers, (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0) AS seeders, torrents.freeleech, categories.name AS catname, categories.image, peers.downloaded, peers.uploaded, peers.started FROM peers LEFT JOIN torrents ON peers.fid = torrents.id LEFT JOIN categories ON torrents.category = categories.id WHERE peers.uid = $id AND `left` > 0 AND active = 1 ORDER BY peers.started DESC") or sqlerr(__FILE__, __LINE__);
	print(maketable($r));
}
elseif ($_GET["seeded"])
{
	if (!staff() || $user["class"] > $CURUSER["class"] && $user["id"] != $CURUSER["id"])
		die("Tillgång nekad");
		
	print("<table><tr><td class='colhead'>Länk</td><td class='colhead'>Påbörjad</td><td class='colhead'>Uppladdat</td><td class='colhead'>Nedladdat</td><td class='colhead'>Ratio</td><td class='colhead'>Färdig</td><td class='colhead'>Spenderad tid</td><td class='colhead'>Senast aktiv</td></tr>\n");
		
	$res = mysql_query("SELECT snatched.*, torrents.name AS torrentname FROM snatched LEFT JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.userid = $user[id] AND snatched.download = 'no' ORDER BY snatched.added DESC") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
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
		
		if ($arr["name"])
			$name = "<span style='color: gray; font-weight: bold;'>$arr[name]</span>";
		else
			$name = "<a href='details.php?id=$arr[torrentid]'>$arr[torrentname]</a>";
		
		print("<tr><td>$name</td><td>$arr[added]</td><td>$uploaded</td><td>$downloaded</td><td>$ratio</td><td>$arr[done]</td><td>" . mkprettytime($arr["timespent"]) . "</td><td>$arr[last_action]</td></tr>\n");
	}
	print("</table>\n");
}
elseif ($_GET["leeched"])
{
	if (!staff() || $user["class"] > $CURUSER["class"] && $user["id"] != $CURUSER["id"])
		die("Tillgång nekad");
		
	print("<table><tr><td class='colhead'>Länk</td><td class='colhead'>Påbörjad</td><td class='colhead'>Uppladdat</td><td class='colhead'>Nedladdat</td><td class='colhead'>Ratio</td><td class='colhead'>Färdig</td><td class='colhead'>Spenderad tid</td><td class='colhead'>Senast aktiv</td></tr>\n");
		
	$res = mysql_query("SELECT snatched.*, torrents.name AS torrentname FROM snatched LEFT JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.userid = $user[id] AND snatched.download = 'yes' ORDER BY snatched.added DESC") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
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
		
		if ($arr["name"])
			$name = "<span style='color: gray; font-weight: bold;'>$arr[name]</span>";
		else
			$name = "<a href='details.php?id=$arr[torrentid]'>$arr[torrentname]</a>";
		
		print("<tr" . ($arr["added"] != '0000-00-00 00:00:00' && $arr["done"] == '0000-00-00 00:00:00' ? " style='background-color: #ffcccc;'" : "") . "><td>$name</td><td>$arr[added]</td><td>$uploaded</td><td>$downloaded</td><td>$ratio</td><td>$arr[done]</td><td>" . mkprettytime($arr["timespent"]) . "</td><td>$arr[last_action]</td></tr>\n");
	}
	print("</table>\n");
}

?>