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

			// redirect to My Fixtures, as this is what the user is most likely to want to use after logging in
			// TODO: if the user was redirected here from another page, return to that page
			redirect(303, 'my_fixtures.php');
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
	<p>Everybody who had a user account under the old results website, and every team captain listed in the 2016-17 LRCA Handbook, should have a user account here.&nbsp; In the near future, a facility will be implemented to enable registered users to create user accounts for members of their clubs to enable them to submit results.&nbsp; Meanwhile, if you do not have an account and need to submit results for your club, please contact the <a href="mailto:smjg@iname.com">Results Webmaster</a>.</p>
</div>

<p>Team captains can log in here in order to submit and approve results.</p>

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