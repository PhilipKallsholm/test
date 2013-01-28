<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("StakeBet");

print("<img src='/pic/stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px; color: gray;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px;'>Lägg till spel</a>" : "") . "</h3>\n");

print("<div class='frame' style='width: 500px;'>\n");
print("<h3>Anvisningar till bettingsystemet</h3>

Swepiracys bettingsystem baserat på odds fungerar på liknande vis som
andra kända bettingsidor på nätet; det vill säga att utbetalning
sker beroende på oddsen (notera att du <u>enbart</u> satsar med dina bonuspoäng).
<br /><br />
Oddsen är rörliga, det vill säga att de varierar i takt med hur många
som satsar på ett visst resultat. Därför kan det vara en fördel
att lägga ditt bet när flera redan har satsat och oddsen har stabiliserats.
<br /><br />
Alla bets är <b>bindade</b> och kan inte återkrävas.
<br /><br />
Resultatet efter full tid räknas, en förlängning i matchen skulle innebära ett kryss (X) oavsett vilket lag som vinner. Vid eventuell vinst får du de poäng du satsade multiplicerat med resultatets odds.\n");
print("</div>\n");

foot();

?>