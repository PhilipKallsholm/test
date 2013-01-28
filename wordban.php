<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

if ($_POST)
{
	$word = trim($_POST["word"]);
	
	if (!$word)
		stderr("Fel", "Du måste skriva något");
	
	mysql_query("INSERT INTO bannedwords (word) VALUES(" . sqlesc($word) . ")") or sqlerr(__FILE__, __LINE__);
	
	header("Location: wordban.php");
}

if ($id = 0 + $_GET["del"])
	mysql_query("DELETE FROM bannedwords WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
elseif ($_GET["trunc"])
{
	mysql_query("TRUNCATE TABLE bannedwordslog") or sqlerr(__FILE__, __LINE__);
	
	header("Location: wordban.php");
}

head("Bannade ord");

begin_frame("Banna ord i forum", 0, true);

$res = mysql_query("SELECT * FROM bannedwords ORDER BY word ASC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
	$words[] = $arr["word"] . " (<a href='?del=$arr[id]'>X</a>)";
	
print("<form method='post' action='wordban.php'><table>\n");
print("<tr><td colspan=2>" . ($words ? implode(", ", $words) : "<i>Det finns inga bannade ord</i>") . "</td></tr>\n");
print("<tr><td><input type='text' name='word' size=30 /></td><td><input type='submit' value='Lägg till' /></td></tr>\n");
print("</table></form>\n");
print("</div></div><br />\n");

begin_frame("Logg (<a href='?trunc=1'>töm</a>)", 500);

$res = mysql_query("SELECT * FROM bannedwordslog ORDER BY added DESC") or sqlesc(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<i>Loggen är tom</i>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
	
	if ($user = mysql_fetch_assoc($user))
		$username = "<a href='userdetails.php?id=$arr[userid]'>$user[username]</a>";
	else
		$username = "<i>Borttagen</i>";
		
	$topic = mysql_query("SELECT name FROM topics WHERE id = $arr[topicid]") or sqlerr(__FILE__, __LINE__);
	
	if ($topic = mysql_fetch_assoc($topic))
		$topic = "<a href='forums.php/viewtopic/$arr[topicid]'>$topic[name]</a>";
	else
		$topic = "ny tråd";

	print("$arr[added] (" . get_elapsed_time($arr["added"]) . " sedan) av $username i $topic\n");
	print("<div style='margin-bottom: 10px; padding: 5px; background-color: white; border: 2px solid red;'>" . format_comment($arr["body"]) . "</div>\n");
}

print("</div></div>\n");

foot();

?>