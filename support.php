<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	$subject = trim($_POST["subject"]);
	$body = trim($_POST["body"]);
	$dt = get_date_time();
	
	if (!$subject)
		jErr("Du måste ange ett ämne");
		
	if (!$body)
		jErr("Du måste skriva något");
		
	$res = mysql_query("SELECT id FROM staffmessages WHERE userid = $CURUSER[id] AND staffid = 0 LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($res))
		jErr("Du har redan ett obehandlat ärende hos staff");
	
	mysql_query("INSERT INTO staffmessages (userid, added, subject, body) VALUES(" . (implode(", ", array_map("sqlesc", array($CURUSER["id"], $dt, $subject, $body)))) . ")") or sqlerr(__FILE__, __LINE__);
	
	$return["res"] = "Meddelande skickat";
	print(json_encode($return));
	die;
}

head("Support");

begin_frame("Kontakta staff", 600, true);

print("<div id='res'></div>\n");

print("<form method='post' action='support.php' id='supportform'><table>\n");
print("<tr><td><h3>Ämne</h3><input type='text' name='subject' size=60 />\n");
print("<h3 style='margin-top: 10px;'>Meddelande</h3><textarea name='body' style='width: 100%; height: 150px;'></textarea></td></tr>\n");
print("<tr class='clear'><td style='text-align: center;'><div class='errormess'></div><input type='submit' value='Skicka' /></td></tr>\n");
print("</table></form>\n");

print("</div></div>\n");
foot();

?>