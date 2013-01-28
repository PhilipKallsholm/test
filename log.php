<?php

require_once("globals.php");

dbconn(false);
loggedinorreturn();
parked();

function torrlink($matches)
{
	$res = mysql_query("SELECT id FROM torrents WHERE name LIKE " . sqlesc($matches[1])) or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
			
	return "<a href='details.php?id=$arr[id]'>$matches[1]</a>";
}

head("Swepiracy logg");

print("<h1>Swepiracy logg</h1>\n");

$engdays = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
$swedays = array("Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag");

$res = mysql_query("SELECT DATE(added) AS added FROM sitelog GROUP BY DATE(added) ORDER BY added DESC LIMIT 7");

$days = array();

while ($row = mysql_fetch_assoc($res))
{	
	$day = str_replace($engdays, $swedays, date("l", strtotime($row["added"])));
	$days[] = "<a href='log.php?date={$row["added"]}'" . ($_GET["date"] == $row["added"] || !$_GET["date"] && $row["added"] == date("Y-m-d") ? " style='color: gray; font-size: 10pt;'": "") . ">$day</a>";
}

print("<p>" . implode(" | ", $days) . "</p>\n");

$date = $_GET["date"] ? sqlesc($_GET["date"]) : "DATE(NOW())";

$res = mysql_query("SELECT added, body, name, anonymous FROM sitelog WHERE DATE(added) = $date ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<i>Loggen är tom</i>\n");
else
{
	print("<table style='width: 90%;'>\n");
	print("<tr><td class='colhead' style='width: 1%;'>Datum</td><td class='colhead' style='width: 1%;'>Tid</td><td class='colhead'>Händelse</td></tr>\n");

	while ($arr = mysql_fetch_assoc($res))
	{
		$colors = array("#ccffcc" => array("laddades upp av", "requestade"), "#ffcccc" => array("tog bort", "togs bort av", "rensades automatiskt"), "#96BFE0" => array("ändrades av"), "black" => array());
	
		foreach ($colors AS $color => $keywords)
			foreach ($keywords AS $keyword)
				if (stripos($arr["body"], $keyword) !== false)
					break 2;

		$datetime = explode(" ", $arr["added"]);

		$date = $datetime[0];
		$time = $datetime[1];

		if (get_user_class() < UC_MODERATOR && $arr["anonymous"] == 'yes')
			$arr["body"] = str_replace("NAMNET", "<i>Anonym</i>", $arr["body"]);
		else
			$arr["body"] = str_replace("NAMNET", $arr["name"], $arr["body"]);
			
		$arr["body"] = preg_replace_callback("#<b>(.+?-.+?)</b>#i", torrlink, $arr["body"]);

		print("<tr style='background-color: $color; white-space: nowrap;'><td>$date</td><td>$time</td><td>$arr[body]</td></tr>\n");
	}
	print("</table>\n");
}

print("<p><i>Tidzonen är CET</i></p>\n");

foot();

?>