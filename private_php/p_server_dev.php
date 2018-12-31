<?php
require_once 'p_error_page.php';

date_default_timezone_set('UTC');
mb_internal_encoding('utf-8');

try {
	$dsn = 'mysql:host=127.0.0.1;dbname=lrca;charset=utf8';
	$opt = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
	];
	$Database = new PDO($dsn, 'website', 'w3b5i7e', $opt);

	unset($dsn);
	unset($opt);
} catch (Exception $ex) {
	echo $ex->getMessage();
	errorPage(500);
}

$CanSendEmail = false;
$GoogleMapKey = 'AIzaSyDVK5jT-tHmFw5nixdZHIS5kh96ztK2zcc';
$UriBase = '/lrca/';
$UploadTempFolder = 'C:/windows/temp/';

$DevNotices = [];
?>