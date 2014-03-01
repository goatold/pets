#!/usr/bin/php
<?
require_once(dirname(__FILE__)."/equiz_db.php");
require_once(dirname(__FILE__)."/common.php");
require_once(dirname(__FILE__)."/htmltempl.php");
require_once(dirname(__FILE__)."/quiz.php");

$emlst = file('data/emlst_plex.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "email\t\tname in post\n";
$i = 0;
foreach ($emlst as $em) {
	$em = trim($em, " ;");
	$nm = ldapQuery("mail=".$em);
	if (isset($nm[0])){
		$nm = $nm[0]['displayname'][0];
	} else {
		$nm = "query post failed";
	}
	$nm = ltrim($nm, " %");
	echo $em."\t\t<".$nm.">\n";
	$i++;
}
echo "total: ".$i."\n";

?>
