<?php

require_once("globals.php");

dbconn();
staff(UC_SYSOP);
loggedinorreturn();

if ($_POST)
{
	if ($_GET["create"])
	{
		$poll = trim($_POST["poll"]);
		
		foreach ($_POST["answer"] AS $answer)
			if (trim($answer))
				$answers[] = $answer;
				
		if (!$poll)
			jErr("Du måste ange en fråga");
			
		if (count($answers) < 2)
			jErr("Du måste ange minst två svar");
			
		$dt = get_date_time();
			
		mysql_query("INSERT INTO polls (name, added) VALUES(" . sqlesc($poll) . ", '$dt')") or sqlerr(__FILE__, __LINE__);
		$pollid = mysql_insert_id();
		
		foreach ($answers AS $answer)
			mysql_query("INSERT INTO pollalternatives (pollid, name) VALUES($pollid, " . sqlesc($answer) . ")") or sqlerr(__FILE__, __LINE__);
		
		mysql_query("INSERT INTO topics (name, forumid, added, descr) VALUES(" . sqlesc($poll) . ", 2, '$dt', 'Diskutera')") or sqlerr(__FILE__, __LINE__);
		$topicid = mysql_insert_id();
		
		mysql_query("INSERT INTO posts(body, body_orig, topicid, added) VALUES(" . sqlesc($poll) . ", " . sqlesc($poll) . ", $topicid, '$dt')") or sqlerr(__FILE__, __LINE__);
		$postid = mysql_insert_id();
		
		mysql_query("UPDATE topics SET lastpost = $postid WHERE id = $topicid") or sqlerr(__FILE__, __LINE__);
		
		mysql_query("UPDATE polls SET topicid = $topicid WHERE id = $pollid") or sqlerr(__FILE__, __LINE__);
			
		print(json_encode(""));
		die;
	}
	
	if ($_GET["edit"])
	{
		$pollid = 0 + $_POST["pollid"];
		$poll = trim($_POST["poll"]);
		
		$alts = mysql_query("SELECT * FROM pollalternatives WHERE pollid = " . sqlesc($pollid)) or sqlerr(__FILE__, __LINE__);
		
		while ($alt = mysql_fetch_assoc($alts))
			$oldalts[] = $alt["id"];
		
		foreach ($_POST["answer"] AS $k => $answer)
		{
			$answer = trim($answer);
			
			if (in_array($k, $oldalts))
			{
				if (!$answer)
				{
					mysql_query("DELETE FROM pollalternatives WHERE id = " . sqlesc($k)) or sqlerr(__FILE__, __LINE__);
					mysql_query("DELETE FROM pollanswers WHERE voteid = " . sqlesc($k)) or sqlerr(__FILE__, __LINE__);
				}
				else
					mysql_query("UPDATE pollalternatives SET name = " . sqlesc($answer) . " WHERE id = " . sqlesc($k)) or sqlerr(__FILE__, __LINE__);
			}
			elseif ($answer)
				mysql_query("INSERT INTO pollalternatives (pollid, name) VALUES($pollid, " . sqlesc($answer) . ")") or sqlerr(__FILE__, __LINE__);
		}
				
		if (!$poll)
			jErr("Du måste ange en fråga");
			
		mysql_query("UPDATE polls SET name = " . sqlesc($poll) . " WHERE id = $pollid") or sqlerr(__FILE__, __LINE__);
		
		$topic = mysql_query("SELECT topicid FROM polls WHERE id = " . sqlesc($pollid)) or sqlerr(__FILE__, __LINE__);
		$topic = mysql_fetch_assoc($topic);
		
		$post = mysql_query("SELECT id FROM posts WHERE topicid = $topic[topicid] ORDER BY id ASC LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$post = mysql_fetch_assoc($post);
		
		mysql_query("UPDATE posts SET body = " . sqlesc($poll) . ", lastedit = '" . get_date_time() . "' WHERE id = $post[id]") or sqlerr(__FILE__, __LINE__);
			
		print(json_encode(""));
		die;
	}
}

if ($_GET["edit"])
{
	$pollid = 0 + $_GET["edit"];
	
	$poll = mysql_query("SELECT * FROM polls WHERE id = " . sqlesc($pollid)) or sqlerr(__FILE__, __LINE__);
	$poll = mysql_fetch_assoc($poll);
	
	if (!$poll)
		stderr("Fel", "Omröstningen hittades inte");
		
	head("Ändra omröstning \"$poll[name]\"");
	
	print("<h1>Ändra omröstning</h1>\n");

	print("<form method='post' action='?edit=1' id='pollform'><input type='hidden' name='pollid' value=$pollid /><table>\n");
	print("<tr><td class='form'>Fråga</td><td><input type='text' size=50 name='poll' value='$poll[name]' /></td></tr>\n");
	
	$alts = mysql_query("SELECT * FROM pollalternatives WHERE pollid = " . sqlesc($pollid)) or sqlerr(__FILE__, __LINE__);
	
	$i = 1;
	while ($alt = mysql_fetch_assoc($alts))
		print("<tr><td class='form'>Svar " . $i++ . "</td><td><input type='text' size=50 name='answer[$alt[id]]' value='$alt[name]' /></td></tr>\n");
		
	print("<tr><td class='form'>Svar $i</td><td><input type='text' size=50 name='answer[]' /></td></tr>\n");

	print("</table>\n");
	print("<br /><div class='errormess'></div>\n");
	print("<input type='submit' value='Uppdatera' id='pollupdate' />\n");
	print("</form>\n");
	
	foot();
	die;
}

if ($_GET["del"])
{
	$pollid = 0 + $_GET["del"];
		
	$res = mysql_query("SELECT id, topicid FROM polls WHERE id = " . sqlesc($pollid)) or sqlerr(__FILE__, __LINE__);
		
	if ($arr = mysql_fetch_assoc($res))
	{
		mysql_query("DELETE FROM polls WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
		mysql_query("DELETE FROM pollalternatives WHERE pollid = $arr[id]") or sqlerr(__FILE__, __LINE__);
		mysql_query("DELETE FROM pollanswers WHERE pollid = $arr[id]") or sqlerr(__FILE__, __LINE__);
		deletetopic($arr["topicid"]);
			
		header("Location: index.php");
	}
	else
		stderr("Fel", "Omröstningen finns inte");
}

head("Omröstningar");

print("<h1>Ny omröstning</h1>\n");

print("<form method='post' action='?create=1' id='pollform'><table>\n");
print("<tr><td class='form'>Fråga</td><td><input type='text' size=50 name='poll' /></td></tr>\n");
print("<tr><td class='form'>Svar 1</td><td><input type='text' size=50 name='answer[]' /></td></tr>\n");
print("<tr><td class='form'>Svar 2</td><td><input type='text' size=50 name='answer[]' /></td></tr>\n");
print("</table>\n");
print("<br /><div class='errormess'></div>\n");
print("<input type='submit' value='Skapa' id='pollsubmit' />\n");
print("</form>\n");

foot();

?>