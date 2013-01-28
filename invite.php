<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

if ($_POST)
{
	$mail = trim($_POST["mail"]);
	$hash = md5(mksecret() . time());
	$time = get_date_time();

	/*if ($CURUSER["invites"] < 1)
		stderr("Fel", "Du har inte några invites kvar.");*/

	if (!validmail($mail))
		jErr("Du måste ange en giltig mailadress");

	$res = mysql_query("SELECT id FROM invites WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($res))
		jErr("En invite har redan blivit skickad till personen");

	$res = mysql_query("SELECT id FROM users WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($res))
		jErr("Mailen finns redan registrerad");

	mysql_query("INSERT INTO invites (inviter, hash, mail, added) VALUES($CURUSER[id], " . sqlesc($hash) . ", " . sqlesc($mail) . ", " . sqlesc($time) . ")") or sqlerr();
	$id = mysql_insert_id();
	//mysql_query("UPDATE users SET invites = invites - 1 WHERE id = $CURUSER[id]") or sqlerr();

	$body = <<<EOD
Please follow this link to create your $sitename account:

$defaultbaseurl/signup.php/$hash

--
$sitename
EOD;

	$mailed = mail($mail, "$sitename - Invite", $body, "From: $sitemail");
	
	if (!$mailed)
		stafflog("En invite misslyckades att skickas");
	
	$invites = mysql_query("SELECT id FROM invites WHERE inviter = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
	$return["id"] = $id;
	$return["res"] = "<tr id='i$id'><td>" . mysql_num_rows($invites) . " - <i>Väntar</i></td><td>$hash</td><td>$mail</td><td>$time</td><td><a class='jlink' onClick='delinvite($id)'>X</a></td></tr>\n";

	print(json_encode($return));
	die;
}

if ($_GET["del"])
{
	$id = 0 + $_GET["del"];

	$res = mysql_query("SELECT inviter, userid FROM invites WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	$res = mysql_fetch_assoc($res);
	
	if (!$res)
		jErr("Inviten finns inte");

	if ($res["inviter"] != $CURUSER["id"])
		jErr("Inviten är inte din");

	if ($res["userid"])
		jErr("Inviten är redan förbrukad");

	mysql_query("DELETE FROM invites WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	//mysql_query("UPDATE users SET invites = invites + 1 WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
	print(json_encode(""));
	die;
}

head("Invites");

print("<div class='frame' style='border: 4px solid red; width: 600px; font-size: 10pt; font-weight: bold;'>\n");
print("<ul><!-- <li>Du bär det yttersta ansvaret för den du bjuder in. Missköter sig personen resulterar det i påföljder för dig, i värsta fall permanent avstängning.</li> -->\n");
print("<li style='font-size: 12pt;'>Du uppmuntras bjuda in alla gamla Swepiracy-medlemmar du känner!</li>\n");
print("<li>Bjud enbart in personer du känner.</li>\n");
print("<li>Det är inte tillåtet att sälja invites.</li>\n");
print("<li>Det är inte tillåtet att byta invites.</li>\n");
print("<li>Det är inte tillåtet att ge bort invites i öppna forum.</li></ul>");
print("</div>\n");
print("<br /><br />\n");

print("<div class='errormess'></div>\n");
print("<form method='post' action='invite.php' id='inviteform'><table cellpadding=10><tr><td align=center><!-- <h2>Invites: {$CURUSER["invites"]}</h2><br /> --><input type='text' name='mail' value='Mail...' style='color: gray; font-style: italic;' onClick=\"this.value=''; this.style.fontStyle='normal'; this.style.color='#444444';\" /> <input type='submit' value='Skicka invite' id='sendinvite' /></td></tr></table></form>\n");

print("<br /><br />\n");

print("<table id='sentinvites'><tr><td class='colhead'>#</td><td class='colhead'>Hash</td><td class='colhead'>Mail</td><td class='colhead'>Tillagd</td><td class='colhead'>X</td></tr>\n");

$res = mysql_query("SELECT * FROM invites WHERE inviter = $CURUSER[id] ORDER BY id ASC") or sqlerr(__FILE__, __LINE__);

$i = 1;
while ($arr = mysql_fetch_assoc($res))
{
	if ($arr["userid"])
	{
		$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

		if ($user = mysql_fetch_assoc($user))
			$username = "<a href=userdetails.php?id=$arr[userid]>$user[username]</a>";
		else
			$username = "<i>Borttagen</i>";
	}
	else
		$username = "<i>Väntar</i>";

	print("<tr" . ($arr["userid"] ? " style='background-color: #ccffcc'" : " style='background-color: #ffcccc'") . " id='i$arr[id]'><td>" . $i++ . " - $username</td><td>$arr[hash]</td><td>$arr[mail]</td><td>$arr[added]</td><td>" . ($arr["userid"] ? "<span style='color: gray;'>X</span>" : "<a class='jlink' onClick='delinvite($arr[id])'>X</a>") . "</td></tr>\n");
}

print("</table>\n");

foot();

?>