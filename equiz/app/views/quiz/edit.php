<form autocomplete="off" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase . '/quiz/edit/'?>">
<table>
<?php
foreach($vargs['fields'] as $f=>$v) {
	$inp = '<input type="text" name="' . $f . '" value="' . $v['value'] . '"/>';
	if (isset($v['ftype']) && $v['ftype'] == 'textarea') {
		$inp = '<textarea name="' . $f . '" cols=80 rows=5>' . $v['value'] . '</textarea>';
	}
	echo '<tr><td>' . $v['label'] . '</td><td>' . $inp . '</td></tr>';
}
?>
</table>
<input type="hidden" name="id" value="<?php echo $vargs['id'];?>"/>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
