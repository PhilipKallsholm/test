<?php

require_once("globals.php");

class imdb {

	private $baselink = "http://www.imdb.com/title/";
	private $id;
	private $data;
	
	function __construct($link = "")
	{
		$this->id = $link;
		$this->data = file_get_contents($this->baselink . $this->id);
	}
			
	function title()
	{
		preg_match("#<h1 class=\"header\" itemprop=\"name\">\s(.+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function tagline()
	{
		preg_match("#<h4 class=\"inline\">Taglines:</h4>\s+(.+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function year()
	{
		preg_match("#<a href=\"/year/(\d{4})#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function release()
	{
		preg_match("#<time itemprop=\"datePublished\" datetime=\"([\d-]+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function rating()
	{
		preg_match("#<span itemprop=\"ratingValue\">([\d\.]+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function plot()
	{
		preg_match("#<p itemprop=\"description\">\s(?!</p>)(.+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function genres()
	{
		preg_match_all("#href=\"/genre/([\w-]+)\"\s*>#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function stars()
	{
		preg_match_all("#itemprop=\"actors\"\s+>([^<]+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function recommendations()
	{
		$data = file_get_contents($this->baselink . $this->id . "/recommendations");
		
		preg_match_all("#size=\"-1\"><a href=\"/title/(tt\d+)#i", $data, $matches);
		
		return $matches[1];
	}
	
	function saveposter($small = true)
	{
		if ($small)
			preg_match("#<link rel='image_src' href='([^']+)(?<!imdb-share-logo\.png)('/>)#i", $this->data, $matches);
		else
			preg_match("#" . $this->id . "\"\s+><img src=\"([^\"]+)#i", $this->data, $matches);
			
		$pic = fopen($matches[1], "rb");
			
		if (!$pic)
			return false;
				
		$fp3 = "";
			
		while (!feof($pic))
			$fp3 .= fread($pic, 8192);
			
		$img = imagecreatefromstring($fp3);
		imagepng($img, "/var/imdb_" . ($small ? "small" : "large") . "/" . $this->id . ".png");

		fclose($pic);
			
		return $this->id . ".png";
	}
}

?>