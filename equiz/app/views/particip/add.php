<form autocomplete="off" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase . '/particip/add/'?>">
<table>
<?php
foreach($vargs as $f=>$v) {
	$inp = '<input type="text" name="' . $f . '"/>';
	echo '<tr><td>' . $v['lable'] . '</td><td>' . $inp . '</td></tr>';
}
?>
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
