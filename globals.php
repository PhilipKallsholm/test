<?php

//session_start();

//set_magic_quotes_runtime(false);

require_once("secrets.php");

$defaultbaseurl = "http://www.swepiracy.org";
$sitename = "SWP";
$sitemail = "noreply@swepiracy.org";
$time_online_start = strtotime("2012-06-12 12:20:00");

define("UC_USER", 0);
define("UC_POWER_USER", 1);
define("UC_SOPHISTICATED_USER", 2);
define("UC_MARVELOUS_USER", 3);
define("UC_BLISSFUL_USER", 4);
define("UC_VIP", 5);
define("UC_UPLOADER", 6);
define("UC_MODERATOR", 7);
define("UC_ADMINISTRATOR", 8);
define("UC_SYSOP", 9);

$max_torrent_size = 3145728;
$max_sub_size = 5242880;
$announce_interval = 30 * 60;

$covers_dir = "/var/covers";
$imdb_small_dir = "/var/imdb_small";
$imdb_large_dir = "/var/imdb_large";
$sub_dir = "/var/subs";

function _404() {
	header("HTTP/1.1 404 Not Found");
	include("404.php");
}

function getip() {
	return $_SERVER["REMOTE_ADDR"];
}

function get_user_class() {
	global $CURUSER;
	
	return $CURUSER["class"];
}

function staff() {
	global $CURUSER;
	
	if (get_user_class() >= UC_MODERATOR)
		return true;
	
	return false;
}

function get_user_class_name($class) {
	switch($class)
	{
		case UC_USER:
			return "User";
			break;
		case UC_POWER_USER:
			return "Power User";
			break;
		case UC_SOPHISTICATED_USER:
			return "Sophisticated User";
			break;
		case UC_MARVELOUS_USER:
			return "Marvelous User";
			break;
		case UC_BLISSFUL_USER:
			return "Blissful User";
			break;
		case UC_VIP:
			return "VIP";
			break;
		case UC_UPLOADER:
			return "Uploader";
			break;
		case UC_MODERATOR:
			return "Moderator";
			break;
		case UC_ADMINISTRATOR:
			return "Administrator";
			break;
		case UC_SYSOP:
			return "SysOp";
			break;
	}
}

function usericons($id)
{
	$res = mysql_query("SELECT enabled, warned, warned_reason, donor, donated, crown FROM users WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if (!$arr)
		return;
	
	$icons = array();
	
	if ($arr["enabled"] == 'no')
		$icons[] = array("disabled.gif", "Inaktiverad");
		
	if ($arr["warned"] == 'yes')
		$icons[] = array("warnedsmall.gif", $arr["warned_reason"]);
		
	if ($arr["donor"] == 'yes')
	{
		if ($arr["donated"] >= 1000)
			$color = "blue";
		elseif ($arr["donated"] >= 500)
			$color = "red";
		else
			$color = "";
		
		$icons[] = array("starsmall{$color}.png", "Donator");
	}
		
	if ($arr["crown"] == 'yes')
		$icons[] = array("crownsmall.png", "Krona");
		
	if (!count($icons))
		return;
	
	$return = array();
	foreach ($icons AS $i)
		$return[] = "<img src='/pic/$i[0]' title='$i[1]' style='vertical-align: text-top;' />";
		
	return implode("", $return);
}

function is_valid_id($id)
{
  return is_numeric($id) && ($id > 0) && (floor($id) == $id);
}

function validfilename($name) {
    return preg_match('/^[^\0-\x1f:\\\\\/?*\xff#<>|]+$/si', $name);
}

function searchfield($s) {
    return preg_replace(array('/[^a-z0-9]/si', '/^\s*/s', '/\s*$/s', '/\s+/s'), array(" ", "", "", " "), $s);
}

function mksize($bytes)
{
	if ($bytes < 1000 * 1024)
		return number_format($bytes / 1024, 2) . " kB";
	elseif ($bytes < 1000 * 1048576)
		return number_format($bytes / 1048576, 2) . " MB";
	elseif ($bytes < 1000 * 1073741824)
		return number_format($bytes / 1073741824, 2) . " GB";
	else
		return number_format($bytes / 1099511627776, 2) . " TB";
}

function stripchr($a, $s)
{
	return str_ireplace($a, "", $s);
}

function write_log($text, $name = "", $anonymous = false)
{
	$dt = get_date_time();
	$anonymous = $anonymous ? "yes" : "no";

	mysql_query("INSERT INTO sitelog (added, body, name, anonymous) VALUES(" . implode(", ", array_map("sqlesc", array($dt, $text, $name, $anonymous))) . ")") or sqlerr(__FILE__, __LINE__);
}

function jErr($text) {
	global $return;

	$return["err"] = $text;
	
	print(json_encode($return));
	die;
}

$cleaninterval = 15; //minuter
require_once("cleanup.php");

function auto_cleanup() {
	global $cleaninterval;

	$res = mysql_query("SELECT value FROM autovalues WHERE name = 'lastclean'") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_row($res);
	
	$lastclean = $arr[0];
	
	if (strtotime("+{$cleaninterval} minutes", $lastclean) < time())
	{
		mysql_query("UPDATE autovalues SET value = '" . time() . "' WHERE name = 'lastclean'") or sqlerr(__FILE__, __LINE__);
		docleanup($lastclean);
	}
}

function genrelist() {
	$ret = array();
	$res = mysql_query("SELECT id, name, cattype FROM categories ORDER BY name") or sqlerr(__FILE__, __LINE__);
	
	while ($row = mysql_fetch_array($res))
		$ret[] = $row;
		
	return $ret;
}

function pager($href, $count, $perpage, $page = 1, $text = true, $index = false) {
	$pages = ceil($count / $perpage);
	
	if (!$pages)
		$pages = 1;
	
	if ($page < 1)
		$page = 1;
	elseif ($page > $pages)
		$page = $pages;
		
	$pager = array();
	$pagerlink = array();

	if ($text)
	{
		if ($page > 1)
			$pager[] = "<span class='pager' style='margin-right: 20px;'><a href='$href" . ($page - 1) . "'>&laquo; Föregående</a></span>";
		else
			$pager[] = "<span class='pager' style='margin-right: 20px; color: grey; font-weight: bold;'>&laquo; Föregående</span>";
	}
	
	for ($i = 1; $i <= $pages; $i++)
	{
		if ($i != 1 && $i != $pages && $i > 3 && $i < $pages - 2 && ($i - $page > 2 || $page - $i > 2) && $i != $page - 10 && $i != $page - 100 && $i != $page + 10 && $i != $page + 100)
		{
			if (!$spacer)
				$pagerlink[] = " <strong>&hellip;</strong> ";
				
			$spacer = true;
		}
		else
		{
			$resc = (($i - 1) * $perpage + 1) . " - " . (($i * $perpage) > $count ? $count : ($i * $perpage));
		
			if ($i == $page && $text)
				$pagerlink[] = "<span class='pager' style='color: grey; font-weight: bold;'>" . ($index ? $resc : $i) . "</span>";
			else
				$pagerlink[] = "<span class='pager'><a href='$href$i' class='pager'>" . ($index ? $resc : $i) . "</a></span>";
			
			$spacer = False;
		}
	}
	
	$pager[] = implode(($index ? " | " : ""), $pagerlink);
	
	if ($text)
	{
		if ($page < $pages)
			$pager[] = "<span class='pager' style='margin-left: 20px;'><a href='$href" . ($page + 1) . "' class='pager'>Nästa &raquo;</a></span>";
		else
			$pager[] = "<span class='pager' style='margin-left: 20px; color: grey; font-weight: bold;'>Nästa &raquo;</span>";
	}
		
	$begin = ($page - 1) * $perpage;
		
	return array(implode("", $pager), "LIMIT $begin, $perpage");
}

function mksecret($len = 20) {
	$secret = "";

	for ($i = 0; $i < $len; $i++)
		$secret .= chr(mt_rand(0, 255));

	return $secret;
}

function mkpassword($len = 10) {
	$pass = "";
	
	for ($i = 0; $i < $len; $i++)
		$pass .= chr(mt_rand(33, 122));
		
	return $pass;
}

function validmail($mail) {
	if (!preg_match("#^[\w-\.\+]+@[\w-]+\.[a-z]{2,6}$#i", $mail))
		return false;

	return true;
}

function validusername($name) {
	if (!$name)
		return false;

	$allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	for ($i = 0; $i < strlen($name); $i++)
		if (strpos($allowed, $name[$i]) === False)
			return false;

	return true;
}

function get_ratio_color($ratio)
{
	if ($ratio < 0.1) return "#ff0000";
	if ($ratio < 0.2) return "#ee0000";
	if ($ratio < 0.3) return "#dd0000";
	if ($ratio < 0.4) return "#cc0000";
	if ($ratio < 0.5) return "#bb0000";
	if ($ratio < 0.6) return "#aa0000";
	if ($ratio < 0.7) return "#990000";
	if ($ratio < 0.8) return "#880000";
	if ($ratio < 0.9) return "#770000";
	if ($ratio < 1) return "#660000";
	return "#000000";
}

function get_slr_color($ratio)
{
	if ($ratio < 0.025) return "#ff0000";
	if ($ratio < 0.05) return "#ee0000";
	if ($ratio < 0.075) return "#dd0000";
	if ($ratio < 0.1) return "#cc0000";
	if ($ratio < 0.125) return "#bb0000";
	if ($ratio < 0.15) return "#aa0000";
	if ($ratio < 0.175) return "#990000";
	if ($ratio < 0.2) return "#880000";
	if ($ratio < 0.225) return "#770000";
	if ($ratio < 0.25) return "#660000";
	if ($ratio < 0.275) return "#550000";
	if ($ratio < 0.3) return "#440000";
	if ($ratio < 0.325) return "#330000";
	if ($ratio < 0.35) return "#220000";
	if ($ratio < 0.375) return "#110000";
	return "#000000";
}

function htmlent($x) {
	return htmlspecialchars($x, ENT_QUOTES);
}

function get_row_count($table, $where = "") {
	$count = mysql_query("SELECT COUNT(*) FROM $table" . ($where ? " $where" : "")) or sqlerr(__FILE__, __LINE__);
	$count = mysql_fetch_row($count);
	
	return $count[0];
}

function findPage($id) {
	global $CURUSER;
	
	$perpage = $CURUSER["postsperpage"];
	
	if (!$perpage)
		$perpage = 25;
	elseif ($perpage > 50)
		$perpage = 50;

	$post = mysql_query("SELECT topicid FROM posts WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	$post = mysql_fetch_assoc($post);
	
	$posts = mysql_query("SELECT id FROM posts WHERE topicid = $post[topicid] AND id <= $id ORDER BY added ASC") or sqlerr(__FILE__, __LINE__);
	$posts = mysql_num_rows($posts);
	
	return ceil($posts / $perpage);
	
	/*$i = 1;
	
	while ($post = mysql_fetch_assoc($posts))
	{
		if ($post["id"] == $id)
			return ceil($i / $perpage);
	
		$i++;
	}*/
}

$smilies = array(
  ":)" => "smile1.gif",
  ":smile:" => "smile2.gif",
  ":-D" => "grin.gif",
  ":lol:" => "laugh.gif",
  ":w00t:" => "w00t.gif",
  ":-P" => "tongue.gif",
  ":wink:" => "wink.gif",
  ":-|" => "noexpression.gif",
  ":-/" => "confused.gif",
  ":-(" => "sad.gif",
  ":'-(" => "cry.gif",
  ":weep:" => "weep.gif",
  ":-O" => "ohmy.gif",
  ":o)" => "clown.gif",
  "8-)" => "cool1.gif",
  "|-)" => "sleeping.gif",
  ":innocent:" => "innocent.gif",
  ":whistle:" => "whistle.gif",
  ":unsure:" => "unsure.gif",
  ":closedeyes:" => "closedeyes.gif",
  ":cool:" => "cool2.gif",
  ":fun:" => "fun.gif",
  ":thumbsup:" => "thumbsup.gif",
  ":thumbsdown:" => "thumbsdown.gif",
  ":blush:" => "blush.gif",
  ":unsure:" => "unsure.gif",
  ":yes:" => "yes.gif",
  ":no:" => "no.gif",
  ":love:" => "love.gif",
  ":?:" => "question.gif",
  ":!:" => "excl.gif",
  ":idea:" => "idea.gif",
  ":arrow:" => "arrow.gif",
  ":arrow2:" => "arrow2.gif",
  ":hmm:" => "hmm.gif",
  ":hmmm:" => "hmmm.gif",
  ":huh:" => "huh.gif",
  ":geek:" => "geek.gif",
  ":look:" => "look.gif",
  ":rolleyes:" => "rolleyes.gif",
  ":kiss:" => "kiss.gif",
  ":shifty:" => "shifty.gif",
  ":blink:" => "blink.gif",
  ":smartass:" => "smartass.gif",
  ":sick:" => "sick.gif",
  ":crazy:" => "crazy.gif",
  ":wacko:" => "wacko.gif",
  ":alien:" => "alien.gif",
  ":wizard:" => "wizard.gif",
  ":wave:" => "wave.gif",
  ":wavecry:" => "wavecry.gif",
  ":baby:" => "baby.gif",
  ":angry:" => "angry.gif",
  ":ras:" => "ras.gif",
  ":sly:" => "sly.gif",
  ":devil:" => "devil.gif",
  ":evil:" => "evil.gif",
  ":evilmad:" => "evilmad.gif",
  ":sneaky:" => "sneaky.gif",
  ":axe:" => "axe.gif",
  ":slap:" => "slap.gif",
  ":wall:" => "wall.gif",
  ":rant:" => "rant.gif",
  ":jump:" => "jump.gif",
  ":yucky:" => "yucky.gif",
  ":nugget:" => "nugget.gif",
  ":smart:" => "smart.gif",
  ":shutup:" => "shutup.gif",
  ":shutup2:" => "shutup2.gif",
  ":crockett:" => "crockett.gif",
  ":zorro:" => "zorro.gif",
  ":snap:" => "snap.gif",
  ":beer:" => "beer.gif",
  ":beer2:" => "beer2.gif",
  ":drunk:" => "drunk.gif",
  ":strongbench:" => "strongbench.gif",
  ":weakbench:" => "weakbench.gif",
  ":dumbells:" => "dumbells.gif",
  ":music:" => "music.gif",
  ":stupid:" => "stupid.gif",
  ":dots:" => "dots.gif",
  ":offtopic:" => "offtopic.gif",
  ":spam:" => "spam.gif",
  ":oops:" => "oops.gif",
  ":lttd:" => "lttd.gif",
  ":please:" => "please.gif",
  ":sorry:" => "sorry.gif",
  ":hi:" => "hi.gif",
  ":yay:" => "yay.gif",
  ":cake:" => "cake.gif",
  ":hbd:" => "hbd.gif",
  ":band:" => "band.gif",
  ":punk:" => "punk.gif",
	":rofl:" => "rofl.gif",
  ":bounce:" => "bounce.gif",
  ":mbounce:" => "mbounce.gif",
  /*":thankyou:" => "thankyou.gif",*/
  ":gathering:" => "gathering.gif",
  ":hang:" => "hang.gif",
  ":chop:" => "chop.gif",
  ":rip:" => "rip.gif",
  ":whip:" => "whip.gif",
  ":judge:" => "judge.gif",
  ":chair:" => "chair.gif",
  ":tease:" => "tease.gif",
  ":box:" => "box.gif",
  ":boxing:" => "boxing.gif",
  ":guns:" => "guns.gif",
  ":shoot:" => "shoot.gif",
  ":shoot2:" => "shoot2.gif",
  ":flowers:" => "flowers.gif",
  ":wub:" => "wub.gif",
  ":lovers:" => "lovers.gif",
  ":kissing:" => "kissing.gif",
  ":kissing2:" => "kissing2.gif",
  ":console:" => "console.gif",
  ":group:" => "group.gif",
  ":hump:" => "hump.gif",
  ":hooray:" => "hooray.gif",
  ":happy2:" => "happy2.gif",
  ":clap:" => "clap.gif",
  ":clap2:" => "clap2.gif",
	":weirdo:" => "weirdo.gif",
  ":yawn:" => "yawn.gif",
  ":bow:" => "bow.gif",
	":dawgie:" => "dawgie.gif",
	":cylon:" => "cylon.gif",
  ":book:" => "book.gif",
  ":fish:" => "fish.gif",
  ":mama:" => "mama.gif",
  ":pepsi:" => "pepsi.gif",
  ":medieval:" => "medieval.gif",
  ":rambo:" => "rambo.gif",
  ":ninja:" => "ninja.gif",
  ":hannibal:" => "hannibal.gif",
  ":party:" => "party.gif",
  ":snorkle:" => "snorkle.gif",
  ":evo:" => "evo.gif",
  ":king:" => "king.gif",
  ":chef:" => "chef.gif",
  ":mario:" => "mario.gif",
  ":pope:" => "pope.gif",
  ":fez:" => "fez.gif",
  ":cap:" => "cap.gif",
  ":cowboy:" => "cowboy.gif",
  ":pirate:" => "pirate.gif",
  ":pirate2:" => "pirate2.gif",
  ":rock:" => "rock.gif",
  ":cigar:" => "cigar.gif",
  ":icecream:" => "icecream.gif",
  ":oldtimer:" => "oldtimer.gif",
	":trampoline:" => "trampoline.gif",
	":banana:" => "bananadance.gif",
  ":smurf:" => "smurf.gif",
  ":yikes:" => "yikes.gif",
  ":osama:" => "osama.gif",
  ":saddam:" => "saddam.gif",
  ":santa:" => "santa.gif",
  ":indian:" => "indian.gif",
  ":pimp:" => "pimp.gif",
  ":nuke:" => "nuke.gif",
  ":jacko:" => "jacko.gif",
  ":ike:" => "ike.gif",
  ":greedy:" => "greedy.gif",
	":super:" => "super.gif",
  ":wolverine:" => "wolverine.gif",
  ":spidey:" => "spidey.gif",
  ":spider:" => "spider.gif",
  ":bandana:" => "bandana.gif",
  ":construction:" => "construction.gif",
  ":sheep:" => "sheep.gif",
  ":police:" => "police.gif",
	":detective:" => "detective.gif",
  ":bike:" => "bike.gif",
	":fishing:" => "fishing.gif",
  ":clover:" => "clover.gif",
  ":horse:" => "horse.gif",
  ":shit:" => "shit.gif",
  ":soldiers:" => "soldiers.gif",
  ":newyear:" => "newyear.gif",
);

function format_quote($body) {
	while ($oldbody != $body)
	{
		$oldbody = $body;
		$close = stripos($body, "[/quote]");
	
		if ($close === false)
			return $body;
	
		$open = strripos(substr($body, 0, $close), "[quote");
	
		if ($open === false)
			return $body;
			
		$quote = substr($body, $open, $close - $open + 8);
	
		$quote = preg_replace("#\[quote\](?s)(.+?)\[/quote\]#i", "<span class='quote'>Citat:</span><div class='quote'>$1</div>", $quote);
		$quote = preg_replace("#\[quote=(.+?)\](?s)(.+?)\[/quote\]#i", "<span class='quote'>Citerar $1:</span><div class='quote'>$2</div>", $quote);
		
		$quote = preg_replace("#\[img=((https?://|www\.).+?\.(jpe?g|png|gif))\]#i", "$1" , $quote);
		$quote = preg_replace("#\[img\]((https?://|www\.).+?\.(jpe?g|png|gif))\[/img\]#i", "$1" , $quote);
		$quote = preg_replace("#\[imgw\]((https?://|www\.).+?\.(jpe?g|png|gif))\[/imgw\]#i", "$1" , $quote);
		$quote = preg_replace("#(?<![/=])(https?://|www\.)[^\s]*?youtube\.[^\s]+?v=([\w-]+)[^\s\[<]*+(?!\[/url\])#i", "[url]$0[/url]", $quote);
	
		$body = substr($body, 0, $open) . $quote . substr($body, $close + 8);
	}
	
	return $body;
}

function format_link($matches)
{
	$local = stripos($matches[0], "swepiracy.") !== false || (stripos($matches[0], "://") === false && stripos($matches[0], "www.") === false) ? true : false;

	if (stripos($matches[0], "[url") === false)
	{
		if (!$local)
			return "<a target='_blank' href='http://anonym.to/?http://" . $matches[2] . $matches[3] . "'>" . $matches[1] . "</a>";
		else
			return "<a href='" . $matches[3] . "'>" . $matches[1] . "</a>";
	}
	else
	{
		if (stripos($matches[0], "[url=") === false)
		{
			if (!$local)
				return "<a target='_blank' href='http://anonym.to/?http://" . $matches[2] . $matches[3] . "'>" . $matches[1] . "</a>";
			else
				return "<a href='" . $matches[2] . $matches[3] . "'>" . $matches[1] . "</a>";
		}
		else
		{
			if (!$local)
				return "<a target='_blank' href='http://anonym.to/?http://" . $matches[1] . $matches[2] . "'>" . $matches[3] . "</a>";
			else
				return "<a href='" . $matches[2] . "'>" . $matches[3] . "</a>";
		}
	}
}

function format_comment($x) {
	global $smilies, $CURUSER;
	
	$x = str_replace(";)", ":wink:", $x);
	$x = str_replace(":D", ":-D", $x);

	$x = htmlspecialchars($x);
	
	$x = format_quote($x);

	$x = preg_replace("#\[b\]((?s).+?)\[/b\]#i", "<strong>$1</strong>" , $x);
	$x = preg_replace("#\[i\]((?s).+?)\[/i\]#i", "<em>$1</em>" , $x);
	$x = preg_replace("#\[u\]((?s).+?)\[/u\]#i", "<u>$1</u>" , $x);
	$x = preg_replace("#\[c\]((?s).+?)\[/c\]#i", "<div class='center'>$1</div>" , $x);
	$x = preg_replace("#\[color=([\w\#]+)\]((?s).+?)\[/color\]#i", "<font color='$1'>$2</font>" , $x);
	$x = preg_replace("#\[size=([1-7])\]((?s).+?)\[/size\]#i", "<font size=$1>$2</font>" , $x);
	$x = preg_replace("#\[font=([\w-,\s]+)\]((?s).+?)\[/font\]#i", "<font style='font-family: $1'>$2</font>" , $x);
	$x = preg_replace_callback("#\[url\]((?:(?:(?:https?://(?:www\.)?|www\.)swepiracy\.(?:nu|org))|(?:(?:https?|ftps?|irc)://)|(www\.))?(.+?))\[/url\]#i", "format_link" , $x);
	$x = preg_replace_callback("#\[url=(?:(?:(?:https?://(?:www\.)?|www\.)swepiracy\.(?:nu|org))|(?:(?:https?|ftps?|irc)://)|(www\.))?(.+?)\](.+?)\[/url\]#i", "format_link" , $x);
	$x = preg_replace("#\[img=((https?://|www\.).+?\.(jpe?g|png|gif))\]#i", "<a target='_blank' href='http://anonym.to/?$1'><img src='$1' /></a>" , $x);
	$x = preg_replace("#\[img\]((https?://|www\.).+?\.(jpe?g|png|gif))\[/img\]#i", "<a target='_blank' href='http://anonym.to/?$1'><img src='$1' /></a>" , $x);
	$x = preg_replace("#\[imgw\]((https?://|www\.).+?\.(jpe?g|png|gif))\[/imgw\]#i", "<a target='_blank' href='http://anonym.to/?$1'><img style='width: 480px;' src='$1' class='transbor' /></a>" , $x);
	$x = preg_replace("#\[spoiler\]((?s).+?)\[/spoiler\]#i", "<fieldset class='spoiler'><legend>Spoiler:</legend><div>$1</div></fieldset>" , $x);
	$x = preg_replace("#\[\*\](.+)#i", "<ul><li>$1</li></ul>", $x);
	$x = preg_replace("#\[pre\]((?s).+?)\[/pre\]#i", "<pre>$1</pre>", $x);
	
	$x = preg_replace("#(?<![/'?])(https?://|www\.)[^\s]*?youtube\.[^\s]+?v=([\w-]+)[^\s'<]*+(?!</a>)#i", "<object style='height: 292px; width: 480px;'><param name='movie' value='https://www.youtube.com/v/$2?version=3&amp;feature=player_embedded'><param name='allowFullScreen' value='true'><param name='allowScriptAccess' value='always'><param name='wmode' value='transparent'><embed src='https://www.youtube.com/v/$2?version=3&amp;feature=player_embedded' type='application/x-shockwave-flash' allowfullscreen='true' allowScriptAccess='always' width=480 height=292 wmode='transparent' /></object>", $x);
	$x = preg_replace_callback("#(?<![/'?])((?:(?:(?:https?://(?:www\.)?|www\.)swepiracy\.(?:nu|org))|(?:(?:https?|ftps?|irc)://)|(www\.))([^\s\[\]()<>]++))(?!</a>)#i", "format_link", $x);
	
	$x = nl2br($x);
	$x = str_replace("  ", "&nbsp; ", $x);
	
	reset($smilies);
	
	if ($CURUSER["show_smilies"] != 'no')
		while (list($char, $url) = each($smilies))
			$x = str_replace($char, "<img src='/pic/smilies/$url' alt='$char' title='" . htmlent($char) . "' style='vertical-align: text-bottom;' />", $x);

	return $x;
}

function messhead($matches) {
	$matches = explode("@", $matches[1]);
	$id = 0 + $matches[0];
	$dt = $matches[1];
	
	if (!$id)
	{
		$username = "<em>System</em>";
		$avatar = "/pic/default_avatar.jpg";
	}
	else
	{
		$res = mysql_query("SELECT username, avatar FROM users WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		
		if ($arr = mysql_fetch_assoc($res))
		{
			$username = $arr["username"];
			$avatar = $arr["avatar"];
			
			if (!$avatar)
				$avatar = "/pic/default_avatar.jpg";
		}
		else
		{
			$username = "<em>Borttagen</em>";
			$avatar = "/pic/default_avatar.jpg";
		}
	}
		
	return "<fieldset class='message'><legend>$username</legend><span class='messdate'>" . get_elapsed_time($dt) . " sedan</span><img src='$avatar' style='width: 50px; vertical-align: text-top;' /><div style='width: 440px; float: right;'>";
}

function format_message($x) {
	$x = format_comment($x);

	$z = preg_split("#({\d+@[\d\s-:]+}<br />)#i", $x, NULL, PREG_SPLIT_DELIM_CAPTURE);
	$z = preg_replace_callback("#{(\d+@[\d\s-:]+)}<br />#i", messhead, $z);
	
	$x = $z[0];
	unset($z[0]);
	
	$z = array_chunk($z, 2);
	
	foreach ($z AS $y)
		$x .= "</div><div class='prevmess'>" . implode("", $y) . "</div></fieldset></div>";
		
	return $x;
}

function sqlinj() {
	$words = array();

	if (isset($_POST))
	{
		$arr = mysql_query("SELECT word FROM sqlinj");
		while ($res = mysql_fetch_assoc($arr))
			$words[] = $res["word"];

		foreach ($_POST as $p)
		{
			foreach ($words AS $word)
			{
				if (stripos("$p", $word) !== false && $word)
					mysql_query("INSERT INTO sqlerrors (userid, ip, added, error, page) VALUES(" . implode(", ", array_map("sqlesc", array($CURUSER["id"], $_SERVER["REMOTE_ADDR"], get_date_time(), $p, $_SERVER["REQUEST_URI"]))) . ")") or sqlerr(__FILE__, __LINE__);
			}
		}
	}

	$words = array();

	if (isset($_GET))
	{
		$arr = mysql_query("SELECT word FROM sqlinj");
		while ($res = mysql_fetch_assoc($arr))
			$words[] = $res["word"];

		foreach ($_GET as $p)
		{
			foreach ($words AS $word)
			{
				if (stripos("$p", $word) !== false && $word)
					mysql_query("INSERT INTO sqlerrors (userid, ip, added, error, page) VALUES(" . implode(", ", array_map("sqlesc", array($CURUSER["id"], $_SERVER["REMOTE_ADDR"], get_date_time(), $p, $_SERVER["REQUEST_URI"]))) . ")") or sqlerr(__FILE__, __LINE__);
			}
		}
	}
}

function dbconn($autoclean = false) {
	global $mysql_host, $mysql_user, $mysql_pass, $mysql_db;
	
	if (!mysql_connect($mysql_host, $mysql_user, $mysql_pass))
	{
		switch (mysql_errno())
		{
			case 2002:
				if ($_SERVER["REQUEST_METHOD"] == 'GET')
					die("<html><head><meta http-equiv=refresh content='5 $_SERVER[REQUEST_URI]'></head><body><table border=0 width=100% height=100%><tr><td><h3 align=center>The server load is very high at the moment. Retrying, please wait...</h3></td></tr></table></body></html>");
				else
					die("Too many users. Please press the Refresh button in your browser to retry.");
			default:
				die("Unable to connect to DB: " . mysql_error() . " (" . mysql_errno() . ")");
		}
	}
	
	if (!mysql_select_db($mysql_db))
		die("Unable to select DB: " . mysql_error() . " (" . mysql_errno() . ")");

	sqlinj();
	userlogin();
	
	if ($autoclean)	
		register_shutdown_function("auto_cleanup");
}

function userlogin() {
	global $sitename;
	
	unset($GLOBALS["CURUSER"]);
	
	$ip = getip();
	$ban = mysql_query("SELECT id, permban FROM bans WHERE ip = " . sqlesc($ip) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if ($ban = mysql_fetch_assoc($ban))
	{
		if ($ban["permban"] == 'yes')
		{
			$expires = strtotime("+1 year");
			setcookie("deacct", '1', $expires, "/");
		}
		
		header("HTTP/1.1 403 Forbidden");
		print("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n");
		print("<html><head><title>$sitename - 403 Forbidden</title></head><body><h1>403 Forbidden</h1>Unauthorized IP address.</body></html>\n");
		
		die;
	}

	$id = 0 + $_COOKIE["id"];

	$res = mysql_query("SELECT * FROM users WHERE id = $id AND confirmed = 'yes' AND enabled = 'yes' LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);

	if (!$arr)
		return;

	if ($_COOKIE["pass"] !== md5($arr["passhash"] . $ip . $arr["secret"]))
		return;
		
	if ($arr["class"] >= UC_MODERATOR)
	{
		$staffip = mysql_query("SELECT ip FROM staffip WHERE username = " . sqlesc($arr["username"])) or sqlerr(__FILE__, __LINE__);
		$staffip = mysql_fetch_assoc($staffip);
		
		if ($ip != $staffip["ip"])
		{
			stafflog("$ip försökte få tillgång till $arr[username] men hade inte sitt IP tillåtet");
			die("<html><head><meta http-equiv=refresh content='5 logout.php'></head><body><h1>Tillgång nekad. Du har blivit loggad.</h1></body></html>");
		}
	}
	
	$timediff = time() - strtotime($arr["last_access"]);
		
	mysql_query("UPDATE users SET last_access = '" . get_date_time() . "', ip = '$ip'" . ($timediff < 180 && $timediff > 0 ? ", time_online = time_online + $timediff" : "") . "  WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);

	$GLOBALS["CURUSER"] = $arr;
}

function loggedinorreturn() {
	global $CURUSER;

	if (!$CURUSER)
	{
		header("Location: /login.php?returnto=" . urlencode($_SERVER["REQUEST_URI"]));
		die;
	}
}

function mkprettytime($s) {
	if ($s < 0)
		$s = 0;
		
	$t = array();
	
	foreach (array("60:sec", "60:min", "24:hour", "0:day") as $x)
	{
		$y = explode(":", $x);
		
		if ($y[0] > 1)
		{
			$v = $s % $y[0];
			$s = floor($s / $y[0]);
		}
		else
			$v = $s;
			
		$t[$y[1]] = $v;
	}

	if ($t["day"])
		return $t["day"] . "d " . sprintf("%02d:%02d:%02d", $t["hour"], $t["min"], $t["sec"]);
	elseif ($t["hour"])
		return sprintf("%d:%02d:%02d", $t["hour"], $t["min"], $t["sec"]);
	else
		return sprintf("%d:%02d", $t["min"], $t["sec"]);
}

function elapsed_time($t) {
	$t = date("j F Y", strtotime($t));
	
	$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$swemonths = array("januari", "februari", "mars", "april", "maj", "juni", "juli", "augusti", "september", "oktober", "november", "december");
	
	$t = str_ireplace($months, $swemonths, $t);
	
	return $t;
}

function get_elapsed_time($t, $s = false) {
	if ($s)
		$secs = strtotime($t) - strtotime($s);
	else
		$secs = time() - strtotime($t);

	$mins = floor($secs / 60);
	$hours = floor($mins / 60);
	$days = floor($hours / 24);
	$weeks = floor($days / 7);

	if ($weeks)
		return "$weeks veck" . ($weeks > 1 ? "or" : "a");
	elseif ($days)
		return "$days dag" . ($days > 1 ? "ar" : "");
	elseif ($hours)
		return "$hours timm" . ($hours > 1 ? "ar" : "e");
	elseif ($mins)
		return "$mins minut" . ($mins > 1 ? "er" : "");
	else
		return "< 1 minut";
}

function get_elapsed_time_all($ts, $ts2)
{
	$secs = $ts - $ts2;
	$mins = floor($secs / 60);
	$secs -= $mins * 60;
	$hours = floor($mins / 60);
	$mins -= $hours * 60;
	$days = floor($hours / 24);
	$hours -= $days * 24;
	$weeks = floor($days / 7);
	$days -= $weeks * 7;
	$t = array();

	if ($weeks > 0)
		$t[] = "$weeks veck" . ($weeks > 1 ? "or" : "a");
	if ($days > 0)
		$t[] = "$days dag" . ($days > 1 ? "ar" : "");
	if ($hours > 0)
		$t[] = "$hours timm" . ($hours > 1 ? "ar" : "e");
	if ($mins > 0)
		$t[] = "$mins minut" . ($mins > 1 ? "er" : "");
	if ($secs > 0)
		$t[] = "$secs sekund" . ($secs > 1 ? "er" : "");
  
	return implode(" ", $t);
}

function get_time($t) {
	$secs = $t;

	$mins = floor($secs / 60);
	$hours = floor($mins / 60);
	$days = floor($hours / 24);
	$weeks = floor($days / 7);

	if ($weeks)
		return "$weeks veck" . ($weeks > 1 ? "or" : "a");
	elseif ($days)
		return "$days dag" . ($days > 1 ? "ar" : "");
	elseif ($hours)
		return "$hours timm" . ($hours > 1 ? "ar" : "e");
	elseif ($mins)
		return "$mins minut" . ($mins > 1 ? "er" : "");
	else
		return "< 1 minut";
}

function postTime($t) {
	if (date("Ymd") == date("Ymd", strtotime($t)))
		$c = "i dag";
	elseif (date("Ymd", strtotime("-1 day")) == date("Ymd", strtotime($t)))
		$c = "i går";
	elseif (date("Ymd", strtotime("-2 days")) == date("Ymd", strtotime($t)))
		$c = "i förrgår";
	else
		$c = date("Y-m-d", strtotime($t));
	
	return "$c " . date("H:i", strtotime($t));
}

function sqlesc($x) {
	return "'" . mysql_real_escape_string($x) . "'";
}

function sqlwildcardesc($x) {
	return str_replace(array("%","_"), array("\\%","\\_"), mysql_real_escape_string($x));
}

function deletetopic($id) {
	mysql_query("DELETE FROM posts WHERE topicid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM readposts WHERE topicid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM topics WHERE id = $id") or sqlerr(__FILE__, __LINE__);
}

function deletetorrent($id, $man = true)
{
	global $covers_dir;

	$id = 0 + $id;
	
	$torrent = mysql_query("SELECT name, owner, req FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	$torrent = mysql_fetch_assoc($torrent);
	
	mysql_query("UPDATE snatched SET name = " . sqlesc($torrent["name"]) . " WHERE torrentid = $id") or sqlerr(__FILE__, __LINE__);

	$res = mysql_query("SELECT * FROM uploads WHERE torrentid = $id") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
		mysql_query("DELETE FROM uploads WHERE torrentid = $id") or sqlerr(__FILE__, __LINE__);
	elseif ($man)
		mysql_query("INSERT INTO uploads (torrentid, userid, points) VALUES($id, $torrent[owner], " . (!$torrent["req"] ? -20 : -10) . ")") or sqlerr(__FILE__, __LINE__);
		
	$res = mysql_query("SELECT uid FROM peers WHERE fid = $id") or sqlerr(__FILE__, __LINE__);
	
	$subject = "En länk du har aktiv har blivit borttagen";
	$msg = "Länken [b]$torrent[name][/b] har blivit borttagen och kommer inte längre påverka din statistik på Swepiracy.";
	
	while ($arr = mysql_fetch_assoc($res))
		mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($arr["uid"], get_date_time(), $subject, $msg))) . ")") or sqlerr(__FILE__, __LINE__);
		
	foreach (explode(":", "bookmarks:comments:files:ratings:subs:torrentvotes") AS $x)
		mysql_query("DELETE FROM $x WHERE torrentid = $id") or sqlerr(__FILE__, __LINE__);
		
	mysql_query("DELETE FROM peers WHERE fid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM torrents WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	@unlink("$covers_dir/$id.png");
}

function deleteuser($id) {
	mysql_query("DELETE FROM bets WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM blocks WHERE userid = $id OR blockid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM bonuslog WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM bookmarks WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM cheaters WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM friends WHERE userid = $id OR friendid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM guarded WHERE userid = $id OR posterid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM guardedmovies WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM invites WHERE inviter = $id AND userid = 0") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM iplogg WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM messages WHERE receiver = $id AND saved = 'no'") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM peers WHERE uid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM readposts WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM snatched WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM snatched_links WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM suspected WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM toplists WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM traileradds WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM uploads WHERE userid = $id") or sqlerr(__FILE__, __LINE__);
	mysql_query("DELETE FROM users WHERE id = $id") or sqlerr(__FILE__, __LINE__);
}

function get_date_time($t = 0) {
	if ($t)
		return "" . date("Y-m-d H:i:s", $t) . "";
	else
		return "" . date("Y-m-d H:i:s") . "";
}

function cutStr($str, $len = 100) {
	if (strlen($str) <= $len)
		return $str;

	$end = strpos(substr($str, $len), " ") + $len;

	return substr($str, 0, $end) . "...";
}

function highlight($str, $txt) {
	$offset = 0;
	$len = strlen($str);
	
	while ($offset != strlen($txt))
	{
		$pos = stripos($txt, $str, $offset);
	
		if ($pos !== false)
		{
			$new .= substr($txt, $offset, ($pos - $offset)) . "<span style='color: red;'>" . substr($txt, $pos, $len) . "</span>";
			$offset = $pos + $len;
		}
		else
		{
			$new .= substr($txt, $offset);
			$offset = strlen($txt);
		}
	}
		
	return $new;
}

function begin_frame($head, $width = 0, $clear = false, $align = 'left', $padding = 10) {
	print("<div style='" . ($width ? "width: {$width}px;" : "display: table;") . " text-align: left; margin: 0px auto;'>");

	if ($head)
		print("<h1>$head</h1>");

	print("<div class='" . ($clear ? "clearframe" : "frame") . "' style='padding: {$padding}px; text-align: {$align};'>");
}

function stderr($head = "", $err = "") {
	head();

	print("<div style='width: 500px; text-align: left; margin: 0px auto;'><h2>$head</h2></div>");
	print("<div class='stderr' style='padding: 10px; width: 500px;'>$err</div>");

	foot();
	die;
}

function parked() {
	global $CURUSER;
	
	if ($CURUSER["parked"] == 'yes')
		stderr("Fel", "Du har inte behörighet att besöka denna sida när ditt konto är parkerat");
}

function staffacc($class = UC_MODERATOR) {
	global $CURUSER;
	
	if (get_user_class() < $class)
	{
		stafflog("$CURUSER[username] försökte få tillgång till $_SERVER[REQUEST_URI]");
	
		_404();
	}
}

function stafflog($t) {
	mysql_query("INSERT INTO stafflog (added, txt) VALUES('" . get_date_time() . "', " . sqlesc($t) . ")") or sqlerr(__FILE__, __LINE__);
}

function sqlerr($file = "", $line = "") {
	global $CURUSER, $_SERVER;
	
	$error = mysql_error();
	
	mysql_query("INSERT INTO sqlerrors (userid, added, line, file, error, page) VALUES(" . implode(", ", array_map("sqlesc", array($CURUSER["id"], get_date_time(), $line, $file, $error, $_SERVER["REQUEST_URI"]))) . ")");

	print("<table><tr style='border: none; background-color: blue; text-align: left; padding: 10px; color: white;'><td><h1>SQL Error</h1><strong>" . $error . ($file && $line ? "<p>in $file, line $line</p>" : "") . "</strong></td></tr></table>\n");
	die;
}

function head($title = "") {
	global $CURUSER, $sitename;

	print("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n\n");
	print("<html xmlns=\"http://www.w3.org/1999/xhtml\">\n<head>\n");

	print("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n");
	print("<title>$sitename" . ($title ? " - $title" : "") . "</title>\n");
	print("<link rel=\"icon\" href=\"/favicon.ico\" />\n");
	
	if ($CURUSER)
	{
		$sheet = mysql_query("SELECT url FROM stylesheets WHERE id = $CURUSER[stylesheet]") or sqlerr(__FILE__, __LINE__);
		$sheet = mysql_fetch_assoc($sheet);
		
		$sheet = $sheet["url"];
	}
	
	if (!$sheet)
	{
		$sheet = mysql_query("SELECT url FROM stylesheets WHERE id = 1") or sqlerr(__FILE__, __LINE__);
		$sheet = mysql_fetch_assoc($sheet);
		
		$sheet = $sheet["url"];
	}
	
	print("<link rel=\"stylesheet\" type=\"text/css\" href=\"/$sheet\" />\n");

	print("<script type=\"text/javascript\" src=\"/jquery-1.7.2.min.js\"></script>\n");
	print("<script type=\"text/javascript\" src=\"/jquery-ui-1.8.21.custom.min.js\"></script>\n");
	print("<script type=\"text/javascript\" src=\"/jqueries.js\"></script>\n");
	
	if (get_user_class() >= UC_MODERATOR)
		print("<script type=\"text/javascript\" src=\"/staffjqueries.js\"></script>\n");

	print("</head>\n\n<body>\n");
	
	if ($CURUSER)
	{
		if (get_user_class() >= UC_POWER_USER && time() > strtotime("+7 days", strtotime($CURUSER["seedbonus_update"])))
		{
			$bonuspoints = 0;
			
			$uploaded = $CURUSER["uploaded"] - $CURUSER["seedbonus_uploaded"];
			$seedbonus = $uploaded / 1073741824;

			if ($seedbonus <= 10)
				$seedbonus *= 2;
			elseif($seedbonus > 10 && $seedbonus <= 20)
				$seedbonus = 20 + (($seedbonus - 10) * 1);
			elseif($seedbonus > 20 && $seedbonus <= 40)
				$seedbonus = 30 + (($seedbonus - 20) * 0.5);
			elseif($seedbonus > 40)
				$seedbonus = 40;
				
			if ($seedbonus)
			{
				$bonus[] = "<strong>" . round($seedbonus) . "p</strong> för " . mksize($uploaded) . " uppladdat";
				$bonuspoints += round($seedbonus);
			}

			$trails = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM traileradds WHERE userid = $CURUSER[id]"));
			$trailerbonus = $trails[0] * 2;
			
			if ($trailerbonus)
			{
				$bonus[] = "<strong>{$trailerbonus}p</strong> för $trails[0] " . ($trails[0] == 1 ? "trailer" : "trailers");
				$bonuspoints += $trailerbonus;
			}

			$uploadsplus = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS uploads, SUM(points) AS points FROM uploads WHERE userid = $CURUSER[id] AND points > 0"));
			$uploadsminus = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS uploads, SUM(points) AS points FROM uploads WHERE userid = $CURUSER[id] AND points < 0"));
			$torrentbonus = $uploadsplus["points"] + $uploadsminus["points"];
			$torrents = $uploadsplus["uploads"];
			
			if ($torrentbonus)
			{
				$bonus[] = "<strong>{$torrentbonus}p</strong> för $torrents " . ($torrents == 1 ? "uppladdad torrent" : "uppladdade torrents");
				$bonuspoints += $torrentbonus;
			}

			$seedtime = $CURUSER["seedtime"] - $CURUSER["seedbonus_seedtime"];
			$timebonus = round(($seedtime / 3600) * 0.01);
			
			if ($timebonus)
			{
				$bonus[] = "<strong>{$timebonus}p</strong>  för " . mkprettytime($seedtime) . " seed";
				$bonuspoints += $timebonus;
			}
		
			$onlinetime = $CURUSER["time_online"] - $CURUSER["time_online_week"];
			$onlinehours = round($onlinetime / 3600, 1);
			$onlinebonus = $onlinehours * 10;
			
			if ($onlinebonus)
			{
				$bonus[] = "<strong>{$onlinebonus}p</strong> för $onlinehours h online";
				$bonuspoints += $onlinebonus;
			}
		
			if ($postbonus = $CURUSER["posts_week"])
			{
				$bonus[] = "<strong>{$postbonus}p</strong> för $CURUSER[posts_week] inlägg";
				$bonuspoints += $postbonus;
			}
			
			$dt = get_date_time();
			
			if (!$bonuspoints)
				mysql_query("UPDATE users SET seedbonus_update = '$dt', seedbonus_uploaded_last = 0 WHERE id = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
			else
			{
				$bonuscomment = "Du har inkasserat <strong>{$bonuspoints}p</strong> (" . implode(", ", $bonus) . ")";
				mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '$dt', " . sqlesc($bonuscomment) . ")") or sqlerr(__FILE__, __LINE__);
				
				mysql_query("UPDATE users SET seedbonus = seedbonus + $bonuspoints, seedbonus_update = '$dt', seedbonus_uploaded = $CURUSER[uploaded], seedbonus_uploaded_last = $uploaded, seedbonus_seedtime = $CURUSER[seedtime], time_online_week = $CURUSER[time_online], posts_week = 0 WHERE id = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
				mysql_query("DELETE FROM traileradds WHERE userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
				mysql_query("DELETE FROM uploads WHERE userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
			}
		}
		
		if ($CURUSER["downloaded"])
		{
			$ratio = $CURUSER['uploaded'] / $CURUSER['downloaded'];
			$ratio = number_format($ratio, 3);
			$color = get_ratio_color($ratio);

		} elseif ($CURUSER["uploaded"])
			$ratio = "Inf.";
		else
			$ratio = "---";
	
		print("<div id='statusbar'><span style='float: left;'>Välkommen, <a href='/userdetails.php?id=$CURUSER[id]'>$CURUSER[username]</a>" . usericons($CURUSER["id"]) . " (" . get_user_class_name($CURUSER["class"]) . ")</span>Ratio: $ratio <img src='/pic/uploaded.gif' style='margin-left: 10px; vertical-align: top;' />" . mksize($CURUSER["uploaded"]) . "  <img src='/pic/downloaded.gif' style='margin-left: 10px; vertical-align: top;' />" . mksize($CURUSER["downloaded"]) . (get_user_class() >= UC_POWER_USER ? " <img src='/pic/bonussmall.png' style='margin-left: 10px;' /> " . number_format($CURUSER["seedbonus"], 0, ".", " ") . " p" : ""));
	
		$stat[] = "<a href='/messages.php'><img src='/pic/messages.png' title='Inkorg' /></a>";
		$stat[] = "<a href='/my.php'><img src='/pic/editprof.png' title='Ändra profil' /></a>";
		$stat[] = "<a href='/users.php'><img src='/pic/searchuser.png' title='Sök användare' /></a>";
		$stat[] = "<a href='/friends.php'><img src='/pic/friendlist.png' title='Vänner' /></a>";
		$stat[] = "<a href='/bookmarks.php'><img src='/pic/bookmarks.png' title='Bokmärken' /></a>";
		$stat[] = "<a href='/getrss.php'><img src='/pic/rss.png' title='RSS' /></a>";
		$stat[] = "<a href='/span.php'><img src='/pic/movieguard.png' title='Bevaka filmer' /></a>";
		$stat[] = "<a href='/logout.php' style='margin-left: 30px;'><img src='/pic/logout.png' title='Logga ut' /></a>";
	
		$statico = implode("<span class='statspacer'>&nbsp;</span>", $stat);
	
		print("<span style='float: right;'>$statico</span></div>\n");
	}
	
	$res = mysql_query("SELECT * FROM doodles ORDER BY added DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);
	
	if ($arr)
	{
		$src = "/getdoodle.php/$arr[name]";
		$link = $arr["url"];
	}
	else
	{
		$src = "/pic/logo_orig.png";
		$link = "index.php";
	}
		
	print("<div id='header'>\n");
	
	print("<img src='$src' style='z-index: 0; cursor: pointer;' onClick=\"window.location.href = '/$link';\" />\n");
	
	if ($CURUSER)
	{
		if ($CURUSER["freeleech"] > get_date_time())
			print("<div style='width: 200px; position: absolute; bottom: 10px; left: 5px; text-align: left; font-size: 7pt; color: red;'>Fri leech till " . date("j/n H:i", strtotime($CURUSER["freeleech"])) . "</div>\n");
	
		$donated = mysql_query("SELECT value FROM autovalues WHERE name = 'donated'") or sqlerr(__FILE__, __LINE__);
		$donated = mysql_fetch_assoc($donated);
		$max = $donated["value"];
	
		$donation = mysql_query("SELECT value FROM autovalues WHERE name = 'donations'") or sqlerr(__FILE__, __LINE__);
		$donation = mysql_fetch_assoc($donation);
		$donations = $donation["value"];
	
		$donratio = round($donations / $max, 2) * 100;
		$donratio = $donratio > 100 ? 100 : $donratio;
	
		$dayratio = round(date("j") / 30, 2) * 100;
		$dayratio = $dayratio > 100 ? 100 : $dayratio;
	
		$color = $donratio < $dayratio ? "red" : "#717171";
	
		print("<div style='width: 100px; position: absolute; bottom: 10px; right: 5px; text-align: left;'>\n");
	
		print("<span class='small' style='color: {$color};'><a href='/donate.php' style='font-weight: normal;'>Donationer: {$donratio}%</a></span>");
		print("<div style='height: 2px; background-color: #d7d7d7; padding: 1px;'>");
		print("<div style='width: {$donratio}px; height: 2px; background-color: #a8a4a5;'></div>");
		print("</div>\n</div>\n");
	}
	
	print("</div>\n");
	print("<div id='body'>\n");
	print("<div id='leftfill'></div>\n");
	print("<div id='rightfill'></div>\n");

	$url = $_SERVER["REQUEST_URI"];
	
	print("<ul class='nav'>\n");

	if ($CURUSER)
		$menus = array("Hem" => "/index.php", "Bläddra" => "/browse.php", "Requests" => "/requests.php", "Ladda upp" => "/upload.php", "Forum" => "/forums.php", "Invite" => "/invite.php", "Bonus" => "/bonus.php", "StakeBet" => "/bet.php", "Logg" => "/log.php", "Topplistor" => "/topten.php", "Support" => "/support.php", "Staff" => "/staff.php", "<span style='color: red;'>Donera</span>" => "/donate.php");
	else
		$menus = array("Login" => "/login.php", "Recover" => "/recover.php", "Apply" => "/apply.php", "Terms of Use" => "/useragreement.php");

	foreach ($menus AS $menu => $link)
		print("<li" . (strpos($url, $link) !== false || $url == '/' && $link == '/index.php' ? " class='cur'" : "") . "><a href='$link'>$menu</a></li>\n");
		
	if (get_user_class() >= UC_MODERATOR)
		print("<li style='float: right;'>" . ($_COOKIE["staffnav"] ? "<img id='staffmenu' src='/pic/minus.gif' alt='hide' />" : "<img id='staffmenu' src='/pic/plus.gif' alt='show' />") . "</li>\n");

	print("</ul>\n");

	if (get_user_class() >= UC_MODERATOR)
	{
		print("<ul id='staffnav'" . (!$_COOKIE["staffnav"] ? " style='display: none;'" : "") . ">\n");
		
		$unreadmess = mysql_query("SELECT COUNT(*) FROM staffmessages WHERE staffid = 0") or sqlerr(__FILE__, __LINE__);
		$unreadmess = mysql_fetch_row($unreadmess);
		
		$unreadrep = mysql_query("SELECT COUNT(*) FROM reports WHERE solvedby = 0") or sqlerr(__FILE__, __LINE__);
		$unreadrep = mysql_fetch_row($unreadrep);
		
		$applications = mysql_query("SELECT COUNT(*) FROM applications WHERE accepted = 'pending'") or sqlerr(__FILE__, __LINE__);
		$applications = mysql_fetch_row($applications);
	
		$staffmenus = array("Banna" => array("/bans.php", UC_MODERATOR), "Forumhanteraren" => array("/forumedit.php", UC_SYSOP), "Inloggningsförsök" => array("/logins.php", UC_SYSOP), "Massmeddelande" => array("/staffmess.php", UC_SYSOP), "Skapa konto" => array("/adduser.php", UC_MODERATOR), ($unreadmess[0] ? "<span style='color: red;'>Staffmeddelanden ($unreadmess[0])</span>" : "Staffmeddelanden") => array("/staffbox.php", UC_MODERATOR), "SQL-fel" => array("/sqlerrors.php", UC_SYSOP), "Donationer" => array("/donations.php", UC_SYSOP), "Samma IP" => array("/ipcheck.php", UC_MODERATOR), "Invites" => array("/invited.php", UC_MODERATOR), "Omröstningar" => array("/polloverview.php", UC_MODERATOR), ($unreadrep[0] ? "<span style='color: red;'>Rapporter ($unreadrep[0])</span>" : "Rapporter") => array("/reports.php", UC_MODERATOR), "Utstickande" => array("/warned.php", UC_MODERATOR), "Skapa doodle" => array("/addlogo.php", UC_SYSOP), "Bonusalternativ" => array("/bonusedit.php", UC_SYSOP), "Banna ord" => array("/wordban.php", UC_SYSOP), "Bevaka ord" => array("/wordguard.php", UC_SYSOP), "Staff-IP" => array("/staffip.php", UC_SYSOP), "Stafflogg" => array("/stafflog.php", UC_SYSOP), "Kategorihanteraren" => array("/catedit.php", UC_SYSOP), ($applications[0] ? "<span style='color: red;'>Medlemsansökningar ($applications[0])</span>" : "Medlemsansökningar") => array("/applications.php", UC_MODERATOR));
	
		foreach ($staffmenus AS $menu => $link)
		{
			if (get_user_class() < $link[1])
				continue;
				
			print("<li><a href='$link[0]'>$menu</a></li>\n");
		}
	
		print("</ul>\n");
	}
	
	print("<div id='page'>\n");
	print("<div id='shadow'></div><div id='background'></div><div id='popup'></div>\n");
	
	if ($CURUSER)
	{
		$unread = mysql_query("SELECT COUNT(*) FROM messages WHERE receiver = $CURUSER[id] AND `read` = 0 AND location = 1") or sqlerr(__FILE__, __LINE__);
		$unread = mysql_fetch_row($unread);
	
		if ($unread[0])
			print("<div id='newmess'><a href='/messages.php'>Du har $unread[0] oläst" . ($unread[0] > 1 ? "a" : "") . " meddelande" . ($unread[0] > 1 ? "n" : "") . "</a></div>\n");
		elseif ($CURUSER["unreadnews"])
			print("<div id='newmess'><a href='/index.php?viewnews=1'>Det finns $CURUSER[unreadnews] oläst" . ($CURUSER["unreadnews"] > 1 ? "a" : "") . " nyhet" . ($CURUSER["unreadnews"] > 1 ? "er" : "") . "</a></div>\n");
	}
}

function CutName($s, $l)
{	
	if (strlen($s) <= $l)
		return $s;
		
	return substr($s, 0, $l) . "...";
}

function get_pre_time($name)
{
	$release = str_replace("-", "\-", $name);
	
	$data = file_get_contents("http://orlydb.com/?q=\"$release\"");
	
	if (preg_match("#class=\"timestamp\">([^<]+)#i", $data, $matches))
		return get_date_time(strtotime("+1 hour", strtotime($matches[1])));
	else
		return false;
}

function foot() {
	print("</div>\n");
	
	$links = array("FAQ" => "/faq.php", "Regler" => "/rules.php", "Doodlar" => "/doodles.php", "Legal" => "/legal.php");
	
	$link = "";
	foreach ($links AS $name => $page)
		$link .= "<a href='$page' style='margin-right: 10px;'>$name</a>";
	
	print("<div id='bottommeny'><div style='float: left;'>$link</div><span class='small'>2006 - " . date("Y") . "</span></div>\n");
	print("</div></body></html>");
	die;
}

include("notifo.php");

function notifo_send($username, $subject, $body, $uri)
{
	$notifo = new Notifo_API("Swepiracy.org", "4f4eb3c1057e25fe7a5ce22c8be4e100f22e6194");

	$params = array("to" => "{$username}",
	"label" => "Dictionary",
	"title" => "{$subject}",
	"msg" => "{$body}",
	"uri" => "http://www.swepiracy.org/{$uri}");

	$response = $notifo->sendNotification($params);
	
	return $response;
}

function notifo_subscribe($username)
{
	$notifo = new Notifo_API("Swepiracy.org", "4f4eb3c1057e25fe7a5ce22c8be4e100f22e6194");

	$response = $notifo->subscribeUser($username);
	
	return $response; 
}

function commenttable($rows) {
	global $CURUSER;

	$count = 0;
	foreach ($rows as $row)
	{
		$username = $row["username"];

		print("<p style='width: 640px; margin: 10px auto 5px auto; text-align: left;'>#" . $row["id"] . " av ");
		
		if ($row["username"])
		{
			if (get_user_class() >= UC_MODERATOR || $row["anonymous"] == 'no' || $row["userid"] != $row["owner"])
				print("<a name='comm$row[id]' href='userdetails.php?id=$row[userid]'>$row[username]</a>" . usericons($row["userid"]) . " (" . ($row["title"] ? $row["title"] : get_user_class_name($row["class"])) . ")\n");
			else
				print("<em>Anonym uppladdare</em>\n");
		}
		else
			print("<a name='comm" . $row["id"] . "'><em>Borttagen</em></a>\n");

		print(" - $row[added]" . ($row["userid"] == $CURUSER["id"] || get_user_class() >= UC_MODERATOR ? " - [<a href='comment.php?action=edit&amp;cid=$row[id]'>Ändra</a>]" : "") . " - [<a class='jlink' onclick=\"report($row[id], 'comment')\">Rapportera</a>]" . (get_user_class() >= UC_MODERATOR ? " - [<a href='comment.php?action=delete&amp;cid=$row[id]'>Radera</a>]" : "") . ($row["editedby"] && get_user_class() >= UC_MODERATOR ? " - [<a href='comment.php?action=vieworiginal&amp;cid=$row[id]'>Visa original</a>]" : "") . "</p>\n");

		if (($row["userid"] == $row["owner"] && $row["anonymous"] == 'yes') || ($CURUSER["show_avatars"] == 'notbad' && $row["bad_avatar"] == 'yes') || $CURUSER["show_avatars"] == 'no')
			$avatar = "/pic/default_avatar.jpg";
		else
			$avatar = htmlspecialchars($row["avatar"]);

		if (!$avatar)
			$avatar = "/pic/default_avatar.jpg";

		print("<table>\n");
		print("<tr><td style='width: 150px; padding: 0px; vertical-align: top;'><img src='$avatar' style='width: 150px;' /></td><td style='width: 480px; vertical-align: top;'>" . format_comment($row["body"]) . "</td></tr>\n");
		print("</table>\n");
	}
}

function subflag($sub)
{
	switch ($sub)
	{
		case 'swe':
			return 'Svenska';
			break;
		case 'eng':
			return 'English';
			break;
		case 'nor':
			return 'Norsk';
			break;
		case 'dan':
			return 'Dansk';
			break;
		case 'fin':
			return 'Suomeksi';
			break;
	}
}

function torrenttable ($res, $variant = "index", $del = false, $req = false, $archive = false)
{
	global $pic_base_url, $CURUSER;

	if ($archive == true)
		$last_browse = $CURUSER["last_reqbrowse"];
	else
		$last_browse = $CURUSER["last_browse"];

	print("<table><tr>\n");

	$count_get = 0;

	foreach ($_GET as $get_name => $get_value)
	{
		if ($get_name != "sort" && $get_name != "type")
		{
			if ($count_get)
				$oldlink = $oldlink . "&amp;" . $get_name . "=" . $get_value;
			else
				$oldlink = $oldlink . $get_name . "=" . $get_value;
			$count_get++;
		}
	}

	if ($count_get > 0)
		$oldlink = $oldlink . "&amp;";

	if ($_GET['sort'] == "1") {
		if ($_GET['type'] == "desc")
			$link1 = "asc";
		else
			$link1 = "desc";
	}

	if ($_GET['sort'] == "2") {
		if ($_GET['type'] == "desc")
			$link2 = "asc";
		else
			$link2 = "desc";
	}

	if ($_GET['sort'] == "3") {
		if ($_GET['type'] == "desc")
			$link3 = "asc";
		else
			$link3 = "desc";
	}

	if ($_GET['sort'] == "4") {
		if ($_GET['type'] == "desc")
			$link4 = "asc";
		else
			$link4 = "desc";
	}

	if ($_GET['sort'] == "5") {
		if ($_GET['type'] == "desc")
			$link5 = "asc";
		else
			$link5 = "desc";
	}

	if ($_GET['sort'] == "6") {
		if ($_GET['type'] == "desc")
			$link6 = "asc";
		else
			$link6 = "desc";
	}

	if ($_GET['sort'] == "7") {
		if ($_GET['type'] == "desc")
			$link7 = "asc";
		else
			$link7 = "desc";
	}

	if ($_GET['sort'] == "8") {
		if ($_GET['type'] == "desc")
			$link8 = "asc";
		else
			$link8 = "desc";
	}

	if ($_GET['sort'] == "9") {
		if ($_GET['type'] == "desc")
			$link9 = "asc";
		else
			$link9 = "desc";
	}

	if ($_GET['sort'] == "10") {
		if ($_GET['type'] == "desc")
			$link10 = "asc";
		else
			$link10 = "desc";
	}

	if ($_GET['sort'] == "11") {
		if ($_GET['type'] == "desc")
			$link11 = "asc";
		else
			$link11 = "desc";
	}

	if ($link1 == "")
		$link1 = "asc";
	if ($link2 == "")
		$link2 = "desc";
	if ($link3 == "")
		$link3 = "desc";
	if ($link4 == "")
		$link4 = "desc";
	if ($link5 == "")
		$link5 = "desc";
	if ($link6 == "")
		$link6 = "desc";
	if ($link7 == "")
		$link7 = "desc";
	if ($link8 == "")
		$link8 = "desc";
	if ($link9 == "")
		$link9 = "desc";
	if ($link10 == "")
		$link10 = "desc";
	if ($link11 == "")
		$link11 = "desc";

	$linky = "browse.php";

	print("<th class='center'>Typ</th>\n");
	print("<th colspan=2><a href='" . $linky . "?" . $oldlink . "sort=1&amp;type=" . $link1 . "'>Namn</a></th>\n");

	if ($variant == "mytorrents")
	{
		print("<th class='center'>Ändra</th>\n");
		print("<th class='center'>Synlig</th>\n");
	}

	print("<th class='center'>DL</th>\n");
	print("<th class='center'>i</th>\n");

	if ($variant == "index")
		print("<th class='center'>BM</th>\n");

	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=2&amp;type=" . $link2 . "'>F</a></th>\n");
	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=3&amp;type=" . $link3 . "'>K</a></th>\n");
	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=10&amp;type=" . $link10 . "'>IMDb</a></th>\n");
	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=5&amp;type=" . $link5 . "'>Storlek</a>" . (get_user_class() >= UC_POWER_USER ? " / <a href='" . $linky . "?" . $oldlink . "sort=6&amp;type=" . $link6 . "'>DL</a>" : "") . "</th>\n");
	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=4&amp;type=" . $link4 . "'>Tillagd</a></th>\n");
	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=7&amp;type=" . $link7 . "'><img src='/pic/arrow_up_svart.gif' alt='Sort' /></a></th>\n");
	print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=8&amp;type=" . $link8 . "'><img src='/pic/arrow_down_svart.gif' alt='Sort' /></a></th>\n");

	if ($variant == "index")
		print("<th class='center'><a href='" . $linky . "?" . $oldlink . "sort=9&amp;type=" . $link9 . "'>Upplagd av</a></th>\n");

	if ($variant == "bookmarks")
		print("<th class='center'>X</th>\n");

	$torrentsperpage = $CURUSER["torrentsperpage"];
	
	if (!$torrentsperpage)
		$torrentsperpage = 15;

	foreach ($res AS $row)
	{
		$id = $row["id"];

		print("<tr " . ($row["freeleech"] == 'yes' || $CURUSER["freeleech"] > get_date_time() ? "onMouseOver=\"this.style.backgroundColor='#ccffff'\" onMouseOut=\"this.style.backgroundColor='#ccffcc'\" style='background-color: #ccffcc;'" : "onMouseOver=\"this.style.backgroundColor='#e8e8e8'\" onMouseOut=\"this.style.backgroundColor='#ffffff'\" style='background-color: #ffffff;'") . ">");

		print("<td style='padding: 0px'>");

		$cat = mysql_query("SELECT categories.*, cattype.name AS cattype FROM categories LEFT JOIN cattype ON categories.cattype = cattype.id WHERE categories.id = $row[category]") or sqlerr(__FILE__, __LINE__);
		$cat = mysql_fetch_assoc($cat);

		if ($cat["name"])
			print("<a href='$linky?cat=" . $row["category"] . "'><img src='" . $cat["image"] . "' alt='" . $cat["name"] . "' /></a>");
		else
			print("-");
			
		print("</td>\n");

		$char = 60;
		$dispname = htmlspecialchars($row["name"]);
		$dispname = CutName($dispname, $char);

		$highlight = explode(" ", urldecode($_GET["search"]));

		/*foreach ($highlight as $soktext)
		{
			if ($soktext)
				$dispname = highlight($soktext, $dispname);
		}*/

		//$cover = ((file_exists("../covers/" . $id . ".png") || file_exists("../imdbpiclarge/" . $id . ".jpg")) && $CURUSER["visacover"] == 'yes' ? " onmouseover=\"domTT_activate(this, event, 'content', '<img width=200 src=getcover.php/" . $id . ".png />', 'trail', true, 'delay', 0);\"" : "");

		print("<td class='nowrap' style='width: 500px; border-right: 0px;'><a title='" . $row["name"] . "' href='details.php?id=$id");

		if ($variant == "mytorrents")
			print("&amp;returnto=" . urlencode($_SERVER["REQUEST_URI"]));

		if ($variant == "index")
			print("&amp;hit=1");
		
		if ($row["imdb_genre"])
		{
			$genres = explode(" / ", $row["imdb_genre"]);
			$gen = array();

			foreach ($genres AS $genre)
				$gen[] = "<a href='" . $linky . "?" . $oldlink . "genre=" . htmlspecialchars($genre) . "' style='font-weight: normal;'>$genre</a>";

			$addgen = "<span class='small'>" . implode(" / ", $gen) . "</span>";
		}
		elseif (!$row["req"]) 
		{
			$addgen = "<span style='color: gray;'>";
			
			if ($row["pretime"] == '0000-00-00 00:00:00') 
				$addgen .= "<em>Ingen pre kunde hittas</em>";
			else
			{
				if (strtotime($row["added"]) > strtotime($row["pretime"]))
					$addgen .= get_elapsed_time_all(strtotime($row["added"]), strtotime($row["pretime"])) . " efter pre";
				else
					$addgen .= get_elapsed_time_all(strtotime($row["pretime"]), strtotime($row["added"])) . " innan pre";
			}
				
			$addgen .= "</span>";
		}
		elseif ($row["req"] == 1)
		{
			$req = mysql_query("SELECT added FROM requests WHERE id = $row[reqid]") or sqlerr(__FILE__, __LINE__);
			
			if ($req = mysql_fetch_assoc($req))
				$addgen = "<span style='color: gray;'>" . get_elapsed_time_all(strtotime($row["added"]), strtotime($req["added"])) . " efter request</span>";
		}
		else
			$addgen = "";

		$new = $row["added"] > $last_browse ? " <span style='font-size: 10pt; font-weight: bold; color: red;'>NY</span>" : "";
		$request = $row["req"] == 1 && $_GET["browse"] != 'requests' ? " <span style='font-size: 10pt; font-weight: bold; color: red;'>REQ</span>" : "";
		$nuked = $row["nuked"] == 'yes' ? "<img src='/pic/nuked.gif' alt='Nuked' title='$row[nukedreason]' />" : "";
		$p2p = $row["p2p"] == 'yes' ? " <img src='/pic/p2p.png' alt='p2p' title='p2p-release' style='vertical-align: text-bottom;' />" : "";

		$cattype = $cat["cattype"];
		$lang = "";
		$lan = "";
		if ($cattype == 'HD')
		{
			$subs = mysql_query("SELECT * FROM subs WHERE torrentid = $row[id]") or sqlerr(__FILE__, __LINE__);
			if (mysql_num_rows($subs))
			{
				$lan = array();
				while ($sub = mysql_fetch_assoc($subs))
				{
					$lang = subflag($sub["lang"]);
					$lan[] = "<a href='sub.php/$sub[id]/$sub[file]'><img src='/pic/subflags/small/{$lang}.png' title='$sub[file]' /></a>";
				}
			}

			foreach (explode(",", $row["subs"]) as $sub)
			{
				if ($lang = subflag($sub))
					$lan[] = "<img src='/pic/subflags/small/{$lang}.png' title='Sub integr.' />";
			}

			if ($lan)
				$lang = implode(" ", $lan);
		}

		print("'><span style='font-size: 10pt;'" . ($row["imdb_poster"] && $CURUSER["show_covers"] == 'yes' ? " class='$row[imdb_poster]'" : "") . ">" . $dispname . "</span></a>" . $p2p . $request . $new . "<br />$addgen</td>");

		print("<td style='text-align: right; border-left: 0px;'>" . $lang . $nuked . "</td>\n");

		if ($variant == "mytorrents")
			print("<td class='center'><a href='edit.php?returnto=" . urlencode($_SERVER["REQUEST_URI"]) . "&amp;id=" . $row["id"] . "'>Ändra</a></td>\n");

		if ($variant == "mytorrents")
			print("<td class='center'>" . ($row["visible"] == 'no' ? "<strong>Nej</strong>" : "Ja") . "</td>\n");

		$seed = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM peers WHERE fid = $row[id] AND uid = $CURUSER[id] AND `left` = 0 AND active = 1"));

		print("<td class='center'><a target='_blank' href='download.php/$id'>" . ($seed[0] ? "<img src='/pic/magnets.gif' alt='Ladda ner' />" : "<img src='/pic/magnet.gif' alt='Ladda ner' />") . "</a></td>\n");
		print("<td class='center'><a class='jlink' onClick=\"loadPage('details.php?id=$id&amp;type=nfo')\"><img src='/pic/nfo.png' alt='i' title='.nfo' /></a></td>\n");
		
		if ($variant == "index")
		{
			$bokmarkt = mysql_query("SELECT * FROM bookmarks WHERE userid = $CURUSER[id] and torrentid = $id LIMIT 1") or sqlerr(__FILE__, __LINE__, $_SERVER[REQUEST_URI]);

			print("<td class='center'><div id=\"b{$id}\"><a class='jlink' onclick='bookmark($id)'>" . (mysql_num_rows($bokmarkt) ? "<img src='/pic/bok2.gif' alt='Radera bokmärke' />" : "<img src='/pic/bok.gif' alt='Bokmärk' />") . "</a></div></td>\n");
		}

		if ($row["type"] == "single")
			print("<td class='center'>" . $row["numfiles"] . "</td>\n");
		else {
			if ($variant == "index")
				print("<td class='center'><a href='details.php?id=$id&amp;hit=1&amp;files=1'>" . $row["numfiles"] . "</a></td>\n");
			else
				print("<td class='center'><a href='details.php?id=$id&amp;hit=1&amp;files=1'>" . $row["numfiles"] . "</a></td>\n");
		}

		if (!$row["comments"])
			print("<td class='center'>" . $row["comments"] . "</td>\n");
		else {
			if ($variant == "index")
				print("<td class='center'><a href='details.php?id=$id&amp;hit=1&amp;tocomm=1'>" . $row["comments"] . "</a></td>\n");
			else
				print("<td class='center'><a href='details.php?id=$id&amp;page=0#startcomments'>" . $row["comments"] . "</a></td>\n");
		}

		if ($row["imdb_rating"] > 0.0)
			print("<td class='center'><a target='_blank' href='http://www.imdb.com/title/$row[imdb]/'>" . $row["imdb_rating"] . "</a></td>\n");
		else
			print("<td class='center'>n/a</td>\n");

		if (get_user_class() >= UC_MODERATOR)
			$snatches = "<a href=viewsnatches.php?id=$row[id]>" . number_format($row["times_completed"]) . "</a>";
		else
			$snatches = number_format($row["times_completed"]);

		print("<td class='center'>" . mksize($row["size"]) . (get_user_class() >= UC_POWER_USER ? "<br />/ <strong>$snatches x</strong>" : "") . "</td>\n");
		print("<td class='center'>" . str_replace(" ", "<br />", $row["added"]) . "</td>\n");

		if ($row["seeders"])
		{
			if ($row["leechers"])
				$ratio = $row["seeders"] / $row["leechers"];
			else
				$ratio = 1;

			print("<td class='center'><a href='details.php?id=$id&amp;hit=1&amp;peers=1&amp;toseeders=1'><span style='color: " . get_slr_color($ratio) . ";'>" . $row["seeders"] . "</span></a></td>\n");
		}
		else
			print("<td class='center'><span style='color: red;'>" . $row["seeders"] . "</span></td>\n");

		if ($row["leechers"])
			print("<td class='center'><a href='details.php?id=$id&amp;hit=1&amp;peers=1&amp;todlers=1'>" . number_format($row["leechers"]) . "</a></td>\n");
		else
			print("<td class='center'>0</td>\n");

		if ($variant == "index")
		{
			if ($row["anonymous"] == 'yes')
			{
				if (get_user_class() < UC_MODERATOR)
					print("<td class='center'><em>Anonym</em></td>\n");
				else
					print("<td class='center'>(" . (!$row["username"] ? "<em>Borttagen</em>" : "<a href='userdetails.php?id=$row[owner]'>" . $row["username"] . "</a>") . ")");
			} else
				print("<td class='center'>" . ($row["username"] ? "<a href='userdetails.php?id=" . $row["owner"] . "'>" . $row["username"] . "</a>" : "<em>Borttagen</em>") . "</td>\n");
		}

		if ($variant == "bookmarks")
			print("<td class='center'><input type='checkbox' name='del[]' value=$row[bookmarkid] /></td>\n");

		print("</tr>\n");
	}
	print("</table>\n");
}

function Toppen($edit = false)
{
	global $CURUSER;
	
	$count = 0;
	for ($order = 1; $order <= 3; $order++)
	{
		switch ($order)
		{
			case 1:
				$params = array(2, 1, 1, 1, 0);
				break;
			case 2:
				$params = array(2, 1, 2, 1, 0);
				break;
			case 3:
				$params = array(2, 1, 1, 2, 0);
				break;
		}
		
		list($time, $lang, $type, $section, $sort) = $params;
	
		$genre = array();
		$edited = false;

		$res = mysql_query("SELECT * FROM toplists WHERE userid = $CURUSER[id] AND `order` = $order") or sqlerr(__FILE__, __LINE__);

		if ($arr = mysql_fetch_assoc($res))
		{
			$time = $arr["time"];
			$lang = $arr["lang"];
			$type = $arr["type"] ? explode(",", $arr["type"]) : array();
			$section = $arr["section"];
			$sort = $arr["sort"];
			$genre = $arr["genre"] ? explode(",", $arr["genre"]) : array();

			$edited = true;
			
			if ($arr["disabled"] && !$edit)
				continue;
		}
		
		$disabled = $arr["disabled"] ? true : false;

		switch ($time)
		{
			case 1:
				$timelap = 1;
				break;
			case 2:
				$timelap = 7;
				break;
			case 3:
				$timelap = 30;
				break;
			default:
				$timelap = 0;
		}

		switch ($type)
		{
			case 1:
				$cattype = "DVDr";
				break;
			case 2:
				$cattype = "HD";
				break;
			default:
				$cattype = "";
		}

		switch ($section)
		{
			case 1:
				$sect = 0;
				break;
			case 2:
				$sect = 2;
				break;
			case 3:
				$sect = 1;
				break;
			default:
				$sect = 3;
		}

		switch ($sort)
		{
			case 1:
				$sorts = "torrents.times_completed";
				break;
			case 2:
				$sorts = "torrents.added";
				break;
			case 3:
				$sorts = "RAND()";
				break;
			default:
				$sorts = "torrents.imdb_rating";
		}
	
		if ($edit)
		{
			$a = "<select name='time'>";
			$ar = array("-Alla-", "Dagens", "Veckans", "Månadens");

			foreach ($ar AS $k => $r)
				$a .= "<option value=$k" . ($time == $k ? " selected" : "") . ">$r</option>";

			$a .= "</select>";

			$p = "<select name='sort'>";
			$ar = array("Bästa", "Populäraste", "Nyaste", "Slumpade");

			foreach ($ar AS $k => $r)
				$p .= "<option value=$k" . ($sort == $k ? " selected" : "") . ">$r</option>";

			$p .= "</select>";


			$s = "<select name='lang'>";
			$ar = array("-Alla språk-", "Svenskrelaterade");

			foreach ($ar AS $k => $r)
				$s .= "<option value=$k" . ($lang == $k ? " selected" : "") . ">$r</option>";

			$s .= "</select>";


			$t = "<select name='type[]' size=3 multiple style='vertical-align: text-top;'>";
			$t .= "<option value=0" . (!$type ? " selected" : "") . ">-Alla format-</option>";
	
			$cattypes = mysql_query("SELECT id, name FROM cattype WHERE id IN(1, 2) ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	
			while ($ctype = mysql_fetch_assoc($cattypes))
			{
				$t .= "<optgroup label='$ctype[name]'>";
	
				$cats = mysql_query("SELECT id, name FROM categories WHERE cattype = $ctype[id] AND name != 'TV' ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
		
				while ($cat = mysql_fetch_assoc($cats))
					$t .= "<option value=$cat[id]" . ($edited ? (in_array($cat["id"], $type) ? " selected" : "") : ($ctype["name"] == $cattype ? " selected" : "")) . ">$cat[name]</option>";
		
				$t .= "</optgroup>";
			}

			$t .= "</select>";


			$e = "<select name='section'>";
			$ar = array("-Alla sektioner-", "Nytt", "Arkiv", "Requests");

			foreach ($ar AS $k => $r)
				$e .= "<option value=$k" . ($section == $k ? " selected" : "") . ">$r</option>";

			$e .= "</select>";


			$g = "<select name='genre[]' size=3 multiple style='vertical-align: text-top;'>";
			$g .= "<option value=''" . (!$genre ? " selected" : "") . ">-Alla genres-</option>";

			$res = mysql_query("SELECT torrents.imdb_genre FROM torrents INNER JOIN categories ON torrents.category = categories.id INNER JOIN cattype ON categories.cattype = cattype.id WHERE cattype.name IN('DVDr', 'HD') LIMIT 100") or sqlerr(__FILE__, __LINE__);
			$genres = array();

			while ($arr = mysql_fetch_assoc($res))
				$genres = array_merge($genres, array_map("trim", explode(" / ", $arr["imdb_genre"])));

			$genres = array_filter($genres);
			sort($genres);
			$genres = array_unique($genres);

			foreach ($genres AS $genren)
				$g .= "<option value='$genren'" . (in_array($genren, $genre) ? " selected" : "") . ">$genren</option>";

			$g .= "</select>";
		}
		
		$wherea = array();
		$wherea[] = "torrents.imdb != ''";
		$genwherea = array();

		$wherea[] = "torrents.visible = 'yes'";
		$wherea[] = "categories.name != 'TV'";

		if ($timelap)
			$wherea[] = "ADDDATE(torrents.added, INTERVAL $timelap DAY) > '" . get_date_time() . "'";

		if ($lang)
			$wherea[] = "(subs.lang = 'swe' OR torrents.subs LIKE '%swe%' OR torrents.category IN (2, 6, 7))";

		if ($cattype)
			$wherea[] = "cattype.name = '$cattype'";
		elseif ($type)
		{
			foreach ($type AS $ty)
				$typewherea[] = "categories.id = " . sqlesc($ty);
			
			$wherea[] = "(" . implode(" OR ", $typewherea) . ")";
		}
		else
			$wherea[] = "(cattype.name = 'DVDr' OR cattype.name = 'HD')";

		if ($sect != 3)
			$wherea[] = "torrents.req = $sect";

		foreach ($genre AS $gen)
			if ($gen = trim($gen))
				$genwherea[] = "torrents.imdb_genre LIKE '%" . mysql_real_escape_string($gen) . "%'";
		
		if ($genwherea)
			$wherea[] = "(" . implode(" OR ", $genwherea) . ")";
		
		switch ($sort)
		{
			case 1:
				$sort = "Populäraste";
				break;
			case 2:
				$sort = "Nyaste";
				break;
			case 3:
				$sort = "Slumpade";
				break;
			default:
				$sort = "Bästa";
		}
	
		switch ($section)
		{
			case 1:
				$section = "nya ";
				break;
			case 2:
				$section = "gamla ";
				break;
			case 3:
				$section = "requestade ";
				break;
			default:
				$section = "";
		}
	
		switch ($time)
		{
			case 1:
				$time = " detta dygn";
				break;
			case 2:
				$time = " denna vecka";
				break;
			case 3:
				$time = " denna månad";
				break;
			default:
				$time = "";
		}
		
		if ($edited && $type)
		{
			$res = mysql_query("SELECT DISTINCT cattype.name FROM categories LEFT JOIN cattype ON categories.cattype = cattype.id WHERE categories.id IN(" . implode(", ", array_map("sqlesc", $type)) . ")") or sqlerr(__FILE__, __LINE__);
		
			$type = array();
			while ($arr = mysql_fetch_assoc($res))
				$type[] = $arr["name"] . "-";
			
			$type = implode("/", $type);
		}
		else
			switch ($type)
			{
				case 1:
					$type = "DVDr-";
					break;
				case 2:
					$type = "HD-";
					break;
				default:
					$type = "";
			}

		print("<h3 style='margin: 10px 0px;'>" . $sort . " " . $section . $type . "filmerna" . $time . ($genre ? " - " . implode(" / ", $genre) : "") . "</h3>\n");
	
		if (!$edit && ++$count == 1)
			print("</td></tr><tr class='clear'><td style='width: 700px; padding-top: 0px; vertical-align: top;'>\n");
	
		print("<div class='frame" . ($disabled ? " transp" : "") . "' style='text-align: center; white-space: nowrap;'>\n");

		$res = mysql_query("SELECT torrents.id, torrents.name, torrents.category, torrents.req, torrents.imdb_poster, torrents.imdb_rating FROM torrents LEFT JOIN subs ON torrents.id = subs.torrentid LEFT JOIN categories ON torrents.category = categories.id LEFT JOIN cattype ON categories.cattype = cattype.id WHERE " . implode(" AND ", $wherea) . " ORDER BY $sorts DESC LIMIT 7") or sqlerr(__FILE__, __LINE__);

		while ($toppen = mysql_fetch_assoc($res))
			print("<a href='details.php?id=" . $toppen["id"] . "&amp;hit=1'><img src='" . ($toppen["imdb_poster"] ? "/getimdb.php/" . $toppen["imdb_poster"] : "/pic/noposter.png") . "' class='imdb' alt=$toppen[id] /></a>");

		if ($edit)
			print("<br /><br /><form method='post' action='toppen.php'>$a $p $s $t $e $g <input type='hidden' name='order' value=$order /> <input type='submit' value='Uppdatera' /> <input type='submit' name='reset' value='Återställ'" . (!$edited ? " disabled" : "") . " /> " . ($disabled ? "<input type='submit' name='enable' value='Aktivera' />" : "<input type='submit' name='disable' value='Inaktivera' />") . "</form>\n");
		
		print("</div>\n");
	}
}

?>