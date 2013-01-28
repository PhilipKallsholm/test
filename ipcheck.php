<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

head("Användare som använt samma IP");
begin_frame("Användare som använt samma IP", "", true);

$res = mysql_query("SELECT id FROM iplogg GROUP BY ip HAVING COUNT(*) > 1") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("ipcheck.php?page=", $count, 10, $_GET["page"]);

print("<p style='text-align: center;'>$pager</p>\n");

print("<table>\n");
print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Mail</td><td class='colhead'>Registrerad</td><td class='colhead'>Senast aktiv</td><td class='colhead'>IP</td></tr>\n");

$res = mysql_query("SELECT COUNT(*) AS t, ip, host FROM iplogg GROUP BY ip HAVING t > 1 ORDER BY t DESC $limit") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($arr = mysql_fetch_assoc($res))
{
	$users = mysql_query("SELECT iplogg.timesseen, users.id, users.username, users.mail, users.added, users.last_access FROM iplogg INNER JOIN users ON iplogg.userid = users.id WHERE iplogg.ip = " . sqlesc($arr["ip"]) . " ORDER BY iplogg.lastseen DESC") or sqlerr(__FILE__, __LINE__);
	
	while ($user = mysql_fetch_assoc($users))
		print("<tr" . ($i % 2 == 0 ? " style='background-color: #ededed;'" : "") . "><td><a href='userdetails.php?id=$user[id]'>$user[username]</a> ($user[timesseen])</td><td>$user[mail]</td><td>$user[added]</td><td>$user[last_access]</td><td>$arr[ip] ($arr[host])</td></tr>\n");

	$i++;
}

print("</table>\n");
print("<p style='text-align: center;'>$pager</p>\n");
print("</div></div>\n");

foot();
?>