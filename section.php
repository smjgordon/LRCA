<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_section.php';

$year = @$_GET['year'];
if (!is_numeric($year)) errorPage(404);
$year = (int) $year;

$sectionID = @$_GET['sid'];
if (!is_numeric($sectionID)) errorPage(404);
$sectionID = (int) $sectionID;

try {
	$section = new Section($sectionID, $year);
} catch (Exception $ex) {
	errorPage(404);
}
$title = $section->displayName();

pageHeader($title);
?>

<div id="subNav"><?php
	$anyDivisions = $section->divisionIndex();
	$section->documentList();
?></div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($title); ?></h2>
	
	<?php if (!$anyDivisions) { ?>
		<p>Sorry, there is nothing to see in this section.</p>
	<?php } else { ?>
		<p>Please select a Division to view.</p>
	<?php } ?>
</div>

<?php
pageFooter();
?>