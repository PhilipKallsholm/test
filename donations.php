<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

if ($_POST)
{
	foreach ($_POST["id"] AS $id)
	{
		$id = 0 + $id;
		$donated = 0 + $_POST["donated"][$id];
		$verified = $_POST["verified"][$id] == 'yes' ? "yes" : "no";
		$count = $_POST["count"][$id];
		
		$res = mysql_query("SELECT * FROM donations WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
		
		if (!$arr)
			continue;
			
		$freeleech = round(($donated / 100) * 24);
		$freeleech = get_date_time(strtotime("+{$freeleech} hours"));
		$uploaded = round(($donated / 2) * 1073741824);
		
		mysql_query("UPDATE donations SET donated = $donated, verified = " . sqlesc($verified) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		
		if ($verified != $arr["verified"])
		{
			if ($verified == 'yes')
			{
				$subject = "Tack för ditt bidrag!";
				$body = "Stort [b]TACK[/b] för att du stödjer Swepiracy! Du har nu fått din utlovade belöning. ;)\n\nMed vänliga hälsningar Bossman";
				$dt = get_date_time();

				mysql_query("UPDATE users SET donor = 'yes', donated = donated + $donated, uploaded = uploaded + $uploaded, freeleech = '$freeleech', seedbonus = seedbonus + $donated WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
				mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($arr["userid"], $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
				
				if ($count)
					mysql_query("UPDATE autovalues SET value = value + $donated WHERE name = 'donations'") or sqlerr(__FILE__, __LINE__);
			}
			else
			{
				mysql_query("UPDATE users SET donor = 'no', donated = donated - $donated, uploaded = uploaded - $uploaded, freeleech = '0000-00-00 00:00:00', seedbonus = seedbonus - $donated WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
				
				if ($count)
					mysql_query("UPDATE autovalues SET value = value - $donated WHERE name = 'donations'") or sqlerr(__FILE__, __LINE__);
			}
		}
	}
	die;
}

head("Donationer");

$res = mysql_query("SELECT COUNT(userid) AS donations, SUM(donated) AS totaldonated FROM donations") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_array($res);

$res2 = mysql_query("SELECT SUM(donated) AS monthdonated FROM donations WHERE MONTH(NOW()) = MONTH(added) AND YEAR(NOW()) = YEAR(added)") or sqlerr(__FILE__, __LINE__);
$row2 = mysql_fetch_array($res2);

$res3 = mysql_query("SELECT SUM(donated) AS record, MONTHNAME(added) AS month, YEAR(added) AS year FROM donations GROUP BY MONTH(added), YEAR(added) ORDER BY record DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
$row3 = mysql_fetch_array($res3);

print("<h3>$row[donations] donationer har gjorts vilka sammanlagt blir " . number_format($row["totaldonated"]) . " kr</h3>");
print("<h3>Denna månad har " . number_format($row2["monthdonated"]) . " kr kommit in</h3>");
print("<h3>Donationsrekordet sattes i $row3[month] år $row3[year] då " . number_format($row3["record"]) . " kr donerades</h3>");

$res = mysql_query("SELECT id FROM donations") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("donations.php?page=", $count, 25, $_GET["page"]);

print("<p>$pager</p>\n");

print("<form method='post' action='donatations.php' id='donationsform'><table>\n");
print("<tr><td class='colhead'>Avsändare</td><td class='colhead'>Summa</td><td class='colhead'>Tjänst</td><td class='colhead'>Kod</td><td class='colhead'>Kommentar</td><td class='colhead' colspan=2>Verifierad</td><td class='colhead'>Månad</td></tr>\n");

$res = mysql_query("SELECT * FROM donations ORDER BY added DESC $limit") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
	
	if ($user = mysql_fetch_assoc($user))
		$username = "<a href='userdetails.php?id=$arr[userid]'>$user[username]</a>";
	else
		$username = "<i>Borttagen</i>";
		
	print("<tr style='background-color: " . ($arr["verified"] == 'yes' ? "#66cc33" : "#ff3333") . ";'><td>$username</td><td><input type='hidden' name='id[]' value=$arr[id] /><input type='text' name='donated[$arr[id]]' size=5 value=$arr[donated] /></td><td>$arr[method]</td><td>$arr[code]</td><td>$arr[comment]</td><td><input type='checkbox' name='count[$arr[id]]' value=1 /></td><td><input type='radio' name='verified[$arr[id]]' value='yes'" . ($arr["verified"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='verified[$arr[id]]' value='no'" . ($arr["verified"] != 'yes' ? " checked" : "") . " />Nej</td><td>" . date("F", strtotime($arr["added"])) . "</td></tr>\n");
}

print("<tr class='clear'><td colspan=8 style='padding: 5px 0px; text-align: right;'><input type='submit' value='Uppdatera' /></td></tr>\n");

print("</table></form>\n");
print("<p>$pager</p>\n");
foot();

?>