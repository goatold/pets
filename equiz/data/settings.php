<?php
/*
* EQuiz
* Copyright (C) 2010 Wang, Leo Li
* All rights reserved.
* License: GNU/GPL License v2 or later
* EQuiz is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* settings.php 2014-04-21 leow
* global settings
*/
$adminLogin = 'admin';
$adminPass = 'ef0ebbb77298e1fbd81f756a4efc35b977c93dae';
$subEmailDomain = '@ALCATEL-LUCENT.COM';
$smtpSvr = 'us70tusmtp1.zam.alcatel-lucent.com';
$smtpPort = 25;
$dbgEmail = 'leo.wang@alcatel-lucent.com';
date_default_timezone_set('Asia/Shanghai');
// default Quiz due after 2 days
$quizDueDay = new DateInterval('P2D');
// default Quiz due time
$quizDueTime = '18:00:00';
$defaultTag = 'test';
$viewPgSize = 10;
$quizMailHead = '<h2>Machine generated email. Do NOT reply. <br>' .
                'To submit answers, please view this message ' .
                'in a web browser or Click <a href="%s">here</a>. <br>' .
                'Contact %s (%s) for further assistance.</h2><hr>';
