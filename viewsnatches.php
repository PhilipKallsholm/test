<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Hämtare");

$id = 0 + $_GET["id"];

$dt = time() - 180;
$dt = get_date_time($dt);

$res = mysql_query("SELECT COUNT(snatched.id) AS count, torrents.name FROM snatched LEFT JOIN users ON snatched.userid = users.id LEFT JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.torrentid = $id AND snatched.done != '0000-00-00 00:00:00'") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_array($res);

print("<h1><a href='details.php?id=$id'>$arr[name]</a></h1>\n");
print("<h1>Hämtade ($arr[count] st)</h1>\n");

print("<table>\n");
print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Uppladdat</td><td class='colhead'>Nerladdat</td><td class='colhead'>Ratio</td><td class='colhead'>Påbörjad</td><td class='colhead'>Färdighämtad</td><td class='colhead'>Aktiv tid</td><td class='colhead'>Senast aktiv</td><td class='colhead'>Status</td><td class='colhead'>Seedar</td></tr>\n");

$res = mysql_query("SELECT users.id, users.username, users.title, users.last_access, users.enabled, users.warned, users.warned_reason, snatched.* FROM snatched LEFT JOIN users ON snatched.userid = users.id LEFT JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.torrentid = $id AND snatched.done != '0000-00-00 00:00:00' ORDER BY snatched.done DESC");
while ($arr = mysql_fetch_assoc($res))
{
	$peer = mysql_query("SELECT * FROM peers WHERE fid = $id AND uid = $arr[userid]");
	$peer = mysql_fetch_assoc($peer);

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

	$uploaded =mksize($arr["uploaded"]);
	$downloaded = mksize($arr["downloaded"]);

	print("<tr><td><a href='userdetails.php?id=$arr[userid]'>$arr[username]</a></td><td>$uploaded</td><td>$downloaded</td><td>$ratio</td><td>$arr[added]</td><td>$arr[done]</td><td>" . mkprettytime($arr["timespent"]) . "</td><td>$arr[last_action]</td><td style='text-align: center;'>" . ($arr["last_access"] > $dt ? "<img src='/pic/online.gif' />" : "<img src='/pic/offline.gif' />" ) . "</td><td style='text-align: center;'>" . ($peer["active"] ? "<span style='color: green; font-weight: bold;'>Ja</span>" : "<span style='color: red; font-weight: bold;'>Nej</span>") . "</td></tr>\n");
}
print("</table>\n");

$res = mysql_query("SELECT COUNT(snatched.id) AS count, torrents.name FROM snatched LEFT JOIN users ON snatched.userid = users.id LEFT JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.torrentid = $id AND snatched.done = '0000-00-00 00:00:00'") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_array($res);

print("<h1 style='margin-top: 10px;'>Delvis hämtade ($arr[count] st)</h1>\n");

print("<table>\n");
print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Uppladdat</td><td class='colhead'>Nerladdat</td><td class='colhead'>Ratio</td><td class='colhead'>Påbörjad</td><td class='colhead'>Aktiv tid</td><td class='colhead'>Senast aktiv</td><td class='colhead'>Status</td><td class='colhead'>Leechar</td></tr>\n");

$res = mysql_query("SELECT users.id, users.username, users.title, users.last_access, users.enabled, users.warned, users.warned_reason, snatched.* FROM snatched LEFT JOIN users ON snatched.userid = users.id LEFT JOIN torrents ON snatched.torrentid = torrents.id WHERE snatched.torrentid = $id AND snatched.done = '0000-00-00 00:00:00' ORDER BY snatched.id DESC");
while ($arr = mysql_fetch_assoc($res))
{
	$peer = mysql_query("SELECT * FROM peers WHERE fid = $id AND uid = $arr[userid]");
	$peer = mysql_fetch_assoc($peer);

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

	$uploaded =mksize($arr["uploaded"]);
	$downloaded = mksize($arr["downloaded"]);

	print("<tr><td><a href='userdetails.php?id=$arr[userid]'>$arr[username]</a></td><td>$uploaded</td><td>$downloaded</td><td>$ratio</td><td>$arr[added]</td><td>" . mkprettytime($arr["timespent"]) . "</td><td>$arr[last_action]</td><td style='text-align: center;'>" . ($arr["last_access"] > $dt ? "<img src='/pic/online.gif' />" : "<img src='/pic/offline.gif' />" ) . "</td><td style='text-align: center;'>" . ($peer["active"] ? "<span style='color: green; font-weight: bold;'>Ja</span>" : "<span style='color: red; font-weight: bold;'>Nej</span>") . "</td></tr>\n");
}
print("</table>\n");

foot();

?>