<?php
require_once 'common.php';
$act = $urlbase . '/particip/edit/?id='. $vargs['id'];

echo genFormHtml($vargs['fields'], $act, $formfields);
?>
