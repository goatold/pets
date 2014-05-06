<?php
/*
* EQuiz
* Copyright (C) 2014 Wang, Leo Li
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* EQuiz is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* Common functions to generate html view
* vcommon.php 2014-05-04 leow
*/

function genFormHtml($vargs, $act, $formfields) {
	$htmlGen = array('text' => 'genInpTextHtml',
	                 'textarea' => 'genTextareaHtml',
	                 'checkbox' => 'genChkBHtml',
	                 'select' => 'genSelHtml');

	$html = <<<EOV
<form autocomplete="on" enctype="multipart/form-data" method="post"
action="$act">
<table>
EOV;
	foreach($vargs as $f=>$v) {
		if (isset($formfields[$f]) && isset($htmlGen[$formfields[$f]])) {
			$html .= '<tr><td>'. $v['label'] .'</td><td>'. $htmlGen[$formfields[$f]]($f, $v) .'</td></tr>';
		}
	}
	$html .= <<<EOV
</table>
<input type="reset" value="reset" />
<input type="submit" value="submit" />
</form>
EOV;
	return $html;
}

function genInpTextHtml($f, $v) {
	$html = '<input type="text" name="' . $f . '"';
	if (isset($v['value'])) {
		$html .= ' value="' . $v['value'] . '"';
	}
	$html .= '/>';
	return $html;
}

function genTextareaHtml($f, $v) {
	$html = '<textarea name="' . $f . '" cols=80 rows=5>';
	if (isset($v['value'])) {
		$html .=  $v['value'];
	}
	$html .= '</textarea>';
	return $html;
}

function genChkBHtml($f, $v) {
	$html = '';
	foreach($v['choices'] as $k=>$c) {
		$idname = $f .'_'. $k;
		$html .= '<input id="'. $idname .'" name="'. $idname .'" type="checkbox"';
		if (in_array($k, $v['values'])) $html .= ' checked ';
		$html .= '/><label class="choice" for="'. $idname .'">'. $c .' </label><br>';
	}
	return $html;
}

function genSelHtml($f, $v) {
	$html = '<select name="' . $f . '">';
	foreach($v['options'] as $ov=>$op) {
		$html .= '<option value="' . $ov . '"';
		if (isset($v['value']) && $ov == $v['value']) {
			$html .= ' selected';
		}
		if ($ov != $op) $ov .= ':'. $op;
		$html .= '>'. $ov .'</option>';
	}
	$html .= '</select>';
	return $html;
}
?>

