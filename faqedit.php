<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_SYSOP)
	die;
	
if ($_POST)
{
	$id = 0 + $_POST["id"];
	$name = trim($_POST["name"]);
	$body = trim($_POST["body"]);
	
	if ($_GET["edit"])
	{	
		if (!$id)
			stderr("Fel", "Ogiltigt ID");
			
		if (!$name && !$body)
			mysql_query("DELETE FROM faq WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		else
			mysql_query("UPDATE faq SET name = " . sqlesc($name) . ", body = " . sqlesc($body) . ", edited = '" . get_date_time() . "' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
			
		header("Location: faqedit.php?edited=1");
	}
	else
	{
		if (!$name)
			stderr("Fel", "Du måste ange en rubrik");
			
		if (!$body)
			stderr("Fel", "Du måste skriva något");
			
		mysql_query("INSERT INTO faq (name, body) VALUES(" . implode(", ", array_map("sqlesc", array($name, $body))) . ")") or sqlerr(__FILE__, __LINE__);
		
		header("Location: faqedit.php?added=1");
	}
	
}
	
head("FAQ-editeraren");
	
begin_frame("FAQ-editeraren", 500);

print("<form method='post' action='faqedit.php'><table>\n");
print("<tr class='main'><td><input type='text' name='name' size=50 /></td></tr>\n");
print("<tr class='main'><td><textarea name='body' cols=50 rows=10></textarea></td></tr>\n");
print("<tr class='clear'><td style='text-align: center;'><input type='submit' value='Skapa' /></td></tr>\n");
print("</table></form>\n");

$res = mysql_query("SELECT * FROM faq ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	print("<form method='post' action='faqedit.php?edit=1'><table style='margin-top: 10px;'>\n");
	print("<tr class='main'><td><input type='hidden' name='id' value=$arr[id] /><input type='text' name='name' value='$arr[name]' size=50 /></td></tr>\n");
	print("<tr class='main'><td><textarea name='body' cols=50 rows=10>$arr[body]</textarea></td></tr>\n");
	print("<tr class='clear'><td style='text-align: center;'><input type='submit' value='Uppdatera' /></td></tr>\n");
	print("</table></form>\n");
}

print("</div></div>\n");

foot();
?>