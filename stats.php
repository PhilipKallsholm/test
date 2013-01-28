<?php

require_once("secrets.php");

function stafflog($t) {
	mysql_query("INSERT INTO stafflog (added, txt) VALUES('" . get_date_time() . "', " . sqlesc($t) . ")") or sqlerr(__FILE__, __LINE__)  or err("Errno: 11");
}

function get_date_time($t = 0)
{
	if ($t)
		return "" . date("Y-m-d H:i:s", $t) . "";
	else
		return "" . date("Y-m-d H:i:s") . "";
}

function err($msg)
{
	benc_resp(array("failure reason" => array(type => "string", value => $msg)));
	die();
}

function benc($obj)
{
	if (!is_array($obj) || !isset($obj["type"]) || !isset($obj["value"]))
		return;

	$c = $obj["value"];

	switch ($obj["type"])
	{
		case "string":
			return benc_str($c);
		case "integer":
			return benc_int($c);
		case "list":
			return benc_list($c);
		case "dictionary":
			return benc_dict($c);
		default:
			return;
	}
}

function benc_str($s) {
	return strlen($s) . ":$s";
}

function benc_int($i) {
	return "i" . $i . "e";
}

function benc_list($a)
{
	$s = "l";

	foreach ($a as $e)
		$s .= benc($e);

	$s .= "e";

	return $s;
}

function benc_dict($d)
{
	$s = "d";
	$keys = array_keys($d);
	sort($keys);

	foreach ($keys as $k)
	{
		$v = $d[$k];
		$s .= benc_str($k);
		$s .= benc($v);
	}
	
	$s .= "e";

	return $s;
}

function benc_resp($d){
	benc_resp_raw(benc(array(type => "dictionary", value => $d)));
}

function benc_resp_raw($x)
{
	header("Content-Type: text/plain");
	header("Pragma: no-cache");
	print($x);
}

function hash_where($name, $hash)
{
	$shhash = preg_replace('/ *$/s', "", $hash);
	return "($name = " . sqlesc($hash) . " OR $name = " . sqlesc($shhash) . ")";
}

function hash_pad($hash) {
	return str_pad($hash, 20);
}

function sqlesc($x) {
	return "'" . mysql_real_escape_string($x) . "'";
}

$_GET["passkey"] = substr($_SERVER["PATH_INFO"], 1);

foreach (array("info_hash", "peer_id", "event", "passkey") AS $x)
	$GLOBALS[$x] = $_GET[$x];
	
foreach (array("port", "uploaded", "downloaded", "left") AS $x)
	$GLOBALS[$x] = 0 + $_GET[$x];
	
mysql_connect($mysql_host, $mysql_user, $mysql_pass);
mysql_select_db($mysql_db);

foreach (array("info_hash", "peer_id") as $x)
	if (strlen($GLOBALS[$x]) != 20)
	{
		preg_match("/{$x}=([^&]+)/i", $_SERVER["QUERY_STRING"], $matches);
		$GLOBALS[$x] = rawurldecode($matches[1]);
		
		if (strlen($GLOBALS[$x]) != 20)
		{
			//stafflog("Invalid $x (" . strlen($GLOBALS[$x]) . " - " . urlencode($GLOBALS[$x]) . ") :: " . $_GET["info_hash"] . " - " . $_SERVER["QUERY_STRING"]);
			err("Invalid $x (" . strlen($GLOBALS[$x]) . " - " . urlencode($GLOBALS[$x]) . ")");
		}
	}

if (strlen($passkey) != 32)
	err("Invalid passkey (" . strlen($passkey) . " - $passkey)");
	
$res = mysql_query("SELECT id, username, uploaded, freeleech FROM users WHERE passkey = " . sqlesc($passkey) . " LIMIT 1") or err("Errno: 1");
$user = mysql_fetch_assoc($res);

if (!$user)
	err("Invalid passkey; please re-download the torrent");

$ip = $_SERVER["REMOTE_ADDR"];	
$userid = $user["id"];
$last_up = $user["uploaded"];
	
$res = mysql_query("SELECT id, banned, size, freeleech FROM torrents WHERE " . hash_where("info_hash", $info_hash)) or err("Errno: 2");
$torrent = mysql_fetch_assoc($res);

if (!$torrent)
	err("Link not registered with Swepiracy");
	
$torrentid = $torrent["id"];

$res = mysql_query("SELECT * FROM peers WHERE fid = $torrentid AND " . hash_where("peer_id", $peer_id)) or err("Errno: 3");
$self = mysql_fetch_assoc($res);
	
$agent = $_SERVER["HTTP_USER_AGENT"];

if (preg_match("/^(Mozilla|Opera|Links|Lynx)/i", $agent))
	err("Link not registered with Swepiracy");
	
$res = mysql_query("SELECT id FROM snatched WHERE torrentid = $torrentid AND userid = $userid LIMIT 1") or err("Errno: 31");
$snatched = mysql_fetch_assoc($res);

$snatchid = $snatched["id"];
	
if (!$snatched)
{
	mysql_query("INSERT INTO snatched (torrentid, userid, added) VALUES($torrentid, $userid, '" . get_date_time() . "')");
	$snatchid = mysql_insert_id();
}

$snatch = array();
$userd = array();
$updateset = array();
	
if ($left)
	$snatch[] = "download = 'yes'";
	
if ($self)
{
	$last = $self["mtime"];
	$end = time();
	$timespent = $end - $last;

	if ($timespent < 30 && $self["announced"] > 2 && $event != 'stopped')
		err("Sorry, minimum announce interval = 30 sec");

	$upthis = max(0, $uploaded - $self["uploaded"]);
	$downthis = max(0, $downloaded - $self["downloaded"]);

	$freeleech = strtotime($user["freeleech"]);

	if ($upthis || $downthis)
	{
		$userd[] = "uploaded = uploaded + $upthis";
		
		if ($freeleech < time() && $torrent["freeleech"] != 'yes')
			$userd[] = "downloaded = downloaded + $downthis";
		
		$snatch[] = "uploaded = uploaded + $upthis";
		$snatch[] = "downloaded = downloaded + $downthis";
	}
	
	if (!$left && !$self["left"])
	{
		$userd[] = "seedtime = seedtime + $timespent";
		$snatch[] = "seedtime = seedtime + $timespent";
	}
	else
		$snatch[] = "leechtime = leechtime + $timespent";
	
	$snatch[] = "timespent = timespent + $timespent";
	$snatch[] = "last_action = '" . get_date_time() . "'";
	
	if (!$left && $self["left"])
	{
		$updateset[] = "times_completed = times_completed + 1";
		$snatch[] = "done = '" . get_date_time() . "'";
	}
	
	if (count($userd))
		mysql_query("UPDATE users SET " . implode(", ", $userd) . " WHERE id = $userid") or err("Errno: 5");

	$upspeed = $upthis / ($timespent + 1);
	$downspeed = $downthis / ($timespent + 1);

	if ($upspeed > 10485760)
		mysql_query("INSERT INTO cheaters (added, userid, torrentid, client, rate, beforeup, upthis, timediff, userip) VALUES(" . time() . ", $userid, $torrentid, " . sqlesc($agent) . ", $upspeed, $last_up, $upthis, $timespent, $ip)")  or err("Errno: 6");
}
	
if ($event == 'stopped')
{
	if ($self)
	{
		mysql_query("DELETE FROM peers WHERE id = $self[id]")  or err("Errno: 7");
		
		if (mysql_affected_rows())
		{
			if (!$self["left"])
				$updateset[] = "seeders = seeders - 1";
			else
				$updateset[] = "leechers = leechers - 1";
		}
	}
}
else
{
	/*if ($event == 'completed')
	{
		$updateset[] = "times_completed = times_completed + 1";
		mysql_query("UPDATE snatched SET done = '" . get_date_time() . "' WHERE torrentid = $torrentid AND userid = $userid");
	}*/
	
	$sockres = @fsockopen($ip, $port, $errno, $errstr, 5);
	if (!$sockres)
		$connectable = 0;
	else
	{
		$connectable = 1;
		@fclose($sockres);
	}

	if ($self)
	{
		$announce_interval = 30 * 60;

		mysql_query("UPDATE peers SET announced = announced + 1" . (!$left && $self["left"] ? ", completed = " . time() : "") . ", downloaded = $downloaded, `left` = $left, uploaded = $uploaded, mtime = " . time() . ", upspeed = $upspeed, downspeed = $downspeed, timespent = timespent + $timespent, useragent = " . sqlesc($agent) . ", connectable = $connectable WHERE id = $self[id]") or err("Errno: 8");
		
		if (mysql_affected_rows() && $self["left"] != $left)
		{
			if ($left)
			{
				$updateset[] = "seeders = seeders - 1";
				$updateset[] = "leechers = leechers + 1";
			}
			else
			{
				$updateset[] = "seeders = seeders + 1";
				$updateset[] = "leechers = leechers - 1";
			}
		}
	}
	else
	{
		$announce_interval = 1 * 60;
		//$left = $torrent["size"];
		$ret = mysql_query("INSERT INTO peers (fid, uid, ipa, active, announced, completed, downloaded, `left`, uploaded, mtime, upspeed, downspeed, timespent, useragent, connectable, peer_id) VALUES($torrentid, $userid, " . ip2long($ip) . ", 1, 1, 0, $downloaded, $left, $uploaded, " . time() . ", 0, 0, 0, " . sqlesc($agent) . ", $connectable, " . sqlesc($peer_id) . ")") or err("Errno: 9");
		
		if ($ret)
		{
			if (!$left)
				$updateset[] = "seeders = seeders + 1";
			else
				$updateset[] = "leechers = leechers + 1";
		}
	}
}

if (!$left)
{
	if ($torrent["banned"] != "yes")
		$updateset[] = "visible = 'yes'";
		
	$updateset[] = "last_action = '" . get_date_time() . "'";
}

if (count($updateset))
	mysql_query("UPDATE torrents SET " . implode(", ", $updateset) . " WHERE id = $torrentid") or err("Errno: 10");

if (count($snatch))
	mysql_query("UPDATE snatched SET " . implode(", ", $snatch) . " WHERE id = $snatchid") or err("Errno: 11");
	
/*if ($userid == 1)
	stafflog("$user[username] med " . $_SERVER["QUERY_STRING"]);*/

print("d8:intervali{$announce_interval}e5:peerslee");

?>