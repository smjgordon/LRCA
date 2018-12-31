<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'u_id_wrapper.php';

class Team {
	// TODO: write this once Division is ready (and I've found a use case :) )
	/*public static function loadByDivisionAndName($division, $name) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT * FROM team
			WHERE name = ?');
		$stmt->execute([$name]);

		if ($row = $stmt->fetch()) {
			return new Team($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadTeamName, $name);
		}
	}*/

	static public function loadByUri($uri) {
		global $Database;

		$uriParts = array_slice(explode('/', trim($uri, '/')), -6);
		if (count($uriParts) != 6) throw new ModelAccessException(ModelAccessException::BadUrl, $uri);
		$stmt = $Database->prepare('
			SELECT t.* -- , f.status AS any_played [DISTINCT]
			FROM team t
				JOIN club c ON t.club_id = c.club_id
				-- LEFT JOIN fixture f ON r.round_id = f.round_id AND f.status = 1
				JOIN division d ON t.division_id = d.division_id
				JOIN section s ON d.section_id = s.section_id
			WHERE d.year = ? AND s.url_name = ? AND d.url_name = ? AND c.url_name = ? AND t.sequence = ?');
		$stmt->execute([$uriParts[0], $uriParts[1], $uriParts[2], $uriParts[4], $uriParts[5]]);
		if ($row = $stmt->fetch()) {
			$id = (int) $row['team_id'];
			/*if (isset(Team::$instanceCache[$id])) {
				return Team::$instanceCache[$id];
			} else {*/
				$result = new Team();
				$result->populateFromDbRow($row);
				//$result->_year = (int) $uriParts[0];
				//Round::$instanceCache[$id] = $result;
				return $result;
			//}
		} else {
			throw new ModelAccessException(ModelAccessException::BadTeamUrlName, $uri);
		}
	}

	public static function loadById($id) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT * FROM team
			WHERE team_id = ?');
		$stmt->execute([$id]);

		if ($row = $stmt->fetch()) {
			$result = new Team();
			$result->populateFromDbRow($row);
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadTeamId, $id);
		}
	}

	// TODO: come up with a more generic way of doing this
	public static function rankingOrder($team1, $team2) {
		if ($team1->adjustedPoints > $team2->adjustedPoints) return -1;
		if ($team1->adjustedPoints < $team2->adjustedPoints) return 1;
		if ($team1->tieBreak < $team2->tieBreak) return -1;
		if ($team1->tieBreak > $team2->tieBreak) return 1;
		if ($team1->gpd > $team2->gpd) return -1;
		if ($team1->gpd < $team2->gpd) return 1;
		return 0;
	}

	public function __construct() {
		$this->status = TeamStatus::Active;
	}

	public function id() { return $this->_id; }
	public function division() { return $this->_division; }
	public function club() {
		// TODO: refactor
		if ($this->_club instanceof IdWrapper) $this->_club = Club::loadById($this->_club->id());
		return $this->_club;
	}
	public function sequence() { return $this->_sequence; }
	public function name() { return $this->_name; }
	public function setDivision($division) { $this->_division = $division; }
	public function setClub($club) { $this->_club = $club; }
	public function setSequence($sequence) { $this->_sequence = $sequence; }
	public function setName($name) { $this->_name = $name; }

	public $status, $played, $won, $drawn, $lost, $gpd;
	public $rawPoints, $adjustedPoints, $tieBreak;

	private function populateFromDbRow($row) {
		$this->_id = $row['team_id'];
		//$this->division = new IdWrapper($row['division_id']);
		$this->_division = Division::loadById($row['division_id']);
		$this->_club = new IdWrapper($row['club_id']);
		$this->_sequence = $row['sequence'];
		$this->_name = $row['name'];
		$this->status = $row['status'];
		$this->played = $row['played'];
		$this->won = $row['won'];
		$this->drawn = $row['drawn'];
		$this->lost = $row['lost'];
		$this->gpd = $row['gpd'];
		$this->rawPoints = $row['raw_points'];
		$this->adjustedPoints = $row['adjusted_points'];
		$this->tieBreak = $row['tie_break'];
	}

	private $_id, $_division, $_club, $_sequence, $_name;

	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO team(division_id, club_id, sequence, name)
				VALUES(?, ?, ?, ?)');
			$stmt->execute([$this->_division->id(), $this->_club->id(), $this->_sequence, $this->_name]);
			$this->_id = $Database->lastInsertId();

		} else if (!$silentFail) {
			throw new Exception('Saving to an existing team not implemented');
		}
	}

	public function recursiveSave() {
		$this->save();
	}

	// DEBUG
	public function dump() {
		echo "<p>Team ID: $this->_id; Club ID: ", $this->_club->id(), "; Name: $this->_name; ",
			"Sequence: $this->_sequence; Status: $this->status</p>";
	}

	public function recursiveDump() {
		$this->dump();
	}
}

abstract class TeamStatus {
	const WithdrawnDeleted = 0;
	const Active = 1;
	const Eliminated = 2;
	const WithdrawnDefaulted = 3;
}
?>