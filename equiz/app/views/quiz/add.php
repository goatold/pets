<form id="quizfm" name="quizfm" class="quizForm" autocomplete="off" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase . '/quiz/add/'?>">
<table>
<?php
foreach($vargs as $f=>$v) {
	$inp = '<input type="text" name="' . $f . '"/>';
	if (isset($v['ftype']) && $v['ftype'] == 'textarea') {
		$inp = '<textarea name="' . $f . '"></textarea>';
	}
	echo '<tr><td>' . $v['lable'] . '</td><td>' . $inp . '</td></tr>';
}
?>
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
