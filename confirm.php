<?php

require_once("globals.php");

dbconn();

$a = explode("/", $_SERVER["PATH_INFO"]);

$id = 0 + $a[1];
$secret = $a[2];

if (!$id)
	die;
	
$res = mysql_query("SELECT passhash, secret, confirmed, editsecret FROM users WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr)
	die;
	
if ($arr["confirmed"] == 'yes')
	stderr("Activated", "The account is already active - you may <a href='/login.php'>login</a>.");
	
if ($secret !== md5($arr["editsecret"]))
	die;
	
$freeleech = get_date_time(strtotime("+12 hours"));
	
mysql_query("UPDATE users SET confirmed = 'yes', editsecret = '', freeleech = '$freeleech' WHERE id = $id") or sqlerr(__FILE__, __LINE__);

$exp = strtotime("+1 day");
$ip = getip();

setcookie("id", "$id", $exp, "/");
setcookie("pass", "" . md5($arr["passhash"] . $ip . $arr["secret"]) . "", $exp, "/");

header("Location: /index.php");

die;
?>