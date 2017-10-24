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

	public $division, $club, $sequence, $name, $status, $played, $won, $drawn, $lost, $gpd;
	public $rawPoints, $adjustedPoints, $tieBreak;

	private function populateFromDbRow($row) {
		$this->_id = $row['team_id'];
		//$this->division = new IdWrapper($row['division_id']);
		$this->division = Division::loadById($row['division_id']);
		$this->club = new IdWrapper($row['club_id']);
		$this->sequence = $row['sequence'];
		$this->name = $row['name'];
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

	private $_id;

	public function save($silentFail = false) {
		global $Database;
		
		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO team(division_id, club_id, sequence, name)
				VALUES(?, ?, ?, ?)');
			$stmt->execute([$this->division->id(), $this->club->id(), $this->sequence, $this->name]);
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
		echo "<p>Team ID: $this->_id; Club ID: ", $this->club->id(), "; Name: $this->name; ",
			"Sequence: $this->sequence; Status: $this->status</p>";
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