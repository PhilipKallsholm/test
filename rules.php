<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Regler");

print("<h1>Regelverk</h1>\n");

print("<div class='frame' style='width: 600px; padding-top: 0px;'>\n");

print("<h2 id='allmanna' style='margin: 10px 0px;'>Allmänna regler</h2>\n");
print("<div class='frame' style='background-color: white;'>\n");
print("<ul>\n");
print("<li><b>Det är strängt förbjudet att sprida vidare magnetlänkar från Swepiracy. Upptäcks detta kommer berörd användare omedelbart bli borttagen och permanent bannad.</b></li>\n");
print("<li><b>Det är strängt förbjudet att redigera/ta bort trackers i torrentklienten. Upptäcks detta kommer berörd användare omedelbart bli borttagen och permanent bannad.</b></li>\n");
print("<li><b>Script som per automatik uppdaterar webbläsaren är strikt förbjudet. Detta kommer tolkas som försök till fusk/manipulation av våra system och behandlas därefter.</b></li>\n");
print("<li><b>Sunt förnuft</b></li>\n");
print("<li><b>Endast ett konto per person</b></li>\n");
print("</ul>\n");
print("</div>\n");

print("<h2 id='forum' style='margin: 10px 0px;'>Forum- och kommentarregler</h2>\n");
print("<div class='frame' style='background-color: white;'>\n");
print("<ul>\n");
print("<li>Endast staff modererar, med andra ord; du är ingen moderator</li>\n");
print("<li>Se till att ditt inlägg/din kommentar har relevans och tillhör rätt avdelning</li>\n");
print("<li>Inget aggressivt beteende eller störande uppträdande i forumet</li>\n");
print("<li>Inga länkar till warez eller cracksidor</li>\n");
print("<li>Inget språkande om serienummer, CD-nycklar, lösenord eller cracks</li>\n");
print("<li>Inga requests</li>\n");
print("<li>Ingen BUMP/SPAM</li>\n");
print("<li>Inga bilder bredare än 480px utan [imgw]-tag</li>\n");
print("<li>Ingen annonsering (säljes/köpes/bytes)</li>\n");
print("<li>Inga vinstdrivande länkar</li>\n");
print("<li>Inga &quot;Seeda!&quot;-kommentarer</li>\n");
print("<li>Inga spoilers - såvida det inte tydligt framgår i förtid</li>\n");
print("</ul>\n");
print("</div>\n");

print("<h2 id='avatar' style='margin: 10px 0px;'>Avatarregler</h2>\n");
print("<div class='frame' style='background-color: white;'>\n");
print("<ul>\n");
print("<li>Tillåtna bildformat är: .gif, .jpg och .png</li>\n");
print("<li>Omvandla helst din avatar till en bredd av 150px</li>\n");
print("<li>Använd inga stötande bilder; framför allt inte: porr, religiöst material, djur-/människogrymhet eller ideologiskt laddade bilder. Moderatorerna har beslutsrätt på vad som är förbjudet. Om det är något tvivel, rapportera användaren.</li>\n");
print("</ul>\n");
print("</div>\n");

print("<h2 id='uppladdning' style='margin: 10px 0px;'>Uppladdningsregler</h2>\n");
print("<div class='frame' style='background-color: white;'>\n");
print("<ul>\n");
print("<li><b>Scenreleaser</b> - undantag i <a href='faq.php#bluray'>Bluray</a> och som följer:\n");
print("<ul style='display: block; margin-left: 10px;'>\n");

$cattypes = mysql_query("SELECT cattype.* FROM p2p INNER JOIN cattype ON p2p.cattype = cattype.id GROUP BY p2p.cattype ORDER BY cattype.name ASC") or sqlerr(__FILE__, __LINE__);

while ($cattype = mysql_fetch_assoc($cattypes))
{
	$res = mysql_query("SELECT `group` FROM p2p WHERE cattype = $cattype[id] ORDER BY `group` ASC") or sqlerr(__FILE__, __LINE__);
	
	$groups = array();
	while ($arr = mysql_fetch_assoc($res))
		$groups[] = $arr["group"];
		
	print("<li><b>$cattype[name]</b>: " . implode(", ", $groups) . "</li>\n");
}

print("</ul>\n</li>\n");
print("<li>Alla länkar som läggs upp i följande kategorier <b>måste</b> vara svenskrelaterade:\n");
print("<ul style='display: block; margin-left: 10px;'>\n");
print("<li><b>DVDr</b>: CUSTOM, PAL, TV</li>\n");
print("</ul>\n</li>\n");
print("<li>Alla länkar <b>måste</b> inkludera en giltig NFO</li>\n");
print("<li>Alla länkar under nytt <b>får inte</b> vara mer än <b>5 dagar</b> gamla</li>\n");
print("<li>Alla filer <b>måste</b> vara i orginalstorlek (vanligtvis 14,3MB / 50MB för RAR-filer)</li>\n");
print("<li>Alla länkar <b>måste</b> seedas i god hastighet i minst 24 timmar</li>\n");
print("</ul>\n");
print("</div>\n");

print("<h2 id='request' style='margin: 10px 0px;'>Requestregler</h2>\n");
print("<div class='frame' style='background-color: white;'>\n");
print("<ul>\n");
print("<li>Alla requests <b>måste</b> vara mer än <b>5 dagar</b> gamla</li>\n");
print("<li>Alla requests <b>måste</b> följa de scen- och språkkrav som finns publicerade under <b>uppladdningsregler</b></li>\n");
print("<li>En request <b>får</b> laddas upp i annat format än det efterfrågade, under förutsättning att det nya formatet är av bättre kvalitét än det efterfrågade (ex. PAL istället för CUSTOM)</li>\n");
print("</ul>\n");
print("</div>\n");

print("</div>\n");

foot();
?>