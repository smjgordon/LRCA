<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/p_match.php';

$divisionId = @$_GET['did'];
if (!is_numeric($divisionId)) errorPage(404);
$divisionId = (int) $divisionId;

try {
	$division = new OldDivision($divisionId);
	$divisionView = new HtmlDivisionView(Division::loadById($divisionId));
} catch (Exception $ex) {
	errorPage(404);
}

$datePattern = $_GET['month'];
if (!preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]$/', $datePattern)) errorPage(404);

$subtitle = monthNameFromIso($datePattern) . ' Results';
pageHeader($subtitle . ' – ' . $division->headerTitle());
?>

<div id="subNav">
	<?php $division->section->divisionIndex(); // TODO: figure out what to do with this ?>
	<ul><li><a href='penalties.php?did=<?php echo $divisionId; ?>'>Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

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
	$stmt->execute([$divisionId, $datePattern . '%']);

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