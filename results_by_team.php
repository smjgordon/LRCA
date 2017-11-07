<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_division.php';
require_once 'private_php/v_html_division.php';
require_once 'private_php/p_match.php';

$teamId = @$_GET['tid'];
if (!is_numeric($teamId)) errorPage(404);
$teamId = (int) $teamId;

$team = Team::loadById($teamId);

$division = $team->division;
//$divisionId = $division->id();
//$divisionView = new HtmlDivisionView(Division::loadById($divisionId));
$divisionView = new HtmlDivisionView($division);
$subtitle = $team->name . ' Results';

pageHeader($subtitle . ' – ' . $divisionView->headerTitle());
?>

<div id="subNav">
	<?php $division->section->divisionIndex(); // TODO: figure out what to do with this ?>
	<ul><li><a href='penalties.php?did=<?php echo $divisionId; ?>'>Penalties</a></li></ul>
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
	$stmt->execute([$teamId]);

	$row = $stmt->fetch();
	if ($row) {
		do {
			$match = Match::load($row['fixture_id']);
			$match->renderResult();
		} while (!!($row = $stmt->fetch()));
	} else {
		echo '<p>Sorry, no matches have yet been played by this team.</p>';
	}
?></div>

<?php
pageFooter();
?>