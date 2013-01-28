<?php

require_once("globals.php");

dbconn();

$user = mysql_query("SELECT id FROM users WHERE passkey = " . sqlesc($_GET["passkey"]) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysql_fetch_assoc($user);

if (!$user)
	die;
	
if ($user["parked"] == 'yes')
	die;

$req = $_GET["req"];
$new = $_GET["new"];
$arc = $_GET["arc"];

if ($_GET["new"])
	$sect[] = "torrents.req = 0";

if ($_GET["req"])
	$sect[] = "torrents.req = 1";

if ($_GET["arc"])
	$sect[] = "torrents.req = 2";

if ($sect)
	$wherea[] = "(" . implode(" OR ", $sect) . ")";

$dd = $_GET["dd"] ? "yes" : "no";

$rating = $_GET["ra"];
$rating = str_replace(",", ".", $rating);

if (is_numeric($rating))
{
	$rating = number_format($rating, 1, '.', '');
	$oun = $_GET["our"] ? "<" : ">";

	$wherea[] = "torrents.imdb_rating $oun $rating";
}

$count = 0 + $_GET["co"];

if (!$count)
	$count = 15;
elseif ($count > 50)
	$count = 50;

if ($genre = trim($_GET["gen"]))
	$wherea[] = "torrents.imdb_genre LIKE '%" . mysql_real_escape_string($genre) . "%'";

if ($search = trim($_GET["sea"]))
{
	$search = str_replace(" ", ".", $search);
	
	$wherea[] = "torrents.name LIKE '%" . mysql_real_escape_string($search) . "%'";
}

if ($cats = $_GET["cat"])
{
	$cats = explode(",", $cats);

	foreach ($cats as $c)
		if (is_numeric($c))
			$cat[] = $c;

	$wherea[] = "(torrents.category IN(" . implode(",", $cat) . "))";
}

if ($_GET["fl"])
	$wherea[] = "torrents.freeleech = 'yes'";

$size = 0 + $_GET["si"];

if ($size)
{
	$pref = 0 + $_GET["pr"];

	switch ($pref)
	{
		case 1:
			$x = 1073741824;
			break;
		case 2:
			$x = 1099511627776;
			break;
		default:
			$x = 1048576;
	}

	$size = round($size * $x);

	$oun = $_GET["ous"] ? "<" : ">";
	
	$wherea[] = "torrents.size $oun $size";
}

if ($_GET["cat"]) 
	$sqlstr = "SELECT torrents.id, torrents.info_hash, torrents.name, lower(torrents.filename) AS filename, torrents.descr, torrents.category, UNIX_TIMESTAMP(torrents.added) AS added , categories.name AS catname, cattype.name AS cattype FROM torrents LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN cattype ON categories.cattype = cattype.id WHERE " . implode(" AND ", $wherea) . " ORDER BY torrents.added DESC LIMIT $count";
else
	$sqlstr = "SELECT torrents.id, torrents.info_hash, torrents.name, lower(torrents.filename) AS filename, torrents.descr, torrents.category, UNIX_TIMESTAMP(torrents.added) AS added , categories.name AS catname, cattype.name AS cattype FROM torrents LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN cattype ON categories.cattype = cattype.id LEFT JOIN bookmarks ON torrents.id = bookmarks.torrentid LEFT join users ON bookmarks.userid = users.id WHERE users.passkey = " . sqlesc($_GET["passkey"]) . " ORDER BY torrents.added DESC";
	
header("Content-Type: application/xml");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

print("<?xml version=\"1.0\" encoding=\"utf-8\"?>");
print("<rss version=\"2.0\">");
print("<channel>");
print("<title>Swepiracy - " . ($_GET["cat"] ? "Länkar" : "Bokmärken") . "</title>");
print("<link>http://www.swepiracy.org</link>");
print("<description>RSS Feed</description>");
print("<language>en-us</language>");
print("<ttl>60</ttl>");

$passkey = $_GET["passkey"];
	
$trackers[] = "http://stats.swepiracy.org:80/" . $passkey;
$trackers[] = "udp://tracker.openbittorrent.com:80";
$trackers[] = "udp://tracker.publicbt.com:80";
$trackers[] = "udp://tracker.ccc.de:80";
$trackers[] = "udp://tracker.istole.it:80";

$res = mysql_query($sqlstr) or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	$name = htmlspecialchars($arr["name"]);
	$cat = htmlspecialchars($arr["catname"]);
	$cattype = htmlspecialchars($arr["cattype"]);
 
	$id = $arr["id"];
	//$filename = urlencode($arr["filename"]);
	$link = $dd == 'yes'|| !$_GET["cat"] ? htmlspecialchars("magnet:?xt=urn:btih:" . bin2hex($arr["info_hash"]) . "&dn={$arr["name"]}&tr=" . implode("&tr=", $trackers)) : "http://www.swepiracy.org/details.php?id=" . $id;
	$descr = "<a href='$detaillink'>Details</a>";

	print("<item>");
	print("<title>$name</title>");
	print("<pubDate>" . date ("r", $arr["added"]) . "</pubDate>");
	print("<description>$cattype - $cat</description>");
	print("<link>$link</link>");
	print("</item>");
}
 
print("</channel>");
print("</rss>");

die();
?>