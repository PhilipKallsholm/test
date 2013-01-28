<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

if (get_user_class() < UC_POWER_USER)
	stderr("Fel", "Du måste vara minst Power User för att kunna visa dina bokmärken");
	
if ($_POST)
{
	$dels = $_POST["del"];
	
	$res = mysql_query("SELECT id, userid FROM bookmarks WHERE id IN(" . implode(", ", array_map("sqlesc", $dels)) . ")") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		if ($arr["userid"] != $CURUSER["id"])
			stderr("Fel", "Bokmärket är inte ditt");
			
		mysql_query("DELETE FROM bookmarks WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
	}
	
	header("Location: bookmarks.php");
	die;
}

$res = mysql_query("SELECT username FROM users WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

head("Mina bokmärken");

print("<p style='font-size: 9pt; font-weight: bold;'><img src='/pic/rss.png' style='vertical-align: text-bottom;' /> http://www.swepiracy.org/rss.php?passkey={$CURUSER["passkey"]}</p>\n");

print("<h1>Mina bokmärken</h1>\n");

$res = mysql_query("SELECT bookmarks.id as bookmarkid, users.username, users.id as owner, torrents.anonymous, torrents.id, torrents.info_hash, torrents.name, torrents.comments, (SELECT COUNT(*) FROM peers WHERE peers.fid = torrents.id AND `left` = 0 AND active = 1) AS seeders, (SELECT COUNT(*) FROM peers WHERE peers.fid = torrents.id AND `left` > 0 AND active = 1) AS leechers, ROUND(torrents.ratingsum / torrents.numratings) AS rating, categories.name AS cat_name, categories.image AS cat_pic, torrents.save_as, torrents.numfiles, torrents.added, torrents.filename, torrents.size, torrents.freeleech, torrents.req, torrents.imdb_rating, torrents.imdb_genre, torrents.pretime, torrents.views, torrents.visible, torrents.hits, torrents.times_completed, torrents.category FROM bookmarks LEFT JOIN torrents ON bookmarks.torrentid = torrents.id LEFT JOIN users on torrents.owner = users.id LEFT JOIN categories ON torrents.category = categories.id WHERE bookmarks.userid = $CURUSER[id] ORDER BY torrents.id DESC $limit") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<i>Inga bokmärken</i>");
else
{
	print("<form method='post' action='bookmarks.php'>");

	while ($arr = mysql_fetch_assoc($res))
		$tor[] = $arr;
		
	torrenttable($tor, "bookmarks", true);

	print("<br /><input type='button' id='selectall' value='Markera alla' /> <input type='submit' value='Radera' /></form>\n");
}

foot();

?>