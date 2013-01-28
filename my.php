<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Kontoinställningar");

if ($_GET["mail"])
	print("<h3>Mail ändrad!</h3>\n");

begin_frame("Kontoinställningar");

$clist = "<select name='country'>\n";
$clist .= "<option value=0>- Välj -</option>\n";

$countries = mysql_query("SELECT * FROM countries ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($country = mysql_fetch_assoc($countries))
	$clist .= "<option value=$country[id]" . ($country["id"] == $CURUSER["country"] ? " selected" : "") . ">$country[name]</option>\n";
	
$clist .= "</select>\n";

$age = explode("-", $CURUSER["age"]);

$ylist = "<select name='age[]'" . (0 + $age[0] ? " disabled" : "") . ">\n";
$ylist .= "<option value=0>- Välj år -</option>\n";

for ($i = date("Y"); $i >= (date("Y") - 80); $i--)
	$ylist .= "<option value=$i" . ($age[0] == $i ? " selected" : "") . ">$i</option>\n";
	
$ylist .= "</select>\n";
	
$months = array("Januari", "Februari", "Mars", "April", "Maj", "Juni", "Juli", "Augusti", "September", "Oktober", "November", "December");

$mlist = "<select name='age[]'" . (0 + $age[1] ? " disabled" : "") . ">\n";
$mlist .= "<option value=0>- Välj månad -</option>\n";

foreach ($months AS $id => $month)
	$mlist .= "<option value=" . ($id + 1) . ($age[1] == ($id + 1) ? " selected" : "") . ">$month</option>\n";
	
$mlist .= "</select>\n";

$dlist = "<select name='age[]'" . (0 + $age[2] ? " disabled" : "") . ">\n";
$dlist .= "<option value=0>- Välj dag -</option>\n";

for ($i = 31; $i >= 1; $i--)
	$dlist .= "<option value=$i" . ($age[2] == $i ? " selected" : "") . ">$i</option>\n";
	
$dlist .= "</select>\n";

print("<form method='post' action='takemy.php' id='profform'>\n");

print("<fieldset><legend>Foruminställningar</legend><table>\n");
print("<tr class='clear'><td class='form'>Visa avatarer</td><td><input type='radio' name='show_avatars' value='yes'" . ($CURUSER["show_avatars"] == 'yes' ? " checked" : "") . " />Alla <input type='radio' name='show_avatars' value='notbad'" . ($CURUSER["show_avatars"] == 'notbad' ? " checked" : "") . " />Ej stötande <input type='radio' name='show_avatars' value='no'" . ($CURUSER["show_avatars"] == 'no' ? " checked" : "") . " />Inga</td></tr>\n");
print("<tr class='clear'><td class='form'>Visa smileys</td><td><input type='radio' name='show_smilies' value='yes'" . ($CURUSER["show_smilies"] != 'no' ? " checked" : "") . " />Ja <input type='radio' name='show_smilies' value='no'" . ($CURUSER["show_smilies"] == 'no' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form'>Visa signaturer</td><td><input type='radio' name='showforumsign' value='yes'" . ($CURUSER["showforumsign"] != 'no' ? " checked" : "") . " />Ja <input type='radio' name='showforumsign' value='no'" . ($CURUSER["showforumsign"] == 'no' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form'>Stötande avatar</td><td><input type='radio' name='bad_avatar' value='yes'" . ($CURUSER["bad_avatar"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='bad_avatar' value='no'" . ($CURUSER["bad_avatar"] != 'yes' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form'>Inlägg per sida</td><td><input type='text' name='postsperpage' size=10 value='$CURUSER[postsperpage]' /><br /><span class='small'>0 = standard</span></td></tr>\n");
print("<tr class='clear'><td class='form'>Trådar per sida</td><td><input type='text' name='topicsperpage' size=10 value='$CURUSER[topicsperpage]' /><br /><span class='small'>0 = standard</span></td></tr>\n");
print("<tr class='clear'><td class='form' style='vertical-align: top;'>Signatur</td><td><textarea name='forumsign' cols=50 rows=4 maxlength=255>" . htmlspecialchars($CURUSER["forumsign"]) . "</textarea><br /><span class='small'>Får innehålla BB-taggar och visas efter varje foruminlägg</span></td></tr>\n");
print("</table></fieldset><br />\n");

print("<fieldset><legend>Länkinställningar</legend><table>\n");
print("<tr class='clear'><td class='form'>Länkar per sida</td><td><input type='text' name='torrentsperpage' size=10 value='$CURUSER[torrentsperpage]' /><br /><span class='small'>0 = standard</span></td></tr>\n");
print("<tr class='clear'><td class='form'>Standardbläddra</td><td><input type='radio' name='browse' value='all'" . ($CURUSER["browse"] == 'all' ? " checked" : "") . " />Alla <input type='radio' name='browse' value='new'" . ($CURUSER["browse"] == 'new' ? " checked" : "") . " />Nytt <input type='radio' name='browse' value='old'" . ($CURUSER["browse"] == 'old' ? " checked" : "") . " />Gammalt</td></tr>\n");
print("<tr class='clear'><td class='form'>Visa covers på \"bläddra\"</td><td><input type='radio' name='show_covers' value='yes'" . ($CURUSER["show_covers"] == 'yes' ? " checked" : "") . " />Ja  <input type='radio' name='show_covers' value='no'" . ($CURUSER["show_covers"] != 'yes' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form' style='vertical-align: top;'>Standardkategorier</td><td>\n");

print("<table class='clear' style='width: 100%; text-align: center;'>\n<tr>");

$cats = genrelist();

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
			
			print("<br /><input name='categories[]' type='checkbox' value=$cat[id]" . (strpos($CURUSER["notifs"], "[cat{$cat["id"]}]") !== false ? " checked" : "") . " />" . htmlspecialchars($cat["name"]) . "\n");
			$i++;
		}
	}
	print("</td>");
}

print("</tr></table>\n");
print("</td></tr>\n");
print("</table></fieldset><br />\n");

print("<fieldset><legend>Meddelandeinställningar</legend><table>\n");
print("<tr class='clear'><td class='form'>Acceptera meddelanden från</td><td><input type='radio' name='acceptpms' value='all'" . ($CURUSER["acceptpms"] == 'all' ? " checked" : "") . " />Alla <input type='radio' name='acceptpms' value='friends'" . ($CURUSER["acceptpms"] == 'friends' ? " checked" : "") . " />Vänner och staff <input type='radio' name='acceptpms' value='staff'" . ($CURUSER["acceptpms"] == 'staff' ? " checked" : "") . " /> Staff</td></tr>\n");
print("<tr class='clear'><td class='form'>Radera besvarade meddelanden</td><td><input type='radio' name='delpms' value='yes'" . ($CURUSER["delpms"] != 'no' ? " checked" : "") . " />Ja <input type='radio' name='delpms' value='no'" . ($CURUSER["delpms"] == 'no' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form'>Spara meddelanden i Skickat</td><td><input type='radio' name='savepms' value='yes'" . ($CURUSER["savepms"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='savepms' value='no'" . ($CURUSER["savepms"] != 'yes' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form'>Meddelanden per sida</td><td><input type='text' name='pmperpage' size=10 value='$CURUSER[pmperpage]' /><br /><span class='small'>0 = standard</span></td></tr>\n");
print("<tr class='clear'><td class='form'>Notifo-användarnamn</td><td><input type='text' name='notifo' size=10 maxlength=32 value='$CURUSER[notifo]' /><br /><span class='small'><a target='_blank' href='http://notifo.com'>Notifo</a> är en gratistjänst för liveuppdateringar,<br />var det användarnamn du anger här <b>måste</b> vara registrerat.</span></td></tr>\n");
print("</table></fieldset><br />\n");

print("<fieldset><legend>Personliga inställningar</legend><table>\n");

print("<tr class='clear'><td class='form'>Parkerad</td><td><input type='radio' name='parked' value='yes'" . ($CURUSER["parked"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='parked' value='no'" . ($CURUSER["parked"] != 'yes' ? " checked" : "") . " />Nej<br /><span class='small'>Begränsar ditt konto och förhindrar det från att bli borttaget pga. inaktivitet</span></td></tr>\n");

if (get_user_class() >= UC_MARVELOUS_USER || $CURUSER["donor"] == 'yes')
	print("<tr class='clear'><td class='form'>Titel</td><td><input type='text' name='title' size=50 value='$CURUSER[title]' /></td></tr>\n");
	
print("<tr class='clear'><td class='form'>Avatar-URL</td><td><input type='text' name='avatar' size=50 value='$CURUSER[avatar]' /><br /><span class='small'>Bilden kommer automatiskt bli breddanpassad till 150px</span></td></tr>\n");
print("<tr class='clear'><td class='form'>Hemsida</td><td><input type='text' name='website' size=50 value='$CURUSER[website]' /></td></tr>\n");
print("<tr class='clear'><td class='form'>Kön</td><td><input type='radio' name='gender' value='female'" . ($CURUSER["gender"] == 'female' ? " checked" : "") . " />Kvinna <input type='radio' name='gender' value='male'" . ($CURUSER["gender"] == 'male' ? " checked" : "") . " />Man <input type='radio' name='gender' value='NA'" . ($CURUSER["gender"] == 'NA' ? " checked" : "") . " />N/A</td></tr>\n");
print("<tr class='clear'><td class='form'>Landskap</td><td>$clist</td></tr>\n");
print("<tr class='clear'><td class='form'>Ålder</td><td>$ylist $mlist $dlist<br /><span class='small'>Kan endast anges vid ett tillfälle</span></td></tr>\n");
print("<tr class='clear'><td class='form' style='vertical-align: top;'>Presentation</td><td><textarea name='pres' cols=50 rows=10>" . htmlspecialchars($CURUSER["pres"]) . "</textarea><br /><span class='small'>Får innehålla BB-taggar</span></td></tr>\n");
print("</table></fieldset><br />\n");

/*$land = array(2, 5, 8, 9, 10, 13, 15, 16, 17, 19, 20, 21, 22, 23, 24, 25);
$lands = array("Bohuslän", "Gästrikland", "Hälsingland", "Härjedalen", "Jämtland", "Närke", "Skåne", "Småland", "Södermanland", "Värmland", "Västerbotten", "Västergötland", "Västmanland", "Ångermanland", "Öland", "Östergötland");

foreach ($land AS $k => $l)
	mysql_query("UPDATE countries SET name = '$lands[$k]' WHERE id = $l") or sqlerr(__FILE__, __LINE__);
	
print(mysql_affected_rows());*/

print("<fieldset><legend>Säkerhetsinställningar</legend><table>\n");
print("<tr class='clear'><td class='form'>Anonym trafik</td><td><input type='radio' name='hidetraffic' value='yes'" . ($CURUSER["hidetraffic"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='hidetraffic' value='no'" . ($CURUSER["hidetraffic"] != 'yes' ? " checked" : "") . " />Nej</td></tr>\n");
print("<tr class='clear'><td class='form'>Anonyma länkar</td><td><input type='checkbox' name='anonlinks' value=1 /> Markera <b>alla hittills</b> uppladdade länkar som anonyma</td></tr>\n");
print("<tr class='clear'><td class='form'>Passkey</td><td><input type='checkbox' name='passkey' value=1 /> Återställ passkey</td></tr>\n");
print("<tr class='clear'><td class='form'>Mail</td><td><input type='text' name='mail' size=50 value='$CURUSER[mail]' /><br /><span class='small'>Vid byte av mail erhålls en länk till din gamla mailadress som måste följas för att fullfölja bytet</span></td></tr>\n");
print("<tr class='clear'><td class='form'>Nytt lösenord</td><td><input type='password' name='newpassword' size=50 /></td></tr>\n");
print("<tr class='clear'><td class='form'>Repetera lösenord</td><td><input type='password' name='conpassword' size=50 /></td></tr>\n");
print("<tr class='clear'><td class='form'>Nuvarande lösenord</td><td><input type='password' name='curpassword' size=50 /><br /><span class='small'>Krävs vid ändring av mail/lösenord</span></td></tr>\n");
print("</table></fieldset><br />\n");

print("<div style='text-align: center;'><span id='editres' style='display: none; margin-right: 10px;'></span><input type='submit' value='Ändra' id='editprof' /> <input type='reset' value='Återställ' /></div>\n");
print("</form></div></div>\n");

foot();

?>