<div>
<table class="vlist">
<?php 
$args = '?';
if (isset($vargs['quizId'])) {
	$args .= 'quizId=' . $vargs['quizId'];
}

if (isset($vargs['data'][0])) {
	echo '<tr>';
	foreach (array_keys($vargs['data'][0]) as $label) {
		echo '<th>' . $label . '</th>';
	}
	echo '<th colspan=2>Operations</th>';
	echo '</tr>';

	foreach ($vargs['data'] as $q) {
		echo '<tr>';
		foreach ($q as $v) {
			echo '<td>' . $v . '</td>';
		}
		echo '<td><a href="' . $urlbase . '/question/del/?id=' . $q['ID'] . '">-Del</a></td>' .
	     	'<td><a href="' . $urlbase . '/question/edit/?id=' . $q['ID'] . '">Edit</a></td>' .
	     	'</tr>';
	}
} else {
	echo 'No Data!';
}
?>

</table>
<a href="<?php echo $urlbase . '/question/add/' . $args?>">+Add Question</a>
</div>
