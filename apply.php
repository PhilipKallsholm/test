<?php

require_once("globals.php");

dbconn();
$sites = array("HDBits", "Rarat", "RevolutionTT", "SceneAccess", "Sparvar", "TheInternationals", "TorrentLeech");

if ($_POST)
{
	$dt = get_date_time();
	$link = trim($_POST["link"]);
	$mail = trim($_POST["mail"]);
	$code = trim($_POST["code"]);
	$rules = $_POST["rules"];
	$faq = $_POST["faq"];
	$agreement = $_POST["agreement"];
	
	$validlink = false;
	foreach ($sites AS $site)
		if (stripos($link, $site) !== false)
		{
			$validlink = true;
			break;
		}
	
	if (!$validlink)
		stderr("Error", "Invalid link");
		
	if (strrpos($link, "http"))
		stderr("Error", "Invalid or multiple links; only one link allowed");
	
	if (!validmail($mail))
		stderr("Error", "Invalid mail address");
		
	$res = mysql_query("SELECT id FROM applications WHERE mail = " . sqlesc($mail) . " AND accepted = 'pending' LIMIT 1") or sqlerr(__FILE__, __LINE__);
	
	if (mysql_num_rows($res))
		stderr("Error", "You already have an application being reviewed");
		
	if (!$rules)
		stderr("Error", "You must read and comply with our rules");
		
	if (!$faq)
		stderr("Error", "You must read the FAQ");
		
	if (!$agreement)
		stderr("Error", "You must have read, understood and accepted our <a href='useragreement.php'>Terms of Use</a> to become a member of Swepiracy");
		
	mysql_query("INSERT INTO applications (added, mail, ip, code, link) VALUES(" . implode(", ", array_map("sqlesc", array($dt, $mail, getip(), $code, $link))) . ")") or sqlerr(__FILE__, __LINE__);
	
	stderr("Your application has been sent!", "Please note that if your application is successful, an invite will be sent to the mail address stated.");
}

head("Apply for membership");

begin_frame("Apply for membership", 600);

print("<h3>Use the form below to apply for a Swepiracy invite</h3>\n");
print("It is now possible to apply for a Swepiracy invite if you already are an active member of another private site. You will be <b>required</b> to make the following code visible on your <b>personal public profile</b> in order for us to confirm your data;");

$code = md5(getip() . time());

print("<div style='width: 200px; margin: 10px; padding: 2px; background-color: white; border: 1px solid black; font-style: italic;'>$code</div>\n");
print("Please observe that your account also <b>must be active and above the lowest user class</b>.<br /><br />We currently only accept memberships on following trackers: <b>" . implode("</b>, <b>", $sites) . "</b>\n");

print("<form method='post' action='apply.php'><table style='margin-top: 20px;'>\n");
print("<tr class='main'><td class='form'>Link to personal profile</td><td><input type='text' name='link' size=40 /></td></tr>\n");
print("<tr class='main'><td class='form'>Invitation mail</td><td><input type='text' name='mail' size=40 /></td></tr>\n");
print("<tr class='main'><td class='form'>I will follow the Rules</td><td><input type='radio' name='rules' value=1 />Yes <input type='radio' name='rules' value=0 />No</td></tr>\n");
print("<tr class='main'><td class='form'>I will read the FAQ</td><td><input type='radio' name='faq' value=1 />Yes <input type='radio' name='faq' value=0 />No</td></tr>\n");
print("<tr class='main'><td class='form'>I understand and accept the <a href='useragreement.php' style='border-bottom: 1px dotted black;'>Terms of Use</a></td><td><input type='radio' name='agreement' value=1 />Yes <input type='radio' name='agreement' value=0 />No</td></tr>\n");
print("<tr class='clear'><td colspan=2 style='text-align: center;'><input type='hidden' name='code' value='$code' /><input type='submit' class='btn' value='Apply now' /></td></tr>\n");
print("</table></form>\n");

print("</div></div>\n");

foot();
?>