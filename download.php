<?php

require_once("globals.php");
require_once("BDecode.php");
require_once("BEncode.php");

dbconn();

if ($CURUSER["laddaner"] == 'no')
	stderr("Fel", "Dina nedladdningsrättigheter har blivit inaktiverade");
	
$id = 0 + substr($_SERVER["PATH_INFO"], 1);

if (!$id)
	stderr("Fel", "Ogiltigt ID");
	
$res = mysql_query("SELECT info_hash, name, owner FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_assoc($res);

if (!$row)
	stderr("Fel", "Länken finns inte");
	
if ($CURUSER && strlen($CURUSER["passkey"]) != 32)
{
	$CURUSER["passkey"] = md5($CURUSER["username"] . get_date_time() . $CURUSER["passhash"]);
	mysql_query("UPDATE users SET passkey = '$CURUSER[passkey]' WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
}

$passkey = trim($_GET["passkey"]);

if (!$passkey)
	$passkey = $CURUSER["passkey"];
	
$user = mysql_query("SELECT id FROM users WHERE passkey = " . sqlesc($passkey) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($user))
	stderr("Fel", "Ingen användare med matchande passkey");
	
if (strlen($passkey) != 32)
	stderr("Fel", "Ogiltig passkey");

$trackers[] = "http://stats.swepiracy.org:80/" . $passkey;
$trackers[] = "udp://tracker.openbittorrent.com:80";
$trackers[] = "udp://tracker.publicbt.com:80";
$trackers[] = "udp://tracker.ccc.de:80";
$trackers[] = "udp://tracker.istole.it:80";
	
$link = "magnet:?xt=urn:btih:" . bin2hex($row["info_hash"]) . "&dn={$row["name"]}&tr=" . implode("&tr=", array_map("urlencode", $trackers));

mysql_query("INSERT INTO snatched_links (torrentid, userid, added) VALUES($id, $CURUSER[id], '" . get_date_time() . "')");

if ($CURUSER["id"] == $row["owner"] && file_exists("/var/torrents/$id.torrent"))
{
	$torrent = BDecode(file_get_contents("/var/torrents/$id.torrent"));
	$torrent["announce"] = "http://stats.swepiracy.org:80/" . $passkey;
	$torrent["announce-list"][0][0] = "http://stats.swepiracy.org:80/" . $passkey;
	
	unlink("/var/torrents/$id.torrent");
	
	header("Content-Type: application/x-bittorrent");
	
	print(BEncode($torrent));
}
elseif ($_GET["show"])
	print($link);
else
	header("refresh: 0; url=$link");

/*if (!preg_match("/^/(\d{1,10})/(.+)\.torrent$/", $_SERVER["PATH_INFO"], $matches))
	stderr("Ingen torrentfil");

$id = 0 + $matches[1];

if (!$id)
	stderr("Fel", "Ogiltigt ID");

$res = mysql_query("SELECT name FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_assoc($res);

$fn = "$torrent_dir/$id.torrent";

if (!$row || !is_file($fn) || !is_readable($fn))
	stderr("Fel", "Torrenten finns inte");

mysql_query("UPDATE torrents SET hits = hits + 1 WHERE id = $id");

if (strlen($CURUSER["passkey"]) != 32)
{
	$CURUSER["passkey"] = md5($CURUSER["username"] . get_date_time() . $CURUSER["passhash"]);
	mysql_query("UPDATE users SET passkey = '$CURUSER[passkey]' WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
}

$passkey = $_GET["passkey"];

if (!$_GET["passkey"])
	$passkey = $CURUSER['passkey'];

$dict = BDecode(file_get_contents($fn));

//$dict['value']['announce']['value'] = "$DOWNLOADURL/announce.php?passkey=$passkey";
$dict['value']['announce']['value'] = "$DOWNLOADURL:$announceport/$passkey/announce";
$dict['value']['announce']['string'] = strlen($dict['value']['announce']['value']).":".$dict['value']['announce']['value'];
$dict['value']['announce']['strlen'] = strlen($dict['value']['announce']['string']);

$dict["announce"] = "$DOWNLOADURL:$announceport/$passkey/announce";

header("Content-Type: application/x-bittorrent");

print(BEncode($dict));*/

?>
