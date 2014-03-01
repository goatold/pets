<div>
<table border=1>
<tr>
<?php 
foreach (array_keys($vargs[0]) as $label) {
	echo '<td>' . $label . '</td>';
}
?>

</tr><tr>

<?php
foreach ($vargs as $q) {
	echo '<tr>';
	foreach ($q as $v) {
		echo '<td>' . $v . '</td>';
	}
	echo '<td><a href="' . $urlbase . '/quiz/del/?id=' . $q['ID'] . '">-Del</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/edit/?id=' . $q['ID'] . '">Edit</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/take/?id=' . $q['ID'] . '">Preview</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/state/?id=' . $q['ID'] . '">State</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/score/?id=' . $q['ID'] . '">Score</a></td>' .
	     '</tr>';
}
?>

</table>
<a href="<?php echo $urlbase . '/quiz/add/'?>">+Add</a>
<div><form method="post"
action="<?php echo $urlbase . '/quiz/score/'?>">
<input type="text" name="id"/>
<input type="submit" value="score" />
</form></div>
</div>
