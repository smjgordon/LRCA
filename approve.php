<?php
// TODO: separate out common view code into V class
// TODO: update once fixture/match are refactored
require_once 'private_php/p_global.php';
require_once 'private_php/p_match.php';
requireLogin(['can_submit']);

$fixtureId = @$_REQUEST['fid'];
if (!is_numeric($fixtureId)) errorPage(404);
$fixtureId = (int) $fixtureId;

try {
	$fixture = Fixture::loadById($fixtureId);
} catch (Exception $ex) {
	errorPage(404);
}

$fixtures = $CurrentUser->club()->fixturesPendingApproval();
$loopFixture = null;
foreach ($fixtures as $loopFixture) {
	if ($loopFixture->id() == $fixtureId) break;
}
// TODO: distinguish failure scenarios:
// - not the user's club
// - already approved
// - not submitted yet
// - other status - postponed, defaulted, scored bye
if (!$loopFixture || $fixture->id() != $loopFixture->id()) errorPage(403);

$match = Match::load($fixtureId);
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (@$_POST['confirm'] == 'on') {

		try {
			$Database->beginTransaction();
			$match->saveApproval();
			$Database->commit();

			redirect(HttpStatus::RedirectSeeOther, 'approved');

		} catch (ReportableException $ex) {
			$Database->rollBack();
			$error = $ex->getMessage();

		} catch (Exception $ex) {
			$Database->rollBack();
			//$error = $ex->getMessage(); // debug
			errorPage(500);
		}
	} else {
		$error = 'You must tick "I confirm that the result details are complete and correct" to continue';
	}
}

pageHeader('Approve Result');
?>

<h2>Approve Result</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form method="post" action="approve">
	<p>Please confirm that these details are correct.&nbsp; If there are any errors or omissions, please do not proceed, but contact the <a href="mailto:smjg@iname.com">Webmaster</a> with the corrections.</p>
	<?php $match->renderResult(); ?>

	<p><label><input type="checkbox" name="confirm" /> I confirm that the result details are complete and correct.</p>

	<p>
		<input type="hidden" name="fid" value="<?php echo $fixtureId; ?>" />
		<input type="submit" value="Approve" />
	</p>
</form>

<?php
pageFooter();
?>