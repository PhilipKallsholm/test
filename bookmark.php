<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$id = 0 + $_POST["id"];

$res = mysql_query("SELECT id FROM bookmarks WHERE torrentid = $id AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if ($arr)
{
	mysql_query("DELETE FROM bookmarks WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
	print("<a class='jlink' onclick='bookmark($id)'><img src='/pic/bok.gif'></a>");
}
else
{
	mysql_query("INSERT INTO bookmarks (torrentid, userid) VALUES($id, $CURUSER[id])") or sqlerr(__FILE__, __LINE__);
	print("<a class='jlink' onclick='bookmark($id)'><img src='/pic/bok2.gif'></a>");
}

?>