<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

if ($_POST)
{
	$username = trim($_POST["username"]);
	$password = $_POST["password"];
	$passwordagain = $_POST["passwordagain"];
	$mail = trim($_POST["mail"]);
	
	if (!$username)
		jErr("Du måste ange ett användarnamn");
		
	$res = mysql_query("SELECT id FROM users WHERE username = " . sqlesc($username)) or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($res))
		jErr("Användernamnet är upptaget");
		
	if (!$password)
		jErr("Du måste ange ett lösenord");
		
	if (strlen($password) < 6)
		jErr("För kort lösenord (minst sex tecken)");
	
	if (strlen($password) > 40)
		jErr("För långt lösenord (max 40 tecken)");
		
	if ($password !== $passwordagain)
		jErr("Lösenorden stämmer inte överens");
		
	if (!$mail)
		jErr("Du måste ange en mailadress");
		
	if (!validmail($mail))
		jErr("Ogiltig mailadress");
		
	$sec = mksecret();
	$passhash = md5($sec . $password . $sec);
	$dt = get_date_time();
	
	stafflog("$CURUSER[username] skapade användaren $username");
	
	mysql_query("INSERT INTO users (username, passhash, secret, confirmed, mail, added, invitedby) VALUES(" . implode(", ", array_map("sqlesc", array($username, $passhash, $sec, 'yes', $mail, $dt, $CURUSER["id"]))) . ")") or sqlerr(__FILE__, __LINE__);
	$return["id"] = mysql_insert_id();
	
	print(json_encode($return));
	die;
}

head("Skapa konto");
begin_frame("Skapa konto", 0, true);

print("<form method='post' action='adduser.php' id='adduserform'><table>\n");

print("<tr><td class='form'>Användarnamn</td><td><input type='text' name='username' /></td></tr>\n");
print("<tr><td class='form'>Lösenord</td><td><input type='password' name='password' /></td></tr>\n");
print("<tr><td class='form'>Repetera lösenord</td><td><input type='password' name='passwordagain' /></td></tr>\n");
print("<tr><td class='form'>Mail</td><td><input type='text' name='mail' /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><span class='errormess' style='margin-right: 10px;'></span><input type='submit' value='Skapa' /></td></tr>\n");

print("</table></form>\n");
print("</div></div>\n");

foot();
?>