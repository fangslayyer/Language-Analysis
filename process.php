<?php
session_start();
require('config.php');
$link = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error($link));
mysqli_select_db($link, SQL_DB);
mb_regex_encoding('UTF-8');
mb_internal_encoding("UTF-8");

// drop previously generated display tables to make room for new ones
$drop = "DROP TABLE IF EXISTS display;";
mysqli_multi_query($link, $drop) or die(mysql_error());
$drop = "DROP TABLE IF EXISTS curve;";
mysqli_multi_query($link, $drop) or die(mysql_error());

foreach ($_POST as $key => $value) {
	$$key = $value;
}
foreach ($_GET as $key => $value) {
	$$key = $value;
}

$_SESSION['textin'] = $textin;
$_SESSION['lang'] = $lang;

function mb_str_split( $string ) { 
    # Split at all position not after the start: ^ 
    # and not before the end: $ 
    return preg_split('/(?<!^)(?!$)/u', $string ); 
} 

function curve($occur_percent_x) {
	$link = $GLOBALS['link'];
	$length = $GLOBALS['length'];
	$lang = $GLOBALS['lang'];
	foreach ($occur_percent_x as $value) {
		if ($value > 100) {
			echo "can't know more than everything!";
			return false;
		}
	}

	$sql = "SELECT i.word, i.cnt, c.rank FROM in_count AS i
			LEFT JOIN $lang AS c ON i.word = c.word ORDER BY c.rank ASC";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));

	$total = 0;
	$i = 0;
	$j = 1;
	$occur_char_overall_y[] = null;
	$occur_char_this_y[]    = null;
	$max_param = count($occur_percent_x);
	while ($row = mysqli_fetch_array($result)) {
		if (isset($row['rank'])) {
			$total = $total + $row['cnt'];
			while ($i < $max_param && $total / $length >= $occur_percent_x[$i] / 100.0) {
				$occur_char_overall_y[$i] = $row['rank'];
				$occur_char_this_y[$i]    = $j;
				$i++;
			}
		$j++;
		}
	}

	$sql = "CREATE TABLE IF NOT EXISTS curve (
				percentage FLOAT(4) NOT NULL,
				char_overall INT(5) NOT NULL,
				char_this INT(5) NOT NULL,
				PRIMARY KEY (percentage)
			)";
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));

	$sql = "INSERT INTO curve (percentage, char_overall, char_this) VALUES ";
	foreach ($occur_percent_x as $key => $value) {
		$sql .= "($occur_percent_x[$key], $occur_char_overall_y[$key], $occur_char_this_y[$key]),"; 
	}
	$sql  = substr_replace($sql, "", -1);
	$result = mysqli_query($link, $sql) or die(mysqli_error($link));
}


// IMPORTANT: MUST INCLUDE THIS OR ELSE MYSQL WILL NOT USE UTF-8
$sql = "SET NAMES utf8;";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));

$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS input (
			input_id INT(11) NOT NULL AUTO_INCREMENT,
			word VARCHAR(20) NOT NULL,
			PRIMARY KEY (input_id))";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));

// #################################################################
// ROUTE DIFFERENT PROCESS DEPENDING ON SPECIFIED LANGUAGE
// #################################################################
$sql_insert  = "INSERT INTO input (input_id, word) VALUES ";
switch ($lang) {
	case "chinese":
	case "japanese_kanji":
		$in_array = mb_str_split($textin);
		foreach ($in_array as $value) {
			if ($value != "'") {
				$sql_insert .= "(NULL, '$value'),";
			} else {
				$sql_insert .= "(NULL, \"$value\"),";
			}
		}
		break;

	case "japanese":
		$textin_sjis = iconv('utf-8', 'sjis', $textin);
		file_put_contents("mecab\jp.txt", $textin_sjis);
		exec("mecab\insert_space.bat") or die("fail");
		$chasen = file_get_contents("mecab\jp_chasen.txt");
		$chasen_converted = iconv('sjis', 'utf-8', $chasen);
		file_put_contents("mecab\jp_chasen_utf.txt", $chasen_converted);

		$lines = file("mecab\jp_chasen_utf.txt");
		foreach ($lines as $line_num => $line) {
			$line_text = explode("\t", $line);
			if (isset($line_text[2])) {
				if (strpos($line_text[2], "'") === false) {
					$sql_insert .= "(NULL, '$line_text[2]'),";
				} else {
					$sql_insert .= "(NULL, \"$line_text[2]\"),";
				}
			}
		}
		break;
}


$sql_insert  = substr_replace($sql_insert, "", -1); // replaces last comma, which should not be there
$result = mysqli_query($link, $sql_insert) or die(mysqli_error($link));

$sql1 = "CREATE TEMPORARY TABLE IF NOT EXISTS in_count AS (
			SELECT i.word, count(i.word) AS cnt
			FROM input AS i
			GROUP BY i.word ORDER BY cnt DESC
		);";
$sql2 = "CREATE TABLE IF NOT EXISTS display AS (
			SELECT i.word, i.cnt, c.rank, c.ind_freq, c.cum_freq
			FROM in_count AS i
			LEFT JOIN $lang AS c ON i.word = c.word 
			ORDER BY c.rank DESC
		);";
$result = mysqli_query($link, $sql1) or die(mysqli_error($link));
$result = mysqli_query($link, $sql2) or die(mysqli_error($link));


// get length of article, only including recognized characters
$length = 0;
$sql = "SELECT cnt, rank FROM display";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));
while ($row = mysqli_fetch_array($result)) {
	if(isset($row['rank'])) {
		$length = $length + $row['cnt'];
	}
}

// generate curve table
$occur_percent_x = array(50, 75, 85, 90, 95, 98, 99, 99.5, 99.8, 100);
curve($occur_percent_x);

if ($verify) {
	header('Location: display.php');
} else {
	header('Location: input.php');
}
?>