<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_section.php';
require_once 'private_php/v_html_section.php';
require_once 'private_php/v_html_document.php';

$year = @$_GET['year'];
if (!is_numeric($year)) errorPage(404);
$year = (int) $year;

$sectionId = @$_GET['sid'];
if (!is_numeric($sectionId)) errorPage(404);
$sectionId = (int) $sectionId;

try {
	$section = Section::loadByYearAndId($year, $sectionId);
	$sectionView = new HtmlSectionView($section);
} catch (ModelAccessException $ex) {
	errorPage(404);
}

pageHeader($sectionView->displayName());
?>

<div id="subNav"><?php
	$anyDivisions = !empty($section->divisions());
	$sectionView->showDivisionIndex();
	showDocumentIndex($section->documents());
?></div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($sectionView->displayName()); ?></h2>
	
	<?php if (!$anyDivisions) { ?>
		<p>Sorry, there is nothing to see in this section.</p>
	<?php } else { ?>
		<p>Please select a Division to view.</p>
	<?php } ?>
</div>

<?php
pageFooter();
?>