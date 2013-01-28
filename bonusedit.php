<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

if ($_POST)
{
	if ($_POST["add"])
	{
		$name = trim($_POST["name"]);
		$descr = trim($_POST["descr"]);
		$points = 0 + $_POST["points"];
		$row = trim($_POST["row"]);
		$log = trim($_POST["log"]);
		$value = trim($_POST["value"]);
		$page = trim($_POST["page"]);
		$type = trim($_POST["type"]);
		
		if (!$name)
			stderr("Fel", "Du måste ange en rubrik");
			
		if (!$descr)
			stderr("Fel", "Du måste ange en beskrivning");
		
		mysql_query("INSERT INTO bonusshop (name, descr, points, row, log, `value`, page, type) VALUES(" . implode(", ", array_map("sqlesc", array($name, $descr, $points, $row, $log, $value, $page, $type))) . ")") or sqlerr(__FILE__, __LINE__);
	}
	else
	{
		$ids = $_POST["id"];
		$names = $_POST["name"];
		$descrs = $_POST["descr"];
		$pointss = $_POST["points"];
		$rows = $_POST["row"];
		$logs = $_POST["log"];
		$values = $_POST["value"];
		$pages = $_POST["page"];
		$types = $_POST["type"];
	
		foreach ($ids AS $id)
		{
			if (!is_numeric($id))
				continue;
		
			$name = trim($names[$id]);
			$descr = trim($descrs[$id]);
			$points = 0 + $pointss[$id];
			$row = trim($rows[$id]);
			$log = trim($logs[$id]);
			$value = trim($values[$id]);
			$page = trim($pages[$id]);
			$type = trim($types[$id]);
			
			mysql_query("UPDATE bonusshop SET name = " . sqlesc($name) . ", descr = " . sqlesc($descr) . ", points = " . sqlesc($points) . ", row = " . sqlesc($row) . ", log = " . sqlesc($log) . ", `value` = " . sqlesc($value) . ", page = " . sqlesc($page) . ", type = " . sqlesc($type) . " WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
		}
		
		if ($_POST["del"])
			mysql_query("DELETE FROM bonusshop WHERE id IN (" . implode(", ", $_POST["del"]) . ")") or sqlerr(__FILE__, __LINE__);
		
	}
}

head("Bonusalternativ");

begin_frame("Bonusalternativ");

print("<form method='post' action='bonusedit.php' id='bonusform'><table>\n");
print("<tr><td class='colhead'>Rubrik</td><td class='colhead'>Beskrivning</td><td class='colhead'>Pris</td><td class='colhead'>Rad</td><td class='colhead'>Logg</td><td class='colhead'>Antal</td><td class='colhead'>Vidarekoppling</td><td class='colhead'>Typ</td><td class='colhead' style='text-align: center;'>X</td></tr>\n");

$res = mysql_query("SELECT * FROM bonusshop ORDER BY points DESC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
	print("<tr class='main'><td><input type='hidden' name='id[]' value=$arr[id] /><input type='text' name='name[$arr[id]]' size=10 maxlength=32 value='$arr[name]' /></td><td><input type='text' name='descr[$arr[id]]' size=30 value='$arr[descr]' /></td><td><input type='text' name='points[$arr[id]]' size=10 value='$arr[points]' /></td><td><input type='text' name='row[$arr[id]]' size=10 value='$arr[row]' /></td><td><input type='text' name='log[$arr[id]]' size=10 value='$arr[log]' /></td><td><input type='text' name='value[$arr[id]]' size=10 value='" . htmlent($arr["value"]) . "' /></td><td><input type='text' name='page[$arr[id]]' size=30 value='$arr[page]' /></td><td><select name='type[$arr[id]]'><option value='data'>DATA</option><option value='date'" . ($arr["type"] == 'date' ? " selected" : "") . ">DATUM</option></select></td><td><input type='checkbox' name='del[]' value=$arr[id] /></td></tr>\n");

print("<tr class='clear'><td colspan=7 style='padding: 5px 0px; text-align: center;'><input type='submit' value='Uppdatera' /></td></tr>\n");
	
print("</table></form>\n");

print("<form method='post' action='bonusedit.php' id='bonusaddform'><table style='margin-top: 10px;'>\n");
print("<tr><td class='colhead'>Rubrik</td><td class='colhead'>Beskrivning</td><td class='colhead'>Pris</td><td class='colhead'>Rad</td><td class='colhead'>Logg</td><td class='colhead'>Antal</td><td class='colhead'>Vidarekoppling</td><td class='colhead'>Typ</td></tr>\n");
print("<tr class='main'><td><input type='text' name='name' size=10 maxlength=32 /></td><td><input type='text' name='descr' size=30 /></td><td><input type='text' name='points' size=10 /></td><td><input type='text' name='row' size=10 /></td><td><input type='text' name='log' size=10 /></td><td><input type='text' name='value' size=10 /></td><td><input type='text' name='page' size=30 /></td><td><select name='type'><option value='data'>DATA</option><option value='date'>DATUM</option></select></td></tr>\n");
print("<tr class='clear'><td colspan=7 style='padding: 5px 0px; text-align: center;'><input type='submit' name='add' value='Lägg till' /></td></tr>\n");

print("</table></form>\n");
print("</div></div>\n");

foot();
?>