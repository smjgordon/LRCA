<?php
require_once 'private_php/p_global.php';
require_once 'private_php/c_create_user.php';
require_once 'private_php/p_html_functions.php';

$error = null;
requireLogin(['can_create_users']);

if (isset($_POST['action'])) {
	$forename = trim($_POST['forename']);
	$surname = trim($_POST['surname']);
	$email1 = trim($_POST['email1']);
	$email2 = trim($_POST['email2']);
	$clubId = $_POST['cid'];
	
	if (!$forename) {
		$error = 'Missing forename';
	} else if (!$surname) {
		$error = 'Missing surname';
	} else if (!$email1) {
		$error = 'Missing email address';
	} else if (!filter_var($email1, FILTER_VALIDATE_EMAIL)) {
		$error = 'Invalid email address';
	} else if ($email1 != $email2) {
		$error = 'Email addresses do not match';
	} else if (!$clubId) {
		$error = 'Missing club';
	} else {
		try {
			createUser($forename, $surname, $email1, $clubId);
		} catch (Exception $ex) {
			$error = $ex->getMessage();
		}
	}
} else {
	$forename = $surname = $email1 = $email2 = '';
}

pageHeader('Create User');
?>

<h2>Create User</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" enctype="multipart/form-data" method="post"
		action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<p><label for="forename">Forename:</label> <input type="text" name="forename" id="forename" value="<?php echo $forename; ?>" /></p>
	<p><label for="surname">Surname:</label> <input type="text" name="surname" id="surname" value="<?php echo $surname; ?>" /></p>
	<p><label for="email1">Email address:</label> <input type="text" name="email1" id="email1" value="<?php echo $email1; ?>" /></p>
	<p><label for="email2">Re-enter email address:</label> <input type="text" name="email2" id="email2"  value="<?php echo $email2; ?>"/></p>
	<p>
		<label for="cid">Club:</label>
		<input type="hidden" name="cid" value="<?php echo $CurrentUser->club()->id(); ?>" />
		<?php echo htmlspecialchars($CurrentUser->club()->name); ?>
	</p>

	<p><input type="submit" name="action" value="Create" /></p>
</form>

<?php
pageFooter();
?>