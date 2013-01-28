<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

if ($_GET["del"])
{
	mysql_query("TRUNCATE TABLE sqlerrors") or sqlerr(__FILE__, __LINE__);
	header("Location: sqlerrors.php");
}

head("SQL-fel");
begin_frame("SQL-fel (<a href='?del=1'>töm</a>)", 600);

$res = mysql_query("SELECT * FROM sqlerrors ORDER BY id DESC") or sqlerr(__FILE__, __LINE__);

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
		$username = "<i>Utloggad</i>";

	print("<span>Rad <b>$arr[line]</b> i <b>$arr[file]</b> när $username besökte <b>$arr[page]</b> (" . get_elapsed_time($arr["added"]) . " sedan)</span>\n");
	print("<div class='frame' style='background-color: white; margin: 5px 0px 10px 0px;'>$arr[error]</div>\n");
}

print("</div></div>\n");
foot();

?>