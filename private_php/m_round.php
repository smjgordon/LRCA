<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'm_fixture.php';
require_once 'u_id_wrapper.php';

class Round {
	public static function loadById($id) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT DISTINCT r.*, f.status AS any_played
			FROM round r
				LEFT JOIN fixture f ON r.round_id = f.round_id AND f.status = 1
			WHERE r.round_id = ?');
		$stmt->execute([$id]);

		if ($row = $stmt->fetch()) {
			$result = new Round();
			$result->populateFromDbRow($row);
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadRoundId, $id);
		}
	}

	public function id() { return $this->_id; }

	public $division, $sequence, $urlName, $name, $anyPlayed;
	public $fixtures;

	public function loadFixtures() {
		global $Database;

		if (!$this->_fixturesLoaded) {
			$this->fixtures = [];
			$stmt = $Database->prepare('
				SELECT fixture_id
				FROM fixture
				WHERE round_id = ?
				ORDER BY fixture_date, fixture_id');
			$stmt->execute([$this->_id]);
			while ($row = $stmt->fetch()) $this->fixtures[] = Fixture::loadById($row['fixture_id']);

			$this->_fixturesLoaded = true;
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = $row['round_id'];

		$this->division = new IdWrapper($row['division_id']);
		$this->sequence = $row['sequence'];
		$this->urlName = $row['url_name'];
		$this->name = $row['name'];
		$this->anyPlayed = !!$row['any_played'];
	}

	private $_id, $_fixturesLoaded;

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