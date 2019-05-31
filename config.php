<?php

define('SQL_HOST', 'localhost');
define('SQL_USER', 'root');
define('SQL_PASS', 'adgadg');
define('SQL_DB', 'language');
define('LARGE', 10000);
define('TIMEZONE', 'America/Toronto');
date_default_timezone_set(TIMEZONE);


// for setting charset to UTF-8 in PHP and MySQL
mb_regex_encoding('UTF-8');
mb_internal_encoding("UTF-8");
header('Content-Type: text/html; charset=utf-8');

$link = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error($link));

?>