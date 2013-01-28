<?php

require_once("globals.php");

dbconn();

head("Legal");

print("<h1>Legal</h1>\n");

print("<div class='frame' style='width: 500px; font-size: 10pt; font-weight: bold;'><ul>\n");
print("<li>Swepiracy does not provide a tracker</li>\n");
print("<li>Swepiracy does not provide torrents</li>\n");
print("<li>Swepiracy can not verify the content behind an info hash, and can not be held responsible for what material magnet links shared between users eventually will lead to</li>\n");
print("</ul></div>\n");

foot();

?>