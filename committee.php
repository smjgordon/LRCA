<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_committee.php';
require_once 'private_php/v_html_contact.php';
require_once 'private_php/c_captcha.php';

try {
	$committee = Committee::loadByUri($_SERVER['REQUEST_URI']);
	//$committeeView = new HtmlCommitteeView($committee);
} catch (Exception $ex) {
	errorPage(HttpStatus::NotFound);
}

//pageHeader($club->name() . ' – Club Profile');
pageHeader($committee->longName());
?>

<div id="subNav">
	<ul>
		<li><a href="../">Home</a></li
		><li><a href="../about">About Us</a></li
		><li><a href="principal">Committees</a></li
		><li><a href="https://www.leicestershirejuniorchess.com/">Juniors</a></li
		><li><a href="../links">Links</a></li
		><li><a href="https://www.eventbrite.co.uk/e/leicester-he-atkins-memorial-rapidplay-sunday-7th-october-2018-tickets-46358564715">Atkins Rapidplay</a></li>
	</ul>
	<?php echo committeeNavBar(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($committee->longName()); ?></h2>

<?php
	$contacts = Contact::loadByCommittee($committee);
	$showContactInfo = ($CurrentUser != null || haveValidCaptcha());

	if (!empty($contacts)) {
	?>	<table class="contacts">
			<?php foreach ($contacts as $contact) { ?>
				<tr>
					<td><?php
						echo htmlspecialchars($contact->type());
						if ($contact->exOfficio()) echo ' <i>(ex officio)</i>';
					?></td>
					<?php if ($contact->id() > 0) { ?>
						<?php echo showContact($contact, $showContactInfo); ?>
					<?php } else { ?>
						<td <?php if ($showContactInfo) echo 'colspan="3"'; ?>><i>Vacant</i></td>
					<?php } ?>
					</tr>
			<?php } ?>
		</table>
	<?php
	}
?>
	<?php if (!$showContactInfo) { ?>
		<p><a href="../captcha">Show Contact Information</a></p>
	<?php } ?>

</div>

<?php
pageFooter();
?>