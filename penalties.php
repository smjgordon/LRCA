<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/u_text.php';

if (substr($_SERVER['REQUEST_URI'], -10) != '/penalties') errorPage(HttpStatus::InternalError);

try {
	$division = Division::loadByUri(substr($_SERVER['REQUEST_URI'], 0, -10));
	$divisionView = new HtmlDivisionView($division);
} catch (Exception $ex) {
	errorPage(404);
}

pageHeader("Penalties – " . $divisionView->headerTitle());
?>

<div id="subNav">
<?php
	$section = $division->section();
	$sectionView = new HtmlSectionView($section);
	$sectionView->showDivisionIndex();
?>
	<ul><li><a href="penalties">Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>
	<h3 class="sub">Penalties</h3>
	
<?php
	if ($section->year() < ($section->season() == Season::Winter ? 2016 : 2017)) {
	?>
		<p class="devNotice">Penalties prior to the 2016–17 League season are not fully entered into the database.&nbsp; As such, this information may be inaccurate.</p>
	<?php
	}
	$anyPenalties = false;

	$divisionView->showStandings();
	if ($divisionView->showBoardDefaults()) {
		$divisionView->showDefaultTotals();
		$anyPenalties = true;
	}
	
	$anyPenalties |= $divisionView->showMatchPenalties();

	if (!$anyPenalties) {
	?>
		<p>No penalties have been incurred in this division.</p>
	<?php
	}
?>
</div>

<?php
pageFooter();
?>
