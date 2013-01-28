<?php

require_once('include/bittorrent.php');

session_start();

ob_start();

dbconn(false);

loggedinorreturn();

stdhead();


print("<table width=\"400\" cellpadding=\"5\">");

print("<tr><td class=\"bet\" bgcolor='#000000' colspan='3'>Aktuella Bets</td></tr>");

//print("<tr><td>");

$res = mysql_query("SELECT id, namn, beskrivning, avgjort, tillagd, slut FROM betting WHERE UNIX_TIMESTAMP(slut) > UNIX_TIMESTAMP(NOW()) ORDER BY slut ASC LIMIT 10");

while ($arr = mysql_fetch_assoc($res))
{
  //print("<tr><td>" . mkprettytime(sql_timestamp_to_unix_timestamp($arr["slut"]) - $now) . " kvar</td><td><a href=bet.php#" . $arr[id] . "><b>$arr[namn]</b></a></td><td><i>" . $arr[beskrivning] . "</i></td><br/></td></tr>");
  print("<tr><td>" . mkprettytime(sql_timestamp_to_unix_timestamp($arr["slut"]) - $now) . " kvar</td><td><a href=bet.php#" . $arr[id] . "><b>$arr[namn]</b></a></td><td><i>" . $arr[beskrivning] . "</i></td><br/></td></tr>");
}

print("</td></tr></table>");