<?php

require_once("globals.php");

dbconn();
loggedinorreturn();
parked();

function forumhead($path = array(), $info = array()) {
	print("<div id='topichead'>\n");
	print("<span id='head'>" . implode(" <span class='sar'>&nbsp;</span> ", $path) . "</span>\n");
	
	if ($info)
		print("<span id='info'>" . implode("<span class='spacer'>&#8226;</span>", $info) . "</span>\n");
		
	print("</div>\n");
}

function newPost($topicid, $lastpost) {
	global $CURUSER;
	
	if (!$topicid)
		return false;
			
	$readpost = mysql_query("SELECT postid FROM readposts WHERE userid = $CURUSER[id] AND topicid = $topicid") or sqlerr(__FILE__, __LINE__);
	$readpost = mysql_fetch_row($readpost);
		
	if (!$readpost || $lastpost > $readpost[0])
		return true;
	else
		return false;
}

function get_forum_pic($arr) {
	return (newPost($arr["id"], $arr["lastpost"]) ? "<img src='/pic/unread" . ($arr["locked"] == 'yes' ? "locked" : "") . ".png' title='Olästa inlägg' />" : "<img src='/pic/read" . ($arr["locked"] == 'yes' ? "locked" : "") . ".png' title='Inga olästa inlägg' />");
}

function showPost($arr) {
	global $CURUSER;
	
	if (!$arr["userid"])
		$username = "<i id='u$arr[id]'>System</i>";
	else
	{
		$users = mysql_query("SELECT id, username, avatar, bad_avatar, class, added, title, donor, crown, warned, warned_reason, forumsign FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

		if ($user = mysql_fetch_assoc($users))
			$username = "<a href='/userdetails.php?id=$arr[userid]' id='u$arr[id]'>$user[username]</a>" . usericons($user["id"]);
		else
			$username = "<i id='u$arr[id]'>Borttagen</i>";
	}
			
	if ($arr["editedby"])
	{
		$edited = mysql_query("SELECT username FROM users WHERE id = $arr[editedby]") or sqlerr(__FILE__, __LINE__);
			
		if ($editor = mysql_fetch_assoc($edited))
			$editor = "<a href='/userdetails.php?id=$arr[editedby]'>$editor[username]</a>";
		else
			$editor = "<i>Borttagen</i>";
	}
	else
		$editor = "<i>System</i>";
	
	$guarded = mysql_query("SELECT id FROM guarded WHERE userid = $CURUSER[id] AND topicid = $arr[topicid] AND posterid = $arr[userid]") or sqlerr(__FILE__, __LINE__);
		
	$return = "<div class='" . ($arr["i"] % 2 == 0 ? "post1" : "post1") . "' id='pb$arr[id]'>\n";
	$return .= "<span class='postinfo'><a id='p$arr[id]' href='#p$arr[id]'>#$arr[id]</a> " . postTime($arr["added"]) . " av $username (" . get_elapsed_time($arr["added"]) . " sedan)</span><span class='postman'>" . ($CURUSER["id"] == $arr["userid"] || get_user_class() >= UC_MODERATOR ? "<a class='jlink' onClick=\"editPost($arr[id])\">Ändra</a>" : "") . (get_user_class() >= UC_MODERATOR ? " / <a class='jlink' onClick=\"delPost($arr[id])\">Radera</a>" : "") . "<img src='/pic/pil.png' class='top' /><img src='/pic/pilned.png' class='bottom' /></span>\n";
	$return .= "<div class='postbody'><div class='avatar'>" . ($user ? "<div><a class='jlink' onClick='sendMess($user[id])'>Skicka PM</a></div><div><b>" . ($user["title"] ? $user["title"] : get_user_class_name($user["class"])) . "</b><br />Medlem i " . get_elapsed_time($user["added"]) . "</div>" : "") . "<img src='" . ($user["avatar"] && ($CURUSER["show_avatars"] == 'yes' || $CURUSER["show_avatars"] == 'notbad' && $user["bad_avatar"] == 'no') ? $user["avatar"] : "/pic/default_avatar.jpg") . "' class='transbor' style='width: 150px;' /></div><div class='errormess' id='errormess$arr[id]'></div><div class='posttext' id='posttext$arr[id]'>" . format_comment($arr["body"]) . ($arr["lastedit"] <> '0000-00-00 00:00:00' ? "<br /><br /><font style='font-size: 7pt;'>Senast ändrad $arr[lastedit] (" . get_elapsed_time($arr["lastedit"]) . " sedan) av $editor</font>" : "");
	
	if ($user["forumsign"] && $CURUSER["showforumsign"] == 'yes')
		$return .= "<div class='forumsign'><span class='bar' style='margin-right: 10px;'>&nbsp;</span>" . format_comment($user["forumsign"]) . "</div>\n";
	
	$return .= "</div><input type='hidden' value='" . htmlent($arr["body"]) . "' id='pe$arr[id]' /><div class='underpost'>";
	
	if ($arr["userid"] && $arr["userid"] != $CURUSER["id"])
		$return .= "<span id='guard$arr[userid]$arr[id]' style='float: left;'><a class='jlink thin' onClick='guard($arr[topicid], $arr[userid])'>" . (mysql_num_rows($guarded) < 1 ? "Bevaka användare" : "Sluta bevaka användare") . "</a></span>";
		
	$return .= "<img src='/pic/comments.png' /> <a class='jlink' onClick=\"Quote($arr[id])\">Citera</a> / <a class='jlink' onClick=\"report($arr[id], 'post')\">Rapportera</a></div></div>\n";
	$return .= "</div>\n";
	
	return $return;
}

function lastpost($t) {
	$ti = strtotime($t);
	$sec = time() - $ti;

	if ($sec < 60)
		return "$sec sekunder sedan";
	elseif ($sec < 60 * 60)
		return round($sec / 60, 0) . " minuter sedan";
	elseif ($sec < 60 * 60 * 24)
		return round($sec / (60 * 60), 0) . " timmar sedan";
	else
		return $t;
}

function searchpager($pages, $page) {
	if ($page != 1)
		$pager .= "<a class='jlink' style='font-weight: normal;' onClick=\"forumsearch(" . ($page - 1) . ")\"><span class='sal'></span></a>";
	
	for ($i = 1; $i <= $pages; $i++)
	{
		if ($i != 1 && $i != $pages && ($i - $page > 2 || $page - $i > 2) && $i != $page - 10 && $i != $page - 100 && $i != $page + 10 && $i != $page + 100)
		{
			if (!$spacer)
				$pager .= " - ";
				
			$spacer = True;
		} else {
			$pager .= "<span class='pager'>" . ($i == $page ? "<b>$i</b>" : "<a class='jlink' style='font-weight: normal;' onClick=\"forumsearch($i)\">$i</a>") . "</span>";
			
			$spacer = False;
		}
	}
	
	if ($page != $pages)
		$pager .= "<a class='jlink' style='font-weight: normal;' onClick=\"forumsearch(" . ($page + 1) . ")\"><span class='sar'></span></a>";
		
	return $pager;
}

if ($_POST)
{
	if ($_GET["post"])
	{
		$topicid = 0 + $_POST["topicid"];
		$body = trim($_POST["post"]);
		$body = preg_replace("#\]\s+\[quote=#i", "][quote=", $body);
		$dt = get_date_time();
		
		$topic = mysql_query("SELECT id, name, forumid, locked, lastpost FROM topics WHERE id = " . sqlesc($topicid)) or sqlerr(__FILE__, __LINE__);
		$topic = mysql_fetch_assoc($topic);
		
		if ($CURUSER["forumrights"] == 'no')
			jErr("Dina forumrättigheter har blivit inaktiverade");
		
		if (!$topic)
			jErr("Tråden finns inte");
		
		if ($topic["locked"] == 'yes')
			jErr("Tråden är låst");
		
		if (!$body)
			jErr("Du måste skriva något");
			
		$bans = mysql_query("SELECT word FROM bannedwords") or sqlerr(__FILE__, __LINE__);
		
		while ($ban = mysql_fetch_assoc($bans))
			if (stripos($body, $ban["word"]) !== false)
			{
				mysql_query("INSERT INTO bannedwordslog (userid, topicid, added, body) VALUES($CURUSER[id], $topicid, '" . get_date_time() . "', " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
				
				jErr("Texten innehåller ett otillåtet ord");
			}
		
		$lastpost = mysql_query("SELECT userid FROM posts WHERE id = $topic[lastpost]") or sqlerr(__FILE__, __LINE__);
		$lastpost = mysql_fetch_assoc($lastpost);
		
		if ($lastpost["userid"] == $CURUSER["id"] && get_user_class() < UC_MODERATOR)
			jErr("<span class='err'>Multipla poster ej tillåtet</span>");

		mysql_query("INSERT INTO posts (body, topicid, userid, added, body_orig) VALUES(" . implode(", ", array_map("sqlesc", array($body, $topicid, $CURUSER["id"], $dt, $body))) . ")") or sqlerr(__FILE__, __LINE__);
		$postid = mysql_insert_id();
		
		mysql_query("UPDATE topics SET lastpost = $postid WHERE id = $topic[id]") or sqlerr(__FILE__, __LINE__);
		
		if (preg_match_all("#\[quote=([^\]]+)#i", $body, $matches, PREG_OFFSET_CAPTURE))
		{
			$i = 0;
			foreach ($matches[1] AS $match)
			{
				$end = $match[1] - 7;
			
				$openings = substr_count(strtolower(substr($body, 0, $end)), "[quote=");
				$endings = substr_count(strtolower(substr($body, 0, $end)), "[/quote]");
				
				if ($openings != $endings)
					continue;
			
				$username = $match[0];

				$quoted = mysql_query("SELECT id, notifo FROM users WHERE username = " . sqlesc($username)) or sqlerr(__FILE__, __LINE__);
			
				if ($quoted = mysql_fetch_assoc($quoted))
				{
					if ($CURUSER["id"] == $quoted["id"])
						continue;
				
					$subject = "Du har blivit citerad";
					$messbody = "[url=userdetails.php?id=" . $CURUSER["id"] . "][b]" . $CURUSER["username"] . "[/b][/url] har citerat dig i tråden [b]" . $topic["name"] . "[/b].\n\nKlicka [url=forums.php/viewtopic/" . $topic["id"] . "/" . findPage($postid) . "?p" . $postid . "]här[/url] för att komma till inlägget.";
			
					$oldmess = mysql_query("SELECT id, added, body FROM messages WHERE receiver = $quoted[id] AND sender = 0 AND subject = '$subject'") or sqlerr(__FILE__, __LINE__);
				
					if ($oldmess = mysql_fetch_assoc($oldmess))
					{
						$messbody = $messbody . "\n\n{0@$oldmess[added]}\n" . $oldmess["body"];
					
						mysql_query("UPDATE messages SET `read` = 0, added = '$dt', body = " . sqlesc($messbody) . ", location = 1 WHERE id = $oldmess[id]") or sqlerr(__FILE__, __LINE__);
					}
					else
						mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($quoted["id"], $dt, $subject, $messbody))) . ")") or sqlerr(__FILE__, __LINE__);
			
					if ($quoted["notifo"])
						notifo_send($quoted["notifo"], $subject, $CURUSER["username"] . " har citerat dig i tråden: " . $topic["name"], "forums.php/viewtopic/" . $topic["id"] . "/" . findPage($postid) . "?p" . $postid);
				}
				
				$i++;
			}
		}
		
		$guarded = mysql_query("SELECT guarded.userid, guarded.posterid, guarded.pm, users.notifo FROM guarded LEFT JOIN users ON guarded.userid = users.id WHERE topicid = " . sqlesc($topicid) . " AND userid != $CURUSER[id] GROUP BY userid") or sqlerr(__FILE__, __LINE__);
		
		while ($guard = mysql_fetch_assoc($guarded))
		{
			$unreads = mysql_query("SELECT COUNT(posts.id) FROM posts INNER JOIN readposts ON posts.topicid = readposts.topicid WHERE readposts.userid = $guard[userid] AND readposts.topicid = " . sqlesc($topicid) . " AND posts.id > readposts.postid" . ($guard["posterid"] ? " AND posts.userid = $guard[posterid]" : "")) or sqlerr(__FILE__, __LINE__);
			$unreads = mysql_fetch_row($unreads);
			
			if ((!$guard["posterid"] || $guard["posterid"] == $CURUSER["id"]) && ($guard["pm"] == 'yes' || $guard["pm"] == 'once' && $unreads[0] < 2))
			{
				$dt = get_date_time();
				$subject = "Nytt inlägg i tråd du bevakar";
				$body = "[url=userdetails.php?id=" . $CURUSER["id"] . "][b]" . $CURUSER["username"] . "[/b][/url] har skrivit ett inlägg i tråden [b]" . $topic["name"] . "[/b] som du bevakar.\n\nKlicka [url=forums.php/viewtopic/" . $topic["id"] . "/" . findPage($postid) . "?p" . $postid . "]här[/url] för att komma till inlägget.";
				
				$oldmess = mysql_query("SELECT id, added, body FROM messages WHERE receiver = $guard[userid] AND sender = 0 AND subject = '$subject'") or sqlerr(__FILE__, __LINE__);
				
				if ($oldmess = mysql_fetch_assoc($oldmess))
				{
					$body = $body . "\n\n{0@$oldmess[added]}\n" . $oldmess["body"];
					
					mysql_query("UPDATE messages SET `read` = 0, added = '$dt', body = " . sqlesc($body) . ", location = 1 WHERE id = $oldmess[id]") or sqlerr(__FILE__, __LINE__);
				}
				else
					mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($guard["userid"], $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
				
				if ($guard["notifo"])
					notifo_send($guard["notifo"], $subject, $CURUSER["username"] . " har skrivit ett inlägg i tråden: " . $topic["name"], "forums.php/viewtopic/" . $topic["id"] . "/" . findPage($postid) . "?p" . $postid);
			}
		}
		
		if ($topic["forumid"] != 24)
			mysql_query("UPDATE users SET posts_week = posts_week + 1 WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
		
		$arr["id"] = $postid;
		$arr["body"] = $body;
		$arr["userid"] = $CURUSER["id"];
		$arr["added"] = $dt;
		$arr["lastedit"] = "0000-00-00 00:00:00";
		$arr["topicid"] = $topic["id"];
		
		$return["post"] = showPost($arr);
		$return["pid"] = "p" . $postid;

		print(json_encode($return));
		die;
	}

	if ($_GET["review"])
	{
		print(format_comment($_POST["body"] . $_POST["post"]));
		die;
	}

	if ($_GET["edit"])
	{
		$id = sqlesc($_POST["id"]);
		$body = trim($_POST["body"]);
		$dt = get_date_time();

		$post = mysql_fetch_assoc(mysql_query("SELECT userid, topicid FROM posts WHERE id = $id"));

		if ($post["userid"] != $CURUSER["id"] && get_user_class() < UC_MODERATOR)
			jErr("Inlägget är inte ditt");
		
		if (!$body)
			jErr("Du måste skriva något");
			
		$bans = mysql_query("SELECT word FROM bannedwords") or sqlerr(__FILE__, __LINE__);
		
		while ($ban = mysql_fetch_assoc($bans))
			if (stripos($body, $ban["word"]) !== false)
			{
				mysql_query("INSERT INTO bannedwordslog (userid, topicid, added, body) VALUES($CURUSER[id], $post[topicid], '" . get_date_time() . "', " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
				
				jErr("Texten innehåller ett otillåtet ord");
			}

		mysql_query("UPDATE posts SET body = " . sqlesc($body) . ", lastedit = '$dt', editedby = $CURUSER[id] WHERE id = $id") or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);

		$return["post"] = format_comment($body) . "<br /><br /><font style='font-size: 7pt;'>Senast ändrad $dt (" . get_elapsed_time($dt) . " sedan) av <a href='/userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a></font>";
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["delete"])
	{
		$id = 0 + $_POST["id"];

		if (get_user_class() < UC_MODERATOR)
			jErr("Du har inte behörighet att radera inlägget");
		
		$topic = mysql_query("SELECT topics.id, topics.forumid, topics.lastpost, posts.userid FROM topics LEFT JOIN posts ON topics.id = posts.topicid WHERE posts.id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		$topic = mysql_fetch_assoc($topic);
		
		$posts = mysql_query("SELECT COUNT(*) FROM posts WHERE topicid = $topic[id]") or sqlerr(__FILE__, __LINE__);
		$posts = mysql_fetch_row($posts);
		
		if ($posts[0] < 2)
			jErr("Trådens enda inlägg");
			
		if ($id == $topic["lastpost"])
		{
			$lastpost = mysql_query("SELECT id FROM posts WHERE topicid = $topic[id] AND id < $topic[lastpost] ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
			$lastpost = mysql_fetch_assoc($lastpost);
			
			mysql_query("UPDATE topics SET lastpost = $lastpost[id] WHERE id = $topic[id]") or sqlerr(__FILE__, __LINE__);
		}

		mysql_query("DELETE FROM posts WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		if ($topic["forumid"] != 24)
			mysql_query("UPDATE users SET posts_week = posts_week - 1 WHERE id = $topic[userid]") or sqlerr(__FILE__, __LINE__);
		
		print(json_encode(""));
		die;
	}
	
	if ($_GET["edittopic"])
	{
		$locked = $_POST["locked"];
		$sticky = $_POST["sticky"];
		$topic = $_POST["topic"];
		$descr = $_POST["descr"];
		$topicid = 0 + $_POST["topicid"];
		$forumid = 0 + $_POST["overforum"];
		$minclasswrite = 0 + $_POST["minclasswrite"];
		
		if (get_user_class() < UC_MODERATOR)
			jErr("Du har inte behörighet att ändra tråden");
			
		if (!$topic)
			jErr("Du måste ange ett namn");
			
		if (!$topicid)
			jErr("Ogiltigt tråd-ID");
			
		if (!$forumid)
			jErr("Ogiltigt forum-ID");
			
		mysql_query("UPDATE topics SET name = " . sqlesc($topic) . ", descr = " . sqlesc($descr) . ", locked = " . sqlesc($locked) . ", sticky = " . sqlesc($sticky) . ", forumid = " . sqlesc($forumid) . ", minclasswrite = " . sqlesc($minclasswrite) . " WHERE id = " . sqlesc($topicid)) or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);
		
		print(json_encode(""));	
		die;
	}
	
	if ($_GET["createtopic"])
	{
		$name = trim($_POST["name"]);
		$descr = trim($_POST["descr"]);
		$body = trim($_POST["body"]);
		$forumid = $_POST["forumid"];
		$dt = get_date_time();
		
		if (!$name)
		{
			$return["errfield"] = 1;
			jErr("Du måste ange ett ämne");
		}
		
		if (!$body)
		{
			$return["errfield"] = 3;
			jErr("Du måste skriva något");
		}
		
		$bans = mysql_query("SELECT word FROM bannedwords") or sqlerr(__FILE__, __LINE__);
		
		while ($ban = mysql_fetch_assoc($bans))
			if (stripos($name . $descr . $body, $ban["word"]) !== false)
			{
				mysql_query("INSERT INTO bannedwordslog (userid, added, body) VALUES($CURUSER[id], '" . get_date_time() . "', " . sqlesc($body) . ")") or sqlerr(__FILE__, __LINE__);
				
				jErr("Din rubrik, beskrivning eller text innehåller ett otillåtet ord");
			}
		
		mysql_query("INSERT INTO topics (name, forumid, userid, added, descr) VALUES(" . implode(", ", array_map("sqlesc", array($name, $forumid, $CURUSER["id"], $dt, $descr))) . ")") or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);
		
		$topicid = mysql_insert_id();
		
		mysql_query("INSERT INTO posts (body, topicid, userid, added) VALUES(" . implode(", ", array_map("sqlesc", array($body, $topicid, $CURUSER["id"], $dt))) . ")") or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);
		$postid = mysql_insert_id();
		
		mysql_query("UPDATE topics SET lastpost = $postid WHERE id = $topicid") or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);
		
		print(json_encode(""));
		die;
	}
	
	if ($_GET["deltopic"])
	{
		$id = 0 + $_POST["id"];
		
		if (!is_numeric($id))
			jErr("Ogiltigt ID");
			
		if (get_user_class() < UC_MODERATOR)
			jErr("Du har inte behörighet att radera tråden");
			
		$return = mysql_fetch_assoc(mysql_query("SELECT forumid FROM topics WHERE id = $id"));
		
		deletetopic($id);
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["search"])
	{
		$search = trim($_POST["search"]);
		$section = $_POST["section"];
		$forums = $_POST["forums"];
		$page = 0 + $_GET["page"];
		$perpage = 25;
		
		if ($forums)
			foreach ($forums AS $forum)
				if (is_numeric($forum))
					$forumsearch[] = $forum;
				
		if ($section == 'posts')
		{
			$wherea[] = "posts.body LIKE '%" . mysql_real_escape_string($search) . "%'";
			
			if ($forumsearch)
				$wherea[] = "overforums.id IN (" . implode(", ", array_map("sqlesc", $forumsearch)) . ")";
				
			$wherea[] = "overforums.minclassread <= $CURUSER[class]";
				
			$results = mysql_query("SELECT posts.id FROM posts LEFT JOIN topics ON posts.topicid = topics.id LEFT JOIN overforums ON topics.forumid = overforums.id WHERE " . implode(" AND ", $wherea)) or sqlerr(__FILE__, __LINE__);
			$results = mysql_num_rows($results);
			
			if (!$results)
			{
				print("<i>Inga träffar hittades</i>");
				die;
			}

			$pages = ceil($results / $perpage);
			
			if ($page < 1)
				$page = 1;
			elseif ($page > $pages)
				$page = $pages;
				
			$searchpager = searchpager($pages, $page);
				
			$begin = ($page - 1) * $perpage;
			$end = ($begin + $perpage < $results ? $begin + $perpage : $results);
			
			function results() {
				global $begin, $end, $results;
			
				return "<span style='font-size: 7pt;'>Visar " . ($begin + 1) . " till $end av $results träffar</span>";
			}
			
			print($searchpager);
			print("<br /><br />" . results() . "\n");
			
			print("<br /><br /><table><tr><td class='colhead'>Inlägg</td></tr>\n");
				
			$res = mysql_query("SELECT posts.id, posts.body, posts.userid, posts.added, topics.id AS topicid, topics.name, topics.descr, topics.sticky, overforums.id AS forumid, overforums.name AS forumname FROM posts LEFT JOIN topics ON posts.topicid = topics.id LEFT JOIN overforums ON topics.forumid = overforums.id WHERE " . implode(" AND ", $wherea) . " ORDER BY posts.added DESC LIMIT $begin, $perpage") or sqlerr(__FILE__, __LINE__);
		
			while ($arr = mysql_fetch_assoc($res))
			{
				$posts = mysql_query("SELECT id FROM posts WHERE topicid = $arr[topicid]") or sqlerr(__FILE__, __LINE__);
				$posts = mysql_num_rows($posts);
				
				$perpage = $CURUSER["postsperpage"];
	
				if (!$perpage)
					$perpage = 25;
				elseif ($perpage > 50)
					$perpage = 50;

				$topicpages = ceil($posts / $perpage);
				
				if ($arr["userid"])
				{
					$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

					if ($user = mysql_fetch_assoc($user))
						$username = "<a href='/userdetails.php?id=$arr[userid]'>$user[username]</a>";
					else
						$username = "<i>Borttagen</i>";
				}
				else
					$username = "<i>System</i>";
			
				if ($topicpages > 1)
				{
					$page = "<img src='/pic/multipage.gif' /> ";
					
					list($pager, $limit) = pager("/forums.php/viewtopic/$arr[topicid]/", $posts, $perpage, 0, false);
		
					$page .= $pager;
				}
				
				$start = max(0, stripos($arr["body"], $search) - 250);
				
				if ($start)
					$arr["body"] = "... " . substr($arr["body"], $start);
				
				$body = preg_replace("#($search)#i", "[b]$1[/b]", $arr["body"]);
				$body = format_comment(cutStr($body, 500));
			
				print("<tr><td align='left'><span class='postinfo'><a href='/forums.php/viewtopic/$arr[topicid]/" . findPage($arr["id"]) . "?p$arr[id]'>#$arr[id]</a> " . postTime($arr["added"]) . " av $username (" . get_elapsed_time($arr["added"]) . " sedan)</span><span class='postman'>Tråd: <a href='/forums.php/viewtopic/$arr[topicid]/'>" . cutStr($arr["name"], 31) . "</a>" . ($topicpages > 1 ? " ($page)" : "") . " (<a href='/forums.php/viewforum/$arr[forumid]/'>$arr[forumname]</a>)</span><div class='searchpost'>$body</div></td></tr>\n");
				print("<tr class='clear'><td style='padding: 10px;'></td></tr>\n");
			}
		
			print("</table>");
			print(results() . "\n");
			print("<br /><br />$searchpager");
		}
		else
		{
			$wherea[] = "topics.name LIKE '%" . mysql_real_escape_string($search) . "%'";
			
			if ($forumsearch)
				$wherea[] = "topics.forumid IN (" . implode(", ", array_map("sqlesc", $forumsearch)) . ")";
				
			$wherea[] = "overforums.minclassread <= $CURUSER[class]";
				
			$results = mysql_query("SELECT topics.id FROM topics LEFT JOIN overforums ON topics.forumid = overforums.id WHERE " . implode(" AND ", $wherea)) or sqlerr(__FILE__, __LINE__);
			$results = mysql_num_rows($results);
			
			if (!$results)
			{
				print("<i>Inga träffar hittades</i>");
				die;
			}

			$pages = ceil($results / $perpage);
			
			if ($page < 0)
				$page = 1;
			elseif ($page > $pages)
				$page = $pages;
				
			$searchpager = searchpager($pages, $page);
				
			$begin = ($page - 1) * $perpage;
			$end = ($begin + $perpage < $results ? $begin + $perpage : $results);
			
			function results() {
				global $begin, $end, $results;
			
				return "<span style='font-size: 7pt;'>Visar " . ($begin + 1) . " till $end av $results träffar</span>";
			}
			
			print($searchpager);
			print("<br /><br />" . results() . "\n");
			
			print("<br /><br /><table>\n");
			print("<tr><td class='colhead' colspan=2>Ämne</td><td class='colhead'>Svar</td><td class='colhead'>Visningar</td><td class='colhead'>Skapare</td><td class='colhead'>Senaste inlägg</td></tr>");
				
			$res = mysql_query("SELECT topics.*, overforums.name AS forumname FROM topics LEFT JOIN overforums ON topics.forumid = overforums.id WHERE " . implode(" AND ", $wherea) . " ORDER BY added DESC") or sqlerr(__FILE__, __LINE__);
			
			while ($arr = mysql_fetch_assoc($res))
			{
				$posts = mysql_query("SELECT id FROM posts WHERE topicid = $arr[id]") or sqlerr(__FILE__, __LINE__);
				$posts = mysql_num_rows($posts);
				
				$topicpages = ceil($posts[0] / $perpage);
				
				if ($arr["userid"])
				{
					$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

					if ($user = mysql_fetch_assoc($user))
						$username = "<a href='/userdetails.php?id=$arr[userid]'>$user[username]</a>";
					else
						$username = "<i>Borttagen</i>";
				}
				else
					$username = "<i>System</i>";
			
				if ($topicpages > 1)
				{
					$page = "<img src='/pic/multipage.gif' />";
					
					list($pager, $limit) = pager("/forums.php/viewtopic/$arr[id]/", $posts[0], $perpage, 0, false);
		
					$page .= $pager;
				}

				$lastpost = mysql_query("SELECT userid, added FROM posts WHERE id = $arr[lastpost]") or sqlerr(__FILE__, __LINE__);
				$lastpost = mysql_fetch_assoc($lastpost);
				
				if ($lastpost["userid"])
				{
					$lastuser = mysql_query("SELECT username FROM users WHERE id = '$lastpost[userid]'") or sqlerr(__FILE__, __LINE__);

					if ($last = mysql_fetch_assoc($lastuser))
						$lastuser = "<a href='/userdetails.php?id=$lastpost[userid]'>$last[username]</a>";
					else
						$lastuser = "<i>Borttagen</i>";
				}
				else
					$lastuser = "<i>System</i>";

				print("<tr><td style='border-right: 0px; padding-right: 0px;'>" . get_forum_pic($arr) . "</td><td style='border-left: 0px;'>" . ($arr["sticky"] == 'yes' ? "Klistrad: " : "") . "<a href='/forums.php/viewtopic/$arr[id]/'>" . cutStr($arr["name"], 31) . "</a>" . ($topicpages > 1 ? " ($pager)" : "") . ($arr[descr] ? "<br /><font style='font-size: 7pt;'>- $arr[descr]</font>" : "") . "</td><td>" . number_format($posts - 1) . "</td><td>" . number_format($arr["hits"]) . "</td><td>$username</td><td>" . get_elapsed_time($lastpost["added"]) . " sedan<br />av $lastuser</td></tr>\n");
			}
			print("</table>\n");
			print("<br />" . results() . "\n");
			print("<br /><br />$searchpager\n");
		}
		die;
	}
	
	if ($_GET["guard"])
	{
		$topicid = 0 + $_POST["topicid"];
		$posterid = 0 + $_POST["posterid"];
		
		$return["starttopic"] = "Bevaka tråd";
		$return["stoptopic"] = "Sluta bevaka tråd";
		$return["startuser"] = "Bevaka användare";
		$return["stopuser"] = "Sluta bevaka användare";
		
		$res = mysql_query("SELECT id FROM guarded WHERE userid = $CURUSER[id] AND topicid = " . sqlesc($topicid) . " AND posterid = " . sqlesc($posterid)) or sqlerr(__FILE__, __LINE__);
		
		if ($arr = mysql_fetch_assoc($res))
		{
			mysql_query("DELETE FROM guarded WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			
			if ($posterid)
				$return["type"] = "stopuser";
			else
				$return["type"] = "stoptopic";
		}
		else
		{
			if ($posterid)
			{
				$guardtop = mysql_query("SELECT id FROM guarded WHERE userid = $CURUSER[id] AND topicid = " . sqlesc($topicid) . " AND posterid = 0") or sqlerr(__FILE__, __LINE__);
			
				if ($guardtop = mysql_fetch_assoc($guardtop))
				{
					mysql_query("UPDATE guarded SET posterid = " . sqlesc($posterid) . " WHERE id = $guardtop[id]") or sqlerr(__FILE__, __LINE__);
					$return["type"] = "topictouser";
				}
				else
				{
					mysql_query("INSERT INTO guarded (topicid, userid, posterid) VALUES(" . implode(", ", array_map("sqlesc", array($topicid, $CURUSER["id"], $posterid))) . ")") or sqlerr(__FILE__, __LINE__);
					$return["type"] = "startuser";
				}
			}
			else
			{
				$guards = mysql_query("SELECT id FROM guarded WHERE userid = $CURUSER[id] AND topicid = " . sqlesc($topicid) . " AND posterid != 0") or sqlerr(__FILE__, __LINE__);
				
				if ($guards = mysql_fetch_assoc($guards))
				{
					mysql_query("DELETE FROM guarded WHERE userid = $CURUSER[id] AND topicid = " . sqlesc($topicid)) or sqlerr(__FILE__, __LINE__);
					$return["type"] = "usertotopic";
				}
				else
					$return["type"] = "starttopic";
				
				mysql_query("INSERT INTO guarded (topicid, userid, posterid) VALUES(" . implode(", ", array_map("sqlesc", array($topicid, $CURUSER["id"], $posterid))) . ")") or sqlerr(__FILE__, __LINE__);
			}
		}
		
		print(json_encode($return));
		die;
	}
	
	if ($_GET["takeguarded"])
	{
		$pm = $_POST["pm"];
		$del = $_POST["del"];
		
		foreach ($pm AS $id => $val)
			mysql_query("UPDATE guarded SET pm = " . sqlesc($val) . " WHERE id = " . sqlesc($id) . " AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
			
		foreach ($del AS $id)
			mysql_query("DELETE FROM guarded WHERE id = " . sqlesc($id) . " AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
		
		print("Uppdaterat!");
		die;
	}
}

$path = explode("/", $_SERVER["PATH_INFO"]);
$action = $path[1];

if ($action == 'createtopic')
{
	$forumid = $path[2];
	$forums = mysql_query("SELECT overforums.name AS overforum, overforums.id AS forumid, overforums.minclasswrite, forums.name AS forum FROM overforums LEFT JOIN forums ON overforums.forumid = forums.id WHERE overforums.id = " . sqlesc($forumid)) or sqlerr(__FILE__, __LINE__);
	$forum = mysql_fetch_assoc($forums);
	
	if (get_user_class() < $forum["minclasswrite"])
		stderr("Fel", "Tillgång nekad");
	
	head("Forum - Skapa tråd");
	
	$forumpath = array("<a href='/forums.php'>Forum</a>", $forum["forum"], "<a href='/forums.php/viewforum/$forum[forumid]/'>$forum[overforum]</a>", "Skapa tråd");
	
	forumhead($forumpath);
	
	print("<div class='errormess'></div>\n");
	
	print("<form method='post' action='/forums.php?createtopic=1' id='topicform'>\n");
	print("<input type='hidden' name='forumid' value=$forumid />\n");
	print("<table cellpadding=5>\n");
	print("<col style='width: 160px;' />\n");
	print("<tr><td class='form'>Ämne:</td><td><input type='text' name='name' maxlength=32 style='width: 400px;' /></td></tr>\n");
	print("<tr><td class='form'>Beskrivning:</td><td><input type='text' name='descr' maxlength=50 style='width: 400px;' /></td></tr>\n");
	print("<tr class='clear'><td colspan=2 style='width: 670px; padding: 5px 0px; text-align: right;'>\n");
	print("<div id='newpost'></div><div id='granskning'></div>\n");
	
	$btags = array("b" => array("b", format_comment("[b]Fet[/b] text") . "<br /><br /><span class=small>Ex. [b]Fet[/b]</span>"), "i" => array("i", format_comment("[i]Kursiv[/i] text") . "<br /><br /><span class=small>Ex. [i]Kursiv[/i]</span>"), "u" => array("u", format_comment("[u]Understruken[/u] text") . "<br /><br /><span class=small>Ex. [u]Understruken[/u]</span>"), "c" => array("c", format_comment("[c]Centrerad[/c] text") . "<br /><br /><span class=small>Ex. [c]Centrerad[/c]</span>"), "img" => array("img", "Bifoga originalstor bild<br />" . format_comment("[img]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/img]") . "<br /><br /><span class=small>Ex. [img]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/img]</span>"), "img=" => array("img2", "Bifoga originalstor bild<br />" . format_comment("[img=http://swepiracy.nu/images/2012/logo_orig_191420010.png]") . "<br /><br /><span class=small>Ex. [img=http://swepiracy.nu/images/2012/logo_orig_191420010.png]</span>"), "imgw" => array("imgw", "Bifoga storleksanpassad bild<br />" . format_comment("[imgw]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/imgw]") . "<br /><br /><span class=small>Ex. [imgw]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/imgw]</span>"), "url" => array("url", "Bifoga länk<br /><br />" . format_comment("[url]www.google.se[/url]") . "<br /><br /><span class=small>Ex. [url]www.google.se[/url]</span>"), "url=" => array("url2", "Bifoga " . format_comment("[url=www.google.se]länk[/url]") . "<br /><br /><span class=small>Ex. [url=www.google.se]Länk[/url]</span>"), "*" => array("list", "Bifoga lista<br /><br />" . format_comment("[*] Listpunkt") . "<br /><br /><span class=small>Ex. [*] Listpunkt</span>"), "spoiler" => array("spoiler", "Bifoga spoiler<br /><br />" . format_comment("[spoiler]Dold information[/spoiler]") . "<br /><br /><span class=small>Ex. [spoiler]Dold information[/spoiler]</span>"), "pre" => array("pre", format_comment("[pre]Formaterad[/pre] text") . "<br /><br /><span class=small>Ex. [pre]Formaterad[/pre]</span>"), "quote" => array("quote", "Bifoga citat<br /><br />" . format_comment("[quote]Citat[/quote]") . "<br /><br /><span class=small>Ex. [quote]Citat[/quote]</span>"), "quote=" => array("quote2", "Bifoga citat<br /><br />" . format_comment("[quote=Swepiracy]Citat[/quote]") . "<br /><br /><span class=small>Ex. [quote=Swepiracy]Citat[/quote]</span>"), "size=" => array("size", format_comment("[size=4]Storleksanpassad[/size] text") . "<br /><br /><span class=small>Ex. [size=5]Storleksanpassad[/size]</span>"), "font=" => array("font", format_comment("[font=courier]Teckensnittsanpassad[/font] text") . "<br /><br /><span class=small>Ex. [font=courier]Teckensnittsanpassad[/font]</span>"), "color=" => array("color", format_comment("[color=red]Färgad[/color] text") . "<br /><br /><span class=small>Ex. [color=red]Färgad[/color]</span>"));
	
	while (list($b, list($id, $descr)) = each($btags))
		$buttons .= "<input type='button' class='btag' alt='$id' value='[$b]'" . ($b != '*' ? " onClick=\"BB('$b')\"" : "") . " /><div id='$id' class='btaginfo'>$descr</div>";
	
	print("<div id='btags'>$buttons</div>\n");
	
	foreach ($smilies AS $key => $url)
		$smil .= "<img class='smilie' title='" . htmlent($key) . "' src='/pic/smilies/$url' />";
	
	print("<div id='smilies' class='smilactive' style='clear: both;'>$smil</div><textarea id='postfield' class='postactive' name='body' style='width: 500px;'></textarea><br /><input type='button' name='topicrev' id='review' value='Förhandsgranska' /> <input type='submit' id='createtopic' value='Skicka' />\n");
	print("</td></tr>\n");
	print("<tr class='clear'><td colspan=2></td></tr>\n");
	print("</table></form>\n");
	
	foot();
	die;
}

if ($action == 'viewforum')
{
	$forumid = 0 + $path[2];
	$page = 0 + $path[3];
	
	if (!$forumid)
		die("Ogiltigt forumid");

	$forum = mysql_query("SELECT overforums.name AS overforum, overforums.minclassread, overforums.minclasswrite, forums.name AS forum, COUNT(topics.id) AS topcount, SUM(topics.hits) AS hitcount FROM topics LEFT JOIN overforums ON topics.forumid = overforums.id LEFT JOIN forums ON overforums.forumid = forums.id WHERE overforums.id = " . sqlesc($forumid)) or sqlerr(__FILE__, __LINE__);
	$forum = mysql_fetch_assoc($forum);
	
	if (get_user_class() < $forum["minclassread"])
		stderr("Fel", "Tillgång nekad");
	
	$posts = mysql_query("SELECT COUNT(*) FROM posts LEFT JOIN topics ON posts.topicid = topics.id WHERE topics.forumid = " . sqlesc($forumid)) or sqlerr(__FILE__, __LINE__);
	$posts = mysql_fetch_row($posts);
	
	head("Forum - $forum[overforum]");
	
	$perpage = $CURUSER["topicsperpage"];
	
	if (!$perpage)
		$perpage = 25;
	elseif ($perpage > 50)
		$perpage = 50;
	
	list($forumpager, $limit) = pager("/forums.php/viewforum/$forumid/", $forum["topcount"], $perpage, $page);
	
	$headinfo[] = number_format($forum["topcount"]) . " trådar";
	$headinfo[] = number_format($posts[0]) . " inlägg";
	$headinfo[] = number_format($forum["hitcount"]) . " visningar";
	
	$forumpath = array("<a href='/forums.php'>Forum</a>", htmlspecialchars($forum["forum"]), htmlspecialchars($forum["overforum"]));
	
	forumhead($forumpath, $headinfo);
	
	print("<p>$forumpager</p>\n");
	
	print("<table cellpadding=5>\n");
	print("<tr><td class='colhead' colspan=2>Ämne</td><td class='colhead'>Svar</td><td class='colhead'>Visningar</td><td class='colhead'>Skapare</td><td class='colhead'>Senaste inlägg</td></tr>");

	$res = mysql_query("SELECT * FROM topics WHERE forumid = " . sqlesc($forumid) . " ORDER BY sticky, lastpost DESC $limit") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		$posts = mysql_query("SELECT COUNT(*) FROM posts WHERE topicid = $arr[id]") or sqlerr(__FILE__, __LINE__);
		$posts = mysql_fetch_row($posts);
		
		$perpage = $CURUSER["postsperpage"];
	
		if (!$perpage)
			$perpage = 25;
		elseif ($perpage > 50)
			$perpage = 50;
		
		$pages = ceil($posts[0] / $perpage);
		
		list($pager, $limit) = pager("/forums.php/viewtopic/$arr[id]/", $posts[0], $perpage, 0, false);
		
		if (!$arr["userid"])
			$username = "<i>System</i>";
		else
		{
			$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);

			if ($user = mysql_fetch_assoc($user))
				$username = "<a href='/userdetails.php?id=$arr[userid]'>$user[username]</a>";
			else
				$username = "<i>Borttagen</i>";
		}
		
		if ($pages > 1)
		{
			$page = "<img src='/pic/multipage.gif' /> ";
		
			$page .= $pager;
		}

		$lastpost = mysql_query("SELECT userid, added FROM posts WHERE id = $arr[lastpost] LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$lastpost = mysql_fetch_assoc($lastpost);
		
		if (!$lastpost["userid"])
			$lastuser = "<i>System</i>";
		else
		{
			$lastuser = mysql_query("SELECT username FROM users WHERE id = '$lastpost[userid]'") or sqlerr(__FILE__, __LINE__);

			if ($last = mysql_fetch_assoc($lastuser))
				$lastuser = "<a href='/userdetails.php?id=$lastpost[userid]'>$last[username]</a>";
			else
				$lastuser = "<i>Borttagen</i>";
		}

		print("<tr><td style='border-right: 0px; padding-right: 0px;'>" . get_forum_pic($arr) . "</td><td style='border-left: 0px;'>" . ($arr["sticky"] == 'yes' ? "Klistrad: " : "") . "<a href='/forums.php/viewtopic/$arr[id]/'>" . htmlspecialchars($arr["name"]) . "</a>" . ($pages > 1 ? " ($page)" : "") . ($arr["descr"] ? "<br /><font style='font-size: 7pt;'>" . htmlspecialchars($arr["descr"]) . "</font>" : "") . "</td><td>" . number_format($posts[0] - 1) . "</td><td>" . number_format($arr["hits"]) . "</td><td>$username</td><td align='left'>" . get_elapsed_time($lastpost["added"]) . " sedan<br />av $lastuser</td></tr>");
	}

	print("</table>");
	
	print("<p>$forumpager</p>\n");
	
	if (get_user_class() < $forum["minclasswrite"])
		print("<div style='margin-top: 10px; font-style: italic; color: gray;'>Enbart " . get_user_class_name($forum["minclasswrite"]) . " och högre kan skapa trådar i detta forum</div>\n");
	else
		print("<br /><input type='button' value='Skapa tråd' onClick=\"window.location = '/forums.php/createtopic/$forumid/';\" />");
	
	foot();
	die;
}

if ($action == 'viewtopic')
{
	$topicid = 0 + $path[2];
	$page = 0 + $path[3];
	
	$perpage = $CURUSER["postsperpage"];
	
	if (!$perpage)
		$perpage = 25;
	elseif ($perpage > 50)
		$perpage = 50;
	
	if ($path[2] == 'last')
	{
		$topicid = mysql_fetch_row(mysql_query("SELECT id FROM topics ORDER BY id DESC LIMIT 1"));
		$topicid = $topicid[0];
	}
	elseif (!$topicid)
		die("Ogiltigt trådid");
	
	mysql_query("UPDATE topics SET hits = hits + 1 WHERE id = " . sqlesc($topicid)) or sqlerr(__FILE__, __LINE__);

	$posts = mysql_query("SELECT COUNT(*) FROM posts WHERE topicid = " . sqlesc($topicid)) or sqlerr(__FILE__, __LINE__);
	$posts = mysql_fetch_row($posts);
	
	if (!$posts[0])
		stderr("Fel", "Tråden finns inte");

	$pages = ceil($posts[0] / $perpage);

	if ($path[3] == 'last')
		$page = $pages;

	$topic = mysql_query("SELECT topics.name, topics.descr, topics.locked, topics.sticky, topics.userid, topics.hits, topics.minclasswrite, overforums.name AS overforum, overforums.id AS forumid, overforums.minclassread, forums.name AS forum FROM topics LEFT JOIN overforums ON topics.forumid = overforums.id LEFT JOIN forums ON overforums.forumid = forums.id WHERE topics.id = " . sqlesc($topicid)) or sqlerr(__FILE__, __LINE__);
	$topic = mysql_fetch_assoc($topic);
	
	if (get_user_class() < $topic["minclassread"])
		stderr("Fel", "Tillgång nekad");
	
	$user = mysql_query("SELECT username FROM users WHERE id = $topic[userid]") or sqlerr(__FILE__, __LINE__);
	
	if ($user = mysql_fetch_assoc($user))
		$username = "<a href='/userdetails.php?id=$topic[userid]'>$user[username]</a>";
	else
		$username = "<i>Borttagen</i>";
	
	head("Forum - " . htmlspecialchars($topic["name"]));
	
	$guarded = mysql_query("SELECT id FROM guarded WHERE userid = $CURUSER[id] AND topicid = " . sqlesc($topicid) . " AND posterid = 0") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($guarded) < 1)
		$guard = "<a class='jlink' onClick='guard($topicid)'>Bevaka tråd</a>";
	else
		$guard = "<a class='jlink' onClick='guard($topicid)'>Sluta bevaka tråd</a>";
		
	list($pager, $limit) = pager("/forums.php/viewtopic/$topicid/", $posts[0], $perpage, $page);
	
	$headinfo[] = "$username - " . ($topic["descr"] ? htmlspecialchars($topic["descr"]) : htmlspecialchars($topic["name"]));
	$headinfo[] = number_format($posts[0] - 1) . " svar";
	$headinfo[] = number_format($topic["hits"]) . " visningar <span id='guard' style='float: right;'>$guard</span>";
	
	$forumpath = array("<a href='/forums.php'>Forum</a>", htmlspecialchars($topic["forum"]), "<a href='/forums.php/viewforum/$topic[forumid]/'>" . htmlspecialchars($topic["overforum"]) . "</a>", htmlspecialchars($topic["name"]));
	
	forumhead($forumpath, $headinfo);

	print($pager);
	
	$res = mysql_query("SELECT * FROM posts WHERE topicid = " . sqlesc($topicid) . " ORDER BY id ASC $limit") or sqlerr(__FILE__, __LINE__);

	$i = 1;
	while ($arr = mysql_fetch_assoc($res))
	{
		$arr["i"] = $i;
		print(showPost($arr));
		
		if ($i++ == mysql_num_rows($res))
		{
			$read = mysql_query("SELECT id, postid FROM readposts WHERE userid = $CURUSER[id] AND topicid = " . sqlesc($topicid)) or sqlerr(__FILE__, __LINE__);
			$read = mysql_fetch_assoc($read);
			
			if ($read)
			{
				if ($read["postid"] < $arr["id"])
					mysql_query("UPDATE readposts SET postid = $arr[id] WHERE id = $read[id]") or sqlerr(__FILE__, __LINE__);
			} else
				mysql_query("INSERT INTO readposts (userid, topicid, postid) VALUES($CURUSER[id], " . sqlesc($topicid) . ", $arr[id])") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	if (get_user_class() < $topic["minclasswrite"])
		print("<div style='margin-bottom: 10px; font-style: italic; color: gray;'>Enbart " . get_user_class_name($topic["minclasswrite"]) . " och högre kan skriva i denna tråd</div>\n");
	else
	{
		print("<div id='posttools'><div id='newpost'></div><div id='granskning'></div>\n");
	
		$btags = array("b" => array("b", format_comment("[b]Fet[/b] text") . "<br /><br /><span class=small>Ex. [b]Fet[/b]</span>"), "i" => array("i", format_comment("[i]Kursiv[/i] text") . "<br /><br /><span class=small>Ex. [i]Kursiv[/i]</span>"), "u" => array("u", format_comment("[u]Understruken[/u] text") . "<br /><br /><span class=small>Ex. [u]Understruken[/u]</span>"), "c" => array("c", format_comment("[c]Centrerad[/c] text") . "<br /><br /><span class=small>Ex. [c]Centrerad[/c]</span>"), "img" => array("img", "Bifoga originalstor bild<br />" . format_comment("[img]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/img]") . "<br /><br /><span class=small>Ex. [img]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/img]</span>"), "img=" => array("img2", "Bifoga originalstor bild<br />" . format_comment("[img=http://swepiracy.nu/images/2012/logo_orig_191420010.png]") . "<br /><br /><span class=small>Ex. [img=http://swepiracy.nu/images/2012/logo_orig_191420010.png]</span>"), "imgw" => array("imgw", "Bifoga storleksanpassad bild<br />" . format_comment("[imgw]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/imgw]") . "<br /><br /><span class=small>Ex. [imgw]http://swepiracy.nu/images/2012/logo_orig_191420010.png[/imgw]</span>"), "url" => array("url", "Bifoga länk<br /><br />" . format_comment("[url]www.google.se[/url]") . "<br /><br /><span class=small>Ex. [url]www.google.se[/url]</span>"), "url=" => array("url2", "Bifoga " . format_comment("[url=www.google.se]länk[/url]") . "<br /><br /><span class=small>Ex. [url=www.google.se]Länk[/url]</span>"), "*" => array("list", "Bifoga lista<br /><br />" . format_comment("[*] Listpunkt") . "<br /><br /><span class=small>Ex. [*] Listpunkt</span>"), "spoiler" => array("spoiler", "Bifoga spoiler<br /><br />" . format_comment("[spoiler]Dold information[/spoiler]") . "<br /><br /><span class=small>Ex. [spoiler]Dold information[/spoiler]</span>"), "pre" => array("pre", format_comment("[pre]Formaterad[/pre] text") . "<br /><br /><span class=small>Ex. [pre]Formaterad[/pre]</span>"), "quote" => array("quote", "Bifoga citat<br /><br />" . format_comment("[quote]Citat[/quote]") . "<br /><br /><span class=small>Ex. [quote]Citat[/quote]</span>"), "quote=" => array("quote2", "Bifoga citat<br /><br />" . format_comment("[quote=Swepiracy]Citat[/quote]") . "<br /><br /><span class=small>Ex. [quote=Swepiracy]Citat[/quote]</span>"), "size=" => array("size", format_comment("[size=4]Storleksanpassad[/size] text") . "<br /><br /><span class=small>Ex. [size=5]Storleksanpassad[/size]</span>"), "font=" => array("font", format_comment("[font=courier]Teckensnittsanpassad[/font] text") . "<br /><br /><span class=small>Ex. [font=courier]Teckensnittsanpassad[/font]</span>"), "color=" => array("color", format_comment("[color=red]Färgad[/color] text") . "<br /><br /><span class=small>Ex. [color=red]Färgad[/color]</span>"));
	
		while (list($b, list($id, $descr)) = each($btags))
			$buttons .= "<input type='button' class='btag' alt='$id' value='[$b]'" . ($b != '*' ? " onClick=\"BB('$b')\"" : "") . " /><div id='$id' class='btaginfo'>$descr</div>";
	
		print("<div id='btags'>$buttons</div>\n");

		print("<form method='post' action='/forums.php?post=1' id='postform'>\n");
	
		foreach ($smilies AS $key => $url)
			$smil .= "<img class='smilie' title='" . htmlent($key) . "' src='/pic/smilies/$url' />";
	
		print("<input type='hidden' name='topicid' value=$topicid /><div id='smilies' class='smilstandard'>$smil</div><textarea id='postfield' class='poststandard' name='post'" . ($topic["locked"] == 'yes' || $CURUSER["forumrights"] == 'no' ? " disabled" : "") . ">" . ($topic["locked"] == 'yes' ? "Tråd låst, inga nya inlägg tillåtna" : ($CURUSER["forumrights"] == 'no' ? "Dina forumrättigheter har blivit inaktiverade" : "Skriv inlägg...")) . "</textarea><br /><div class='errormess' id='errormess' style='display: inline-block; margin-right: 10px; opacity: 0; filter: alpha(opacity=0);'></div><input type='button' value='Förhandsgranska' id='review' disabled /> <input type='submit' value='Skicka' id='post' disabled /></form></div>\n");
	}
	
	print($pager);
	
	if (get_user_class() >= UC_MODERATOR)
	{
		$move = "<select name='overforum'>\n";
		$forums = mysql_query("SELECT * FROM forums ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
		
		while ($forum = mysql_fetch_assoc($forums))
		{
			$move .= "<optgroup label='$forum[name]'>\n";
			
			$overforums = mysql_query("SELECT * FROM overforums WHERE forumid = $forum[id] ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
			
			while ($overforum = mysql_fetch_assoc($overforums))
				$move .= "<option value=$overforum[id]" . ($overforum["id"] == $topic["forumid"] ? " selected" : "") . ">$overforum[name]</option>\n";
			
			$move .= "</optgroup>\n";
		}
		
		$move .= "</select>";
		
		$minclasswrite = "<select name='minclasswrite'>\n";
		
		$i = 0;
		while ($class = get_user_class_name($i))
			$minclasswrite .= "<option value=$i" . ($topic["minclasswrite"] == $i++ ? " selected" : "") . ">$class</option>\n";
			
		$minclasswrite .= "</select>\n";
	
		print("<form method='post' action='/forums.php?edittopic=1' id='modtools'><table class='clear'>\n");
		print("<tr><td class='form'>Låst:</td><td><input type='radio' name='locked' value='yes'" . ($topic["locked"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='locked' value='no'" . ($topic["locked"] == 'no' ? " checked" : "") . " />Nej </td><td class='form'>Namn:</td><td><input type='text' size=50 maxlength=32 name='topic' value='" . htmlent($topic["name"]) . "' /></td></tr>\n");
		print("<tr><td class='form'>Klistrad:</td><td><input type='radio' name='sticky' value='yes'" . ($topic["sticky"] == 'yes' ? " checked" : "") . " />Ja <input type='radio' name='sticky' value='no'" . ($topic["sticky"] == 'no' ? " checked" : "") . " />Nej </td><td class='form'>Beskrivning:</td><td><input type='text' size=50 maxlength=50 name='descr' value='" . htmlent($topic["descr"]) . "' /><input type='hidden' name='topicid' value='$topicid' /></td></tr>\n");
		print("<tr><td class='form'>Flytta:</td><td colspan=3>$move</td></tr>\n");
		print("<tr><td class='form'>Skrivbehörighet:</td><td colspan=3>$minclasswrite</td></tr>\n");
		print("<tr><td style='text-align: center;' colspan=4><input type='submit' id='edittopic' value='Ändra tråd' /> <input type='button' id='deltopic' value='Radera tråd' onclick='delTopic($topicid)' /></td></tr>\n");
		print("</table><div class='errormess' id='tediterr' style='margin: 10px 0px 0px 0px;'></div></form>\n");
	}

	foot();
	die;
}

if ($action == 'viewunread')
{
	head("Forum - visa olästa trådar");
	
	$forumpath = array("<a href='/forums.php'>Forum</a>", "Olästa forumtrådar");
	
	forumhead($forumpath);
	
	$res = mysql_query("SELECT topics.id AS topicid, topics.name AS topicname, topics.descr, topics.lastpost, topics.locked, posts.userid, posts.added, overforums.id AS forumid, overforums.name AS forumname FROM topics LEFT JOIN readposts ON topics.id = readposts.topicid AND readposts.userid = $CURUSER[id] LEFT JOIN posts ON topics.lastpost = posts.id INNER JOIN overforums ON topics.forumid = overforums.id WHERE (readposts.id IS NULL OR topics.lastpost > readposts.postid) AND overforums.minclassread <= $CURUSER[class] ORDER BY topics.lastpost DESC LIMIT 50") or sqlerr(__FILE__, __LINE__);
	$count = mysql_num_rows($res);
	
	if (!$count)
	{
		print("<i>Det finns inga olästa inlägg</i>\n");
		foot();
	}
	
	if ($count >= 50)
		print("<p style='font-style: italic;'>Det finns fler än 50 olästa trådar</p>\n");
	
	print("<table><tr><td class='colhead' colspan=2>Tråd</td><td class='colhead'>Forum</td><td class='colhead'>Senaste inlägg</td></tr>\n");
	
	$i = 0;
	while ($arr = mysql_fetch_assoc($res))
	{
		if (!$arr["userid"])
			$username = "<i>System</i>";
		else
		{
			$user = mysql_query("SELECT username FROM users WHERE id = $arr[userid]") or sqlerr(__FILE__, __LINE__);
			
			if ($user = mysql_fetch_assoc($user))
				$username = "<a href='/userdetails.php?id=$arr[userid]'>$user[username]</a>";
			else
				$username = "<i>Borttagen</i>";
		}
		
		print("<tr><td style='border-right: none; padding-right: 0px;'><img src='/pic/unread" . ($arr["locked"] == 'yes' ? "locked" : "") . ".png' title='Olästa inlägg' /></td><td style='border-left: none;'><a href='/forums.php/viewtopic/$arr[topicid]/last?p$arr[lastpost]'>$arr[topicname]</a>" . ($arr["descr"] ? "<br /><span class='small'>$arr[descr]</span>" : "") . "<td><a href='/forums.php/viewforum/$arr[forumid]'>$arr[forumname]</a></td><td style='text-align: right;'>" . get_elapsed_time($arr["added"]) . " sedan<br />av $username</td></tr>\n");
		$i++;
	}
	
	print("</table>\n");
	
	print("<p><a href='/forums.php/catchup'>Visa alla som lästa</a></p>\n");
	
	foot();
}

if ($action == 'catchup')
{
	mysql_query("DELETE FROM readposts WHERE userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
	$res = mysql_query("SELECT id, lastpost FROM topics") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
		mysql_query("INSERT INTO readposts (userid, topicid, postid) VALUES($CURUSER[id], $arr[id], $arr[lastpost])") or sqlerr(__FILE__, __LINE__);
		
	header("Location: /forums.php");
}

if ($action == 'viewguarded')
{
	head("Forum - bevakade trådar");
	
	$forumpath = array("<a href='/forums.php'>Forum</a>", "Bevakade trådar");
	
	forumhead($forumpath);
	
	print("<form method='post' action='/forums.php?takeguarded=1' id='guardform'>\n");
	print("<table><tr><td class='colhead' colspan=2>Tråd</td><td class='colhead'>Forum</td><td class='colhead'>Senaste inlägg</td><td class='colhead'>PM-notis</td><td class='colhead' style='text-align: center;'>X</td></tr>\n");
	
	$res = mysql_query("SELECT * FROM guarded WHERE userid = $CURUSER[id] ORDER BY id DESC") or sqlerr(__FILE__, __LINE__);
	
	if (!mysql_num_rows($res))
	{
		print("<tr class='clear'><td colspan=6 style='text-align: center;'><i>Du har inte bevakat några trådar</i></td></tr>\n");
		$deact = true;
	}
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$topic = mysql_query("SELECT id, name, forumid, descr, locked, lastpost FROM topics WHERE id = $arr[topicid]") or sqlerr(__FILE__, __LINE__);
		
		if ($topic = mysql_fetch_assoc($topic))
		{
		
			$forum = mysql_query("SELECT name FROM overforums WHERE id = $topic[forumid]") or sqlerr(__FILE__, __LINE__);
			$forum = mysql_fetch_assoc($forum);
		
			if ($arr["posterid"])
			{
				$user = mysql_query("SELECT username FROM users WHERE id = $arr[posterid]") or sqlerr(__FILE__, __LINE__);
				
				if ($user = mysql_fetch_assoc($user))
					$username = "<a href='/userdetails.php?id=$arr[posterid]'>$user[username]</a>";
				else
					$username = "<i>Borttagen</i>";
			}
			
			$lastpost = mysql_query("SELECT userid, added FROM posts WHERE id = $topic[lastpost]") or sqlerr(__FILE__, __LINE__);
			$lastpost = mysql_fetch_assoc($lastpost);
			
			$lastuser = mysql_query("SELECT username FROM users WHERE id = $lastpost[userid]") or sqlerr(__FILE__, __LINE__);
			
			if ($lastuser = mysql_fetch_assoc($lastuser))
				$lastusername = "<a href='/userdetails.php?id=$lastpost[userid]'>$lastuser[username]</a>";
			else
				$lastusername = "<i>Borttagen</i>";
			
			print("<tr><td style='padding-right: 0px; border-right: none;'>" . get_forum_pic($topic) . "</td><td style='border-left: none;'>" . ($username ? "<i>$username</i> i " : "") . "<a href='/forums.php/viewtopic/$arr[topicid]/last#p$topic[lastpost]'>" . htmlspecialchars($topic["name"]) . "</a>" . ($topic["descr"] ? "<br /><span class='small'>" . htmlspecialchars($topic["descr"]) . "</span>" : "") . "</td><td><a href='/forums.php/viewforum/$topic[forumid]'>$forum[name]</a><td>" . get_elapsed_time($lastpost["added"]) . " sedan<br />av $lastusername</td><td><input type='radio' name='pm[$arr[id]]' value='yes'" . (!$arr["pm"] || $arr["pm"] == 'yes' ? " checked" : "") . " /> Alltid<br /><input type='radio' name='pm[$arr[id]]' value='once'" . ($arr["pm"] == 'once' ? " checked" : "") . " /> Förstainlägg<br /><input type='radio' name='pm[$arr[id]]' value='no'" . ($arr["pm"] == 'no' ? " checked" : "") . " /> Aldrig</td><td><input type='checkbox' name='del[]' value='$arr[id]' /></td></tr>\n");
		}
	}
	
	print("</table>\n");
	
	if (!$deact)
		print("<br /><span id='res' style='display: none; margin-right: 10px;'></span><input type='submit' id='guardupdate' value='Uppdatera' /> <input type='button' id='selectall' value='Markera alla' />\n");
		
	print("</form>");
	
	foot();
	die;
}

if ($action == 'userposts')
{
	$id = 0 + $_GET["id"];
	
	if ($id != $CURUSER["id"] && get_user_class() < UC_MODERATOR)
		stderr("Fel", "Tillgång nekad.");
	
	$user = mysql_query("SELECT username FROM users WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	
	if ($user = mysql_fetch_assoc($user))
		$username = $user["username"];
	else
		stderr("Fel", "Användaren finns inte.");
	
	head("Inlägg skrivna av $username");
	
	$forumpath = array("<a href='/forums.php'>Forum</a>", "Inlägg skrivna av $username");
	
	forumhead($forumpath);
	
	$res = mysql_query("SELECT id FROM posts WHERE userid = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
	$count = mysql_num_rows($res);
	
	list($pager, $limit) = pager("/forums.php/userposts?id=$id&page=", $count, 25, $_GET["page"]);
	
	print($pager);
	
	print("<table style='margin-top: 20px;'><tr><td class='colhead'>Inlägg</td></tr>\n");
				
	$res = mysql_query("SELECT posts.id, posts.body, posts.userid, posts.added, topics.id AS topicid, topics.name, topics.descr, topics.sticky, overforums.id AS forumid, overforums.name AS forumname FROM posts LEFT JOIN topics ON posts.topicid = topics.id LEFT JOIN overforums ON topics.forumid = overforums.id WHERE posts.userid = " . sqlesc($id) . " ORDER BY posts.added DESC $limit") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$posts = mysql_query("SELECT id FROM posts WHERE topicid = $arr[topicid]") or sqlerr(__FILE__, __LINE__);
		$posts = mysql_num_rows($posts);
				
		$perpage = $CURUSER["postsperpage"];
	
		if (!$perpage)
			$perpage = 25;
		elseif ($perpage > 50)
			$perpage = 50;

		$topicpages = ceil($posts / $perpage);
			
		if ($topicpages > 1)
		{
			$topicpager = "<img src='/pic/multipage.gif' />";
		
			for ($i = 1; $i <= $topicpages; $i++)
				$topicpager .= " <a href='/forums.php/viewtopic/$arr[topicid]/$i/'>$i</a>";
		}
				
		$body = format_comment(cutStr($arr["body"], 500));
			
		print("<tr><td><span class='postinfo'><a href='/forums.php/viewtopic/$arr[topicid]/" . findPage($arr["id"]) . "?p$arr[id]'>#$arr[id]</a> " . postTime($arr["added"]) . " av <a href='/userdetails.php?id=$id'>$username</a> (" . get_elapsed_time($arr["added"]) . " sedan)</span><span class='postman'>Tråd: <a href='/forums.php/viewtopic/$arr[topicid]/'>" . cutStr($arr["name"], 31) . "</a>" . ($topicpages > 1 ? " ($topicpager)" : "") . " (<a href='/forums.php/viewforum/$arr[forumid]/'>$arr[forumname]</a>)</span><div class='searchpost'>$body</div></td></tr>\n");
		print("<tr class='clear'><td style='padding: 10px;'></td></tr>\n");
	}
		
	print("</table>");
	
	print($pager);
	
	foot();
	die;
}

head("Forum");

$forums = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM overforums"));
$topics = mysql_fetch_row(mysql_query("SELECT COUNT(*), SUM(hits) FROM topics"));
$posts = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM posts"));

$headinfo[] = number_format($forums[0]) . " forum";
$headinfo[] = number_format($topics[0]) . " trådar";
$headinfo[] = number_format($posts[0]) . " inlägg";
$headinfo[] = number_format($topics[1]) . " visningar";

$forumpath = array("Forum");

forumhead($forumpath, $headinfo);

print("<form method='post' action='/forums.php?search=1' id='searchform'><table style='margin-bottom: 10px;'>\n");
print("<tr><td align='left'><input type='text' name='search' id='search' class='search' size=50 value='Forumsök...' /><br /><span style='font-size: 7pt;'>Minst fyra tecken</span></td><td id='searchsections' style='display: none;'><input type='radio' name='section' class='search' value='posts'" . ($_POST["section"] == 'posts' ? " checked" : "") . " />Inlägg <input type='radio' name='section' class='search' value='topics'" . ($_POST["section"] != 'posts' ? " checked" : "") . " />Trådar</td></tr>\n");
print("<tr id='searchforums' style='display: none;'><td colspan=2>\n");
	
$res = mysql_query("SELECT * FROM forums WHERE minclassread <= $CURUSER[class] ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	
print("<table cellpadding=5><tr class='clear'>\n");

if (!count($_POST["forums"]))
	$_POST["forums"] = array();
	
$i = 1;
while ($arr = mysql_fetch_assoc($res))
{
	print("<td style='vertical-align: top;" . ($i++ % 2 == 0 ? " background-color: #efefef;" : "") . "'><div style='font-weight: bold;'>$arr[name]</div>\n");
		
	$ofor = mysql_query("SELECT * FROM overforums WHERE forumid = $arr[id] AND minclassread <= $CURUSER[class] ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
		
	while ($for = mysql_fetch_assoc($ofor))
		print("<br /><input type='checkbox' name='forums[]' class='search' value='$for[id]'" . (in_array($for["id"], $_POST["forums"]) ? " checked" : "") . " /> " . htmlspecialchars($for["name"]) . "\n");
			
	print("</td>\n");
}
	
print("</tr></table></td></tr>\n");
print("</table></form>\n");
	
print("<div id='searchres' style='display: none;'></div>\n");

print("<table id='forums'>\n");

$res = mysql_query("SELECT * FROM forums WHERE minclassread <= $CURUSER[class] ORDER BY `order` ASC") or sqlerr(__FILE__, __LINE__);
$i = 0;

while ($arr = mysql_fetch_assoc($res))
{
	if ($i++ > 0)
		print("<tr class='clear'><td colspan=4></td></tr>");

	print("<tr><td class='colhead' colspan=2>" . htmlspecialchars($arr["name"]) . "</td><td class='colhead'>Trådar</td><td class='colhead'>Inlägg</td><td class='colhead'>Senaste inlägg</td></tr>");

	$ras = mysql_query("SELECT * FROM overforums WHERE forumid = $arr[id] AND minclassread <= $CURUSER[class] ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($ras))
	{
		$topics = mysql_query("SELECT COUNT(*) FROM topics WHERE forumid = $arr[id]") or sqlerr(__FILE__, __LINE__);
		$topics = mysql_fetch_row($topics);
		
		$posts = mysql_query("SELECT COUNT(*) FROM posts LEFT JOIN topics ON posts.topicid = topics.id WHERE topics.forumid = $arr[id]") or sqlerr(__FILE__, __LINE__);
		$posts = mysql_fetch_row($posts);

		$lastpost = mysql_query("SELECT posts.id, posts.userid, posts.added, posts.topicid, topics.name FROM posts LEFT JOIN topics ON topics.lastpost = posts.id WHERE topics.forumid = $arr[id] ORDER BY topics.lastpost DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$lastpost = mysql_fetch_assoc($lastpost);
		
		if (!$lastpost["userid"])
			$user = "<i>System</i>";
		else
		{
			$user = mysql_query("SELECT username FROM users WHERE id = '$lastpost[userid]'") or sqlerr(__FILE__, __LINE__);

			if ($user = mysql_fetch_assoc($user))
				$user = "<a href='/userdetails.php?id=$lastpost[userid]'>$user[username]</a>";
			else
				$user = "<i>Borttagen</i>";
		}

		print("<tr><td style='border-right: 0px; padding-right: 0px;'>" . (newPost($lastpost["topicid"], $lastpost["id"]) ? "<img src='/pic/unread.png' title='Olästa inlägg' />" : "<img src='/pic/read.png' title='Inga olästa inlägg' />") . "</td><td style='border-left: 0px;'><a href='forums.php/viewforum/$arr[id]/'>" . htmlspecialchars($arr["name"]) . "</a><br /><font style='font-size: 7pt;'>" . htmlspecialchars($arr["descr"]) . "</font></td><td>" . number_format($topics[0]) . "</td><td>" . number_format($posts[0]) . "</td><td>" . ($lastpost ? get_elapsed_time($lastpost["added"]) . " sedan av $user<br />i <a href='/forums.php/viewtopic/$lastpost[topicid]/last?p$lastpost[id]'>" . htmlspecialchars($lastpost["name"]) . "</a>" : "<i>Inga inlägg</i>") . "</td></tr>\n");
	}
}

print("</table>\n");

$forumbottom = array("Visa olästa" => "/forums.php/viewunread", "Häng med" => "/forums.php/catchup", "Bevakade" => "/forums.php/viewguarded", "Mina inlägg" => "/forums.php/userposts?id=$CURUSER[id]");

foreach ($forumbottom AS $link => $url)
	$links[] = "<a href='$url'>$link</a>";
	
print("<div id='topicbottom'>" . implode("<span class='spacer'>&#8226;</span>", $links) . "</div>\n");

foot();

?>