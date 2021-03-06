<?php
require_once 'p_error_page.php';

date_default_timezone_set('UTC');
mb_internal_encoding('utf-8');

//if (!@$_GET['0vr']) errorPage(HttpStatus::TemporarilyDown);

try {
	$dsn = 'mysql:host=127.0.0.1;dbname=stewart_lrca;charset=utf8';
	$opt = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
	];
	$Database = new PDO($dsn, 'stewart_lrca', 'w3b5i7e', $opt);

	unset($dsn);
	unset($opt);
} catch (Exception $ex) {
	errorPage(HttpStatus::InternalError);
}

$CanSendEmail = true;
$GoogleMapKey = 'AIzaSyAO14roLN-8rzZzmab1xl9EYP-Zeu5PvoI';
$UriBase = '/mockup/';
$UploadTempFolder = '/tmp/';

$DevNotices = [
	null => 'This mockup was created as a preview of the new, reintegrated <a href="http://leicestershirechess.org/">LRCA website</a>, which is now live.&nbsp; Those who have been given access to post news can still log in in order to use this to test the news posting facility.'
];
?>