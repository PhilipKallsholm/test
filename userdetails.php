<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$userid = 0 + $_GET["id"];

$user = mysql_query("SELECT * FROM users WHERE id = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
$user = mysql_fetch_assoc($user);

if (!$user)
	stderr("Fel", "Användaren finns inte");
	
if ($user["hidetraffic"] == 'no' || $CURUSER["id"] == $user["id"] || get_user_class() >= UC_MODERATOR)
	$viewtraffic = true;
	
if ($_GET["iplogg"])
{
	if ($CURUSER["id"] != $user["id"] && !staff() || get_user_class() < $user["class"])
	{
		stafflog("$CURUSER[username] försökte komma åt IP-loggen för $user[username]");
		die;
	}

	$res = mysql_query("SELECT * FROM iplogg WHERE userid = " . sqlesc($userid) . " ORDER BY lastseen DESC") or sqlerr(__FILE__, __LINE__);
	
	print("<table style='margin: 0px;'>\n");
	
	while ($arr = mysql_fetch_assoc($res))
		print("<tr class='main nowrap'><td>$arr[lastseen] (" . get_elapsed_time($arr["lastseen"]) . " sedan)</td><td>$arr[ip]" . ($arr["host"] && $arr["host"] != $arr["ip"] ? " ($arr[host])" : "") . "</td></tr>\n");
		
	print("</table>\n");
	
	die;
}
	
head($user["username"]);

print("<div style='display: inline-block;'>\n");

$country = mysql_query("SELECT * FROM countries WHERE id = $user[country]");

if ($country = mysql_fetch_assoc($country))
	$country = "<img src='/pic/countries/$country[pic]' title='$country[name]' style='margin-left: 5px;' />";
	
if ($user["age"] != '0000-00-00')
{
	$now = date_create(get_date_time());
	$born = date_create($user["age"]);
	
	$age = date_diff($now, $born);
	$age = ", " . $age->format("%y");
}

if ($user["enabled"] == 'no')
	$disabled = "<img src='/pic/disabledbig.gif' title='Inaktiverad' />";

if (time() - strtotime($user["last_access"]) > 180)
	$status = "<img src='/pic/offline.gif' title='Offline' />";
else
	$status = "<img src='/pic/online.gif' title='Online' />";

if ($user["warned"] == 'yes')
	$warned = "<img src='/pic/warnedbig.gif' title='$user[warned_reason]' />";
	
if ($user["crown"] == 'yes')
	$crown = "<img src='/pic/crownbig.png' title='Krona' />";
	
if ($user["donor"] == 'yes')
{
		if ($user["donated"] >= 1000)
			$color = "blue";
		elseif ($user["donated"] >= 500)
			$color = "red";
		else
			$color = "";
			
	$donor = "<img src='/pic/starbig{$color}.png' title='Donatör' />";
}

print("<div id='userhead'><h2>$user[username]$age</h2>$disabled$status$warned<span>$country$crown$donor</span></div>\n");

print("<div id='useravatar'>\n");
print("<img src='" . ($user["avatar"] ? "$user[avatar]" : "/pic/default_avatar.jpg") . "' class='transbor' style='width: 150px;' />\n");

if ($user["id"] != $CURUSER["id"])
{
	print("<div id='friendrep'>\n");
	print("<span id='friendactions'>");

	$friend = mysql_query("SELECT id FROM friends WHERE userid = $CURUSER[id] AND friendid = $user[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$blocked = mysql_query("SELECT id FROM blocks WHERE userid = $CURUSER[id] AND blockid = $user[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($friend))
		print("<a class='jlink' onClick='delFriend($userid)'>Ta bort vän</a>");
	elseif (mysql_num_rows($blocked))
		print("<a class='jlink' onClick=\"delFriend($userid, 'block')\">Ta bort blockering</a>");
	else
		print("<a class='jlink' onClick='addFriend($user[id])'>Bli vän</a> / <a class='jlink' onClick=\"addFriend($user[id], 'block')\">Blockera</a>");

	print("</span><br /><a class='jlink' onClick=\"report($user[id], 'user')\">Rapportera</a>");
	print("</div>\n");
}

print("</div>\n");

print("<div id='user'>\n");
print("<table style='width: inherit; table-layout: fixed;'><col style='width: 150px;' />\n");

if ($user["warned"] == 'yes')
	print("<tr class='clear line'><td class='form' style='color: red;'>Varnad</td><td>På grund av $user[warned_reason] fram tills $user[warned_until] (" . mkprettytime(strtotime($user["warned_until"]) - time()) . " kvar)</td></tr>\n");

print("<tr class='clear line'><td class='form'>Blev medlem</td><td>$user[added] (" . get_elapsed_time($user["added"]) . " sedan)</td></tr>\n");
print("<tr class='clear line'><td class='form'>Senast aktiv</td><td>$user[last_access] (" . get_elapsed_time($user["last_access"]) . " sedan)</td></tr>\n");

if ($user["class"] >= UC_POWER_USER && (get_user_class() >= UC_MODERATOR || $user["id"] == $CURUSER["id"]))
{
	$bdate = date("Y-m-d H:i:s", strtotime($user["seedbonus_update"] . " + 7 days"));
	$bleft = get_elapsed_time_all(strtotime($bdate), time());

	print("<tr class='clear line'><td class='form'>Bonuscheck</td><td>$bdate ($bleft)</td></tr>\n");
}

if (strtotime($user["added"]) > $time_online_start)
	$time_online_start = strtotime($user["added"]);

$days = ceil((time() - $time_online_start) / (3600 * 24));
$time_online = $user["time_online"] / $days;

print("<tr class='clear line'><td class='form'>Aktivitet</td><td>Online " . get_time($time_online) . " om dagen i snitt</td></tr>\n");

if (staff() && get_user_class() >= $user["class"] || $CURUSER["id"] == $user["id"])
{
	print("<tr class='clear line'><td class='form'>Mail</td><td>$user[mail]</td></tr>\n");
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'>IP-logg</td><td><div id='iplogg' style='display: none; margin-bottom: 5px;'></div><a class='jlink' onClick='ipLogg($user[id])'>Visa / dölj</a></td></tr>\n");
}

if ($viewtraffic)
{
	//print("<tr class='clear line'><td class='form'>Seedtid</td><td>" . mkprettytime($user["seedtime"]) . $seedtid . "</td></tr>\n");
	print("<tr class='clear line'><td class='form'>Uppladdat</td><td>" . mksize($user["uploaded"]) . " (" . mksize($user["uploaded"] / $days) . "/dag)</td></tr>\n");
	print("<tr class='clear line'><td class='form'>Nedladdat</td><td>" . mksize($user["downloaded"]) . " (" . mksize($user["downloaded"] / $days) . "/dag)</td></tr>\n");

	if ($user["downloaded"])
	{
		$sr = number_format($user["uploaded"] / $user["downloaded"], 3);

		if ($sr >= 4)
			$s = "w00t";
		elseif ($sr >= 2)
			$s = "grin";
		elseif ($sr >= 1)
			$s = "smile1";
		elseif ($sr >= 0.5)
			$s = "noexpression";
		elseif ($sr >= 0.25)
			$s = "sad";
		else
			$s = "cry";

		$sr = "<span style='color: " . get_ratio_color($sr) . ";'>$sr</span> <img src='/pic/smilies/$s.gif' alt='Ratio' style='vertical-align: middle;' />";

		print("<tr class='clear line'><td class='form'>Ratio</td><td style='padding: 0px 5px;'>$sr</td></tr>\n");
	}

	if ($user["class"] >= UC_POWER_USER)
	{		
		$uploaded = $user["uploaded"] - $user["seedbonus_uploaded"];
		$bonuspoints = 0;
		$seedbonus = $uploaded / 1073741824;

		if ($seedbonus <= 10)
			$seedbonus *= 2;
		elseif($seedbonus > 10 && $seedbonus <= 20)
			$seedbonus = 20 + (($seedbonus - 10) * 1);
		elseif($seedbonus > 20 && $seedbonus <= 40)
			$seedbonus = 30 + (($seedbonus - 20) * 0.5);
		elseif($seedbonus > 40)
			$seedbonus = 40;
	
		$bonuspoints += round($seedbonus);

		$trails = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM traileradds WHERE userid = $user[id]"));
		$trailerbonus = $trails[0] * 2;
			
		$bonuspoints += $trailerbonus;

		$uploadsplus = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS uploads, SUM(points) AS points FROM uploads WHERE userid = $user[id] AND points > 0"));
		$uploadsminus = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS uploads, SUM(points) AS points FROM uploads WHERE userid = $user[id] AND points < 0"));
		$torrentbonus = $uploadsplus["points"] + $uploadsminus["points"];
		$torrents = $uploadsplus["uploads"];
			
		$bonuspoints += $torrentbonus;

		$seedtime = $user["seedtime"] - $user["seedbonus_seedtime"];
		$timebonus = round(($seedtime / 3600) * 0.01);
			
		$bonuspoints += $timebonus;
		
		$onlinetime = $user["time_online"] - $user["time_online_week"];
		$onlinehours = round($onlinetime / 3600, 1);
		$onlinebonus = $onlinehours * 10;
			
		$bonuspoints += $onlinebonus;
		
		$bonuspoints += $user["posts_week"];

		$bonus = number_format($user["seedbonus"]);
		$lastuped = mksize($user["bonus_last"] / 7 / 24 / 60 / 60);
		
		print("<tr class='clear line'><td class='form'>Bonuspoäng</td><td>$bonus p</td></tr>\n");
		
		if ($CURUSER["class"] >= UC_MODERATOR || $user['id'] == $CURUSER['id'])
			print("<tr class='clear line'><td class='small' colspan=2>(" . mksize($uploaded) . " uppladdat, " . mkprettytime($seedtime) . " seedtid, $torrents torrents, $trails[0] trailers, " . mkprettytime($onlinetime) . " onlinetid, $user[posts_week] foruminlägg = <b>{$bonuspoints}p</b> denna vecka)</td></tr>\n");
	}
}

if ($user["class"] >= UC_POWER_USER)
{
	if ($user["bets"])
	{
		$winproc1 = number_format(($user["bet_wincount"] / $user["bets"]) * 100);
		$winproc2 = number_format(($user["bet_win"] / $user["bet_stakes"]) * 100);
		print("<tr class='clear line'><td class='form'>Betting</td><td class='small'>Bets: <b>" . $user["bets"] . " st</b>, satsat: <b>" . number_format($user["bet_stakes"]) . " p</b>, vinster: <b>" . $user["bet_wincount"] . " st</b> (<b>$winproc1 %</b>), vunnet: <b>" . number_format($user["bet_win"]) . " p</b> (<b>$winproc2 %</b>), vinstrekord: <b>" . number_format($user["bet_record"]) . " p</b></td></tr>\n");
	}

	$res = mysql_query("SELECT COUNT(*) AS count, SUM(bet) AS bet FROM bets WHERE userid = $user[id]") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);

	if ($arr["count"])
		print("<tr class='clear line'><td class='form'>Aktiva bets</td><td>Antal: <b>$arr[count] st</b>, satsat: <b>" . number_format($arr["bet"]) . " p</b></td></tr>\n");
}

if ($user["gender"] != 'NA')
{
	$gender = ($user["gender"] == 'male' ? "Man" : "Kvinna");
	print("<tr class='clear line'><td class='form'>Kön</td><td>$gender</td></tr>\n");
}

print("<tr class='clear line'><td class='form'>Klass</td><td>" . get_user_class_name($user["class"]) . ($user["title"] ? " | " . htmlspecialchars($user["title"]) : "") . "</td></tr>\n");

if ($user["invitedby"] && ($user["id"] == $CURUSER["id"] || staff()))
{
	$inviter = mysql_query("SELECT username FROM users WHERE id = $user[invitedby]") or sqlerr(__FILE__, __LINE__);
	
	if ($inviter = mysql_fetch_assoc($inviter))
		$inviter = "<a href='userdetails.php?id=$user[invitedby]'>$inviter[username]</a>";
	else
		$inviter = "<i>Borttagen</i>";
		
	print("<tr class='clear line'><td class='form'>Inbjuden av</td><td>$inviter</td></tr>\n");
}

if ($user["website"])
	print("<tr class='clear line'><td class='form'>Hemsida</td><td>$user[website]</td></tr>\n");

$posts = mysql_query("SELECT id FROM posts WHERE userid = $user[id]") or sqlerr(__FILE__, __LINE__);

if ($posts = mysql_num_rows($posts))
	print("<tr class='clear line'><td class='form'>Foruminlägg</td><td>" . ($userid == $CURUSER["id"] || get_user_class() >= UC_MODERATOR ? "<a href='/forums.php/userposts?id=$userid'>$posts</a>" : "$posts") . "</td></tr>\n");

$new = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM torrents WHERE owner = $user[id]" . (get_user_class() < UC_MODERATOR && $CURUSER["id"] != $user["id"] ? " AND anonymous = 'no'" : "") . " AND req = 0 ORDER BY added DESC"));
$archive = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM torrents WHERE owner = $user[id]" . (get_user_class() < UC_MODERATOR && $CURUSER["id"] != $user["id"] ? " AND anonymous = 'no'" : "") . " AND req = 2 ORDER BY added DESC"));
$requests = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM torrents WHERE owner = $user[id]" . (get_user_class() < UC_MODERATOR && $CURUSER["id"] != $user["id"] ? " AND anonymous = 'no'" : "") . " AND req = 1 ORDER BY added DESC"));

if ($viewtraffic)
{
	$leeching = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM peers WHERE uid = $user[id] AND `left` > 0 AND active = 1"));
	$seeding = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM peers WHERE uid = $user[id] AND `left` = 0 AND active = 1"));
}

if (staff() && ($CURUSER["id"] == $user["id"] || $CURUSER["class"] > $user["class"]))
{
	$seeded = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM snatched WHERE userid = $user[id] AND download = 'no'"));
	$leeched = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM snatched WHERE userid = $user[id] AND download = 'yes'"));
}

if ($new[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;new=1', 'u1')\"><img src='/pic/plus.gif' id='pu1' style='vertical-align: text-bottom;' /> Uppladdade<br />nya länkar</a> (" . $new[0] . " st)</td><td><div id='u1' style='display: none;'></div></td></tr>\n");

if ($archive[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;old=1', 'u2')\"><img src='/pic/plus.gif' id='pu2' style='vertical-align: text-bottom;' /> Uppladdade<br />gamla länkar</a> (" . $archive[0] . " st)</td><td><div id='u2' style='display: none;'></div></td></tr>\n");

if ($requests[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;requests=1', 'u3')\"><img src='/pic/plus.gif' id='pu3' style='vertical-align: text-bottom;' /> Uppladdade<br />requestade länkar</a> (" . $requests[0] . " st)</td><td><div id='u3' style='display: none;'></div></td></tr>\n");

if ($seeding[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;seeding=1', 'u4')\"><img src='/pic/plus.gif' id='pu4' style='vertical-align: text-bottom;' /> Seedar för<br />närvarande</a> (" . $seeding[0] . " st)</td><td><div id='u4' style='display: none;'></div></td></tr>\n");

if ($leeching[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;leeching=1', 'u5')\"><img src='/pic/plus.gif' id='pu5' style='vertical-align: text-bottom;' /> Leechar för<br />närvarande</a> (" . $leeching[0] . " st)</td><td><div id='u5' style='display: none;'></div></td></tr>\n");

if ($seeded[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;seeded=1', 'u6')\"><img src='/pic/plus.gif' id='pu6' style='vertical-align: text-bottom;' /> Seedade<br />länkar</a> (" . $seeded[0] . " st)</td><td><div id='u6' style='display: none;'></div></td></tr>\n");
	
if ($leeched[0])
	print("<tr class='clear line'><td class='form' style='vertical-align: top;'><a class='jlink' onclick=\"loadPage('peers.php?id=$user[id]&amp;leeched=1', 'u7')\"><img src='/pic/plus.gif' id='pu7' style='vertical-align: text-bottom;' /> Leechade<br />länkar</a> (" . $leeched[0] . " st)</td><td><div id='u7' style='display: none;'></div></td></tr>\n");
	
print("</table>\n");

if ($user["pres"])
	print("<div id='userinfo'>" . format_comment($user["pres"]) . "</div>\n");
	
print("</div>\n");

if ($user["id"] != $CURUSER["id"])
{
	print("<div id='useractions'>");
	print("<input type='button' class='btag' onClick='sendMess($user[id])' value='Skicka meddelande' /></div>\n");
}
	
print("</div>\n");

if (get_user_class() >= UC_MODERATOR && $user["class"] < get_user_class() || get_user_class() >= UC_SYSOP)
{
	print("<div style='display: inline-block; margin: 20px auto 0px auto; text-align: left;'><h2>Ändra användare</h2>\n");
	
	print("<script type='text/javascript'>\n");
	?>
	function traffic(pic, input)
	{
		$pic = $("img#" + pic);
		$input = $("input[name='" + input + "']");
		
		if ($pic.is("[src*='plus']"))
		{
			$pic.attr("src", "/pic/minus.gif");
			$input.val("minus");
		}
		else
		{
			$pic.attr("src", "/pic/plus.gif");
			$input.val("plus");
		}
	}
	<?php
	print("</script>\n");

	print("<form method='post' action='edituser.php' id='userform' autocomplete='off'><input type='hidden' name='id' id='userid' value=$user[id] /><table>\n");

	print("<tr><td class='form'>Aktiverad</td><td><input type='radio' name='enabled' value='yes'" . ($user["enabled"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='enabled' value='no'" . ($user["enabled"] != 'yes' ? " checked" : "") . " /> Nej</td></tr>\n");
	print("<tr><td class='form'>Användarnamn</td><td><input type='text' name='username' value='$user[username]' size=30 /></td></tr>\n");
	print("<tr><td class='form'>Nytt lösenord</td><td><input type='password' name='password' size=30 /></td></tr>\n");
	print("<tr><td class='form'>Repetera lösenord</td><td><input type='password' name='passwordagain' size=30 /></td></tr>\n");
	print("<tr><td class='form'>Mail</td><td><input type='text' name='mail' value='$user[mail]' size=30 /></td></tr>\n");

	$classes = "<select name='class'>\n";

	if ($CURUSER["class"] > UC_ADMINISTRATOR)
		$maxclass = $CURUSER["class"];
	else
		$maxclass = $CURUSER["class"] - 1;

	for ($i = 0; $i <= $maxclass; $i++)
		$classes .= "<option value=$i" . ($i == $user["class"] ? " selected" : "") . ">" . get_user_class_name($i) . "</option>\n";
	
	$classes .= "</select>\n";

	print("<tr><td class='form'>Klass</td><td>$classes</td></tr>\n");
	print("<tr><td class='form'>Betadmin</td><td><input type='radio' name='betadmin' value='yes'" . ($user["betadmin"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='betadmin' value='no'" . ($user["betadmin"] != 'yes' ? " checked" : "") . " /> Nej</td></tr>\n");
	print("<tr><td class='form'>Titel</td><td><input type='text' name='title' value='$user[title]' size=30 /></td></tr>\n");
	print("<tr><td class='form'>Dubbel seed</td><td><input type='text' name='doubleupload' size=30 value='" . (strtotime($user["doubleupload"]) >= time() ? $user["doubleupload"] : "0000-00-00 00:00:00") . "' /></td></tr>\n");
	print("<tr><td class='form'>Fri leech</td><td><input type='text' name='freeleech' size=30 value='" . (strtotime($user["freeleech"]) >= time() ? $user["freeleech"] : "0000-00-00 00:00:00") . "' /></td></tr>\n");
	print("<tr><td class='form'>Uppladdat</td><td><img src='/pic/plus.gif' id='muploaded' onclick=\"traffic('muploaded', 'upchange')\" /><input type='hidden' name='upchange' value='plus' /><input type='text' name='uploaded' size=10 /><select name='formatuploaded'><option value=1048576>MB</option><option value=1073741824>GB</option></select></td></tr>\n");
	print("<tr><td class='form'>Nedladdat</td><td><img src='/pic/plus.gif' id='mdownloaded' onclick=\"traffic('mdownloaded', 'downchange')\" /><input type='hidden' name='downchange' value='plus' /><input type='text' name='downloaded' size=10 /><select name='formatdownloaded'><option value=1048576>MB</option><option value=1073741824>GB</option></select></td></tr>\n");
	print("<tr><td class='form'>Bonuspoäng</td><td><input type='text' name='seedbonus' value='$user[seedbonus]' size=10 /></td></tr>\n");
	
	$blog = mysql_query("SELECT * FROM bonuslog WHERE userid = $user[id] ORDER BY added DESC");
	
	while ($log = mysql_fetch_assoc($blog))
		$bonuslog .= "$log[added] - $log[body]\n";
	
	print("<tr><td class='form' style='vertical-align: top;'>Bonuslogg</td><td><textarea name='bonuslog' cols=60 rows=6 readonly>$bonuslog</textarea></td></tr>\n");
	print("<tr><td class='form' style='vertical-align: top;'>Logg</td><td><textarea name='modcomment' cols=60 rows=6 readonly>$user[modcomment]</textarea></td></tr>\n");

	$warn = "<select name='warnlength'>\n";
	$warntime = array(1, 2, 4, 8);

	foreach($warntime AS $t)
		$warn .= "<option value=$t>$t veck" . ($t > 1 ? "or" : "a") . "</option>\n";
	
	$warn .= "</select>\n";

	print("<tr><td class='form'>Varnad</td><td><input type='radio' name='warned' value='yes'" . ($user["warned"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='warned' value='no'" . ($user["warned"] != 'yes' ? " checked" : "") . " /> Nej");

	if ($user["warned"] == 'yes')
		print(" ($user[warned_reason]) " . mkprettytime(strtotime($user["warned_until"]) - time()) . " kvar</td></tr>\n");
	else
		print(" $warn Anledning: på grund av <input type='text' name='warnreason' /></td></tr>\n");

	if ($user["times_warned"])
	{
		if ($user["warned_by"])
		{
			$warner = mysql_query("SELECT username FROM users WHERE id = $user[warned_by]") or sqlerr(__FILE__, __LINE__);
		
			if ($warner = mysql_fetch_assoc($warner))
				$warner = "<a href='userdetails.php?id=$user[warned_by]'>$warner[username]</a>";
			else
				$warner = "<i>Borttagen</i>";
		}
		else
			$warner = "<i>System</i>";
		
		print("<tr><td class='form'>Varningar</td><td><b>$user[times_warned]</b> - senast av $warner (" . get_elapsed_time($user["last_warned"]) . " sedan)</td></tr>\n");
	}

	if (get_user_class() >= UC_SYSOP)
		print("<tr><td class='form'>Donatör</td><td><input type='radio' name='donor' value='yes'" . ($user["donor"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='donor' value='no'" . ($user["donor"] != 'yes' ? " checked" : "") . " /> Nej <input type='text' name='donated' value='$user[donated]' /></td></tr>\n");
		
	print("<tr><td class='form'>Krona</td><td><input type='radio' name='crown' value='yes'" . ($user["crown"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='crown' value='no'" . ($user["crown"] != 'yes' ? " checked" : "") . " /> Nej</td></tr>\n");
	print("<tr><td class='form'>Forumrättigheter</td><td><input type='radio' name='forumrights' value='yes'" . ($user["forumrights"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='forumrights' value='no'" . ($user["forumrights"] != 'yes' ? " checked" : "") . " /> Nej</td></tr>\n");
	print("<tr><td class='form'>PM-rättigheter</td><td><input type='radio' name='pmrights' value='yes'" . ($user["pmrights"] == 'yes' ? " checked" : "") . " /> Ja <input type='radio' name='pmrights' value='no'" . ($user["pmrights"] != 'yes' ? " checked" : "") . " /> Nej</td></tr>\n");
	print("<tr><td class='form'>Ålder</td><td><input type='text' name='age' value='$user[age]' size=30 /></td></tr>\n");
	print("<tr><td class='form'>Avatar</td><td><input type='text' name='avatar' value='$user[avatar]' size=30 /></td></tr>\n");
	print("<tr><td class='form'>Hemsida</td><td><input type='text' name='website' value='$user[website]' size=30 /></td></tr>\n");
	print("<tr><td class='form' style='vertical-align: top;'>Presentation</td><td><textarea name='pres' cols=60 rows=6>$user[pres]</textarea></td></tr>\n");
	print("<tr><td class='form' style='color: red;'>Radera</td><td><input type='checkbox' onClick='$(\"input#del\").removeAttr(\"disabled\")' /> <input type='submit' name='del' id='del' value='Radera' disabled /></td></tr>\n");
	print("<tr><td colspan=2 style='text-align: center;'><input type='submit' id='edituser' value='Ändra' /><span class='errormess' style='margin-left: 10px;'></span></td></tr>\n");

	print("</table></form></div>\n");
}

foot();
?>