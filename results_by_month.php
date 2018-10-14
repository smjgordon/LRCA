<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/p_match.php';

$uriParts = explode('/month/', $_SERVER['REQUEST_URI']);
if (count($uriParts) != 2) errorPage(HttpStatus::InternalError);

try {
	$division = Division::loadByUri($uriParts[0]);
	$divisionView = new HtmlDivisionView($division);
} catch (Exception $ex) {
	errorPage(HttpStatus::NotFound);
}

//$datePattern = $_GET['month'];
$datePattern = $uriParts[1];
if (!preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]$/', $datePattern)) errorPage(HttpStatus::NotFound);

$subtitle = monthNameFromIso($datePattern) . ' Results';
pageHeader($subtitle . ' – ' . $divisionView->headerTitle());
?>

<div id="subNav">
<?php
	$sectionView = new HtmlSectionView($division->section());
	$sectionView->showDivisionIndex();
?>
	<ul><li><a href="../penalties">Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>
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
	$stmt->execute([$division->id(), $datePattern . '%']);
	$anyPenalties = false;

	$row = $stmt->fetch();
	if ($row) {
		do {
			$match = Match::load($row['fixture_id']);
			$anyPenalties |= $match->renderResult();
		} while ($row = $stmt->fetch());
	} else {
		echo '<p>Sorry, no matches have been played in this division in this time period.</p>';
	}
	
	if ($anyPenalties) {
	?>	<p>* A penalty has been applied.  Please see the <a href="../penalties">penalties</a> page for details.</p>
	<?php
	}
?></div>

<?php
pageFooter();
?>