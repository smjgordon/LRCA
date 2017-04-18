<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/u_text.php';

$divisionId = @$_GET['did'];
if (!is_numeric($divisionId)) errorPage(404);
$divisionId = (int) $divisionId;

try {
	$division = Division::loadById($divisionId);
	$divisionView = new HtmlDivisionView($division);
} catch (Exception $ex) {
	errorPage(404);
}

pageHeader("Penalties – " . $divisionView->headerTitle());
?>

<div id="subNav"><?php
	$division->section->divisionIndex(); // TODO: figure out what to do with this
	echo $divisionView->breakdown();
?></div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>

	<p class="devNotice">Board defaults and team withdrawals over the course of the 2016–17 season have led to some penalties being applied, but these are not currently reflected in the displayed numbers.&nbsp; This will be rectified soon.</p>
	<?php $divisionView->showStandings(); ?>
	<h3>Board Defaults Incurred</h3>
	<?php $divisionView->showBoardDefaults(); ?>
	<h3>Board Default Totals</h3>
	<?php $divisionView->showDefaultTotals(); ?>
</div>

<?php
pageFooter();
?>
