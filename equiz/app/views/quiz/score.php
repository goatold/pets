<div>
<?php count($vargs) > 0 or die('no data!');?>
<h1>
Scores:
</h1>
<table class="vlist">
<?php
$head = true;
foreach ($vargs as $s) {
	if ($head) {
		foreach (array_keys($s) as $label) {
			echo '<th>' . $label . '</th>';
		}
		$head = false;
	}
	echo '<tr>';
	foreach ($s as $v) {
		echo '<td>' . $v . '</td>';
	}
	echo '</tr>';
}
?>

</table>
</div>
