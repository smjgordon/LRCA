<?php
require_once 'p_server.php';
//require_once 'p_section.php';
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
				Division::$instanceCache[$id] = $result;
				return $result;
			} else {
				throw new ModelAccessException(ModelAccessException::BadDivisionId, $id);
			}
		}
	}

	static public function loadByUri($uri) {
		global $Database;

		$uriParts = array_slice(explode('/', trim($uri, '/')), -3);
		if (count($uriParts) != 3) throw new ModelAccessException(ModelAccessException::BadUrl, $uri);
		$stmt = $Database->prepare('
			SELECT d.*
			FROM division d
				JOIN section s ON d.section_id = s.section_id
			WHERE d.year = ? AND s.url_name = ? AND d.url_name = ?');
		$stmt->execute($uriParts);
		if ($row = $stmt->fetch()) {
			$id = (int) $row['division_id'];
			if (isset(Division::$instanceCache[$id])) {
				return Division::$instanceCache[$id];
			} else {
				$result = new Division();
				$result->populateFromDbRow($row);
				//$result->_year = (int) $uriParts[0];
				Division::$instanceCache[$id] = $result;
				return $result;
			}
		} else {
			throw new ModelAccessException(ModelAccessException::BadSectionUrlName, $uriParts[1]);
		}
	}

	public static function loadBySection($section) {
		global $Database;

		$result = [];

		$stmt = $Database->prepare('
			SELECT * FROM division
			WHERE section_id = ? AND year = ?');
		$stmt->execute([$section->id(), $section->year()]);

		while (!!($row = $stmt->fetch())) {
			$divisionId = (int) $row['division_id'];
			if (isset(Division::$instanceCache[$divisionId])) {
				$result[] = Division::$instanceCache[$divisionId];
			} else {
				$div = new Division();
				$div->populateFromDbRow($row);
				Division::$instanceCache[$div->_id] = $div;
				$result[] = $div;
			}
		}
		return $result;
	}

	public function id() { return $this->_id; }
	public function section() { return $this->_section; }
	public function name() { return $this->_name; }
	public function urlName() { return $this->_urlName; }
	public function setSection($section) { $this->_section = $section; }
	public function setName($name) { $this->_name = $name; }
	public function setUrlName($name) { $this->_urlName = $name; }

	private $_id, $_section, $_name, $_urlName, $_teamsLoaded, $_roundsLoaded;

	public $matchStyle, $breakdown, $sequence, $format, $requireGrade, $maxGrade;
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
			if ($round->anyPlayed()) $result[] = $round;
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
		$this->_section = Section::loadByYearAndId($row['year'], $row['section_id']);
		$this->_name = $row['name'];
		$this->_urlName = $row['url_name'];
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

	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO division(year, section_id, url_name, name,
					match_style, breakdown, sequence, format, require_grade)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$this->_section->year(), $this->_section->id(), $this->_urlName,
				$this->_name, $this->matchStyle, $this->breakdown, $this->sequence, $this->format, $this->requireGrade]);
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
		echo "<p>Division ID: $this->_id; Section: ", $this->_section->displayName(), "; Name: $this->_name; ",
			"URL name: $this->_urlName; Match style: $this->matchStyle; ",
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