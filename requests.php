<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

head("Requests");

$params = array();
$wherea = array();
$wherecatina = array();

switch ($_GET["type"])
{
	case 'asc':
		$ad = "ASC";
		break;
	case 'desc':
		$ad = "DESC";
		break;
}

if ($ad)
	$params["type"] = "type=" . $_GET["type"];

switch ($_GET["sort"])
{
	case 1:
		$order = "requests.name";
		
		if (!$ad)
			$ad = "ASC";
		
		if ($ad == 'ASC')
			$ascdesc1 = "desc";
		elseif ($ad == 'DESC')
			$ascdesc1 = "asc";
		
		break;
	case 2:
		$order = "requests.votes";
		
		if (!$ad)
			$ad = "DESC";
		
		if ($ad == 'ASC')
			$ascdesc2 = "desc";
		elseif ($ad == 'DESC')
			$ascdesc2 = "asc";
		
		break;
	case 3:
		$order = "requests.added";
		
		if (!$ad)
			$ad = "DESC";
		
		if ($ad == 'ASC')
			$ascdesc3 = "desc";
		elseif ($ad == 'DESC')
			$ascdesc3 = "asc";
		
		break;
	case 4:
		$order = "users.username";
		
		if (!$ad)
			$ad = "ASC";
		
		if ($ad == 'ASC')
			$ascdesc4 = "desc";
		elseif ($ad == 'DESC')
			$ascdesc4 = "asc";
		
		break;
	case 5:
		$order = "requests.points";
		
		if (!$ad)
			$ad = "DESC";
		
		if ($ad == 'ASC')
			$ascdesc5 = "desc";
		elseif ($ad == 'DESC')
			$ascdesc5 = "asc";
		
		break;
	default:
		$order = "requests.added";
		$ad = "DESC";
}

if ($_GET["sort"])
	$params["sort"] = "sort=" . $_GET["sort"];
	
$wherea[] = "requests.uploaderid = 0";
	
$cats = genrelist();
$category = 0 + $_GET["cat"];
$all = $_GET["all"];

if (!$all)
{
	if ($category)
	{
		$wherecatina[] = $category;
		$params["cat"] = "cat=" . $category;
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
				$params["c$cat[id]"] = "c" . $cat["id"] . "=1";
			}
		}
	}
}

if ($all)
{
	$wherecatina = array();
	$params["all"] = "all=1";
}

if (count($wherecatina) > 1)
{
	$wherecatin = implode(",", $wherecatina);
	$wherea[] = "requests.category IN(" . $wherecatin . ")";
}
elseif (count($wherecatina) == 1)
	$wherea[] = "requests.category = $wherecatina[0]";
	
$search = trim($_GET["search"]);
	
if ($search)
{
	$searcha = explode(" ", $search);
	$sc = 0;
	
	foreach ($searcha as $searchs)
	{
		if (strlen($searchs) <= 1)
			continue;
			
		if (++$sc > 5)
			break;
		
		$wherea[] = "requests.name LIKE '%" . mysql_real_escape_string($searchs) . "%'";
	}
	
	$params["search"] = "search=" . urlencode($search);
}

$where = implode(" AND ", $wherea);

if ($where)
	$where = "WHERE $where";

print("<form method='get' action='requests.php'>\n");
print("<table style='margin-bottom: 20px;'>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'>");
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
			
			print("<br /><input name=c$cat[id] type='checkbox' value=1" . (in_array($cat["id"], $wherecatina) ? " checked" : "") . " /><a href='requests.php?cat=$cat[id]' style='font-weight: normal;'>" . $cat["name"] . "</a>\n");
			$i++;
		}
	}
	print("</td>");
}

print("</tr></table>\n</td></tr>\n<tr class='nowrap'>");
print("<td><input type='text' name='search' id='searchinput' class='search' style='width: 500px;' /></td>");
print("<td style='text-align: center;'><input type='submit' class='btn' id='linksearch' value='Sök' /></td></tr>\n");

print("</table></form>\n");

$s = "<select name='cat' id='cat'>\n<option value=0>(Välj en)</option>\n";

$cattypes = mysql_query("SELECT * FROM cattype ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($cattype = mysql_fetch_assoc($cattypes))
{
	$s .= "<optgroup label='$cattype[name]'>";

	$categories = mysql_query("SELECT id, name, cattype FROM categories WHERE cattype = $cattype[id] ORDER BY name") or sqlerr(__FILE__, __LINE__);

	while ($row = mysql_fetch_assoc($categories))
		$s .= "<option value=$row[id]>$row[name]</option>\n";
		
	$s .= "</optgroup>";
}

$s .= "</select>";

print("<form method='post' action='addreq.php' id='addreq' style='display: block; margin-bottom: 20px;'>\n");
print("<div class='errormess' id='errormess'></div>\n");
print("<table class='clear' style='background-color: #ededed; border: 1px solid rgba(0, 0, 0, 0.1);'>\n");
print("<tr><td class='form' rowspan=2>Request</td><td class='reqinfo'><input type='radio' name='type' value='release' /> <input type='text' name='release' value='Releasenamn...' class='search' size=50 disabled /><br /><span class='small'>(Releasenamn - specifik release)</span></td></tr>\n");
print("<tr><td class='reqinfo'><input type='radio' name='type' value='imdb' /> <input type='text' name='imdb' value='IMDb-länk...' class='search' size=50 disabled /><br /><span class='small'>(IMDb - ej specifik release)</span></td></tr>\n");
print("<tr style='display: none;' id='tv'><td class='form'></td><td class='small'>Säsong: <input type='text' name='season' size=3 style='margin-right: 20px;' />Episod: <input type='text' name='episode' size=3 /><br /><span class='small'>(Enbart för TV-serier)</span></td></tr>\n");
print("<tr><td class='form'>Kategori</td><td>$s</td></tr>\n");
print("<tr><td class='form'>Hittelön</td><td><input type='text' name='points' size=10 /><br /><span class='small'>(Dras från dina bonuspoäng och ges till eventuell uppladdare)</span></td></tr>\n");
print("<tr id='del' style='display: none;'><td class='form'>Radera</td><td><input type='checkbox' name='del' value=1 /> <input type='text' name='reason' value='Anledning...' class='search' size=50 disabled /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' name='add' value='Lägg till' /> <input type='button' value='Avbryt' id='canceledit' style='display: none;' /></td></tr>\n");
print("</table>\n</form>\n");

$res = mysql_query("SELECT COUNT(requests.id) FROM requests $where") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_row($res);
$count = $row[0];

list($pager, $limit) = pager("requests.php?" . (count($params) ? implode("&amp;", $params) . "&amp;" : "") . "page=", $count, 50, $_GET["page"], true, true);

unset($params["sort"]);
unset($params["type"]);

if ($params = implode("&amp;", $params))
	$params .= "&amp;";

print("<p style='text-align: center;'>$pager</p>\n");

print("<table id='reqtable'><tr><td class='colhead' colspan=2><a href='requests.php?" . $params . "sort=1" . ($ascdesc1 ? "&amp;type=$ascdesc1" : "") . "'>Request</a></td><td class='colhead'>Rapportera</td><td class='colhead'><a href='requests.php?" . $params . "sort=2" . ($ascdesc2 ? "&amp;type=$ascdesc2" : "") . "'>Rösta</a></td><td class='colhead'><a href='requests.php?" . $params . "sort=3" . ($ascdesc3 ? "&amp;type=$ascdesc3" : "") . "'>Tillagd</a></td><td class='colhead'><a href='requests.php?" . $params . "sort=4" . ($ascdesc4 ? "&amp;type=$ascdesc4" : "") . "'>Ansökare</a></td><td class='colhead'><a href='requests.php?" . $params . "sort=5" . ($ascdesc5 ? "&amp;type=$ascdesc5" : "") . "'>Hittelön</a></td><td class='colhead'>Ladda upp</td></tr>\n");

$res = mysql_query("SELECT requests.*, users.username FROM requests LEFT JOIN users ON requests.userid = users.id $where ORDER BY $order $ad $limit") or sqlerr(__FILE__, __LINE__);
	
while ($arr = mysql_fetch_assoc($res))
{
	$cat = mysql_query("SELECT name, image FROM categories WHERE id = $arr[category]") or sqlerr(__FILE__, __LINE__);
	$cat = mysql_fetch_assoc($cat);
	
	$voted = mysql_query("SELECT id FROM reqvotes WHERE reqid = $arr[id] AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	$voted = mysql_num_rows($voted) ? true : false;
	
	if ($arr["username"])
		$user = "<a href='userdetails.php?id=$arr[userid]'>$arr[username]</a>";
	else
		$user = "<i>Borttagen</i>";
		
	$new = $arr["added"] > $CURUSER["last_requestbrowse"] ? " <span style='color: red; font-weight: bold;'>NY</span>" : "";
	$edit = staff() || $CURUSER["id"] == $arr["userid"] ? "<span style='float: right; margin-left: 10px; font-size: 8pt; font-style: italic;'>(<a class='jlink' onClick=\"editReq($arr[id])\">ändra</a>)</span>" : "";
		
	if ($arr["season"])
	{
		$tv = "<br /><span class='small' style='font-style: italic;'>";
		$tv .= "Säsong " . $arr["season"];
		
		if ($arr["episode"])
			$tv .= ", episod " . $arr["episode"];
			
		$tv .= "</span>";
	}
	else
		$tv = "";
	
	print("<tr id='r$arr[id]'><td title='$cat[name]' style=\"width: 40px; height: 30px; padding: 0px; background-image: url('$cat[image]'); background-repeat: no-repeat; background-position: 0px -7px; cursor: pointer;\" onClick=\"window.location.href = 'requests.php?cat=$arr[category]'\"></td><td style='padding: 0px 5px; font-size: 9pt;'>" . ($arr["link"] ? "<a href='$arr[link]' target='_blank'>$arr[name]</a>" : $arr["name"]) . $new . $edit . $tv . "</td><td><a class='jlink' onClick=\"report($arr[id], 'request')\">Rapportera</a></td><td id='v$arr[id]' style='text-align: center;'><a class='jlink' onClick=\"vote($arr[id])\"" . ($voted ? " style='color: gray;'" : "") . ">$arr[votes]</a></td><td>$arr[added]</td><td>$user</td><td id='p$arr[id]' style='text-align: center;'><i>" . number_format($arr["points"]) . " p</i></td><td><a href='upload.php/$arr[id]'>Ladda upp</a></td></tr>\n");
}

print("</table>\n");

print("<p style='text-align: center;'>$pager</p>\n");

mysql_query("UPDATE users SET last_requestbrowse = '" . get_date_time() . "' WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

foot();
?>