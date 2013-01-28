<?php

require_once("globals.php");

dbconn();

$path = explode("/", $_SERVER["PATH_INFO"]);

$userid = 0 + $path[1];
$hash = trim($path[2]);
$mail = trim(urldecode($path[3]));

$res = mysql_query("SELECT id, username, mail, editsecret FROM users WHERE id = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr["editsecret"])
	die;
	
if (md5($arr["editsecret"] . $mail . $arr["editsecret"]) !== $hash)
	die;
	
mysql_query("UPDATE users SET editsecret = '', mail = " . sqlesc($mail) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);

if (!mysql_affected_rows())
	die;
	
stafflog("$arr[username] ändrade sin mail från $arr[mail] till $mail");
	
header("Location: /my.php?mail=1");

?>