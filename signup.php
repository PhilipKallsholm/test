<?php

require_once("globals.php");

dbconn();

$path = explode("/", $_SERVER["PATH_INFO"]);

$i = $path[1];

$res = mysql_query("SELECT userid, mail FROM invites WHERE hash = " . sqlesc($i)) or sqlerr(__FILE__, __LINE__);
$arr = mysql_fetch_assoc($res);

if (!$arr && time() > strtotime("2013-01-01 00:00:00"))
	_404();
	
if ($arr["userid"])
	stderr("Fel", "Inviten har redan blivit använd");
	
head("Signup");
begin_frame("Signup", 600);

print("<div class='errormess' id='regmess' style='text-align: center;'></div>\n");

print("<form method='post' action='takesignup.php' id='signform' autocomplete='off'><input type='hidden' name='i' value='$i' /><table>\n");

print("<tr class='main'><td class='form'>Username</td><td><input type='text' size=20 name='username' /></td></tr>\n");
print("<tr class='main'><td class='form'>Password</td><td><input type='password' size=20 name='password' /></td></tr>\n");
print("<tr class='main'><td class='form'>Repeat password</td><td><input type='password' size=20 name='passwordrep' /></td></tr>\n");
print("<tr class='main'><td class='form'>E-mail</td><td><input type='text' size=20 name='mail'" . ($arr ? " value='$arr[mail]' disabled" : "") . " /></td></tr>\n");
print("<tr class='main'><td class='form'>I will follow the Rules</td><td><input type='radio' name='rules' value=1 />Yes <input type='radio' name='rules' value=0 />No</td></tr>\n");
print("<tr class='main'><td class='form'>I will read the FAQ</td><td><input type='radio' name='faq' value=1 />Yes <input type='radio' name='faq' value=0 />No</td></tr>\n");
print("<tr class='main'><td class='form'>I understand and accept the <a href='/useragreement.php' style='border-bottom: 1px dotted black;'>Terms of Use</a></td><td><input type='radio' name='agreement' value=1 />Yes <input type='radio' name='agreement' value=0 />No</td></tr>\n");
print("<tr><td colspan=2 style='text-align: center; border: none;'><input type='submit' class='btn' id='signup' value='Signup' /></td></tr>\n");

print("</table></form>\n");
print("</div></div>\n");

foot();

?>