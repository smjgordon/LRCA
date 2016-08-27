<?php
// TODO: update once user class has been refactored
require_once 'p_server.php';
require_once 'p_system_settings.php';
require_once 'p_uri_functions.php';

function emailConfirmation($subject, $message, $toUsers, $redirectUri) {
	global $CanSendEmail;

	foreach ($toUsers as $user) {
		$toStrings[] = '"' . $user->fullName() . '" <' . $user->email() . '>';
	}
	$toHeader = implode(', ', $toStrings);

	if ($CanSendEmail) {
		mail($toHeader, $subject, $message, 'From: ' . SystemSettings::$fromEmail . '
Reply-To: ' . SystemSettings::$replyEmail . '
CC: ' . SystemSettings::$ccEmail);
		redirect(303, $redirectUri);

	} else {
		pageHeaderNoDb("Email Confirmation Test");
	
	?>
		<p>From: <?php echo htmlspecialchars(SystemSettings::$fromEmail); ?></p>
		<p>To: <?php echo htmlspecialchars($toHeader); ?></p>
		<p>Subject: <?php echo htmlspecialchars($subject); ?></p>
		<p>Reply-To: <?php echo htmlspecialchars(SystemSettings::$replyEmail); ?></p>
	
		<pre><?php echo htmlspecialchars($message); ?></pre>
		
		<p><a href="<?php echo htmlspecialchars($redirectUri); ?>">Continue</a></p>
	<?php
		pageFooterNoDb();
		exit;
	}
}
?>