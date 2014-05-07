<div>
<a href="<?php echo $urlbase . '/tag/add/'?>">+Add</a>
<table class="vlist">
<tr>
<th>Tag</th>
<th colspan=3>Operations</th>
</tr>

<?php
foreach ($vargs as $t) {
	echo '<tr><td>' . $t . '</td>';
	echo '<td><a href="' . $urlbase . '/tag/del/?tag=' . $t . '">-Del</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/view/?tag=' . $t . '">Quizes</a></td>' .
	     '<td><a href="' . $urlbase . '/particip/view/?tag=' . $t . '">Subscribers</a></td>' .
	     '</tr>';
}
?>

</table>
</div>
