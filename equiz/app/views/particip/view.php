<div>
<a href="<?php echo $urlbase . '/particip/add/'?>">+Add</a>
<table class="vlist">
<tr>
<?php 
foreach (array_keys($vargs[0]) as $label) {
	echo '<th>' . $label . '</th>';
}
?>
<th colspan=2>Operations</th>
</tr>

<?php
foreach ($vargs as $q) {
	echo '<tr>';
	foreach ($q as $v) {
		echo '<td>' . $v . '</td>';
	}
	echo '<td><a href="' . $urlbase . '/particip/del/?id=' . $q['ID'] . '">-Del</a></td>' .
	     '<td><a href="' . $urlbase . '/particip/edit/?id=' . $q['ID'] . '">Edit</a></td>' .
	     '</tr>';
}
?>

</table>
</div>
