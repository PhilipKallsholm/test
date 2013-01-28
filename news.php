<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

if ($_POST)
{
	if ($_GET["add"])
	{
		$subject = trim($_POST["subject"]);
		$body = trim($_POST["body"]);
		$dt = get_date_time();
		
		if (!$subject)
			jErr("Du måste ange en rubrik");
			
		if (!$body)
			jErr("Du måste skriva något");
			
		mysql_query("INSERT INTO topics (name, forumid, added, descr) VALUES(" . sqlesc($subject) . ", 5, '$dt', 'Diskutera')") or sqlerr(__FILE__, __LINE__);
		$topicid = mysql_insert_id();
		
		mysql_query("INSERT INTO posts (body, topicid, added) VALUES(" . sqlesc($body) . ", $topicid, '$dt')") or sqlerr(__FILE__, __LINE__);
		$postid = mysql_insert_id();
		
		mysql_query("UPDATE topics SET lastpost = $postid WHERE id = $topicid") or sqlerr(__FILE__, __LINE__);
			
		mysql_query("INSERT INTO news (subject, body, added, userid, topicid) VALUES(" . implode(", ", array_map("sqlesc", array($subject, $body, $dt, $CURUSER["id"], $topicid))) . ")") or sqlerr(__FILE__, __LINE__);
		$newsid = mysql_insert_id();
		
		mysql_query("UPDATE users SET unreadnews = unreadnews + 1") or sqlerr(__FILE__, __LINE__);
		
		$return["id"] = $newsid;
		$return["news"] = "<div class='news' id='news$newsid' style='display: none;'>";
		$return["news"] .= "<h2 id='nh$newsid'>$subject<span class='newsedit'><a class='jlink' onClick='editNews($newsid)'><img src='edit.png' /></a> <a class='jlink' onClick='delNews($newsid)'><img src='delete.png' /></a></span></h2>";
		$return["news"] .= "<div id='nb$newsid'>" . format_comment($body) . "</div>";
		$return["news"] .= "<span class='newsfoot'>" . elapsed_time($dt) . "</span>";
		$return["news"] .= "<span class='newsfoot' style='float: right;'><a href='/forums.php/viewtopic/$topicid/'>Diskutera (0)</a></span>\n";
		$return["news"] .= "</div>";
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["edit"])
	{
		$id = 0 + $_POST["id"];
		
		$res = mysql_query("SELECT * FROM news WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		if ($arr = mysql_fetch_assoc($res))
		{
			$return["head"] = "<input type='text' class='newssubject newssubjectactive' id='newssubject$arr[id]' value='$arr[subject]' />";
			$return["body"] = "<textarea class='newsbody' id='newsbody$arr[id]' style='display: block;'>$arr[body]</textarea>";
			$return["body"] .= "<input type='button' id='newsupdate$id' value='Uppdatera' /><span class='errormess' id='newserr$id' style='margin-left: 10px;'></span>";
		}
		else
			jErr("Nyheten finns inte");
			
		print(json_encode($return));
		die;
	}
	
	if ($_GET["takeedit"])
	{
		$id = 0 + $_POST["id"];
		$subject = trim($_POST["subject"]);
		$body = trim($_POST["body"]);
		
		if (!$subject)
			jErr("Du måste ange en rubrik");
		
		if (!$body)
			jErr("Du måste skriva något");
			
		$res = mysql_query("SELECT id, topicid FROM news WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		if ($arr = mysql_fetch_assoc($res))
		{	
			mysql_query("UPDATE news SET subject = " . sqlesc($subject) . ", body = " . sqlesc($body) . " WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
			mysql_query("UPDATE topics SET name = " . sqlesc($subject) . " WHERE id = $arr[topicid]") or sqlerr(__FILE__, __LINE__);
			
			$post = mysql_query("SELECT MIN(id) AS id FROM posts WHERE topicid = $arr[topicid]") or sqlerr(__FILE__, __LINE__);
			$post = mysql_fetch_assoc($post);
			
			mysql_query("UPDATE posts SET body = " . sqlesc($body) . " WHERE id = $post[id]") or sqlerr(__FILE__, __LINE__);
		}
		else
			jErr("Nyheten finns inte");
			
		$return["head"] = $subject;
		$return["body"] = format_comment($body);
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["del"])
	{
		$id = 0 + $_POST["id"];
		
		$res = mysql_query("SELECT * FROM news WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		if ($arr = mysql_fetch_assoc($res))
		{
			mysql_query("DELETE FROM news WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			deletetopic($arr["topicid"]);
		}
		else
			jErr("Nyheten finns inte");
			
		print(json_encode(""));
	}
}
?>