<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

head("Misslyckade inloggningsförsök");

begin_frame("Statistik");

print("<table><tr class='clear'><td>\n");

print("<h3>Topp 10 lösenord</h3><table>\n");
print("<tr><td class='colhead'>#</td><td class='colhead'>Lösenord</td><td class='colhead'>Försök</td></tr>\n");

$res = mysql_query("SELECT COUNT(*) AS attempts, password FROM failedlogins GROUP BY password ORDER BY attempts DESC LIMIT 10") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($arr = mysql_fetch_assoc($res))
	print("<tr class='main'><td>" . ++$i . "</td><td>$arr[password]</td><td>$arr[attempts]</td></tr>\n");
	
print("</table></td><td>\n");

print("<h3>Topp 10 IP-adresser</h3><table>\n");
print("<tr><td class='colhead'>#</td><td class='colhead'>IP</td><td class='colhead'>Försök</td></tr>\n");

$res = mysql_query("SELECT SUM(attempts) AS attempts, ip FROM failedlogins GROUP BY ip ORDER BY attempts DESC LIMIT 10") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($arr = mysql_fetch_assoc($res))
	print("<tr class='main'><td>" . ++$i . "</td><td>$arr[ip]" . ($arr["host"] ? " ($arr[host])" : "") . "</td><td>$arr[attempts]</td></tr>\n");
	
print("</table></td><td>\n");

print("<h3>Topp 10 användare</h3><table>\n");
print("<tr><td class='colhead'>#</td><td class='colhead'>Användarnamn</td><td class='colhead'>Försök</td></tr>\n");

$res = mysql_query("SELECT COUNT(*) AS attempts, username, userid FROM failedlogins GROUP BY username ORDER BY attempts DESC LIMIT 10") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($arr = mysql_fetch_assoc($res))
	print("<tr class='main'><td>" . ++$i . "</td><td>" . ($arr["userid"] ? "<a href='userdetails.php?id=$arr[userid]'>$arr[username]</a>" : $arr["username"]) . "</td><td>$arr[attempts]</td></tr>\n");
	
print("</table></td>\n");

print("</tr></table></div></div><br /><br />\n");

$res = mysql_query("SELECT * FROM failedlogins") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("logins.php?page=", $count, 100, $_GET["page"]);

begin_frame("Misslyckade inloggningsförsök (" . number_format($count) . ")");

switch ($_GET["sort"])
{
	case 'ip':
		$sort = "ip DESC";
		break;
	case 'username':
		$sort = "username ASC";
		break;
	case 'password':
		$sort = "password ASC";
		break;
	case 'attempts':
		$sort = "attempts DESC";
		break;
	default:
		$sort = "added DESC";
		break;
}

print("<p style='text-align: center;'>$pager</p>\n");

print("<table>\n");
print("<tr><td class='colhead'><a href='?sort=added' class='white'>Datum</a></td><td class='colhead'><a href='?sort=ip' class='white'>IP</a></td><td class='colhead'><a href='?sort=username' class='white'>Användarnamn</a></td><td class='colhead'><a href='?sort=password' class='white'>Lösenord</a></td><td class='colhead'><a href='?sort=attempts' class='white'>Försök</a></td></tr>\n");

$res = mysql_query("SELECT * FROM failedlogins ORDER BY $sort $limit") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
	print("<tr class='main'><td>$arr[added]</td><td>$arr[ip]" . ($arr["host"] ? " ($arr[host])" : "") . "</td><td>" . ($arr["userid"] ? "<a href='userdetails.php?id=$arr[userid]'>$arr[username]</a>" : htmlent($arr["username"])) . "</td><td>" . htmlent($arr["password"]) . "</td><td>$arr[attempts]</td></tr>\n");

print("</table>\n");

print("<p style='text-align: center;'>$pager</p>\n");

print("</div></div>\n");

foot();
?>