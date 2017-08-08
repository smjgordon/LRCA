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
	
<?php
	if ($division->section->year < ($division->section->season == Season::Winter ? 2016 : 2017)) {
	?>
		<p class="devNotice">Penalties prior to the 2016–17 League season are not fully entered into the database.&nbsp; As such, this information may be inaccurate.</p>
	<?php
	}

	$divisionView->showStandings();
	if ($divisionView->showBoardDefaults()) {
		$divisionView->showDefaultTotals();
	} else {
	?>
		<p>No penalties have been incurred in this division.</p>
	<?php
	}
?>
</div>

<?php
pageFooter();
?>
