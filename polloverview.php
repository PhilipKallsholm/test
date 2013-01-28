<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_GET["id"])
{
	$id = 0 + $_GET["id"];

	$res = mysql_query("SELECT * FROM polls WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if (!$arr)
		stderr("Fel", "Omröstningen hittades inte");
		
	head($arr["name"]);
	begin_frame($arr["name"], "", true);
	
	print("<table>\n");
	print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>Svar</td></tr>\n");
	
	$res = mysql_query("SELECT pollanswers.userid, pollalternatives.name, users.username FROM pollanswers LEFT JOIN pollalternatives ON pollanswers.voteid = pollalternatives.id LEFT JOIN users ON users.id = pollanswers.userid WHERE pollanswers.pollid = " . sqlesc($id) . " ORDER BY pollanswers.id DESC") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		if ($arr["username"])
			$username = "<a href='userdetails.php?id=$arr[userid]'>$arr[username]</a>";
		else
			$username = "<i>Borttagen</i>";
			
		if (!$arr["name"])
			$arr["name"] = "<i>Blank röst</i>";
			
		print("<tr><td>$username</td><td>$arr[name]</td></tr>\n");
	}
	
	print("</table>\n");
	print("</div></div>\n");
	
	foot();
	die;
}

head("Omröstningar");
begin_frame("Omröstningar", "", true);

print("<table>\n");
print("<tr><td class='colhead'>#</td><td class='colhead'>Fråga</td><td class='colhead'>Tillagd</td><td class='colhead'>Svar</td></tr>\n");

$res = mysql_query("SELECT polls.*, COUNT(pollanswers.id) AS votes FROM polls LEFT JOIN pollanswers ON polls.id = pollanswers.pollid GROUP BY pollanswers.pollid ORDER BY polls.added DESC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
	print("<tr><td style='text-align: center;'>$arr[id]</td><td><a href='polloverview.php?id=$arr[id]'>$arr[name]</a></td><td>$arr[added]</td><td style='text-align: center;'>" . number_format($arr["votes"]) . "</td></tr>\n");
	
print("</table>\n");
print("</div></div>\n");

foot();
?>