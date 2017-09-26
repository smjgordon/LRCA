<?php
// TODO: refactor
// TODO: consider handicapping for rapidplay (currently not needed, but who knows?)
require_once 'p_enumerations.php';
require_once 'p_server.php';
require_once 'm_division.php';
require_once 'm_handicap.php';
require_once 'm_player.php';
require_once 'p_html_functions.php';
require_once 'p_email.php';
require_once 'u_text.php';

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

	//public $handicapSchemeID; // debug
	protected $id, $date, $status, /*$handicapSchemeID,*/ $handicapScheme, /*$divisionId,*/ $division, $gradeDifference;
	protected $homeTeamID, $homeTeamName, $homeRawScore, $homeHandicap, $homeAdjustedScore;
	protected $awayTeamID, $awayTeamName, $awayRawScore, $awayHandicap, $awayAdjustedScore;
	protected $comments;
	protected $games;

	private function __construct($fixtureID) {
		global $Database;

		$this->id = $fixtureID;

		$stmt = $Database->prepare('
			SELECT d.division_id, f.fixture_date, f.status, d.handicap_scheme_id,
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

		//var_dump($row); // debug
		//echo 'fixture: ', $fixtureID; // debug
		if (!$row) throw new Exception('Fixture ID not found');
		$this->date = strtotime($row['fixture_date']);
		$this->status = $row['status'];
		$handicapSchemeId = $row['handicap_scheme_id'];
		if ($handicapSchemeId) $this->handicapScheme = HandicapScheme::loadById($handicapSchemeId);
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
		//$this->divisionId = $row['division_id'];
		$this->division = Division::loadById($row['division_id']);

		$stmt = $Database->prepare("
			SELECT g.board, g.home_colour, g.raw_result, g.adjusted_result,
				hp.player_id AS home_player_id,
				g.home_planned_player_id,
				Concat(hp.surname, Coalesce(Concat(', ', NullIf(hp.forename, '')), '')) AS home_player,
				NullIf(g.home_grade, 0) AS home_grade,
				ap.player_id AS away_player_id,
				g.away_planned_player_id,
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

		$this->games = [];
		while ($row = $stmt->fetch()) {
			$this->games[] = Game::fromDbRow($row);
		}
		//echo 'handicap: ', $this->handicapSchemeID; // debug
	}

	// default implementation, suitable for standard and rapid-different formats
	public function renderResult() {
	?>
		<table class="result sp">
			<col class="board"/><col class="colour"/><col class="grade"/><col class="name"/>
			<col class="homeScore"/><col class="dash"/><col class="awayScore"/><col class="name"/><col class="grade"/>
			<?php if ($this->handicapScheme) { // extra column for grade difference ?>
				<col class="grade"/>
			<?php } ?>
			<thead>
				<tr>
					<th colspan="2"><?php echo formatDate($this->date, false); ?></th>
					<th colspan="2"><?php echo $this->homeTeamName; ?></th>
					<th></th><th class="dash">v</th><th></th>
					<th colspan="2"><?php echo $this->awayTeamName; ?></th>
					<?php if ($this->handicapScheme) { ?>
						<th class="grade">GD</th>
					<?php } ?>
				</tr>
			</thead>
			<tfoot>
				<?php if ($this->handicapScheme) { ?>
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
						<td colspan="2"></td>
						<td></td>
					</tr>
				<?php } ?>
				<tr>
					<td colspan="4"></td>
					<td class="homeScore"><?php echo formatScore($this->homeAdjustedScore); ?></td>
					<td class="dash">–</td>
					<td class="awayScore"><?php echo formatScore($this->awayAdjustedScore); ?></td>
					<td colspan="<?php echo $this->handicapScheme ? 3 : 2; ?>"></td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->games as $game) { ?>
					<tr>
						<td class="board"><?php echo $game->board; ?></td>
						<td class="colour"><?php echo $game->homeColour; ?></td>
						<td class="grade"><?php echo $game->homePlayerGrade; ?></td>
						<td class="name"><?php echo Match::renderPlayerName($game->homePlannedPlayer, $game->homePlayer); /*htmlspecialchars($game->homePlayer->fullNameFiling());*/ ?></td>
						<?php formatGameResult($game->adjustedResult,
							$game->homePlayer->id() == PlayerId::BoardDefault,
							$game->awayPlayer->id() == PlayerId::BoardDefault); ?>
						<td class="name"><?php echo Match::renderPlayerName($game->awayPlannedPlayer, $game->awayPlayer); /*htmlspecialchars($game->awayPlayer->fullNameFiling());*/ ?></td>
						<td class="grade"><?php echo $game->awayPlayerGrade; ?></td>
						<?php if ($this->handicapScheme) { ?>
							<td class="grade"><?php echo $game->gradeDifference; ?></td>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php
	}

	protected static function renderPlayerName($plannedPlayer, $actualPlayer) {
		if ($plannedPlayer->id() == $actualPlayer->id() | $actualPlayer->id() == PlayerId::BoardDefault) {
			return htmlspecialchars($plannedPlayer->fullNameFiling());
		} else {
			return htmlspecialchars($actualPlayer->fullNameFiling()) . ' (sub)';
		}
	}
	
	// default implementation, suitable for standard and rapid-different formats
	// TODO: implement for rapid-same
	public function renderPlainTextResult() {
		/* Sample rendering:
		01 Sep     Ashby 1                     v    Loughborough 1
		1 B    123 Armstrong, Victor         1 – 0  Glover, John              130
		2 W    185 Agnew, Alan               ½ – ½  Miller, James             158
		3 B    127 Hayden, Lawrence          0 – 1  Adcock, Terry             127
		4 W    125 Reynolds, David           1 – 0  Northage, Robert          152
		                                    2½ – 1½

		01 Sep     Ashby 1                     v    Loughborough 1
		1 B    123 Armstrong, Victor         1 – 0  Glover, John              130     7
		2 W    185 Agnew, Alan               ½ – ½  Miller, James             158   -27
		3 B    127 Hayden, Lawrence          0 – 1  Adcock, Terry             127     0
		4 W    125 Reynolds, David           1 – 0  Northage, Robert          152    27
		                                    2½ – 1½                                   7
		Handicap                             0 – 0
		                                    2½ – 1½
		*/
		// header line: date and teams
		$result = formatDate($this->date, false) . '     ' . padRight($this->homeTeamName, 25) . '   v    ' . $this->awayTeamName;
		// HACK: since we've so far only written 1 line, we can pad the whole string to get the 'GD' in the right place
		if ($this->handicapScheme) $result = padRight($result, 71) . 'GD';

		// body: game results
		foreach ($this->games as $game) {
			// TEMPORARY: just show the planned player - can't submit substitutions currently
			$result .= "\n$game->board $game->homeColour"
				//. str_pad($game->homePlayerGrade, 7, ' ', STR_PAD_LEFT) . ' ' . str_pad($game->homePlayerName, 26);
				. '   ' . padLeft($game->homePlayerGrade, 4) . ' ' . padRight($game->homePlannedPlayer->fullNameFiling(), 25);
				
			switch ($game->adjustedResult) {
				case GameResult::HomeWin:
					$result .= ($game->awayPlayer->id() == PlayerId::BoardDefault) ? ' 1 - 0d ' : ' 1 - 0  ';
					break;
				// TEMPORARY
				case GameResult::Draw:
					$result .= '0.5-0.5 ';
					break;

				//case GameResult::Draw:          $result .= ' ½ - ½  '; break;
				case GameResult::AwayWin:
					$result .= ($game->homePlayer->id() == PlayerId::BoardDefault) ? 'd0 - 1  ' : ' 0 - 1  ';
					break;
				case GameResult::DoubleDefault:
					$result .= 'd0 - 0d ';
			}

			$result .= padRight($game->awayPlannedPlayer->fullNameFiling(), 25) . padLeft($game->awayPlayerGrade, 4);
			if ($this->handicapScheme) $result .= padLeft($game->gradeDifference, 6);
		}

		// footer line: total score
		// TEMPORARY: formatScoreEmailWorkaround
		if ($this->handicapScheme) {
			$result .= "\n" . padLeft(self::formatScoreEmailWorkaround($this->homeRawScore), 39)
				. '-' . self::formatScoreEmailWorkaround($this->awayRawScore)
				. padLeft($this->gradeDifference, 36);
			$result .= "\nHandicap" . padLeft(self::formatScoreEmailWorkaround($this->homeHandicap), 31)
				. '-' . self::formatScoreEmailWorkaround($this->awayHandicap);
		}
		$result .= "\n" . padLeft(self::formatScoreEmailWorkaround($this->homeAdjustedScore), 39)
			. '-' . self::formatScoreEmailWorkaround($this->awayAdjustedScore);
		//$result .= "\n" . padLeft(formatScore($this->homeAdjustedScore), 38)
		//	. ' - ' . formatScore($this->awayAdjustedScore);

		return $result;
	}

	public function saveSubmission() {
		global $Database, $CurrentUser;

		// tables to update:
		// team - increment tallies
		// game - add game
		// fixture - update status and score
		// board_default - add default if applicable

		// populate game table with individual board results
		$stmt = $Database->prepare('
			INSERT INTO game(fixture_id, board, home_colour,
				home_planned_player_id, home_player_id, home_grade_id, home_grade,
				away_planned_player_id, away_player_id, away_grade_id, away_grade,
				raw_result, adjusted_result, grade_difference)
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

		$stmtBoardDefault = $Database->prepare('
					INSERT INTO board_default(fixture_id, game_id, reason, exempt, home_defaults, away_defaults, incurred_date)
					VALUES(?, ?, ?, 0, ?, ?, ?)');

		foreach ($this->games as $game) {
			$stmt->execute([
				$this->id, $game->board, $game->homeColour,
				$game->homePlannedPlayer->id(), $game->homePlayer->id(),
				$game->homePlayerGradeObj ? $game->homePlayerGradeObj->id() : null, $game->homePlayerGrade,
				$game->awayPlannedPlayer->id(), $game->awayPlayer->id(),
				$game->awayPlayerGradeObj ? $game->awayPlayerGradeObj->id() : null, $game->awayPlayerGrade,
				$game->rawResult, $game->adjustedResult, $game->gradeDifference]);

			// if this board has been defaulted, then record the default
			if ($game->homePlayer->id() == PlayerID::BoardDefault || $game->awayPlayer->id() == PlayerID::BoardDefault) {
				$stmtBoardDefault->execute([
					$this->id, $Database->lastInsertId(), PenaltyReason::BoardDefault,
					$game->homePlayer->id() == PlayerID::BoardDefault ? 1 : 0,
					$game->awayPlayer->id() == PlayerID::BoardDefault ? 1 : 0,
					date('c', $this->date)]);
			}
		}

		// determine which club is submitting, and hence which club needs to approve
		$homeTeam = Team::loadById($this->homeTeamID);
		$awayTeam = Team::loadById($this->awayTeamID);
		if ($CurrentUser->club()->id() == $homeTeam->club->id()) {
			$approvalClub = $awayTeam->club;
		} else if ($CurrentUser->club()->id() == $awayTeam->club->id()) {
			$approvalClub = $homeTeam->club;
		} else {
			throw new Exception("User shouldn't have been able to submit this result");
		}

		// update match status in fixture table
		// TODO: support handicaps
		/*if ($this->homeRawScore > $this->awayRawScore) {
			$rawResult = GameResult::HomeWin;
		} else if ($this->homeRawScore == $this->awayRawScore) {
			$rawResult = GameResult::Draw;
		} else {
			$rawResult = GameResult::AwayWin;
		}*/

		// update match status in fixture table
		// TODO: support handicaps
		if ($this->homeAdjustedScore > $this->awayAdjustedScore) {
			$rawResult = $adjustedResult = GameResult::HomeWin;
		} else if ($this->homeAdjustedScore == $this->awayAdjustedScore) {
			$rawResult = $adjustedResult = GameResult::Draw;
		} else {
			$rawResult = $adjustedResult = GameResult::AwayWin;
		}

		$stmt = $Database->prepare('
			UPDATE fixture SET
				home_raw_score = ?, home_adjusted_score = ?,
				away_raw_score = ?, away_adjusted_score = ?,
				status = 1, submitted_user_id = ?, submitted_date = ?,
				raw_result = ?, adjusted_result = ?, approval_club_id = ?,
				home_handicap = ?, away_handicap = ?, grade_difference = ?,
				comment = ?
			WHERE fixture_id = ?');
		$stmt->execute([
			$this->homeRawScore, $this->homeAdjustedScore,
			$this->awayRawScore, $this->awayAdjustedScore,
			$CurrentUser->id(), date('c'),
			$rawResult, $adjustedResult, $approvalClub->id(),
			$this->homeHandicap, $this->awayHandicap, $this->gradeDifference,
			$this->comments,
			$this->id]);

		// update team standings
		// TODO: populate penalty table
		$stmt = $Database->prepare('
			UPDATE team SET
				played = played + 1,
				won = won + ?,
				drawn = drawn + ?,
				lost = lost + ?,
				gpd = gpd + ?,
				raw_points = raw_points + ?,
				adjusted_points = adjusted_points + ?
			WHERE team_id = ?');

		$homeWin = $awayWin = $draw = $homeLoss = $awayLoss = $homePoints = $awayPoints = 0;

		switch ($adjustedResult) {
			case GameResult::DoubleDefault:
				$homeLoss = $awayLoss = 1;
				break;

			case GameResult::AwayWin:
				$homeLoss = $awayWin = 1;
				$awayPoints = 2;
				break;

			case GameResult::Draw:
				$draw = 1;
				$homePoints = $awayPoints = 1;
				break;

			case GameResult::HomeWin:
				$homeWin = $awayLoss = 1;
				$homePoints = 2;
		}

		$stmt->execute([$homeWin, $draw, $homeLoss, $this->homeAdjustedScore - $this->awayAdjustedScore,
			$homePoints, $homePoints, $this->homeTeamID]);
		$stmt->execute([$awayWin, $draw, $awayLoss, $this->awayAdjustedScore - $this->homeAdjustedScore,
			$awayPoints, $awayPoints, $this->awayTeamID]);
	}

	public function generateEmailConfirmation() {
		global $CurrentUser;

		$subject = "Result: " . $this->division->name . " - $this->homeTeamName v $this->awayTeamName";

		$message = 'Dear ' . $CurrentUser->forename() . ',

This is a confirmation of the result you have submitted.

' . $this->renderPlainTextResult();

		if ($this->comments) {
			$message .= "

Comments:
$this->comments";
		}

		emailConfirmation($subject, $message, [$CurrentUser], 'result_submitted.php');
	}

	public function saveApproval() {
		global $Database, $CurrentUser;

		$stmt = $Database->prepare('
			UPDATE fixture SET
				approved_user_id = ?, approved_date = ?
			WHERE fixture_id = ?');
		$stmt->execute([
			$CurrentUser->id(), date('c'), $this->id]);
	}

	abstract public function renderSubmissionForm();
	abstract public function buildSubmission();

	// TODO: fetch the players only once for the match
	public function renderPlayerSelection($fieldName, $teamId, $selectedPlayerId) {
		global $Database;

		$stmt = $Database->prepare("
			SELECT p.player_id, forename, surname
			FROM player p
				JOIN team t ON p.club_id = t.club_id
			WHERE t.team_id = ?
				-- make sure the player hasn't played in another match on the same date
				AND p.player_id NOT IN (SELECT g.home_player_id FROM game g JOIN fixture f ON g.fixture_id = f.fixture_id AND f.fixture_date = ?)
				AND p.player_id NOT IN (SELECT g.away_player_id FROM game g JOIN fixture f ON g.fixture_id = f.fixture_id AND f.fixture_date = ?)
				AND p.status = 1
			ORDER BY surname, forename");
		$dateStr = date('c', $this->date);
		$stmt->execute([$teamId, $dateStr, $dateStr]);
	?>
		<select name="<?php echo $fieldName; ?>">
		<?php
			renderSelectOption('', $selectedPlayerId, '');
			renderSelectOption(2, $selectedPlayerId, '(Default)');
			while ($row = $stmt->fetch()) {
				$player = Player::loadById($row['player_id']);
				$player->loadGrades($this->date, $this->division->section->season);

				if ($this->division->canPlayPlayer($player)) {
					renderSelectOption($player->id(), $selectedPlayerId, $player->fullNameFiling());
				/*?>
					<option value="<?php echo $player->id(); ?>"><?php echo $player->surname; ?>, <?php echo $player->forename; ?></option>
				<?php*/
				}
			}
		?>
		</select>
	<?php
	}

	protected function validateGameScore($iBoard, $homePlayer, $awayPlayer, $scoreStr, &$homeDefault, &$awayDefault) {
		if ($scoreStr == '') {
			throw new ReportableException("Missing score on board $iBoard");
		} else if (strlen($scoreStr) != 2) {
			throw new ReportableException("Invalid score on board $iBoard");
		}

		if ($scoreStr[0] == 'd') {
			$homeDefault = true;
			$scoreStr[0] = '0';
		} else if ($homePlayer->id() == PlayerId::BoardDefault) {
			throw new ReportableException("For a defaulted board, the 'd0' score must be used");
		} else {
			$homeDefault = false;
		}

		if ($scoreStr[1] == 'd') {
			$awayDefault = true;
			$scoreStr[1] = '0';
		} else if ($awayPlayer->id() == PlayerId::BoardDefault) {
			throw new ReportableException("For a defaulted board, the '0d' score must be used");
		} else {
			$awayDefault = false;
		}

		switch ($scoreStr) {
			case '10':
				++$this->homeRawScore;
				return GameResult::HomeWin;

			case '01':
				++$this->awayRawScore;
				return GameResult::AwayWin;

			case '55':
				$this->homeRawScore += 0.5;
				$this->awayRawScore += 0.5;
				return GameResult::Draw;

			case '00':
				if (!($homeDefault && $awayDefault)) {
					throw new ReportableException("Invalid score on board $iBoard");
				}
				return GameResult::DoubleDefault;
/*
								renderSelectOption('10', $score, '1 – 0');
								renderSelectOption('55', $score, '½ – ½');
								renderSelectOption('01', $score, '0 – 1');
								renderSelectOption('1d', $score, '1 – 0d');
								renderSelectOption('d1', $score, 'd0 – 1');
								renderSelectOption('dd', $score, 'd0 – 0d');*/
/*
			case '00':
				if ($homePlayer->id() == PlayerId::BoardDefault && $awayPlayer->id() == PlayerId::BoardDefault) {
					$score = GameResult::DoubleDefault;
				} else {
					throw new ReportableException("Score of 0 – 0 valid only for a double-defaulted board");
				}
				break;

			case '10':
				if ($homePlayer->id() == PlayerId::BoardDefault) {
					throw new ReportableException("A defaulted board must be a loss for the defaulting side");
				} else {
					$score = GameResult::HomeWin;
					++$this->homeRawScore;
				}
				break;

			case '01':
				if ($awayPlayer->id() == PlayerId::BoardDefault) {
					throw new ReportableException("A defaulted board must be a loss for the defaulting side");
				} else {
					$score = GameResult::AwayWin;
					++$this->awayRawScore;
				}
				break;

			case '55':
				if ($homePlayer->id() == PlayerId::BoardDefault || $awayPlayer->id() == PlayerId::BoardDefault) {
					throw new ReportableException("A defaulted board must be a loss for the defaulting side");
				} else {
					$score = GameResult::Draw;
					$this->homeRawScore += 0.5;
					$this->awayRawScore += 0.5;
				}
*/
			default:
				throw new ReportableException("Invalid score on board $iBoard");
		}
		//return $score;
	}

	protected function getPlayer($homeOrAway, $iBoard) {
		$playerId = @$_POST[$homeOrAway[0] . $iBoard];
		if (!$playerId) return null;
		if (!is_numeric($playerId)) {
			throw new ReportableException("Invalid player ID on $homeOrAway board $iBoard");
		}
		return Player::loadById($playerId); // TODO: handle exception
	}

	protected function fillInAveragesAndHandicaps($games) {
		if ($this->division->maxGrade || $this->handicapScheme) {
			// first, see if there are any defaulted boards, and if so then fill the grades in with averages
			$homeNonDefaults = $awayNonDefaults = 0;
			$homeTotalGrade = $awayTotalGrade = 0;
			$homeDefaultedGames = $awayDefaultedGames = [];

			foreach ($games as $game) {
				if ($game->homePlayer->id() == PlayerID::BoardDefault) {
					$homeDefaultedGames[] = $game;
				} else {
					++$homeNonDefaults;
					$homeTotalGrade += $game->homePlayerGrade;
				}

				if ($game->awayPlayer->id() == PlayerID::BoardDefault) {
					$awayDefaultedGames[] = $game;
				} else {
					++$awayNonDefaults;
					$awayTotalGrade += $game->awayPlayerGrade;
				}
			}

			if (count($homeDefaultedGames) > 0) {
				$averageGrade = (integer) ((1.0 * $homeTotalGrade / $homeNonDefaults) + 0.5);
				foreach ($homeDefaultedGames as $game) $game->homePlayerGrade = $averageGrade;
			}

			if (count($awayDefaultedGames) > 0) {
				$averageGrade = (integer) ((1.0 * $awayTotalGrade / $awayNonDefaults) + 0.5);
				foreach ($awayDefaultedGames as $game) $game->awayPlayerGrade = $averageGrade;
			}
		}

		if ($this->handicapScheme) {
			// fill in the grade differences and add up the handicap
			foreach ($games as $game) {
				$game->gradeDifference = $game->awayPlayerGrade - $game->homePlayerGrade;
				$this->handicapScheme->addBoard($game->gradeDifference);
			}

			$this->handicapScheme->handicapPoints($this->gradeDifference, $this->homeHandicap, $this->awayHandicap);
		}
	}

	// TEMPORARY
	protected static function formatScoreEmailWorkaround($score) {
		if (($score * 2) % 2 == 0) {
			return ' ' . $score . ' ';
		} else {
			return (string) $score;
		}
	}
}

class Game {
	public $board, $homeColour, $rawResult, $adjustedResult;
	public $homePlayer, $homePlayerID, $homePlannedPlayer, /*$homePlayerName,*/ $homePlayerGradeObj, $homePlayerGrade;
	public $awayPlayer, $awayPlayerID, $awayPlannedPlayer, /*$awayPlayerName,*/ $awayPlayerGradeObj, $awayPlayerGrade;
	public $gradeDifference;

	private function __construct() {}

	public static function fromDbRow($row) {
		$game = new Game();
		$game->board = $row['board'];
		$game->homeColour = $row['home_colour'];
		$game->rawResult = $row['raw_result'];
		$game->adjustedResult = $row['adjusted_result'];
		$game->homePlayerID = $row['home_player_id'];
		$game->homePlayer = Player::loadById($game->homePlayerID);
		$game->homePlannedPlayer = Player::loadById($row['home_planned_player_id']);
		//$game->homePlayerName = $row['home_player'];
		$game->homePlayerGrade = $row['home_grade'];
		$game->awayPlayerID = $row['away_player_id'];
		$game->awayPlayer = Player::loadById($game->awayPlayerID);
		$game->awayPlannedPlayer = Player::loadById($row['away_planned_player_id']);
		//$game->awayPlayerName = $row['away_player'];
		$game->awayPlayerGrade = $row['away_grade'];
		$game->gradeDifference = $row['grade_difference'];
		return $game;
	}

	public static function constructFromSubmission($board, $homeColour,
			$homePlayer, $homePlayerGrade, $awayPlayer, $awayPlayerGrade, $score/*, $handicapScheme*/,
			$homeDefault, $awayDefault) {
		$game = new Game();
		$game->board = $board;
		$game->homeColour = $homeColour;
		$game->rawResult = $game->adjustedResult = $score;
		$game->homePlannedPlayer = $homePlayer;
		$game->homePlayer = $homeDefault ? Player::loadById(PlayerId::BoardDefault) : $game->homePlannedPlayer;
		//$game->homePlanned
		//$game->homePlayerName = $homePlayer->fullNameFiling();
		$game->homePlayerGradeObj = $homePlayerGrade;
		if ($game->homePlayerGradeObj) $game->homePlayerGrade = $game->homePlayerGradeObj->grade;
		$game->awayPlannedPlayer = $awayPlayer;
		$game->awayPlayer = $awayDefault ? Player::loadById(PlayerId::BoardDefault) : $game->awayPlannedPlayer;
		//$game->awayPlayerName = $awayPlayer->fullNameFiling();
		$game->awayPlayerGradeObj = $awayPlayerGrade;
		if ($game->awayPlayerGradeObj) $game->awayPlayerGrade = $game->awayPlayerGradeObj->grade;
/*
		if ($game->homePlayer->id() != PlayerID::BoardDefault && $game->awayPlayer->id() != PlayerID::BoardDefault
		$game->gradeDifference = $game->awayPlayerGrade - $game->homePlayerGrade;

		if ($handicapScheme) {
			$handicapScheme->addBoard($game->gradeDifference);
		}*/

		return $game;
	}
}

class StandardMatch extends Match {
	public function renderSubmissionForm() {
	?>
		<table class="resultSubmit sp">
			<col class="board"/><col class="colour"/><col class="name"/>
			<col class="score"/><col class="name"/>
			<thead>
				<tr>
					<th colspan="2"><?php echo formatDate($this->date, false); ?></th>
					<th><?php echo $this->homeTeamName; ?></th>
					<th class="score">v</th>
					<th><?php echo $this->awayTeamName; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php for ($iBoard = 1; $iBoard <= $this->division->maxBoards; ++$iBoard) { ?>
					<tr>
						<td class="board"><?php echo $iBoard; ?></td>
						<td class="colour"><?php
							switch ($this->division->colours) {
								case Colours::HomeWhiteOnOdds:
									echo $iBoard % 2 == 0 ? 'B' : 'W';
									break;

								case Colours::HomeBlackOnOdds:
									echo $iBoard % 2 == 0 ? 'W' : 'B';
									break;

								case Colours::DecidePerMatch:
									if ($iBoard == 1) {
									?>	<select name="col"><?php
											$colours = @$_POST['col'];
											renderSelectOption('', $colours, '');
											renderSelectOption('W', $colours, 'W');
											renderSelectOption('B', $colours, 'B');
										?></select>
									<?php
									}
							}
						?></td>
						<td class="name"><?php $this->renderPlayerSelection('h' . $iBoard, $this->homeTeamID, @$_POST['h' . $iBoard]); ?></td>
						<td class="score">
							<select name="s<?php echo $iBoard; ?>"><?php
								$score = @$_POST['s' . $iBoard];
								renderSelectOption('', $score, '');
								renderSelectOption('10', $score, '1 – 0');
								renderSelectOption('55', $score, '½ – ½');
								renderSelectOption('01', $score, '0 – 1');
								renderSelectOption('1d', $score, '1 – 0d');
								renderSelectOption('d1', $score, 'd0 – 1');
								renderSelectOption('dd', $score, 'd0 – 0d');
							?></select>
						</td>
						<td class="name"><?php $this->renderPlayerSelection('a' . $iBoard, $this->awayTeamID, @$_POST['a' . $iBoard]); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php
	}

	public function buildSubmission() {
		$homeTeam = Team::loadById($this->homeTeamID);
		$awayTeam = Team::loadById($this->awayTeamID);
		$this->homeRawScore = $this->awayRawScore = 0.0;

		// first, see how many boards we have actually used
		for ($iBoard = $this->division->maxBoards; $iBoard > $this->division->minBoards; --$iBoard) {
			if (@$_POST['h' . $iBoard] || @$_POST['a' . $iBoard] || @$_POST['s' . $iBoard]) break;
		}
		$nBoards = $iBoard;
		$playersById = [];

		$colours = $this->division->colours;
		if ($colours == Colours::DecidePerMatch) {
			switch (@$_POST['col']) {
				case 'W': $colours = Colours::HomeWhiteOnOdds; break;
				case 'B': $colours = Colours::HomeBlackOnOdds; break;
				default: throw new ReportableException('Please select what colours were played');
			}
		}

		$homeDefaultPlayerSelected = $awayDefaultPlayerSelected = false;
		
		// now go through them
		for ($iBoard = 1; $iBoard <= $nBoards; ++$iBoard) {

			// validate home player
			$homePlayer = $this->getPlayer('home', $iBoard);
			if (!$homePlayer) {
				throw new ReportableException("Missing home player on board $iBoard");
			}
			if ($homePlayer->id() != PlayerId::BoardDefault && $homePlayer->club->id() != $homeTeam->club->id()) {
				throw new ReportableException("Home player on board $iBoard does not play for this club");
			}
			if ($homePlayer->id() != PlayerId::BoardDefault && isset($playersById[$homePlayer->id()])) {
				throw new ReportableException("Duplicate home player on board $iBoard");
			}
			$homePlayer->loadGrades($this->date, $this->division->section->season);
			if (!$this->division->canPlayPlayer($homePlayer)) {
				throw new ReportableException("Home player on board $iBoard is not eligible to play in this match");
			}

			$playersById[$homePlayer->id()] = $homePlayer;

			// validate away player
			$awayPlayer = $this->getPlayer('away', $iBoard);
			if (!$awayPlayer) {
				throw new ReportableException("Missing away player on board $iBoard");
			}
			if ($awayPlayer->id() != PlayerId::BoardDefault && $awayPlayer->club->id() != $awayTeam->club->id()) {
				throw new ReportableException("Away player on board $iBoard does not play for this club");
			}
			if ($awayPlayer->id() != PlayerId::BoardDefault && isset($playersById[$awayPlayer->id()])) {
				throw new ReportableException("Duplicate away player on board $iBoard");
			}
			$awayPlayer->loadGrades($this->date, $this->division->section->season);
			if (!$this->division->canPlayPlayer($awayPlayer)) {
				throw new ReportableException("Away player on board $iBoard is not eligible to play in this match");
			}

			$playersById[$awayPlayer->id()] = $awayPlayer;

			// validate score
			$homeDefault = $awayDefault = false;
			$score = $this->validateGameScore($iBoard, $homePlayer, $awayPlayer, $_POST['s' . $iBoard], $homeDefault, $awayDefault);

			if ($homePlayer->id() == PlayerId::BoardDefault) {
				$homeDefaultPlayerSelected = true;
			} else if ($homeDefaultPlayerSelected) {
				throw new ReportableException('(Default) option valid only on bottom board(s).  If the player was a '
					. "no-show, please select that player's name and enter a 'd0' score.");
			}

			if ($awayPlayer->id() == PlayerId::BoardDefault) {
				$awayDefaultPlayerSelected = true;
			} else if ($awayDefaultPlayerSelected) {
				throw new ReportableException('(Default) option valid only on bottom board(s).  If the player was a '
					. "no-show, please select that player's name and enter a '0d' score.");
			}
			
			/*$scoreStr = @$_POST['s' . $iBoard];
			switch ($scoreStr) {
				case '':
					throw new ReportableException("Missing score on board $iBoard");

				case '00':
					if ($homePlayer->id() == PlayerId::BoardDefault && $awayPlayer->id() == PlayerId::BoardDefault) {
						$score = GameResult::DoubleDefault;
					} else {
						throw new ReportableException("Score of 0 – 0 valid only for a double-defaulted board");
					}
					break;

				case '10':
					if ($homePlayer->id() == PlayerId::BoardDefault) {
						throw new ReportableException("A defaulted board must be a loss for the defaulting side");
					} else {
						$score = GameResult::HomeWin;
						++$this->homeRawScore;
					}
					break;

				case '01':
					if ($awayPlayer->id() == PlayerId::BoardDefault) {
						throw new ReportableException("A defaulted board must be a loss for the defaulting side");
					} else {
						$score = GameResult::AwayWin;
						++$this->awayRawScore;
					}
					break;

				case '55':
					if ($homePlayer->id() == PlayerId::BoardDefault || $awayPlayer->id() == PlayerId::BoardDefault) {
						throw new ReportableException("A defaulted board must be a loss for the defaulting side");
					} else {
						$score = GameResult::Draw;
						$this->homeRawScore += 0.5;
						$this->awayRawScore += 0.5;
					}
			}*/
			switch ($colours) {
				case Colours::HomeWhiteOnOdds:
					$gameColour = $iBoard % 2 == 0 ? 'B' : 'W';
					break;

				case Colours::HomeBlackOnOdds:
					$gameColour = $iBoard % 2 == 0 ? 'W' : 'B';
					break;

				default:
					throw new Exception('Bad value of colours field');
			}

			$this->games[] = Game::constructFromSubmission($iBoard, $gameColour /*$iBoard % 2 == 0 ? 'W' : 'B'*/,
				$homePlayer, $homePlayer->standardGrade, $awayPlayer, $awayPlayer->standardGrade, $score/*,
				$this->handicapScheme*/, $homeDefault, $awayDefault);
		}

		$this->fillInAveragesAndHandicaps($this->games);
		//$this->fillInHandicaps();

		$this->homeAdjustedScore = $this->homeRawScore + $this->homeHandicap;
		$this->awayAdjustedScore = $this->awayRawScore + $this->awayHandicap;
		$this->comments = @$_POST['comments'];
	}

	// for a handicapped competition, fill in any missing grades, and populate the match-level handicap fields
	/*private function fillInHandicaps() {
		if (!$this->handicapScheme) return;

		$homeNonDefaults = $awayNonDefaults = 0;
		$homeTotalGrade = $awayTotalGrade = 0;
		$homeDefaultedGames = $awayDefaultedGames = [];

		foreach ($this->games as $game) {
			if ($game->homePlayer->id() == PlayerID::BoardDefault) {
				$homeDefaultedGames[] = $game;
			} else {
				++$homeNonDefaults;
				$homeTotalGrade += $game->homePlayerGrade;
			}

			if ($game->awayPlayer->id() == PlayerID::BoardDefault) {
				$awayDefaultedGames[] = $game;
			} else {
				++$awayNonDefaults;
				$awayTotalGrade += $game->awayPlayerGrade;
			}
		}

		if (count($homeDefaultedGames) > 0) {
			$averageGrade = (integer) ((1.0 * $homeTotalGrade / $homeNonDefaults) + 0.5);
			foreach ($homeDefaultedGames as $game) $game->homePlayerGrade = $averageGrade;
		}

		if (count($awayDefaultedGames) > 0) {
			$averageGrade = (integer) ((1.0 * $awayTotalGrade / $awayNonDefaults) + 0.5);
			foreach ($awayDefaultedGames as $game) $game->awayPlayerGrade = $averageGrade;
		}

		foreach ($this->games as $game) {
			$game->gradeDifference = $game->awayPlayerGrade - $game->homePlayerGrade;
			$this->handicapScheme->addBoard($game->gradeDifference);
		}

		$this->handicapScheme->handicapPoints($this->gradeDifference, $this->homeHandicap, $this->awayHandicap);
	}*/
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
						<td class="name"><?php echo $wbGame->homePlayer->fullNameFiling(); ?></td>
						<?php formatGameResult($wbGame->adjustedResult,
							$game->homePlayer->id() == PlayerId::BoardDefault,
							$game->awayPlayer->id() == PlayerId::BoardDefault); ?>
						<td></td>
						<?php formatGameResult($bwGame->adjustedResult,
							$game->homePlayer->id() == PlayerId::BoardDefault,
							$game->awayPlayer->id() == PlayerId::BoardDefault); ?>
						<td class="name"><?php echo $wbGame->awayPlayer->fullNameFiling(); ?></td>
						<td class="grade"><?php echo $wbGame->awayPlayerGrade; ?></td>
					</tr>
				<?php
				}
			?></tbody>
		</table>
	<?php
	}

	public function renderSubmissionForm() {
	?>
		<table class="resultSubmit rp">
			<col class="board"/><col class="name"/>
			<col class="score"/><col class="score"/><col class="name"/>
			<thead>
				<tr>
					<th><?php echo formatDate($this->date, false); ?></th>
					<th><?php echo $this->homeTeamName; ?></th>
					<th class="score">W-B</th>
					<th class="score">B-W</th>
					<th><?php echo $this->awayTeamName; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php for ($iBoard = 1; $iBoard <= $this->division->maxBoards; ++$iBoard) { ?>
					<tr>
						<td class="board"><?php echo $iBoard; ?></td>
						<td class="name"><?php $this->renderPlayerSelection('h' . $iBoard, $this->homeTeamID, @$_POST['h' . $iBoard]); ?></td>
						<td class="score">
							<select name="s<?php echo $iBoard; ?>wb"><?php
								$score = @$_POST['s' . $iBoard . 'wb'];
								renderSelectOption('', $score, '');
								renderSelectOption('10', $score, '1 – 0');
								renderSelectOption('55', $score, '½ – ½');
								renderSelectOption('01', $score, '0 – 1');
								renderSelectOption('1d', $score, '1 – 0d');
								renderSelectOption('d1', $score, 'd0 – 1');
								renderSelectOption('dd', $score, 'd0 – 0d');
							?></select>
						</td>
						<td class="score">
							<select name="s<?php echo $iBoard; ?>bw"><?php
								$score = @$_POST['s' . $iBoard . 'bw'];
								renderSelectOption('', $score, '');
								renderSelectOption('10', $score, '1 – 0');
								renderSelectOption('55', $score, '½ – ½');
								renderSelectOption('01', $score, '0 – 1');
								renderSelectOption('1d', $score, '1 – 0d');
								renderSelectOption('d1', $score, 'd0 – 1');
								renderSelectOption('dd', $score, 'd0 – 0d');
							?></select>
						</td>
						<td class="name"><?php $this->renderPlayerSelection('a' . $iBoard, $this->awayTeamID, @$_POST['a' . $iBoard]); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php
	}

	public function buildSubmission() {
		$homeTeam = Team::loadById($this->homeTeamID);
		$awayTeam = Team::loadById($this->awayTeamID);
		$this->homeRawScore = $this->awayRawScore = 0.0;

		// first, see how many boards we have actually used
		for ($iBoard = $this->division->maxBoards; $iBoard > $this->division->minBoards; --$iBoard) {
			if (@$_POST['h' . $iBoard] || @$_POST['a' . $iBoard]
				|| @$_POST['s' . $iBoard . 'wb'] || @$_POST['s' . $iBoard . 'bw']) break;
		}
		$nBoards = $iBoard;
		$playersById = [];
		$wbGames = []; // here we will build a list of the W-B games, for filling in averages and handicaps

		// now go through them
		for ($iBoard = 1; $iBoard <= $nBoards; ++$iBoard) {

			// validate home player
			$homePlayer = $this->getPlayer('home', $iBoard);
			if (!$homePlayer) {
				throw new ReportableException("Missing home player on board $iBoard");
			}
			if ($homePlayer->id() != PlayerId::BoardDefault && $homePlayer->club->id() != $homeTeam->club->id()) {
				throw new ReportableException("Home player on board $iBoard does not play for this club");
			}
			if ($homePlayer->id() != PlayerId::BoardDefault && isset($playersById[$homePlayer->id()])) {
				throw new ReportableException("Duplicate home player on board $iBoard");
			}
			$homePlayer->loadGrades($this->date, $this->division->section->season);
			if (!$this->division->canPlayPlayer($homePlayer)) {
				throw new ReportableException("Home player on board $iBoard is not eligible to play in this match");
			}

			$playersById[$homePlayer->id()] = $homePlayer;

			// validate away player
			$awayPlayer = $this->getPlayer('away', $iBoard);
			if (!$awayPlayer) {
				throw new ReportableException("Missing away player on board $iBoard");
			}
			if ($awayPlayer->id() != PlayerId::BoardDefault && $awayPlayer->club->id() != $awayTeam->club->id()) {
				throw new ReportableException("Away player on board $iBoard does not play for this club");
			}
			if ($awayPlayer->id() != PlayerId::BoardDefault && isset($playersById[$awayPlayer->id()])) {
				throw new ReportableException("Duplicate away player on board $iBoard");
			}
			$awayPlayer->loadGrades($this->date, $this->division->section->season);
			if (!$this->division->canPlayPlayer($awayPlayer)) {
				throw new ReportableException("Away player on board $iBoard is not eligible to play in this match");
			}

			$playersById[$awayPlayer->id()] = $awayPlayer;

			$homeDefault = $awayDefault = false;
			$score = $this->validateGameScore($iBoard, $homePlayer, $awayPlayer, $_POST['s' . $iBoard . 'wb'], $homeDefault, $awayDefault);
			$wbGames[] = $this->games[] = Game::constructFromSubmission($iBoard, 'W',
				$homePlayer, $homePlayer->lrcaRapidGrade, $awayPlayer, $awayPlayer->lrcaRapidGrade, $score/*,
				$handicapScheme*/, $homeDefault, $awayDefault);

			$homeDefault = $awayDefault = false;
			$score = $this->validateGameScore($iBoard, $homePlayer, $awayPlayer, $_POST['s' . $iBoard . 'bw'], $homeDefault, $awayDefault);
			$this->games[] = Game::constructFromSubmission($iBoard, 'B',
				$homePlayer, $homePlayer->lrcaRapidGrade, $awayPlayer, $awayPlayer->lrcaRapidGrade, $score/*,
				$handicapScheme*/, $homeDefault, $awayDefault);

			// validate score
			/*$scoreStr = @$_POST['s' . $iBoard];
			switch ($scoreStr) {
				case '':
					throw new ReportableException("Missing score on board $iBoard");

				case '00':
					if ($homePlayer->id() == PlayerId::BoardDefault && $awayPlayer->id() == PlayerId::BoardDefault) {
						$score = GameResult::DoubleDefault;
					} else {
						throw new ReportableException("Score of 0 – 0 valid only for a double-defaulted board");
					}
					break;

				case '10':
					if ($homePlayer->id() == PlayerId::BoardDefault) {
						throw new ReportableException("A defaulted board must be a loss for the defaulting side");
					} else {
						$score = GameResult::HomeWin;
						++$this->homeRawScore;
					}
					break;

				case '01':
					if ($awayPlayer->id() == PlayerId::BoardDefault) {
						throw new ReportableException("A defaulted board must be a loss for the defaulting side");
					} else {
						$score = GameResult::AwayWin;
						++$this->awayRawScore;
					}
					break;

				case '55':
					if ($homePlayer->id() == PlayerId::BoardDefault || $awayPlayer->id() == PlayerId::BoardDefault) {
						throw new ReportableException("A defaulted board must be a loss for the defaulting side");
					} else {
						$score = GameResult::Draw;
						$this->homeRawScore += 0.5;
						$this->awayRawScore += 0.5;
					}
			}
			$this->games[] = Game::constructFromSubmission($iBoard, $iBoard % 2 == 0 ? 'W' : 'B',
				$homePlayer, $homePlayer->lrcaRapidGrade, $awayPlayer, $awayPlayer->lrcaRapidGrade, $score);*/
		}
		//$this->fillInHandicaps();
		$this->fillInAveragesAndHandicaps($wbGames);

		$this->homeAdjustedScore = $this->homeRawScore + $this->homeHandicap;
		$this->awayAdjustedScore = $this->awayRawScore + $this->awayHandicap;
		$this->comments = @$_POST['comments'];
	}

	public function renderPlainTextResult() {
		/* Sample rendering:
		01 May    Ashby 1                   W - B   B - W  Loughborough 1
		1     123 Armstrong, Victor         1 – 0   1 – 0  Glover, John              130
		2     185 Agnew, Alan               ½ – ½   0 – 1  Miller, James             158
		3     127 Hayden, Lawrence          1 – 0   0 – 1  Adcock, Terry             127
		4     125 Reynolds, David           1 – 0   1 – 0  Northage, Robert          152
		                                       2½ – 1½
		*/
		// header line: date and teams
		$result = formatDate($this->date, false) . '    ' . padRight($this->homeTeamName, 25) . ' W - B   B - W  ' . $this->awayTeamName;

		// body: game results
		foreach ($this->games as $game) {
			if ($game->homeColour == 'W') {
				$result .= "\n$game->board "
					//. str_pad($game->homePlayerGrade, 7, ' ', STR_PAD_LEFT) . ' ' . str_pad($game->homePlayerName, 26);
					. '   ' . padLeft($game->homePlayerGrade, 4) . ' ' . padRight($game->homePlayer->fullNameFiling(), 25);
			}

			switch ($game->adjustedResult) {
				case GameResult::HomeWin:       $result .= ' 1 - 0  '; break;
				// TEMPORARY
				case GameResult::Draw:          $result .= '0.5-0.5 '; break;
				//case GameResult::Draw:          $result .= ' ½ - ½  '; break;
				case GameResult::AwayWin:       $result .= ' 0 - 1  '; break;
				case GameResult::DoubleDefault: $result .= ' 0 - 0  ';
			}

			if ($game->homeColour == 'B') {
				$result .= padRight($game->awayPlayer->fullNameFiling(), 25) . padLeft($game->awayPlayerGrade, 4);
			}
		}

		// footer line: total score
		// TEMPORARY
		$result .= "\n" . padLeft(self::formatScoreEmailWorkaround($this->homeAdjustedScore), 42)
			. '-' . self::formatScoreEmailWorkaround($this->awayAdjustedScore);
		//$result .= "\n" . padLeft(formatScore($this->homeAdjustedScore), 38)
		//	. ' - ' . formatScore($this->awayAdjustedScore);

		return $result;
	}
}

class RapidDifferentMatch extends Match {
	public function renderSubmissionForm() {
		throw new Exception('rapidplay-different match submission not yet implemented');
	}
	public function buildSubmission() {
		throw new Exception('rapidplay-different match submission not yet implemented');
	}
}
?>