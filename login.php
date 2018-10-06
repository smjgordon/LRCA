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
		$Database->beginTransaction();
		try {
			$user = User::loadByEmail($email);
			$user->logIn($password);
			$Database->commit();

			// if the user was redirected here from another page, redirect back to that page on successful login
			// otherwise go to My Fixtures, as this is what the user is most likely to want to use after logging in
			$redirectTo = @$_REQUEST['uri'] or $redirectTo = 'my_fixtures/';
			redirect(HttpStatus::RedirectSeeOther, $redirectTo);

		} catch (UserAccountException $ex) {
			$Database->rollBack();
			$error = $ex->getMessage();

		} catch (ReportableException $ex) {
			$Database->rollBack();
			$error = $ex->getMessage();

		} catch (Exception $ex) {
			$Database->rollBack();
			errorPage(500);
		}
	}
}

// if already logged in, redirect
if ($CurrentUser) redirect(303, 'my_fixtures/');

pageHeader('Log In');
?>

<h2>Log In</h2>
<p>Team captains can log in here in order to submit and approve results.</p>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" method="post" action="login">
	<p><label for="email">Email address:</label> <input type="text" name="email" id="email" /></p>
	<p><label for="pwd">Password:</label> <input type="password" name="pwd" id="pwd" /></p>

	<p>
		<?php if (isset($_REQUEST['uri'])) { ?>
			<input type="hidden" name="uri" value="<?php echo htmlspecialchars($_REQUEST['uri']); ?>" />
		<?php } ?>
		<input type="submit" value="Log In" />
	</p>
</form>

<p><a href="rpwd/">Forgotten your password?</a></p>

<?php
pageFooter();
?>