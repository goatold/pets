<html><head>
<title>EQuiz unsubscription</title>
</head><body>
<h1 id="logo">
<!--equiz logo here-->
<img src="
<?php echo $urlbase . '/../images/equiz_logo.png';?>
">
</h1>
<?php
require_once 'common.php';
global $dirviews;
require_once $dirviews.'/vcommon.php';

$act = $urlbase . '/particip/unsub/';

echo genFormHtml($vargs, $act, $formfields);
?>
