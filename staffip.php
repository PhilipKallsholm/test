<?php

require_once("globals.php");

dbconn();

if ($username = trim($_GET["name"]))
{
	mysql_query("UPDATE staffip SET ip = " . sqlesc($_GET["ip"]) . " WHERE username = " . sqlesc($username)) or sqlerr(__FILE__, __LINE__);
	
	stafflog("$_SERVER[REMOTE_ADDR] tillät IP $_GET[ip] till $username");
	die("Klart");
}

loggedinorreturn();

staffacc(UC_SYSOP);

if ($_POST)
{
	if ($_GET["edit"])
	{
		$ids = $_POST["id"];
		$usernames = $_POST["username"];
		$ips = $_POST["ip"];
		
		foreach ($ids AS $id)
		{
			$id = 0 + $id;
			$username = trim($usernames[$id]);
			$ip = trim($ips[$id]);
		
			if (!$username)
				mysql_query("DELETE FROM staffip WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
			else
				mysql_query("UPDATE staffip SET ip = " . sqlesc($ip) . ", username = " . sqlesc($username) . " WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		}
	}
	else
	{
		$username = trim($_POST["username"]);
		$ip = trim($_POST["ip"]);
		
		if (!$username)
			stderr("Fel", "Du måste ange ett användarnamn");
			
		if (!$ip)
			stderr("Fel", "Du måste ange ett IP");
		
		mysql_query("INSERT INTO staffip (ip, username) VALUES(" . implode(", ", array_map("sqlesc", array($ip, $username))) . ")") or sqlerr(__FILE__, __LINE__);
	}
	
	header("Location: staffip.php");
}

head("Staff-IP");

begin_frame("Staff-IP", 0, true);

print("<form method='post' action='staffip.php'><table>\n");
print("<tr><td class='colhead'>Användarnamn</td><td class='colhead'>IP</td></tr>\n");
print("<tr><td><input type='text' name='username' size=30 /></td><td><input type='text' name='ip' size=30 /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' value='Lägg till' /></td></tr>\n");
print("</table></form>\n");

print("<form method='post' action='staffip.php?edit=1'><table style='margin-top: 20px;'>\n");

$res = mysql_query("SELECT * FROM staffip ORDER BY username ASC") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<tr><td colspan=2><i>Inga IP-adresser tillagda</i></td></tr>\n");

while ($arr = mysql_fetch_assoc($res))
	print("<tr><td><input type='hidden' name='id[]' value=$arr[id] /><input type='text' name='username[$arr[id]]' size=30 value='$arr[username]' /></td><td><input type='text' name='ip[$arr[id]]' size=30 value='$arr[ip]' /></td></tr>\n");
	
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' value='Uppdatera' /></td></tr>\n");
print("</table></form>\n");

print("</div></div>\n");
foot();

?>