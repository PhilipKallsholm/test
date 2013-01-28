<?php

require_once("globals.php");

dbconn();

if ($_POST)
{
	$mail = trim($_POST["mail"]);
	
	if (!$mail)
		jErr("You must enter a valid mail address");
	
	$res = mysql_query("SELECT id FROM users WHERE mail = " . sqlesc($mail)) or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if (!$arr)
		jErr("Unregistered mail address");
		
	$sec = mksecret();
	$hash = md5($sec . $mail . $sec);
	$ip = getip();
	
	mysql_query("UPDATE users SET editsecret = '$sec' WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
	
	$body = <<<EOD
$ip has requested to reset the user password belonging to $mail.

If you wish to proceed, please follow this link:

$defaultbaseurl/recover.php?id={$arr["id"]}&secret=$hash

When confirmed, your password will be reset and sent back to you.

--
$sitename
EOD;

	mail($mail, "$sitename - Recover password", $body, "From: $sitemail");
	$return["result"] = "<h2>Success</h2>A confirmation mail has been sent. Please allow a few minutes for the mail to arrive.";
	
	print(json_encode($return));
	die;
}

if ($_GET)
{
	$id = 0 + $_GET["id"];
	$hash = trim($_GET["secret"]);
	
	$res = mysql_query("SELECT username, mail, editsecret FROM users WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if ($hash !== md5($arr["editsecret"] . $arr["mail"] . $arr["editsecret"]))
		die;
	
	$pass = mkpassword();
	$sec = mksecret();
	$passhash = md5($sec . $pass . $sec);
	
	mysql_query("UPDATE users SET passhash = '$passhash', secret = '$sec', editsecret = '' WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	
	$body = <<<EOD
A new password has been generated to your account.

Please use following user details to access your account:

    Username: {$arr["username"]}
    Password: $pass

You are now able to login on $defaultbaseurl/login.php

--
$sitename
EOD;

	$mail = mail($arr["mail"], "$sitename - Account details", $body, "From: $sitemail");
	
	if (!$mail)
		stderr("Fel", "Något gick fel, dina användardetaljer har inte blivit skickade");
	
	head("Recover");
	begin_frame("Success");
	
	print("The new account details have been sent to <b>$arr[mail]</b>. Please allow a few minutes for the mail to arrive.");
	print("</div></div>\n");
	
	foot();
	die;
}

head("Recover");
begin_frame("Recover lost username and/or password", 600);

print("<span class='small' style='color: red;'>Use the this form to have your password reset and your account details mailed back to you. You will receive a mail with instructions that you must follow in order to get your account details. If no mail arrives, add $sitemail to \"safe addresses\" and try again. Request only <u>once</u> and wait until the mail has arrived - multiple requests will only require extra time. Be careful with upper-/lower-case letters.</span><br /><br />\n");

print("<form method='post' action='recover.php' id='recoverform'><table>\n");
print("<tr class='main'><td class='form'>Registered mail</td><td><input type='text' name='mail' size=30 /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><div class='errormess'></div><input type='submit' value='Recover' id='recover' /></td></tr>\n");
print("</table></form>\n");

print("</div></div>\n");
foot();
?>