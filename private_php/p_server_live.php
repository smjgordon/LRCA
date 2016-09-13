<?php
require_once 'p_error_page.php';

date_default_timezone_set('UTC');

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
	errorPage(500);
}

$CanSendEmail = true;
?>