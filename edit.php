<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$id = 0 + $_GET["id"];

if (!$id)
	stderr("Fel", "Ogiltigt id");

$res = mysql_query("SELECT torrents.*, cattype.name AS cattype FROM torrents LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN cattype ON categories.cattype = cattype.id WHERE torrents.id = $id");
$row = mysql_fetch_array($res);

if (!$row)
	stderr("Fel", "Länken finns inte");

head("Ändra länk \"" . $row["name"] . "\"");

if ($CURUSER["id"] != $row["owner"] && get_user_class() < UC_MODERATOR)
	stderr("Fel", "Du har inte behörighet att ändra denna länk");
	
print("<h1>Ändra länk</h1>\n");

print("<form method='post' action='takeedit.php' enctype='multipart/form-data'>\n");
print("<input type='hidden' name='id' value=$id />\n");

print("<table>\n");
print("<tr><td class='form'>Namn</td><td><input type='text' name='name' value='$row[name]' size=80 /></td></tr>\n");
print("<tr><td class='form'>Beskrivning</td><td><textarea name='descr' rows=10 cols=80>$row[descr]</textarea><br /><span class='small'>(HTML/BB-kod är <b>inte</b> tillåtet)</span></td></tr>\n");
print("<tr><td class='form'>IMDb</td><td><input type='text' name='imdb' size=80 value='" . ($row["imdb"] ? "http://www.imdb.com/title/$row[imdb]/" : "") . "' /></td></tr>\n");
print("<tr><td class='form'>Youtube</td><td><input type='text' name='youtube' size=80 value='" . ($row["youtube"] ? "http://www.youtube.com/watch?v=$row[youtube]" : "") . "' /></td></tr>\n");

if (get_user_class() >= UC_SYSOP)
	print("<tr><td class='form'>Fri nedladdning</td><td><input type='radio' name='freeleech' value='yes'" . ($row["freeleech"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='freeleech' value='no'" . ($row["freeleech"] != 'yes' ? " checked" : "") . " />Nej</td></tr>\n");

print("<tr><td class='form'>Nukad</td><td><input type='radio' name='nuked' value='yes'" . ($row["nuked"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='nuked' value='no'" . ($row["nuked"] != 'yes' ? " checked" : "") . " />Nej <input type='text' name='nukedreason' size=60 value='$row[nukedreason]' /></td></tr>\n");
print("<tr><td class='form'>Anonym</td><td><input type='radio' name='anonymous' value='yes'" . ($row["anonymous"] == 'yes' ? " checked" : "" ) . " />Ja <input type='radio' name='anonymous' value='no'" . ($row["anonymous"] != 'yes' ? " checked" : "" ) . " />Nej</td></tr>\n");

if (staff())
	print("<tr><td class='form'>p2p</td><td><input type='radio' name='p2p' value='yes'" . ($row["p2p"] == 'yes' ? " checked" : "" ) . " />Ja <input type='radio' name='p2p' value='no'" . ($row["p2p"] != 'yes' ? " checked" : "" ) . " />Nej</td></tr>\n");

if ($row["req"] != 1)
{
	$s = "<select name='section'>\n";

	if (get_user_class() >= UC_VIP || !$row["req"])
		$s .= "<option value=0" . (!$row["req"] ? " selected" : "") . ">Nytt</option>";

	$s .= "<option value=2" . ($row["req"] ? " selected" : "") . ">Arkiv</option>";
	$s .= "</select>\n";
}
else
	$s = "Request";

print("<tr><td class='form'>Avdelning</td><td>$s</td></tr>\n");

$s = "<select name='type'>\n";

$cattypes = mysql_query("SELECT * FROM cattype ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($cattype = mysql_fetch_assoc($cattypes))
{
	$s .= "<optgroup label='$cattype[name]'>\n";

	$categories = mysql_query("SELECT id, name, cattype FROM categories WHERE cattype = $cattype[id] ORDER BY name");

	while ($cat = mysql_fetch_assoc($categories))
		$s .= "<option value=$cat[id] id='k$cat[id]'" . ($cat["id"] == $row["category"] ? " selected" : "") . ">$cat[name]</option>\n";

	$s .= "</optgroup>\n";
}

$s .= "</select>\n";

$m = "<select name='music'>\n<option value=0>(Välj en)</option>\n";

$categories = mysql_query("SELECT id, name FROM music ORDER BY name") or sqlerr(__FILE__, __LINE__);

while ($mus = mysql_fetch_assoc($categories))
	$m .= "<option value='" . htmlent($mus["name"]) . "'" . ($mus["name"] == $row["imdb_genre"] ? " selected" : "") . ">$mus[name]</option>\n";

$m .= "</select>";

$subs = explode(",", $row["subs"]);
$langs = array("swe", "eng", "nor", "dan", "fin");

foreach ($langs AS $lang)
	$hdlangs[] = "<input type='checkbox' name='languages[]' value='$lang'" . (in_array($lang, $subs) ? " checked" : "") . " /><img src='/pic/subflags/small/" . subflag($lang) . ".png' title='" . subflag($lang) . "' />";
	
print("<tr><td class='form'>Typ</td><td>$s<div id='music' style='" . ($row["cattype"] != 'Music' ? "display: none; " : "") . "margin-top: 10px;'>$m</div><div id='language' style='" . ($row["cattype"] != 'HD' ? "display: none; " : "") . " margin-top: 10px;'>" . implode(" ", $hdlangs) . "</div></td></tr>\n");
print("<tr><td class='form'>Synlig</td><td><input type='checkbox' name='visible' value=1" . ($row["visible"] == 'yes' ? " checked" : "" ) . " /> Synlig på sidan<br /><div style='width: 420px; margin: 0px auto; padding: 5px; background-color: white; border-radius: 5px;'>Notera att torrenten automatiskt kommer bli synlig då det finns minst en seedare, och kommer automatiskt bli osynlig (död) när det inte har funnits seedare på en stund. Använd detta alternativ för att snabba på processen manuellt.</div>\n");
print("<tr><td colspan=2 style='text-align: center;'><input type='submit' class='btn' value='Ändra!' /> <input type='reset' class='btn' value='Återställ' /></td></tr>\n");
print("</table>\n");
print("</form>\n");

if (strtotime($row["added"]) > strtotime("-1 hours") || get_user_class() >= UC_MODERATOR)
{
	print("<form method='post' action='delete.php'>\n");
	print("<br /><table>\n");
	print("<tr style='background-color: #F5F4EA;'><td class='form'>Radera torrent</td><td class='form'>Orsak</td></tr>\n");
	print("<tr><td><input type='radio' name='reasontype' value=1 /> Död</td><td>0 seedare, 0 leechare = 0 peers totalt</td></tr>\n");
	print("<tr><td><input type='radio' name='reasontype' value=2 /> Dupe</td><td><input type='text' name='reason[]' size=40 /></td></tr>\n");
	print("<tr><td><input type='radio' name='reasontype' value=3 /> Nukad</td><td><input type='text' name='reason[]' size=40 /></td></tr>\n");
	print("<tr><td><input type='radio' name='reasontype' value=4 /> Regelbrott</td><td><input type='text' name='reason[]' size=40 /></td></tr>\n");
	print("<tr><td><input type='radio' name='reasontype' value=5 checked /> Annat</td><td><input type='text' name='reason[]' size=40 /></td></tr>\n");
	print("<tr><td colspan=2 style='text-align: center;'><input type='hidden' name='id' value=$id /><input type='submit' class='btn' value='Radera!' /></td></tr>\n");
	print("</table>\n");
	print("</form>\n");
}

foot();

?>