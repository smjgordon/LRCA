<?php
require_once 'p_server.php';
require_once 'p_section.php';
require_once 'm_team.php';
require_once 'm_round.php';
require_once 'p_exceptions.php';
require_once 'p_enumerations.php'; // TODO: do away with this?

class Division {
	private static $instanceCache = [];

	public static function loadById($id) {
		global $Database;

		if (isset(Division::$instanceCache[$id])) {
			return Division::$instanceCache[$id];
		} else {
			$stmt = $Database->prepare('
				SELECT * FROM division
				WHERE division_id = ?');
			$stmt->execute([$id]);

			if ($row = $stmt->fetch()) {
				$result = new Division();
				$result->populateFromDbRow($row);
				return $result;
			} else {
				throw new ModelAccessException(ModelAccessException::BadDivisionId, $id);
			}
		}
	}

	public function id() { return $this->_id; }

	public $section, $name, $urlName, $matchStyle, $breakdown, $sequence, $format, $requireGrade, $maxGrade;
	public $minBoards, $maxBoards, $colours, $boardDefaultPenaltyFirst, $boardDefaultPenaltyEvery;
	public $teams, $rounds;

	public function canPlayPlayer($player) {

		if ($player->status != PlayerStatus::Active) return false;
		if (!$this->requireGrade) return true;
		if ($player->id() == PlayerId::BoardDefault) return true;

		switch ($this->matchStyle) {
			case MatchStyle::Standard:
				$grade = $player->standardGrade;
				break;

			case MatchStyle::RapidSame: case MatchStyle::RapidDifferent:
				$grade = $player->lrcaRapidGrade;
		}
		// if the grade object has a non-zero grade value, we are OK
		return !!($grade->grade);
	}

	public function playedMatchMonths() {
		global $Database;

		$stmt = $Database->prepare('
			SELECT DISTINCT Substring(f.fixture_date, 1, 7) AS month -- yyyy-mm-dd -> yyyy-mm
			FROM round r
				JOIN fixture f ON r.round_id = f.round_id
			WHERE r.division_id = ? AND f.status = 1
			ORDER BY 1');
		$stmt->execute([$this->_id]);

		$result = [];

		while ($row = $stmt->fetch()) {
			$result[] = $row['month'];
		}
		return $result;
	}

	public function playedMatchRounds() {
		$this->loadRounds();

		$result = [];
		foreach ($this->rounds as $round) {
			if ($round->anyPlayed) $result[] = $round;
		}
		return $result;
	}

	public function loadTeams() {
		global $Database;

		if (!$this->_teamsLoaded) {
			$this->teams = [];
			$stmt = $Database->prepare('
				SELECT team_id
				FROM team
				WHERE division_id = ?
				ORDER BY name');
			$stmt->execute([$this->_id]);
			while ($row = $stmt->fetch()) $this->teams[] = Team::loadById($row['team_id']);

			$this->_teamsLoaded = true;
		}
	}

	public function rankedTeams() {
		$this->loadTeams();
		$result = $this->teams;
		usort($result, 'Team::rankingOrder');
		return $result;
	}

	public function loadRounds() {
		global $Database;

		if (!$this->_roundsLoaded) {
			$this->rounds = [];
			$stmt = $Database->prepare('
				SELECT round_id
				FROM round
				WHERE division_id = ?
				ORDER BY sequence');
			$stmt->execute([$this->_id]);
			while ($row = $stmt->fetch()) $this->rounds[] = Round::loadById($row['round_id']);

			$this->_roundsLoaded = true;
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = $row['division_id'];

		// TODO: decide what to do with this
		$this->section = new Section($row['section_id'], $row['year']);

		$this->name = $row['name'];
		$this->urlName = $row['url_name'];
		$this->matchStyle = $row['match_style'];
		$this->breakdown = $row['breakdown'];
		$this->sequence = $row['sequence'];
		$this->format = $row['format'];
		$this->requireGrade = !!$row['require_grade'];
		$this->maxGrade = $row['max_grade'];
		$this->minBoards = $row['min_boards'];
		$this->maxBoards = $row['max_boards'];
		$this->colours = $row['colours'];
		$this->boardDefaultPenaltyFirst = $row['board_default_penalty_first'];
		$this->boardDefaultPenaltyEvery = $row['board_default_penalty_every'];
	}

	private $_id, $_teamsLoaded, $_roundsLoaded;

	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO division(year, section_id, url_name, name,
					match_style, breakdown, sequence, format, require_grade)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$this->section->year, $this->section->id, $this->urlName,
				$this->name, $this->matchStyle, $this->breakdown, $this->sequence, $this->format, $this->requireGrade]);
			$this->_id = $Database->lastInsertId();

		} else if (!$silentFail) {
			throw new Exception('Saving to an existing division not implemented');
		}
	}

	public function recursiveSave() {
		$this->save();
		foreach ($this->teams as $team) $team->recursiveSave(true);
		foreach ($this->rounds as $round) $round->recursiveSave(true);
	}

	// DEBUG
	public function dump() {
		echo "<p>Division ID: $this->_id; Section: ", $this->section->displayName(), "; Name: $this->name; ",
			"URL name: $this->urlName; Match style: $this->matchStyle; ",
			"Breakdown: $this->breakdown; Format: $this->format; Require grade: ", (integer) $this->requireGrade, '</p>';
	}

	public function recursiveDump() {
		$this->dump();
		foreach ($this->teams as $team) $team->recursiveDump();
		foreach ($this->rounds as $round) $round->recursiveDump();
	}
}
/* TODO: move back here
abstract class DivisionFormat {
	const RoundRobinSingle = 1;
	const RoundRobinDouble = 2;
	const Swiss = 11;
	const Knockout = 21;
}

abstract class Breakdown {
	const ByMonth = 1;
	const ByRound = 2;
}*/
?>