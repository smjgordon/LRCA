<?php
// TODO: update once User has been refactored
require_once 'm_user.php';
require_once 'p_uri_functions.php';

// this variable will be used throughout the system to identify the currently logged in user
$CurrentUser = null;

$sessionKey = @$_COOKIE['session'];
if ($sessionKey) {
	$stmt = $Database->prepare('SELECT * FROM session WHERE status = 1 AND session_key = ?');
	$stmt->execute([$sessionKey]);
	if ($row = $stmt->fetch()) {
		if (time() < strtotime($row['expiry_date'])) {
			$CurrentUser = new User($row['user_id']);
		} else {
			$stmt = $Database->prepare('
				UPDATE session SET status = 3, logout_date = expiry_date
				WHERE session_key = ?');
			$stmt->execute([$sessionKey]);
			setcookie('session', '', 1);
		}
	}

	unset($stmt);
	unset($row);
}
unset($sessionKey);

function requireLogin($permissionsNeeded = null) {
	global $CurrentUser;
	
	if (!$CurrentUser) {
		redirect(303, 'login.php?url=' . urlencode($_SERVER['REQUEST_URI']));
	}
	
	if ($permissionsNeeded) {
		foreach ($permissionsNeeded as $perm) {
			if (!$CurrentUser->hasPermission($perm)) {
				errorPage(403);
			}
		}
	}
}
?>