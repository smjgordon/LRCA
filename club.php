<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_club.php';
require_once 'private_php/p_html_functions.php';
require_once 'private_php/c_captcha.php';

$clubId = @$_GET['cid'];
if (!is_numeric($clubId)) errorPage(404);
$clubId = (int) $clubId;

try {
	$club = Club::loadById($clubId);
	$clubView = new HtmlClubView($club);
} catch (Exception $ex) {
	errorPage(404);
}

pageHeader($club->name() . ' – Club Profile');
?>

<div id="subNav">
	<?php echo clubNavBar(); ?>
</div>

<div id="subBody">
	<h2><?php
		if ($club->websiteUrl()) {
			echo '<a href="', htmlspecialchars($club->websiteUrl()), '">',
				htmlspecialchars($club->longName()), '</a>';
		} else {
			echo htmlspecialchars($club->longName());
		}

		echo $clubView->mapLink();
	?></h2>
	<div id="clubVenue">
		<p><b><?php echo htmlspecialchars($club->venueName()); ?></b><br />
		<?php echo encodeMultiLine($club->venueAddress()); ?><br />
		<?php echo htmlspecialchars($club->venuePostcode()); ?></p>
	</div>

	<table id="clubInfo">
		<tr>
			<td>Club night:</td>
			<td><?php echo $club->meetingDay(); ?></td>
		</tr>
		<tr>
			<td>Start time:</td>
			<td><?php
				echo $club->meetingTime();
				if ($club->meetingEndTime()) {
					echo ' sharp<br />(must finish by ', $club->meetingEndTime(), ')';
				}
			?></td>
		</tr>
		<tr>
			<td>Session length:</td>
			<td><?php
				switch ($club->sessionLength()) {
					case SessionLength::TwoHours40:
						echo '2 hours 40 minutes';
						break;
					case SessionLength::ThreeHours:
						echo '3 hours';
						break;
					case SessionLength::Negotiable:
						echo 'To suit visiting team';
				}
			?></td>
		</tr>
		<tr>
			<td>Digital clocks:</td>
			<td><?php
				switch ($club->digitalClocks()) {
					case DigitalClocks::No:
						echo 'No';
						break;
					case DigitalClocks::Limited:
						echo 'Limited';
						break;
					case DigitalClocks::Yes:
						echo 'Yes';
				}
			?></td>
		</tr>
	</table>
	
	<p><?php echo encodeMultiLine($club->venueInfo()); ?></p>

<?php
	$contacts = Contact::loadByClub($club);
	$lastContactType = 0;
	$showContactInfo = ($CurrentUser != null || haveValidCaptcha());

	foreach ($contacts as $contact) {
		switch ($contact->type()) {
			case ContactType::Secretary:
				if ($lastContactType == 0) echo '<table class="contacts">';
			?>
				<tr>
					<td>Secretary</td>
					<?php echo showContact($contact, $showContactInfo); ?>
				</tr>
			<?php
				break;

			case ContactType::EmailContact:
				if ($lastContactType == 0) echo '<table class="contacts">';
			?>
				<tr>
					<td>Email Contact</td>
					<?php echo showContact($contact, $showContactInfo); ?>
				</tr>
			<?php
				break;

			case ContactType::TeamCaptain:
				if ($lastContactType != ContactType::TeamCaptain) {
					if ($lastContactType == ContactType::Secretary) echo '</table>';
					echo '<h3>Teams and Captains</h3>';
					echo '<table class="contacts">';
				}
			?>
				<tr>
					<td><?php echo $contact->divisionName(); ?></td>
					<td><?php echo $contact->teamName(); ?></td>
					<?php echo showContact($contact, $showContactInfo); ?>
				</tr>
			<?php
		}
		$lastContactType = $contact->type();
	}
	if ($lastContactType != 0) echo '</table>';
?>

<?php if (!$showContactInfo) { ?>
	<p><a href="captcha.php">Show Contact Information</a></p>
<?php } ?>

</div>

<?php
pageFooter();
?>