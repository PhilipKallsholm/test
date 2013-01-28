<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	if ($_GET["send"])
	{
		if ($CURUSER["pmrights"] == 'no')
			jErr("Dina PM-rättigheter har blivit inaktiverade");
	
		$userid = 0 + $_POST["userid"];
		$users = mysql_query("SELECT username, acceptpms FROM users WHERE id = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
		
		if ($user = mysql_fetch_assoc($users))
			$username = $user["username"];
		else
			jErr("Användaren finns inte");
			
		if ($userid == $CURUSER["id"])
			jErr("Du kan inte skriva till dig själv");
		
		if (get_user_class() < UC_MODERATOR)
		{
			if ($user["acceptpms"] == 'all')
			{
				$blocked = mysql_query("SELECT id FROM blocks WHERE userid = " . sqlesc($userid) . " AND blockid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
			
				if (mysql_num_rows($blocked))
					jErr("Du har inte behörighet att skriva till denna medlem (blockerad)");
			}
			elseif ($user["acceptpms"] == 'friends')
			{
				$friend = mysql_query("SELECT * FROM friends WHERE userid = " . sqlesc($userid) . " AND friendid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
			
				if (!mysql_num_rows($friend))
					jErr("Du har inte behörighet att skriva till denna medlem (ej vän)");
			}
			elseif ($user["acceptpms"] == 'staff')
				jErr("Du har inte behörighet att skriva till denna medlem (ej staff)");
		}
			
		$return["head"] = "Skicka meddelande till $username";
		
		$return["body"] = "<form method='post' action='/messages.php?takesend=1' id='message'><input type='hidden' name='userid' value=$userid />";
		$return["body"] .= "<b>Ämne:</b><input type='text' name='subject' id='messubject' />";
	
		foreach ($smilies AS $key => $url)
			$smil .= "<img class='msmilie' title='" . htmlent($key) . "' src='/pic/smilies/$url' />";
	
		$return["body"] .= "<div id='messmilies'>$smil</div><textarea name='body' id='messfield'></textarea>\n";
		$return["body"] .= "<div style='clear: both; text-align: right;'><span class='errormess' id='messerr' style='margin-right: 10px;'></span><input type='submit' value='Skicka' id='sendmess' /></div>";
		$return["body"] .= "<div class='undermess'><input type='checkbox' name='save' value=1" . ($CURUSER["savepms"] == 'yes' ? " checked" : "") . " /> Spara meddelande i Skickat</div></form>";

		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["takesend"])
	{
		if ($CURUSER["pmrights"] == 'no')
			jErr("Dina PM-rättigheter har blivit inaktiverade");
			
		$userid = 0 + $_POST["userid"];
		$subject = trim($_POST["subject"]);
		$body = trim($_POST["body"]);
		
		$users = mysql_query("SELECT username, acceptpms, notifo FROM users WHERE id = " . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
		
		if ($user = mysql_fetch_assoc($users))
			$username = $user["username"];
		else
			jErr("Användaren finns inte");
			
		if (!$body)
			jErr("Du måste skriva något");
			
		if ($userid == $CURUSER["id"])
			jErr("Du kan inte skriva till dig själv");
			
		if (get_user_class() < UC_MODERATOR)
		{
			if ($user["acceptpms"] == 'all')
			{
				$blocked = mysql_query("SELECT id FROM blocks WHERE userid = " . sqlesc($userid) . " AND blockid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
			
				if (mysql_num_rows($blocked))
					jErr("Du har inte behörighet att skriva till denna medlem (blockerad)");
			}
			elseif ($user["acceptpms"] == 'friends')
			{
				$friend = mysql_query("SELECT * FROM friends WHERE userid = " . sqlesc($userid) . " AND friendid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
			
				if (!mysql_num_rows($friend))
					jErr("Du har inte behörighet att skriva till denna medlem (ej vän)");
			}
			elseif ($user["acceptpms"] == 'staff')
				jErr("Du har inte behörighet att skriva till denna medlem (ej staff)");
		}
			
		if (!$subject)
			$subject = "Inget ämne";
			
		if ($_POST["save"])
			$saved = 'yes';
		else
			$saved = 'no';
			
		$dt = get_date_time();
		
		mysql_query("INSERT INTO messages (receiver, sender, added, subject, body, saved) VALUES(" . implode(", ", array_map("sqlesc", array($userid, $CURUSER["id"], $dt, $subject, $body, $saved))) . ")") or sqlerr(__FILE__, __LINE__);
		$id = mysql_insert_id();
		
		if ($userid != 1 && $CURUSER["id"] != 1)
		{
			$guards = mysql_query("SELECT word FROM guardedwords") or sqlerr(__FILE__, __LINE__);
		
			while ($guard = mysql_fetch_assoc($guards))
				if (stripos($body, $guard["word"]) !== false)
				{
					mysql_query("INSERT INTO guardedwordslog (messid, userid, receiver, added, body) VALUES($id, $CURUSER[id], " . sqlesc($userid) . ", '" . get_date_time() . "', " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
					break;
				}
		}
			
		if ($user["notifo"])
			notifo_send($user["notifo"], "Nytt meddelande", $CURUSER["username"] . " har skickat ett meddelande till dig", "messages.php");
		
		$return["conf"] = "<h1>Skickat</h1>Meddelandet har blivit skickat";
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["view"])
	{
		$id = 0 + $_POST["id"];

		$res = mysql_query("SELECT id, receiver, sender, body, `read`, added FROM messages WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
	
		if (!$arr)
			jErr("Meddelandet finns inte");
		
		if ($arr["receiver"] != $CURUSER["id"] && $arr["sender"] != $CURUSER["id"])
			jErr("Meddelandet tillhör inte dig");
			
		if (!$arr["sender"])
		{
			$username = "<i>System</i>";
			$avatar = "/pic/default_avatar.jpg";
		}
		else
		{		
			$users = mysql_query("SELECT username, avatar FROM users WHERE id = $arr[sender]") or sqlerr(__FILE__, __LINE__);
		
			if ($user = mysql_fetch_assoc($users))
			{
				$username = "<a href='userdetails.php?id=$arr[sender]'>$user[username]</a>";
				
				$avatar = $user["avatar"];
				
				if (!$avatar)
					$avatar = "/pic/default_avatar.jpg";
			}
			else
			{
				$user = "<i>Borttagen</i>";
				$avatar = "/pic/default_avatar.jpg";
			}
		}
	
		$return["body"] = "<div class='overmess'>Senaste meddelande från $username: " . get_elapsed_time($arr["added"]) . " sedan</div>";
		
		if ($arr["receiver"] == $CURUSER["id"] && $arr["sender"])
			$return["body"] .= "<form method='post' action='messages.php?takereply=1' id='reply$id'><input type='hidden' name='id' value=$id /><textarea class='replystandard' name='body' id='reply'>Svara...</textarea><div class='replyactions' id='ra$arr[id]'><div style='float: left;'><input type='checkbox' name='del' id='d$id' value=1" . ($CURUSER["delpms"] != 'no' ? " checked" : "") . " /> Radera besvarat meddelande <input type='checkbox' name='save' id='s$id' value=1" . ($CURUSER["savepms"] == 'yes' ? " checked" : "") . " /> Spara meddelande i Skickat</div><span class='errormess' id='e$id' style='margin-right: 10px;'></span><input type='submit' value='Skicka' id='reply$id' /></div></form>";
			
		$return["body"] .= "<img src='$avatar' style='width: 50px; vertical-align: text-top;' /><div style='width: 520px; float: right; min-height: 100px;'>" . format_message($arr["body"]) . "</div>";
		$return["body"] .= "<div class='undermess'><a class='jlink' onClick='delMess($id)'>Radera</a></div>";
	
		if (!$arr["read"] && $arr["receiver"] == $CURUSER["id"])
			mysql_query("UPDATE messages SET `read` = 1 WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	
		print(json_encode($return));
		die;
	}
	
	if ($_GET["takereply"])
	{
		$id = 0 + $_POST["id"];
		
		$res = mysql_query("SELECT * FROM messages WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
		
		$dt = get_date_time();
		$subject = "Re: " . $arr["subject"];
		$body = trim($_POST["body"]);
		$saved = ($_POST["save"] ? "yes" : "no");
		
		if (!$arr)
			jErr("Meddelandet finns inte");
			
		$sender = mysql_query("SELECT id, notifo FROM users WHERE id = $arr[sender]") or sqlerr(__FILE__, __LINE__);
		$sender = mysql_fetch_assoc($sender);
		
		if (!$sender)
			jErr("Användaren finns inte");
		
		if ($CURUSER["id"] != $arr["receiver"])
			jErr("Meddelandet tillhör inte dig");
			
		if (!$body)
			jErr("Du måste skriva något");
		
		$oldmess = mysql_query("SELECT * FROM messages WHERE id = $arr[answerid]") or sqlerr(__FILE__, __LINE__);
		$old = mysql_fetch_assoc($oldmess);
		
		if ($arr["read"] == 2 && $old)
			$body = $body . "\n\n{" . $old["sender"] . "@$old[added]}\n$old[body]";
		else
			$body = $body . "\n\n{" . $arr["sender"] . "@$arr[added]}\n$arr[body]";
		
		if ($old)
			mysql_query("UPDATE messages SET `read` = 0, added = '$dt', body = " . sqlesc($body) . ", location = 1, answerid = " . sqlesc($id) . " WHERE id = $arr[answerid]") or sqlerr(__FILE__, __LINE__);
		else
		{
			mysql_query("INSERT INTO messages (receiver, sender, added, subject, body, saved, answerid) VALUES($arr[sender], $CURUSER[id], '$dt', " . sqlesc($subject) . ", " . sqlesc($body) . ", '$saved', " . sqlesc($id) . ")") or sqlerr(__FILE__, __LINE__);
			$answerid = mysql_insert_id();
			
			mysql_query("UPDATE messages SET answerid = $answerid WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		}
		
		mysql_query("UPDATE messages SET `read` = 2 WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		if ($arr["receiver"] != 1 && $arr["sender"] != 1)
		{
			$guards = mysql_query("SELECT word FROM guardedwords") or sqlerr(__FILE__, __LINE__);
		
			while ($guard = mysql_fetch_assoc($guards))
				if (stripos($body, $guard["word"]) !== false)
				{
					$res = mysql_query("SELECT id FROM guardedwordslog WHERE messid = " . sqlesc($id) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
				
					if (mysql_num_rows($res))
						mysql_query("UPDATE guardedwordslog SET added = '$dt', body = " . sqlesc($body) . " WHERE messid = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
					else
						mysql_query("INSERT INTO guardedwordslog (messid, userid, receiver, added, body) VALUES(" . sqlesc($id) . ", $CURUSER[id], $arr[sender], '" . get_date_time() . "', " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
				
					break;
				}
		}
		
		if ($sender["notifo"])
			notifo_send($sender["notifo"], "Nytt meddelande", $CURUSER["username"] . " har skickat ett meddelande till dig", "messages.php");
		
		$return["conf"] = "Meddelande skickat";
		
		if ($_POST["del"])
		{
			$return["del"] = 1;
			$return["conf"] .= " och tas nu bort <img src='/pic/load.gif' />";
		}
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["del"])
	{
		$id = 0 + $_POST["id"];
		
		$res = mysql_query("SELECT receiver, sender, saved, location FROM messages WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
		
		if (!$arr)
			jErr("Meddelandet finns inte");
			
		if ($arr["receiver"] != $CURUSER["id"] && $arr["sender"] != $CURUSER["id"])
			jErr("Meddelandet tillhör inte dig");
			
		if ($arr["receiver"] == $CURUSER["id"] && $arr["saved"] == 'yes')
			mysql_query("UPDATE messages SET location = 0 WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		elseif ($arr["sender"] == $CURUSER["id"] && $arr["location"] != 0)
			mysql_query("UPDATE messages SET saved = 'no' WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		else
			mysql_query("DELETE FROM messages WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		print(json_encode(""));
		die;
	}
}

$sent = $_GET["sent"];
$page = 0 + $_GET["page"];

head(($sent ? "Skickat" : "Inkorg"));

$perpage = $CURUSER["pmperpage"];

if (!$perpage)
	$perpage = 25;

if ($sent)
	$res = mysql_query("SELECT id FROM messages WHERE sender = $CURUSER[id] AND saved = 'yes'") or sqlerr(__FILE__, __LINE__);
else
	$res = mysql_query("SELECT id FROM messages WHERE receiver = $CURUSER[id] AND location != 0") or sqlerr(__FILE__, __LINE__);
	
$count = mysql_num_rows($res);

list($pager, $limit) = pager("/messages.php" . ($sent ? "?sent=1&page=" : "?page="), $count, $perpage, $page);

if ($sent)
	$res = mysql_query("SELECT * FROM messages WHERE sender = $CURUSER[id] AND saved = 'yes' ORDER BY added DESC $limit") or sqlerr(__FILE__, __LINE__);
else
	$res = mysql_query("SELECT * FROM messages WHERE receiver = $CURUSER[id] AND location != 0 ORDER BY added DESC $limit") or sqlerr(__FILE__, __LINE__);

print("<script type='text/javascript'>\$delpms = " . ($CURUSER["delpms"] == no ? "false" : "true") . "; \$savepms = " . ($CURUSER["savepms"] == yes ? "true" : "false") . ";</script>\n");

print("<h1>" . ($sent ? "<a href='messages.php'>Inkorg</a>" : "<font style='color: #b1b1b1;'>Inkorg</font>") . " / " . ($sent ? "<font style='color: #b1b1b1;'>Skickat</font>" : "<a href='messages.php?sent=1'>Skickat</a>") . "</h1>\n");
print("<p style='text-align: center;'>$pager</p>\n");
print("<table id='messages'><tr><td class='colhead'>Ämne</td><td class='colhead'>" . ($sent ? "Mottagare" : "Avsändare") . "</td><td class='colhead' colspan=2>Datum</td></tr>\n");

if (!$count)
{
	print("<tr><td colspan=4 style='text-align: center; font-style: italic;'>Inga meddelanden</td></tr>\n");
	$disabled = true;
}

$i = 0;
while ($arr = mysql_fetch_assoc($res))
{
	$senderid = $sent ? $arr["receiver"] : $arr["sender"];
	
	if ($arr["subject"])
		$subject = $arr["subject"];
	else
		$subject = "<i>Inget ämne</i>";
		
	if (!$senderid)
		$sender = "<i>System</i>";
	else
	{
		$sender = mysql_query("SELECT username FROM users WHERE id = " . ($sent ? "$arr[receiver]" : "$arr[sender]")) or sqlerr(__FILE__, __LINE__);
	
		if ($sender = mysql_fetch_assoc($sender))
			$sender = "<a href='userdetails.php?id=" . ($sent ? "$arr[receiver]" : "$arr[sender]") . "'>$sender[username]</a>";
		else
			$sender = "<i>Borttagen</i>";
	}
		
	switch ($arr["read"])
	{
		case 0:
			$pic = "mess_unread.png";
			$alt = "Oläst meddelande";
			break;
		case 1:
			$pic = "mess_read.png";
			$alt = "Läst meddelande";
			break;
		case 2:
			$pic = "mess_answered.png";
			$alt = "Besvarat meddelande";
			break;
	}

	print("<tr id='m$arr[id]' class='messhead'><td><img src='/pic/$pic' title='$alt' /><a class='jlink' onClick='readMess($arr[id])' style='margin-left: 10px;'>$subject</a></td><td id='s$arr[id]'>$sender</td><td>$arr[added]</td><td><input type='checkbox' name='del[]' value=$arr[id] /></td></tr>\n");
}

print("<tr class='clear'><td style='text-align: right; padding: 5px 0px;' colspan=4><input type='button' id='selectall' name='select' value='Markera alla'" . ($disabled ? " disabled" : "") . " /> <input type='button' id='deletemess' value='Radera'" . ($disabled ? " disabled" : "") . " /></td></tr>\n");
print("</table>\n");
print("<p style='text-align: center;'>$pager</p>\n");

foot();

?>