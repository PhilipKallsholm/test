<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

$action = $_GET["action"];

if ($CURUSER["commentrights"] == 'no')
	stderr("Fel", "Du har blivit avstängd från kommentarerna.");

if ($action == "add")
{
	if ($_SERVER["REQUEST_METHOD"] == 'POST')
	{
		$id = 0 + $_POST["tid"];
	
		$res = mysql_query("SELECT userid FROM comments WHERE torrentid = $id ORDER BY added DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$arr = mysql_fetch_assoc($res);

		if (!staff() && $arr["userid"] == $CURUSER["id"])
			stderr("Fel", "Dubbelposting är inte tillåtet! Vänligen ändra ditt föregående inlägg istället.");

		if (!$id)
			stderr("Fel", "Ogiltigt ID");

		$rating = 0 + $_POST["rating"];

		if ($rating < 0 || $rating > 5)
			stderr("Fel", "Röst ogiltig");

		$res = mysql_query("SELECT name, owner, anonymous FROM torrents WHERE id = $id") or sqlerr(__FILE__,__LINE__);
		$arr = mysql_fetch_array($res);
		
		if (!$arr)
			stderr("Fel", "Länken finns inte");

		$text = trim($_POST["text"]);

		if (!$text)
			stderr("Fel", "Du måste skriva något");
			
		$anonymous = $CURUSER["id"] == $arr["owner"] && $arr["anonymous"] == 'yes' ? "yes" : "no";
		
		mysql_query("INSERT INTO comments (torrentid, userid, added, body, ori_body) VALUES (" . implode(", ", array_map("sqlesc", array($id, $CURUSER["id"], get_date_time(), $text, $text))) . ")") or sqlerr(__FILE__, __LINE__);
		$newid = mysql_insert_id();

		mysql_query("UPDATE torrents SET comments = comments + 1 WHERE id = $id") or sqlerr(__FILE__, __LINE__);

		if ($rating)
		{
			$res = mysql_query("INSERT INTO torrentvotes (torrentid, userid, rating) VALUES ($id, $CURUSER[id], $rating)") or sqlerr(__FILE__, __LINE__);

			if (!$res)
			{
				if (mysql_errno() == 1062)
					stderr("Fel", "Du har redan röstat på denna länk");
				else
					sqlerr(__FILE__, __LINE__);
			}

			mysql_query("UPDATE torrents SET numratings = numratings + 1, ratingsum = ratingsum + $rating WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		}

		header("Location: details.php?id=$id&viewcomm=$newid#comm$newid");

		die;
	}

	$id = 0 + $_GET["tid"];

	if (!$torrentid)
		stderr("Fel", "Ogiltigt ID");

	$res = mysql_query("SELECT name, owner, anonymous FROM torrents WHERE id = $id") or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);
	
	if (!$arr)
	  stderr("Fel", "Länken finns inte");

	head("Skriv en kommentar om \"" . $arr["name"] . "\"");

	print("<h1>Skriv en kommentar om \"" . htmlspecialchars($arr["name"]) . "\"</h1>\n");
	print("<form method='post' action='comment.php?action=add'>\n");
	print("<input type='hidden' name='tid' value=$id />\n");
	print("<textarea name='text' rows=10 cols=60></textarea>\n");
	print("<br /><br /><input type='submit' class='btn' value='Kommentera' /> <input type='button' OnClick=Smilies() class='btn' value='Smilies' /></form>\n");

	$res = mysql_query("SELECT comments.*, users.username, users.avatar FROM comments LEFT JOIN users ON comments.userid = users.id WHERE comments.torrentid = $id ORDER BY comments.id DESC LIMIT 5");

	$allrows = array();
	while ($row = mysql_fetch_array($res))
		$allrows[] = $row;

	if (count($allrows))
	{
		print("<h2>Senaste kommentarerna</h2>\n");
		commenttable($allrows);
	}

	foot();
	die;
}
elseif ($action == 'edit')
{
	$id = 0 + $_GET["cid"];

	if (!$id)
		stderr("Fel", "Ogiltigt ID");

	$res = mysql_query("SELECT c.*, t.name FROM comments AS c JOIN torrents AS t ON c.torrentid = t.id WHERE c.id = $id") or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);

	if (!$arr)
		stderr("Fel", "Kommentaren finns inte");

	if ($arr["userid"] != $CURUSER["id"] && get_user_class() < UC_MODERATOR)
		stderr("Fel", "Kommentaren tillhör inte dig");

	if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
		$text = trim($_POST["text"]);
		$returnto = $_POST["returnto"];

		if (!$text)
			stderr("Fel", "Du måste skriva något");

		mysql_query("UPDATE comments SET body = " . sqlesc($text) . ", editedby = $CURUSER[id], editedat = '" . get_date_time() . "' WHERE id = $id") or sqlerr(__FILE__, __LINE__);

		if ($returnto)
			header("Location: $returnto");
		else
			header("Location: $BASEURL");

		die;
	}

	head("Ändra kommentar om \"" . $arr["name"] . "\"");

	print("<h1>Ändra kommentar om \"" . htmlspecialchars($arr["name"]) . "\"</h1>\n");
	print("<form method='post' action='comment.php?action=edit&amp;cid=$id'>\n");
	print("<input type='hidden' name='returnto' value='" . $_SERVER["HTTP_REFERER"] . "' />\n");
	print("<input type='hidden' name='cid' value=$id />\n");
	print("<p><textarea name='text' rows=10 cols=60>$arr[body]</textarea></p>\n");
	print("<p><input type='submit' class='btn' value='Ändra' /></p></form>\n");

	foot();
	die;
}
elseif ($action == 'delete')
{
	if (get_user_class() < UC_MODERATOR)
		stderr("Fel", "Tillgång nekad");

	$id = 0 + $_GET["cid"];

	if (!$id)
		stderr("Fel", "Ogiltigt ID");

	$sure = $_GET["sure"];

	if (!$sure)
	{
 		$referer = $_SERVER["HTTP_REFERER"];
		stderr("Radera kommentar", "Klicka <a href='?action=delete&cid=$id&sure=1" . ($referer ? "&returnto=" . urlencode($referer) : "") . "'>här</a> för att radera kommentaren.");
	}

	$res = mysql_query("SELECT torrentid FROM comments WHERE id = $id")  or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);

	if ($arr)
		$torrentid = $arr["torrentid"];

	mysql_query("DELETE FROM comments WHERE id = $id") or sqlerr(__FILE__,__LINE__);
	
	if ($torrentid && mysql_affected_rows())
		mysql_query("UPDATE torrents SET comments = comments - 1 WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);

	$returnto = $_GET["returnto"];

	if ($returnto)
	  header("Location: $returnto");
	else
	  header("Location: $BASEURL");

	die;
}
elseif ($action == "vieworiginal")
{
	if (get_user_class() < UC_MODERATOR)
		stderr("Fel", "Tillgång nekad");

	$id = 0 + $_GET["cid"];

	if (!$id)
		stderr("Fel", "Ogiltigt ID");

	$res = mysql_query("SELECT c.*, t.name FROM comments AS c JOIN torrents AS t ON c.torrentid = t.id WHERE c.id = $id") or sqlerr(__FILE__,__LINE__);
	$arr = mysql_fetch_array($res);

	if (!$arr)
		stderr("Fel", "Kommentaren finns inte");

	head("Originalkommentar");

	print("<h1>Originalkommentar för #$id</h1><p>\n");
	print("<table width=500 border=1 cellspacing=0 cellpadding=5>");
	print("<tr><td class=comment>" . format_comment($arr["ori_body"]) . "</td></tr></table>\n");

	$returnto = $_SERVER["HTTP_REFERER"];

	if ($returnto)
		print("<p class='small'>(<a href='$returnto'>Tillbaka</a>)</p>\n");

	foot();
	die;
}
?>