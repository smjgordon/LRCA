<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_division.php';
require_once 'private_php/p_match.php';

$divisionID = @$_GET['did'];
if (!is_numeric($divisionID)) errorPage(404);
$divisionID = (int) $divisionID;

try {
	$division = new OldDivision($divisionID);
} catch (Exception $ex) {
	errorPage(404);
}

$datePattern = $_GET['month'];
if (!preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]$/', $datePattern)) errorPage(404);

$subtitle = monthNameFromIso($datePattern) . ' Results';
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
		SELECT f.fixture_id
		FROM fixture f
			JOIN round r ON f.round_id = r.round_id
		WHERE r.division_id = ?
			AND f.fixture_date LIKE ?
			AND f.status = 1
		ORDER BY f.fixture_date DESC');
	$stmt->execute([$divisionID, $datePattern . '%']);

	$row = $stmt->fetch();
	if ($row) {
		do {
			$match = Match::load($row['fixture_id']);
			$match->renderResult();
		} while ($row = $stmt->fetch());
	} else {
		echo '<p>Sorry, no matches have been played in this division in this time period.</p>';
	}
?></div>

<?php
pageFooter();
?>