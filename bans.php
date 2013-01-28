<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

$mail = $_GET["mail"];

if ($_POST)
{
	if ($_GET["add"])
	{
		if ($mail)
		{
			$mail = trim($_POST["mail"]);
			$reason = trim($_POST["reason"]);
			$dt = get_date_time();
		
			if (!$mail)
				jErr("Du måste ange en mailadress");
			
			if (!$reason)
				jErr("Du måste ange en anledning");
			
			$res = mysql_query("SELECT id FROM bannedmails WHERE mail = " . sqlesc($mail) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
		
			if (mysql_num_rows($res))
				jErr("Mailadressen är redan bannad");
			
			mysql_query("INSERT INTO bannedmails (userid, mail, reason, added) VALUES($CURUSER[id], " . sqlesc($mail) . ", " . sqlesc($reason) . ", '$dt')") or sqlerr(__FILE__, __LINE__);
			$id = mysql_insert_id();
		
			$return["result"] = "<tr id='b$id' style='display: none;'><td>$dt</td><td>$mail</td><td><a href='userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a></td><td>$reason</td><td><a class='jlink' onclick='delBan($id" . ($mail ? ", 'mail'" : "") . ")'>Radera</a></td></tr>";
		
			print(json_encode($return));
			die;
		}
		else
		{
			$ip = trim($_POST["ip"]);
			$reason = trim($_POST["reason"]);
			$dt = get_date_time();
		
			if (!$ip)
				jErr("Du måste ange en IP-adress");
			
			if (!$reason)
				jErr("Du måste ange en anledning");
			
			$res = mysql_query("SELECT id FROM bans WHERE ip = " . sqlesc($ip)) or sqlerr(__FILE__, __LINE__);
		
			if (mysql_num_rows($res))
				jErr("IP-adressen är redan bannad");
			
			mysql_query("INSERT INTO bans (userid, ip, reason, added) VALUES($CURUSER[id], " . sqlesc($ip) . ", " . sqlesc($reason) . ", '$dt')") or sqlerr(__FILE__, __LINE__);
			$id = mysql_insert_id();
		
			$return["result"] = "<tr id='b$id' style='display: none;'><td>$dt</td><td>$ip</td><td><a href='userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a></td><td>$reason</td><td><a class='jlink' onclick='delBan($id" . ($mail ? ", 'mail'" : "") . ")'>Radera</a></td></tr>";
		
			print(json_encode($return));
			die;
		}
	}
	
	if ($_GET["del"])
	{
		$id = 0 + $_POST["id"];
		
		mysql_query("DELETE FROM " . (!$_POST["mail"] ? "bans" : "bannedmails") . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		die;
	}
}

head("Bans");

begin_frame((!$mail ? "<span style='color: gray;'>Bannade IP-adresser</span>" : "<a href='bans.php'>Bannade IP-adresser</a>") . " - " . ($mail ? "<span style='color: gray;'>bannade mailadresser</span>" : "<a href='bans.php?mail=1'>bannade mailadresser</a>"), 600);

print("<form method='post' action='bans.php?add=1" . ($mail ? "&amp;mail=1" : "") . "' id='banform'><table>\n");

print("<tr class='main'><td class='form'>" . (!$mail ? "IP" : "Mail") . "</td><td><input type='text' maxlength=15 name='" . (!$mail ? "ip" : "mail") . "' /></td></tr>\n");
print("<tr class='main'><td class='form'>Anledning</td><td><input type='text' maxlength=32 name='reason' /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' id='addban' value='Banna' /><span class='errormess' style='margin-left: 10px;'></span></td></tr>\n");

print("</table></form>\n");

$res = mysql_query("SELECT * FROM " . (!$mail ? "bans" : "bannedmails") . " ORDER BY id DESC") or sqlerr(__FILE__, __LINE__);

print("<table id='bans' style='margin-top: 10px; white-space: nowrap;'><tr><td class='colhead'>Datum</td><td class='colhead'>" . (!$mail ? "IP" : "Mail") . "</td><td class='colhead'>Bannad av</td><td class='colhead'>Anledning</td><td class='colhead'>Radera</td></tr>\n");
	
while ($arr = mysql_fetch_assoc($res))
{
	if ($arr["userid"])
	{
		$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
	
		if ($user = mysql_fetch_assoc($user))
			$username = "<a href='userdetails.php?id=$arr[userid]'>$user[username]</a>";
		else
			$username = "<i>Borttagen</i>";
	}
	else
		$username = "<i>System</i>";

	print("<tr class='main' id='b$arr[id]'><td>$arr[added]</td><td>" . (!$mail ? $arr["ip"] : $arr["mail"]) . "</td><td>$username</td><td>$arr[reason]</td><td><a class='jlink' onclick='delBan($arr[id]" . ($mail ? ", 1" : "") . ")'>Radera</a></td></tr>\n");
}

print("</table>\n");

print("</div></div>\n");
foot();
?>