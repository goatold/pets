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
	echo '<td><a href="' . $urlbase . '/question/del/?id=' . $q['ID'] . '">-Del</a></td>' .
	     '<td><a href="' . $urlbase . '/question/edit/?id=' . $q['ID'] . '">Edit</a></td>' .
	     '</tr>';
}
?>

</table>
<a href="<?php echo $urlbase . '/question/add/'?>">+Add</a>
</div>
