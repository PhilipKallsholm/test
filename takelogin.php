<?php

require_once("globals.php");

dbconn();

$username = $_POST["username"];
$password = $_POST["password"];
$ip = getip();
$host = gethostbyaddr($ip);
$dt = get_date_time();

if ($username == '%USER%' || $password == '%PASS%')
	die;

$res = mysql_query("SELECT * FROM users WHERE username = " . sqlesc($username) . " AND confirmed = 'yes'") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (md5($arr["secret"] . $password . $arr["secret"]) !== $arr["passhash"])
{
	if ($arr["class"] >= UC_MODERATOR)
		stafflog("$ip försökte komma åt $arr[username] men uppgav fel lösenord");
	
	if ($password)
	{
		$userid = $arr["id"];
		
		$att = mysql_query("SELECT SUM(attempts) FROM failedlogins WHERE ip = " . sqlesc($ip)) or sqlerr(__FILE__, __LINE__);
		$att = mysql_fetch_row($att);
		
		$left = 4 - $att[0];
		
		$return["left"] = $left < 2 ? "<span style='color: red;'>$left tries left</span>" : "$left tries left";
		
		if ($left < 1)
		{
			mysql_query("INSERT INTO bans (ip, permban, reason, added) VALUES(" . sqlesc($ip) . ", 'no', 'Misslyckade inloggningsförsök', '$dt')");
			
			stafflog("$ip bannades på grund av misslyckade inloggningsförsök");
			$return["refresh"] = 1;
		}
	
		$logs = mysql_query("SELECT id FROM failedlogins WHERE ip = " . sqlesc($ip) . " AND username = " . sqlesc($username) . " AND password = " . sqlesc($password)) or sqlerr(__FILE__, __LINE__);
	
		if ($logs = mysql_fetch_assoc($logs))
			mysql_query("UPDATE failedlogins SET added = '$dt', attempts = attempts + 1 WHERE id = $logs[id]") or sqlerr(__FILE__, __LINE__);
		else
			mysql_query("INSERT INTO failedlogins (ip, host, added, username, userid, password, attempts) VALUES(" . implode(", ", array_map("sqlesc", array($ip, $host, $dt, $username, $userid, $password, 1))) . ")") or sqlerr(__FILE__, __LINE__);
	}
	
	jErr("Invalid username or password");
}

if ($arr["enabled"] == 'no')
{
	mysql_query("INSERT INTO bans (ip, reason, added) VALUES(" . sqlesc($ip) . ", 'Inloggning på inaktiverat konto', '$dt')");
	
	stafflog("$ip försökte logga in på ett inaktiverat konto och blev därför ipbannad ($username - $ip)");
	
	jErr("This account has been disabled");
}

mysql_query("UPDATE failedlogins SET attempts = 0 WHERE ip = " . sqlesc($ip)) or sqlerr(__FILE__, __LINE__);

if ($arr["ip"] != $ip)
{
	$logg = mysql_query("SELECT * FROM iplogg WHERE userid = $arr[id] AND ip = " . sqlesc($ip)) or sqlerr(__FILE__, __LINE__);
	
	if ($logg = mysql_fetch_assoc($logg))
		mysql_query("UPDATE iplogg SET lastseen = '$dt', timesseen = timesseen + 1 WHERE id = $logg[id]") or sqlerr(__FILE__, __LINE__);
	else
		mysql_query("INSERT INTO iplogg (userid, ip, host, firstseen, lastseen) VALUES($arr[id], " . sqlesc($ip) . ", " . sqlesc($host) . ", '$dt', '$dt')") or sqlerr(__FILE__, __LINE__);
}

$exp = strtotime("+1 day");

setcookie("id", "$arr[id]", $exp, "/");
setcookie("pass", "" . md5($arr["passhash"] . $ip . $arr["secret"]) . "", $exp, "/");

/*$_SESSION["id"] = "$arr[id]";
$_SESSION["pass"] = "" . md5($arr["passhash"] . $_SERVER["REMOTE_ADDR"]) . "";*/

$return["returnto"] = $_GET["returnto"] ? $_GET["returnto"] : "index.php";

print(json_encode($return));

?>