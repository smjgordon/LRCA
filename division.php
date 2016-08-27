<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_division.php';

$divisionID = @$_GET['did'];
if (!is_numeric($divisionID)) errorPage(404);
$divisionID = (int) $divisionID;

try {
	$division = new OldDivision($divisionID);
} catch (Exception $ex) {
	errorPage(404);
}

pageHeader($division->headerTitle());
?>

<div id="subNav"><?php
	$division->section->divisionIndex();
	$division->breakdown();
?></div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($division->bodyTitle()); ?></h2>

	<?php if ($division->format != DivisionFormat::Knockout) { ?>
		<table class="standings">
			<thead>
				<tr>
					<th scope="col">Pos</th>
					<th scope="col">Team</th>
					<th scope="col">Pl</th>
					<th scope="col">W</th>
					<th scope="col">D</th>
					<th scope="col">L</th>
					<th scope="col">GPD</th>
					<th scope="col">Pts</th>
				</tr>
			</thead>
			<tbody><?php
				$position = 0;
				$stmt = $Database->prepare('
					SELECT name, played, won, drawn, lost, gpd, raw_points, adjusted_points
					FROM team
					WHERE division_id = ?
					ORDER BY adjusted_points DESC, gpd DESC, name');
				$stmt->execute([$divisionID]);
				
				while ($row = $stmt->fetch()) {
				?>
					<tr>
						<td><?php echo ++$position; ?></td>
						<td><?php echo htmlspecialchars($row['name']); ?></td>
						<td><?php echo $row['played']; ?></td>
						<td><?php echo $row['won']; ?></td>
						<td><?php echo $row['drawn']; ?></td>
						<td><?php echo $row['lost']; ?></td>
						<td><?php echo $row['gpd']; ?></td>
						<td><?php echo $row['adjusted_points']; ?></td>
					</tr>
				<?php
				}
			?></tbody>
		</table>
	<?php } ?>

<?php
	$stmt = $Database->prepare("
		SELECT r.round_id, f.fixture_id, r.name AS round_name, f.fixture_date, f.status,
			Coalesce(home.name, 'bye') AS home_name, Coalesce(away.name, 'bye') AS away_name,
			f.home_adjusted_score, f.away_adjusted_score
		FROM round r
			JOIN fixture f ON r.round_id = f.round_id
			LEFT JOIN team home ON f.home_team_id = home.team_id
			LEFT JOIN team away ON f.away_team_id = away.team_id
		WHERE r.division_id = ?
		ORDER BY r.sequence, f.fixture_date, f.fixture_id");
	$stmt->execute([$divisionID]);
	
	$lastRoundName = null;
	$inTable = false;
	
	while ($row = $stmt->fetch()) {
		// first, see if we're starting a new round
		$roundName = $row['round_name'];
		
		if ($roundName != $lastRoundName) {
			if ($inTable) {
				echo '</table>';
				$inTable = false;
			}
			echo '<h3>', htmlspecialchars($roundName), '</h3>';
			$lastRoundName = $roundName;
		}
		if (!$inTable) {
			echo '<table class="fixtures">';
			$inTable = true;
		}

		$fixtureDate = strtotime($row['fixture_date']);
		$homeScore = $row['home_adjusted_score'];
		$awayScore = $row['away_adjusted_score'];
		
		// now display the fixture
		echo "<!--$row[fixture_id]-->"; // DEBUG
	?>
		<tr>
			<td class="date"><?php
				if ($fixtureDate) echo formatDate($fixtureDate);
			?></td>
			<td class="homeTeam"><?php echo $row['home_name']; ?></td>
		<?php
			switch ($row['status']) {
				case MatchStatus::Unplayed:
					echo '<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>';
					break;
				case MatchStatus::Played:
					echo '<td class="homeScore">', formatScore($homeScore),
						'</td><td class="dash">–</td><td class="awayScore">',
						formatScore($awayScore), '</td>';
					break;
				case MatchStatus::Postponed:
					echo '<td class="homeScore">P</td><td class="dash">–</td><td class="awayScore">P</td>';
					break;
				case MatchStatus::Defaulted:
					echo '<td class="homeScore">', $homeScore == 0 ? 'd' : '', formatScore($homeScore),
						'</td><td class="dash">–</td><td class="awayScore">',
						formatScore($awayScore), $awayScore == 0 ? 'd' : '', '</td>';
					break;
				case MatchStatus::ScoredBye:
					echo '<td class="homeScore">', formatScore($homeScore),
						'</td><td class="dash">–</td><td class="awayScore">',
						formatScore($awayScore), '</td>';
					break;
				case MatchStatus::Void:
					echo '<td class="homeScore">0</td><td class="dash">–</td><td class="awayScore">0</td>';
			}
		?>
			<td class="awayTeam"><?php echo $row['away_name']; ?></td>
		</tr>
	<?php
	}
	
	if ($inTable) echo '</table>';
?>
</div>

<?php
pageFooter();
?>
