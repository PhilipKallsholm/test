<?php

if (!preg_match("#^/(\d+)/(.+)(\.txt|\.srt|\.sub|\.rar)$#i", $_SERVER["PATH_INFO"], $matches))
	die;

function ob_clean_all()
{ 
	$ob_active = ob_get_length () !== false; 

	while($ob_active)
	{ 
		ob_end_clean(); 
		$ob_active = ob_get_length () !== false; 
	}

	return true; 
}

$id = 0 + $matches[1]; 
$filename = $matches[2];
$fileending = $matches[3]; 

$subpath = "/var/subs";

if(ini_get('zlib.output_compression'))
	ini_set('zlib.output_compression', 'Off');
	
session_cache_limiter('nocache');
session_start();

$fileLocation = $subpath . '/' . $filename . $fileending;
$filename = $filename . $fileending;

$browser = get_browser($_SERVER['HTTP_USER_AGENT'], TRUE);

$mime_type = 'application/octetstream';

function laddaner($f_location, $f_name)
{
	global $id;

	ob_clean_all();
	ob_start();
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	header("Content-Type: ".$mime_type);
	header("Content-Disposition: attachment; filename=\"".basename($f_name)."\";" );
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($f_location));
	ob_end_clean();
	readfile("$f_location");
	exit();
}

laddaner($fileLocation, $filename);

?>