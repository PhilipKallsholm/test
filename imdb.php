<?php

set_time_limit(120);
require_once("globals.php");

dbconn();
loggedinorreturn();

function delposter($id)
{
	@unlink("/var/imdb_small/{$id}.png");
	@unlink("/var/imdb_large/{$id}.png");
}

$data = file_get_contents("http://www.imdb.com/movies-in-theaters/");

if (preg_match("#<h3>Opening This Week - (\w+ \d{1,2})#i", $data, $matches))
	$dt = date("Y-m-d", strtotime($matches[1]));
else
	$dt = date("Y-m-d");

class imdb {

	var $baselink = "http://www.imdb.com/title/";
	var $id;
	var $data;
	var $newmovies;
	var $name;
	
	function __construct($link = "")
	{
		global $dt;
		$this->dt = $dt;
	
		$this->data = file_get_contents($baselink . $link);
	}
	
	function indexmovies($limit)
	{
		$tables = array("theatermovies", "rentalmovies", "newmovies", "comingmovies");
		
		foreach ($tables AS $table)
		{
			switch ($table)
			{
				case 'theatermovies':
					$this->data = file_get_contents("http://www.imdb.com/movies-in-theaters/");
					$pos = stripos($this->data, "In Theaters Now - Box Office Top Ten");
					$this->data = substr($this->data, $pos);
					
					preg_match_all("#\shref=\"/title/(\w+)/\"\stitle=#i", $this->data, $matches);
					break;
				case 'rentalmovies':
					$this->data = file_get_contents("http://www.imdb.com/boxoffice/rentals");
					
					preg_match_all("#align=left><b><a href=\"/title/(\w+)/\"#i", $this->data, $matches);
					break;
				case 'newmovies':
					$this->data = file_get_contents("http://www.imdb.com/movies-in-theaters/");
					$pos = stripos($this->data, "In Theaters Now - Box Office Top Ten");
					$this->data = substr($this->data, 0, $pos);
					
					preg_match_all("#\shref=\"/title/(\w+)/\"\stitle=#i", $this->data, $matches);
					break;
				case 'comingmovies':
					$this->data = file_get_contents("http://www.imdb.com/movies-coming-soon/");
					
					preg_match_all("#\shref=\"/title/(\w+)/\"\stitle=#i", $this->data, $matches);
					break;
			}
		
			$res = mysql_query("SELECT imdbid FROM $table WHERE added = '$this->dt'") or sqlerr(__FILE__, __LINE__);
			$count = mysql_num_rows($res);
		
			if (count($matches[1]) > $count && $count < $limit)
			{
				while ($arr = mysql_fetch_assoc($res))
					delposter($arr["imdbid"]);
				
				mysql_query("DELETE FROM $table WHERE added = '$this->dt'") or sqlerr(__FILE__, __LINE__);
		
				$i = 0;
				foreach ($matches[1] AS $id)
				{
					$this->id = $id;
					$this->data = file_get_contents("http://www.imdb.com/title/" . $this->id);
			
					$title = $this->title();
					$year = $this->year();
					$rating = $this->rating();
					$genres = implode(" / ", $this->genres());
					$plot = $this->plot();
					$stars = implode(", ", $this->stars());
			
					mysql_query("INSERT INTO $table (imdbid, added, title, year, rating, genres, plot, stars) VALUES(" . implode(", ", array_map("sqlesc", array($id, $this->dt, $title, $year, $rating, $genres, $plot, $stars))) . ")") or sqlerr(__FILE__, __LINE__);
			
					$return .= $this->saveposter(false);
					$return .= $this->saveposter();
			
					if (++$i >= $limit)
						break;
				}
				$return .= "<br />";
			}
			else
				$return .= "$table redan hämtade<br />";
		}
		return $return;
	}
		
	function title()
	{
		preg_match("#<h1 class=\"header\" itemprop=\"name\">\s(.+)#i", $this->data, $matches);
		
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
		preg_match("#<p itemprop=\"description\">\s(.+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function genres()
	{
		preg_match_all("#href=\"/genre/(\w+)\"\s*>#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function stars()
	{
		preg_match_all("#itemprop=\"actors\"\s+>([^<]+)#i", $this->data, $matches);
		
		return $matches[1];
	}
	
	function saveposter($small = true)
	{
		if ($small)
			preg_match("#<link rel='image_src' href='([^']+)(?<!imdb-share-logo\.png)'/>#i", $this->data, $matches);
		else
			preg_match("#" . $this->id . "\"\s+><img src=\"([^\"]+)#i", $this->data, $matches);
			
		$pic = fopen($matches[1], "rb");
		//$target = fopen("C:/wamp/www/files/$id.png", "wb");
			
		if (!$pic)
			return false;
				
		$fp3 = "";
			
		while (!feof($pic))
			$fp3 .= fread($pic, 8192);
			
		$img = imagecreatefromstring($fp3);
		imagepng($img, "/var/imdb_" . ($small ? "small" : "large") . "/" . $this->id . ".png");
			
		//fwrite($target, $fp3);
		fclose($pic);
		//fclose($target);
		$posters .= $id . "<br />";
			
		return "<img src='/getimdb.php/" . $this->id . ".png" . ($small ? "" : "?l=1");
	}
}

$indexmovs = new imdb();
$index = $indexmovs->indexmovies(7);

head();

print($index);

foot();

?>