<?php
// TODO: separate out common view code into V class
// TODO: update once fixture/match are refactored
require_once 'private_php/p_global.php';
require_once 'private_php/c_check_team.php';
requireLogin(['can_submit']);

$controller = new CheckTeamController();

$errors = null;
try {
	$errors = $controller->process();
} catch (UserInputException $ex) {
	$errors = [$ex->getMessage()];
} catch (ModelAccessException $ex) {
	errorPage(HttpStatus::NotFound);
} catch (Exception $ex) {
	errorPage(HttpStatus::InternalError);
}

pageHeader('Team Checker');
?>

<h2>Team Checker</h2>

<?php
if ($errors !== null) {
	if (count($errors) == 0) {
	?>	<p>The team you have selected is valid.</p>
	<?php
	} else {
		foreach ($errors as $error) {
		?>	<p class="error"><?php echo htmlspecialchars($error); ?></p>
		<?php
		}
	}
}
?>

<form method="post" action="check_team">
	<p>Please select the players you plan to play in this match.</p>
<?php
	$controller->match()->renderTeamCheckerForm();
?>
	<p>
		<input type="hidden" name="fid" value="<?php echo $controller->fixtureId(); ?>" />
		<input type="submit" value="Check" />
	</p>
</form>

<?php
pageFooter();
?>