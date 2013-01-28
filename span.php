<?php

require_once("globals.php");
require_once("imdb_class.php");

dbconn();
loggedinorreturn();
parked();

if (get_user_class() < UC_POWER_USER)
	stderr("Fel", "Du måste vara lägst Power User för att kunna bevaka filmer");

if ($_POST)
{
	if (isset($_POST["add"]) || $confirm = isset($_POST["confirm"]))
	{
		$imdb = trim($_POST["imdb"]);
		
		if (!preg_match("#tt\d+#i", $imdb, $matches))
			stderr("Fel", "Ogiltig länk");
			
		$imdb = $matches[0];
		
		$res = mysql_query("SELECT id FROM guardedmovies WHERE userid = $CURUSER[id] AND imdb = " . sqlesc($imdb) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		
		if (mysql_num_rows($res))
			stderr("Fel", "Du har redan bevakat filmen");
			
		$res = mysql_query("SELECT id FROM torrents WHERE imdb = " . sqlesc($imdb) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		
		if (!mysql_num_rows($res) || $confirm)
		{
			$data = new imdb($imdb);
			$year = $data->year();
			$title = $data->title() . ($year ? " ($year)" : "");
			$tagline = $data->tagline();
			$plot = $data->plot();
			$poster = $data->saveposter();
		
			foreach (genrelist() AS $cat)
				if (in_array($cat["cattype"], array(1, 2, 8)))
					$cats[] = $cat["id"];
				
			$cats = implode(",", $cats);
		
			mysql_query("INSERT INTO guardedmovies (userid, imdb, title, tagline, plot, categories) VALUES(" . implode(", ", array_map("sqlesc", array($CURUSER["id"], $imdb, $title, $tagline, $plot, $cats))) . ")") or sqlerr(__FILE__, __LINE__);
			
			header("Location: span.php");
		}
	}
	else
	{	
		foreach ($_POST["id"] AS $id)
		{
			$id = 0 + $id;
			$cats = $_POST["categories"][$id] ? implode(",", $_POST["categories"][$id]) : "";
			$rss = $_POST["rss"][$id] ? "yes" : "no";
			$del = $_POST["del"][$id];
			
			if (!$cats)
				stderr("Fel", "Du måste välja några kategorier");
			
			if ($del)
				mysql_query("DELETE FROM guardedmovies WHERE id = $id") or sqlerr(__FILE__, __LINE__);
			else
				mysql_query("UPDATE guardedmovies SET categories = " . sqlesc($cats) . ", rss = '$rss' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		}
		
		header("Location: span.php");
	}
}

head("Bevakade filmer");

print("<h1 style='margin-bottom: 5px;'>Bevakade filmer</h1>\n");
print("<span style='display: inline-block; margin-bottom: 10px; font-style: italic;'>(Få PM vid uppladdning och ladda hem automatiskt genom personligt RSS-flöde)</span>\n");

print("<form method='post' action='span.php'>\n");
print("<input type='text' name='imdb' class='search' id='movieguard' value='" . ($imdb ? "http://www.imdb.com/title/$imdb/" : "IMDb-länk...") . "' size=100" . ($imdb ? " disabled" : "") . " /> <input type='submit' name='add' value='Lägg till'" . ($imdb ? " disabled" : "") . " />\n");

if ($imdb)
{			
	print("<table style='margin: 10px auto; width: 500px;'>\n");
	
	$rels = mysql_query("SELECT torrents.id, torrents.name, torrents.added, torrents.category, torrents.freeleech, torrents.req, torrents.pretime, torrents.imdb_genre, categories.id AS catid, categories.name AS catname, categories.image FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE torrents.imdb = " . sqlesc($imdb) . " ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
		
	$i = 0;
	while ($rel = mysql_fetch_assoc($rels))
	{
		if ($rel["imdb_genre"])
		{
			$genres = explode(" / ", $rel["imdb_genre"]);
				
			$gen = array();
			foreach ($genres AS $genre)
				$gen[] = "<a href='browse.php?genre=$genre' style='font-weight: normal;'>$genre</a>";
					
			$addgen = "<span class='small'>" . implode(" / ", $gen) . "</span>";
		}
		elseif (!$rel["req"])
		{
			$addgen = "<span style='color: gray;'>";
			
			if ($rel["pretime"] != '0000-00-00 00:00:00')
				$addgen .= get_elapsed_time_all(strtotime($rel["added"]), strtotime($rel["pretime"])) . " efter pre";
			else
				$addgen .= "<i>Ingen pre kunde hittas</i>";
				
			$addgen .= "</span>";
		}
	
		print("<tr class='main'" . ($rel["freeleech"] == 'yes' ? " style='background-color: #ccffcc;'" : "") . "><td style='width: 40px; padding: 0px;'><a href='browse.php?cat=$rel[catid]'><img src='$rel[image]' /></a></td><td><a href='details.php?id=$rel[id]' title='$rel[name]' style='font-size: 9pt;'>" . CutName($rel["name"], 60) . "</a><br />$addgen</td><td><a target='_blank' href='download.php/$rel[id]'><img src='/pic/magnet.gif' alt='Ladda ner' /></a></td></tr>\n");
	}
	
	print("</table>\n");
	print("<h3>Filmen du vill bevaka finns redan tillgänglig, vill du ändå bevaka den?</h3>\n");
	print("<input type='hidden' name='imdb' value='$imdb' /><input type='submit' name='confirm' value='Lägg till' /> <input type='submit' value='Avbryt' />\n");
}

print("</form>\n");

$cats = genrelist();

$res = mysql_query("SELECT * FROM guardedmovies WHERE userid = $CURUSER[id] ORDER BY title ASC") or sqlerr(__FILE__, __LINE__);

print("<table><tr class='clear'><td style='vertical-align: top;'>\n");
print("<form method='post' action='span.php'>\n");
print("<table style='width: 600px; margin-top: 10px;'><tr><td class='colhead' style='width: 100%;'>Film</td><td class='colhead'>Kategorier</td><td class='colhead'>RSS</td><td class='colhead'>Ta bort</td></tr>\n");

if (!mysql_num_rows($res))
	print("<tr class='clear'><td colspan=4 style='text-align: center; font-style: italic;'>Du har inte bevakat några filmer</td></tr>\n");
	
while ($arr = mysql_fetch_assoc($res))
{
	$c = "<select name='categories[$arr[id]][]' size=10 multiple>";
	$guardcats = explode(",", $arr["categories"]);
	
	$cattypes = mysql_query("SELECT * FROM cattype WHERE id IN(1,2,8) ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	while ($cattype = mysql_fetch_assoc($cattypes))
	{
		$c .= "<optgroup label='$cattype[name]'>";
		foreach ($cats AS $cat)
			if ($cat["cattype"] == $cattype["id"])
				$c .= "<option value=$cat[id]" . (in_array($cat["id"], $guardcats) ? " selected" : "") . ">$cat[name]</option>";
					
		$c .= "</optgroup>";
	}
		
	$c .= "</select>";

	print("<tr><td style='vertical-align: top;'><input type='hidden' name='id[]' value=$arr[id] /><img src='" . (file_exists($imdb_small_dir . "/" . $arr["imdb"] . ".png") ? "/getimdb.php/$arr[imdb].png" : "/pic/noposter.png") . "' class='guarded' /><h2><a href='http://www.imdb.com/title/$arr[imdb]' target='_blank'>$arr[title]</a></h2>" . ($arr["tagline"] ? "<h3 style='font-style: italic;'>$arr[tagline]</h3>" : "") . ($arr["plot"] ? "<i>$arr[plot]</i>" : "") . "</td><td>$c</td><td style='text-align: center;'><input type='checkbox' name='rss[$arr[id]]' value=1" . ($arr["rss"] == 'yes' ? " checked" : "") . " /></td><td style='text-align: center;'><input type='checkbox' name='del[$arr[id]]' value=1 /></td></tr>\n");
}

if (mysql_num_rows($res))	
	print("<tr class='clear'><td colspan=4 style='text-align: center;'><input type='submit' name='update' value='Uppdatera' /></td></tr>\n");

print("</table></form>\n");
print("</td><td style='vertical-align: top;'>\n");
	
print("<div class='frame' style='margin-top: 10px;'><h3>Topplista</h3><table class='clear graymark'>\n");
	
$res = mysql_query("SELECT * FROM guardedmovies GROUP BY imdb ORDER BY COUNT(userid) DESC LIMIT 20") or sqlerr(__FILE__, __LINE__);

$i = 1;
while ($arr = mysql_fetch_assoc($res))
{
	$guarded = mysql_query("SELECT id FROM guardedmovies WHERE imdb = " . sqlesc($arr["imdb"]) . " AND userid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);

	print("<tr><td style='text-align: center; font-size: 9pt; font-weight: bold;'>" . $i++ . "</td><td style='border-right: 0px;'><img class='spantop' style='float: left; width: 30px; margin-right: 5px;' src='" . (file_exists($imdb_small_dir . "/" . $arr["imdb"] . ".png") ? "/getimdb.php/$arr[imdb].png" : "/pic/noposter.png") . "' /><h3 style='display: inline-block; margin-bottom: 5px;'><a href='http://www.imdb.com/title/$arr[imdb]' target='_blank'>" . cutStr($arr["title"], 20) . "</a></h3>" . ($arr["tagline"] ? "<br /><span class='small' style='font-style: italic;'>$arr[tagline]</span>" : "") . "</td><td style='border-left: 0px;'><form method='post' action='span.php'><input type='hidden' name='imdb' value='$arr[imdb]' /><input type='submit' name='add' value='Lägg till'" . (mysql_num_rows($guarded) ? " disabled" : "") . " /></form></td></tr>\n");
}

print("</table></div>\n");
print("</td></tr></table>\n");

foot();
?>