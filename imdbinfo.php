<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$id = 0 + $_POST["id"];

$res = mysql_query("SELECT * FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr)
	die;
	
for ($i = 1; $i <= 10; $i++)
{
	if ($i <= round($arr["imdb_rating"]))
		$stars .= "<img src='/pic/goldstar.gif' />";
	else
		$stars .= "<img src='/pic/greystar.gif' />";
}

$cover = $arr["imdb_poster"] && file_exists("$imdb_large_dir/" . $arr["imdb_poster"]);
	
print("<img src='" . ($cover ? "/getimdb.php/$arr[imdb_poster]?l=1" : "/pic/noposter.png") . "'" . (!$cover ? " style='border: 0px; padding: 5px 0px 0px 5px;'" : "") . " />\n");
print("<div><h2 style='margin-bottom: 5px;'>$arr[imdb_title]" . ($arr["imdb_year"] ? " ($arr[imdb_year])" : "") . "</h2>\n");
print($stars . "\n");

if ($arr["imdb_genre"])
	print("<h3 style='margin-top: 10px; margin-bottom: 5px;'>$arr[imdb_genre]</h3>\n");
	
if ($arr["imdb_stars"])
	print($arr["imdb_stars"] . "\n");

if ($arr["imdb_plot"])
	print("<span class='imdbplot'><i>$arr[imdb_plot]</i></span>\n");
	
print("</div>\n");
print("<div>" . cutStr($arr["name"], 55) . "</div>\n");
?>