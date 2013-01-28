<?

require_once("globals.php");
require_once("imdb_class.php");
require_once("BDecode.php");
require_once("BEncode.php");

ini_set("upload_max_filesize", $max_torrent_size);

function bark($msg) {
	stderr($msg, "Uppladdningen misslyckades!");
}

dbconn(); 
loggedinorreturn();

$req = 0 + $_POST["req"];

if ($req)
{
	$req = mysql_query("SELECT * FROM requests WHERE id = $req") or sqlerr(__FILE__, __LINE__);
	$req = mysql_fetch_assoc($req);
	
	if (!$req)
		bark("Requesten finns inte");
		
	if ($req["uploaderid"])
		bark("Requesten är redan uppladdad");
		
	if (get_user_class() < UC_POWER_USER)
		bark("Du måste vara lägst Power User för att ladda upp requests");
}

if (!isset($_FILES["file"]))
	bark("Torrentfil saknas");

if (!isset($_FILES["nfo"]))
	bark(".nfo saknas");

$f = $_FILES["file"];
$fname = trim($f["name"]);

if (!$fname)
    bark("Filnamn saknas");
	
if (!validfilename($fname))
	bark("Ogiltigt filnamn");

if (!preg_match("/^(.+)\.torrent$/si", $fname, $matches))
	bark("Ogiltig fil (inte en .torrent)");

$shortfname = $torrent = $matches[1];

if ($_POST["name"])
	$torrent = trim($_POST["name"]);
	
$dt = get_date_time();
	
if (!preg_match("/(-\w+$)|(FLAC)/i", $torrent, $matches))
{
	$stafflog = "$CURUSER[username] försökte ladda upp en icke-scenrelease ($torrent)";
	mysql_query("INSERT INTO stafflog (added, txt) VALUES(" . implode(", ", array_map("sqlesc", array($dt, $stafflog))) . ")") or sqlerr(__FILE__, __LINE__);
	
	bark("Ej scenrelease");
}

$tmpname = $f["tmp_name"];

if (!is_uploaded_file($tmpname))
	bark("Ogiltig uppladdning");
	
if (!filesize($tmpname))
	bark("Tom fil");

$fp = fopen($tmpname, "rb");

if (!$fp)
	bark("Torrenten går inte att läsa");

$data = BDecode(fread($fp, $max_torrent_size));

if (strlen($data["info"]["pieces"]) % 20 != 0)
	bark("Ogiltiga delar");
	
if (!count($data["info"]["files"]))
	bark("Filer saknas");
	
$trackers[] = "http://stats.swepiracy.org:80";
$trackers[] = "udp://tracker.openbittorrent.com:80";
$trackers[] = "udp://tracker.publicbt.com:80";
$trackers[] = "udp://tracker.ccc.de:80";
$trackers[] = "udp://tracker.istole.it:80";

$data["announce"] = $trackers[0];

foreach ($trackers AS $i => $tracker)
	$data["announce-list"][$i][0] = $tracker;
	
unset($data["info"]["private"]);

$infohash = sha1(BEncode($data["info"]), true);
$filelist = $data["info"]["files"];

fclose($fp);

$fp = fopen($tmpname, "w+");
fwrite($fp, BEncode($data));
fclose($fp);

$size = 0;	
foreach ($filelist AS $file)
	$size += $file["length"];

$torrent = str_replace("_", " ", $torrent);

$res = mysql_query("SELECT id FROM torrents WHERE name = " . sqlesc($torrent) . " OR info_hash = " . sqlesc($infohash) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);

if (mysql_num_rows($res))
	bark("Länken finns redan");

$nfo = file_get_contents($_FILES["nfo"]["tmp_name"]);
$nfoname = trim($_FILES["nfo"]["name"]);

if (!preg_match("/^(.+)\.nfo$/si", $nfoname, $matches2))
	bark("Ogiltig fil (inte en .nfo)");

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

if (!$descr)
	bark("Beskrivning saknas");

$cat = 0 + $_POST["type"];
$genres = trim($_POST["music"]);

if (get_user_class() < UC_VIP && !$req)
	$section = 2;
else
	$section = 0 + $_POST["section"];

if ($cat == 17 && !$req)
	$section = 0;

if (!$cat)
	bark("Kategori saknas");
	
$p2p = 'no';
$p2pgroups = mysql_query("SELECT `group` FROM p2p") or sqlerr(__FILE__, __LINE__);

while ($p2pgroup = mysql_fetch_assoc($p2pgroups))
	if (substr($torrent, -strlen($p2pgroup["group"])) == $p2pgroup["group"])
	{
		$p2p = 'yes';
		break;
	}
	
$cattype = mysql_query("SELECT cattype.name FROM categories LEFT JOIN cattype ON categories.cattype = cattype.id WHERE categories.id = $cat") or sqlerr(__FILE__, __LINE__);
$cattype = mysql_fetch_assoc($cattype);
	
if (!$genres && $cattype == 'Music')
	bark("Musikgenre saknas");

if ($cat == 14 || $cat == 17 || $section == 2)
	$freeleech = 1;
	
if ($req["link"] && $req["link"] != $_POST["imdb"])
	bark("Länken stämmer inte överens med requesten");

if (preg_match("/tt\d+/i", $_POST["imdb"], $matches))
	$imdb = $matches[0];
elseif (preg_match("/imdb\.com\/title\/(tt\d+)/i", $descr, $matches))
	$imdb = $matches[1];
elseif (preg_match("/imdb\.com\/Title\?(\d+)/i", $descr, $matches))
	$imdb = "tt" . $matches[1];
	
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
	
if (preg_match("/v=([^&]+)/i", $_POST["youtube"], $matches))
	$youtube = $matches[1];
	
if ($_POST["anonymous"])
{
	$anonymous = "yes";
	$uploader = "Anonym";
}
else
{
	$anonymous = "no";
	$uploader = $CURUSER["username"];
}

if (!$req)
	$pretime = get_pre_time($torrent);

if ($subs = $_POST["languages"])
	$subs = implode(",", $subs);

mysql_query("INSERT INTO torrents (info_hash, name, filename, save_as, search_text, descr, category, size, added, numfiles, p2p, last_action, owner, ownername, imdb, imdb_plot, imdb_rating, imdb_genre, imdb_tagline, imdb_title, imdb_year, imdb_poster, imdb_stars, imdb_recs, youtube, req, freeleech, anonymous, pretime, subs, reqid) VALUES(" . implode(", ", array_map("sqlesc", array($infohash, $torrent, $fname, $dname, searchfield("$shortfname $dname $torrent"), $descr, $cat, $size, $dt, count($filelist), $p2p, $dt, $CURUSER["id"], $CURUSER["username"], $imdb, $plot, $rating, $genres, $tagline, $title, $year, $poster, $stars, $recs, $youtube, $section, $freeleech, $anonymous, $pretime, $subs, $req["id"]))) . ")") or sqlerr(__FILE__, __LINE__);
$id = mysql_insert_id();

foreach ($filelist as $file)
{
	$size = 0 + $file["length"];
	$filename = implode("\\", $file["path"]);
	mysql_query("INSERT INTO files (torrentid, filename, size) VALUES ($id, " . sqlesc($filename) . ", $size)") or sqlerr(__FILE__, __LINE__);
}

move_uploaded_file($tmpname, "/var/torrents/$id.torrent");

if (get_user_class() >= UC_USER)
	mysql_query("INSERT INTO uploads (torrentid, userid, points) VALUES($id, $CURUSER[id], " . ($req ? ($req["points"] + 10) : (!$section ? 20 : 10)) . ")") or sqlerr(__FILE__, __LINE__);

write_log(($req ? "Request " : "") . "<b>" . $torrent . "</b> ($id) laddades upp av NAMNET", $CURUSER["username"], ($anonymous != 'yes' ? false : true));

if (get_user_class() < UC_MODERATOR && !$section)
	mysql_query("UPDATE users SET last_upload = '$dt' WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
if ($imdb)
{
	$res = mysql_query("SELECT * FROM guardedmovies WHERE imdb LIKE " . sqlesc($imdb)) or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$cats = explode(",", $arr["categories"]);
		
		if (in_array($cat, $cats))
		{
			$subject = "Bevakad film har blivit uppladdad";
			$body = ($anonymous == 'yes' ? "[i]Anonym[/i]" : "[url=userdetails.php?id={$CURUSER["id"]}]{$CURUSER["username"]}[/url]") . " har laddat upp filmen [i]{$title}[/i] ([url=details.php?id={$id}]{$torrent}[/url]) som du bevakar.\n\n[i]Notera att filmen nu automatiskt blivit borttagen från dina bevakningar.[/i]";
			
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($arr["userid"], get_date_time(), $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
			
			if ($arr["rss"] == 'yes')
				mysql_query("INSERT INTO bookmarks (torrentid, userid) VALUES($id, $arr[userid])") or sqlerr(__FILE__, __LINE__);
			
			mysql_query("DELETE FROM guardedmovies WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
		}
	}
}

if ($req)
{
	$votes = mysql_query("SELECT * FROM reqvotes WHERE reqid = $req[id]") or sqlerr(__FILE__, __LINE__);
	
	$subject = "Request uppladdad";
	$body = ($anonymous == 'yes' ? "[i]Anonym[/i]" : "[url=userdetails.php?id={$CURUSER["id"]}]{$CURUSER["username"]}[/url]") . " har laddat upp requesten [url=details.php?id=$id]$req[name][/url]";
	
	while ($vote = mysql_fetch_assoc($votes))
		mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($vote["userid"], get_date_time(), $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);

	mysql_query("UPDATE requests SET uploaderid = $CURUSER[id] WHERE id = $req[id]") or sqlerr(__FILE__, __LINE__);
}

if (in_array($cattype["name"], array("DVDr", "Games", "HD", "Rips")))
	header("Location: covers.php?id=$id&uploaded=1");
else
	header("Location: details.php?id=$id&uploaded=1");

?>