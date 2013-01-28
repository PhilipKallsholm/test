<?php

require_once("globals.php");

ini_set("upload_max_filesize", $max_sub_size);

function bark($msg) {
	stderr($msg, "Upload failed!");
}

dbconn(); 
loggedinorreturn();

$id = 0 + $_POST["id"];
$title = trim($_POST["title"]);
$lang = trim($_POST["language"]);
$file = $_FILES["file"];

if (!$id)
	bark("Torrent-ID saknas");

if (!$file)
	bark("Fil saknas");

$fname = trim($file["name"]);

if (!$fname)
	bark("Filnamn saknas");
	
if (!validfilename($fname))
	bark("Ogiltigt filnamn");
	
if (!preg_match("#^(.+)(\.txt|\.srt|\.sub|\.rar)$#i", $fname, $matches))
	bark("Ogiltig fil (inte en sub- eller rarfil)");
	
if (!$title)
	$title = $matches[1];
		
if (!$lang)
	bark("Välj språk");
	
$tmpname = $file["tmp_name"];

if (!is_uploaded_file($tmpname))
	bark("Filen kunde inte laddas upp");
	
$size = filesize($tmpname);
	
if (!$size)
	bark("Filen är tom");

$res = mysql_query("SELECT * FROM subs WHERE torrentid = $id AND lang = " . sqlesc($lang)) or sqlerr(__FILE__, __LINE__);

if (mysql_num_rows($res))
	bark("Undertext i samma språk redan uppladdad");

$ret = mysql_query("INSERT INTO subs (torrentid, userid, name, added, file, size, lang) VALUES(" . implode(", ", array_map("sqlesc", array($id, $CURUSER["id"], $title, get_date_time(), $fname, $size, $lang))) . ")") or sqlerr(__FILE__, __LINE__);

if (!$ret)
{
	if (mysql_errno() == 1062)
		bark("Undertexten finns redan");
	bark("mysql puked: " . mysql_error());
}

move_uploaded_file($tmpname, "$sub_dir/$fname");

header("Location: details.php?id=$id&subuploaded=1");

?>