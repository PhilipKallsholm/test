<?php

require_once("globals.php");

function docleanup($lastclean = 0) {
	global $announce_interval;

	$dt = get_date_time();
	
	// RADERA EJ BEKRÄFTADE ANVÄNDARE
	
	$date = get_date_time(strtotime("-2 days"));
	
	$res = mysql_query("SELECT id, username FROM users WHERE confirmed = 'no' AND added < '$date'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$stafflog = "$arr[username] togs automatiskt bort efter två dygn utan bekräftelse";
		mysql_query("INSERT INTO stafflog (added, txt) VALUES('$dt', '$stafflog')") or sqlerr(__FILE__, __LINE__);
		
		deleteuser($arr["id"]);
	}
	
	// RADERA INAKTIVA ANVÄNDARE
	
	$date = get_date_time(strtotime("-4 weeks"));
	
	$res = mysql_query("SELECT id, username FROM users WHERE last_access < '$date' AND class < 5 AND parked != 'yes'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$stafflog = "$arr[username] togs automatiskt bort efter fyra veckors inaktivitet";
		mysql_query("INSERT INTO stafflog (added, txt) VALUES('$dt', '$stafflog')") or sqlerr(__FILE__, __LINE__);
		
		deleteuser($arr["id"]);
	}
	
	// RADERA PARKERADE INAKTIVA ANVÄNDARE
	
	$date = get_date_time(strtotime("-20 weeks"));
	
	$res = mysql_query("SELECT id, username FROM users WHERE last_access < '$date' AND class < 5 AND parked != 'yes'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$stafflog = "$arr[username] togs automatiskt bort efter 20 veckors inaktivitet med parkerat konto";
		mysql_query("INSERT INTO stafflog (added, txt) VALUES('$dt', '$stafflog')") or sqlerr(__FILE__, __LINE__);
		
		deleteuser($arr["id"]);
	}
	
	// RADERA INAKTIVA PEERS
	
	$announce_interval += 600;
	$res = mysql_query("SELECT id, fid, uid, `left` FROM peers WHERE mtime < " . strtotime("-{$announce_interval} seconds")) or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$snatch = mysql_query("SELECT id, done FROM snatched WHERE torrentid = $arr[fid] AND userid = $arr[uid]") or sqlerr(__FILE__, __LINE__);
		$snatch = mysql_fetch_assoc($snatch);
		
		if ($snatch["done"] == '0000-00-00 00:00:00' && $arr["left"])
			mysql_query("UPDATE snatched SET timedout = 'yes' WHERE id = $snatch[id]") or sqlerr(__FILE__, __LINE__);
	
		if (!$arr["left"])
			mysql_query("UPDATE torrents SET seeders = seeders - 1 WHERE id = $arr[fid]") or sqlerr(__FILE__, __LINE__);
		else
			mysql_query("UPDATE torrents SET leechers = leechers - 1 WHERE id = $arr[fid]") or sqlerr(__FILE__, __LINE__);
			
		mysql_query("DELETE FROM peers WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
	}
	
	// GÖR LÄNKAR OSYNLIGA

	$res = mysql_query("SELECT DISTINCT id FROM torrents WHERE visible = 'yes' AND (SELECT COUNT(*) FROM peers WHERE fid = torrents.id AND `left` = 0 AND active = 1) = 0") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
		mysql_query("UPDATE torrents SET visible = 'no', last_action = '$dt' WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
		
	// RADERA IP-ADRESSER

	$date = get_date_time(strtotime("-1 year"));
	mysql_query("DELETE FROM iplogg WHERE lastseen < '$date'") or sqlerr(__FILE__, __LINE__);
	
	// UPPGRADERA POWER USERS

	$limit = 25 * 1024 * 1024 * 1024;
	$minratio = 1.05;
	$maxdt = get_date_time(strtotime("-4 weeks"));
	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 0 AND uploaded >= $limit AND uploaded / downloaded >= $minratio AND added < '$maxdt'") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit befodrad till Power User";
		$msg = "Grattis, Du har automatiskt blivit befodrad till [b]Power User[/b]. :)\n";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt uppgraderad till Power User\n" . $arr["modcomment"];
			
			mysql_query("UPDATE users SET class = 1, reqslots = reqslots + 2, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// UPPGRADERA SOPHISTICATED USERS

	$limit = 1024 * 1024 * 1024 * 1024;
	$minratio = 1.20;
	$maxdt = get_date_time(strtotime("-20 weeks"));
	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 1 AND uploaded >= $limit AND uploaded / downloaded >= $minratio AND added < '$maxdt'") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit befodrad till Sophisticated User";
		$msg = "Grattis, Du har automatiskt blivit befodrad till [b]Sophisticated User[/b]. :)\n";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt uppgraderad till Sophisticated User\n" . $arr["modcomment"];
			
			mysql_query("UPDATE users SET class = 2, reqslots = reqslots + 3, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// UPPGRADERA MARVELOUS USERS

	$limit = 5120 * 1024 * 1024 * 1024;
	$minratio = 1.50;
	$maxdt = get_date_time(strtotime("-40 weeks"));
	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 2 AND uploaded >= $limit AND uploaded / downloaded >= $minratio AND added < '$maxdt'") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit befodrad till Marvelous User";
		$msg = "Grattis, Du har automatiskt blivit befodrad till [b]Marvelous User[/b]. :)\n";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt uppgraderad till Marvelous User\n" . $arr["modcomment"];
			
			mysql_query("UPDATE users SET class = 3, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// UPPGRADERA BLISSFUL USERS

	$ulimit = 10240 * 1024 * 1024 * 1024;
	$dlimit = 500*1024*1024*1024;
	$minratio = 2;
	$maxdt = get_date_time(strtotime("-60 weeks"));
	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 3 AND uploaded >= $ulimit AND downloaded >= $dlimit AND uploaded / downloaded >= $minratio AND added < '$maxdt'") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit befodrad till Blissful User";
		$msg = "Grattis, Du har automatiskt blivit befodrad till [b]Blissful User[/b]. :)\n";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt uppgraderad till Blissful User\n" . $arr["modcomment"];
			
			mysql_query("UPDATE users SET class = 4, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// VARNA ANVÄNDARE MED DÅLIG RATIO

	$minratio = 1.0;
	$downloaded = 10 * 1024 * 1024 * 1024;

	$res = mysql_query("SELECT id, last_warned, modcomment FROM users WHERE class < 6 AND uploaded / downloaded < $minratio AND downloaded >= $downloaded AND enabled = 'yes' AND permban = 'no'") or sqlerr(__FILE__, __LINE__);
    
	if (mysql_num_rows($res))
	{
		$dt = get_date_time();
		$subject = "Ratiovarning";
		$msg = "
[img=http://swepiracy.org/pic/warnedsmall.gif] [i]Detta är ett automatiskt genererat varningsmeddelande[/i]

Din ratio är under gränsen för vad vi tillåter här på Swepiracy. Du har alltså misskött ditt konto.
Du har nu 7 dagar på dig att ordna till din ratio. Efter dessa 7 dagar kommer en bedömning att göras och om Swepiracys automatiska system finner att din ratio är tillräcklig, får du stanna kvar och din varning försvinner. Är den inte tillräcklig raderas ditt konto, och du blir permanent avstängd från Swepiracy.

Detta beslut går inte att överklaga, det spelar alltså ingen som helst roll om du skickar ett meddelande till staff och ber om ursäkt eller frågar om du kan få ett antal extra dagar på dig för att ordna ration. Detta hänger helt och hållet på dig, och ditt intresse av att behålla din plats hos oss.

Är det uppenbart att du inte hinner uppfylla dessa krav inom utsatt tid, finns även möjligheten att [url=/donate.php]donera[/url], varigenom du utöver att hjälpa dig själv stöttar Swepiracys överlevnad och fortsatta utveckling.

Lycka till!";

		$until = get_date_time(strtotime("+1 week"));

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt varnad på grund av dålig ratio\n" . $arr["modcomment"];
          
			mysql_query("UPDATE users SET permban = 'yes', warned = 'yes', warned_until = '$until', times_warned = times_warned + 1, last_warned = '$dt', warned_by = 0, warned_reason = 'dålig ratio', modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}

	// RADERA VARNINGAR EFTER DÅLIG RATIO

	$minratio = 1.0;
	$downloaded = 10 * 1024 * 1024 * 1024;
    
	$res = mysql_query("SELECT id, modcomment FROM users WHERE (uploaded / downloaded >= $minratio OR downloaded < $downloaded) AND permban = 'yes' AND warned_until < '$dt'") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Din varning är borttagen";
		$msg = "Din varning har nu automatiskt blivit borttagen. Håll din ratio över 1.0 i framtiden.\n";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Varning automatiskt borttagen.\n" . $arr["modcomment"];
            
			mysql_query("UPDATE users SET permban = 'no', warned = 'no', warned_until = '0000-00-00 00:00:00', modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// RADERA ANVÄNDARE MED FORTSATT DÅLIG RATIO

	$minratio = 1.0;
	$downloaded = 10 * 1024 * 1024 * 1024;

	$res = mysql_query("SELECT id, mail, modcomment, username, ip, uploaded, downloaded, mail, invitedby FROM users WHERE class < 6 AND uploaded / downloaded < $minratio AND downloaded >= $downloaded AND permban = 'yes' AND warned_until < '$dt'") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		while ($arr = mysql_fetch_assoc($res))
		{
			$ratio = $arr["uploaded"] / $arr["downloaded"];
			$stafflog = $arr["username"] . " togs bort och blev mailbannad på grund av en veckas dålig ratio (" . number_format($ratio, 3) . ")";

			mysql_query("INSERT INTO stafflog (added, txt) VALUES('$dt', '$stafflog')") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO bans (ip, reason, added) VALUES('$arr[ip]', 'En veckas dålig ratio', '$dt')");
			mysql_query("INSERT INTO bannedmails (mail, reason, added) VALUES(" . sqlesc($arr["mail"]) . ", 'En veckas dålig ratio', '$dt')");

			deleteuser($arr[id]);

			/*if ($arr["invitedby"])
			{
				$inviter = mysql_query("SELECT modcomment FROM users WHERE id = $arr[invitedby]") or sqlerr(__FILE__, __LINE__);
				$inviter = mysql_fetch_assoc($inviter);
		
				$subject = "Invitevarning";
				$msg = "Du har blivit varnad i två veckor efter att ha bjudit in en person ([b]{$arr["username"]}[/b]) som misskött sitt konto till den grad, att denne nu blivit bannad från Swepiracy.";
				$until = get_date_time(strtotime("+1 week"));

				$modcomment = "$dt - Automatiskt varnad på grund av att ha bjudit in en misskötsam användare.\n" . $inviter["modcomment"];
          
				mysql_query("UPDATE users SET warned = 'yes', warned_until = '$until', times_warned = times_warned + 1, last_warned = '$dt', warned_by = 0, warned_reason = 'dålig invite', modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[invitedby]") or sqlerr(__FILE__, __LINE__);
				mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[invitedby], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
			}*/
		}
	}
	
	// DEGRADERA POWER USERS

	$minratio = 0.95;

	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 1 AND uploaded / downloaded < $minratio") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit degraderad till User";
		$msg = "Du har automatiskt blivit degraderad från [b]Power User[/b] till [b]User[/b] på grund av att din ratio har sjunkit under $minratio.";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt degraderad till User.\n" . $arr["modcomment"];

			mysql_query("UPDATE users SET class = 0, reqslots = reqslots - 2, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// DEGRADERA SOPHISTICATED USERS

	$minratio = 1.10;

	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 2 AND uploaded / downloaded < $minratio") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit degraderad till Power User";
		$msg = "Du har automatiskt blivit degraderad från [b]Sophisticated User[/b] till [b]Power User[/b] på grund av att din ratio har sjunkit under $minratio.";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt degraderad till Power User.\n" . $arr["modcomment"];

			mysql_query("UPDATE users SET class = 1, reqslots = reqslots - 3, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// DEGRADERA MARVELOUS USERS

	$minratio = 1.40;

	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 3 AND uploaded / downloaded < $minratio") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit degraderad till Sophisticated User";
		$msg = "Du har automatiskt blivit degraderad från [b]Marvelous User[/b] till [b]Sophisticated User[/b] på grund av att din ratio har sjunkit under $minratio.";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt degraderad till Sophisticated User.\n" . $arr["modcomment"];

			mysql_query("UPDATE users SET class = 2, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// DEGRADERA BLISSFUL USERS

	$minratio = 1.90;

	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 4 AND uploaded / downloaded < $minratio") or sqlerr(__FILE__, __LINE__);

	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit degraderad till Marvelous User";
		$msg = "Du har automatiskt blivit degraderad från [b]Blissful User[/b] till [b]Marvelous User[/b] på grund av att din ratio har sjunkit under $minratio.";

		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt degraderad till Marvelous User.\n" . $arr["modcomment"];

			mysql_query("UPDATE users SET class = 3, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}
	
	// DEGRADERA UPLOADERS
	
	$date = get_date_time(strtotime("-1 week"));
	
	$res = mysql_query("SELECT id, modcomment FROM users WHERE class = 6 AND last_upload < '$date'") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($res))
	{
		$subject = "Du har blivit degraderad från Uploader";
		$msg = "Du har automatiskt blivit degraderad från [b]Uploader[/b] till [b]User[/b] på grund av att du inte har laddat upp något inom sektionen [i]nytt[/i] på en vecka.";
		
		while ($arr = mysql_fetch_assoc($res))
		{
			$modcomment = "$dt - Automatiskt degraderad från Uploader till User.\n" . $arr["modcomment"];

			mysql_query("UPDATE users SET class = 0, modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
			mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES($arr[id], '$dt', '$subject', '$msg')") or sqlerr(__FILE__, __LINE__);
		}
	}

	// RADERA VARNINGAR
	
	$res = mysql_query("SELECT id, warned_until, last_warned, modcomment FROM users WHERE permban = 'no' AND warned = 'yes' AND warned_until < '$dt'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
	{
		$subject = "Varning borttagen";
		$body = "Din varning har automatiskt blivit borttagen efter " . get_time(strtotime($arr["warned_until"]) - strtotime($arr["last_warned"])) . ". Försök uppföra dig i fortsättningen.";
		
		$modcomment = "$dt - Varning automatisk borttagen\n" . $arr["modcomment"];
		
		mysql_query("UPDATE users SET warned = 'no', warned_until = '', modcomment = " . sqlesc($modcomment) . " WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);
		mysql_query("INSERT INTO messages (receiver, added, subject, body) VALUES(" . implode(", ", array_map("sqlesc", array($arr["id"], $dt, $subject, $body))) . ")") or sqlerr(__FILE__, __LINE__);
	}
	
	// RADERA DÖDA TORRENTS EFTER 7 DAGAR

	$date = get_date_time(strtotime("-1 week"));
	$res = mysql_query("SELECT id, name FROM torrents WHERE last_action < '$date' and visible = 'no'") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		deletetorrent($arr["id"], FALSE);
		write_log("<b>" . $arr[name] . "</b> rensades automatiskt (död i 7 dagar)");
	}
	
	// RADERA IP-BANS EFTER 30 DAGAR

	$date = get_date_time(strtotime("-60 days"));
	$res = mysql_query("SELECT * FROM bans WHERE added < '$date'") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		$stafflog = "Bannen för ip " . $arr["ip"] . " togs automatiskt bort efter 60 dagar (" . $arr["reason"] . ")";
		mysql_query("INSERT INTO stafflog (added, txt) VALUES('$dt', '$stafflog')") or sqlerr(__FILE__, __LINE__);
		mysql_query("DELETE FROM bans WHERE id = $arr[id]");
	}

	// RADERA IP-BANS FÖR FELAKTIGA INLOGG EFTER 1 DAG

	$date = get_date_time(strtotime("-1 day"));
	$res = mysql_query("SELECT * FROM bans WHERE added < '$date' AND permban = 'no'") or sqlerr(__FILE__, __LINE__);

	while ($arr = mysql_fetch_assoc($res))
	{
		$stafflog = "Bannen för ip " . $arr["ip"] . " togs automatiskt bort efter ett dygn (" . $arr["reason"] . ")";
		mysql_query("INSERT INTO stafflog (added, txt) VALUES('$dt', '$stafflog')") or sqlerr(__FILE__, __LINE__);
		mysql_query("DELETE FROM bans WHERE id = $arr[id]");
	}
	
	// UPPDATERA RIKTIG PEERTID
	
	$timespent = time() - $lastclean;
	$date = "2013-01-08 12:45:00";
	
	$res = mysql_query("SELECT DISTINCT uid FROM peers LEFT JOIN snatched ON peers.uid = snatched.userid AND peers.fid = snatched.torrentid WHERE peers.announced > 1 AND peers.left = 0 AND snatched.added > '$date'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
		mysql_query("UPDATE users SET real_seedtime = real_seedtime + $timespent WHERE id = $arr[uid]") or sqlerr(__FILE__, __LINE__);
		
	$res = mysql_query("SELECT DISTINCT uid FROM peers LEFT JOIN snatched ON peers.uid = snatched.userid AND peers.fid = snatched.torrentid WHERE peers.left > 0 AND snatched.added > '$date'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
		mysql_query("UPDATE users SET real_leechtime = real_leechtime + $timespent WHERE id = $arr[uid]") or sqlerr(__FILE__, __LINE__);
		
	$res = mysql_query("SELECT DISTINCT uid FROM peers LEFT JOIN snatched ON peers.uid = snatched.userid AND peers.fid = snatched.torrentid WHERE snatched.added > '$date'") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
		mysql_query("UPDATE users SET real_peertime = real_peertime + $timespent WHERE id = $arr[uid]") or sqlerr(__FILE__, __LINE__);
	
	/*// SÄKRA MISSTÄNKTA LÄNKHÄMTARE
	$res = mysql_query("SELECT snatched_links.id FROM snatched_links INNER JOIN peers ON snatched_links.torrentid = peers.fid AND snatched_links.userid = peers.uid INNER JOIN torrents ON snatched_links.torrentid = torrents.id WHERE (SELECT id FROM snatched WHERE torrentid = snatched_links.torrentid AND userid = snatched_links.userid) IS NULL AND peers.left = 0") or sqlerr(__FILE__, __LINE__);
	
	while ($arr = mysql_fetch_assoc($res))
		mysql_query("UPDATE snatched_links SET seeded = 'yes' WHERE id = $arr[id]") or sqlerr(__FILE__, __LINE__);*/
}
?>