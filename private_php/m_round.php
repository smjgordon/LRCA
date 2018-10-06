<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'm_fixture.php';
require_once 'm_division.php';
require_once 'u_id_wrapper.php';

class Round {
	private static $instanceCache = [];
	
	public static function loadById($id) {
		global $Database;
		
		if (isset(Round::$instanceCache[$id])) {
			return Round::$instanceCache[$id];
		} else {
			$stmt = $Database->prepare('
				SELECT DISTINCT r.*, f.status AS any_played
				FROM round r
					LEFT JOIN fixture f ON r.round_id = f.round_id AND f.status = 1
				WHERE r.round_id = ?');
			$stmt->execute([$id]);

			if ($row = $stmt->fetch()) {
				$result = new Round();
				$result->populateFromDbRow($row);
				Round::$instanceCache[$id] = $result;
				return $result;
			} else {
				throw new ModelAccessException(ModelAccessException::BadRoundId, $id);
			}
		}
	}

	static public function loadByUri($uri) {
		global $Database;
		
		$uriParts = array_slice(explode('/', trim($uri, '/')), -5);
		if (count($uriParts) != 5) throw new ModelAccessException(ModelAccessException::BadUrl, $uri);
		$stmt = $Database->prepare('
			SELECT DISTINCT r.*, f.status AS any_played
			FROM round r
				LEFT JOIN fixture f ON r.round_id = f.round_id AND f.status = 1
				JOIN division d ON r.division_id = d.division_id
				JOIN section s ON d.section_id = s.section_id
			WHERE d.year = ? AND s.url_name = ? AND d.url_name = ? AND r.url_name = ?');
		$stmt->execute([$uriParts[0], $uriParts[1], $uriParts[2], $uriParts[4]]);
		if ($row = $stmt->fetch()) {
			$id = (int) $row['round_id'];
			if (isset(Round::$instanceCache[$id])) {
				return Round::$instanceCache[$id];
			} else {			
				$result = new Round();
				$result->populateFromDbRow($row);
				//$result->_year = (int) $uriParts[0];
				Round::$instanceCache[$id] = $result;
				return $result;
			}
		} else {
			throw new ModelAccessException(ModelAccessException::BadRoundUrlName, $uriParts[1]);
		}
	}

	public $fixtures;

	public function id() { return $this->_id; }
	public function division() { return $this->_division; }
	public function sequence() { return $this->_sequence; }
	public function name() { return $this->_name; }
	public function urlName() { return $this->_urlName; }
	public function setDivision($division) { $this->_division = $division; }
	public function setSequence($sequence) { $this->_sequence = $sequence; }
	public function setName($name) { $this->_name = $name; }
	public function setUrlName($name) { $this->_urlName = $name; }
	public function anyPlayed() { return $this->_anyPlayed; }

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

		$this->_division = Division::loadById($row['division_id']);
		$this->_sequence = $row['sequence'];
		$this->_urlName = $row['url_name'];
		$this->_name = $row['name'];
		$this->_anyPlayed = !!$row['any_played'];
	}

	private $_id, $_division, $_fixturesLoaded, $_sequence, $_urlName, $_name, $_anyPlayed;

	public function save($silentFail = false) {
		global $Database;

		if ($this->_id == 0) {
			$stmt = $Database->prepare('
				INSERT INTO round(division_id, sequence, url_name, name)
				VALUES(?, ?, ?, ?)');
			$stmt->execute([$this->_division->id(), $this->_sequence, $this->_urlName, $this->_name]);
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
		echo "<p>Round ID: $this->_id; Name: $this->_name; ",
			"URL name: $this->_urlName; Sequence: $this->_sequence</p>";
	}

	public function recursiveDump() {
		$this->dump();
		foreach ($this->fixtures as $fixtures) $fixtures->recursiveDump();
	}
}
?>