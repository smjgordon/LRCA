<?php
require_once 'p_server.php';
require_once 'm_round.php';
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
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadFixtureId, $id);
		}
	}

	public function id() { return $this->_id; }

	public $round, $division, $date, $status;
	public $homeTeam, $homeRawScore, $homeAdjustedScore;
	public $awayTeam, $awayRawScore, $awayAdjustedScore;

	private function populateFromDbRow($row) {
		$this->_id = $row['fixture_id'];

		$this->round = Round::loadById($row['round_id']);
		$this->division = $this->round->division;
		
		$this->homeTeam = $row['home_team_id'] ? Team::loadById($row['home_team_id']) : null;
		$this->awayTeam = $row['away_team_id'] ? Team::loadById($row['away_team_id']) : null;
		$this->date = strtotime($row['fixture_date']);
		$this->status = $row['status'];

		if ($this->status == MatchStatus::Played || $this->status == MatchStatus::Defaulted
				|| $this->status == MatchStatus::ScoredBye) {
			$this->homeRawScore = $row['home_raw_score'];
			$this->awayRawScore = $row['away_raw_score'];
			$this->homeAdjustedScore = $row['home_adjusted_score'];
			$this->awayAdjustedScore = $row['away_adjusted_score'];
		}
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

		} else {
			$stmt = $Database->prepare('
				UPDATE fixture SET
					round_id = ?,
					home_team_id = ?,
					away_team_id = ?,
					fixture_date = ?,
					status = ?
				WHERE fixture_id = ?');
			$stmt->execute([$this->round->id(), $this->homeTeam->id(), $this->awayTeam->id(),
				$this->date ? date('c', $this->date) : null, $this->status, $this->_id]);
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