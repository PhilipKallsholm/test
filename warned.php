<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

head("Varnade användare");

$type = $_GET["type"] ? $_GET["type"] : 1;

print("<p style='text-align: center;'>"  .
	($type == 1 ? "<span style='color: gray; font-weight: bold;'>Varnade användare</span>" : "<a href='warned.php?type=1'>Varnade användare</a>") .	" | " .
 	($type == 2 ? "<span style='color: gray; font-weight: bold;'>50 senast registrerade</span>" : "<a href='warned.php?type=2'>50 senast registrerade</a>") . " | " .
	($type == 3 ? "<span style='color: gray; font-weight: bold;'>Användare med titel</span>" : "<a href='warned.php?type=3'>Användare med titel</a>")
. "</p>\n");

if ($type == 1)
{
	$res = mysql_query("SELECT id, username, added, last_access, class, warned_until FROM users WHERE warned = 'yes' ORDER BY warned_until ASC") or sqlerr(__FILE__, __LINE__);
	$count = mysql_num_rows($res);

	begin_frame("Varnade användare ($count)", "", true);

	print("<table>\n");
	print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Registrerad</td><td class='colhead'>Senast aktiv</td><td class='colhead'>Klass</td><td class='colhead'>Slut på varning</td></tr>\n");

	while ($arr = mysql_fetch_assoc($res))
		print("<tr><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a></td><td>$arr[added]</td><td>$arr[last_access]</td><td>" . get_user_class_name($arr["class"]) . "</td><td>$arr[warned_until]</td></tr>\n");
	
	print("</table>\n");
	print("</div></div>\n");
}
elseif ($type == 2)
{
	$res = mysql_query("SELECT id, username, added, last_access, mail, class FROM users ORDER BY added DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
	$count = mysql_num_rows($res);

	begin_frame("Senast registrerade användare ($count)", "", true);

	print("<table>\n");
	print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Mail</td><td class='colhead'>Registrerad</td><td class='colhead'>Senast aktiv</td><td class='colhead'>Klass</td></tr>\n");

	while ($arr = mysql_fetch_assoc($res))
		print("<tr><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a></td><td>$arr[mail]</td><td>$arr[added]</td><td>$arr[last_access]</td><td>" . get_user_class_name($arr["class"]) . "</td></tr>\n");
	
	print("</table>\n");
	print("</div></div>\n");
}
elseif ($type == 3)
{
	$res = mysql_query("SELECT id, username, added, last_access, class, title FROM users WHERE title != '' ORDER BY username ASC") or sqlerr(__FILE__, __LINE__);
	$count = mysql_num_rows($res);

	begin_frame("Användare med titel ($count)", "", true);

	print("<table>\n");
	print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Titel</td><td class='colhead'>Registrerad</td><td class='colhead'>Senast aktiv</td><td class='colhead'>Klass</td></tr>\n");

	while ($arr = mysql_fetch_assoc($res))
		print("<tr><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a></td><td>$arr[title]</td><td>$arr[added]</td><td>$arr[last_access]</td><td>" . get_user_class_name($arr["class"]) . "</td></tr>\n");
	
	print("</table>\n");
	print("</div></div>\n");
}

foot();
?>