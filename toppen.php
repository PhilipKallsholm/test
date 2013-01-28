<?

require_once("globals.php");

dbconn();
loggedinorreturn();

if (get_user_class() < UC_POWER_USER)
	stderr("Fel", "Du måste vara lägst Power User för att kunna anpassa förstasidan");

if ($_POST)
{
	$time = 0 + $_POST["time"];
	$lang = 0 +  $_POST["lang"];
	$type = $_POST["type"] ? implode(",", $_POST["type"]) : "";
	$section = 0 + $_POST["section"];
	$genre = $_POST["genre"] ? implode(",", $_POST["genre"]) : "";
	$order = 0 + $_POST["order"];
	$sort = 0 + $_POST["sort"];
	
	if ($_POST["disable"])
		$disabled = 1;
	elseif ($_POST["enable"])
		$disabled = 0;

	if ($_POST["reset"])
	{
		mysql_query("DELETE FROM toplists WHERE userid = $CURUSER[id] AND `order` = $order") or sqlerr(__FILE__, __LINE__);
		header("Location: toppen.php");
		die;
	}

	if ($order > 3)
		stderr("Fel", "Ogiltig rang");

	$res = mysql_query("SELECT * FROM toplists WHERE userid = $CURUSER[id] AND `order` = $order") or sqlerr(__FILE__, __LINE__);

	if (!mysql_num_rows($res))
		mysql_query("INSERT INTO toplists (userid, time, lang, type, section, genre, `order`, `sort`" . (isset($disabled) ? ", disabled" : "") . ") VALUES($CURUSER[id], $time, $lang, " . sqlesc($type) . ", $section, " . sqlesc($genre) . ", $order, $sort" . (isset($disabled) ? ", $disabled" : "") . ")") or sqlerr(__FILE__, __LINE__);
	else
		mysql_query("UPDATE toplists SET time = $time, lang = $lang, type = " . sqlesc($type) . ", section = $section, genre = " . sqlesc($genre) . ", `sort` = $sort" . (isset($disabled) ? ", disabled = $disabled" : "") . " WHERE userid = $CURUSER[id] AND `order` = $order") or sqlerr(__FILE__, __LINE__);

	header("Location: toppen.php");
}

head("Startmeny");

print("<h1>Anpassa förstasidan</h1>\n");

begin_frame("", 0, true);

Toppen(true);

print("</div></div>\n");

foot();
?>