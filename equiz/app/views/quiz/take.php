<?php
include 'usrview.php';
?>
<html><head>
<?php echo $htmlcss;?>
</head><body>
<?php
$html = genQzHtml($vargs);
echo $html[0];
echo genPinfoHtml($vargs['token'], $vargs['pid']);
echo $html[1];
?>
</body></html>

