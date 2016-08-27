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
			$result->loadFromDbRow($row);
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadTeamId, $id);
		}
	}

	public function __construct() {
		$this->status = TeamStatus::Active;
	}

	public function id() { return $this->_id; }

	public $division, $club, $sequence, $name, $status;

	private function loadFromDbRow($row) {
		$this->_id = $row['team_id'];
		$this->division = new IdWrapper($row['division_id']);
		$this->club = new IdWrapper($row['club_id']);
		$this->sequence = $row['sequence'];
		$this->name = $row['name'];
		$this->status = $row['status'];
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
	const Withdrawn = 0;
	const Active = 1;
	const Eliminated = 2;
}
?>