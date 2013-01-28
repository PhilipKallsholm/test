<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

staffacc(UC_SYSOP);

if ($_POST)
{
	$text = trim($_POST["text"]);
	$name = trim($_POST["name"]);
	$url = trim($_POST["url"]);
	$file = $_FILES["file"];
	
	if (!$text || !$name || !$url || !$file["size"])
		stderr("Fel", "Fyll i alla fält");
		
	$name = $name . "_" . date("Y-m-d") . ".png";
		
	if ($file["error"])
		stderr("Fel", "Filen är för stor");
	
	$target = "/var/doodles/$name";
	
	if (file_exists($target))
		stderr("Fel", "Filen finns redan");
	
	$size = getimagesize($file["tmp_name"]);
	$type = $size[2];
	
	if ($size[0] > 800)
		stderr("Fel", "Bilden är för bred");
	
	/*if ($type == 1)
		$img = imagecreatefromgif($file["tmp_name"]);
	elseif ($type == 2)
		$img = imagecreatefromjpeg($file["tmp_name"]);
	elseif ($type == 3)
		$img = imagecreatefrompng($file["tmp_name"]);
	else
		stderr("Fel", "Ogiltigt filformat (GIF/JPEG/PNG)");
		
	unlink($file["tmp_name"]);
	
	if (!imagepng($img, $target))
		stderr("Fel", "Filen kunde inte laddas upp");*/
		
	if (!move_uploaded_file($file["tmp_name"], $target))
		stderr("Fel", "Filen kunde inte laddas upp");
	
	mysql_query("INSERT INTO doodles (added, text, name, url) VALUES(" . implode(", ", array_map("sqlesc", array(get_date_time(), $text, $name, $url))) . ")") or sqlerr(__FILE__, __LINE__);
	
	header("Location: addlogo.php");
}

head("Ladda upp doodle");

print("<h1>Ladda upp doodle</h1>\n");

print("<form method='post' action='addlogo.php' enctype='multipart/form-data'><table>\n");

print("<tr><td class='form'>Text</td><td><input type='text' name='text' size=40 /></td></tr>\n");
print("<tr><td class='form'>Namn</td><td><input type='text' name='name' size=40 /></td></tr>\n");
print("<tr><td class='form'>URL</td><td><input type='text' name='url' size=40 /></td></tr>\n");
print("<tr><td class='form'>Fil</td><td><input type='file' name='file' size=40 /></td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='submit' value='Ladda upp' /></td></tr>\n");

print("</table></form>\n");

foot();

?>