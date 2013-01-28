<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

notifo_send("Swepiracy", "Test", "Testar lite barra", "staff.php");

$dt = get_date_time(strtotime("-30 days"));
mysql_query("DELETE FROM stafflog WHERE added < '$dt'") or sqlerr(__FILE__, __LINE__);

head("Stafflog");

print("<h1>Staffloggen</h1>\n");

$res = mysql_query("SELECT DATE(added) AS added FROM stafflog GROUP BY DATE(added) ORDER BY added DESC LIMIT 14");

$count = 0;
while ($row = mysql_fetch_assoc($res))
{
	if (++$count != 1)
		echo "&nbsp;|&nbsp;";
		
	print("<a href='stafflog.php?date=$row[added]'>$row[added]</a>");
}

$date = $_GET["date"] ? sqlesc($_GET["date"]) : "CURDATE()";

$res = mysql_query("SELECT added, txt FROM stafflog WHERE DATE(added) = $date ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<b>Loggen är tom</b>\n");
else
{
	print("<table style='width: 600px;'>\n");
	print("<tr><td class='colhead' style='width: 1%;'>Datum</td><td class='colhead' style='width: 1%;'>Tid</td><td class='colhead'>Händelse</td></tr>\n");
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$color = 'black';
		
		$reds = array("degraderade", "tog bort", "togs bort", "togs automatiskt bort", "drog bort", "nekade", "inaktiverade", "varnade", "bannades");
		$greens = array("lade till", "gav", "uppgraderade", "höjde", "accepterade", "skapade");
		$blues = array("aktiverade", "gav tillbaka", "benådade", "parkerade", "återställde");
		$yellows = array("startade", "avgjorde", "tog bort spelet");
		$blacks = array("försökte", "tillät", "uppgav");
		
		$colors = array("#ee9695" => $reds, "#c4e7bf" => $greens, "#b0d1ff" => $blues, "#999900" => $yellows, "black; color: white" => $blacks);
		
		foreach ($colors AS $color => $words)
		{
			foreach ($words AS $word)
			{
				if (stripos($arr["txt"], $word) !== false)
					break 2;
			}
		}

		$date = date("Y-m-d", strtotime($arr["added"]));
		$time = date("H:i:s", strtotime($arr["added"]));

		print("<tr style='background-color: $color;'><td>$date</td><td>$time</td><td>$arr[txt]</td></tr>\n");
	}

	print("</table>\n");
}

foot();

?>