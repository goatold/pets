<form method='post' action=
<?php echo "'" . $urlbase . "/site/login/'" ?>
>
<?php
if(isset($vargs['warnmsg'])) {
	echo '<div class="warn">' . $vargs['warnmsg'] . '</div>';
}
?>
<table>
<tr><td>login:</td><td><input type=text name='user'></td></tr>
<tr><td>password:</td><td><input type=password name='pass'></td></tr>
<tr><td colspan=2><input type=submit value='login'></td></tr>
</table>
</form>
