<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc();

if ($_POST)
{
	if ($_POST["edit"])
	{
		foreach ($_POST["id"] AS $id)
		{
			$id = 0 + $id;
			$group = trim($_POST["group"][$id]);
			$cattype = 0 + $_POST["cattype"][$id];
			
			if ($_POST["del"][$id])
			{
				mysql_query("DELETE FROM p2p WHERE id = $id") or sqlerr(__FILE__, __LINE__);
				continue;
			}
			
			mysql_query("UPDATE p2p SET `group` = " . sqlesc($group) . ", cattype = $cattype WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		}
	}
	else
	{
		$group = trim($_POST["group"]);
		$cattype = 0 + $_POST["cattype"];
		
		mysql_query("INSERT INTO p2p (`group`, cattype) VALUES(" . sqlesc($group) . ", $cattype)") or sqlerr(__FILE__, __LINE__);
	}
	
	header("Location: p2p.php");
}

head("Godkända p2p-grupper");

print("<h1>Godkända p2p-grupper</h1>\n");

$cattypes = mysql_query("SELECT * FROM cattype ORDER BY name DESC") or sqlerr(__FILE__, __LINE__);

while ($cattype = mysql_fetch_assoc($cattypes))
	$cats[] = $cattype;
	
print("<form method='post' action='p2p.php'><table>\n");
print("<tr><td class='colhead'>Releasegrupp</td><td class='colhead'>Kategori</td><td class='colhead' style='text-align: center;'>X</td></tr>\n");

$res = mysql_query("SELECT * FROM p2p ORDER BY `group` ASC") or sqlerr(__FILE__, __LINE__);

while ($arr = mysql_fetch_assoc($res))
{
	$c = "<select name='cattype[$arr[id]]'>";
	
	foreach ($cats AS $cat)
		$c .= "<option value=$cat[id]" . ($arr["cattype"] == $cat["id"] ? " selected" : "") . ">$cat[name]</option>";
		
	$c .= "</select>";
	
	print("<tr><td><input type='hidden' name='id[]' value=$arr[id] /><input type='text' name='group[$arr[id]]' size=10 value='$arr[group]' /></td><td>$c</td><td><input type='checkbox' name='del[$arr[id]]' value=1 /></td></tr>\n");
}

print("<tr class='clear'><td colspan=3 style='text-align: center;'><input type='submit' name='edit' value='Uppdatera' /></td></tr>\n");
print("</table></form>\n");

print("<form method='post' action='p2p.php'><table style='margin-top: 10px;'>\n");
print("<tr><td class='colhead'>Releasegrupp</td><td class='colhead'>Kategori</td></tr>\n");

$c = "<select name='cattype'>";
	
foreach ($cats AS $cat)
	$c .= "<option value=$cat[id]" . ($arr["cattype"] == $cat["id"] ? " selected" : "") . ">$cat[name]</option>";
		
$c .= "</select>\n";
	
print("<tr><td><input type='text' name='group' size=10 /></td><td>$c</td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' value='Lägg till' /></td></tr>\n");
print("</table></form>\n");

foot();

?>