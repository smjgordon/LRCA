<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/p_match.php';

try {
	$team = Team::loadByUri($_SERVER['REQUEST_URI']);
	$division = $team->division();
	$divisionView = new HtmlDivisionView($division);
	$subtitle = $team->name() . ' Results';
} catch (ModelAccessException $ex) {
	errorPage(HttpStatus::NotFound);
}

pageHeader($subtitle . ' – ' . $divisionView->headerTitle());
?>

<div id="subNav">
<?php
	$sectionView = new HtmlSectionView($division->section());
	$sectionView->showDivisionIndex();
?>
	<ul><li><a href="../../penalties">Penalties</a></li></ul>
	<?php echo $divisionView->breakdown(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($divisionView->bodyTitle()); ?></h2>
	<h3 class="sub"><?php echo $subtitle; ?></h3>

<?php
	$stmt = $Database->prepare('
		SELECT fixture_id
		FROM fixture
		WHERE ? IN (home_team_id, away_team_id) AND status = 1
		ORDER BY fixture_date DESC');
	$stmt->execute([$team->id()]);
	$anyPenalties = false;

	$row = $stmt->fetch();
	if ($row) {
		do {
			$match = Match::load($row['fixture_id']);
			$anyPenalties |= $match->renderResult();
		} while (!!($row = $stmt->fetch()));
	} else {
		echo '<p>Sorry, no matches have yet been played by this team.</p>';
	}

	if ($anyPenalties) {
	?>	<p>* A penalty has been applied.  Please see the <a href="../../penalties">penalties</a> page for details.</p>
	<?php
	}
?></div>

<?php
pageFooter();
?>