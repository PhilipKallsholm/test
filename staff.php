<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Staff");

/*if ($CURUSER["id"] == 1)
{
	$data = file_get_contents("hej.sql");
	
	preg_match_all("#'([^']+@[^']+)','confirmed'#i", $data, $matches);
	
	foreach ($matches[1] AS $mail)
		mysql_query("INSERT INTO mails (mail) VALUES(" . sqlesc($mail) . ")") or sqlerr(__FILE__, __LINE__);
}

if ($CURUSER["id"] == 1)
{
	$mailss = mysql_query("SELECT mail FROM mails") or sqlerr(__FILE__, __LINE__);
	
	$i = 0;
	
	while ($mails = mysql_fetch_assoc($mailss))
	{
		$hash = md5(mksecret());
		$time = get_date_time();
		$mail = $mails["mail"];
	
		if (!validmail($mail))
			continue;

		$res = mysql_query("SELECT * FROM invites WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if (mysql_num_rows($res))
			continue;

		$res = mysql_query("SELECT * FROM users WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if (mysql_num_rows($res))
			continue;

		mysql_query("INSERT INTO invites (hash, mail, added) VALUES(" . implode(", ", array_map("sqlesc", array($hash, $mail, $time))) . ")") or sqlerr(__FILE__, __LINE__);

		$body = <<<EOD
Please follow this link to create your $sitename account:

$defaultbaseurl/signup.php/$hash

--
$sitename
EOD;

		$mailed = mail($mail, "$sitename - Invite", $body, "From: $sitemail");
		$i++;
	}
	
	print($i . " inviter skickades");
}*/

print("<div style='width: 600px; margin: 0px auto; padding: 10px; text-align: left;'>\n");
print("<h3 style='text-align: center; color: red;'>Vid ej personliga ärenden, vänligen kontakta support</h3>\n");

define(UC_BETADMIN, 6);
$i = UC_SYSOP;

while ($i >= UC_BETADMIN)
{
	$class = $i--;
	
	print("<h3 style='margin: 5px 0px; border-bottom: 2px solid #ededed;'>" . ($class > UC_BETADMIN ? get_user_class_name($class) : "Betadmin") . "</h3>\n");
	
	print("<table style='margin: 0px;'><tr class='clear'>\n");
	
	$res = mysql_query("SELECT id, username, last_access FROM users WHERE " . ($class > UC_BETADMIN ? "class = $class" : "betadmin = 'yes'") . " ORDER BY username ASC") or sqlerr(__FILE__, __LINE__);
	
	if (!mysql_num_rows($res))
		print("<td><i>Ingen personal</i></td>\n");

	$l = 0;
	while ($arr = mysql_fetch_assoc($res))
	{		
		print("<td style='width: 100px;'><a href='userdetails.php?id=$arr[id]'>$arr[username]</a></td><td style='width: 70px;'><img src='/pic/" . ($arr["last_access"] > get_date_time(strtotime("-3 minutes")) ? "online.gif" : "offline.gif") . "' /></td>\n");
		
		if ($l++ && $l % 3 == 0 && mysql_num_rows($res) > $l)
			print("</tr><tr class='clear'>\n");
	}
	
	print("</tr></table>\n");
}

print("</div>\n");
	
foot();

?>