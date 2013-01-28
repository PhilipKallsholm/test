<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Behöver seedas");

print("<h2>Torrents som behöver seedas</h2>\n");
print("<table>\n");  

print("<tr><td class='colhead' style='width: 80%;'>Namn</td><td class='colhead' style='width: 10%;'>Seedare</td><td class='colhead' style='width: 10%;'>Leechare</td></tr>\n");

$res = mysql_query("SELECT MIN(peers.`left`) AS seeders, COUNT(peers.uid) AS leechers, peers.fid, torrents.name FROM peers, torrents WHERE torrents.req = 0 AND peers.fid = torrents.id GROUP BY fid HAVING seeders > 0 ORDER BY leechers DESC");

if (mysql_num_rows($res))
{
	while ($arr = mysql_fetch_assoc($res))
	{
		$torrname = htmlspecialchars($arr["name"]);
 
		if (strlen($torrname) > 55)
			$torrname = substr($torrname, 0, 55) . "...";

		print("<tr><td><a href='details.php?id=$arr[fid]&amp;hit=1' title='$arr[name]'>$torrname</a></td><td style='color: red;'>0</td><td>" . number_format($arr["leechers"]) . "</td></tr>\n");  
	}
}

print("</table><br />\n");

print("<h2>Requests som behöver seedas</h2>\n");
print("<table>\n");  

print("<tr><td class='colhead' style='width: 80%;'>Namn</td><td class='colhead' style='width: 10%;'>Seedare</td><td class='colhead' style='width: 10%;'>Leechare</td></tr>\n");

$res = mysql_query("SELECT MIN(peers.`left`) AS seeders, COUNT(peers.uid) AS leechers, peers.fid, torrents.name FROM peers, torrents WHERE torrents.req = 1 AND peers.fid = torrents.id GROUP BY fid HAVING seeders > 0 ORDER BY leechers DESC");

if (mysql_num_rows($res))
{
	while ($arr = mysql_fetch_assoc($res))
	{
		$torrname = htmlspecialchars($arr["name"]);
 
		if (strlen($torrname) > 55)
			$torrname = substr($torrname, 0, 55) . "...";

		print("<tr><td><a href='details.php?id=$arr[fid]&amp;hit=1' title='$arr[name]'>$torrname</a></td><td style='color: red;'>0</td><td>" . number_format($arr["leechers"]) . "</td></tr>\n");  
	}
}

print("</table><br />\n");

print("<h2>Arkiv som behöver seedas</h2>\n");
print("<table>\n");  

print("<tr><td class='colhead' style='width: 80%;'>Namn</td><td class='colhead' style='width: 10%;'>Seedare</td><td class='colhead' style='width: 10%;'>Leechare</td></tr>\n");

$res = mysql_query("SELECT MIN(peers.`left`) AS seeders, COUNT(peers.uid) AS leechers, peers.fid, torrents.name FROM peers, torrents WHERE torrents.req = 2 AND peers.fid = torrents.id GROUP BY fid HAVING seeders > 0 ORDER BY leechers DESC");

if (mysql_num_rows($res))
{
	while ($arr = mysql_fetch_assoc($res))
	{
		$torrname = htmlspecialchars($arr["name"]);
 
		if (strlen($torrname) > 55)
			$torrname = substr($torrname, 0, 55) . "...";

		print("<tr><td><a href='details.php?id=$arr[fid]&amp;hit=1' title='$arr[name]'>$torrname</a></td><td style='color: red;'>0</td><td>" . number_format($arr["leechers"]) . "</td></tr>\n");  
	}
}

print("</table>\n");

foot();

?>