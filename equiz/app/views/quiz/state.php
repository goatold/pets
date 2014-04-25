<?php 
$qid = $vargs['qid'];
echo '<div>quiz#'.$qid.': '.$vargs['title'].'</div>';
?>
<div>
<table><tr>
<td><a href="<?php echo $urlbase . '/quiz/token/?id=' . $qid?>">gen Token for all who got none</a></td>
<td><a href="<?php echo $urlbase . '/quiz/email/?id=' . $qid?>">email notice to all</a></td>
</tr></table>
<?php count($vargs['pinfo'])>0 or die('no participant!');?>
<table class="vlist">
<tr>
<?php 
foreach (array_keys($vargs['pinfo'][0]) as $label) {
	echo '<th>' . $label . '</th>';
}
?>
<th colspan=2>Operations</th>
</tr>

<?php
foreach ($vargs['pinfo'] as $r) {
	echo '<tr>';
	foreach ($r as $v) {
		echo '<td>' . $v . '</td>';
	}
	echo '<td><a href="' . $urlbase . '/quiz/token/?id=' . $qid . '&pid=' . $r['id'] .
	     '">genToken</a></td><td><a href="' . $urlbase . '/quiz/email/?id=' . $qid . '&pid=' . $r['id'] .
	     '">email</a></td></tr>';
}
?>

</table>
</div>
