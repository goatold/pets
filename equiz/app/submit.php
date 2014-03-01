<?
/*
* EQuiz
* Copyright (C) 2010 Wang, Leo Li
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* EQuiz is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* submit.php 2010-09-16 leow
*/
// DB
require_once(dirname(__FILE__)."/equiz_db.php");
// Quiz classes
require_once(dirname(__FILE__)."/quiz.php");

$quiz_id = $_REQUEST['quiz_id'];
$quiz = new Quiz($quiz_id);
$quiz->dbLoada($eqdb);
$quiz->dbSubmita($_REQUEST);
if($quiz->isClosed()) {
	exit("Sorry, the Quiz has closed.");
}

print "Thanks %s for your submission."
?>

