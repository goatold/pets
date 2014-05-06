<?php
$tag = '';
if (isset($_REQUEST['tag'])) $tag = '?tag='. $_REQUEST['tag'];
echo <<<EOV
<a href="$urlbase/particip/add/$tag">+Add</a>
EOV;
if (count($vargs) < 1) die('<hr>No data!');
?>
<div>
<table class="vlist">
<tr>
<?php 
foreach (array_keys($vargs[0]) as $label) {
	echo '<th>' . $label . '</th>';
}
?>
<th colspan=2>Operations</th>
</tr>

<?php
foreach ($vargs as $q) {
	echo '<tr>';
	foreach ($q as $v) {
		echo '<td>' . $v . '</td>';
	}
	echo '<td><a href="' . $urlbase . '/particip/del/?id=' . $q['ID'] . '">-Del</a></td>' .
	     '<td><a href="' . $urlbase . '/particip/edit/?id=' . $q['ID'] . '">Edit</a></td>' .
	     '</tr>';
}
?>

</table>
</div>
