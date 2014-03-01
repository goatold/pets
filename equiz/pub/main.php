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
* main.php 2010-10-14 leow
* equiz web app main
*/

define('DEV_ENV', true);

// global config
$dirbase = dirname(__FILE__) . '/../';
$dirviews = $dirbase . 'app/views/';
$urlbase = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
$dbfile = 'sqlite:' . $dirbase . 'data/equiz_sqlite3.db';
$jqfile = $urlbase . '/../js/jq1.4.2.min.js';
$reqStr = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
$adminLogin = 'admin';
$adminPass = 'd0be2dc421be4fcd0172e5afceea3970e2f3d940';
$adminEmail = 'leo.wang@alcatel-lucent.com';
require($dirbase . 'app/controllers.php');
require($dirbase . 'app/models.php');

/** Check if environment is development and display errors **/
function setReporting() {
	error_reporting(E_ALL);
	if (DEV_ENV == true) {
	ini_set('display_errors','On');
	} else {
		ini_set('display_errors','Off');
		ini_set('log_errors', 'On');
		ini_set('error_log', $dirbase . '/tmp/log/error.log');
	}
}

/** Check for Magic Quotes and remove them **/
function stripSlashesDeep($value) {
	$value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
	return $value;
}

function removeMagicQuotes() {
	if ( get_magic_quotes_gpc() ) {
		$_GET    = stripSlashesDeep($_GET   );
		$_POST   = stripSlashesDeep($_POST  );
		$_COOKIE = stripSlashesDeep($_COOKIE);
	}
}

/** Check register globals and remove them **/
function unregisterGlobals() {
    if (ini_get('register_globals')) {
        $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
        foreach ($array as $value) {
            foreach ($GLOBALS[$value] as $key => $var) {
                if ($var === $GLOBALS[$key]) {
                    unset($GLOBALS[$key]);
                }
            }
        }
    }
}

/** Main Call Function **/
setReporting();
removeMagicQuotes();
unregisterGlobals();

$reqArray = explode("/", ltrim($reqStr, "/"));
$strCtrl = array_shift($reqArray);
$strAct = array_shift($reqArray);
$ctrl = null;
if (!isset($strCtrl) || !class_exists($strCtrl . 'Controller')) {
	$ctrl = new siteController();
} else {
	$ctrlClass = new ReflectionClass($strCtrl . 'Controller');
	$ctrl = $ctrlClass->newInstanceArgs(array());
}
if (isset($strAct)) {
	$ctrl->exec($strAct);
} else {
	$ctrl->exec(null);
}


