<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_html_functions.php';

$clubID = @$_GET['cid'];
// make sure the club ID exists, and get the name of the club
if ($clubID) {
	if (!is_numeric($clubID)) errorPage(404);
	$clubID = (int) $clubID;
	$stmt = $Database->prepare('SELECT name FROM club WHERE club_id = ?');
	$stmt->execute([$clubID]);
	if ($row = $stmt->fetch()) {
		$pageTitle = $row['name'] . ' Player List';
	} else {
		errorPage(404);
	}
} else {
	$pageTitle = 'Player List';
}

if (isset($_GET['ps'])) {
	$playerStatusFilter = $_GET['ps'];
	// string must be a comma-separated list containing only 0/1/2
	if (!preg_match('/^[0-2](,[0-2])*$/', $playerStatusFilter)) errorPage(404);
} else {
	// default to active only
	$playerStatusFilter = '1';
}

$season = @(int) $_GET['season'];

if (!($season == Season::Winter || $season == Season::Summer)) {
	switch (SystemSettings::$currentSeason) {
		case Season::BothDefaultWinter: $season = Season::Winter; break;
		case Season::BothDefaultSummer: $season = Season::Summer; break;
		default: $season = SystemSettings::$currentSeason;
	}
}
$chooseSeason
	= SystemSettings::$currentSeason == Season::BothDefaultWinter
	|| SystemSettings::$currentSeason == Season::BothDefaultSummer;

pageHeader($pageTitle);
?>

<div id="subNav">
	<form method="get" action="players.php">
		<p>Club:<br />
				<select name="cid">
			<?php
				renderSelectOption('', $clubID, 'All');
				$stmt = $Database->query('SELECT club_id, name FROM club WHERE status = 1 ORDER BY name');
				while ($row = $stmt->fetch()) {
					renderSelectOption($row['club_id'], $clubID, $row['name']);
				}
			?>
			</select>
		</p>
		
		<p>Players to show:<br />
			<select name="ps"><?php
				renderSelectOption('0,1,2', $playerStatusFilter, 'Active and Inactive');
				renderSelectOption('1', $playerStatusFilter, 'Active Only');
				renderSelectOption('0,2', $playerStatusFilter, 'Inactive Only');
			?></select>
		</p>
		
		<?php if ($chooseSeason) { ?>
			<p>Grades to show:<br />
				<select name="season"><?php
					renderSelectOption(Season::Winter, $season, 'July ' . SystemSettings::$winterYear);
					renderSelectOption(Season::Summer, $season, 'January ' . SystemSettings::$summerYear);
				?></select>
			</p>		
		<?php } ?>

		<p><input type="submit" value="Filter" /></p>
	</form>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($pageTitle); ?></h2>
	<h3 class="sub">With <?php
		switch ($season) {
			case Season::Winter:
				echo 'July ', SystemSettings::$winterYear;
				break;
			case Season::Summer:
				echo 'January ', SystemSettings::$summerYear;
				break;
		}
	?> Grades</h3>

	<table class="players">
		<col class="ecfCode" />
		<col class="name" />
		<?php if (!$clubID) { ?>
			<col class="club" />
		<?php } ?>
		<colgroup><col class="grade" /><col class="cat" /></colgroup>
		<colgroup><col class="grade" /><col class="cat" /></colgroup>
		<col class="grade" />
		<thead>
			<tr>
				<th rowspan="2">ECF Code</th>
				<th rowspan="2">Name</th>
				<?php if (!$clubID) { ?>
					<th rowspan="2">Club</th>
				<?php } ?>
				<th colspan="2">Standard</th>
				<th colspan="2">Rapid</th>
				<th>LRCA Rapid</th>
			</tr>
			<tr>
				<th>Grade</th>
				<th>Cat</th>
				<th>Grade</th>
				<th>Cat</th>
				<th>Grade</th>
			</tr>
		</thead>
		<tbody><?php
			$sql = "
				SELECT p.player_id, p.forename, p.surname, p.ecf_grading_code, c.name AS club_name
				FROM player p JOIN club c ON p.club_id = c.club_id
				WHERE p.status IN ($playerStatusFilter)";

			if ($clubID) {
				$sql .= " AND c.club_id = $clubID";
			}

			$sql .= ' ORDER BY p.surname, p.forename';

			$stmtPlayer = $Database->query($sql);
			
			// OPTIMISE: cut number of calls to db
			$stmtGrade = $Database->prepare('
				SELECT g1.type, g1.effective_from, NullIf(g1.grade, 0) AS grade, g1.category
				FROM grade g1
					LEFT JOIN grade g2
						ON g1.player_id = g2.player_id
							AND g1.season = g2.season
							AND g1.type = g2.type
							AND g1.effective_from < g2.effective_from
				WHERE g1.player_id = ? AND g1.season = ?
					AND g2.grade_id IS NULL');

			while ($player = $stmtPlayer->fetch()) {
				$stmtGrade->execute([$player['player_id'], $season]);
				$grade = $stmtGrade->fetch();
			?>
				<tr>
					<td><?php
						$ecfCode = $player['ecf_grading_code'];
						if ($ecfCode) {
							echo "<a href='http://www.ecfgrading.org.uk/new/player.php?PlayerCode=$ecfCode'>$ecfCode</a>";
						}
					?></td>
					<td>
						<a href="player.php?pid=<?php echo $player['player_id']; ?>">
							<?php echo htmlspecialchars($player['surname']), ', ', htmlspecialchars($player['forename']); ?>
						</a>
					</td>
				<?php
					if (!$clubID) {
					?>
						<td><?php echo htmlspecialchars($player['club_name']); ?></td>
					<?php
					}

					// render the player's grades
					if ($grade && $grade['type'] == GradeType::Standard) {
					?>
						<td class="grade"><?php echo $grade['grade']; ?></td>
						<td class="cat"><?php echo $grade['category']; ?></td>
					<?php
						$grade = $stmtGrade->fetch();
					} else {
					?>
						<td class="grade"></td>
						<td class="cat"></td>
					<?php
					}
					if ($grade && $grade['type'] == GradeType::Rapid) {
					?>
						<td class="grade"><?php echo $grade['grade']; ?></td>
						<td class="cat"><?php echo $grade['category']; ?></td>
					<?php
						$grade = $stmtGrade->fetch();
					} else {
					?>
						<td class="grade"></td>
						<td class="cat"></td>
					<?php
					}
					if ($grade && $grade['type'] == GradeType::LrcaRapid) {
					?>
						<td class="grade"><?php echo $grade['grade']; ?></td>
					<?php
						$grade = $stmtGrade->fetch();
					} else {
					?>
						<td class="grade"></td>
					<?php
					}
				?>
				</tr>
			<?php
			}
		?>
		</tbody>
	</table>

	<?php /*if ($division->format != DivisionFormat::Knockout) { ?>
		<table class="standings">
			<thead>
				<tr>
					<th>Pos</th>
					<th>Team</th>
					<th>Pl</th>
					<th>W</th>
					<th>D</th>
					<th>L</th>
					<th>GPD</th>
					<th>Pts</th>
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

	if ($inTable) echo '</table>';*/
?>
</div>

<?php
pageFooter();
?>
