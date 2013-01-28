<?

require_once("globals.php");

dbconn();
loggedinorreturn();

$req = 0 + substr($_SERVER["PATH_INFO"], 1);

if ($req)
{
	$req = mysql_query("SELECT * FROM requests WHERE id = $req AND uploaderid = 0") or sqlerr(__FILE__, __LINE__);
	$req = mysql_fetch_assoc($req);
	
	if (!$req)
		stderr("Fel", "Requesten finns inte");
		
	if (get_user_class() < UC_POWER_USER)
		stderr("Fel", "Du måste vara lägst Power User för att ladda upp requests");
}

head("Ladda upp " . ($req ? "request" : "länk"));

if ($req)
	print("<h1>Ladda upp request $req[name]</h1>\n");
else
	print("<h1>Ladda upp länk</h1>\n");

print("<div style='width: 400px; margin: 0px auto 10px auto; padding: 10px; background-color: #ffcccc; border: 1px solid rgba(0, 0, 0, 0.1); font-size: 9pt; font-weight: bold;'><ul>\n");
print("<li>Swepiracy lagrar inte längre några .torrent-filer, och som uppladdare genereras och möjliggörs din torrent endast vid ett tillfälle i samband med uppladdning.
Det är därför viktigt att då välja <u>spara</u>, om torrenten av någon anledning vid ett senare tillfälle skulle behövas läggas till på nytt i din klient.</li>\n");
print("<li>Torrents behöver <u>inte</u> skapas om för att bli kompatibla med Swepiracy, detta sker per automatik vid uppladdning.</li>\n");
print("</ul></div>\n");

print("<form method='post' enctype='multipart/form-data' action='/takeupload.php' id='uploadform'>\n");
print("<input type='hidden' name='MAX_FILE_SIZE' value=$max_torrent_size />\n");
print("<table>\n");

if (get_user_class() < UC_VIP)
{
	$s = "<input type='radio' name='section' value=2 checked />Arkiv\n";
	$s .= "&nbsp;(<a href=support.php>Bli uppladdare</a>)";
}
else
{
	$s = "<input type='radio' name='section' value=0 checked />Nytt\n";
	$s .= "<input type='radio' name='section' value=2 />Arkiv\n";
}

print("<tr><td class='form'>Avdelning</td><td>" . ($req ? "<input type='radio' name='section' value=1 checked /><input type='hidden' name='req' value=$req[id] />Request" : $s) . "</td></tr>\n");
print("<tr><td class='form'>Torrentfil</td><td><input type='file' name='file' size=80 /><br /><span class='small'>(Behöver <b>inte</b> packas om; trackerbyte sker automatiskt)</span></td></tr>\n");
print("<tr><td class='form'>.nfo</td><td><input type='file' name='nfo' size=80 /><br /><span class='small'>(Strippas om automatiskt)</span></td></tr>\n");
print("<tr><td class='form'>Namn</td><td><input type='text' name='name'" . ($req && !$req["link"] ? " value='$req[name]'" : "") . " size=80 /><br /><span class='small'>(Tas från filnamnet om det inte specificeras)</span></td></tr>\n");
print("<tr><td class='form'>IMDb</td><td><input type='text' name='imdb'" . ($req["link"] ? " value='$req[link]'" : "") . " size=80 /><br /><span class='small'>(Tas från .nfo om inkluderad)</span></td></tr>\n");
print("<tr><td class='form'>Youtube</td><td><input type='text' name='youtube' size=80 /></td></tr>\n");
print("<tr><td class='form'>Anonym</td><td><input type='checkbox' name='anonymous' value=1 style='vertical-align: text-bottom;' /> <i>Visa inte mitt användarnamn</i></td></tr>\n");
//print("<tr><td class='form'>Fri nedladdning</td><td><input type=checkbox name=freeleech value=yes />Fri nerladdning</td></tr>\n");

$s = "<select name='type'>\n<option value=0>(Välj en)</option>\n";

$cattypes = mysql_query("SELECT * FROM cattype ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($cattype = mysql_fetch_assoc($cattypes))
{
	$s .= "<optgroup label='$cattype[name]'>";

	$categories = mysql_query("SELECT id, name, cattype FROM categories WHERE cattype = $cattype[id] ORDER BY name") or sqlerr(__FILE__, __LINE__);

	while ($row = mysql_fetch_assoc($categories))
		$s .= "<option value=$row[id]" . ($row["id"] == $req["category"] ? " selected" : "") . ">$row[name]</option>\n";
		
	$s .= "</optgroup>";
}

$s .= "</select>";

$m = "<select name='music'>\n<option value=0>(Välj en)</option>\n";

$categories = mysql_query("SELECT id, name FROM music ORDER BY name") or sqlerr(__FILE__, __LINE__);

while ($row = mysql_fetch_assoc($categories))
	$m .= "<option value='" . htmlent($row["name"]) . "'>$row[name]</option>\n";

$m .= "</select>";

$langs = array("swe", "eng", "nor", "dan", "fin");

foreach ($langs AS $lang)
	$hdlangs[] = "<input type='checkbox' name='languages[]' value='$lang' /><img src='/pic/subflags/small/" . subflag($lang) . ".png' title='" . subflag($lang) . "' />";

print("<tr><td class='form'>Typ</td><td>$s<div id='music' style='display: none; margin-top: 10px;'>$m</div><div id='language' style='display: none; margin-top: 10px;'>" . implode(" ", $hdlangs) . "</div></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' class='btn' value='Lägg till' /></td></tr>\n");
print("</table></form>\n");

foot();

?>