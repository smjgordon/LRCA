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

<div id="subNav"><?php
	$division->section->divisionIndex(); // TODO: figure out what to do with this
	echo $divisionView->breakdown();
?></div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>

<?php
	$divisionView->showStandings();
	$divisionView->showFixtures();
?>
</div>

<?php
pageFooter();
?>
