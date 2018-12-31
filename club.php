<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_club.php';
require_once 'private_php/v_html_contact.php';
require_once 'private_php/v_html_section.php';
require_once 'private_php/p_html_functions.php';
require_once 'private_php/c_captcha.php';

try {
	$club = Club::loadByUri($_SERVER['REQUEST_URI']);
	$clubView = new HtmlClubView($club);
} catch (Exception $ex) {
	errorPage(HttpStatus::NotFound);
}

pageHeader($club->name() . ' – Club Profile');
?>

<div id="subNav">
	<?php echo clubNavBar(); ?>
	<ul>
		<li><a href="dtp">Declared Team Players</a></li>
	</ul>
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
	$contacts = Contact::loadNonTeamByClub($club);
	$showContactInfo = ($CurrentUser != null || haveValidCaptcha());

	if (!empty($contacts)) {
	?>	<table class="contacts">
			<?php foreach ($contacts as $contact) { ?>
				<tr>
					<td><?php
						switch ($contact->type()) {
							case ContactType::Secretary:
								echo 'Secretary';
								break;
							case ContactType::EmailContact:
								echo 'Email Contact';
						}
					?></td>
					<?php echo showContact($contact, $showContactInfo); ?>
				</tr>
			<?php } ?>
		</table>
	<?php
	}

	$sections = Section::loadAllInProgress();

	foreach ($sections as $section) {
		$sectionView = new HtmlSectionView($section);
		$teams = $club->teamsInSection($section);

		if (!empty($teams)) {
		?>
			<h3><?php echo htmlspecialchars($sectionView->displayName()); ?></h3>
			<table class="contacts"><?php
				foreach ($teams as $team) {
					$contacts = Contact::loadByTeam($team);
					if (empty($contacts)) {
					?>
						<tr>
							<td><?php echo $team->division()->name(); ?></td>
							<td><?php echo $team->name(); ?></td>
							<?php echo showNoContact($showContactInfo); ?>
						</tr>
					<?php
					} else {
						foreach ($contacts as $contact) {
						?>
							<tr>
								<td><?php echo $team->division()->name(); ?></td>
								<td><?php echo $team->name(); ?></td>
								<?php echo showContact($contact, $showContactInfo); ?>
							</tr>
						<?php
						}
					}
				}
			?>
			</table>
		<?php
		}
	}
?>

	<?php if (!$showContactInfo) { ?>
		<p><a href="../../captcha">Show Contact Information</a></p>
	<?php } ?>

</div>

<?php
pageFooter();
?>