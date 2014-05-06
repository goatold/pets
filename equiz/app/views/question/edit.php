<?php
require_once 'common.php';
$act = $urlbase . '/question/edit/?id='. $vargs['id'];

echo genFormHtml($vargs['fields'], $act, $formfields);
?>
