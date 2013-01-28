<?php

require_once("globals.php");

dbconn();

if ($mail = $_GET["mail"])
	stderr("Registration Successful", "In order to activate your account, please follow the link that has been sent to <b>" . htmlspecialchars($mail) . "</b>.");

$username = $_POST["username"];
$password = $_POST["password"];
$passwordrep = $_POST["passwordrep"];

$invite = mysql_query("SELECT id, inviter, userid, mail FROM invites WHERE hash = " . sqlesc($_POST["i"])) or sqlerr(__FILE__, __LINE__);
$invite = mysql_fetch_assoc($invite);

if (!$invite && time() > strtotime("2013-01-01 00:00:00"))
	jErr("Signups are closed");
	
if ($invite["userid"])
	jErr("Invite already used");

$mail = $invite ? $invite["mail"] : $_POST["mail"];

$dt = get_date_time();
$ip = getip();

if (!validusername($username))
{
	$return["errfield"] = 1;
	jErr("Invalid username");
}

$res = mysql_query("SELECT id FROM users WHERE username = " . sqlesc($username) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);

if (mysql_num_rows($res))
{
	$return["errfield"] = 1;
	jErr("Username already in use");
}

$res = mysql_query("SELECT id FROM users WHERE ip = " . sqlesc($ip) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);

if (mysql_num_rows($res))
	jErr("IP-adressen används redan");

if ($username == $password)
{
	$return["errfield"] = 2;
	jErr("Password and username must not be the same");
}

if (strlen($password) < 6)
{
	$return["errfield"] = 2;
	jErr("Too short password (minimum six characters)");
}

if (strlen($password) > 40)
{
	$return["errfield"] = 2;
	jErr("Too long password (max 40 characters)");
}

if ($password !== $passwordrep)
{
	$return["errfield"] = 3;
	jErr("Passwords do not match");
}

if (!validmail($mail))
{
	$return["errfield"] = 4;
	jErr("Invalid mail address");
}

if (!$_POST["rules"])
{
	$return["errfield"] = 5;
	jErr("You must follow the rules");
}

if (!$_POST["faq"])
{
	$return["errfield"] = 6;
	jErr("You must read the FAQ");
}

if (!$_POST["agreement"])
{
	$return["errfield"] = 7;
	jErr("You must have read, understood and accepted our <a href='/useragreement.php'>Terms of Use</a> to become a member of Swepiracy");
}

if ($_COOKIE["deacct"])
{
	mysql_query("INSERT INTO bans (ip, reason, added) VALUES(" . sqlesc($ip) . ", 'Registrering med bannad kaka', '$dt')");
	mysql_query("INSERT INTO bannedmails (mail, reason, added) VALUES(" . sqlesc($mail) . ", 'Registrering med bannad kaka', '$dt')");
	
	stafflog("$ip försökte registrera sig med bannad kaka och blev därför ip- och mailbannad ($mail - $ip)");
	
	jErr("You are still banned from Swepiracy");
}

$mailban = mysql_query("SELECT id FROM bannedmails WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);

if (mysql_num_rows($mailban))
{
	mysql_query("INSERT INTO bans (ip, reason, added) VALUES(" . sqlesc($ip) . ", 'Registrering med bannad kaka', '$dt')");
	
	stafflog("$ip försökte registrera sig med bannad mail och blev därför cookie- och ipbannad ($mail - $ip)");
	
	jErr("You are still banned from Swepiracy");
}

$sec = mksecret();
$editsec = mksecret();
$passhash = md5($sec . $password . $sec);
$host = gethostbyaddr($ip);
$passkey = md5($username . $dt . $passhash);
$freeleech = get_date_time(strtotime("+12 hours"));

if ($invite)
	mysql_query("INSERT INTO users (username, passhash, secret, passkey, confirmed, editsecret, mail, ip, added, invitedby, freeleech) VALUES(" . implode(", ", array_map("sqlesc", array($username, $passhash, $sec, $passkey, 'yes', $editsec, $mail, $ip, $dt, $invite["inviter"], $freeleech))) . ")") or sqlerr(__FILE__, __LINE__);
else
	mysql_query("INSERT INTO users (username, passhash, secret, passkey, editsecret, mail, ip, added) VALUES(" . implode(", ", array_map("sqlesc", array($username, $passhash, $sec, $passkey, $editsec, $mail, $ip, $dt))) . ")") or sqlerr(__FILE__, __LINE__);
	
$id = mysql_insert_id();

$subject = "Välkommen";
$body = "[size=3]Välkommen till Swepiracy![/size]

Detta är en helt ny version av Swepiracy som varit under konstant utveckling sedan gamla Swepiracy lämnade oss. Databasen är även den helt ny, och vi har i dagsläget ingen möjlighet att återställa gamla konton. 

Den stora skillnaden med nya Swepiracy är att vi [b]inte längre driver en tracker eller lagrar några .torrent-filer[/b]. Istället har ni användare möjligheten att dela torrents direkt mellan varandra i form av magnetlänkar, och vid behov av trackers hänvisar vi till de öppna trackers som finns. Swepiracy fungerar numera med andra ord [b]enbart[/b] som community och statistikräknare.

För att behålla ditt medlemskap på Swepiracy måste din ratio vara lägst [b]1.0[/b] om du har 10 GB nedladdad trafik eller däröver, och för att underlätta din ratiouppbyggnad har nu [b]12 timmars fri leech[/b] blivit aktiverade på ditt konto. Detta innebär att din ratio enbart kommer påverkas positivt under denna period.

Du uppmanas läsa igenom [url=rules.php]regler[/url] och [url=faq.php]FAQ[/url] för mer information innan du börjar utnyttja sidans funktioner.

[i]Staff[/i]";

if ($invite)
	mysql_query("UPDATE invites SET userid = $id WHERE id = $invite[id]") or sqlerr(__FILE__, __LINE__);

mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($id, $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
mysql_query("INSERT INTO iplogg (userid, ip, host, firstseen, lastseen, timesseen) VALUES($id, '$ip', '$host', '$dt', '$dt', 1)") or sqlerr(__FILE__, __LINE__);

$app = mysql_query("SELECT id FROM applications WHERE mail = " . sqlesc($mail) . " AND accepted = 'yes' AND userid = 0") or sqlerr(__FILE__, __LINE__);
$app = mysql_fetch_assoc($app);

if ($app)
	mysql_query("UPDATE applications SET userid = $id WHERE id = $app[id]") or sqlerr(__FILE__, __LINE__);
	
if ($invite)
{
	$exp = strtotime("+1 day");

	setcookie("id", "$id", $exp, "/");
	setcookie("pass", "" . md5($passhash . $ip . $sec) . "", $exp, "/");

	$return["redir"] = "index.php";
}
else
{
	$secret = md5($editsec);

	$body = <<<EOD
In order to activate your account at $sitename, please follow this link:

$defaultbaseurl/confirm.php/$id/$secret

Before using the functions at the site, we encourage you to read our rules and the FAQ.

If it was not you ($ip) registrating this account, please ignore this message.

Best regards Staff
EOD;

	mail($mail, "$sitename - Activation of new account", $body, "From: $sitemail");
	$return["redir"] = "takesignup.php?mail=" . urlencode($mail);
}

print(json_encode($return));
die;

?>