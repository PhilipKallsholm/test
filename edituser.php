<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

$id = 0 + $_POST["id"];

$user = mysql_query("SELECT * FROM users WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$user = mysql_fetch_assoc($user);

if (!$user)
	die;

if (get_user_class() < UC_MODERATOR || $user["class"] >= get_user_class() && get_user_class() < UC_SYSOP)
	die("Tillgång nekad");
	
if ($_POST["del"])
{
	deleteuser($user["id"]);
	stafflog("$CURUSER[username] tog bort användaren $user[username]");
	stderr("Raderad", "<b>$user[username]</b> har blivit raderad");
}
	
$enabled = ($_POST["enabled"] == 'yes' ? "yes" : "no");
$username = trim($_POST["username"]);
$password = $_POST["password"];
$passwordagain = $_POST["passwordagain"];
$mail = trim($_POST["mail"]);
$class = 0 + $_POST["class"];
$betadmin = trim($_POST["betadmin"]);
$title = trim($_POST["title"]);
$doubleupload = (!$_POST["doubleupload"] || !strtotime($_POST["doubleupload"]) || strtotime($_POST["doubleupload"]) < time()) ? "0000-00-00 00:00:00" : $_POST["doubleupload"];
$freeleech = (!$_POST["freeleech"] || !strtotime($_POST["freeleech"]) || strtotime($_POST["freeleech"]) < time()) ? "0000-00-00 00:00:00" : $_POST["freeleech"];
$uploaded = 0 + $_POST["uploaded"];
$upchange = $_POST["upchange"];
$formatuploaded = 0 + $_POST["formatuploaded"];
$downloaded = 0 + $_POST["downloaded"];
$downchange = $_POST["downchange"];
$formatdownloaded = 0 + $_POST["formatdownloaded"];
$bonus = 0 + $_POST["seedbonus"];
$warned = ($_POST["warned"] == 'yes' ? "yes" : "no");
$warnlength = 0 + $_POST["warnlength"];
$warnreason = trim($_POST["warnreason"]);
$donor = ($_POST["donor"] == 'yes' ? "yes" : "no");
$crown = ($_POST["crown"] == 'yes' ? "yes" : "no");
$donated = $_POST["donated"];
$forumrights = ($_POST["forumrights"] == 'yes' ? "yes" : "no");
$pmrights = ($_POST["pmrights"] == 'yes' ? "yes" : "no");
$age = $_POST["age"];
$avatar = trim($_POST["avatar"]);
$website = trim($_POST["website"]);
$pres = $_POST["pres"];

$modcomment = $user["modcomment"];

if ($enabled != $user["enabled"])
{
	$modcomment = get_date_time() . " - " . ($enabled == 'yes' ? "Aktiverad" : "Inaktiverad") . " av $CURUSER[username]\n" . $modcomment;
	$update[] = "enabled = " . sqlesc($enabled);
	
	stafflog("$CURUSER[username] " . ($enabled == 'yes' ? "aktiverade" : "inaktiverade") . " $user[username]");
}

if ($username != $user["username"])
{
	if (!validusername($username))
		jErr("Ogiltigt användarnamn");
		
	$modcomment = get_date_time() . " - Användarnamnet ($user[username]) ändrades av $CURUSER[username]\n" . $modcomment;
	$update[] = "username = " . sqlesc($username);
	
	stafflog("$CURUSER[username] döpte om $user[username] till $username");
}

if ($password)
{
	if (strlen($password) < 6)
		jErr("För kort lösenord (minst sex tecken)");
		
	if (strlen($password) > 40)
		jErr("För långt lösenord (max 40 tecken)");

	if ($password !== $passwordagain)
		jErr("Lösenorden stämmer inte överens");
		
	$sec = mksecret();
	$passhash = md5($sec . $password . $sec);
	
	$modcomment = get_date_time() . " - Lösenordet ändrades av $CURUSER[username]\n" . $modcomment;
	
	$update[] = "secret = " . sqlesc($sec);
	$update[] = "passhash = " . sqlesc($passhash);
	
	stafflog("$CURUSER[username] ändrade lösenordet till $user[username]");
}

if ($mail != $user["mail"])
{
	if (!validmail($mail))
		jErr("Ogiltig mailadress");
		
	$modcomment = get_date_time() . " - Mail ändrad av $CURUSER[username]\n" . $modcomment;
	$update[] = "mail = " . sqlesc($mail);
	
	stafflog("$CURUSER[username] ändrade mailen till $user[username]");
}

if ($class != $user["class"])
{
	mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($user[id], '" . get_date_time() . "', '" . ($class > $user["class"] ? "Uppgraderad" : "Degraderad") . "', 'Du har blivit " . ($class > $user["class"] ? "uppgraderad" : "degraderad") . " till " . get_user_class_name($class) . " av $CURUSER[username]')") or sqlerr(__FILE__,  __LINE__);
	
	$modcomment = get_date_time() . " - " . ($class > $user["class"] ? "Uppgraderad" : "Degraderad") . " till " . get_user_class_name($class) . " av $CURUSER[username]\n" . $modcomment;
	$update[] = "class = " . sqlesc($class);
	
	if ($class == 6)
		$update[] = "last_upload = " . sqlesc(get_date_time());
	
	stafflog("$CURUSER[username] " . ($class > $user["class"] ? "uppgraderade" : "degraderade") . " $user[username] från " . get_user_class_name($user["class"]) . " till " . get_user_class_name($class));
}

if ($doubleupload != $user["doubleupload"] && (strtotime($user["doubleupload"]) > time() || strtotime($doubleupload) > time()))
{		
	if ($doubleupload > $user["doubleupload"])
	{
		$modcomment = get_date_time() . " - Dubbelt uppladdat aktiverat till $doubleupload av $CURUSER[username]\n" . $modcomment;
		stafflog("$CURUSER[username] gav $user[username] dubbelt uppladdat till $doubleupload");
	}
	else
	{
		$modcomment = get_date_time() . " - Dubbelt uppladdat " . (strtotime($doubleupload) > time() ? "förkortat till $doubleupload" : "inaktiverat") . " av $CURUSER[username]\n" . $modcomment;
		stafflog("$CURUSER[username] " . (strtotime($doubleupload) > time() ? "förkortade $user[username] dubbelt uppladdat till $doubleupload" : "inaktiverade $user[username] dubbelt uppladdat"));
	}
	
	$update["doubleupload"] = "doubleupload = " . sqlesc($doubleupload);
}

if ($freeleech != $user["freeleech"] && (strtotime($user["freeleech"]) > time() || strtotime($freeleech) > time()))
{		
	if ($freeleech > $user["freeleech"])
	{
		$modcomment = get_date_time() . " - Fri leech aktiverat till $freeleech av $CURUSER[username]\n" . $modcomment;
		stafflog("$CURUSER[username] gav $user[username] fri leech till $freeleech");
	}
	else
	{
		$modcomment = get_date_time() . " - Fri leech " . (strtotime($freeleech) > time() ? "förkortat till $freeleech" : "inaktiverat") . " av $CURUSER[username]\n" . $modcomment;
		stafflog("$CURUSER[username] " . (strtotime($freeleech) > time() ? "förkortade $user[username] fri leech till $freeleech" : "inaktiverade $user[username] fri leech"));
	}
	
	$update["freeleech"] = "freeleech = " . sqlesc($freeleech);
}

if ($uploaded)
{
	$newuploaded = $user["uploaded"] . ($upchange == 'plus' ? "+" : "-") . $uploaded * $formatuploaded;
	
	$modcomment = get_date_time() . " - " . mksize($uploaded * $formatuploaded) . " " . ($upchange == 'plus' ? "tillagda" : "bortdragna") . " av $CURUSER[username]\n" . $modcomment;
	stafflog("$CURUSER[username] " . ($upchange == 'plus' ? "gav" : "drog bort") . " $user[username] " . mksize($uploaded * $formatuploaded) . " uppladdat");

	$update[] = "uploaded = " . $newuploaded;
}

if ($downloaded)
{
	$newdownloaded = $user["downloaded"] . ($downchange == 'plus' ? "+" : "-") . $downloaded * $formatdownloaded;
	
	$modcomment = get_date_time() . " - " . mksize($downloaded * $formatdownloaded) . " " . ($downchange == 'plus' ? "tillagda" : "bortdragna") . " av $CURUSER[username]\n" . $modcomment;
	stafflog("$CURUSER[username] " . ($downchange == 'plus' ? "gav" : "drog bort") . " $user[username] " . mksize($downloaded * $formatdownloaded) . " nedladdat");

	$update[] = "downloaded = " . $newdownloaded;
}

if ($bonus != $user["seedbonus"])
{
	if ($bonus > $user["seedbonus"])
	{
		$modcomment = get_date_time() . " - " . ($bonus - $user["seedbonus"]) . " bonuspoäng tillagda av $CURUSER[username]\n" . $modcomment;
		stafflog("$CURUSER[username] gav $user[username] " . ($bonus - $user["seedbonus"]) . " bonuspoäng");
	}
	else
	{
		$modcomment = get_date_time() . " - " . ($user["seedbonus"] - $bonus) . " bonuspoäng bortdragna av $CURUSER[username]\n" . $modcomment;
		stafflog("$CURUSER[username] drog bort " . ($user["seedbonus"] - $bonus) . " bonuspoäng från $user[username]");
	}
		
	$update[] = "seedbonus = " . sqlesc($bonus);
}

if ($warned != $user["warned"])
{
	$update[] = "warned = " . sqlesc($warned);
	
	if ($warned == 'yes')
	{
		$until = get_date_time(strtotime("+$warnlength weeks"));
		$dur = "$warnlength veck" . ($warnlength > 1 ? "or" : "a");
		
		$update[] = "warned_until = " . sqlesc($until);
		$update[] = "warned_reason = " . sqlesc($warnreason);
		$update[] = "warned_by = " . sqlesc($CURUSER["id"]);
		$update[] = "times_warned = times_warned + 1";
		$update[] = "last_warned = " . sqlesc(get_date_time());
		
		$modcomment = get_date_time() . " - Varnad i $dur av $CURUSER[username] ($warnreason)\n" . $modcomment;
		
		$subject = "Du har blivit varnad";
		$msg = "Du har fått en varning ($dur) av $CURUSER[username] på grund av $warnreason.";
		
		stafflog("$CURUSER[username] varnade $user[username] i $dur ($warnreason)");
	}
	else
	{
		$update[] = "warned_until = '0000-00-00 00:00:00'";
		$update[] = "permban = 'no'";
		
		$modcomment = get_date_time() . " - Varning borttagen av $CURUSER[username]\n" . $modcomment;
		
		$subject = "Din varning har blivit borttagen";
		$msg = "Din varning togs bort av $CURUSER[username].";
		
		stafflog("$CURUSER[username] benådade $user[username] varning");
	}
	
	mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($user[id], '" . get_date_time() . "', " . sqlesc($subject) . ", " . sqlesc($msg) . ")") or sqlerr(__FILE__, __LINE__);
}

if ($crown != $user["crown"])
{
	$modcomment = get_date_time() . " - Krona " . ($crown == 'yes' ? "tillagd" : "borttagen") . " av $CURUSER[username]\n" . $modcomment;
	$update[] = "crown = " . sqlesc($crown);
	
	stafflog("$CURUSER[username] " . ($crown == 'yes' ? "gav" : "tog bort") . " $user[username] en krona");
}

if ($forumrights != $user["forumrights"])
{
	$modcomment = get_date_time() . " - Forumrättigheter " . ($forumrights == 'yes' ? "aktiverade" : "inaktiverade") . " av $CURUSER[username]\n" . $modcomment;
	$update[] = "forumrights = " . sqlesc($forumrights);
	
	stafflog("$CURUSER[username] tog bort forumrättigheterna från $user[username]");
}

if ($pmrights != $user["pmrights"])
{
	$modcomment = get_date_time() . " - PM-rättigheter " . ($pmrights == 'yes' ? "aktiverade" : "inaktiverade") . " av $CURUSER[username]\n" . $modcomment;
	$update[] = "pmrights = " . sqlesc($pmrights);
	
	stafflog("$CURUSER[username] tog bort PM-rättigheterna från $user[username]");
}

$update[] = "betadmin = " . sqlesc($betadmin);
$update[] = "title = " . sqlesc($title);

$update[] = "modcomment = " . sqlesc($modcomment);

if (get_user_class() >= UC_SYSOP)
{
	$update[] = "donor = " . sqlesc($donor);
	$update[] = "donated = " . sqlesc($donated);
}

$update[] = "age = " . sqlesc($age);
$update[] = "avatar = " . sqlesc($avatar);
$update[] = "website = " . sqlesc($website);
$update[] = "pres = " . sqlesc($pres);

mysql_query("UPDATE users SET " . implode(", ", $update) . " WHERE id = $user[id]") or sqlerr(__FILE__, __LINE__);
//header("Location: userdetails.php?id=$id");

print(json_encode(""));
die;

?>
