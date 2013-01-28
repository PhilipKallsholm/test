<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	if ($_GET["add"])
	{
		$userid = 0 + $_POST["userid"];
		$block = $_POST["type"] == 'block';
		
		if ($userid == $CURUSER["id"])
			jErr("Du kan inte lägga till dig själv");
		
		$res = mysql_query("SELECT id FROM users WHERE id = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
		
		if (!mysql_num_rows($res))
			jErr("Användaren finns inte");
		
		if ($block)
			mysql_query("INSERT INTO blocks (userid, blockid) VALUES($CURUSER[id], " . sqlesc($userid) . ")");
		else
			mysql_query("INSERT INTO friends (userid, friendid) VALUES($CURUSER[id], " . sqlesc($userid) . ")");
		
		if (mysql_errno() == 1062)
			jErr("Användaren finns redan tillagd");
		
		if ($block)
			$return["result"] = "<a class='jlink' onClick=\"delFriend($userid, 'block')\">Ta bort blockering</a>";
		else
			$return["result"] = "<a class='jlink' onClick='delFriend($userid)'>Ta bort vän</a>";
		
		print(json_encode($return));
		die;
	}

	if ($_GET["del"])
	{
		$userid = 0 + $_POST["userid"];
		$block = $_POST["type"] == 'block';
		
		if ($block)
			mysql_query("DELETE FROM blocks WHERE userid = $CURUSER[id] AND blockid = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
		else
			mysql_query("DELETE FROM friends WHERE userid = $CURUSER[id] AND friendid = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
			
		print("<a class='jlink' onClick='addFriend($userid)'>Bli vän</a> / <a class='jlink' onClick=\"addFriend($userid, 'block')\">Blockera</a>");
		die;
	}
}

head("Vänlista");
print("<h2>Vänlista</h2>\n");

$res = mysql_query("SELECT friends.* FROM friends LEFT JOIN users ON friends.friendid = users.id WHERE userid = $CURUSER[id] ORDER BY users.username ASC") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<i>Du har inga vänner ännu</i>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$user = mysql_query("SELECT username, added, last_access, class, title, warned, warned_reason, donor, avatar, gender, country, time_online FROM users WHERE id = $arr[friendid]") or sqlerr(__FILE__, __LINE__);
	$user = mysql_fetch_assoc($user);
	
	$time_start = strtotime($user["added"]) > $time_online_start ? strtotime($user["added"]) : $time_online_start;

	$days = ceil((time() - $time_start) / (3600 * 24));
	$time_online = $user["time_online"] / $days;
	
	$country = mysql_query("SELECT * FROM countries WHERE id = $user[country]");

	if ($country = mysql_fetch_assoc($country))
		$country = "<img src='/pic/countries/$country[pic]' title='$country[name]' style='margin-left: 5px; float: right;' />";
	
	print("<div class='friend' id='f$arr[friendid]'><div class='friendavatar'><div><h2><a class='white' href='userdetails.php?id=$arr[friendid]'>$user[username]</a></h2></div><img src='" . ($user["avatar"] ? "$user[avatar]" : "/pic/default_avatar.jpg") . "' style='width: 100px;' /></div>\n");
	print("<table style='width: 390px;'>\n");
	print("<tr class='clear line'><td colspan=2><h3>" . get_user_class_name($user["class"]) .  ($user["title"] ? " | " . htmlspecialchars($user["title"]) : "") . " " . ($user["last_access"] > get_date_time(strtotime("-3 minutes")) ? "<img src='/pic/online.gif' title='Online' />" : "<img src='/pic/offline.gif' title='Offline' />") . ($user["warned"] == 'yes' ? "<img src='/pic/warnedsmall.gif' title='$user[warned_reason]' style='margin-left: 5px;' />" : "") . ($user["donor"] == 'yes' ? "<img src='/pic/starsmall.png' title='Donatör' style='margin-left: 5px;' />" : "") . "$country</h3></td></tr>\n");
	print("<tr class='clear line'><td>Senast aktiv för " . get_elapsed_time($user["last_access"]) . " sedan</td><td><a class='jlink' onClick='sendMess($arr[friendid])'>Skicka meddelande</a></td></tr>\n");
	print("<tr class='clear line'><td>Online " . get_time($time_online) . " om dagen i snitt</td><td><a class='jlink' onClick='delFriend($arr[friendid])'>Ta bort vän</a></td></tr></table>\n");
	print("<div style='clear: both;'></div></div>\n");
}

print("<h2 style='margin-top: 20px;'>Blockeringar</h2>\n");

$res = mysql_query("SELECT blocks.* FROM blocks LEFT JOIN users ON blocks.blockid = users.id WHERE userid = $CURUSER[id] ORDER BY users.username ASC") or sqlerr(__FILE__, __LINE__);

if (!mysql_num_rows($res))
	print("<i>Du har inte blockerat någon</i>\n");

while ($arr = mysql_fetch_assoc($res))
{
	$user = mysql_query("SELECT username, added, last_access, class, title, warned, warned_reason, donor, avatar, gender, country, time_online FROM users WHERE id = $arr[blockid]") or sqlerr(__FILE__, __LINE__);
	$user = mysql_fetch_assoc($user);
	
	if (strtotime($user["added"]) > $time_online_start)
		$time_online_start = strtotime($user["added"]);

	$days = ceil((time() - $time_online_start) / (3600 * 24));
	$time_online = $user["time_online"] / $days;
	
	$country = mysql_query("SELECT * FROM countries WHERE id = $user[country]");

	if ($country = mysql_fetch_assoc($country))
		$country = "<img src='/pic/countries/$country[pic]' title='$country[name]' style='margin-left: 5px; float: right;' />";
	
	print("<div class='friend' id='b$arr[blockid]' style='background-color: #fe7777;'><div class='friendavatar'><div><h2><a class='white' href='userdetails.php?id=$arr[blockid]'>$user[username]</a></h2></div><img src='" . ($user["avatar"] ? "$user[avatar]" : "/pic/default_avatar.jpg") . "' style='width: 100px;' /></div>\n");
	print("<table style='width: 390px;'>\n");
	print("<tr class='clear line'><td colspan=2><h3>" . get_user_class_name($user["class"]) .  ($user["title"] ? " | " . htmlspecialchars($user["title"]) : "") . "" . ($user["warned"] == 'yes' ? "<img src='/pic/warnedsmall.gif' title='$user[warned_reason]' style='margin-left: 5px;' />" : "") . ($user["donor"] == 'yes' ? "<img src='/pic/starsmall.png' title='Donatör' style='margin-left: 5px;' />" : "") . "$country</h3></td></tr>\n");
	print("<tr class='clear line'><td>Senast aktiv för " . get_elapsed_time($user["last_access"]) . " sedan</td><td><a class='jlink' onClick='sendMess($arr[blockid])'>Skicka meddelande</a></td></tr>\n");
	print("<tr class='clear line'><td>Online " . get_time($time_online) . " om dagen i snitt</td><td><a class='jlink' onClick=\"delFriend($arr[blockid], 'block')\">Ta bort blockering</a></td></tr></table>\n");
	print("<div style='clear: both;'></div></div>\n");
}

foot();
?>