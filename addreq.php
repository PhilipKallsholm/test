<?php

require_once("globals.php");
require_once("imdb_class.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_POWER_USER)
	jErr("Du måste vara lägst Power User för att skapa requests");

$id = $edit = 0 + $_GET["edit"];
$dt = get_date_time();
$type = $_POST["type"];
$release = trim($_POST["release"]);
$imdb = trim($_POST["imdb"]);
$season = 0 + $_POST["season"];
$episode = 0 + $_POST["episode"];
$category = 0 + $_POST["cat"];
$points = 0 + $_POST["points"];
$del = $_POST["del"];
$reason = trim($_POST["reason"]);

if (!$edit && get_user_class() < UC_MARVELOUS_USER)
{
	$reqs = mysql_query("SELECT id FROM requests WHERE userid = $CURUSER[id] AND uploaderid = 0") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($reqs) >= $CURUSER["reqslots"])
		jErr("Alla dina requestslots är använda - <a href='bonus2.php'>köp fler</a>");
}

if ($points < 0)
	jErr("Ogiltig hittelön");

if ($edit)
{
	$req = mysql_query("SELECT * FROM requests WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	$req = mysql_fetch_assoc($req);
	
	if (!$req)
		jErr("Requesten finns inte");
	
	$vote = mysql_query("SELECT points FROM reqvotes WHERE reqid = $id AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	$vote = mysql_fetch_assoc($vote);
	
	$points = $points - $vote["points"];
		
	$req["type"] = $req["link"] ? "imdb" : "release";
		
	if (!staff() && $CURUSER["id"] != $req["userid"])
		jErr("Requesten tillhör inte dig");
		
	if (!$_POST)
	{
		$return["type"] = $req["type"];
		$return["release"] = $req["name"];
		$return["link"] = $req["link"];
		$return["season"] = $req["season"];
		$return["episode"] = $req["episode"];
		$return["cat"] = $req["category"];
		$return["points"] = $vote["points"];
		
		print(json_encode($return));
		die;
	}
	
	if ($del)
	{
		if (!$reason)
			jErr("Du måste ange en anledning till borttagningen");
			
		$subject = "Request borttagen";
		$body = "$CURUSER[username] har tagit bort requesten [b]$req[name][/b] ($reason)\n\n[i]Eventuell hittelön har nu blivit återbetald[/i]";
			
		$votes = mysql_query("SELECT * FROM reqvotes WHERE reqid = $id") or sqlerr(__FILE__, __LINE__);
		
		while ($vote = mysql_fetch_assoc($votes))
		{
			if ($vote["points"])
			{
				mysql_query("UPDATE users SET seedbonus = seedbonus + $vote[points] WHERE id = $vote[userid]") or sqlerr(__FILE__, __LINE__);
	
				$log = "Återbetalning av hittelön till <i>$req[name]</i> +<b>{$vote["points"]}p</b>";
				mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($vote[userid], '$dt', " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
			}
			
			if ($CURUSER["id"] != $vote["userid"])
				mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($vote["userid"], $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
			
			mysql_query("DELETE FROM reqvotes WHERE id = $vote[id]") or sqlerr(__FILE__, __LINE__);
		}
		
		mysql_query("DELETE FROM requests WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		write_log("Request <b>$req[name]</b> togs bort av NAMNET ($reason)", $CURUSER["username"], false);
		
		$return["id"] = $id;
		$return["del"] = true;
		
		print(json_encode($return));
		die;
	}
	
	if ($type != $req["type"])
		jErr("Requesten måste vara av samma typ som den gamla");
	
	if ($points < 0)
		jErr("Du kan inte minska hittelönen");
}

if (!in_array($type, array('release', 'imdb')))
	jErr("Du måste välja typ av request");
	
if ($type == 'release')
{
	if (!$release)
		jErr("Du måste ange ett releasenamn");

	if (strpos($release, "-") === false)
		jErr("Ogiltigt releasenamn");
		
	$res = mysql_query("SELECT id FROM requests WHERE name = " . sqlesc($release) . " AND id != $id AND uploaderid = 0 LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if ($arr)
		jErr("Requesten finns redan - <a class='jlink' onClick=\"vote($arr[id])\">rösta</a>");
		
	$res = mysql_query("SELECT id FROM torrents WHERE name = " . sqlesc($release) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if (mysql_num_rows($res))
		jErr("Releasen finns redan uppladdad - <a href='details.php?id=$arr[id]'>länk</a>");
}
elseif ($type == 'imdb')
{	
	if (!$imdb)
		jErr("Du måste ange en IMDb-länk");

	if (!preg_match("/tt\d+/i", $imdb, $matches))
		jErr("Ogiltig IMDb-länk");
	
	$imdb = $matches[0];
	$link = "http://www.imdb.com/title/" . $imdb;
	
	$res = mysql_query("SELECT id FROM requests WHERE link = " . sqlesc($link) . " AND category = $category AND id != $id AND uploaderid = 0 LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if ($arr)
		jErr("Requesten finns redan - <a class='jlink' onClick=\"vote($arr[id])\">rösta</a>");
	
	$im = new imdb($imdb);
	$title = $im->title();
	$year = $im->year();
	$release = $year ? "$title ($year)" : $title;
	
	if (!$release)
		jErr("Ogiltig IMDb-länk");
}

if (!$category)
	jErr("Du måste välja en kategori");
	
if ($points > $CURUSER["seedbonus"])
	jErr("Du har inte tillräckligt med bonuspoäng");
	
if ($edit)
{
	mysql_query("UPDATE requests SET name = " . sqlesc($release) . ", category = $category, link = " . sqlesc($link) . ", season = $season, episode = $episode, points = points + $points WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("UPDATE reqvotes SET points = points + $points WHERE reqid = $id AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
}
else
{
	mysql_query("INSERT INTO requests (name, category, added, link, season, episode, userid, votes, points) VALUES(" . sqlesc($release) . ", $category, '$dt', " . sqlesc($link) . ", $season, $episode, $CURUSER[id], 1, $points)") or sqlerr(__FILE__, __LINE__);
	$id = mysql_insert_id();

	mysql_query("INSERT INTO reqvotes (reqid, userid, points) VALUES($id, $CURUSER[id], $points)") or sqlerr(__FILE__, __LINE__);
}

if ($points)
{
	mysql_query("UPDATE users SET seedbonus = seedbonus - $points WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
	$log = "Köp av hittelön till <i>$release</i> -<b>{$points}p</b>";
	mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '$dt', " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
}

$cat = mysql_query("SELECT name, image FROM categories WHERE id = $category") or sqlerr(__FILE__, __LINE__);
$cat = mysql_fetch_assoc($cat);

if ($edit)
{
	$user = mysql_query("SELECT username FROM users WHERE id = $req[userid]") or sqlerr(__FILE__, __LINE__);
	
	if ($user = mysql_fetch_assoc($user))
		$user = "<a href='userdetails.php?id=$req[userid]'>$user[username]</a>";
	else
		$user = "<i>Borttagen</i>";
}
else
	$user = "<a href='userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a>";
	
if ($season)
{
	$tv = "<br /><span class='small' style='font-style: italic;'>";
	$tv .= "Säsong " . $season;
		
	if ($episode)
		$tv .= ", episod " . $episode;
			
	$tv .= "</span>";
}
else
	$tv = "";

$return["edit"] = $edit;
$return["id"] = $id;
$return["row"] = "<tr id='r$id'><td title='$cat[name]' style=\"width: 40px; height: 30px; padding: 0px; background-image: url('$cat[image]'); background-repeat: no-repeat; background-position: 0px -7px; cursor: pointer;\" onClick=\"window.location.href = 'requests.php?cat=$category'\"></td><td style='padding: 0px 5px; font-size: 9pt;'>" . ($type == 'imdb' ? "<a href='$link' target='_blank'>$release</a>" : $release) . "<span style='float: right; margin-left: 10px; font-size: 8pt; font-style: italic;'>(<a class='jlink' onClick=\"editReq($id)\">ändra</a>)</span>" . $tv . "</td><td><a class='jlink' onClick=\"report($id, 'request')\">Rapportera</a></td><td id='v$id' style='text-align: center;'><a class='jlink' onClick=\"vote($id)\"" . (!$edit || $vote ? " style='color: gray;'" : "") . ">" . ($edit ? $req["votes"] : 1) . "</a></td><td>" . ($edit ? $req["added"] : $dt) . "</td><td>$user</td><td id='p$id' style='text-align: center;'><i>" . number_format(($edit ? ($req["points"] + $points) : $points)) . " p</i></td><td><a href='upload.php/$id'>Ladda upp</a></td></tr>\n";

print(json_encode($return));
die;

?>