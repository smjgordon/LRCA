<?php
// TODO: refactor
require_once 'p_enumerations.php';
require_once 'p_server.php';
require_once 'p_html_functions.php';

abstract class Match {
	static public function load($fixtureID) {
		global $Database;

		// OPTIMISE: cut number of calls to db
		$stmt = $Database->prepare('
			SELECT d.division_id, d.match_style
			FROM fixture f
				JOIN round r ON f.round_id = r.round_id
				JOIN division d ON r.division_id = d.division_id
			WHERE f.fixture_id = ?');
		$stmt->execute([$fixtureID]);
		$row = $stmt->fetch();
		if (!$row) throw new Exception('Fixture ID not found');

		switch ($row['match_style']) {
			case MatchStyle::Standard:
				return new StandardMatch($fixtureID);

			case MatchStyle::RapidSame:
				return new RapidSameMatch($fixtureID);

			case MatchStyle::RapidDifferent:
				return new RapidDifferentMatch($fixtureID);
		}
	}

	protected $id, $date, $status, $handicapSchemeID;
	protected $homeTeamID, $homeTeamName, $homeRawScore, $homeHandicap, $homeAdjustedScore;
	protected $awayTeamID, $awayTeamName, $awayRawScore, $awayHandicap, $awayAdjustedScore;
	protected $games;

	private function __construct($fixtureID) {
		global $Database;

		$this->id = $fixtureID;

		$stmt = $Database->prepare('
			SELECT f.fixture_date, f.status, d.handicap_scheme_id,
				home.team_id AS home_team_id, home.name AS home_team_name, f.home_raw_score, f.home_handicap, f.home_adjusted_score,
				away.team_id AS away_team_id, away.name AS away_team_name, f.away_raw_score, f.away_handicap, f.away_adjusted_score,
				f.grade_difference
			FROM fixture f
				JOIN round r ON f.round_id = r.round_id
				JOIN division d ON r.division_id = d.division_id
				LEFT JOIN team home ON f.home_team_id = home.team_id
				LEFT JOIN team away ON f.away_team_id = away.team_id
			WHERE f.fixture_id = ?');
		$stmt->execute([$fixtureID]);
		$row = $stmt->fetch();

		if (!$row) throw new Exception('Fixture ID not found');
		$this->date = strtotime($row['fixture_date']);
		$this->status = $row['status'];
		$this->handicapSchemeID = $row['handicap_scheme_id'];
		$this->homeTeamID = $row['home_team_id'];
		$this->homeTeamName = $row['home_team_name'];
		$this->homeRawScore = $row['home_raw_score'];
		$this->gradeDifference = $row['grade_difference'];
		$this->homeHandicap = $row['home_handicap'];
		$this->homeAdjustedScore = $row['home_adjusted_score'];
		$this->awayTeamID = $row['away_team_id'];
		$this->awayTeamName = $row['away_team_name'];
		$this->awayRawScore = $row['away_raw_score'];
		$this->awayHandicap = $row['away_handicap'];
		$this->awayAdjustedScore = $row['away_adjusted_score'];

		$stmt = $Database->prepare("
			SELECT g.board, g.home_colour, g.raw_result, g.adjusted_result,
				hp.player_id AS home_player_id,
				Concat(hp.surname, Coalesce(Concat(', ', NullIf(hp.forename, '')), '')) AS home_player,
				NullIf(g.home_grade, 0) AS home_grade,
				ap.player_id AS away_player_id,
				Concat(ap.surname, Coalesce(Concat(', ', NullIf(ap.forename, '')), '')) AS away_player,
				NullIf(g.away_grade, 0) AS away_grade,
				g.grade_difference
			FROM game g
				LEFT JOIN player hp ON g.home_player_id = hp.player_id
				-- LEFT JOIN grade hpg ON g.home_grade_id = hpg.grade_id
				LEFT JOIN player ap ON g.away_player_id = ap.player_id
				-- LEFT JOIN grade apg ON g.away_grade_id = apg.grade_id
			WHERE fixture_id = ?
			ORDER BY board, home_colour DESC");
		$stmt->execute([$fixtureID]);

		$this->games = array();
		while ($row = $stmt->fetch()) {
			$this->games[] = new Game($row);
		}
	}

	// default implementation, suitable for standard and rapid-different formats
	public function renderResult() {
	?>
		<table class="result sp">
			<col class="board"/><col class="colour"/><col class="grade"/><col class="name"/>
			<col class="homeScore"/><col class="dash"/><col class="awayScore"/><col class="name"/><col class="grade"/>
			<?php if ($this->handicapSchemeID) { // extra column for grade difference ?>
				<col class="grade"/>
			<?php } ?>
			<thead>
				<tr>
					<th colspan="2"><?php echo formatDate($this->date, false); ?></th>
					<th colspan="2"><?php echo $this->homeTeamName; ?></th>
					<th></th><th class="dash">v</th><th></th>
					<th colspan="2"><?php echo $this->awayTeamName; ?></th>
					<?php if ($this->handicapSchemeID) { ?>
						<th class="grade">GD</th>
					<?php } ?>
				</tr>
			</thead>
			<tfoot>
				<?php if ($this->handicapSchemeID) { ?>
					<tr>
						<td colspan="4"></td>
						<td class="homeScore"><?php echo formatScore($this->homeRawScore); ?></td>
						<td class="dash">–</td>
						<td class="awayScore"><?php echo formatScore($this->awayRawScore); ?></td>
						<td colspan="2"></td>
						<td class="grade"><?php echo $this->gradeDifference; ?></td>
					</tr>
					<tr>
						<td colspan="4">Handicap</td>
						<td class="homeScore"><?php echo formatScore($this->homeHandicap); ?></td>
						<td class="dash">–</td>
						<td class="awayScore"><?php echo formatScore($this->awayHandicap); ?></td>
						<td colspan="3"></td>
					</tr>
				<?php } ?>
				<tr>
					<td colspan="4"></td>
					<td class="homeScore"><?php echo formatScore($this->homeAdjustedScore); ?></td>
					<td class="dash">–</td>
					<td class="awayScore"><?php echo formatScore($this->awayAdjustedScore); ?></td>
					<td colspan="<?php echo $this->handicapSchemeID ? 3 : 2; ?>"></td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->games as $game) { ?>
					<tr>
						<td class="board"><?php echo $game->board; ?></td>
						<td class="colour"><?php echo $game->homeColour; ?></td>
						<td class="grade"><?php echo $game->homePlayerGrade; ?></td>
						<td class="name"><?php echo $game->homePlayerName; ?></td>
						<?php formatGameResult($game->adjustedResult); ?>
						<td class="name"><?php echo $game->awayPlayerName; ?></td>
						<td class="grade"><?php echo $game->awayPlayerGrade; ?></td>
						<?php if ($this->handicapSchemeID) { ?>
							<td class="grade"><?php echo $game->gradeDifference; ?></td>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php
	}
}

class Game {
	public $board, $homeColour, $rawResult, $adjustedResult;
	public $homePlayerID, $homePlayerName, $homePlayerGrade;
	public $awayPlayerID, $awayPlayerName, $awayPlayerGrade;
	public $gradeDifference;

	public function __construct($row) {
		$this->board = $row['board'];
		$this->homeColour = $row['home_colour'];
		$this->rawResult = $row['raw_result'];
		$this->adjustedResult = $row['adjusted_result'];
		$this->homePlayerID = $row['home_player_id'];
		$this->homePlayerName = $row['home_player'];
		$this->homePlayerGrade = $row['home_grade'];
		$this->awayPlayerID = $row['away_player_id'];
		$this->awayPlayerName = $row['away_player'];
		$this->awayPlayerGrade = $row['away_grade'];
		$this->gradeDifference = $row['grade_difference'];
	}
}

class StandardMatch extends Match {
}

class RapidSameMatch extends Match {
	public function renderResult() {
		// TODO: support handicap display
	?>
		<table class="result rp">
			<col class="board"/><col class="grade"/><col class="name"/>
			<col class="homeScore"/><col class="dash"/><col class="awayScore"/>
			<col class="dash"/>
			<col class="homeScore"/><col class="dash"/><col class="awayScore"/>
			<col class="name"/><col class="grade"/>
			<thead>
				<tr>
					<th><?php echo formatDate($this->date, false); ?></th>
					<th colspan="2"><?php echo $this->homeTeamName; ?></th>
					<th class="homeScore">W</th><th class="dash">–</th><th class="awayScore">B</th>
					<th></th>
					<th class="homeScore">B</th><th class="dash">–</th><th class="awayScore">W</th>
					<th colspan="2"><?php echo $this->awayTeamName; ?></th></tr>
			</thead>
			<tfoot>
				<tr><td colspan="3"></td>
				<td colspan="3" class="homeScore"><?php echo formatScore($this->homeAdjustedScore); ?></td>
				<td class="dash">–</td>
				<td colspan="3" class="awayScore"><?php echo formatScore($this->awayAdjustedScore); ?></td>
				<td colspan="2"></td></tr>
			</tfoot>
			<tbody><?php
				for ($iGame = 0; $iGame < count($this->games); $iGame += 2) {
					$wbGame = $this->games[$iGame];
					$bwGame = $this->games[$iGame + 1];
				?>
					<tr>
						<td class="board"><?php echo $wbGame->board; ?></td>
						<td class="grade"><?php echo $wbGame->homePlayerGrade; ?></td>
						<td class="name"><?php echo $wbGame->homePlayerName; ?></td>
						<?php formatGameResult($wbGame->adjustedResult); ?>
						<td></td>
						<?php formatGameResult($bwGame->adjustedResult); ?>
						<td class="name"><?php echo $wbGame->awayPlayerName; ?></td>
						<td class="grade"><?php echo $wbGame->awayPlayerGrade; ?></td>
					</tr>
				<?php
				}
			?></tbody>
		</table>
	<?php
	}
}

class RapidDifferentMatch extends Match {
}
?>