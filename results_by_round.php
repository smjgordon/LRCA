<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_division.php';
require_once 'private_php/p_match.php';

$roundID = @$_GET['rid'];
if (!is_numeric($roundID)) errorPage(404);
$roundID = (int) $roundID;

$stmt = $Database->prepare('SELECT name, division_id FROM round WHERE round_id = ?');
$stmt->execute([$roundID]);
$row = $stmt->fetch();
if (!$row) errorPage(404);

$division = new OldDivision($row['division_id']);
$subtitle = $row['name'] . ' Results';

pageHeader($subtitle . ' – ' . $division->headerTitle());
?>

<div id="subNav"><?php
	$division->section->divisionIndex();
	$division->breakdown();
?></div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($division->bodyTitle()); ?></h2>
	<h3 class="sub"><?php echo $subtitle; ?></h3>

<?php
	$stmt = $Database->prepare('
		SELECT fixture_id
		FROM fixture
		WHERE round_id = ? AND status = 1
		ORDER BY fixture_date DESC');
	$stmt->execute([$roundID]);

	$row = $stmt->fetch();
	if ($row) {
		do {
			$match = Match::load($row['fixture_id']);
			$match->renderResult();
		} while ($row = $stmt->fetch());
	} else {
		echo '<p>Sorry, no matches have yet been played in this round.</p>';
	}
?></div>

<?php
pageFooter();
?>