<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

if ($_POST)
{
	if ($_POST["add"])
	{
		$name = trim($_POST["name"]);
		$image = trim($_POST["image"]);
		$cattype = 0 + $_POST["cattype"];
		
		if (!$name)
			stderr("Du måste ange ett namn");
		
		mysql_query("INSERT INTO categories (name, image, cattype) VALUES(" . implode(", ", array_map("sqlesc", array($name, $image, $cattype))) . ")") or sqlerr(__FILE__, __LINE__);
	}
	else
	{
		$ids = $_POST["ids"];
	
		foreach ($ids AS $id)
		{
			$id = 0 + $id;
			$name = trim($_POST["name"][$id]);
			$image = trim($_POST["image"][$id]);
			$cattype = 0 + $_POST["cattype"][$id];
			
			if (!$name)
				stderr("Fel", "Du måste ange ett namn");
		
			mysql_query("UPDATE categories SET name = " . sqlesc($name) . ", image = " . sqlesc($image) . ", cattype = $cattype WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		}
	}
	header("Location: catedit.php");
}

head("Kategorihanteraren");

print("<h1>Kategorihanteraren</h1>\n");

print("<form method='post' action='catedit.php'><table>\n");
print("<tr><td class='colhead'>Namn</td><td class='colhead'>Ikon</td><td class='colhead'>Typ</td></tr>\n");

$cats = mysql_query("SELECT * FROM categories ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);

while ($cat = mysql_fetch_assoc($cats))
{
	$cattypes = mysql_query("SELECT * FROM cattype ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	
	$ct = "<select name='cattype[$cat[id]]'>";
	
	while ($cattype = mysql_fetch_assoc($cattypes))
		$ct .= "<option value=$cattype[id]" . ($cat["cattype"] == $cattype["id"] ? " selected" : "") . ">$cattype[name]</option>";
		
	$ct .= "</select>\n";
	
	print("<tr><td><input type='hidden' name='ids[]' value=$cat[id] /><img src='$cat[image]' style='margin-right: 10px; vertical-align: middle;' /><input type='text' name='name[$cat[id]]' size=20 value='$cat[name]' /></td><td><input type='text' name='image[$cat[id]]' size=20 value='$cat[image]' /></td><td>$ct</td></tr>\n");
}

print("<tr><td colspan=3 style='text-align: center;'><input type='submit' value='Uppdatera' /></td></tr>\n");
print("</table></form>\n");

$cattypes = mysql_query("SELECT * FROM cattype ORDER BY name ASC") or sqlerr(__FILE__, __LINE__);
	
$ct = "<select name='cattype'>";
	
while ($cattype = mysql_fetch_assoc($cattypes))
	$ct .= "<option value=$cattype[id]>$cattype[name]</option>";
		
$ct .= "</select>\n";

print("<br /><form method='post' action='catedit.php'><table>\n");
print("<tr><td><input type='text' name='name' size=20/></td><td><input type='text' name='image' size=20 /></td><td>$ct</td></tr>\n");
print("<tr><td colspan=3 style='text-align: center;'><input type='submit' name='add' value='Lägg till' /></td></tr>\n");
print("</table></form>\n");

foot();

?>