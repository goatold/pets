<?php
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
* Common functions
* common.php 2010-09-16 leow
*/

function getParam($stringname) {
	if (isset($_REQUEST[$stringname])) {
		return urldecode($_REQUEST[$stringname]);
	} else {
		return NULL;
	}
}

function ldapQuery($filter) {
//	$filter = "upi=PA0019464";
	$ldaphost = "ldap-ap.post.lucent.com";
	$ldapdn = "ou=people, o=lucent.com";
	$ldapconn = ldap_connect($ldaphost) or die("Could not connect to {$ldaphost}");
	$ldapbind = ldap_bind($ldapconn);
	$search = @ldap_search($ldapconn, $ldapdn, $filter);
	$info = ldap_get_entries($ldapconn, $search);
	return $info;
//	var_dump($info[0]);
}

// generate random key string
function getRandKey($klen=18, $seeds="1234567890abcdefghijk", $prefix="") {
	srand((double)microtime()*1000000); // start the random generator
	$key = ""; // set the inital variable
	$slen = strlen($seeds);
	for ($i=0;$i<$klen;$i++) // loop and create key
	$key .= substr($seeds, rand()%$slen, 1);
	return $key;
}

