<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_MODERATOR)
	stderr("Fel", "Tillgång nekad.");

$sites = array("Sparvar", "TheInternationals", "SceneAccess", "SceneHD", "SweDVDr");

if ($_POST)
{
	$id = 0 + $_POST["id"];
	$hash = md5(mksecret());
	$time = get_date_time();

	if ($_POST["accept"])
	{
		$app = mysql_query("SELECT * FROM applications WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$app = mysql_fetch_assoc($app);
		
		$mail = $app["mail"];
		
		if (!$app)
			stderr("Fel", "Ansökningen finns inte");
	
		if (!validmail($mail))
			stderr("Fel", "Mailadressen ej giltig");

		$res = mysql_query("SELECT * FROM invites WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if (mysql_num_rows($res))
			stderr("Fel", "En invite har redan blivit skickad till personen");

		$res = mysql_query("SELECT * FROM users WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if (mysql_num_rows($res))
			stderr("Fel", "Mailen finns redan registrerad");

		if ($app["accepted"] != 'pending')
			stderr("Fel", "Denna ansökan har redan blivit behandlad");

		$elapsed_time = time() - strtotime($app["added"]);

		mysql_query("INSERT INTO invites (hash, mail, added) VALUES(" . implode(", ", array_map("sqlesc", array($hash, $mail, $time))) . ")") or sqlerr(__FILE__, __LINE__);
		mysql_query("UPDATE applications SET accepted = 'yes', staffid = $CURUSER[id], elapsed_time = $elapsed_time WHERE id = $id") or sqlerr(__FILE__, __LINE__);

		$body = <<<EOD
Please follow this link to create your $sitename account:

$defaultbaseurl/signup.php/$hash

--
$sitename
EOD;

		$mailed = mail($mail, "$sitename - Invite", $body, "From: $sitemail");
	}
	else
		mysql_query("UPDATE applications SET accepted = 'no', staffid = $CURUSER[id] WHERE id = $id") or sqlerr(__FILE__, __LINE__);

	header("Location: applications.php");
}

head("Medlemsansökningar");

$totalt = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM applications"));
$nekade = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM applications WHERE accepted = 'no'"));
$accade = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM applications WHERE accepted = 'yes'"));
$pending = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM applications WHERE accepted = 'pending'"));
$svartid = mysql_fetch_row(mysql_query("SELECT SUM(elapsed_time) / COUNT(*) FROM applications WHERE elapsed_time > 0"));
$vantar = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM applications WHERE accepted = 'yes' AND userid = 0"));

print("<table cellpadding=5>");
print("<tr><td class=heading>Totala ansökningar</td><td>$totalt[0]</td></tr>");
print("<tr><td class=heading>Accepterade ansökningar</td><td>$accade[0] ($vantar[0] ej registrerade)</td></tr>");
print("<tr><td class=heading>Nekade ansökningar</td><td>$nekade[0]</td></tr>");
print("<tr><td class=heading>Obesvarade ansökningar</td><td>$pending[0]</td></tr>");
print("<tr><td class=heading>Genomsnittlig svarstid</td><td>" . mkprettytime($svartid[0]) . "</td></tr>");

print("</table>");

begin_frame("Medlemsansökningar");

$res = mysql_query("SELECT * FROM applications ORDER BY id DESC") or sqlerr(__FILE__, __LINE__);

print("<table><tr><td class='colhead'>Mail</td><td class='colhead'>Länk</td><td class='colhead'>Kod</td><td class='colhead'>IP</td><td class='colhead'>Tillagd</td><td class='colhead'>Användarnamn</td><td class='colhead'>Handläggare</td><td class='colhead'>Handling</td></tr>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$stname = mysql_query("SELECT username FROM users WHERE id = $arr[staffid]") or sqlerr(__FILE__, __LINE__);

	if ($name = mysql_fetch_assoc($stname))
		$staffname = "<a href=userdetails.php?id=$arr[staffid]>$name[username]</a>";
	else
		if (!$arr["staffid"])
			$staffname = "<i>Väntar</i>";
		else
			$staffname = "<i>Borttagen</i>";

	$uname = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

	if ($name = mysql_fetch_assoc($uname))
		$username = "<a href=userdetails.php?id=$arr[userid]>$name[username]</a>";
	else
		if (!$arr["userid"])
			if ($arr["accepted"] == 'no')
				$username = "---";
			else
				$username = "<i>Väntar</i>";
		else
			$username = "<i>Borttagen</i>";

	print("<tr style='background-color: " . ($arr["accepted"] == 'yes' ? "#ccffcc" : "#ffcccc") . ";'><td>$arr[mail]</td><td><a target='_blank' href='$arr[link]'>" . cutStr($arr["link"], 25) . "</a></td><td>$arr[code]</td><td><a href='usersearch.php?ip=$arr[ip]'>$arr[ip]</a></td><td>$arr[added]</td><td>$username</td><td>$staffname</td><td" . ($arr["accepted"] == 'pending' ? " style='border: 2px solid red;'" : "") . "><form method='post' action='applications.php'><input type='hidden' name='id' value=$arr[id] /><input type='submit' name='accept' value='Acceptera'" . ($arr["accepted"] != 'pending' ? " disabled" : "") . " /> <input type='submit' name='deny' id='n$arr[id]' value='Neka'" . ($arr["accepted"] != 'pending' ? " disabled" : "") . " /></form></tr>\n");
}

print("</table></div></div>\n");

foot();
?>