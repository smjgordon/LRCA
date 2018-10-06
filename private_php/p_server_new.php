<?php
require_once 'p_error_page.php';

date_default_timezone_set('UTC');
mb_internal_encoding('utf-8');

//if (!@$_GET['0vr']) errorPage(HttpStatus::TemporarilyDown);

try {
	$dsn = 'mysql:host=127.0.0.1;dbname=leicest3_lrca;charset=utf8';
	$opt = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false,
	];
	$Database = new PDO($dsn, 'leicest3_website', 'W3b5i7e', $opt);

	unset($dsn);
	unset($opt);
} catch (Exception $ex) {
	errorPage(HttpStatus::InternalError);
}

$CanSendEmail = true;
$GoogleMapKey = 'AIzaSyCWfnKR9RHLrNX_OCw9Ml8wCzrZsn4UDNw';
$UriBase = '/';
$UploadTempFolder = '/tmp/';

$DevNotices = [];
?>