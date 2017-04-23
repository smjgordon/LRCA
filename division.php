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

pageHeader($divisionView->headerTitle());
?>

<div id="subNav">
	<?php $division->section->divisionIndex(); // TODO: figure out what to do with this ?>
	<ul><li><a href='penalties.php?did=<?php echo $divisionId; ?>'>Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>

	<?php if ($division->breakdown == Breakdown::ByMonth) { ?>
		<p class="devNotice">Board defaults and team withdrawals over the course of the 2016–17 season have led to some penalties being applied.&nbsp; Points have now been deducted to reflect this, but automating the process is still a work in progress.&nbsp; As such, the values may at times be out of sync.</p>
	<?php } ?>
<?php
	$divisionView->showStandings();
	$divisionView->showFixtures();
?>
</div>

<?php
pageFooter();
?>
