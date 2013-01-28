<?

$cover = $_SERVER["PATH_INFO"];

if (!preg_match(':^/(\w+)(\.png|\.jpg|\.gif)$:', $cover, $matches))
	die('');
	
if (is_numeric($matches[1]))
	$path = "/var/covers/$cover";
else
	$path = "/var/" . ($_GET["l"] ? "imdb_large" : "imdb_small") . "/$cover";

$filetype = str_replace ('.' , '' , $matches[2]);

header("Content-Type: img/$filetype");
readfile($path);

?>