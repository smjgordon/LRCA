<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'u_id_wrapper.php';

class Round {
	public static function loadById($id) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT * FROM round
			WHERE round_id = ?');
		$stmt->execute([$id]);
		
		if ($row = $stmt->fetch()) {
			$result = new Round();
			$result->populateFromDbRow($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadRoundId, $id);
		}
	}
	
	public function id() { return $this->_id; }
	
	public $division, $sequence, $urlName, $name;
	public $fixtures;

	private function populateFromDbRow($row) {
		$this->_id = $row['round_id'];
		
		$this->division = new IdWrapper($row['division_id']);
		$this->sequence = $row['sequence'];
		$this->urlName = $row['url_name'];
		$this->name = $row['name'];
	}
	
	private $_id;

	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO round(division_id, sequence, url_name, name)
				VALUES(?, ?, ?, ?)');
			$stmt->execute([$this->division->id(), $this->sequence, $this->urlName, $this->name]);
			$this->_id = $Database->lastInsertId();

		} else if (!$silentFail) {
			throw new Exception('Saving to an existing round not implemented');
		}
	}

	public function recursiveSave() {
		$this->save();
		foreach ($this->fixtures as $team) $team->recursiveSave(true);
	}

	// DEBUG
	public function dump() {
		echo "<p>Round ID: $this->_id; Name: $this->name; ",
			"URL name: $this->urlName; Sequence: $this->sequence</p>";
	}
	
	public function recursiveDump() {
		$this->dump();
		foreach ($this->fixtures as $fixtures) $fixtures->recursiveDump();
	}
}
?>