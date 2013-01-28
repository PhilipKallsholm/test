<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_POWER_USER)
	jErr("Enbart Power Users och högre kan rösta på requests");

$id = 0 + $_GET["id"];

if (!$id)
	jErr("Ogiltigt ID");
	
$res = mysql_query("SELECT * FROM requests WHERE id = $id") or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr)
	jErr("Requesten finns inte");
	
$voted = mysql_query("SELECT id FROM reqvotes WHERE reqid = $id AND userid = $CURUSER[id] LIMIT 1") or sqlerr(__FILE__, __LINE__);
$voted = mysql_num_rows($voted) ? true : false;
	
if ($_POST)
{
	$points = 0 + $_POST["points"];
	
	if ($points < 0)
		jErr("Ogiltig hittelön");
	
	if ($CURUSER["seedbonus"] < $points)
		jErr("Du har inte tillräckligt med poäng");
	
	mysql_query("UPDATE requests SET" . (!$voted ? " votes = votes + 1," : "") . " points = points + $points WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	
	if (!$voted)
		mysql_query("INSERT INTO reqvotes (reqid, userid, points) VALUES($id, $CURUSER[id], $points)") or sqlerr(__FILE__, __LINE__);
	else
		mysql_query("UPDATE reqvotes SET points = points + $points WHERE reqid = $id AND userid = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
	
	if ($points)
	{
		mysql_query("UPDATE users SET seedbonus = seedbonus - $points WHERE id = $CURUSER[id]") or sqlerr(__FILE__, __LINE__);
		
		$log = "Köp av hittelön till <i>$arr[name]</i> -<b>{$points}p</b>";
		mysql_query("INSERT INTO bonuslog (userid, added, body) VALUES($CURUSER[id], '" . get_date_time() . "',  " . sqlesc($log) . ")") or sqlerr(__FILE__, __LINE__);
	}
	
	$return["votes"] = "<a class='jlink' onClick=\"vote($id)\" style='color: gray;'>" . ($voted ? $arr["votes"] : ($arr["votes"] + 1)) . "</a>";
	$return["points"] = "<i>" . number_format($arr["points"] + $points) . " p</i>";
	
	print(json_encode($return));
	die;
}

$return["head"] = "Rösta på $arr[name]";
$return["body"] = "<form method='post' action='reqvote.php' id='reqvote'>" . ($voted ? "<h3 style='color: red;'>Din röst är redan räknad</h3>" : "") . "<h3>Öka hittelön</h3><b>+<input type='text' size=5 name='points' value=0 /> p</b> <span class='small'>(Dras från dina bonuspoäng och ges till eventuell uppladdare)</span><br /><br /><input type='submit' value='" . ($voted ? "Öka" : "Lägg röst") . "' /></form>";

print(json_encode($return));
die;

?>