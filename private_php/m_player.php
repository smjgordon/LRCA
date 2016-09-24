<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'm_club.php';
require_once 'm_grade.php';

class Player {
	/*public static function loadByName($name) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT *
			FROM club
			WHERE name = ?');
		$stmt->execute([$name]);
		
		if ($row = $stmt->fetch()) {
			return new Club($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadClubName, $name);
		}
	}*/
	
	public static function loadAll() {
		global $Database;
		
		$result = [];
		
		$stmt = $Database->query('SELECT * FROM player WHERE club_id > 0');
		while ($row = $stmt->fetch()) {
			$result[] = new Player($row);
		}
		return $result;
	}
	
	public static function loadById($id) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT * FROM player
			WHERE player_id = ?');
		$stmt->execute([$id]);
		
		if ($row = $stmt->fetch()) {
			return new Player($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadPlayerId, $id);
		}
	}
	
	public static function loadByEcfCode($code) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT * FROM player
			WHERE ecf_grading_code = ?');
		$stmt->execute([$ecf]);
		
		if ($row = $stmt->fetch()) {
			return new Player($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadPlayerEcfCode, $id);
		}
	}
	
	public function loadGrades($date, $season) {
		$this->standardGrade = Grade::loadByPlayer($this, $date, $season, GradeType::Standard)
			and $this->standardGrade->player = $this;
		$this->rapidGrade = Grade::loadByPlayer($this, $date, $season, GradeType::Rapid)
			and $this->rapidGrade->player = $this;
		$this->lrcaRapidGrade = Grade::loadByPlayer($this, $date, $season, GradeType::LrcaRapid)
			and $this->lrcaRapidGrade->player = $this;
	}
	
	public function saveGrades() {
		$this->standardGrade->save();
		$this->rapidGrade->save();
		$this->lrcaRapidGrade->save();
	}
	
	private function __construct($row) {
		$this->_id = $row['player_id'];
		// OPTIMISE: get index of clubs first
		$this->club = $row['club_id'] > 0 ? Club::loadById($row['club_id']) : null;
		$this->forename = $row['forename'];
		$this->surname = $row['surname'];
		$this->ecfGradingCode = $row['ecf_grading_code'];
		$this->status = $row['status'];
	}
	
	public function id() { return $this->_id; }
	public $club, $forename, $surname, $ecfGradingCode, $status, $standardGrade, $rapidGrade, $lrcaRapidGrade;
	private $_id;

	public function fullName() {
		return $this->forename . ($this->forename && $this->surname ? ' ' : '') . $this->surname;
	}
	
	public function fullNameFiling() {
		return $this->surname . ($this->forename && $this->surname ? ', ' : '') . $this->forename;
	}
	
	// DEBUG
	public function dump() {
		echo "<p>Player ID: $this->_id; ECF code: $this->ecfGradingCode; Name: $this->forename $this->surname; ",
			'Club: ', ($this->club ? $this->club->id() : 'none'), '</p>';
	}

	public function recursiveDump() {
		$this->dump();
		$this->standardGrade->recursiveDump();
		$this->rapidGrade->recursiveDump();
		$this->lrcaRapidGrade->recursiveDump();
	}
}

abstract class PlayerStatus {
	const Inactive = 0;
	const Active = 1;
	const Departed = 2;
}
?>