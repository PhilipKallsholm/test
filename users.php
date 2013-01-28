<?php

require_once("globals.php");

dbconn();
loggedinorreturn();

head("Användarsökning");

$letter = trim($_GET["l"]);
$username = trim($_GET["username"]);
$state = 0 + $_GET["country"];

if (isset($_GET["class"]) && $_GET["class"] != '-')
	$class = 0 + $_GET["class"];

if ($letter)
{
	$wherea[] = "username LIKE '" . mysql_real_escape_string($letter) . "%'";
	$q[] = "l=$letter";
}
elseif ($username)
{
	$wherea[] = "username LIKE '%" . mysql_real_escape_string($username) . "%'";
	$q[] = "username=$username";
}
	
if ($state)
{
	$wherea[] = "country = " . sqlesc($state);
	$q[] = "country=$state";
}
	
if (isset($class))
{
	$wherea[] = "class = " . sqlesc($class);
	$q[] = "class=$class";
}

$where = "WHERE confirmed = 'yes'";

if (count($wherea))
{
	$where .= "AND " . implode(" AND ", $wherea);
	$q = "?" . implode("&", $q) . "&";
}
else
	$q = "?";
	
if (get_user_class() < UC_MODERATOR)
	$where .= " AND class <= $CURUSER[class]";

$res = mysql_query("SELECT id FROM users $where") or sqlerr(__FILE__, __LINE__);
$count = mysql_num_rows($res);

list($pager, $limit) = pager("users.php{$q}page=", $count, 50, $_GET["page"]);

$res = mysql_query("SELECT id, username, added, last_access, enabled, class, country FROM users $where ORDER BY username ASC $limit") or sqlerr(__FILE__, __LINE__);

$clist = "<select name='country'>\n";
$clist .= "<option value=0>(Välj land)</option>\n";

$countries = mysql_query("SELECT id, name FROM countries ORDER BY id ASC") or sqlerr(__FILE__, __LINE__);

while ($country = mysql_fetch_assoc($countries))
	$clist .= "<option value=$country[id]" . ($state == $country["id"] ? " selected" : "") . ">$country[name]</option>\n";
	
$clist .= "</select>\n";

$classes = "<select name='class'>\n";
$classes .= "<option value='-'>(Välj klass)</option>\n";

$i = -1;
while (get_user_class_name(++$i))
{
	if (get_user_class() < $i)
		break;
		
	$classes .= "<option value='$i'" . ($class === $i ? " selected" : "") . ">" . get_user_class_name($i) . "</option>\n";
}
	
$classes .= "</select>\n";

print("<form method='get' action='users.php'><table>\n");
print("<tr class='clear line'><td class='form'>Sök användare</td><td><input type='text' name='username' value='$username' /> $clist $classes <input type='submit' value='Sök' /></td></tr>\n");
print("</table></form>\n");

print("<div style='margin-top: 20px;'>\n");

for ($i = 65; $i <= 90; $i++)
{
	if ($letter == chr($i))
		$letters .= "<span style='color: grey; font-weight: bold; font-size: 9pt;'>" . chr($i) . "</span> ";
	else
		$letters .= "<a href='?l=" . chr($i) . "'>" . chr($i) . "</a> ";
}
	
print("$letters<br /><br />$pager");
print("</div>\n");

print("<table style='margin: 10px auto;'><tr><td class='colhead'>Användarnamn</td><td class='colhead'>Registrerad</td><td class='colhead'>Senast aktiv</td><td class='colhead'>Klass</td><td class='colhead'>Land</td></tr>\n");

while ($arr = mysql_fetch_assoc($res))
{
	if ($arr["country"])
	{
		$country = mysql_query("SELECT * FROM countries WHERE id = $arr[country]") or sqlerr(__FILE__, __LINE__);
		$country = mysql_fetch_assoc($country);
	
		$flag = "<img src='/pic/countries/$country[pic]' title='$country[name]' />";
	}
	else
		$flag = "-";

	print("<tr><td><a href='userdetails.php?id=$arr[id]'>$arr[username]</a>" . usericons($arr["id"]) . "</td><td>$arr[added]</td><td>$arr[last_access]</td><td>" . get_user_class_name($arr["class"]) . "</td><td style='padding: 0px; text-align: center;'>$flag</td></tr>\n");
}

print("</table>\n");
print($pager);

foot();

?>