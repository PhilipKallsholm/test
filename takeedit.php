<?php

require_once("globals.php");
require_once("imdb_class.php");

$id = 0 + $_POST["id"];

if (!$id)
	stderr("Fel", "Ogiltigt ID");

dbconn();
loggedinorreturn();

$res = mysql_query("SELECT owner, filename, anonymous, freeleech, save_as, category, req, pretime FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_array($res);

if (!$row)
	stderr("Fel", "Länken hittades inte");

if ($CURUSER["id"] != $row["owner"] && !staff())
	stderr("Fel", "Du har inte behörighet att ändra länken");

$fname = $row["filename"];
preg_match('/^(.+)\.torrent$/si', $fname, $matches);
$shortfname = $matches[1];
$dname = $row["save_as"];

$name = trim($_POST["name"]);
$descr = trim($_POST["descr"]);

if (preg_match("/^.+v=(.+)$/i", $_POST["youtube"], $matches))
	$youtube = $matches[1];

$freeleech = $_POST["freeleech"];

$nuked = $_POST["nuked"] == 'yes' ? "yes" : "no";
$nukedreason = trim($_POST["nukedreason"]);
$anonymous = $_POST["anonymous"] == 'yes' ? "yes" : "no";
$p2p = $_POST["p2p"] == 'yes' ? "yes" : "no";
$musicgenre = trim($_POST["music"]);	
$section = 0 + $_POST["section"];
$type = 0 + $_POST["type"];
$subs = $_POST["languages"];
$pretime = get_pre_time($name);
$visible = $_POST["visible"] ? "yes" : "no";

$cattype = mysql_query("SELECT cattype.name FROM categories LEFT JOIN cattype ON categories.cattype = cattype.id WHERE categories.id = $type") or sqlerr(__FILE__, __LINE__);
$cattype = mysql_fetch_assoc($cattype);

if (!$name)
	stderr("Fel", "Du måste ange ett namn");
	
if (!$descr)
	stderr("Fel", "Du måste ange en beskrivning");
	
if (!$type)
	stderr("Fel", "Du måste ange en kategori");
	
if (!$musicgenre && $cattype["name"] == 'Music')
	stderr("Fel", "Du måste ange en musikkategori");
	
if ($nuked == 'yes' && !$nukedreason)
	stderr("Fel", "Du måste ange en orsak till nukningen");
	
if (preg_match("/tt\d+/i", $_POST["imdb"], $matches))
	$imdb = $matches[0];
	
if ($imdb)
{
	$im = new imdb($imdb);
	
	$title = $im->title();
	$year = $im->year();
	$rating = $im->rating();
	$plot = $im->plot();
	$genres = implode(" / ", $im->genres());
	$tagline = $im->tagline();
	$stars = implode(", ", $im->stars());
	$poster = $im->saveposter();
	$im->saveposter(false);
	$recs = implode(", ", $im->recommendations());
}

if ($cattype["name"] == 'Music')
	$genres = $musicgenre;

$nfo = $descr;
$parsed_nfo = "";
$descr = "";

for ($i = 0; $i < strlen($nfo); $i++)
{
	if (ord($nfo[$i]) >= 32 && ord($nfo[$i]) <= 127)
		$parsed_nfo .= $nfo[$i];
	elseif (ord($nfo[$i]) == 10 || ord($nfo[$i]) == 13)
		$parsed_nfo .= "\n";
}

$parsed_nfo = explode("\n", $parsed_nfo);

for ($i = 0; $i < count($parsed_nfo); $i++)
{
	if (trim($parsed_nfo[$i]) || trim($parsed_nfo[$i+1]))
		$descr .= trim($parsed_nfo[$i])."\n";
}

$updateset = array();

$updateset[] = "name = " . sqlesc($name);
$updateset[] = "descr = " . sqlesc($descr);
$updateset[] = "imdb = " . sqlesc($imdb);
$updateset[] = "imdb_title = " . sqlesc($title);
$updateset[] = "imdb_year = " . sqlesc($year);
$updateset[] = "imdb_rating = " . sqlesc($rating);
$updateset[] = "imdb_plot = " . sqlesc($plot);
$updateset[] = "imdb_genre = " . sqlesc($genres);
$updateset[] = "imdb_tagline = " . sqlesc($tagline);
$updateset[] = "imdb_stars = " . sqlesc($stars);
$updateset[] = "imdb_poster = " . sqlesc($poster);
$updateset[] = "imdb_recs = " . sqlesc($recs);
$updateset[] = "youtube = " . sqlesc($youtube);

if (staff() && $freeleech)
	$updateset[] = "freeleech = " . sqlesc($freeleech);
elseif (!$row["req"] && $section == 2)
	$updateset[] = "freeleech = 'yes'";
elseif ($row["req"] && !$section)
	$updateset[] = "freeleech = 'no'";

$updateset[] = "nuked = " . sqlesc($nuked);
$updateset[] = "nukedreason = " . sqlesc($nukedreason);
$updateset[] = "anonymous = " . sqlesc($anonymous);

if (staff())
	$updateset[] = "p2p = " . sqlesc($p2p);

if (staff() || !$row["req"])
	$updateset[] = "req = $section";
	
$updateset[] = "category = $type";
$updateset[] = "subs = " . sqlesc(implode(",", $subs));
$updateset[] = "pretime= " . sqlesc($pretime);
$updateset[] = "search_text = " . sqlesc(searchfield("$shortfname $dname $torrent"));
$updateset[] = "visible = " . sqlesc($visible);

mysql_query("UPDATE torrents SET " . implode(", ", $updateset) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);

write_log("<b>" . $name . "</b> ändrades av NAMNET", $CURUSER["username"], ($anonymous == 'yes' && $CURUSER["id"] == $row["owner"] ? true : false));

header("Location: details.php?id=$id&edited=1");

?>
