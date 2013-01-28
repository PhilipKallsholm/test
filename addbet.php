<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if (!staff() && $CURUSER["betadmin"] == 'no')
	stderr("Fel", "Tillgång nekad.");

if ($_POST)
{
	$id = $_POST["id"];
	$name = trim($_POST["name"]);
	$descr = trim($_POST["descr"]);
	$standard = $_POST["standard"];
	$alts = array_map("trim", $_POST["alts"]);
	$ends = $_POST["ends"] . ":00";

	if ($standard)
	{
		$alts[] = "1";
		$alts[] = "X";
		$alts[] = "2";
	}

	if (!$name || !$descr || count($alts) < 2 || !$_POST["ends"])
		stderr("Fel", "Vänligen fyll i samtliga obligatoriska fält.");

	if (strlen($ends) != 19 || !preg_match("/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/i", $ends))
		stderr("Fel", "Datumet är felaktigt.");

	if ($id)
	{
		mysql_query("UPDATE betting SET name = " . sqlesc($name) . ", descr = " . sqlesc($descr) . ", ends = " . sqlesc($ends) . " WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);

		$i = 1;
		foreach ($alts AS $alt)
			if ($alt)
				mysql_query("UPDATE bettingalts SET alt = " . sqlesc($alt) . " WHERE betid = " . sqlesc($id) . " AND `order` = " . ($i++)) or sqlerr(__FILE__, __LINE__);	   
	}
	else
	{
		mysql_query("INSERT INTO betting (name, descr, ends) VALUES (" . implode(", ", array_map("sqlesc", array($name, $descr, $ends))) . ")") or sqlerr(__FILE__, __LINE__);
		$id = mysql_insert_id();
		
		$i = 1;
		foreach ($alts AS $alt)
			if ($alt)
				mysql_query("INSERT INTO bettingalts (betid, alt, `order`) VALUES($id, " . sqlesc($alt) . ", " . ($i++) . ")") or sqlerr(__FILE__, __LINE__);
				
		stafflog("$CURUSER[username] startade spelet $name ($descr)");
	}

	header("Location: bet.php#b$id");
}

$id = 0 + $_GET["id"];

$bet = mysql_query("SELECT * FROM betting WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$bet = mysql_fetch_assoc($bet);

if ($id && !$bet)
	stderr("Fel", "Spelet hittades inte");

head(($id ? "Ändra" : "Lägg till") . " spel");

print("<img src='stakebet.png' />\n");
print("<h3 style='margin: 10px 0px;'><a href='bet.php' style='margin: 0px 5px;'>Spel</a><a href='bet_kup.php' style='margin: 0px 5px;'>Mina bets</a><a href='bet_top.php' style='margin: 0px 5px;'>Topplistor</a><a href='bet_info.php' style='margin: 0px 5px;'>Information</a>" . (get_user_class() >= UC_MODERATOR || $CURUSER["betadmin"] == 'yes' ? "<a href='addbet.php' style='margin: 0px 5px; color: gray;'>Lägg till spel</a>" : "") . "</h3>\n");

if ($id)
	print("<h1>Ändra spel</h1>");

$count = mysql_query("SELECT id FROM betting WHERE endedby = 0") or sqlerr(__FILE__, __LINE__);

if (!$id && mysql_num_rows($count) > 15)
{
	print("<br /><br /><i>Gränsen för max antal bets är redan uppnådd</i>");
	foot();
}

print("<form method='post' action='addbet.php'><table>\n");

print("<tr><td class='form'>Namn <span style='color: red;'>*</span></td><td><input name='name' size=50 maxlength=50 value='$bet[name]' /><br /><span class='small'>(Skrivs enligt exemplet: <b>Man Utd - Arsenal</b>. <b>OBS!</b> Var noga med <b>hemma- och bortalag</b>.)</span></td></tr>\n");
print("<tr><td class='form'>Beskrivning <span style='color: red;'>*</span></td><td><input name='descr' size=50 maxlength=50 value='$bet[descr]' /><br /><span class='small'>(Skrivs enligt exemplen: <b>Premier Leauge, omgång 12</b> eller <b>Svenska cuppen, semifinal</b>.)</span></td></tr>\n");

if ($id)
{
	$alts = mysql_query("SELECT * FROM bettingalts WHERE betid = " . sqlesc($id) . " ORDER BY `order` ASC") or sqlerr(__FILE__, __LINE__);

	while ($alt = mysql_fetch_assoc($alts))
		print("<tr><td class='form'>Alternativ $alt[order] <span style='color: red;'>*</span></td><td><input name='alts[]' size=50 maxlength=50 value='$alt[alt]' /></td></tr>\n");

	$bet["ends"] = explode(":", $bet["ends"]);
	$bet["ends"] = $bet["ends"][0] . ":" . $bet["ends"][1];
}
else
{
	print("<tr><td class='form'>1 X 2</td><td><input type='checkbox' name='standard' value=1 /><span class='small'>(Anger standardalternativen 1, X och 2. Inga andra alternativ behöver fyllas i.)</span></td></tr>\n");

	print("<tr><td class='form'>Alternativ 1 <span style='color: red;'>*</span></td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 2 <span style='color: red;'>*</span></td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 3</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 4</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 5</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 6</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 7</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 8</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 9</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
	print("<tr><td class='form'>Alternativ 10</td><td><input name='alts[]' size=50 maxlength=50 /></td></tr>\n");
}

print("<tr><td class='form'>Slutar <span style='color: red;'>*</span></td><td><input name='ends' size=50 maxlength=19 value='$bet[ends]' /><br /><span class='small'>(Skrivs enligt exemplet: <b>2012-01-01 13:00</b>.)</span></td></tr>\n");
print("<tr><td colspan=2 style='text-align: center;'><input type='hidden' name='id' value='$bet[id]' /><input type='submit' value='" . ($id ? "Ändra" : "Skapa") . "' /></td></tr>\n");

print("</table></form>\n");

foot();

?>