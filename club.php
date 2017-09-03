<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_club.php';
require_once 'private_php/p_html_functions.php';

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
		/*$mapUrl = $club->mapUrl();
		if ($mapUrl) {
			echo ' <a href="', htmlspecialchars($mapUrl), '">(Map)</a>';
		}*/
	?></h2>
	<p><b><?php echo htmlspecialchars($club->venueName()); ?></b><br />
	<?php echo encodeMultiLine($club->venueAddress()); ?><br />
	<?php echo htmlspecialchars($club->venuePostcode()); ?></p>

	<p><?php echo encodeMultiLine($club->venueInfo()); ?></p>

	<p>
		Club Night: <?php echo $club->meetingDay(); ?>, <?php echo $club->meetingTime(); ?> start
		<?php if ($club->meetingEndTime()) { ?>
			essential to ensure finish by <?php echo $club->meetingEndTime(); ?>
		<?php } ?>
	</p>

<?php
	$contacts = Contact::loadByClub($club);
	$lastContactType = 0;
	$showContactInfo = ($CurrentUser != null);

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
	<p>Note: As an anti-spam measure, contact information is shown only to logged-in users.</p>
<?php } ?>
</div>

<?php
pageFooter();
?>