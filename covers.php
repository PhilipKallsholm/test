<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	$id = 0 + $_POST["id"];
	$link = trim($_POST["link"]);
	
	$res = mysql_query("SELECT owner FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if (!$arr)
		stderr("Fel", "Länken finns inte");
		
	if (!staff() && $CURUSER["id"] != $arr["owner"])
		stderr("Fel", "Du har inte behörighet att ändra cover på denna länk");
	
	$li = fopen($link, "rb");
	
	if (!$li)
		stderr("Fel", "Bilden kunde inte läsas");
	
	$cover = "";
	while (!feof($li))
		$cover .= fread($li, 8192);
		
	fclose($li);
		
	$cover = imagecreatefromstring($cover);
	imagepng($cover, "/var/covers/{$id}.png");
	
	header("Location: details.php?id=$id&" . ($_GET["uploaded"] ? "uploaded=1" : "edited=1"));
}

$id = 0 + $_GET["id"];

$res = mysql_query("SELECT torrents.name, torrents.owner, torrents.imdb_poster, cattype.name AS cattype FROM torrents LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN cattype ON categories.cattype = cattype.id WHERE torrents.id = $id") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr)
	stderr("Fel", "Länken finns inte");
	
if (!staff() && $CURUSER["id"] != $arr["owner"])
	stderr("Fel", "Du har inte behörighet att ändra cover på denna länk");
	
if ($_GET["del"])
{
	unlink("$covers_dir/$id.png");
	header("Location: details.php?id=$id&edited=1");
}

if ($_GET["ignore"])
	header("Location: details.php?id=$id" . ($_GET["uploaded"] ? "&uploaded=1" : ""));

head("Covers");

if ($arr["imdb_poster"])
	print("<img src='getimdb.php/$arr[imdb_poster]' />\n");

print("<h2>Välj cover till <i>$arr[name]</i></h2>\n");

$name = trim($_GET["search"]);

if (!$name)
{
	preg_match("#^(.+?)(\d{4}|[A-Z]{3,}|S\d{1,2})#", $arr["name"], $matches);
	$name = strtolower(trim(str_replace(".", " ", $matches[1])));
}

print("<form method='get' action='covers.php" . ($_GET["uploaded"] ? "?uploaded=1" : "") . "'>\n");
print("<input type='hidden' name='id' value=$id /><input type='text' name='search' size=60 value='$name' /> <input type='submit' value='Sök' /> <input type='submit' name='ignore' value='Hoppa över' />\n");
print("</form>\n");

$data = file_get_contents("http://www.discshop.se/advanced_search.php?title=" . urlencode($name) . "&action=search&type=" . ($arr["cattype"] == 'Games' ? "games" : "movies") . "&page_size=200");
$data = substr($data, 0, strpos($data, "<div class=\"right_promo\">"));

preg_match_all("#s(\d)\.discshop\.se/img/front_small/(\d+)/(\w+)\.jpg#i", $data, $matches);

for ($i = 0; $i < count($matches[0]); $i++)
{
	$link = "http://s" . $matches[1][$i] . ".discshop.se/img/front_large/" . $matches[2][$i] . "/" . $matches[3][$i] . ".jpg";
	
	print("<div style='width: 400px; margin: 10px auto;'><form method='post' action='covers.php" . ($_GET["uploaded"] ? "?uploaded=1" : "") . "'>\n");
	print("<img src='$link' /><br /><input type='hidden' name='id' value=$id /><input type='hidden' name='link' value='$link' /><input type='submit' name='add' value='Lägg till' />\n");
	print("</form></div>\n");
}
	
foot();
?>