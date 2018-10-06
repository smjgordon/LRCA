Email alert task started at <?php echo gmdate('c'); ?>.

<?php
// TODO: split out email functionality in common with p_email.php
// TODO: MVC-separate
require_once 'p_global.php';
require_once 'm_club.php';
require_once 'p_html_functions.php';
require_once 'u_text.php';

$allClubs = Club::loadAll();

// RESULT SUBMISSION REMINDER

$thresholdDate = time() - SystemSettings::$submitResultReminderDays * 86400;
$thresholdDateStr = date('c', $thresholdDate);

$stmt = $Database->prepare('
	SELECT d.name, f.fixture_date, ht.name AS home, at.name AS away
	FROM fixture f
		JOIN team ht on f.home_team_id = ht.team_id
		JOIN team at on f.away_team_id = at.team_id
		JOIN round r ON f.round_id = r.round_id
		JOIN division d ON r.division_id = d.division_id
	WHERE f.fixture_date <= ?
		AND ? IN (ht.club_id, at.club_id)
		AND (f.status = 0 OR (f.status IN (1, 3) AND f.approved_date IS NULL AND f.approval_club_id = ?))
	ORDER BY f.fixture_date, ht.name, at.name');

foreach ($allClubs as $club) {

	$stmt->execute([$thresholdDateStr, $club->id(), $club->id()]);

	if (!!($row = $stmt->fetch())) {
		$matchList = '';
		$nMatches = 0;

		do {
			$matchList .= formatDate(strtotime($row['fixture_date']), false) . " - $row[name] - $row[home] v $row[away]\r\n";
			++$nMatches;
		} while (!!($row = $stmt->fetch()));

		$toSendTo = User::loadByClub($club, 'can_submit');
		$clubName = $club->name();

		sendEmail('Reminder: Fixtures in your club pending result submission or approval',
			$nMatches == 1 ?
"Hello,

The following $clubName fixture is still awaiting result submission or
approval:

$matchList
Please note that this is a generic email to your club.  If this fixture
does not appear in My Fixtures when you log in, it is because somebody has
taken care of it since this email was sent.

Please be aware that any fixtures that are not submitted and approved
within 7 days of the fixture date are liable to incur penalties."
			:
"Hello,

The following $clubName fixtures are still awaiting result submission
or approval:

$matchList
Please note that this is a generic email to your club.  If these fixtures
do not appear in My Fixtures when you log in, it is because somebody has
taken care of them since this email was sent.

Please be aware that any fixtures that are not submitted and approved
by 11pm on the Monday after the match are liable to incur penalties.",
			$toSendTo);
	}
}

// FIXTURE DATE REGISTRATION REMINDER

$thresholdDate = time() + SystemSettings::$registerDateReminderDays * 86400;
$thresholdDateStr = date('c', $thresholdDate);

$stmt = $Database->prepare('
	SELECT d.name, ht.name AS home, at.name AS away, r.deadline
	FROM fixture f
		JOIN team ht on f.home_team_id = ht.team_id
		JOIN team at on f.away_team_id = at.team_id
		JOIN round r ON f.round_id = r.round_id
		JOIN division d ON r.division_id = d.division_id
	WHERE deadline <= ?
		AND ? IN (ht.club_id, at.club_id)
		AND ((f.fixture_date IS NULL AND f.status = 0) OR f.status = 2)
		AND f.home_team_id IS NOT NULL
		AND f.away_team_id IS NOT NULL
	ORDER BY ht.name, at.name');

foreach ($allClubs as $club) {

	$stmt->execute([$thresholdDateStr, $club->id()]);

	if (!!($row = $stmt->fetch())) {
		$matchList = '';
		$nMatches = 0;

		do {
			$matchList .= "$row[name] - $row[home] v $row[away] (to be played by "
				. formatDate(strtotime($row['deadline']), false) . ")\r\n";
			++$nMatches;
		} while (!!($row = $stmt->fetch()));

		$toSendTo = User::loadByClub($club, 'can_submit');
		$clubName = $club->name();

		sendEmail('Reminder: Fixtures in your club pending dates',
			$nMatches == 1 ?
"Hello,

The following $clubName fixture is still waiting for a date to be
registered:

$matchList
Please remember to agree a date with your opponent for this match, and
register this date on the website.

Please note that this is a generic email to your club.  If this fixture
does not appear in My Fixtures when you log in, it is because somebody has
taken care of it since this email was sent."
			:
"Hello,

The following $clubName fixtures are still waiting for dates to be
registered:

$matchList
Please remember to agree dates with your opponent for these matches, and
register the dates on the website.

Please note that this is a generic email to your club.  If these fixtures
do not appear in My Fixtures when you log in, it is because somebody has
taken care of them since this email was sent.",
			$toSendTo);
	}
}

function sendEmail($subject, $message, $toUsers) {
	global $CanSendEmail;

	foreach ($toUsers as $user) {
		$toStrings[] = '"' . $user->fullName() . '" <' . $user->email() . '>';
	}
	$toHeader = implode(', ', $toStrings);

	if ($CanSendEmail) {
		mail($toHeader, $subject, $message, 'From: ' . SystemSettings::$fromEmail . '
Reply-To: ' . SystemSettings::$replyEmail . '
CC: ' . SystemSettings::$ccEmail) . '
Content-type: text/plain; charset=utf-8';

	}
?>
From: <?php echo SystemSettings::$fromEmail; ?>

To: <?php echo $toHeader; ?>

Subject: <?php echo $subject; ?>

Reply-To: <?php echo SystemSettings::$replyEmail; ?>


<?php echo $message; ?>


<?php
}
?>
Email alert task completed at <?php echo gmdate('c'); ?>.
