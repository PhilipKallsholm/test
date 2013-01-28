<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_GET["takereport"])
{
	$type = $_POST["type"];
	$id = 0 + $_POST["id"];
	$reason = $_POST["reason"] == 'other' ? trim($_POST["otherreason"]) : trim($_POST["reason"]);
	$dt = get_date_time();
		
	if (!$reason)
		jErr("Du måste välja ett alternativ");
		
	$res = mysql_query("SELECT id FROM reports WHERE typeid = " . sqlesc($id) . " AND userid = $CURUSER[id] AND solvedby = 0") or sqlerr(__FILE__, __LINE__);
		
	if (mysql_num_rows($res))
		jErr("Du har redan skickat en liknande rapport");
			
	mysql_query("INSERT INTO reports (type, typeid, userid, reason, added) VALUES(" . implode(", ", array_map("sqlesc", array($type, $id, $CURUSER[id], $reason, $dt))) . ")") or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);
	
	switch ($type)
	{
		case 'comment':
			$res = "Kommentaren har blivit rapporterad";
			break;
		case 'post':
			$res = "Inlägget har blivit rapporterat";
			break;
		case 'request':
			$res = "Requesten har blivit rapporterad";
			break;
		case 'torrent':
			$res = "Länken har blivit rapporterad";
			break;
		case 'user':
			$res = "Användaren har blivit rapporterad";
			break;
	}
	
	$return["res"] = "<h1>Tack</h1>$res";
		
	print(json_encode($return));
	die;
}

$id = 0 + $_POST["id"];
$type = $_POST["type"];

$return["body"] = "<form method='post' action='/report.php?takereport=1' id='report'><h3>Vad stämmer in bäst?</h3><input type='hidden' name='id' value=$id />";

switch ($type)
{
	case 'comment':
		$return["head"] = "Rapportera kommentar #" . $id;
		$return["body"] .= "<input type='hidden' name='type' value='$type' /><input type='radio' name='reason' value='Bump' /> BUMP<br /><input type='radio' name='reason' value='Offtopic' /> Offtopic<br /><input type='radio' name='reason' value='Reklam' /> Reklam<br /><input type='radio' name='reason' value='SPAM' /> SPAM<br /><input type='radio' name='reason' value='Stötande' /> Stötande<br /><input type='radio' name='reason' value='other' /> <textarea name='otherreason' style='display: inline-block; vertical-align: text-top; width: 200px; height: 50px;'></textarea>";
		break;
	case 'post':
		$return["head"] = "Rapportera inlägg #" . $id;
		$return["body"] .= "<input type='hidden' name='type' value='$type' /><input type='radio' name='reason' value='Bump' /> BUMP<br /><input type='radio' name='reason' value='Offtopic' /> Offtopic<br /><input type='radio' name='reason' value='Reklam' /> Reklam<br /><input type='radio' name='reason' value='SPAM' /> SPAM<br /><input type='radio' name='reason' value='Stötande' /> Stötande<br /><input type='radio' name='reason' value='other' /> <textarea name='otherreason' style='display: inline-block; vertical-align: text-top; width: 200px; height: 50px;'></textarea>";
		break;
	case 'request':
		$return["head"] = "Rapportera request #" . $id;
		$return["body"] .= "<input type='hidden' name='type' value='$type' /><input type='radio' name='reason' value='Ej scen' /> Ej scen<br /><input type='radio' name='reason' value='För ny' /> För ny<br /><input type='radio' name='reason' value='other' /> <textarea name='otherreason' style='display: inline-block; vertical-align: text-top; width: 200px; height: 50px;'></textarea>";
		break;
	case 'torrent':
		$return["head"] = "Rapportera länk #" . $id;
		$return["body"] .= "<input type='hidden' name='type' value='$type' /><input type='radio' name='reason' value='Ej scen' /> Ej scen<br /><input type='radio' name='reason' value='För gammal' /> För gammal<br /><input type='radio' name='reason' value='Stötande' /> Stötande<br /><input type='radio' name='reason' value='other' /> <textarea name='otherreason' style='display: inline-block; vertical-align: text-top; width: 200px; height: 50px;'></textarea>";
		break;
	case 'user':
		$return["head"] = "Rapportera användare #" . $id;
		$return["body"] .= "<input type='hidden' name='type' value='$type' /><input type='radio' name='reason' value='Stötande avatar' /> Stötande avatar<br /><input type='radio' name='reason' value='Stötande profil' /> Stötande profil<br /><input type='radio' name='reason' value='other' /> <textarea name='otherreason' style='display: inline-block; vertical-align: text-top; width: 200px; height: 50px;'></textarea>";
		break;
}

$return["body"] .= "<br /><br /><input type='submit' value='Rapportera' id='sendreport' /><span class='errormess' id='reporterror' style='margin-left: 10px;'></span></form>";

print(json_encode($return));
die;

?>