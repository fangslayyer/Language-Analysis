<?php
session_start();
require('config.php');
$link = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error());
mysqli_select_db($link, SQL_DB);
mb_regex_encoding('UTF-8');
mb_internal_encoding("UTF-8");

foreach ($_GET as $key => $value) {
	$$key = $value;
}
foreach ($_POST as $key => $value) {
	$$key = $value;
}

// IMPORTANT: MUST INCLUDE THIS OR ELSE MYSQL WILL NOT USE UTF-8
$sql = "SET NAMES utf8;";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));

if (!isset($sortby)) {
	$sortby = "rank";
}
if (!isset($order)) {
	$order = "DESC";
}
if (!isset($remove_misc)) {
	$remove_misc = 0;
}

// ----------------------------------------------------
// GET CURVE DETAILS
// ----------------------------------------------------
$sql = "SELECT * FROM curve ORDER BY percentage ASC";
$result = mysqli_query($link, $sql);
$i = 0;
while ($row = mysqli_fetch_array($result)) {
	$occur_percent_x[$i] = $row['percentage'];
	$occur_overall_y[$i] = $row['char_overall'];
	$occur_this_y[$i]    = $row['char_this'];
	$i++;
}

$curve_table  = "<table id='curve'>
	<thead>";
	foreach ($occur_percent_x as $value) {
		$curve_table .= "<th>" . $value . "%</th>";
	}
$curve_table .= "
	</thead>
	<tr>";
	foreach ($occur_this_y as $value) {
		$curve_table .= "<td>" . $value . "</td>";
	}
$curve_table .= "
	</tr>
	<tr>";
	foreach ($occur_overall_y as $value) {
		$curve_table .= "<td>" . $value . "</td>";
	}
$curve_table .="
	</tr>
	</table>";


// script for showing graph
$script = <<<EOD
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Percentage', 'Characters overall', 'Characters this'],

EOD;

foreach ($occur_percent_x as $key => $value) {
	$script .= "[ " . $value . ", " . $occur_overall_y[$key] . ", null],\n";
}
foreach ($occur_percent_x as $key => $value) {
	$script .= "[ " . $value . ", null, " . $occur_this_y[$key] . "],\n";
}
$script  = substr_replace($script, "", -2); // replaces last comma, which should not be there

$maxValue = $occur_overall_y[count($occur_overall_y) - 1];
$script .= <<<EOD

        ]);

        var options = {
          title: 'Percentage vs. Character comparison',
          hAxis: {title: 'Percentage', minValue: 0, maxValue: 100},
          vAxis: {title: 'Characters', minValue: 0, maxValue: $maxValue},
          legend: {position: 'bottom', textstyle: {fontsize: 14}}
        };

        var chart = new google.visualization.ScatterChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
EOD;
?>

<html>
<head>
	<title>Analysis</title>
	<link rel="stylesheet" type="text/css" href="style.css">	
<?php echo $script; ?>
</head>
<body>
<h2>Curve</h2>
<center><div id="chart_div" style="width: 1200px; height: 600px;"></div></center>
<?php echo $curve_table; ?>

<hr>
<a href="input.php">Return</a> with clear form or 
<a href="input.php?sametext=1">Try again</a> with same text<br>
<?php
if (!$remove_misc) {
	echo '<a href="display.php?sortby=' . $sortby . '&order=' . $order . '&remove_misc=1">Remove non-ranked characters</a><br>';
} else {
	echo '<a href="display.php?sortby=' . $sortby . '&order=' . $order . '&remove_misc=0">Show non-ranked characters</a><br>';
}
?>


<?php 
// makes words clickable, linking to their respective dictionaries
// ___WORD_REPLACE___ is keyword to be replaced for all languages in url to search for term
switch ($_SESSION['lang']) {
	case "chinese":
		$dictionary = "http://www.mdbg.net/chindict/chindict.php?page=worddict&wdrst=0&wdqb=___WORD_REPLACE___";
		break;

	case "japanese":
	case "japanese_kanji":
		$dictionary = "http://jisho.org/words/?jap=___WORD_REPLACE___&eng=&dict=edict";
		break;
}
function dictionary($word) {	
	if (isset($GLOBALS['dictionary'])) {	
		$dictionary = $GLOBALS['dictionary'];
		$dictionary_replaced = str_replace("___WORD_REPLACE___", $word, $dictionary);
		return "<td class='first'><a href='$dictionary_replaced' target='_blank' class='character'><p class='{$_SESSION['lang']}'>$word</p></a></td>";
	} else {
		return "<td class='first'><p class='{$_SESSION['lang']}'>$word</p></td>";
	}
}


$length = 0;
$char_unique_count = 0;
$sql  = "SELECT * FROM display ";
if ($remove_misc){
	$sql .= "WHERE rank IS NOT NULL ";
}
$sql .="ORDER BY $sortby $order ";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));
$table_data = null;
while ($row = mysqli_fetch_array($result)) {
	$table_data .= "
		<tr>";
	$table_data .= dictionary($row['word']);
	$table_data .= "
			<td>{$row['cnt']}</td>
			<td>{$row['rank']}</td>
			<td>{$row['ind_freq']}</td>
			<td>{$row['cum_freq']}</td>
		</tr>";
	if (isset($row['rank'])) {
		$length = $length + $row['cnt'];
		$char_unique_count++;
	}
}

echo "Total characters: $length <br>";
echo "Unique characters: $char_unique_count <br>";
?>
<table id="character_display" align="center">
	<thead>
		<th>Character</th>
		<th><a href='display.php?sortby=cnt&remove_misc=<?php 		echo $remove_misc; if($sortby=="cnt" 		&& $order=="DESC") {echo "&order=ASC";} ?>'>Occurances</a></th>
		<th><a href='display.php?sortby=rank&remove_misc=<?php 		echo $remove_misc; if($sortby=="rank" 		&& $order=="DESC") {echo "&order=ASC";} ?>'>Rank</a></th>
		<th><a href='display.php?sortby=ind_freq&remove_misc=<?php 	echo $remove_misc; if($sortby=="ind_freq" 	&& $order=="DESC") {echo "&order=ASC";} ?>'>Individual Frequency</a></th>
		<th><a href='display.php?sortby=cum_freq&remove_misc=<?php 	echo $remove_misc; if($sortby=="cum_freq" 	&& $order=="DESC") {echo "&order=ASC";} ?>'>Cumulative Frequency</a></th>
	</thead>
<?php echo $table_data; ?>
</table><br>
<a href="input.php?sametext=1&lang=<?php echo $_SESSION['lang'] ?>">Try again</a> with same text<br>

</body>
</html>
