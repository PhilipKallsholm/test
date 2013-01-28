<?

require_once("globals.php");

function speedcalc($bytes)                                                                                                                                                                          
{                                                                                                                                                                                                                 
	$sizes = array('B/s', 'KB/s', 'MB/s', 'GB/s', 'TB/s', 'PB/s');                                                                                                                                                  
	$extension = $sizes[0];                                                                                                                                                                                           

	for ($i = 1; (($i < count($sizes)) && ($bytes >= 1024)); $i++)
	{
		$bytes /= 1024;
		$extension = $sizes[$i];
	}
	
	return round($bytes, 2) . " " . $extension;
} 

function dltable($name, $res, $torrent)                                                                                                                                                                           
{
	global $CURUSER;
	
	$s = "<h2>" . count($res) . " $name</h2>\n";
	$s .= "<table style='width: 100%;'>\n";   
	$s .= "<tr><td class='colhead'>Användarnamn</td>" .
          "<td class='colhead' style='text-align: center;'>Port</td>".
          "<td class='colhead' style='text-align: right;'>Uppladdat</td>".
          "<td class='colhead' style='text-align: right;'>Hastighet</td>".
          "<td class='colhead' style='text-align: right;'>Nedladdat</td>" .
          "<td class='colhead' style='text-align: right;'>Hastighet</td>" .
          "<td class='colhead' style='text-align: right;'>Ratio</td>" .
          "<td class='colhead' style='text-align: right;'>Färdigt</td>" .
          "<td class='colhead' style='text-align: right;'>Ansluten</td>" .
          "<td class='colhead' style='text-align: right;'>Overksam</td>" .
          "<td class='colhead'>Klient</td></tr>\n";
		  
	$now = time();                                                                                                                                                                                                                                                                                                                                                                    

	foreach ($res AS $arr)
	{                                                                                                                                                                                    
		$user = mysql_query("SELECT id, username, warned, warned_reason, donor, hidetraffic FROM users WHERE id = $arr[uid]") or sqlerr(__FILE__, __LINE__);                                                                      
		$user = mysql_fetch_assoc($user);
		
		$highlight = $CURUSER["id"] == $user["id"] ? " style='background-color: #BBAF9B;'" : "";  
		
		$s .= "<tr class='nowrap'$highlight>";

		if ($user)
		{
			if (get_user_class() >= UC_MODERATOR || $user["id"] == $CURUSER["id"] || $user["hidetraffic"] == 'no' && ($torrent['anonymous'] == 'no' || $arr["uid"] != $torrent["owner"]))
				$s .= "<td class='nowrap'><a href='userdetails.php?id=$user[id]'>$user[username]</a>" . usericons($user["id"]) . "</td>";
			else
				$s .= "<td class='nowrap'><i>Anonym</i></td>";
		}
		else
			$s .= "<td>(Borttagen)</td>";  
		
		$s .= "<td style='text-align: center;'>" . ($arr["connectable"] ? "<span style='color: green;'>Öppen</span>" : "<span style='color: red;'>Stängd</span>") . "</td>";                                                           
		$s .= "<td style='text-align: right;'>" . mksize($arr["uploaded"]) . "</td>";                                                                                                                                    
		$s .= "<td style='text-align: right;'>" . speedcalc($arr["upspeed"]) . "</td>";                                                                                                
		$s .= "<td style='text-align: right;'>" . mksize($arr["downloaded"]) . "</td>";                                                                                                                                 
		$s .= "<td style='text-align: right;'>" . speedcalc($arr["downspeed"]) . "</td>";

		if ($arr["downloaded"])                                                                                                                                                                              
		{                                                                                                                                                                                 
			$ratio = $arr["uploaded"] / $arr["downloaded"];                                                                                                                              
			$s .= "<td style='text-align: right;'><font color=" . get_ratio_color($ratio) . ">" . number_format($ratio, 3) . "</font></td>";                                                                       
		}                                                                                                                                                                                 
		elseif ($e["uploaded"])                                                                                                                                                                                                                                                                                                                                                                        
			$s .= "<td style='text-align: right;'>Inf.</td>\n";                                                                                                                                                         
		else                                                                                                                                                                                            
			$s .= "<td style='text-align: right;'>---</td>\n";                                                                                                                                                           

		$s .= "<td style='text-align: right;'>" . sprintf("%.2f%%", 100 * (1 - ($arr["left"] / $torrent["size"]))) . "</td>";                                                                                            
		$s .= "<td style='text-align: right;'>" . mkprettytime($now - strtotime($arr["started"])) . "</td>";
		$s .= "<td style='text-align: right;'>" . mkprettytime($now - $arr["mtime"]) . "</td>";                                                                                                                          
		$s .= "<td style='text-align: right;'>" . $arr["useragent"] . "</td>";  
		$s .= "</tr>\n";                                                                                                                                                                                                  
	}                                                                                                                                                                                                         
	$s .= "</table>\n";                                                                                                                                                                                             
	return $s;
}

dbconn();
loggedinorreturn();

if ($subid = 0 + $_GET["delsub"])
{	
	$res = mysql_query("SELECT subs.torrentid, subs.file, torrents.owner FROM subs LEFT JOIN torrents ON subs.torrentid = torrents.id WHERE subs.id = $subid") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);

	if ($CURUSER["id"] != $arr["owner"] && get_user_class() < UC_MODERATOR)
		stderr("Fel", "Du har inte behörighet att radera undertexten.");

	mysql_query("DELETE FROM subs WHERE id = $subid") or sqlerr(__FILE__, __LINE__);
	unlink("$sub_dir/$arr[file]");
	
	header("Location: details.php?id=$arr[torrentid]&subdel=1");
}

if ($CURUSER["parked"] == 'yes')
	stderr("Fel", "Ditt konto är parkerat");

$id = 0 + $_GET["id"];
$type = trim($_GET["type"]);

if (!$id)
	stderr("Fel", "Torrenten har blivit borttagen.");

$res = mysql_query("SELECT torrents.*, IF(torrents.numratings > 0, NULL, ROUND(torrents.ratingsum / torrents.numratings, 1)) AS rating, categories.name AS catname, cattype.name AS cattype FROM torrents LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN cattype ON categories.cattype = cattype.id WHERE torrents.id = $id") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_array($res);

$owned = $moderator = 0;

if (get_user_class() >= UC_MODERATOR)
	$owned = $moderator = true;
elseif ($CURUSER["id"] == $row["owner"])
	$owned = true;

if ($_POST && !$row["trailer"])
{
	if (preg_match("/v=([^&]+)/i", $_POST["trailer"], $matches))
		$youtube = $matches[1];

	if ($youtube)
	{
		mysql_query("UPDATE torrents SET youtube = " . sqlesc($youtube) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		mysql_query("INSERT INTO traileradds (torrentid, userid, added) VALUES($id, $CURUSER[id], '" . get_date_time() . "')") or sqlerr(__FILE__, __LINE__);

		$stafflog = $CURUSER[username] . " lade till en trailer till torrenten <a href='details.php?id=$id'>$row[name]</a>";
		mysql_query("INSERT INTO stafflog (added, txt) VALUES('" . get_date_time() . "', " . sqlesc($stafflog) . ")") or sqlerr(__FILE__, __LINE__);
	}

	header("Location: details.php?id=$id");
}

if (!$row)
	stderr("Fel", "Torrenten har blivit borttagen.");

if ($_GET["hit"])
{
	mysql_query("UPDATE torrents SET views = views + 1 WHERE id = $id");
		
	if ($_GET["tocomm"])
		header("Location: $BASEURL/details.php?id=$id&page=0#startcomments");
	elseif ($_GET["files"])
		header("Location: $BASEURL/details.php?id=$id&files=1");
	elseif ($_GET["toseeders"])
		header("Location: $BASEURL/details.php?id=$id&peers=1&toseeders=1");
	elseif ($_GET["todlers"])
		header("Location: $BASEURL/details.php?id=$id&peers=1&todlers=1");
	else
		header("Location: $BASEURL/details.php?id=$id");
	exit();
}

if (!$type)
{
	head("Torrent \"" . $row["name"] . "\"");

	print("<script type='text/javascript'>\n");
	?>
		var tag = document.createElement('script');
		tag.src = "http://www.youtube.com/iframe_api";
		var firstScriptTag = document.getElementsByTagName('script')[0];
		firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

		var player;
		function onYouTubeIframeAPIReady()
		{
			player = new YT.Player('youframe', {
				height: '385',
				width: '640',
				videoId: '<?php print($row["youtube"]); ?>',
				playerVars: {
					wmode: "opaque"
				}
			});
			
			$("a#trailer").click(function() {
				$("div#background").show();
				$("div#youtube").show();
				player.playVideo();
			});
			
			$("div#background").click(function() {
				player.pauseVideo();
				$("div#youtube").hide();
				$("div#background").hide();
			});
		}
	
		$(document).ready(function() {
			var rating = <? print(($row["numratings"] ? round($row["ratingsum"] / $row["numratings"]) : 0)) ?>;
			var index = rating - 1;
			
			if (rating == 0) {
				$("img[id^='r']").fadeTo("fast", "0.25");
			} else {
				$("img[id^='r']:gt(" + index + ")").fadeTo("fast", "0.25");
			}

			$("img.rating").on({
			mouseenter:
				function() {
					$(this).fadeTo("fast", "1.00");
					$(this).prevAll("img.rating").fadeTo("fast", "1.00");
					$(this).nextAll("img.rating").fadeTo("fast", "0.25");
				}
			, mouseleave:
				function() {
					if (rating == 0) {
						$("img.rating").fadeTo("fast", "0.25");
					} else {
						$("img.rating:lt(" + rating + ")").fadeTo("fast", "1.00");
						$("img.rating:gt(" + index + ")").fadeTo("fast", "0.25");
					}
				}
			, click:
				function() {
					var id = this.id;
					/*var index = $(this).index() + 1;*/
					
					$("div[name='ratings']").fadeTo("fast", "0.10", function() {
						$.post("vote.php", {id: id}, function(data) {
							$("div[name='ratings']").fadeTo("fast", 1.00);
							$("img.rating:lt(" + data.ind + ")").fadeTo("fast", 1.00);
							$("img.rating:eq(" + data.ind + "), img.rating:gt(" + data.ind + ")").fadeTo("fast", 0.25);
							$("img.rating").off("mouseenter mouseleave click");
							$("span#numratings").html("<b>" + data.votes + "</b>");
							
							if (data.votes >= 5)
							{
								$("span#rating").text(data.rating);
							}
						}, "json");
					});
				}
			});

			$("#beskr").click(function() {
				$("#k7").slideToggle("slow");
			});

			$("#rel").click(function() {
				$("#k8").slideToggle("slow");
			});
		});
		<?php
		print("</script>\n");

		$spacer = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

		if ($_GET["uploaded"])
		{
			print("<h2>Uppladdningen lyckades</h2>\n");
			print("<p><i>Du kan nu börja seeda</i></p>\n");
		}
		elseif ($_GET["edited"])
			print("<h2>Ändrat</h2>\n");
		elseif ($_GET["subuploaded"])
			print("<h2>Undertexten laddades upp</h2>\n");
		elseif ($_GET["subdel"])
			print("<h2>Undertexten har tagits bort</h2>\n");
		elseif ($_GET["rated"])
			print("<h2>Röst räknad</h2>\n");

		function hex_esc($matches) {
			return sprintf("%02x", ord($matches[0]));
		}

		/*tr("Hashinfo", preg_replace_callback('/./s', "hex_esc", hash_pad($row["info_hash"])));*/

		if (file_exists("$covers_dir/$id.png"))
		{
			$name = "<img src='getimdb.php/$id.png' class='shadow' />";
			
			print("<table class='clear'><tr><td style='vertical-align: top;'>$name</td><td style='width: 500px; vertical-align: top;'>");
		}
		elseif ($row["imdb_poster"] && file_exists("$imdb_large_dir/$row[imdb_poster]"))
		{
            $name = "<img src='getimdb.php/$row[imdb_poster]?l=1' class='shadow' />";
			
			print("<table class='clear'><tr><td style='vertical-align: top;'>$name</td><td style='width: 500px; vertical-align: top;'>");
		}
		else
			print("<table class='clear'><tr><td style='width: 500px; vertical-align: top;'>");

		if ($row["imdb"])
		{
			print("<div class='frame' style='border-width: 2px; border-color: #f2f2f2; border-radius: 5px 5px 0px 0px;'>\n");
			print("<a target='_blank' href='http://www.imdb.com/title/$row[imdb]/' style='display: block; float: left; margin: 0px 10px 10px 0px;'><img src='" . ($row["imdb_poster"] ? "getimdb.php/$row[imdb_poster]" : "/pic/noposter.png") . "' title='Klicka här för att komma till IMDb-sidan' /></a>\n");
			
			print("<div>\n");

			if ($row["imdb_title"])
				print("<h1 style='margin-bottom: 5px;'>$row[imdb_title]" . ($row["imdb_year"] ? " ($row[imdb_year])" : "") . "</h1>\n");
				
			if ($row["imdb_rating"])
			{
				$rating = round($row["imdb_rating"]);
				$imdbrating = "";
		
				for ($i = 0; $i < 10; $i++)
				{
					if ($i < $rating)
						$imdbrating .= "<img alt='Stjärna' src='/pic/goldstar.gif' />";
					else
						$imdbrating .= "<img alt='Stjärna' src='/pic/greystar.gif' />";
				}
				
				print("$imdbrating\n");
			}
				
			if ($row["imdb_genre"])
			{
				$genres = explode(" / ", $row["imdb_genre"]);

				foreach ($genres AS $genre)
					$genren[] = "<a href=browse.php?" . ($row["req"] != 0 ? "arkiv=1" : "nytt=1") . "&amp;genre=$genre>$genre</a>";

				$row["imdb_genre"] = implode(" &#47; ", $genren);

				print("<h3 style='margin-top: 10px; margin-bottom: 5px;'>$row[imdb_genre]</h3>\n");
			}

			if ($row["imdb_stars"])
			{
				$stars = explode(", ", $row["imdb_stars"]);

				foreach ($stars AS $star)
					$staren[] = "<a href=browse.php?search=" . urlencode($star) . "&amp;incldead=0><b>{$star}</b></a>";

				$row["imdb_stars"] = implode(", ", $staren);

				print("med $row[imdb_stars]\n");
			}

			if ($row["imdb_tagline"])
				print("<h2 style='margin-top: 10px;'><i>$row[imdb_tagline]</i></h2>\n");

			print("</div>\n");

			if ($row["imdb_plot"])
				print("<div style='clear: left;'><i>$row[imdb_plot]</i></div>\n");


			print("</div><br />\n");
		}

		if ($row["imdb_recs"])
		{
			$recs = explode(", ", $row["imdb_recs"]);
			
			$rs = mysql_query("SELECT id, name, imdb_poster FROM torrents WHERE imdb IN(" . implode(", ", array_map("sqlesc", $recs)) . ") GROUP BY imdb ORDER BY added DESC LIMIT 5") or sqlerr(__FILE__, __LINE__);
			
			if (mysql_num_rows($rs))
			{
				print("<h3 style='text-align: center;'>Rekommenderade filmer</h3><div class='frame' style='border-width: 2px; border-color: #f2f2f2; border-radius: 0px; text-align: center;'>\n");
				print("<div id='imdb'></div>\n");
				
				while ($r = mysql_fetch_assoc($rs))
					print("<a href='details.php?id=$r[id]'><img src='" . ($r["imdb_poster"] && file_exists("/var/imdb_small/$r[imdb_poster]") ? "/getimdb.php/$r[imdb_poster]" : "/pic/noposter.png") . "' class='imdb' alt=$r[id] /></a>");
					
				print("</div><br />");
			}
		}

		print("<div id='inforuta' class=details></div>");

		if ($_GET["peers"])
			print("<script type='text/javascript'>loadPage('details.php?id=$id&type=peers');</script>\n");

		if ($_GET["files"])
			print("<script type='text/javascript'>loadPage('details.php?id=$id&type=files');</script>\n");

		print("<div class='frame' style='border-width: 2px; border-color: #f2f2f2; border-radius: 0px 0px 5px 5px;'><table class='clear' style='width: 100%;'>");

		if ($row["youtube"])
			print("<tr><td class='form'>Trailer:</td><td><a class='jlink' id='trailer'><img src='/pic/trailer.png' /></a><div id='youtube' class='youtube'><div id='youframe'></div></div></td></tr>\n");
		elseif (in_array($row["cattype"], array("DVDr", "Games", "HD", "Rips")))
			print("<tr><td class='form'>Trailer:</td><td><div id='youtube' class='youtube'></div><form method='post' action='details.php?id=$id'><input type='text' size=40 maxlength=100 name='trailer' value='Länk till youtube...' style='font-style: italic; color: grey;' onfocus=\"this.value = ''; this.style.fontStyle = 'normal'; this.style.color = 'black';\" /> <input type='submit' value='Lägg till' /></form></td></tr>\n");
		
		print("<tr" . ($row["freeleech"] == 'yes' ? " style='background-color: #ccffcc'" : "") . "><td class='form'>Ladda ner:</td><td><a target='_blank' href='download.php/$row[id]' style='font-size: 10pt;'>" . htmlspecialchars($row["name"]) . "</a>" . ($row["p2p"] == 'yes' ? " <img src='/pic/p2p.png' alt='p2p-release' title='p2p-release' style='vertical-align: text-bottom;' />" : "") . " <span class='small'>(<a class='jlink' onClick='showMagnet($id)'>visa magnet</a>)</span><div id='magnet' class='small' style='display: none;'></div></td></tr>\n");
		
		if (preg_match("#(^.+?)(?:\.|-)(?:(?:S[0-9]{1,2}\.?(?:E|D)[0-9]{1,2})|\d{4}|[A-Z]{3,})|(^[^-]{4,})-.*(?:-.*|FLAC)#", $row["name"], $matches))
		{
			$match = $matches[1] ? implode(".", array_slice(explode(".", $matches[1]), 0, 2)) : implode(" ", array_slice(explode(" ", $matches[2]), 0, 2));
			
			$rels = mysql_query("SELECT torrents.id, torrents.name, torrents.added, torrents.category, torrents.freeleech, torrents.req, torrents.pretime, torrents.imdb_genre, torrents.p2p, categories.id AS catid, categories.name AS catname, categories.image FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE torrents.name LIKE '" . mysql_real_escape_string($match) . "%' ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
			$count = mysql_num_rows($rels);
			
			if ($count > 1)
			{
				print("<tr><td class='form' style='vertical-align: top;'>Liknande<br />releaser:</td><td style='padding: 5px 0px;'><table style='width: 100%;'>\n");
				
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
				
					print("<tr class='main" . ($rel["id"] == $row["id"] ? " transp" : "") . "'" . ($rel["freeleech"] == 'yes' ? " style='background-color: #ccffcc;'" : "") . "><td style='width: 40px; padding: 0px;'><a href='browse.php?cat=$rel[catid]'><img src='$rel[image]' /></a></td><td style='white-space: nowrap;'><a href='details.php?id=$rel[id]' title='$rel[name]' style='font-size: 9pt;'>" . CutName($rel["name"], 40) . "</a>" . ($rel["p2p"] == 'yes' ? " <img src='/pic/p2p.png' alt='p2p-release' title='p2p-release' style='vertical-align: text-bottom;' />" : "") . "<br />$addgen</td><td><a target='_blank' href='download.php/$rel[id]'><img src='/pic/magnet.gif' alt='Ladda ner' /></a></td></tr>\n");
				}
				
				print("</table></td></tr>\n");
			}
		}
			
		if ($row["visible"] == 'no')
			print("<tr><td class='form'>Synlig:</td><td><b>Nej</b> (död sedan " . get_elapsed_time($row["last_action"]) . ")</td></tr>\n");

		if ($row["nuked"] == 'yes')
				print("<tr><td class='form'>Nukad:</td><td>$row[nukedreason]</td></tr>\n");
		
		if ($row["catname"])
			print("<tr><td class='form'>Typ:</td><td style='font-size: 9pt;'>$row[cattype]/$row[catname]" . ($row["req"] == 1 ? " - Request" : "") . "</td></tr>\n");
		else
			print("<tr><td class='form'>Typ:</td><td><i>Ingen vald</i></td></tr>\n");

		$requester = "<a href='userdetails.php?id=$row[req_userid]'><u>$row[req_user]</u></a>";
		$votes = "";
            
		if ($row["req_votes"] != 1)
			$votes = "er";
		
		if ($row["req"] == 1)
		{
			$req = mysql_query("SELECT * FROM requests WHERE id = $row[reqid]") or sqlerr(__FILE__, __LINE__);
			$req = mysql_fetch_assoc($req);
			
			$requser = mysql_query("SELECT username FROM users WHERE id = $req[userid]") or sqlerr(__FILE__, __LINE__);
			
			if ($requser = mysql_fetch_assoc($requser))
				$requser = "<a href='userdetails.php?id=$req[userid]'>$requser[username]</a>";
			else
				$requser = "<i>Borttagen</i>";
		
			print("<tr><td class='form'>Request:</td><td>$req[name] (requestad av $requser och hade <b>$req[votes]</b> röster)</td></tr>\n");
		}
		
		/*tr("Senaste&nbsp;seedare", "Senaste aktivitet " . mkprettytime($row["lastseed"]) . " sedan");*/
		
		print("<tr><td class='form'>Storlek:</td><td>" . mksize($row["size"]) . "</td></tr>\n");
		
		if (!$row["req"]) 
		{	
			if ($row["pretime"] == '0000-00-00 00:00:00') 
				$addgen = "<i>Ingen pre kunde hittas</i>";
			else
			{
				if (strtotime($row["added"]) > strtotime($row["pretime"]))
					$addgen = get_elapsed_time_all(strtotime($row["added"]), strtotime($row["pretime"])) . " efter pre";
				else
					$addgen = get_elapsed_time_all(strtotime($row["pretime"]), strtotime($row["added"])) . " innan pre";
			}
		}
		elseif ($row["req"] == 1)
		{
			$req = mysql_query("SELECT added FROM requests WHERE id = $row[reqid]") or sqlerr(__FILE__, __LINE__);
			
			if ($req = mysql_fetch_assoc($req))
				$addgen = get_elapsed_time_all(strtotime($row["added"]), strtotime($req["added"])) . " efter request";
		}

		print("<tr><td class='form'>Tillagd:</td><td>$row[added]" . ($addgen ? " ($addgen)" : "") . "</td></tr>\n");

		/*tr("Visningar", $row["views"]);
		tr("Träffar", $row["hits"]);*/

		if ($row["numratings"] < 5)
		{
			$rating = 0;
			$s = "(inväntar fem röster och har fått <span id='numratings'>";
			
			if ($row["numratings"])
				$s .= $row["numratings"] . "</span>)";
			else
				$s .= "0</span>)";
		}
		else
		{
			$rating = round($row["ratingsum"] / $row["numratings"], 1);
			$s = "<b><span id='rating'>$rating</span>/5</b> (<span id='numratings'>$row[numratings]</span> röster totalt)";
		}
		
		$voted = mysql_query("SELECT id FROM torrentvotes WHERE torrentid = $id AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

		$b = "<div style='width: 100px; height: 18px; display: inline-block; margin-right: 10px; vertical-align: text-bottom;' name='ratings'>";

		for ($i = 1; $i <= 5; $i++)
			$b .= "<img src='/pic/goldstar.gif' id='r{$i}t{$id}'" . (!mysql_num_rows($voted) ? " class='rating' style='cursor: pointer;'" : "") . " />";

		$b .= "</div>";
		
		print("<tr><td class='form'>Betyg:</td><td>$b<span id='ratinginfo'>$s</span></td></tr>\n");

		if ($row["anonymous"] == 'yes')
		{
			if (get_user_class() < UC_MODERATOR)
				$uploader = "<i>Anonym</i>";
			else
				$uploader = "<i>Anonym</i> (<a href='userdetails.php?id=$row[owner]'>$row[ownername]</a>)";
		} else
			$uploader = $row["ownername"] ? "<a href='userdetails.php?id=$row[owner]'>$row[ownername]</a>" : "<i>Borttagen</i>";

		if ($owned)
		{
			$uploader .= "<span style='margin-left: 10px;'>[<a href='edit.php?id=$row[id]'>Ändra torrent</a>]";
			$uploader .= " [<a href='covers.php?id=$id&amp;edited=1'>Ändra cover</a>]";

			if (file_exists("$covers_dir/$id.png"))
				$uploader .= " [<a href='covers.php?id=$id&amp;del=1'>Radera cover</a>]";
				
			$uploader .= "</span>";
 		}

		print("<tr><td class='form'>Uppladdare:</td><td>$uploader</td></tr>\n");

		$leechers = mysql_query("SELECT COUNT(*) FROM peers WHERE fid = $row[id] AND `left` > 0 AND active = 1");
		$leechers = mysql_fetch_row($leechers);

		$seeders = mysql_query("SELECT COUNT(*) FROM peers WHERE fid = $row[id] AND `left` = 0 AND active = 1");
		$seeders = mysql_fetch_row($seeders);

		$peers = $leechers[0] + $seeders[0];

		print("<tr><td class='form'>Peers:</td><td><a href=\"javascript:loadPage('details.php?id=$id&amp;type=peers');\">$seeders[0] seedare, $leechers[0] leechare = $peers peer(s) totalt</a></td></tr>\n");

		$snatched = mysql_query("SELECT COUNT(*) FROM snatched WHERE torrentid = $id AND done > 0");
		$snatched = mysql_fetch_row($snatched);
		$row["times_completed"] = $snatched[0];

		print("<tr><td class='form'>Nedladdad:</td><td>" . (get_user_class() >= UC_MODERATOR ? "<a href='viewsnatches.php?id=$id'>$row[times_completed] gånger</a>" : "$row[times_completed] gånger") . "</td></tr>\n");

		print("<tr><td class='form'>Antal filer:</td><td><a href=\"javascript:loadPage('details.php?id=$id&amp;type=files');\">$row[numfiles] filer</a></td></tr>\n");

		$bookmark = mysql_query("SELECT id FROM bookmarks WHERE torrentid = $id AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
		print("<tr><td class='form'>Bokmärk:</td><td><div id=\"b{$id}\"><a class='jlink' onclick='bookmark($id)'>" . (mysql_num_rows($bookmark) ? "<img src='/pic/bok2.gif' />" : "<img src='/pic/bok.gif' />") . "</a></div></td></tr>\n");

		print("<tr><td class='form'>Rapportera:</td><td><a class='jlink' onclick=\"report($id, 'torrent')\">Rapportera länk</a></td></tr>\n");

		if ($row["cattype"] == 'HD')
		{
			print("<tr><td class='form'>Undertexter:</td><td>");

			$subsres = mysql_query("SELECT * FROM subs WHERE torrentid = $id") or sqlerr(__FILE__, __LINE__);

			while ($subs = mysql_fetch_assoc($subsres))
				print("<a href='sub.php/$subs[id]/$subs[file]'><img src='/pic/subflags/" . subflag($subs["lang"]) . "' style='vertical-align: bottom;' /></a>" . ($owned ? "(<a href='details.php?id=$id&amp;delsub=$subs[id]'>X</a>)" : "") . " ");

			print("<form method='post' enctype='multipart/form-data' action='takeuploadsub.php'>");
			print("<input type='hidden' name='id' value='$id' /><input type='hidden' name='title' value='$row[name]' />");

			print("<select name='language'><option value=0>(Välj språk)</option>");

			$languages = array("Swedish", "English", "Norwegian", "Danish", "Finnish", "Other");

			foreach ($languages AS $lang)
				print("<option value='" . strtolower(substr($lang, 0, 3)) . "'>$lang</option>\n");

			print("</select> <input type='file' name='file' size=30 /> <input type='submit' class='btn' value='Ladda upp' />");
			print("</form></td></tr>\n");
		}

		print("<tr><td class='form'>Beskrivning:</td><td><span id='beskr' style='cursor: pointer;'>Visa / dölj</span></td></tr>\n");
		print("<tr><td colspan=2>");

		print("<form method='post' action='comment.php?action=add'>\n");
		print("<input type='hidden' name='tid' value=$id />\n");
		print("<textarea name='text' rows=4 cols=50></textarea>\n");
		print("<br /><input type='submit' class='btn' value='Kommentera' />");

		$ratings = array(
			5 => "Toppen",
			4 => "Bra",
			3 => "Hyfsad",
			2 => "Dålig",
			1 => "Kass",
		);
		
		$rated = mysql_query("SELECT id FROM ratings WHERE torrentid = $id AND userid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$rated = mysql_fetch_array($rated);

		if (!$rated)
		{
			$rating = " <select name='rating'>\n";
			$rating .= "<option id='k0' value=0>(Betygsätt)</option>\n";

			foreach ($ratings AS $k => $v)
				$rating .= "<option value=$k>$k - $v</option>\n";

			$rating .= "</select>\n";

			print($rating);
		}

		print("</form></td></tr>\n");

		print("</table><div id=k7 style='display: none;'><table style='width: 100%;'><tr><td style='background-color: #e8e8e8;'><pre>" . format_comment($row["descr"]) . "</pre></td></tr>\n</table></div></div>\n");
		print("</td></tr></table>\n");

	}
	elseif ($type == 'files')
	{
		$s = "<div style='padding: 5px;'><table>\n";

		$subres = mysql_query("SELECT * FROM files WHERE torrentid = $id ORDER BY id");
		$s .= "<tr><td class='colhead'>Sökväg</td><td class='colhead' style='text-align: right;'>Storlek</td></tr>\n";

		while ($subrow = mysql_fetch_array($subres))
			$s .= "<tr class='nowrap'><td>" . $subrow["filename"] . "</td><td style='text-align: right;'>" . mksize($subrow["size"]) . "</td></tr>\n";

		$s .= "</table></div>\n";
		print($s);
		die;
	}
	elseif ($type == 'peers')
	{
		$downloaders = array();
		$seeders = array();
		$subres = mysql_query("SELECT * FROM peers WHERE fid = $row[id]") or sqlerr(__FILE__, __LINE__);
		$userid = $subrow["uid"];

		while ($subrow = mysql_fetch_array($subres))
		{
			if (!$subrow["left"])
				$seeders[] = $subrow;
			else
				$downloaders[] = $subrow;
		}

		function leech_sort($a, $b) {
			if (isset($_GET["usort"]))
				return seed_sort($a,$b);	
			$x = $a["left"];
			$y = $b["left"];
			if ($x == $y)
				return 0;
			if ($x < $y)
				return -1;
			return 1;
		}

		function seed_sort($a,$b) {
			$x = $a["uploaded"];
			$y = $b["uploaded"];
			if ($x == $y)
				return 0;
			if ($x < $y)
				return 1;
			return -1;
		}

		usort($seeders, "seed_sort");
		usort($downloaders, "leech_sort");

		print("<div style='padding: 5px;'>" . dltable("seedare", $seeders, $row) . "<br />" . dltable("leechare", $downloaders, $row) . "\n");
		die;
	}
	elseif ($type == 'nfo')
	{
		$res = mysql_query("SELECT descr FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
		
		print("<h2 style='margin: 0px; padding: 10px;'><img src='/pic/nfo.png' style='vertical-align: baseline;' /> <a href='details.php?id=$id'>$row[name]</a></h2>\n");
		print("<div style='padding: 10px; background-color: #e8e8e8;'><pre>" . format_comment($arr["descr"]) . "</pre></div>\n");
		die;
	}
	else
	{
		stdhead("Kommentarer för torrent \"" . $row["name"] . "\"");
		print("<h1>Kommentarer för <a href='details.php?id=$id'>$row[name]</a></h1>\n");
	}

	$subres = mysql_query("SELECT COUNT(*) FROM comments WHERE torrentid = $id");
	$subrow = mysql_fetch_row($subres);
	$count = $subrow[0];

	if ($count && !$type)
	{

		print("<p><a name=\"startcomments\"></a></p>\n");

		$subres = mysql_query("SELECT comments.*, torrents.owner, torrents.anonymous, users.username, users.class, users.title, users.avatar, users.warned, users.warned_reason, users.donor, users.crown, users.uploaded, users.downloaded, users.bad_avatar FROM comments LEFT JOIN users ON comments.userid = users.id LEFT JOIN torrents ON comments.torrentid = torrents.id WHERE comments.torrentid = $id ORDER BY comments.id DESC") or sqlerr(__FILE__, __LINE__);
		$allrows = array();

		while ($subrow = mysql_fetch_array($subres))
			$allrows[] = $subrow;

		commenttable($allrows);
	}

	foot();
?>