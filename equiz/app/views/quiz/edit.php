<?php
require_once 'common.php';
$act = $urlbase . '/quiz/edit/?id='. $vargs['id'];

echo genFormHtml($vargs['fields'], $act, $formfields);
?>
