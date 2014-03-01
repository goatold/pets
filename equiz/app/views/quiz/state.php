<?php 
$qid = $vargs['qid'];
echo '<div>quiz#'.$qid.': '.$vargs['title'].'</div>';
?>
<div>
<?php count($vargs['pinfo'])>0 or die('no participant!');?>
<table border=1>
<tr>
<?php 
foreach (array_keys($vargs['pinfo'][0]) as $label) {
	echo '<td>' . $label . '</td>';
}
?>

</tr><tr>

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
<a href="<?php echo $urlbase . '/quiz/token/?id=' . $qid?>">gen Token for all who got none</a>
<a href="<?php echo $urlbase . '/quiz/email/?id=' . $qid?>">email notice to all</a>
</div>
