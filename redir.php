<?php

$url = trim($_GET["url"]);

print("<!DOCTYPE html>\n");
print("<html style='display: table;'>\n");
print("<head>\n");
print("<meta http-equiv='refresh' content='2; url=$url' />\n");
print("<style type='text/css'>
html, body {
	width: 100%;
	height: 100%;
	font-family: 'tahoma', 'helvetica', 'sans-serif';
	background-color: #353535;
	text-align: center;
	color: white;
}
</style>\n");
print("</head>\n");
print("<body style='display: table-cell; vertical-align: middle;'>\n");

print("<img src='logo_orig.png' /><br /><img src='load.gif' />\n");
print("<h2>Redirecting you to:<br />$url</h2>\n");
print("</body></html>\n");

?>