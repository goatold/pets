<form autocomplete="off" enctype="multipart/form-data" method="post"
action="<?php echo $urlbase . '/question/add/'?>">
<table>
<?php
require 'common.php';
foreach($vargs as $f=>$v) {
	if (isset($formfields[$f])) {
		if ($formfields[$f] == 'textarea') {
			$inp = '<textarea name="' . $f . '" cols=50 rows=3></textarea>';
		} elseif ($formfields[$f] == 'text') {
			$inp = '<input type="text" name="' . $f . '"/>';
		} elseif ($formfields[$f] == 'select') {
			$inp = '<select name="' . $f . '">';
			foreach($v['options'] as $ov=>$op) {
				$inp .= '<option value="' . $ov . '"';
				if (isset($v['value']) && $ov == $v['value']) {
					$inp .= ' selected';
				}
				$inp .= '>' . $ov . ':' . $op . '</option>';
			}
			$inp .= '</select>';
		} else {
			continue;
		}
	} else {
		continue;
	}
	echo '<tr><td>' . $v['label'] . '</td><td>' . $inp . '</td></tr>';
}
?>
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
