<?php
// TODO: refactor
require_once 'private_php/p_global.php';
requireLogin();

$error = null;

$userId = @$_POST['uid'];
if ($userId) {
	if ($userId != $CurrentUser->id()) errorPage(403);

	$oldPassword = @$_POST['pwd0'];
	$password1 = @$_POST['pwd1'];
	$password2 = @$_POST['pwd2'];

	if (!$CurrentUser->checkPassword($oldPassword)) {
		$error = 'You did not enter your current password correctly.  Please try again.';
	} else if ($password1 != $password2) {
		$error = 'Passwords do not match!';
	} else if (trim($password1) == '') {
		$error = 'Please enter a password!';
	} else {
		$Database->beginTransaction();
		$CurrentUser->setNewPassword($password1);
		$Database->commit();
		redirect(303, 'password_set');
	}
}

pageHeader('Change Password');
?>

<h2>Change Password</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" method="post" action="change_password">
	<p>
		<input type="hidden" name="uid" value="<?php echo $CurrentUser->id(); ?>" />
		<label for="pwd0">Current password:</label> <input type="password" name="pwd0" id="pwd0" />
	</p>
	<p><label for="pwd1">New password:</label> <input type="password" name="pwd1" id="pwd1" /></p>
	<p><label for="pwd2">Re-enter new password:</label> <input type="password" name="pwd2" id="pwd2" /></p>

	<p><input type="submit" value="Set Password" /></p>
</form>

<?php
pageFooter();
?>