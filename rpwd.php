<?php
// TODO: refactor
require_once 'private_php/p_global.php';
require_once 'private_php/p_email.php';

$user = null;
$error = null;
$pageTitle = 'Password Recovery'; // will be changed to 'Password Creation' if the user is in pending password status

const EmailEntry = 1;
const PasswordEntry = 2;

// get reset key from URI
$uriParts = array_slice(explode('/', $_SERVER['REQUEST_URI']), -1);
$resetKeyStr = $uriParts[0];

// possible scenarios:
// no query parameters - initial entry to page
// POST method, parameter email - requesting key
// GET method, parameter key - using key
// POST method, parameters uid, key, pwd1 and pwd2 - actually setting the password

if (isset($_POST['email']) && trim($_POST['email']) == '') {
	$error = 'Please enter your email address';
	$mode = EmailEntry;

} else if ($email = @$_POST['email']) {
	global $fromEmail, $replyEmail;

	try {
		$user = User::loadByEmail($email);

		$Database->beginTransaction();
		try {
			$resetKeyStr = $user->generatePasswordResetKey($_POST['type'] == 'mig' ? 2 : 1);
			$Database->commit();
		} catch (Exception $ex) {
			$Database->rollBack();
			throw $ex;
		}

		$uri = joinUri(getCurrentUri(), "$resetKeyStr");

		// generate an email and send it
		switch ($_POST['type']) {
			/*case 'mig':
				$emailSubject = 'Welcome to the new LRCA Results Website';
				$emailMessage = 'Dear ' . $user->forename() . ",

The new LRCA Results Website has now been launched, and can be found here:

http://lrca.stewartsplace.org.uk/

You will notice some differences from the previous site.  A few to note
are:
- Each fixtures page leads directly to the results pages for the same
division
- Features that require you to be logged in, such as result submission,
will appear only when you actually are logged in
- Submission and approval are accessed through a single 'My Fixtures'
link, which takes you to a page where you will select the match for which
you wish to submit or approve a result and then enter the required data

If you have difficulty using this website or you find any bugs, large or
small, please do not hesitate to contact the Results Webmaster, Stewart
Gordon, at smjg@iname.com.

You will need to set a password for your user account.  This may be either
the same password you used on the old results website or a new one.  To
set a password, please visit the following page:

$uri

This link will expire 48 hours after this email was sent.  If you are not
able to use it within this period, you can still visit the page, but you
will be prompted to generate a new password reset key which will be
emailed to you.";
				break;*/

			default:
				$emailSubject = 'Password reset for the LRCA Website';
				$emailMessage = 'Dear ' . $user->forename() . ",

A password reset has been requested for your user account.  To set a new
password, please visit the following page:

$uri

This link will expire 24 hours after this email was sent.";

			if ($user->status() == UserStatus::Active) {
				$emailMessage .= '

If you did not request a password reset, please ignore this message.
Your existing password will continue to work.';
				}
		}

		emailConfirmation($emailSubject, $emailMessage, [$user], '../rpwd_generated?status=' . $user->status());
			//'rpwd_generated.php?uid=' . $user->id() . '&status=' . $user->status());

	} catch (ModelAccessException $ex) {
		$error = $ex->getMessage();
		$user = null;
		$mode = EmailEntry;

	} catch (UserAccountException $ex) {
		$error = $ex->getMessage();
		$user = null;
		$mode = EmailEntry;

	} catch (ReportableException $ex) {
		$error = $ex->getMessage();
		$user = null;
		$mode = EmailEntry;
	}

} else if ($resetKeyStr /*($resetKeyStr = @$_GET['key'])*/ || ($resetKeyStr = @$_POST['key'])) {
	try {
		$resetKey = PasswordResetKey::loadByKey($resetKeyStr);
		$user = $resetKey->user();
		$mode = PasswordEntry;

	} catch (ReportableException $ex) {
		$error = $ex->getMessage();
		$resetKey = null;
		$user = $ex->customData;
		$mode = EmailEntry;

	} catch (Exception $ex) {
		errorPage(HttpStatus::NotFound);
	}

	if ($user->status() == UserStatus::PendingPassword) {
		$pageTitle = 'Password Creation';
	}

	if ($mode == PasswordEntry && ($userID = @$_POST['uid'])) {
		$userID = (integer) $userID;
		if ($userID != $user->id()) errorPage(HttpStatus::NotFound);

		$password1 = @$_POST['pwd1'];
		$password2 = @$_POST['pwd2'];

		if ($password1 != $password2) {
			$error = 'Passwords do not match!';
		} else if (trim($password1) == '') {
			$error = 'Please enter a password!';
		} else {
			$Database->beginTransaction();
			$resetKey->setNewPassword($password1);
			$Database->commit();
			redirect(HttpStatus::RedirectSeeOther, '../password_set');
		}
	}

} else {
	// initial entry to page
	$mode = EmailEntry;
}

pageHeader($pageTitle);
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" method="post" action="./"><?php
	switch ($mode) {
		case EmailEntry:
		?>
			<p>
				<input type="hidden" name="type" id="type" value="req" />
				<label for="email">Email address:</label> <input type="text" name="email" id="email"
					value="<?php if ($user) echo $user->email(); ?>" />
			</p>

			<p><input type="submit" value="Generate Reset Key" /></p>
		<?php
			break;
		case PasswordEntry:
		?>
			<p>
				<label for="email">Email address:</label>
				<span id="email"><?php echo htmlspecialchars($user->email()); ?></span>
				<input type="hidden" name="uid" value="<?php echo $user->id(); ?>" />
				<input type="hidden" name="kid" value="<?php echo $resetKey->id(); ?>" />
				<input type="hidden" name="key" value="<?php echo $resetKeyStr; ?>" />
			</p>
			<p><label for="pwd1">New password:</label> <input type="password" name="pwd1" id="pwd1" /></p>

			<p><label for="pwd2">Re-enter new password:</label> <input type="password" name="pwd2" id="pwd2" /></p>

			<p><input type="submit" value="Set Password" /></p>
		<?php
	}
?></form>

<?php
pageFooter();
?>