<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'u_id_wrapper.php';

class Fixture {
	public static function loadById($id) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT * FROM fixture
			WHERE fixture_id = ?');
		$stmt->execute([$id]);
		
		if ($row = $stmt->fetch()) {
			$result = new Fixture();
			$result->populateFromDbRow($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadFixtureId, $id);
		}
	}

	public function id() { return $this->_id; }
	
	public $round, $homeTeam, $awayTeam, $date;

	private function populateFromDbRow($row) {
		$this->_id = $row['fixture_id'];
		
		$this->round = new IdWrapper($row['round_id']);
		$this->homeTeam = new IdWrapper($row['home_team_id']);
		$this->awayTeam = new IdWrapper($row['away_team_id']);
		$this->date = strtotime($row['fixture_date']);
	}
	
	private $_id;


	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {			
			$stmt = $Database->prepare('
				INSERT INTO fixture(round_id, home_team_id, away_team_id, fixture_date)
				VALUES(?, ?, ?, ?)');
			$stmt->execute([$this->round->id(), $this->homeTeam->id(), $this->awayTeam->id(),
				$this->date ? date('c', $this->date) : null]);
			$this->_id = $Database->lastInsertId();

		} else if (!$silentFail) {
			throw new Exception('Saving to an existing fixture not implemented');
		}
	}

	public function recursiveSave() {
		$this->save();
	}

	// DEBUG
	public function dump() {
		echo "<p>Fixture ID: $this->_id; Home team: ", $this->homeTeam->name, "; ",
			"Away team: ", $this->awayTeam->name, "; Date: $this->date</p>";
	}
	
	public function recursiveDump() {
		$this->dump();
	}}
?>