<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$id = explode("t", $_POST["id"]);
$rating = 0 + substr($id[0], 1);
$torrentid = 0 + $id[1];

$res = mysql_query("SELECT id FROM torrentvotes WHERE torrentid = $torrentid AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);

if (mysql_num_rows($res))
	die;
	
mysql_query("INSERT INTO torrentvotes (torrentid, userid, rating) VALUES($torrentid, $CURUSER[id], $rating)") or sqlerr(__FILE__, __LINE__);
mysql_query("UPDATE torrents SET numratings = numratings + 1, ratingsum = ratingsum + $rating WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);

$res = mysql_query("SELECT numratings, ratingsum FROM torrents WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

$return["votes"] = $arr["numratings"];
$return["rating"] = round($arr["ratingsum"] / $arr["numratings"], 1);
$return["ind"] = round($arr["ratingsum"] / $arr["numratings"]);

print(json_encode($return));
die;

?>