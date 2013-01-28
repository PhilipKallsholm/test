<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);
	
function flist($id = 0)
{
	$flist = "<select name='forum[$id]'>\n";
	$flist .= "<option value=0>- Välj överforum -</option>\n";
	
	$forumid = mysql_query("SELECT forumid FROM overforums WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	$forumid = mysql_fetch_assoc($forumid);
	
	$forums = mysql_query("SELECT * FROM forums ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	
	while ($forum = mysql_fetch_assoc($forums))
		$flist .= "<option value=$forum[id]" . ($forum["id"] == $forumid["forumid"] ? " selected" : "") . ">$forum[name]</option>\n";
		
	$flist .= "</select>\n";
	
	return $flist;
}

if ($_POST)
{
	if ($_GET["new"])
	{
		$name = trim($_POST["name"]);
		$descr = trim($_POST["descr"]);
		$forum = 0 + $_POST["forum"][0];
		$minclassreads = 0 + $_POST["minclassread"];
		$minclasswrites = 0 + $_POST["minclasswrite"];
		
		if (!$name)
			jErr("Du måste ange ett namn");
		
		if (!$forum)
			jErr("Du måste ange ett forum");
			
		mysql_query("INSERT INTO overforums (name, descr, forumid, minclassread, minclasswrite) VALUES(" . implode(", ", array_map("sqlesc", array($name, $descr, $forum, $minclassreads, $minclasswrites))) . ")") or sqlerr(__FILE__, __LINE__);
		$id = mysql_insert_id();
		
		$minclassread = "<select name='minclassread[$id]'>\n";
		
		$i = 0;
		while ($class = get_user_class_name($i))
			$minclassread .= "<option value=$i" . ($minclassreads == $i++ ? " selected" : "") . ">$class</option>\n";
			
		$minclassread .= "</select>\n";

		$minclasswrite = "<select name='minclasswrite[$id]'>\n";
		
		$i = 0;
		while ($class = get_user_class_name($i))
			$minclasswrite .= "<option value=$i" . ($minclasswrites == $i++ ? " selected" : "") . ">$class</option>\n";
			
		$minclasswrite .= "</select>\n";
		
		$return["row"] = "<tr class='main' style='display: none;'><td><input type='hidden' name='id[]' value=$id /><input type='text' name='name[$id]' size=32 maxlength=32 value='$name' /></td><td><input type='text' name='descr[$id]' size=32 maxlength=50 value='$descr' /></td><td>" . flist($id) . "</td><td>$minclassread</td><td>$minclasswrite</td></tr>\n";
		
		print(json_encode($return));
		die;
	}
	else
	{
		foreach ($_POST["id"] AS $id)
		{
			$name = trim($_POST["name"][$id]);
			$descr = trim($_POST["descr"][$id]);
			$forum = 0 + $_POST["forum"][$id];
			$minclassread = 0 + $_POST["minclassread"][$id];
			$minclasswrite = 0 + $_POST["minclasswrite"][$id];
		
			if (!$name)
			{
				$topics = mysql_fetch_row(mysql_query("SELECT COUNT(*) FROM topics WHERE forumid = " . sqlesc($id)));
				
				if ($topics[0])
					jErr("Forumet innehåller trådar och kan därför inte tas bort");
			
				mysql_query("DELETE FROM overforums WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
			}
			else
				mysql_query("UPDATE overforums SET name = " . sqlesc($name) . ", descr = " . sqlesc($descr) . ", forumid = " . sqlesc($forum) . ", minclassread = " . sqlesc($minclassread) . ", minclasswrite = " . sqlesc($minclasswrite) . " WHERE id = " . sqlesc($id)) or jErr("SQL-fel: " . __FILE__ . ", " . __LINE__);
		}
		
		print(json_encode(""));
		die;
	}
}

head("Forumhanteraren");
begin_frame("Forumhanteraren");

print("<form method='post' action='forumedit.php' id='feditform'><table>\n");

print("<tr><td class='colhead'>Forum</td><td class='colhead'>Beskrivning</td><td class='colhead'>Överforum</td><td class='colhead'>Läsbehörighet</td><td class='colhead'>Skrivbehörighet</td></tr>\n");

$res = mysql_query("SELECT * FROM overforums ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	$minclassread = "<select name='minclassread[$arr[id]]'>\n";
		
	$i = 0;
	while ($class = get_user_class_name($i))
		$minclassread .= "<option value=$i" . ($arr["minclassread"] == $i++ ? " selected" : "") . ">$class</option>\n";
			
	$minclassread .= "</select>\n";

	$minclasswrite = "<select name='minclasswrite[$arr[id]]'>\n";
		
	$i = 0;
	while ($class = get_user_class_name($i))
		$minclasswrite .= "<option value=$i" . ($arr["minclasswrite"] == $i++ ? " selected" : "") . ">$class</option>\n";
			
	$minclasswrite .= "</select>\n";
	
	print("<tr class='main'><td><input type='hidden' name='id[]' value=$arr[id] /><input type='text' name='name[$arr[id]]' size=32 maxlength=32 value='$arr[name]' /></td><td><input type='text' name='descr[$arr[id]]' size=32 maxlength=50 value='$arr[descr]' /></td><td>" . flist($arr["id"]) . "</td><td>$minclassread</td><td>$minclasswrite</td></tr>\n");
}
	
print("<tr class='clear'><td colspan=5 style='padding: 5px 0px; text-align: right;'><span class='errormess' id='fediterr' style='margin-right: 10px;'></span><input type='submit' value='Uppdatera' id='fedit' /></td></tr>\n");

print("</table></form>\n");
print("<br /><form method='post' action='forumedit.php?new=1' id='faddform'><table>\n");

$minclassread = "<select name='minclassread'>\n";
		
$i = 0;
while ($class = get_user_class_name($i))
	$minclassread .= "<option value=" . $i++ . ">$class</option>\n";
			
$minclassread .= "</select>\n";

$minclasswrite = "<select name='minclasswrite'>\n";
		
$i = 0;
while ($class = get_user_class_name($i))
	$minclasswrite .= "<option value=" . $i++ . ">$class</option>\n";
			
$minclasswrite .= "</select>\n";

print("<tr class='main'><td><input type='text' name='name' size=32 maxlength=32 /></td><td><input type='text' name='descr' size=32 maxlength=50 /></td><td>" . flist() . "</td><td>$minclassread</td><td>$minclasswrite</td></tr>\n");
print("<tr class='clear'><td colspan=5 style='padding: 5px 0px; text-align: right;'><span class='errormess' id='fadderr' style='margin-right: 10px;'></span><input type='submit' value='Skapa' id='fadd' /></td></tr>\n");

print("</table></form>\n</div></div>\n");
foot();

?>