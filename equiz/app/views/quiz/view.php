<div>
<table class="vlist">
<tr>
<?php 
foreach (array_keys($vargs['data'][0]) as $label) {
	echo '<th>' . $label . '</th>';
}
?>
<th colspan=7>Operations</th>
</tr>

<?php
foreach ($vargs['data'] as $q) {
	echo '<tr>';
	foreach ($q as $v) {
		echo '<td>' . $v . '</td>';
	}
	echo '<td><a href="' . $urlbase . '/quiz/del/?id=' . $q['ID'] . '">-Del</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/edit/?id=' . $q['ID'] . '">Edit</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/take/?id=' . $q['ID'] . '" target="_blank">Preview</a></td>' .
	     '<td><a href="' . $urlbase . '/question/view/?quizId=' . $q['ID'] . '">Questions</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/state/?id=' . $q['ID'] . '">State</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/score/?id=' . $q['ID'] . '">Score</a></td>' .
	     '<td><a href="' . $urlbase . '/quiz/review/?id=' . $q['ID'] . '" target="_blank">Review</a></td>' .
	     '</tr>';
}
$plnk = '<a href="' . $urlbase . '/quiz/view/?pagen=%d">%s</a>';
?>

</table>
<a href="<?php echo $urlbase . '/quiz/add/'?>">+Add</a>
<div><form method="post"
action="<?php echo $urlbase . '/quiz/score/'?>">
<input type="text" name="id"/>
<input type="submit" value="score" />
</form></div>
</div>
<table><tr>
<td><?php if ($vargs['pn'] > 1) echo sprintf($plnk, 1, 'First'); ?> </td>
<td><?php if ($vargs['pn'] > 2) echo sprintf($plnk, intval($vargs['pn']-1), 'Previous'); ?>
<td>page <?php echo $vargs['pn'] .' of '. $vargs['maxpg']?></td>
<td><?php if ($vargs['pn'] < (intval($vargs['maxpg'])-1)) echo sprintf($plnk, intval($vargs['pn']+1), 'Next'); ?> </td>
<td><?php if ($vargs['pn'] < $vargs['maxpg']) echo sprintf($plnk, $vargs['maxpg'], 'Last'); ?></td>
</tr></table>

