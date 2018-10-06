<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/v_html_section.php';
require_once 'private_php/u_text.php';

try {
	$division = Division::loadByUri($_SERVER['REQUEST_URI']);
	$divisionView = new HtmlDivisionView($division);
} catch (Exception $ex) {
	errorPage(404);
}

pageHeader($divisionView->headerTitle());
?>

<div id="subNav">
<?php
	$sectionView = new HtmlSectionView($division->section());
	$sectionView->showDivisionIndex();
?>
	<ul><li><a href="penalties">Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>

	<?php /*if ($division->breakdown == Breakdown::ByMonth) { ?>
		<p class="devNotice">Board defaults and team withdrawals over the course of the 2016–17 season have led to some penalties being applied.&nbsp; Points have now been deducted to reflect this, but automating the process is still a work in progress.&nbsp; As such, the values may at times be out of sync.</p>
	<?php ?>
		<p class="devNotice">All League matches have now been played, but the final rankings still need to be updated to take into account tie-breaking rules.&nbsp; This will be done over the next few days.</p>
	<?php }*/ ?>
<?php
	$divisionView->showStandings();
	$divisionView->showFixtures();
?>
</div>

<?php
pageFooter();
?>
