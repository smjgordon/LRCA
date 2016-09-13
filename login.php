<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_uri_functions.php';

$error = null;

if (isset($_POST['email'])) {
	$email = $_POST['email'];
	$password = @$_POST['pwd'];
	
	$email = trim($email);
	if ($email == '') {
		$error = 'Please enter your email address';
	} else if ($password == '') {
		$error = 'Please enter your password';
	} else {
		try {
			$user = User::loadByEmail($email);
			$user->logIn($password);

			// TODO: create a landing page for a logged-in user
			// for now, redirect back to the index page
			redirect(303, './');
		} catch (ReportableException $ex) {
			$error = $ex->getMessage();

		} catch (Exception $ex) {
			errorPage(500);
		}
	}
}

pageHeader('Log In');
?>

<h2>Log In</h2>

<div class="devNotice">
	<p>All user accounts that existed as of 10 May have been migrated from the old results website.&nbsp; If you have an account and wish to test the login facility, you can do so after resetting your password using the 'Forgotten your password?' link.&nbsp; No actual functionality for logged-in users is implemented yet.</p>
	
	<!--<p>If you have previously reset your password but it doesn't work now, I may have refreshed the database.&nbsp; If this is the case, please just reset your password again.</p>-->
	
	<p>All registered users will be notified by email when the site is launched.</p>
</div>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<p><label for="email">Email address:</label> <input type="text" name="email" id="email" /></p>
	<p><label for="pwd">Password:</label> <input type="password" name="pwd" id="pwd" /></p>

	<p><input type="submit" value="Log In" /></p>
</form>

<p><a href="rpwd.php">Forgotten your password?</a></p>

<?php
pageFooter();
?>