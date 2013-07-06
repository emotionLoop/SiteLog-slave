<?php
$dbInfo = array(
	'host' => 'EXTERNALHOST',// External host, with port, if necessary, example: example.com:3306
	'db' => 'DATABASE',
	'user' => 'DB_USER',
	'pwd' => 'DB_PWD'
);

$url = 'http://example.com/';// URL to go in the email

include 'helper.inc.php';

new DB($dbInfo);
new HelperGuru($url);
?>