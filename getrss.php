<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

if ($_POST)
{
	if (!$_POST["cat"])
		jErr("Du måste ange några kategorier");

	foreach ($_POST AS $name => $value)
		$wherea[] = "$name=" . ($name == 'cat' ? implode(",", $value) : $value);
		
	$wherea[] = "passkey=$CURUSER[passkey]";
		
	$where = implode("&amp;", $wherea);
	
	$return["link"] = "http://www.swepiracy.org/rss.php?$where";
	
	print(json_encode($return));
	die;
}

head("Skapa RSS-feed");

print("<img src='/pic/rss.jpg' />\n");
print("<h1>Skapa RSS-feed</h1>\n");

print("<div id='rss'></div>\n");
print("<div class='errormess'></div>\n");

$cats = genrelist();

$cattype = mysql_query("SELECT * FROM cattype ORDER BY name") or sqlerr(__FILE__, __LINE__);
$catsperrow = 4;

$c = 0;
$ca = "<table class='clear' style='margin-bottom: 10px;'>\n<tr>";
while ($type = mysql_fetch_assoc($cattype))
{
	$c++;
	$ca .= "<td title='$type[name]' style='padding-bottom: 2px; padding-left: 7px; vertical-align: top; background-color: " . ($c % 2 ? "#ffffff" : "#f1f1f1") . ";'><a class='jlink' onclick=\"catMark('$type[name]')\" style='font-size: 10pt;'>$type[name]</a>";

	$i = 0;
	foreach ($cats as $cat)
	{
		if ($cat["cattype"] == $type["id"])
		{	
			if ($i && $i % $catsperrow == 0)
				$ca .= "</td><td title='$type[name]' style='padding-bottom: 2px; padding-left: 7px; vertical-align: top; background-color: " . ($c % 2 ? "#ffffff" : "#f1f1f1") . ";'>";
			
			$ca .= "<br /><input name='cat[]' type='checkbox' value=$cat[id] />" . htmlspecialchars($cat["name"]) . "\n";
			$i++;
		}
	}
	$ca .= "</td>";
}
$ca .= "</tr></table>";

print("<form method='post' action='getrss.php' id='rssform'>\n");
print($ca);
print("<table>\n");
print("<tr><td class='form'>Visa nytt</td><td><input type='checkbox' name='new' value=1 /></td></tr>\n");
print("<tr><td class='form'>Visa requests</td><td><input type='checkbox' name='req' value=1 /></td></tr>\n");
print("<tr><td class='form'>Visa arkiv</td><td><input type='checkbox' name='arc' value=1 /></td></tr>\n");
print("<tr><td class='form'>Antal</td><td><input type='text' name='co' size=10 value=15 /></td></tr>\n");
print("<tr><td class='form'>Nyckelord</td><td><input type='text' name='sea' size=10 /></td></tr>\n");
print("<tr><td class='form'>Genre</td><td><input type='text' name='gen' size=10 /></td></tr>\n");
print("<tr><td class='form'>Storlek</td><td><select name='ous'><option value=0>Över</option><option value=1>Under</option></select> <input type='text' name='si' size=5 /> <select name='pr'><option value=0>MB</option><option value=1>GB</option></select></td></tr>\n");
print("<tr><td class='form'>Betyg</td><td><select name='our'><option value=0>Över</option><option value=1>Under</option></select> <input type='text' name='ra' size=5 /></td></tr>\n");
print("<tr><td class='form'>Direkt nedladdning</td><td><input type='checkbox' name='dd' value=1 /></td></tr>\n");
print("<tr><td class='form'>Fri nedladdning</td><td><input type='checkbox' name='fl' value=1 /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' id='submit' value='Generera länk' /></td></tr>\n");
print("</table>\n");
print("</form>\n");

foot();
?>