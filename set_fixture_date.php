<?php
// TODO: separate out common view code into V class
// TODO: update once fixture/match are refactored
require_once 'private_php/p_global.php';
require_once 'private_php/u_text.php';
require_once 'private_php/p_email.php';
requireLogin(['can_submit']);

$fixtureId = @$_REQUEST['fid'];
if (!is_numeric($fixtureId)) errorPage(HttpStatus::NotFound);
$fixtureId = (int) $fixtureId;

try {
	$fixture = Fixture::loadById($fixtureId);
} catch (Exception $ex) {
	errorPage(HttpStatus::NotFound);
}

$dummy1 = $dummy2 = false;
$fixtures = $CurrentUser->club()->fixturesPendingDates($dummy1, $dummy2);
$loopFixture = null;
foreach ($fixtures as $loopFixture) {
	if ($loopFixture->id() == $fixtureId) break;
}
// TODO: distinguish failure scenarios:
// - not the user's club
// - already has a date and not postponed
// - other status - played, defaulted, scored bye, unscored bye
if (!$loopFixture || $fixture->id() != $loopFixture->id()) errorPage(HttpStatus::Forbidden);

//$match = Match::load($fixtureId);
$error = $newDate = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	try {
		$newDateStr = @$_POST['date'];
		$newDate = parseDate($newDateStr);

		if (@$_POST['confirm'] == 'yes') {
			$fixture->status = MatchStatus::Unplayed;
			$fixture->date = $newDate;
			
			$Database->beginTransaction();
			try {
				$fixture->save();
				$Database->commit();

			} catch (Exception $ex) {
				$Database->rollBack();
				throw $ex;
			}

			// generate email confirmation
			$emailSubject = "Fixture date: " . $fixture->division->name . ' - '
				. $fixture->homeTeam->name . ' v ' . $fixture->awayTeam->name;

			$emailMessage = 'Hello,

A new date has been set for this fixture.

It will be played on ' . formatDate($newDate, true) . '.';

			$recipients = array_merge(User::loadByClub($fixture->homeTeam->club), User::loadByClub($fixture->awayTeam->club));

			emailConfirmation($emailSubject, $emailMessage, $recipients, 'fixture_date_set.php');
		}

	} catch (ReportableException $ex) {
		$error = $ex->getMessage();
		$newDate = null;

	} catch (Exception $ex) {
		//$error = $ex->getMessage(); // debug
		errorPage(500);
	}
}

pageHeader('Set Fixture Date');
?>

<h2>Set Fixture Date</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<?php if ($newDate) { ?>
		<p>Please confirm the date you have entered is correct.&nbsp; If you have made a mistake, please use your browser's Back button to return to the date entry form.&nbsp; If you are happy that the date is correct, please press Set Date again to register the date.</p>

		<table class="fixtures">
			<tr>
				<td class="date"><?php echo formatDate($newDate); ?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name) : 'bye'; ?></td>
				<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name) : 'bye'; ?></td>
			</tr>
		</table>

		<p>
			<input type="hidden" name="fid" value="<?php echo $fixtureId; ?>" />
			<input type="hidden" name="date" value="<?php echo date('Y-m-d', $newDate); ?>" />
			<input type="hidden" name="confirm" value="yes" />
			<input type="submit" value="Set Date" />
		</p>

	<?php } else { ?>
		<p>You are setting a new date for this fixture:</p>

		<table class="fixtures">
			<tr>
				<td class="date"><?php echo formatDate($fixture->date); ?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name) : 'bye'; ?></td>
				<?php
					switch ($fixture->status) {
						case MatchStatus::Unplayed: ?>
							<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>
						<?php
							break;
						
						case MatchStatus::Postponed: ?>
							<td class="homeScore">P</td><td class="dash">–</td><td class="awayScore">P</td>
						<?php
							break;

						default:
							errorPage(HttpStatus::InternalError);
					}
				?>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name) : 'bye'; ?></td>
			</tr>
		</table>

		<p><label for="date">New date:</label> <input type="text" name="date" id="date" /> (please enter as dd Mmm yyyy e.g. 09 Oct 2016)</p>

		<p>
			<input type="hidden" name="fid" value="<?php echo $fixtureId; ?>" />
			<input type="submit" value="Set Date" />
		</p>
	<?php } ?>
</form>

<?php
pageFooter();
?>