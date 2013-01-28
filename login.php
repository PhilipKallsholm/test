<?php
require_once("globals.php");

dbconn();

print("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n\n");
print("<html xmlns=\"http://www.w3.org/1999/xhtml\" style='display: table; overflow: hidden;'>\n<head>\n");

print("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n");
print("<title>$sitename" . ($title ? " - $title" : "") . "</title>\n");
print("<link rel=\"icon\" href=\"/favicon.ico\" />\n");
print("<link rel=\"stylesheet\" type=\"text/css\" href=\"/ice.css\" />\n");

print("<script type=\"text/javascript\" src=\"/jquery-1.7.2.min.js\"></script>\n");
print("<script type=\"text/javascript\" src=\"/jquery-ui-1.8.21.custom.min.js\"></script>\n");
print("<script type=\"text/javascript\" src=\"/jqueries.js\"></script>\n");
print("</head><body style='display: table-cell; position: relative; background-color: #f9f9f9; vertical-align: middle;'>\n");

print("<div style='position: fixed; width: 100%; height: 600px; top: 50%; margin-top: -300px; z-index: 0; line-height: 600px; font-size: 600px; color: #d8e2ff; opacity: 0.5;'>2.0</div>\n");

print("<div id='login'>\n");

$res = mysql_query("SELECT SUM(attempts) FROM failedlogins WHERE ip = " . sqlesc(getip())) or sqlerr(__FILE__, __LINE__);
$attempts = mysql_fetch_row($res);

$left = 5 - $attempts[0];

print("<span class='small' id='left'>$left tries left</span>\n");

print("<form method='post' action='takelogin.php?returnto=$_GET[returnto]' id='loginform'><table class='clear'>\n");
print("<tr><td class='form'>Username</td><td><input type='text' name='username' size=30 /></td></tr>\n");
print("<tr><td class='form'>Password</td><td><input type='password' name='password' size=30 /></td></tr>\n");
print("<tr><td colspan=2 style='text-align: center;'><div class='errormess'></div><input type='submit' id='login' value='Login' /><div id='recover' style='display: none; margin-top: 10px;'><a href='recover.php'>Recover password</a></div></td></tr>\n");
print("</table></form>\n");

if (time() > strtotime("2013-01-01 00:00:00"))
	print("<a href='apply.php' style='display: inline-block; font-size: 9pt;'>Apply for membership</a>\n");
else
{
	print("<a href='signup.php' style='display: inline-block; color: red; font-size: 9pt;'>Signups open until New Year!</a>\n");
	
	print("<div id='cdown' style='color: red; font-size: 9pt;'></div>\n");

	$timediff = strtotime("2013-01-01 00:00:00") - time();
	print("<script type='text/javascript'>\n");
?>

timeDiff = <?=$timediff?>;

function cDown()
{
	var secs = timeDiff = timeDiff - 1;
	var days = Math.floor(secs/86400);
	secs -= days*86400;
	var hours = Math.floor(secs/3600);
	secs -= hours*3600;
	var minutes = Math.floor(secs/60);
	secs -= minutes*60;
	
	var time = new Array(hours, minutes, secs);
	
	for (i = 0; i < time.length; i++)
	{
		if (time[i] < 10)
		{
			time.splice(i, 1, '0' + time[i]);
		}
	}
	
	document.getElementById('cdown').innerHTML = days + "d " + time.join(":");
	
	if (timeDiff < 1)
	{
		document.getElementById('cdown').innerHTML = "<img src='/pic/load.gif' style='vertical-align: text-bottom;' />";
		setTimeout(function() {window.location.href = "/login.php";}, 5000);
	}
	else
	{
		setTimeout(cDown, 1000);
	}
}

cDown();

<?php
	print("</script>\n");
}

//print("<br /><a href='http://blogg.swepiracy.nu' style='display: inline-block; margin-top: 10px; font-style: italic; color: gray;'>blogg.swepiracy.org</a>\n");

print("</div></body></html>\n");

?>