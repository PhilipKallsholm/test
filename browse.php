<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

$cats = genrelist();

$searchstr = trim(html_entity_decode($_GET["search"], ENT_QUOTES, "UTF-8"));
$cleansearchstr = htmlspecialchars($searchstr);

if (!$cleansearchstr)
	unset($cleansearchstr);

if ($_GET["sort"] && $_GET["type"])
{
	$column = "";
	$ascdesc = "";

	switch ($_GET["sort"])
	{
		case 1: $column = "torrents.name"; break;
		case 2: $column = "torrents.numfiles"; break;
		case 3: $column = "torrents.comments"; break;
		case 4: $column = "torrents.added"; break;
		case 5: $column = "torrents.size"; break;
		case 6: $column = "(SELECT COUNT(*) FROM snatched WHERE torrentid = torrents.id AND done != '0000-00-00 00:00:00')"; break;
		case 7: $column = "(SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0)"; break;
		case 8: $column = "(SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` > 0)"; break;
		case 9: $column =  staff() ? "torrents.ownername" : "torrents.anonymous DESC, torrents.ownername"; break;
		case 10: $column = "torrents.imdb_rating"; break;
		case 11: $column = "torrents.pretime"; break;
		default: $column = "torrents.id";
	}

	switch ($_GET["type"])
	{
		case 'asc': $ascdesc = "ASC"; break;
		case 'desc': $ascdesc = "DESC"; break;
		default: $ascdesc = "DESC";
	}

	$orderby = "ORDER BY " . $column . " " . $ascdesc;
	$pagerlink = "sort=" . $_GET["sort"] . "&type=" . $_GET["type"] . "&";
}
else
{
	$orderby = "ORDER BY torrents.id DESC";
	$pagerlink = "";
}

$addparam = "";
$wherea = array();
$wherecatina = array();

if ($_GET["incldead"] == 1)
{
	$addparam .= "incldead=1&amp;";
	if (get_user_class() < UC_ADMINISTRATOR)
		$wherea[] = "banned != 'yes'";
}
elseif ($_GET["incldead"] == 2)
{
	$addparam .= "incldead=2&amp;";
	$wherea[] = "visible = 'no'";
}
else
	$wherea[] = "visible = 'yes'";

if ($_GET["freeleech"])
{
	$addparam .= "freeleech=1&amp;";
	$wherea[] = "freeleech = 'yes'";
}

if (!isset($_GET["search"]) && (($CURUSER["browse"] == 'old' && !$_GET["new"]) || $_GET["old"]))
{
	if ($_GET["browse"] == 'archive')
	{
		$addparam .= "old=1&amp;browse=archive&amp;";
      	$browse = "old=1&amp;browse=archive&amp;";
		$wherea[] = "req = 2";
		$var = "req = 2 AND ";
	}
	elseif ($_GET["browse"] == 'requests')
	{
		$addparam .= "old=1&amp;browse=requests&amp;";
      	$browse = "old=1&amp;browse=requests&amp;";
		$wherea[] = "req = 1";
		$var = "req = 1 AND ";
	}
	elseif ($_GET["browse"] == 'all' || !$_GET["browse"])
	{
		$addparam .= "old=1&amp;browse=all&amp;";
		$browse = "old=1&amp;browse=all&amp;";
		$wherea[] = "req > 0";
		$var = "req > 0 AND ";
	}
	$old = 1;
}
elseif (!isset($_GET["search"]) && ($CURUSER["browse"] == 'new' || $_GET["new"]))
{
	$addparam .= "new=1&amp;";
	$browse = "new=1&amp;";
	$wherea[] = "req = 0";
	$var = "req = 0 AND ";
	$new = 1;
}

if ($genre = trim($_GET["genre"]))
{
	$addparam .= "genre=" . $genre . "&amp;";
	$wherea[] = "imdb_genre LIKE '%" . sqlwildcardesc($genre) . "%'";

	//mysql_query("INSERT INTO searches (userid, genre) VALUES($CURUSER[id], " . sqlesc($_GET[genre]) . ")") or sqlerr();
}
	
$category = 0 + $_GET["cat"];
$all = $_GET["all"];

if (!$all)
{
	if (!$category && !isset($_GET["search"]) && $CURUSER["notifs"])
	{
		foreach ($cats as $cat)
		{
			if (strpos($CURUSER["notifs"], "[cat" . $cat["id"] . "]") !== false)
			{
				$wherecatina[] = $cat["id"];
				$addparam .= "c$cat[id]=1&amp;";
			}
		}
	}
	elseif ($category)
	{
		$wherecatina[] = $category;
		$addparam .= "cat=$category&amp;";
	}
	else
	{
		$all = true;
		foreach ($cats as $cat)
		{
			$all &= $_GET["c$cat[id]"];

			if ($_GET["c$cat[id]"])
			{
				$wherecatina[] = $cat["id"];
				$addparam .= "c$cat[id]=1&amp;";
			}
		}
	}
}

if ($all)
{
	$wherecatina = array();
	$addparam .= "all=1&amp;";
}

if (count($wherecatina) > 1)
{
	$wherecatin = implode(",", $wherecatina);
	$wherea[] = "category IN(" . $wherecatin . ")";
}
elseif (count($wherecatina) == 1)
	$wherea[] = "category = $wherecatina[0]";

if (isset($_GET["search"]))
	$addparam .= "search=" . urlencode($searchstr) . "&amp;";
	
if ($_GET["descr"])
	$addparam .= "descr=1&amp;";

$wherebase = $wherea;

if (isset($cleansearchstr))
{
	$searcha = explode(" ", $searchstr);
	$sc = 0;
	
	foreach ($searcha as $searchs)
	{
		if (strlen($searchs) <= 1)
			continue;
			
		if (++$sc > 5)
			break;
			
		$ssa = array();
		foreach (array("torrents.name", "torrents.descr") as $sss)
		{
			$ssa[] = "$sss LIKE '%" . sqlwildcardesc(str_replace(array("å", "ä", "ö", "Å", "Ä", "Ö"), array("a", "a", "o", "A", "A", "O"), $searchs)) . "%'";

			if (!$_GET["descr"])
				break;
		}
		
		$wherea[] = ($sc == 1 ? "(" : "") . "(" . implode(" OR ", $ssa) . ")";
	}
	
	if ($sc)
	{
		$where = implode(" AND ", $wherea);
		$where .= " OR torrents.imdb_stars LIKE '%" . mysql_real_escape_string(htmlentities($searchstr, ENT_QUOTES, "UTF-8")) . "%')";
	}
}
else
	$where = implode(" AND ", $wherea);

if ($where)
	$where = "WHERE $where";

$res = mysql_query("SELECT COUNT(*) FROM torrents $where") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_array($res);
$count = $row[0];

$torrentsperpage = $CURUSER["torrentsperpage"];

if (!$torrentsperpage)
	$torrentsperpage = 15;

if ($count)
{
	if ($addparam)
	{
		if ($pagerlink)
		{
			if ($addparam{strlen($addparam)-1} != ";")
				$addparam = $addparam . "&" . $pagerlink;
			else
				$addparam = $addparam . $pagerlink;
		}
	} else
		$addparam = $pagerlink;
	
	list($pager, $limit) = pager("browse.php?" . $addparam . "page=", $count, $torrentsperpage, $_GET["page"], true, true);
	
	$res = mysql_query("SELECT torrents.id, torrents.anonymous, torrents.category, torrents.save_as, torrents.name, torrents.visible, (SELECT COUNT(*) FROM snatched WHERE torrentid = torrents.id AND done != '0000-00-00 00:00:00') AS times_completed, torrents.size, 
torrents.freeleech, torrents.req, torrents.imdb, torrents.imdb_rating, torrents.imdb_genre, torrents.imdb_poster, torrents.pretime, torrents.added, torrents.comments, torrents.numfiles, 
torrents.filename, torrents.owner, torrents.nuked, torrents.nukedreason, torrents.ownername AS username, (SELECT COUNT(*) FROM peers WHERE peers.fid = torrents.id AND `left` = 0 AND active = 1) AS seeders, (SELECT COUNT(*) FROM peers WHERE peers.fid = torrents.id AND `left` > 0 AND active = 1) AS leechers, torrents.subs, torrents.reqid, torrents.p2p 
FROM torrents $where $orderby $limit") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
		$tor[] = $arr;
}

if (isset($cleansearchstr))
	head("Sökresultat för \"$cleansearchstr\"");
else
	head("Bläddra");

print("<table><tr><td style='padding: 10px; text-align: center;'><a href='browse.php?new=1'><span style='font-size: 10pt;" . ($new && !isset($_GET["search"]) ? " color: #999999;" : "") . "'>Nytt</span></a></td><td style='padding: 10px; text-align: center;'><a href='browse.php?old=1'><span style='font-size: 10pt;" . ($old && !isset($_GET["search"]) ? " color: #999999;" : "") . "'>Gammalt</span></a></td></tr></table>\n");

if ($old)
	print("<table style='margin-top: 10px;'><tr><td  style='text-align: center;'><a href='browse.php?old=1&amp;browse=all'><span style='font-size: 10pt;" . ($_GET["browse"] == 'all' || !$_GET["browse"] ? " color: #999999;" : "") . "'>Alla</span></a></td><td style='text-align: center;'><a href='browse.php?old=1&amp;browse=requests'><span style='font-size: 10pt;" . ($_GET["browse"] == 'requests' ? " color: #999999;" : "") . "'>Requests</font></a></td><td style='text-align: center;'><a href='browse.php?old=1&amp;browse=archive'><span style='font-size: 10pt;" . ($_GET["browse"] == 'archive' ? " color: #999999;" : "") . "'>Arkiv</font></a></td></tr></table>\n");

print("<form method='get' action='browse.php'>\n");
print("<table style='margin-top: 20px;'>\n");
print("<tr class='clear'><td colspan=5 style='text-align: center;'>");
print("<table class='clear' style='width: 100%; text-align: center;'>\n<tr>");

$cattype = mysql_query("SELECT * FROM cattype ORDER BY name") or sqlerr(__FILE__, __LINE__);
$catsperrow = 4;

$c = 0;
while ($type = mysql_fetch_assoc($cattype))
{
	$c++;

	print("<td title='$type[name]' style='padding-bottom: 2px; padding-left: 7px; vertical-align: top; background-color: " . ($c % 2 ? "#ffffff" : "#f1f1f1") . ";'><a class='jlink' onclick=\"catMark('$type[name]')\" style='font-size: 10pt;'>$type[name]</a>");

	$i = 0;
	foreach ($cats as $cat)
	{
		if ($cat["cattype"] == $type["id"])
		{	
			if ($i && $i % $catsperrow == 0)
				print("</td><td title='$type[name]' style='padding-bottom: 2px; padding-left: 7px; vertical-align: top; background-color: " . ($c % 2 ? "#ffffff" : "#f1f1f1") . ";'>");
			
			print("<br /><input name=c$cat[id] type='checkbox' value=1" . (in_array($cat["id"], $wherecatina) ? " checked" : "") . " /><a href='browse.php?" . $browse . "cat=$cat[id]' style='font-weight: normal;'>" . $cat["name"] . "</a>\n");
			$i++;
		}
	}
	print("</td>");
}

print("</tr></table>\n</td></tr>\n<tr class='nowrap'>");
print("<td><input type='text' name='search' id='searchinput' class='search' style='width: 457px;' value='" . ($cleansearchstr ? $cleansearchstr : "Nyckelord eller skådespelare...") . "' autocomplete='off' /></td>");
print("<td><input type='checkbox' name='freeleech' value=1" . ($_GET["freeleech"] ? " checked" : "") . " />Fri leech</td>");
print("<td><select name='incldead'>");

print("<option value=0>Aktiva</option>");
print("<option value=1>Inklusive döda</option>");
print("<option value=2>Endast döda</option>");

print("</select></td>");
print("<td><input type='checkbox' name='descr' value=1" . ($_GET["descr"] ? " checked" : "") . " /> Beskrivning</td>");
print("<td style='text-align: center;'><input type='submit' class='btn' id='linksearch' value='Sök' /></td></tr>\n");

if ($_GET["browse"])
	$browsarn = $_GET["browse"];
else
	$browsarn = "all";

print("<tr><td class='colhead' colspan=5 style='text-align: center;'><a href='browse.php?browse=" . $browsarn . "&amp;all=1'>Visa alla kategorier</a> / <a href='needseed.php'>Torrents som behöver seedas</a></td></tr>\n</table></form>\n");
print("<div id='cover' style='display: none;'></div>\n");

if (isset($cleansearchstr))
	print("<h2 style='margin-top: 10px;'>Sökresultat för \"" . $cleansearchstr . "\"</h2>\n");

if ($count)
{
	print("<p>$pager</p>\n");
	
	torrenttable($tor, "index", false, false, $old);
	
	print("<p>$pager</p>\n");
}
elseif (isset($cleansearchstr))
	print("<h2 style='margin-top: 10px;'>Inga träffar hittades</h2>\n");
else
	print("<h2 style='margin-top: 10px;'>Inga länkar</h2>\n");

mysql_query("UPDATE users SET " . ($old ? "last_reqbrowse" : "last_browse") . " = '" . get_date_time() . "' WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

foot();
?>