<?php

require_once("globals.php");

dbconn();
staffacc(UC_SYSOP);
loggedinorreturn();

if ($_POST)
{
	$classes = $_POST["classes"];
	$subject = trim($_POST["subject"]);
	$body = trim($_POST["body"]);
	$sender = 0 + $_POST["sender"];
	$dt = get_date_time();
	
	if (!$classes)
		jErr("Du måste ange minst en användarklass");
		
	if (!$subject)
		jErr("Du måste ange ett ämne");
		
	if (!$body)
		jErr("Du måste skriva något");
		
	$users = mysql_query("SELECT id FROM users WHERE class IN(" . implode(", ", $classes) . ")") or sqlerr(__FILE__, __LINE__);
	
	while ($user = mysql_fetch_assoc($users))
		mysql_query("INSERT INTO messages (receiver, sender, added, subject, body) VALUES($user[id], " . ($sender ? $CURUSER["id"] : 0) . " ,'$dt', " . sqlesc($subject) . ", " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
		
	print(json_encode(""));
	die;
}

head("Massmeddelande");
begin_frame("Massmeddelande", 0, true);

$classes = "<table>\n<tr class='clear'>";

$i = 0;
while (get_user_class_name($i))
{
	$classes .= "<td style='padding: 2px;" . ($i % 2 ? " background-color: #ededed;" : "") . "'><input type='checkbox' name='classes[]' value=$i style='vertical-align: text-bottom;' /> " . get_user_class_name($i) . "</td>";
	
	if (($i + 1) % 4 == 0)
		$classes .= "</tr>\n<tr class='clear'>";
		
	$i++;
}

$classes .= "</tr>\n</table>\n";

foreach ($smilies AS $key => $url)
	$smil .= "<img class='smilie' title='" . htmlent($key) . "' src='/smilies/$url' />";

print("<form method='post' action='staffmess.php' id='staffmessform'><table>\n");
print("<tr><td class='form' style='vertical-align: top;'>Skicka till</td><td>$classes</td></tr>\n");
print("<tr><td class='form'>Ämne</td><td><input type='text' name='subject' style='width: 100%;' /></td></tr>\n");
print("<tr><td><div id='smilies' class='smilstandard smilactive' style='height: 230px;'>$smil</div></td><td><textarea name='body' rows=15 style='width: 100%;'></textarea></td></tr>\n");
print("<tr><td class='form'>Avsändare</td><td style='text-align: center;'><input type='radio' name='sender' value=$CURUSER[id] />$CURUSER[username] <input type='radio' name='sender' value=0 checked /><i>System</i></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><span class='errormess' style='margin-right: 10px;'></span><input type='submit' value='Skicka' /></td></tr>\n");
print("</table></form>\n");

print("</div></div>\n");
foot();

?>