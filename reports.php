<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

if ($_POST)
{
	$id = 0 + $_POST["id"];
	
	if ($_GET["solve"])
	{	
		mysql_query("UPDATE reports SET solvedby = $CURUSER[id] WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		$return["user"] = "<a href='userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a>";
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["del"])
	{
		mysql_query("DELETE FROM reports WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		die;
	}
}

head("Rapporter");
begin_frame("Rapporter", "", true);

$res = mysql_query("SELECT * FROM reports") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("reports.php?page=", $count, 25, $_GET["page"]);

print("<p style='text-align: center;'>$pager</p>\n");

print("<table>\n");
print("<tr><td class='colhead'>Typ</td><td class='colhead'>Länk</td><td class='colhead'>Orsak</td><td class='colhead'>Rapporterade</td><td class='colhead'>Datum</td><td class='colhead'>Handläggare</td><td class='colhead'>Behandla</td><td class='colhead' style='text-align: center;'>X</td></tr>\n");

$res = mysql_query("SELECT reports.*, r.username AS reporter, s.username AS solver FROM reports LEFT JOIN users r ON r.id = reports.userid LEFT JOIN users s ON s.id = reports.solvedby ORDER BY added DESC $limit") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	switch ($arr["type"])
	{
		case "comment":
			$type = "Kommentar";
			break;
		case "post":
			$type = "Inlägg";
			break;
		case "torrent":
			$type = "Torrent";
			break;
		case "user":
			$type = "Användare";
			break;
	}

	if ($arr["type"] == 'user')
	{
		$user = mysql_query("SELECT username FROM users WHERE id = $arr[typeid]") or sqlerr(__FILE__, __LINE__);
		
		if ($user = mysql_fetch_assoc($user))
			$link = "<a href='userdetails.php?id=$arr[typeid]'>$user[username]</a>";
		else
			$link = "<i>Borttagen</i>";
	}
	elseif ($arr["type"] == 'torrent')
	{
		$post = mysql_query("SELECT name FROM torrents WHERE id = $arr[typeid]") or sqlerr(__FILE__, __LINE__);
		
		if ($post = mysql_fetch_assoc($post))
			$link = "<a href='details.php?id=$arr[typeid]'>#$arr[typeid]</a>";
		else
			$link = "<i>Borttagen</i>";
	}
	elseif ($arr["type"] == 'post')
	{
		$post = mysql_query("SELECT topicid FROM posts WHERE id = $arr[typeid]") or sqlerr(__FILE__, __LINE__);
		
		if ($post = mysql_fetch_assoc($post))
			$link = "<a href='forums.php/viewtopic/$post[topicid]/" . findPage($arr["typeid"]) . "#p$arr[typeid]'>#$arr[typeid]</a>";
		else
			$link = "<i>Borttagen</i>";
	}
	elseif ($arr["type"] == 'comment')
	{
		$comment = mysql_query("SELECT torrentid FROM comments WHERE id = $arr[typeid]") or sqlerr(__FILE__, __LINE__);
		
		if ($comment = mysql_fetch_assoc($comment))
			$link = "<a href='details.php?id=$comment[torrentid]'>#$arr[typeid]</a>";
		else
			$link = "<i>Borttagen</i>";
	}
		
	if ($arr["reporter"])
		$reporter = "<a href='userdetails.php?id=$arr[userid]'>$arr[reporter]</a>";
	else
		$reporter = "<i>Borttagen</i>";
		
	if ($arr["solvedby"])
	{
		if ($arr["solver"])
			$solver = "<a href='userdetails.php?id=$arr[solvedby]'>$arr[solver]</a>";
		else
			$solver = "<i>Borttagen</i>";
	}
	else
		$solver = "-";

	print("<tr id='r$arr[id]' style='background-color: " . ($arr["solvedby"] ? "#ccffcc" : "#ffcccc") . ";'><td>$type</td><td>$link</td><td>$arr[reason]</td><td>$reporter</td><td>$arr[added] (" . get_elapsed_time($arr["added"]) . " sedan)</td><td id='u$arr[id]'>$solver</td><td><input type='button' value='Behandla' id='s$arr[id]' onClick='solveReport($arr[id])'" . ($arr["solvedby"] ? " disabled" : "") . " /></td><td><input type='checkbox' onClick='delReport($arr[id])' /></td></tr>\n");
}

print("</table>\n");
print("<p style='text-align: center;'>$pager</p>\n");
print("</div></div>\n");

foot();
?>