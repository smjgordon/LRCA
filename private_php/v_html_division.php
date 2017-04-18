<?php
require_once 'p_html_functions.php';
require_once 'u_text.php';

class HtmlDivisionView {
	public function __construct($division) {
		$this->_division = $division;
	}

	// TODO: update when/if section is refactored
	public function bodyTitle() {
		return $this->_division->section->displayName() . ' – ' . $this->_division->name;
	}

	public function headerTitle() {
		return $this->_division->name . ' – ' . $this->_division->section->displayName();
	}

	public function breakdown() {
		switch ($this->_division->breakdown) {
			case Breakdown::ByMonth:
				$months = $this->_division->playedMatchMonths();

				if ($months) {
					$result = '<ul>';
					foreach ($months as $yearAndMonth) {
						$monthName = monthNameFromIso($yearAndMonth);
						$result .= "<li><a href='results_by_month.php?did="
							. $this->_division->id()
							. "&amp;month=$yearAndMonth'>$monthName</a></li>";
					}
					return $result . '</ul>';
				} else {
					return null;
				}
				break;

			case Breakdown::ByRound:
				//$this->_division->loadRounds();
				$rounds = $this->_division->playedMatchRounds();

				// TODO: reimplement showing only those that actually have results
				if ($rounds) {
					$result = '<ul>';
					foreach ($rounds as $round) {
						$result .= "<li><a href='results_by_round.php?rid=" . $round->id() . "'>"
							. htmlspecialchars($round->name)
							. '</a></li>';
					}
					return $result . '</ul>';
				} else {
					return null;
				}
		}
	}

	public function showStandings() {
		if ($this->_division->format != DivisionFormat::Knockout) {
		?>
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
					$this->_division->loadTeams();
					$position = 0;

					foreach ($this->_division->rankedTeams() as $team) {
					?>
						<tr>
							<td><?php echo ++$position; ?></td>
							<td><?php echo htmlspecialchars($team->name); ?></td>
							<td><?php echo $team->played; ?></td>
							<td><?php echo $team->won; ?></td>
							<td><?php echo $team->drawn; ?></td>
							<td><?php echo $team->lost; ?></td>
							<td><?php echo $team->gpd; ?></td>
							<td><?php
								echo $team->adjustedPoints;
								if ($team->rawPoints != $team->adjustedPoints) echo '*';
							?></td>
						</tr>
					<?php
					}
				?></tbody>
			</table>
		<?php
		}
	}

	public function showFixtures() {
		$this->_division->loadRounds();
		foreach ($this->_division->rounds as $round) {
			if ($round->name) echo '<h3>', htmlspecialchars($round->name), '</h3>';

			$round->loadFixtures();
		?>
			<table class="fixtures">
				<?php foreach ($round->fixtures as $fixture) { ?>
					<tr>
						<td class="date"><?php
							if ($fixture->date) echo formatDate($fixture->date);
						?></td>
						<td class="homeTeam"><?php echo $fixture->homeTeam ? htmlspecialchars($fixture->homeTeam->name) : 'bye'; ?></td>
					<?php
						switch ($fixture->status) {
							case MatchStatus::Unplayed:
								echo '<td class="homeScore"></td><td class="dash">v</td><td class="awayScore"></td>';
								break;
							case MatchStatus::Played:
								echo '<td class="homeScore">', formatScore($fixture->homeAdjustedScore),
									'</td><td class="dash">–</td><td class="awayScore">',
									formatScore($fixture->awayAdjustedScore), '</td>';
								break;
							case MatchStatus::Postponed:
								echo '<td class="homeScore">P</td><td class="dash">–</td><td class="awayScore">P</td>';
								break;
							case MatchStatus::Defaulted:
								echo '<td class="homeScore">',
										$fixture->homeAdjustedScore == 0 ? 'd' : '', formatScore($fixture->homeAdjustedScore),
									'</td><td class="dash">–</td><td class="awayScore">',
										formatScore($fixture->awayAdjustedScore), $fixture->awayAdjustedScore == 0 ? 'd' : '',
									'</td>';
								break;
							case MatchStatus::ScoredBye:
								echo '<td class="homeScore">', formatScore($fixture->homeAdjustedScore),
									'</td><td class="dash">–</td><td class="awayScore">',
									formatScore($fixture->awayAdjustedScore), '</td>';
								break;
							case MatchStatus::Void:
								echo '<td class="homeScore">0</td><td class="dash">–</td><td class="awayScore">0</td>';
						}
					?>
						<td class="awayTeam"><?php echo $fixture->awayTeam ? htmlspecialchars($fixture->awayTeam->name) : 'bye'; ?></td>
					</tr>
				<?php } ?>
			</table>
		<?php
		}
	}

	// TODO: separate model from view
	public function showBoardDefaults() {
		global $Database;

		$stmt = $Database->prepare('
			SELECT bd.incurred_date, ht.name AS home_team, at.name AS away_team, re.text AS reason, bd.exempt,
				rex.text AS exempt_reason,
				Sum(bd.home_defaults) AS home_defaults,
				Sum(bd.away_defaults) AS away_defaults

			FROM board_default bd
				JOIN fixture f ON bd.fixture_id = f.fixture_id
					JOIN round r ON f.round_id = r.round_id
				JOIN team ht ON f.home_team_id = ht.team_id
				JOIN team at ON f.away_team_id = at.team_id
				JOIN reason re ON bd.reason = re.reason_id
				LEFT JOIN reason rex ON bd.exempt_reason = rex.reason_id

			WHERE r.division_id = ?

			GROUP BY bd.incurred_date, f.fixture_id, ht.name, at.name,
				bd.reason, bd.exempt, bd.exempt_reason

			ORDER BY bd.incurred_date, f.fixture_id');
		$stmt->execute([$this->_division->id()]);
	?>
		<table class="defaults">
	<?php
		while ($row = $stmt->fetch()) {
		?>
			<tr>
				<td class="date"><?php echo formatDate(strtotime($row['incurred_date'])); ?></td>
				<td class="homeTeam"><?php echo htmlspecialchars($row['home_team']); ?></td>
				<td class="homeScore"><?php echo $row['home_defaults']; ?></td>
				<td class="dash">–</td>
				<td class="awayScore"><?php echo $row['away_defaults']; ?></td>
				<td class="awayTeam"><?php echo htmlspecialchars($row['away_team']); ?></td>
				<td><?php echo htmlspecialchars($row['reason']); ?></td>
				<td><?php echo htmlspecialchars($row['exempt_reason']); ?></td>
			</tr>
		<?php
		}
	?>
		</table>
	<?php
	}

	public function showDefaultTotals() {
		// TODO: do away with parameters in SystemSettings and use the database-driven values instead
		global $Database;

		$stmt = $Database->prepare('
			SELECT t.team_id, t.name,
				Sum(CASE WHEN t.team_id = f.home_team_id
						 THEN bd.home_defaults
						 ELSE bd.away_defaults
					END) AS n
			FROM team t
				JOIN fixture f ON t.team_id IN (f.home_team_id, f.away_team_id)
				JOIN board_default bd ON f.fixture_id = bd.fixture_id
			WHERE bd.exempt = 0 AND t.division_id = ?
			GROUP BY t.team_id, t.name
			HAVING n > 0
			ORDER BY t.name');
		$stmt->execute([$this->_division->id()]);
	?>
		<table class="defaultTotals">
			<thead>
				<tr>
					<th>Team</th>
					<th>Defaults</th>
					<th>Points Deducted</th>
				</tr>
			</thead>
		<?php
			while ($row = $stmt->fetch()) {
				$nDefaults = $row['n'];
				if ($nDefaults >= SystemSettings::$defaultFirstPenalty) {
					$nPointsDeducted = (integer)
						(($nDefaults - SystemSettings::$defaultFirstPenalty) / SystemSettings::$defaultPenaltyEvery) + 1;
				} else {
					$nPointsDeducted = 0;
				}
			?>
				<tbody>
					<tr>
						<td><?php echo htmlspecialchars($row['name']); ?></td>
						<td><?php echo $nDefaults; ?></td>
						<td><?php echo $nPointsDeducted; ?></td>
					</tr>
				</tbody>
			<?php
			}
		?>
		</table>
	<?php
	}

	private $_division;
}
?>