<?php
require_once 'p_html_functions.php';

function validateCaptcha() {
	global $Database;
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		session_start();
		require_once 'securimage/securimage.php';
		$securimage = new Securimage();
		
		if ($securimage->check($_POST['captcha_code'])) {
			$activationTime = time();
			$activationTimeStr = date('c', $activationTime);
			$expiryTime = $activationTime + 7200; // captcha expires 2 hours from now

			// now create the new captcha validation record
			$captchaKey = generateKey(40);
			$stmt = $Database->prepare('
				INSERT INTO captcha(captcha_key, user_agent, ip_address, activation_date, expiry_date)
				VALUES(?, ?, ?, ?, ?)');

			$stmt->execute([$captchaKey, substr($_SERVER['HTTP_USER_AGENT'], 0, 255), $_SERVER['REMOTE_ADDR'],
				$activationTimeStr, date('c', $expiryTime)]);

			setcookie('captcha', $captchaKey);
			$redirectTo = @$_POST['referrer'] or $redirectTo = './';
			redirect(HttpStatus::RedirectSeeOther, $redirectTo);
		} else {
			return 'The characters in the image were not entered correctly.';
		}
	} else {
		// initial captcha entry
		return null;
	}
}

function haveValidCaptcha() {
	global $Database;
	
	$captchaKey = @$_COOKIE['captcha'];
	if ($captchaKey) {
		$stmt = $Database->prepare('SELECT * FROM captcha WHERE captcha_key = ?');
		$stmt->execute([$captchaKey]);
		if ($row = $stmt->fetch()) {
			if (time() < strtotime($row['expiry_date']) && $_SERVER['REMOTE_ADDR'] == $row['ip_address']) {
				return true;
			} else {
				setcookie('captcha', '', 1);
				return false;
			}
		}
	}
	return false;
}
?>