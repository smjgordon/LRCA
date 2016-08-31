<?php
require_once 'p_html_functions.php';

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
							<td><?php echo $team->adjustedPoints; ?></td>
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
		
	private $_division;
}
?>