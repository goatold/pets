<form id="quizfm" name="quizfm" class="quizForm" autocomplete="off" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase . '/quiz/add/'?>">
<table>
<?php
foreach($vargs as $f=>$v) {
	if (isset($v['value'])) {
		$inp = '<input type="text" name="' . $f . '" value="' . $v['value'] . '"/>';
	} else {
		$inp = '<input type="text" name="' . $f . '"/>';
	}
	if (isset($v['ftype']) && $v['ftype'] == 'textarea') {
		$inp = '<textarea name="' . $f . '" cols=80 rows=5></textarea>';
	}
	echo '<tr><td>' . $v['label'] . '</td><td>' . $inp . '</td></tr>';
}
?>
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
