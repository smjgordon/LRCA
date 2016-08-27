<?php
require_once 'private_php/p_server.php';
require_once 'private_php/p_uri_functions.php';

$sessionKey = @$_COOKIE['session'];

if ($sessionKey) {
	$stmt = $Database->prepare('
		UPDATE session SET status = 2, logout_date = ?
		WHERE session_key = ?');
	$stmt->execute([date('c'), $sessionKey]);
}

setcookie('session', '', 1);
redirect(303, './');
?>