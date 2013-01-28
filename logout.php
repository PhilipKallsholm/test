<?php

require_once("globals.php");

/*session_destroy();*/

$exp = time() - 3600;

setcookie("id", "", $epx, "/");
setcookie("pass", "", $epx, "/");

header("Location: index.php");

?>