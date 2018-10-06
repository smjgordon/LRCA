<?php
// TODO: separate out common view code into V class
require_once 'private_php/p_global.php';
require_once 'private_php/p_html_functions.php';
require_once 'private_php/u_text.php';
requireLogin(['can_submit']);

pageHeader('My Fixtures - Leicestershire and Rutland Chess Association');
?>

<h2>My Fixtures</h2>

<?php
$anyFixtures = false;

$fixtures = $CurrentUser->club()->fixturesPendingSubmission();
if ($fixtures) {
	$anyFixtures = true;
?>
	<h3>Available for Submission</h3>
	<table class="fixtures">
		<?php foreach ($fixtures as $fixture) { ?>
			<tr>
				<td class="division"><?php echo $fixture->division->name(); ?></td>
				<td class="date"><?php
					echo formatDate($fixture->date);
				?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name()) : 'bye'; ?></td>
				<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name()) : 'bye'; ?></td>
				<td><a href="submit?fid=<?php echo $fixture->id(); ?>">Submit Result</a></td>
			</tr>
		<?php } ?>
	</table>
<?php
}

$fixtures = $CurrentUser->club()->fixturesPendingApproval();
if ($fixtures) {
	$anyFixtures = true;
?>
	<h3>Available for Approval</h3>
	<table class="fixtures">
		<?php foreach ($fixtures as $fixture) { ?>
			<tr>
				<td class="division"><?php echo $fixture->division->name(); ?></td>
				<td class="date"><?php echo formatDate($fixture->date); ?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name()) : 'bye'; ?></td>
				<td class="homeScore"><?php echo formatScore($fixture->homeAdjustedScore); ?></td>
				<td class="dash">–</td>
				<td class="awayScore"><?php echo formatScore($fixture->awayAdjustedScore); ?></td>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name()) : 'bye'; ?></td>
				<td><a href="approve?fid=<?php echo $fixture->id(); ?>">Approve</a></td>
			</tr>
		<?php } ?>
	</table>
<?php
}

$anyPostponed = $anyUnscheduled = false;
$fixtures = $CurrentUser->club()->fixturesPendingDates($anyPostponed, $anyUnscheduled);
if ($fixtures) {
	$anyFixtures = true;
?>

	<?php if ($anyPostponed && $anyUnscheduled) { ?>
		<h3>Postponed or Not Yet Scheduled</h3>
	<?php } else if ($anyPostponed) { ?>
		<h3>Postponed</h3>
	<?php } else if ($anyUnscheduled) { ?>
		<h3>Not Yet Scheduled</h3>
	<?php } ?>
	<table class="fixtures">
		<?php foreach ($fixtures as $fixture) { ?>
			<tr>
				<td class="division"><?php echo $fixture->division->name(); ?></td>
				<td class="date"><?php
					echo formatDate($fixture->date);
				?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name()) : 'bye'; ?></td>
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
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name()) : 'bye'; ?></td>
				<td><a href="set_date?fid=<?php echo $fixture->id(); ?>">Set Date</a></td>
			</tr>
		<?php } ?>
	</table>
<?php
}

$fixtures = $CurrentUser->club()->futureFixtures();
if ($fixtures) {
	$fixturesSoFar = 0;
	$anyFixtures = true;
	$lastFixtureDate = null;
?>
	<h3>Upcoming Fixtures</h3>
	<table class="fixtures"><?php
		foreach ($fixtures as $fixture) {
			++$fixturesSoFar;
			if ($fixture->date != $lastFixtureDate && $fixturesSoFar > SystemSettings::$upcomingFixturesToShow) break;
		?>	<tr>
				<td class="division"><?php echo $fixture->division->name(); ?></td>
				<td class="date"><?php
					echo formatDate($fixture->date);
				?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name()) : 'bye'; ?></td>
				<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name()) : 'bye'; ?></td>
			</tr>
		<?php
			$lastFixtureDate = $fixture->date;
		}
	?></table>
<?php
}

if (!$anyFixtures) {
?>
	<p>You do not currently have any fixtures available for submission or approval.</p>
<?php
}

pageFooter();
?>