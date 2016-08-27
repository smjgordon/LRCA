<?php
require_once 'private_php/p_global.php';

$fixtureID = @$_GET['fid'];
if (!is_numeric($fixtureID)) errorPage(404);
$fixtureID = (int) $fixtureID;

try {
	$match = Match::load($fixtureID);
} catch (Exception $ex) {
	errorPage(404);
}

pageHeader('Match Details');
?>

<div id="subNav">
	<?php //$anyDivisions = $section->divisionIndex(); ?>
</div>

<div id="subBody">
	<?php $match->renderResult(); ?>
</div>

<?php
pageFooter();
?>