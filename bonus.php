<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

if ($_GET["expand"])
{
	$res = mysql_query("SELECT * FROM bonuslog WHERE userid = $CURUSER[id] ORDER BY added DESC LIMIT 10, 500") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
		print("<tr><td>$arr[added]</td><td>$arr[body]</td></tr>\n");
		
	die;
}

head("Bonus");

print("<img src='/pic/bonus.png' />\n");

print("<h3><a href='bonus.php' style='color: gray;'>Logg</a> / <a href='bonus2.php'>Shoppen</a></h3>\n");

$days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
$dagar = array("måndag", "tisdag", "onsdag", "torsdag", "fredag", "lördag", "söndag");

$bonuscheck = date("l H:i", strtotime("+7 days", strtotime($CURUSER["seedbonus_update"])));
$bonuscheck = str_ireplace($days, $dagar, $bonuscheck);

$timediff = strtotime("+7 days", strtotime($CURUSER["seedbonus_update"])) - time();

print("<table style='width: 300px; margin-bottom: 10px; font-size: 11pt;'>\n");
print("<tr><td>Antal bonuspoäng</td><td style='text-align: center;'><b>" . number_format($CURUSER["seedbonus"], 0, ".", " ") . "</b></td></tr>\n");
print("<tr><td colspan=2 style='text-align: center;'>Bonuscheck: <span id='cdown' style='font-weight: bold;'>-</span></td></tr>\n");
print("</table>\n");

if (get_user_class() >= UC_POWER_USER)
{
	print("<script type='text/javascript'>\n");
	?>

	timeDiff = <?=$timediff?>;

	function cDown()
	{
		var secs = timeDiff = timeDiff - 1;
		var days = Math.floor(secs/86400);
		secs -= days*86400;
		var hours = Math.floor(secs/3600);
		secs -= hours*3600;
		var minutes = Math.floor(secs/60);
		secs -= minutes*60;
	
		var time = new Array(hours, minutes, secs);
	
		for (i = 0; i < time.length; i++)
		{
			if (time[i] < 10)
			{
				time.splice(i, 1, '0' + time[i]);
			}
		}
	
		document.getElementById('cdown').innerHTML = days + "d " + time.join(":");
	
		if (timeDiff < 1)
		{
			document.getElementById('cdown').innerHTML = "<img src='/pic/load.gif' style='vertical-align: text-bottom;' />";
			setTimeout(function() {window.location.href = "/bonus.php";}, 5000);
		}
		else
		{
			setTimeout(cDown, 1000);
		}
	}

	cDown();

	<?php
	print("</script>\n");
}

print("<table><tr><td class='colhead'>Datum</td><td class='colhead'>Händelse</td></tr>\n");

$res = mysql_query("SELECT * FROM bonuslog WHERE userid = $CURUSER[id] ORDER BY added DESC LIMIT 10") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
	print("<tr><td>$arr[added]</td><td>$arr[body]</td></tr>\n");
	
print("<tr class='clear' id='showblog'><td colspan=2 style='text-align: center;'><a class='jlink' id='expandblog'>Visa alla</a></td></tr>\n");
print("</table>\n");

print("<div class='frame' style='width: 500px; margin-top: 10px;'>\n");
print("<h3>Vad är bonus?</h3>Bonusen delas ut en gång i veckan till alla <b>Power Users</b> och högre. 
Varje person får sin bonus utdelad på olika tidpunkter under veckan, detta för att spara på serverkraften.<br />
För mer information om när just din tidpunkt äger rum, se klockan under bonusloggan högst upp på denna sida.
<br /><br />
<h3>Hur räknas poängen ut?</h3>
<div style='padding: 10px; background-color: white; border-radius: 5px; border: 2px dashed gray;'>
<h3 style='margin: 10px 0px 5px 0px;'>Seed</h3><ul><li>Upp till <b>10GB</b> får du det du laddat upp gånger <b>2</b>.</li><li>Därefter får du <b>+1p</b> per uppladdad GB upp till <b>20GB</b>.</li><li>Efter 20GB uppladdad trafik får du <b>+0.5p</b> per uppladdad GB upp till <b>40GB</b>.</li><li>Ex; 30GB/vecka ger <b>+35p</b>.</li></ul>
<h3 style='margin: 10px 0px 5px 0px;'>Seedtid</h3><ul><li>Seed ger <b>+0.01p</b> per torrent och timme.</li><li>Ex; 20 aktiva torrents/vecka ger <b>+34p</b>.</li></ul>
<h3 style='margin: 10px 0px 5px 0px;'>Uppladdning</h3><ul><li>Uppladdning under nytt ger <b>+20p</b> per länk</li><li>Uppladdning under arkiv ger <b>+10p</b> per länk.</li><li>Ex; 2 nya länkar/vecka ger <b>+40p</b>.</li></ul>
<h3 style='margin: 10px 0px 5px 0px;'>Trailers</h3><ul><li>För varje tillagd trailer får du <b>+2p</b>.</li><li>Ex; 10 trailers/vecka ger <b>+20p</b>.</li></ul>
<h3 style='margin: 10px 0px 5px 0px;'>Onlinetid</h3><ul><li>Du får <b>+10p</b> per timme du är online.</li><li>Ex; 7 timmar online/vecka ger <b>+70p</b>.</li></ul>
<h3 style='margin: 10px 0px 5px 0px;'>Forumaktivitet</h3><ul><li>Aktivitet i forumet ger <b>+1p</b> per postat inlägg (med undantag för inlägg i lekforumet).</li><li>Ex; 10 inlägg/vecka ger <b>+10p</b>.</li></ul></div>\n");
print("</div>\n");

foot();

?>