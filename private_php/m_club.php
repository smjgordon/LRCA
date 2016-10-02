<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'm_fixture.php';
require_once 'm_team.php';

class Club {
	public static function loadAll() {
		global $Database;

		$result = [];

		$stmt = $Database->query('SELECT * FROM club');
		while ($row = $stmt->fetch()) {
			$result[] = new Club($row);
		}
		return $result;
	}

	public static function loadByName($name) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT * FROM club
			WHERE name = ?');
		$stmt->execute([$name]);

		if ($row = $stmt->fetch()) {
			return new Club($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadClubName, $name);
		}
	}

	public static function loadById($id) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT * FROM club
			WHERE club_id = ?');
		$stmt->execute([$id]);

		if ($row = $stmt->fetch()) {
			return new Club($row);
		} else {
			throw new ModelAccessException(ModelAccessException::BadClubId, $id);
		}
	}
	/*
	public $fixtures;

	public function loadFixtures() {
		global $Database;

		if (!$this->_fixturesLoaded) {
			$this->fixtures = [];
			$stmt = $Database->prepare('
				SELECT f.fixture_id
				FROM fixture f
					JOIN team ht ON f.home_team_id = ht.team_id
					JOIN team at ON f.away_team_id = at.team_id
				WHERE ? IN (ht.club_id, at.club_id)
				ORDER BY f.fixture_date, f.fixture_id');
			$stmt->execute([$this->_id]);
			while ($row = $stmt->fetch()) $this->fixtures[] = Fixture::loadById($row['fixture_id']);

			$this->_fixturesLoaded = true;
		}
	}*/

	public function fixturesPendingSubmission() {
		global $Database;

		$fixtures = [];
		$stmt = $Database->prepare("
			SELECT f.fixture_id
			FROM fixture f
				JOIN team ht ON f.home_team_id = ht.team_id
				JOIN team at ON f.away_team_id = at.team_id
			WHERE ? IN (ht.club_id, at.club_id)
				AND f.status = 0
				AND addtime(fixture_date, '20:00') <= ?
			ORDER BY f.fixture_date, f.fixture_id");
		$stmt->execute([$this->_id, date('c')]);

		while ($row = $stmt->fetch()) $fixtures[] = Fixture::loadById($row['fixture_id']);
		return $fixtures;
	}

	public function fixturesPendingApproval() {
		global $Database;

		$fixtures = [];
		$stmt = $Database->prepare('
			SELECT f.fixture_id
			FROM fixture f
			WHERE approval_club_id = ?
				AND f.status IN (1, 3)
				AND approved_date IS NULL
			ORDER BY f.fixture_date, f.fixture_id');
		$stmt->execute([$this->_id]);

		while ($row = $stmt->fetch()) $fixtures[] = Fixture::loadById($row['fixture_id']);
		return $fixtures;
	}

	public function fixturesPendingDates(&$anyPostponed, &$anyUnscheduled) {
		global $Database;
		
		$fixtures = [];
		$anyPostponed = $anyUnscheduled = false;
		$stmt = $Database->prepare('
			SELECT *
			FROM fixture f
				JOIN team ht ON f.home_team_id = ht.team_id
				JOIN team at ON f.away_team_id = at.team_id
			WHERE ? IN (ht.club_id, at.club_id)
				AND (f.status = 2 OR (f.status = 0 AND f.fixture_date IS NULL))
			ORDER BY f.fixture_date, f.fixture_id');
		$stmt->execute([$this->_id]);

		while ($row = $stmt->fetch()) {
			$fixtures[] = $fixture = Fixture::loadById($row['fixture_id']);
			switch ($fixture->status) {
				case MatchStatus::Unplayed:
					$anyUnscheduled = true;
					break;
				case MatchStatus::Postponed:
					$anyPostponed = true;
			}
		}
		return $fixtures;
	}

	private function __construct($row) {
		$this->_id = $row['club_id'];
		$this->name = $row['name'];
		$this->ecfCode = $row['ecf_code'];
		$this->status = $row['status'];
	}

	public function id() { return $this->_id; }
	public $name, $ecfCode, $status;
	private $_id;//, $_fixturesLoaded;
}

abstract class ClubStatus {
	const Inactive = 0;
	const Active = 1;
}
?>