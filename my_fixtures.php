<?php
// TODO: separate out common view code into V class
require_once 'private_php/p_global.php';
require_once 'private_php/p_html_functions.php';
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
				<td class="date"><?php
					echo formatDate($fixture->date);
				?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name) : 'bye'; ?></td>
				<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name) : 'bye'; ?></td>
				<td><a href="submit.php?fid=<?php echo $fixture->id(); ?>">Submit Result</a></td>
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
				<td class="date"><?php
					echo formatDate($fixture->date);
				?></td>
				<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name) : 'bye'; ?></td>
				<td class="homeScore"><?php echo formatScore($fixture->homeAdjustedScore); ?></td>
				<td class="dash">–</td>
				<td class="awayScore"><?php echo formatScore($fixture->awayAdjustedScore); ?></td>
				<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name) : 'bye'; ?></td>
				<td><a href="approve.php?fid=<?php echo $fixture->id(); ?>">Approve</a></td>
			</tr>
		<?php } ?>
	</table>
<?php
}

if (!$anyFixtures) {
?>
	<p>You do not currently have any fixtures available for submission.</p>
<?php
}

pageFooter();
?>