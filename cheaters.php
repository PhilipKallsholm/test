<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_MODERATOR)
	stderr("Fel", "Tillgång nekad.");

function speedcalc($bytes)                                                                                                                                                                          
{                                                                                                                                                                                                                 
	$sizes = array('B/s', 'kB/s', 'MB/s', 'GB/s', 'TB/s', 'PB/s');                                                                                                                                                  
	$extension = $sizes[0];                                                                                                                                                                                           

	for ($i = 1; ($i < count($sizes) && $bytes >= 1024); $i++)                                                                                                                                           
	{                                                                                                                                                                                                                 
		$bytes /= 1024;                                                                                                                                                                                                   
		$extension = $sizes[$i];                                                                                                                                                                                          
	}                                                                                                                                                                                                                 

	return round($bytes, 2) . " " . $extension;                                                                                                                                                                     
}

/*$res = mysql_query("SELECT id FROM cheaters WHERE added < " . strtotime("-7 days")) or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_array($res))
	mysql_query("DELETE FROM cheaters WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);*/

head("Höghastighetsdetektorn");

begin_frame("Höghastighetsdetektorn");

print("<form method='post' action='cheaters.php'>\n");
print("<input type='hidden' name='search' value='search' />\n");
print("Användarnamn: <input type='text' name='username' size=80 />");
print("<input type='submit' class='submit' value='Sök' />");
print("</form>\n<br />");

if ($_GET["action"] == 'delete')
{
	$id = 0 + $_GET["id"];

	if ($id)
	{
		mysql_query("DELETE FROM cheaters WHERE id = $id") or sqlerr(__FILE__, __LINE__);

		print("<h2>Rad nr $id har nu blivit borttagen</h2>\n");
	}
}

print("<table>\n");

print("<tr><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=username'>Användarnamn</a></td><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=torrent'>Torrent</a></td><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=rate'>Hastighet</a></td><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=time'>Varaktighet</a></td><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=uploaded'>Uppladdat</a></td><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=ip'>DNS/IP</a></td><td class='colhead' style='text-align: center;'><a href='cheaters.php?type=client'>Klient</a></td><td class='colhead' style='text-align: center;'>Banna</td><td class='colhead' style='text-align: center;'>&nbsp;</td></tr>\n");

if ($username = trim($_POST["username"]))
	$where = "WHERE users.username = " . sqlesc($username);
else
	$where = '';

switch ($_GET["type"])
{
	case "username":
		$orderby = "ORDER BY users.username ASC";
		break;
	case "torrent":
		$orderby = "ORDER BY torrents.name ASC";
		break;
	case "rate":
		$orderby = "ORDER BY cheaters.rate DESC";
		break;
	case "time":
		$orderby = "ORDER BY cheaters.timediff DESC";
		break;
	case "uploaded":
		$orderby = "ORDER BY cheaters.upthis DESC";
		break;
	case "ip":
		$orderby = "ORDER BY cheaters.userip ASC";
		break;
	case "client":
		$orderby = "ORDER BY cheaters.client ASC";
		break;
	default:
		$orderby = "ORDER BY cheaters.rate DESC";
}

$res = mysql_query("SELECT cheaters.id AS cid, cheaters.added, cheaters.userid, cheaters.torrentid, cheaters.client, cheaters.rate, cheaters.beforeup, cheaters.upthis, cheaters.timediff, cheaters.userip, users.id AS uid, users.username, users.class, users.downloaded, users.uploaded, users.enabled, users.warned, users.warned_reason, torrents.name FROM cheaters LEFT JOIN users ON cheaters.userid = users.id LEFT JOIN torrents ON cheaters.torrentid = torrents.id $where $orderby") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	if($arr["downloaded"])
		$ratio = number_format($arr["uploaded"] / $arr["downloaded"], 3);
	else
		$ratio = "---";
		
	$ratio = "<span style='color: " . get_ratio_color($ratio) . ";'>$ratio</span>";

	$uppd = mksize($arr["upthis"]);
	$downd = mksize($arr["downloaded"]);
	$ip = long2ip($arr[userip]);
	$dom = @gethostbyaddr($ip);
	
	$keys = array("tbcn", "dsl", "mobile", "tre.se");
	
	foreach ($keys AS $key)
		if (stripos($dom, $key) !== false)	
			$susp = true;
			
	if ($arr["rate"] > 12582912)
		$susp = true;

	print("<tr" . ($susp && $arr["enabled"] == 'yes' ? " style='background-color: #ffcccc;'" : "") . ($arr["enabled"] == 'no' ? " style='background-color: #8f7171;'" : "") . "><td style='text-align: center;'><a href='userdetails.php?id=$arr[userid]'>$arr[username]</a>" . ($arr["warned"] == 'yes' ? "<img src='/pic/warnedsmall.gif' title='$arr[warned_reason]' />" : "") . "</td>");

	if (strlen($arr["name"]) > 45)
		$arr["name"] = substr($arr[name], 0, 43)."...";
	elseif (!strlen($arr["name"]))
		$arr["name"] = "Borttagen (id=$arr[torrentid])";

	print("<td style='text-align: center;'><a href='details.php?id=$arr[torrentid]'>$arr[name]</a></td>");
	print("<td style='text-align: center;'>" . speedcalc($arr["rate"]) . "</td>");
	print("<td style='text-align: center;'>" . mkprettytime($arr["timediff"]) . "</td>");
	print("<td style='text-align: center;'>$uppd</td>");

	if ($dom == $user["ip"] || @gethostbyname($dom) != $ip)
		$ipString = $ip . "(ingen DNS hittades)";
	else
	{
		if(strlen($dom) > 40)
			$dom = "...".substr($dom, -37);

		$ipString = $dom;
	}

	print("<td style='text-align: center;'>$ipString</td>");
	print("<td style='text-align: center;'>$arr[client]</td>");
	print("<td style='text-align: center;'>" . ($arr["enabled"] == 'no' ? "Bannad" : "<a class='jlink' onclick='banna()'>Banna</a>") . "</td>");
	print("<td><a href='cheaters.php?action=delete&amp;id=$arr[cid]'>X</a></td></tr>\n");
}

print("</table>\n");
print("</div></div>\n");
foot();

?>