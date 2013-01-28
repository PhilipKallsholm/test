<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$torrent = $_FILES["file"];
$nfo = $_FILES["nfo"];

if ($torrent["name"])
{	
	if (!validfilename($torrent["name"]))
		jErr("Ogiltigt filnamn");

	if (!preg_match("/^(.+)\.torrent$/si", $torrent["name"], $matches))
		jErr("Ogiltig fil (inte en .torrent)");

	$return["releasename"] = $matches[1];
	
	unlink($torrent["tmp_name"]);
}

if ($nfo["name"])
{
	if (!preg_match("/^(.+)\.nfo$/si", $nfo["name"], $matches))
		jErr("Ogiltig fil (inte en .nfo)");

	if (preg_match("/imdb\.com\/title\/(tt\d+)/i", file_get_contents($nfo["tmp_name"]), $matches))
		$return["imdb"] = "http://www.imdb.com/title/" . $matches[1];
		
	unlink($nfo["tmp_name"]);
}

print(json_encode($return));
die;

?>