<?

$cover = $_SERVER["PATH_INFO"];

if (!preg_match(':^/([\w\-_\.]+)(\.png|\.jpg|\.gif)$:', $cover, $matches))
die('');

$path = "/var/doodles/$cover";

$filetype = str_replace ('.' , '' , $matches[2]);

header("Content-Type: img/$filetype");
readfile($path);

?>