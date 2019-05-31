<?php
require('config.php');
session_start();
$link = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error());
mysqli_select_db($link, SQL_DB);

foreach ($_GET as $key => $value) {
	$$key = $value;
}

?>


<html>
<head>
	<title>Input text to be analyzed</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<p><h2>Input text for analysis:</h2></p>
<form action="process.php?verify=1" method="post" name="analyze_text" accept-charset="UTF-8">
	<select name="lang">
		<option value="chinese" <?php 			if (!isset($_SESSION['lang']) || $_SESSION['lang'] == "chinese")		{echo "selected";}	?>>中文 （简体)</option>
		<option value="japanese" <?php 			if ( isset($_SESSION['lang']) && $_SESSION['lang'] == "japanese") 		{echo "selected";}	?>>日本語</option>
		<option value="japanese_kanji" <?php 	if ( isset($_SESSION['lang']) && $_SESSION['lang'] == "japanese_kanji") {echo "selected";}	?>>日本語 (漢字）</option>
	</select><br>
	<textarea name="textin" cols="150" rows="40"><?php if (isset($sametext) && $sametext) {echo $_SESSION['textin'];} ?></textarea><br>
	<input type="submit" name="action" value="Submit">
</form>
<br><br>
</body>
</html>