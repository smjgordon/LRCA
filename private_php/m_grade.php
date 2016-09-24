<?php
require_once 'u_id_wrapper.php';

class Grade {
	public static function loadById($id) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT * FROM grade
			WHERE grade_id = ?');
		$stmt->execute([$id]);

		if ($row = $stmt->fetch()) {
			$result = new Grade(null, null, null, null);
			$result->populateFromDbRow($row);
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadGradeId, $id);
		}
	}

	public static function loadByPlayer($player, $date, $season, $type) {
		global $Database;
		// debug
		//var_dump($player);
		//echo $date, ' ', $season, ' ', $type;
		
		$stmt = $Database->prepare('
			SELECT * FROM grade
			WHERE player_id = ? AND effective_from <= ? AND season = ? AND type = ?
			ORDER BY effective_from DESC');
		$stmt->execute([$player->id(), date('c', $date), $season, $type]);
		//var_dump($row); // debug
		//echo $stmt->rowCount(); // debug

		// we only care about the 1st record (latest effective_from) returned, as this is the one effective as of $date
		if ($row = $stmt->fetch()) {
			$result = new Grade($player, null, null, null);
			$result->populateFromDbRow($row);
			return $result;
		} else {
			// player ungraded as of date - just don't return anything
			return null;
		}
	}

	public function __construct($player, $effectiveDate, $season, $type) {
		$this->player = $player;
		$this->effectiveDate = $effectiveDate;
		$this->season = $season;
		$this->type = $type;
	}

	public function id() { return $this->_id; }
	public $player, $type, $grade, $effectiveDate, $category, $season;
	private $_id;

	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO grade(player_id, type, grade, effective_from, category, season)
				VALUES(?, ?, ?, ?, ?, ?)');
			$stmt->execute([$this->player->id(), $this->type, $this->grade,
				$this->effectiveDate ? date('c', $this->effectiveDate) : null, $this->category, $this->season]);
			$this->_id = $Database->lastInsertId();

		} else if (!$silentFail) {
			throw new Exception('Saving to an existing fixture not implemented');
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = $row['grade_id'];

		if (!$this->player) $this->player = new IdWrapper($row['player_id']);
		$this->type = $row['type'];
		$this->grade = $row['grade'];
		$this->effectiveDate = $row['effective_from'];
		$this->category = $row['category'];
		$this->season = $row['season'];
	}

	// DEBUG
	public function dump() {
		echo '<p>Player ID: ', $this->player->id(), "; Type: $this->type; Grade: $this->grade; ",
			'Effective: ', date('Y-m-d', $this->effectiveDate), "; Category: $this->category; Season: $this->season</p>";
	}

	public function recursiveDump() {
		$this->dump();
	}
}

abstract class GradeType {
	const Standard = 0;
	const Rapid = 1;
	const LrcaRapid = 2;
}
?>