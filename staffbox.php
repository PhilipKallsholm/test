<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

if ($_POST)
{
	if ($_GET["view"])
	{
		$id = 0 + $_POST["id"];
		
		$res = mysql_query("SELECT * FROM staffmessages WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
	
		if (!$arr)
			jErr("Meddelandet finns inte");
					
		$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
		
		if ($user = mysql_fetch_assoc($user))
			$username = "<a href='userdetails.php?id=$arr[userid]'>$user[username]</a>";
		else
			$username = "<i>Borttagen</i>";
			
		$avatar = mysql_query("SELECT avatar FROM users WHERE id = " . ($arr["staffid"] ? $arr["staffid"] : $arr["userid"])) or sqlerr(__FILE__, __LINE__);
		
		if ($avatar = mysql_fetch_assoc($avatar))
			$avatar = $avatar["avatar"];
		else
			$avatar = "/default_avatar.jpg";
	
		$return["body"] = "<div class='overmess'>Meddelande från $username: " . get_elapsed_time($arr["added"]) . " sedan</div>";
		
		if (!$arr["staffid"])
			$return["body"] .= "<form method='post' id='reply$id'><input type='hidden' name='id' value=$id /><textarea class='replystandard' name='body' id='reply'>Svara...</textarea><div class='replyactions' id='ra$arr[id]'><span class='errormess' id='e$id' style='margin-right: 10px;'></span><input type='submit' value='Skicka' id='reply$id' /></div></form>";
			
		$return["body"] .= "<img src='$avatar' style='width: 50px; vertical-align: text-top;' /><div style='width: 520px; float: right; min-height: 100px;'>" . format_message($arr["body"]) . "</div>";
		$return["body"] .= "<div class='undermess'><a class='jlink' onClick='delstaffMess($id)'>Radera</a></div>";
	
		print(json_encode($return));
		die;
	}
	
	if ($_GET["takereply"])
	{
		$id = 0 + $_POST["id"];
		$body = trim($_POST["body"]);
		$dt = get_date_time();
		
		if (!$body)
			jErr("Du måste skriva något");
		
		$res = mysql_query("SELECT * FROM staffmessages WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);
		
		$subject = "Re: " . $arr["subject"];
		
		if (!$arr)
			jErr("Meddelandet finns inte");
			
		if ($arr["staffid"])
			jErr("Meddelandet är redan besvarat");
			
		$sender = mysql_query("SELECT id FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
		
		if (!mysql_num_rows($sender))
			jErr("Användaren finns inte");
		
		$body = $body . "\n\n{" . $arr["userid"] . "@$arr[added]}\n$arr[body]";
		
		mysql_query("INSERT INTO messages (receiver, sender, added, subject, body) VALUES($arr[userid], $CURUSER[id], '$dt', " . sqlesc($subject) . ", " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
		mysql_query("UPDATE staffmessages SET body = " . sqlesc($body) . ", staffid = $CURUSER[id] WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		$return["res"] = "<a href='userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a>";
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["del"])
	{
		$id = 0 + $_POST["id"];
		
		mysql_query("DELETE FROM staffmessages WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		die;
	}
}

head("Staffmeddelanden");
begin_frame("Meddelanden till support", 700, true);

$res = mysql_query("SELECT * FROM staffmessages") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("staffbox.php?page=", $count, 25, $_GET["page"]);

print("<div style='margin-bottom: 10px; text-align: center;'>$pager</div>\n");

print("<table id='messages'>\n");
print("<tr><td class='colhead'>Ämne</td><td class='colhead'>Avsändare</td><td class='colhead'>Datum</td><td class='colhead'>Handläggare</td>" . (get_user_class() >= UC_SYSOP ? "<td class='colhead' style='text-align: center;'>X</td>" : "") . "</tr>\n");

$res = mysql_query("SELECT * FROM staffmessages ORDER BY added DESC $limit") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
	
	if ($user = mysql_fetch_assoc($user))
		$username = "<a href='userdetails.php?id=$arr[userid]'>$user[username]</a>";
	else
		$username = "<i>Borttagen</i>";
		
	if ($arr["staffid"])
	{
		$answerer = mysql_query("SELECT username FROM users WHERE id = $arr[staffid]") or sqlerr(__FILE__, __LINE__);
	
		if ($answerer = mysql_fetch_assoc($answerer))
			$answerer = "<a href='userdetails.php?id=$arr[staffid]'>$answerer[username]</a>";
		else
			$answerer = "<i>Borttagen</i>";
	}
	else
		$answerer = "<span style='color: red; font-style: italic; font-weight: bold;'>Obesvarat</span>";
	
	print("<tr class='messhead' id='m$arr[id]'><td><a class='jlink' onClick='readstaffMess($arr[id])'>$arr[subject]</a></td><td>$username</td><td>$arr[added]</td><td id='a$arr[id]'>$answerer</td>" . (get_user_class() >= UC_SYSOP ? "<td><input type='checkbox' name='del[]' value=$arr[id] /></td>" : "") . "</tr>\n");
}

if (get_user_class() >= UC_SYSOP)
	print("<tr class='clear'><td colspan=5 style='padding: 5px 0px; text-align: right;'><input type='button' id='delstaffmess' value='Radera' /></td></tr>\n");

print("</table>\n");

print("<div style='margin-bottom: 10px; text-align: center;'>$pager</div>\n");
print("</div></div>\n");

foot();
?>