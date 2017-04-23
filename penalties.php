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

<div id="subNav">
	<?php $division->section->divisionIndex(); // TODO: figure out what to do with this ?>
	<ul><li><a href='penalties.php?did=<?php echo $divisionId; ?>'>Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>
	<h3 class="sub">Penalties</h3>

	<?php $divisionView->showStandings(); ?>
	<h3>Board Defaults Incurred</h3>
	<?php $divisionView->showBoardDefaults(); ?>
	<h3>Board Default Totals</h3>
	<?php $divisionView->showDefaultTotals(); ?>
</div>

<?php
pageFooter();
?>