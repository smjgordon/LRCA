<?php
require_once 'private_php/p_global.php';
require_once 'private_php/c_create_user.php';
require_once 'private_php/p_html_functions.php';

$error = null;
requireLogin(['administrator']);

if (isset($_POST['action'])) {
	$forename = trim($_POST['forename']);
	$surname = trim($_POST['surname']);
	$email = trim($_POST['email']);
	$clubId = $_POST['cid'];

	if (!$forename) {
		$error = 'Missing forename';
	} else if (!$surname) {
		$error = 'Missing surname';
	} else if (!$email) {
		$error = 'Missing email address';
	} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = 'Invalid email address';
	} else if (!$clubId) {
		$error = 'Missing club';
	} else {
		try {
			createUser($forename, $surname, $email, $clubId);
		} catch (Exception $ex) {
			$error = $ex->getMessage();
		}
	}
}

pageHeader('Create User');
?>

<h2>Create User</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" enctype="multipart/form-data" method="post"
		action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<p><label for="forename">Forename:</label> <input type="text" name="forename" id="forename" /></p>
	<p><label for="surname">Surname:</label> <input type="text" name="surname" id="surname" /></p>
	<p><label for="email">Email address:</label> <input type="text" name="email" id="email" /></p>
	<p>
		<label for="cid">Club:</label>
		<select name="cid" id="cid"><?php
			renderSelectOption('', $clubID, '');
			$stmt = $Database->query('SELECT club_id, name FROM club WHERE status = 1 ORDER BY name');
			while ($row = $stmt->fetch()) {
				renderSelectOption($row['club_id'], $clubID, $row['name']);
			}
		?></select>
	</p>

	<p><input type="submit" name="action" value="Create" /></p>
</form>

<?php
pageFooter();
?>