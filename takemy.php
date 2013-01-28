<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$showava = $_POST["show_avatars"];
$showsmil = $_POST["show_smilies"];
$showforumsign = $_POST["showforumsign"];
$badava = $_POST["bad_avatar"];
$postspage = 0 + $_POST["postsperpage"];
$topicspage = 0 + $_POST["topicsperpage"];
$forumsign = trim($_POST["forumsign"]);
$torrentspage = 0 + $_POST["torrentsperpage"];
$browse = trim($_POST["browse"]);
$show_covers = trim($_POST["show_covers"]);
$categories = $_POST["categories"];

$notifs = "";

if ($categories)
	foreach ($categories AS $category)
		$notifs .= "[cat$category]";

$acceptpms = $_POST["acceptpms"];
$delpms = $_POST["delpms"];
$savepms = $_POST["savepms"];
$pmperpage = 0 + $_POST["pmperpage"];
$notifo = trim($_POST["notifo"]);
$parked = $_POST["parked"];
$title = trim($_POST["title"]);
$avatar = trim($_POST["avatar"]);
$website = trim($_POST["website"]);
$gender = $_POST["gender"];
$country = 0 + $_POST["country"];
$age = $_POST["age"];
$pres = $_POST["pres"];
$hidetraffic = $_POST["hidetraffic"];
$mail = trim($_POST["mail"]);
$newpassword = $_POST["newpassword"];
$conpassword = $_POST["conpassword"];
$curpassword = $_POST["curpassword"];

$updates["show_avatars"] = $showava;
$updates["show_smilies"] = $showsmil;
$updates["showforumsign"] = $showforumsign;
$updates["bad_avatar"] = $badava;
$updates["postsperpage"] = $postspage;
$updates["topicsperpage"] = $topicspage;
$updates["forumsign"] = $forumsign;
$updates["torrentsperpage"] = $torrentspage;
$updates["browse"] = $browse;
$updates["show_covers"] = $show_covers;
$updates["notifs"] = $notifs;
$updates["acceptpms"] = $acceptpms;
$updates["delpms"] = $delpms;
$updates["savepms"] = $savepms;
$updates["pmperpage"] = $pmperpage;
$updates["parked"] = $parked;

if (get_user_class() >= UC_MARVELOUS_USER || $CURUSER["donor"] == 'yes')
	$updates["title"] = $title;

$updates["avatar"] = $avatar;
$updates["website"] = $website;
$updates["gender"] = $gender;
$updates["country"] = $country;
$updates["pres"] = $pres;
$updates["hidetraffic"] = $hidetraffic;

if ($_POST["passkey"])
	$updates["passkey"] = md5($CURUSER["username"] . get_date_time() . $CURUSER["passhash"]);
	
if ($_POST["anonlinks"])
	mysql_query("UPDATE torrents SET anonymous = 'yes' WHERE owner = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

if ($CURUSER["notifo"] != $notifo)
{
	if ($notifo)
	{
		$res = mysql_query("SELECT id FROM users WHERE notifo LIKE " . sqlesc($notifo)) or sqlerr(__FILE__, __LINE__);
	
		if (mysql_num_rows($res))
			jErr("Det finns redan en användare med samma Notifo-användare");
		
		$sub = notifo_subscribe($notifo);
	
		if ($sub["response_code"] == 2202)
			jErr("Notifo-användaren finns redan tillagd");
		elseif ($sub["response_code"] == 1105)
			jErr("Notifo-användaren finns inte");
		elseif ($sub["status"] == 'error')
			jErr("Det gick inte att aktivera Notifo-användaren ($sub[response_message])");
	}

	$updates["notifo"] = $notifo;
}

if (array_sum($age) && $CURUSER["age"] == '0000-00-00')
{
	$return["errtable"] = 2;
	$return["errfield"] = 5;
		
	foreach ($age AS $a)
		if (!$a)
			jErr("Ogiltigt datum");

	$age = implode("-", $age);
	
	if (!strtotime($age))
		jErr("Ogiltigt datum");
	
	$updates["age"] = $age;
}
	
if ($CURUSER["mail"] != $mail)
{
	$return["errtable"] = 4;
	
	if (!validmail($mail))
	{
		$return["errfield"] = 1;
		jErr("Ogiltig mailadress");
	}
	
	if (md5($CURUSER["secret"] . $curpassword . $CURUSER["secret"]) !== $CURUSER["passhash"])
	{
		$return["errfield"] = 4;
		jErr("Felaktigt lösenord");
	}

	$secret = mksecret();
	$hash = md5($secret . $mail . $secret);
	$url = "$defaultbaseurl/confmail.php/$CURUSER[id]/$hash/" . urlencode($mail);
	$ip = getip();
		
	$body = <<<EOD
Dear Swepiracy member,

{$ip} has requested to change your current mail address to {$mail}.
	
In order to confirm this request, please use the following link:

$url

Best regards Staff
EOD;

	mail($CURUSER["mail"], "$sitename - Confirm new mail address", $body, "From: $sitemail");
	$updates["editsecret"] = $secret;
}
	
if ($newpassword)
{
	$return["errtable"] = 4;
	
	if ($newpassword == $CURUSER["username"])
	{
		$return["errfield"] = 2;
		jErr("Lösenordet får inte vara samma som användarnamnet");
	}
	
	if (strlen($newpassword) < 6)
	{
		$return["errfield"] = 2;
		jErr("För kort lösenord (minst sex tecken)");
	}
	
	if (strlen($newpassword) > 40)
	{
		$return["errfield"] = 2;
		jErr("För långt lösenord (max 40 tecken)");
	}
	
	if ($newpassword !== $conpassword)
	{
		$return["errfield"] = 3;
		jErr("Lösenorden stämmer inte överens");
	}
	
	if (md5($CURUSER["secret"] . $curpassword . $CURUSER["secret"]) !== $CURUSER["passhash"])
	{
		$return["errfield"] = 4;
		jErr("Felaktigt lösenord");
	}
	
	$secret = mksecret();
	$passhash = md5($secret . $newpassword . $secret);
	
	$updates["secret"] = $secret;
	$updates["passhash"] = $passhash;
	
	$exp = time() + 3600 * 24;	
	setcookie("pass", md5($passhash . $_SERVER["REMOTE_ADDR"]), $exp, "/");
}
	
foreach ($updates AS $name => $value)
	$update[] = "$name = " . sqlesc($value);
	
$update = implode(", ", $update);
	
mysql_query("UPDATE users SET $update WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
print(json_encode(""));

?>