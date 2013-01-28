<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("FAQ");

print("<div style='width: 500px; margin: 0px auto; text-align: left;'>\n");

print("<h2 style='margin-bottom: 5px; text-align: left;'>Om oss</h2>\n");
print("<div class='frame'>");
print("Swepiracy är en privat hemsida, vilket innebär att du måste vara en registrerad medlem för att få tillgång till hela siten.<br /><br />Vi är i ständigt behov av donationer då hela siten lever på detta koncept. Genom att donera pengar gör du det möjligt för oss att driva verksamheten vidare och även utvecklas. För vidare information om donationer, klicka <a href='donate.php'><b>här</b></a>.");
print("</div>\n"); 


$res = mysql_query("SELECT * FROM faq ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	print("<h2 id='" . str_ireplace(array("å", "ä", "ö"), array("a", "a", "o"), strtolower($arr["name"])) . "' style='margin: 30px 0px 5px 0px; text-align: left;'>$arr[name]</h2>\n");
	print("<div class='frame'>$arr[body]</div>\n");
	
	if ($arr["edited"] > get_date_time(strtotime("-4 weeks")))
		print("<span style='color: red; font-weight: bold;'>Nyligen uppdaterad</span>");
}

print("</div>\n");

foot();

?>