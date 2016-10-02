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

$fixtures = $CurrentUser->club()->fixturesPendingSubmission();
$loopFixture = null;
foreach ($fixtures as $loopFixture) {
	if ($loopFixture->id() == $fixtureId) break;
}
// TODO: distinguish failure scenarios:
// - not the user's club
// - approved
// - already submitted by same club
// - already submitted by other club and not approved - redirect to approval
// - other status - postponed, defaulted, scored bye
// - no date set for match
if (!$loopFixture || $fixture->id() != $loopFixture->id()) errorPage(403);

$match = Match::load($fixtureId);
$submissionBuilt = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		if (@$_POST['confirm'] == 'yes') {
			
			$Database->beginTransaction();
			try {
				$match->buildSubmission();
				$match->saveSubmission();
				$Database->commit();

			} catch (Exception $ex) {
				$Database->rollBack();
				throw $ex;
			}
			
			$match->generateEmailConfirmation();

		} else {
			$match->buildSubmission();
			$submissionBuilt = true;
		}

	} catch (ReportableException $ex) {
		$error = $ex->getMessage();
		$submissionBuilt = false;

	} catch (Exception $ex) {
		//$error = $ex->getMessage(); // debug
		errorPage(500);
	}
}

pageHeader('Submit Result');
?>

<h2>Submit Result</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<?php if ($submissionBuilt) { ?>
		<p>Please confirm that these details are correct.&nbsp; If you have made a mistake, please use your browser's Back button to return to the submission form.&nbsp; If you are happy that the result is complete and correct, please press Submit again to complete the submission.</p>
		<?php $match->renderResult(); ?>
		<p>
			<?php carryForwardPostData(); ?>
			<input type="hidden" name="confirm" value="yes" />
			<input type="submit" value="Submit" />
		</p>
	<?php } else { ?>
		<p>Please enter the details of the match.</p>
	<?php
		$division = Division::loadById($fixture->round->division->id());
		/*if ($division->minBoards < $division->maxBoards) {
		?>	<p>If you used fewer than <?php echo $division->maxBoards; ?> boards, please fill in as many boards as you used, starting at the top, and leave the remainder blank.</p>
		<?php
		}*/

		$match->renderSubmissionForm();
	?>
		<p>
			<input type="hidden" name="fid" value="<?php echo $fixtureId; ?>" />
			<input type="submit" value="Submit" />
		</p>
	<?php } ?>
</form>

<?php
pageFooter();
?>