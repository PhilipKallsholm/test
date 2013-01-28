<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	$sum = 0 + $_POST["donated"];
	$alt = $_POST["alt"];
	$code = trim($_POST["code"]);
	$comm = trim($_POST["comm"]);
	
	if (!$sum)
		jErr("Vänligen ange en summa");
		
	if (!$alt)
		jErr("Vänligen ange en tjänst");
		
	if (!$code)
		jErr("Vänligen ange din paysafecard-/Ukash-kod");
		
	mysql_query("INSERT INTO donations (userid, donated, added, method, code, comment) VALUES(" . implode(", ", array_map("sqlesc", array($CURUSER["id"], $sum, get_date_time(), $alt, $code, $comm))) . ")") or sqlerr(__FILE__, __LINE__);
	
	$sub = "Donation via $alt";
	$msg = "[b]Användare:[/b] $CURUSER[username] \n[b]Donerat:[/b] $sum SEK \n[b]Tjänst:[/b] $alt \n[b]Kod:[/b] $code \n[b]Övrigt:[/b] $comm";
	
	mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array(1, get_date_time(), $sub, $msg))) . ")") or sqlerr(__FILE__, __LINE__);
	
	$return["res"] = "Tack för din donation! Din belöning anländer så snart pengarna är bekräftade.";
	
	print(json_encode($return));
	die;
}

head("Donera");

begin_frame("Donera", 600);

print("<h3>Swepiracy behöver Din donation för att överleva</h3>\n");

print("<div style='margin-top: 10px; padding: 10px; border-radius: 5px 5px 0px 0px; background-color: #ededed'>\n");
print("<span style='display: block; padding-bottom: 10px;'>Om du donerar <b>minst</b> 100 SEK, får du en <b>donationsstjärna</b>, en <b>titel</b>, 24h <b>fri leech</b>, halva summan i <b>GB uppladdad trafik</b>, samt <b>donerad summa i bonuspoäng</b>.</span>\n");
print("<div style='padding: 10px; background-color: #ccffcc; border: 1px solid rgba(0, 0, 0, 0.2); outline: rgba(0, 0, 0, 0.1) solid 0px;'><ul style='margin: 0px 0px 10px 20px;'>\n");
print("<li><b>Ex.</b> Om du donerar 100 SEK, får du en <img src='/pic/starsmall.png' />, en titel, ett dygn fri leech, 50 GB och 100 bonuspoäng.</li>\n");
print("<li><b>Ex.</b> Om du donerar 500 SEK, får du en <img src='/pic/starsmallred.png' />, en titel, fem dygn fri leech, 250 GB och 500 bonuspoäng.</li>\n");
print("<li><b>Ex.</b> Om du donerar 1000 SEK, får du en <img src='/pic/starsmallblue.png' />, en titel, tio dygn fri leech, 500 GB och 1000 bonuspoäng.</li>\n");
print("</ul><br />\n");
print("<b>Donationer om 1000 SEK och däröver kommer dessutom bli belönade med permanent <a href='faq.php#anvandarklasser'><u>VIP</u></a>.</b></div>\n");
print("<div style='margin-top: 10px; text-align: center; color: red; font-size: 10pt;'>När du har donerat - fyll i <a class='jlink' id='donated'>detta formulär</a>, så får du din belöning när pengarna är bekräftade.</div></div>\n");

print("<div style='display: none;' id='donform'>\n");
$dalts = array("Bank", "paysafecard", "Ukash");

$alts = "<option value=0>- Välj tjänst -</option>";
foreach ($dalts AS $dalt)
	$alts .= "<option value='$dalt'>$dalt</option>";
	
print("<h3 style='margin-top: 10px; text-align: center;' id='res'></h3>\n");

print("<form method='post' action='donate2.php' id='donorform'><table>\n");
print("<tr class='main'><td class='form'>Användarnamn</td><td><input type='text' name='username' size=15 value='$CURUSER[username]' disabled /></td></tr>\n");
print("<tr class='main'><td class='form'>Summa</td><td><input type='text' name='donated' size=15 /></td></tr>\n");
print("<tr class='main'><td class='form'>Tjänst</td><td><select name='alt'>$alts</select></td></tr>\n");
print("<tr class='main'><td class='form'>paysafecard-/Ukash-kod</td><td><input type='text' name='code' size=30 /></td></tr>\n");
print("<tr class='main'><td class='form' style='vertical-align: top;'>Övrigt</td><td><textarea name='comm' cols=50 rows=3></textarea></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><div class='errormess'></div><input type='submit' value='Skicka' /></td></tr>\n");
print("</table></form>\n");
print("</div>\n");

/*print("<table style='margin-top: 10px;'>\n");
print("<tr class='clear'><td><h3>Donera via bank</h3></td><td><h3>Donera via PayPal</h3></td></tr>\n");
print("<tr class='clear'><td>\n");
print("<table class='graymark'>\n");
print("<tr class='clear'><td class='form'>Bank</td><td>Swedbank</td></tr>\n");
print("<tr class='clear'><td class='form'>Konto</td><td>XXX XXX XXX-X</td></tr>\n");
print("<tr class='clear'><td class='form'>Clearing</td><td>XXXX-X</td></tr>\n");
print("</table>\n");
print("</td>\n");
print("<td>");
print("<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>");
print("<input type='hidden' name='cmd' value='_s-xclick' /><input type='hidden' name='hosted_button_id' value='4QBDZHJXYGRZE' /><input type='image' src='https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif' name='submit' alt='PayPal - The safer, easier way to pay online!' />");
print("<img alt='' border=0 src='https://www.paypalobjects.com/en_US/i/scr/pixel.gif' width=1 height=1 />");
print("</form>");
print("</td></tr>\n");
print("</table>\n");*/

print("<h3 style='margin-top: 10px;'>Donera via bank (<span style='color: red;'>minst 500 SEK</span>)</h3>\n");
print("<div style='background-color: white; border-width: 0px 5px; border-style: solid; border-color: #ededed; text-align: center;'>\n");
print("<ol style='font-size: 9pt;'><li>Kontakta <a class='jlink' onClick='sendMess(1)'>Bossman</a> för kontodetaljer</li><li>Överför valfritt belopp om lägst <b>500 SEK</b></li></ol>\n");
print("</div>\n");

print("<h3>Donera via paysafecard/Ukash</h3>\n");
print("<div style='background-color: white; border-width: 0px 5px 5px 5px; border-style: solid; border-color: #ededed; border-radius: 0px 0px 5px 5px; text-align: center;'><a href='http://www.paysafecard.com/se/koep/foersaeljningsstaellen/' target='_blank'><img src='/pic/paysafecard.png' title='Hitta närmaste paysafecard-återförsäljare' /></a><a href='http://www.ukash.com/se/en/where-to-get.aspx' target='_blank'><img src='/pic/ukash.png' title='Hitta närmaste Ukash-återförsäljare' /></a>\n");
print("<ol style='font-size: 9pt;'><li>Besök en <b>återförsäljare</b> för paysafecard/Ukash</li><li>Fråga efter <b>paysafecard/Ukash</b> och välj valfritt belopp (100, 250, 500, 1000, 1500)</li><li>Betala med <b>kontanter eller kort</b> och spara kvittot</li><li>Skicka <b>koden</b> på ditt kvitto till oss</li></ol>\n");
print("<span style='display: block; padding: 10px; border: 2px dashed gray; border-radius: 0px 0px 5px 5px; font-size: 10pt; line-height: 1.5;'>Donationer genom paysafecard- och Ukash-koder sker <b>100% anonymt</b> och finns att köpa på bland annat <b>Pressbyrån</b>, <b>7-Eleven</b>, <b>Direkten</b>, <b>Timebutiker</b> samt <b>bensinstationer/tobaksaffärer</b>.</span></div>\n");

//print("<div style='background-color: rgba(255, 0, 0, 0.3); border-width: 0px 0px; border-style: dotted; border-color: red; margin-top: 10px; padding: 5px; text-align: center; font-weight: bold; font-style: italic;'>Glöm inte att ange '$CURUSER[username]' i meddelande till mottagare!</div>\n");

print("</div><div class='small' style='margin-top: 5px; text-align: center;'>Observera att Swepiracy är en gratistjänst som fungerar på precis samma sätt för donatorer som för icke-donatorer. Du \"betalar\" alltså inte för en tjänst genom att donera.</div>\n");

print("</div>\n");

foot();

?>