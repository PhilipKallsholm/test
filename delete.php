<?php

require_once("globals.php");

dbconn();
loggedinorreturn();


$id = 0 + $_POST["id"];

if (!$id)
	stderr("Fel", "Ogiltigt ID");

$res = mysql_query("SELECT id, name, owner, anonymous, category FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_array($res);

if (!$row)
	stderr("Fel", "Länken finns inte");

if ($CURUSER["id"] != $row["owner"] && get_user_class() < UC_MODERATOR)
	stderr("Fel", "Länken tillhör inte dig");

$rt = 0 + $_POST["reasontype"];

if (!$rt || $rt < 1 || $rt > 5)
	stderr("Fel", "Ogiltig anledning");

$reason = $_POST["reason"];

if ($rt == 1)
	$reasonstr = "Död: 0 seedare, 0 leechare = 0 peers totalt";
elseif ($rt == 2)
	$reasonstr = "Dupe" . ($reason[0] ? (": " . trim($reason[0])) : "!");
elseif ($rt == 3)
	$reasonstr = "Nukad" . ($reason[1] ? (": " . trim($reason[1])) : "!");
elseif ($rt == 4)
{
	if (!$reason[2])
		stderr("Fel", "Du måste ange en anledning");
		
	$reasonstr = "Regler brutna: " . trim($reason[2]);
}
else
{
	if (!$reason[3])
		stderr("Fel", "Du måste ange en anledning");
		
	$reasonstr = trim($reason[3]);
}

deletetorrent($row["id"]);

if ($row["anonymous"] == "yes" && $CURUSER["id"] == $row["owner"])
	write_log("<b>" . $row[name] . "</b> togs bort av NAMNET ($reasonstr)", $CURUSER[username], "yes");
else
	write_log("<b>" . $row[name] . "</b> togs bort av NAMNET ($reasonstr)", $CURUSER[username], "no");

mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES ($row[owner], '" . get_date_time() . "', 'Din torrent har blivit borttagen', '{$CURUSER["username"]} tog bort [b]{$row["name"]}[/b] ({$reasonstr}).')") or sqlerr(__FILE__, __LINE__);

head("Torrent raderad!");

print("<h2>Torrent raderad!</h2>\n");
print("<p><a href='browse.php'>Tillbaka till länkar</a></p>\n");

foot();

?>