<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

head("Skickade invites");
begin_frame("Statistik", 500);

print("<table><tr class='clear'><td>\n");

print("<table>\n");
print("<tr><td class='colhead'>#</td><td class='colhead'>Användarnamn</td><td class='colhead'>Invites</td></tr>\n");

$res = mysql_query("SELECT id, username, invites FROM users ORDER BY invites DESC LIMIT 5") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($arr = mysql_fetch_assoc($res))
	print("<tr class='main'><td style='text-align: center;'>" . ++$i . "</td><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a></td><td style='text-align: center;'>$arr[invites]</td></tr>\n");
	
print("</table>\n");

print("</td><td>\n");

print("<table>\n");
print("<tr><td class='colhead'>#</td><td class='colhead'>Användarnamn</td><td class='colhead'>Inviterade</td></tr>\n");

$res = mysql_query("SELECT COUNT(invites.id) AS invited, invites.inviter, users.username FROM invites LEFT JOIN users ON users.id = invites.inviter WHERE invites.userid != 0 GROUP BY invites.inviter ORDER BY invited DESC LIMIT 5") or sqlerr(__FILE__, __LINE__);

$i = 0;
while ($arr = mysql_fetch_assoc($res))
{	
	if ($arr["username"])
		$username = "<a href='userdetails.php?id=$arr[inviter]'>$arr[username]</a>";
	else
		$username = "<i>Borttagen</i>";

	print("<tr class='main'><td style='text-align: center;'>" . ++$i . "</td><td>$username</td><td style='text-align: center;'>$arr[invited]</td></tr>\n");
}

print("</table>\n");

print("</td></tr></table>\n");
print("</div></div><br /><br />\n");

begin_frame("Skickade invites", "", true);

switch ($_GET["sort"])
{
	case 'u':
		$sort = "username ASC";
		$q = "u";
		break;
	case 'm':
		$sort = "mail ASC";
		$q = "m";
		break;
	case 'h':
		$sort = "hash ASC";
		$q = "h";
		break;
	case 'i':
		$sort = "invitername ASC";
		$q = "i";
		break;
	case 'a':
		$sort = "added DESC";
		$q = "a";
		break;
	case 'r':
		$sort = "registered DESC";
		$q = "r";
		break;
	default:
		$sort = "added DESC";
}

$res = mysql_query("SELECT * FROM invites") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("invited.php?" . ($q ? "sort={$q}&" : "") . "page=", $count, 100, $_GET["page"]);

print("<p style='text-align: center;'>$pager</p>\n");

print("<table>\n");
print("<tr><td class='colhead'><a href='?sort=u'>Användarnamn</a></td><td class='colhead'><a href='?sort=m'>Mail</a></td><td class='colhead'><a href='?sort=h'>Hash</a></td><td class='colhead'><a href='?sort=i'>Inbjudare</a></td><td class='colhead'><a href='?sort=a'>Inbjuden</a></td><td class='colhead'><a href='?sort=r'>Registrerad</a></td></tr>\n");

$res = mysql_query("SELECT invites.*, i.username AS invitername, u.username, u.added AS registered FROM invites LEFT JOIN users i ON i.id = invites.inviter LEFT JOIN users u ON u.id = invites.userid ORDER BY $sort $limit") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	if ($arr["invitername"])
		$inviter = "<a href='userdetails.php?id=$arr[inviter]'>$arr[invitername]</a>";
	else
	{
		if ($arr["inviter"])
			$inviter = "<i>Borttagen</i>";
		else
			$inviter = "<i>System</i>";
	}

	if ($arr["userid"])
	{	
		if ($arr["username"])
			$username = "<a href='userdetails.php?id=$arr[userid]'>$arr[username]</a>";
		else
			$username = "<i>Borttagen</i>";
	}
	else
		$username = "<i>Väntar</i>";
		
	print("<tr style='background-color: " . ($arr["userid"] ? "#ccffcc" : "#ffcccc") . ";'><td>$username</td><td>$arr[mail]</td><td>$arr[hash]</td><td>$inviter</td><td>$arr[added]</td><td style='text-align: center;'>" . ($arr["registered"] ? $arr["registered"] : "-") . "</td></tr>\n");
}

print("</table>\n");
print("<p style='text-align: center;'>$pager</p>\n");
print("</div></div>\n");

foot();
?>