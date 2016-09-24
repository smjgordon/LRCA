<?php
// TODO: finish the new division classes and do away with this
require_once 'p_section.php';
require_once 'p_html_functions.php';
require_once 'p_enumerations.php'; // TODO: do away with this?

class OldDivision {
	private $id, $name, $breakdown;
	public $section, $format;

	public function id() { return $this->id; }
	
	function __construct($divisionID) {
		global $Database;

		$this->id = $divisionID;

		$stmt = $Database->prepare('
			SELECT name, section_id, year, format, breakdown
			FROM division
			WHERE division_id = ?');
		$stmt->execute([$divisionID]);
		$row = $stmt->fetch();

		if (!$row) throw new Exception('Division ID not found');
		$this->name = $row['name'];
		$this->format = $row['format'];
		$this->breakdown = $row['breakdown'];
		$this->section = new Section($row['section_id'], $row['year']);
	}

	function bodyTitle() {
		return $this->section->displayName() . ' – ' . $this->name;
	}

	function headerTitle() {
		return $this->name . ' – ' . $this->section->displayName();
	}
	
	function breakdown() {
		global $Database;

		switch ($this->breakdown) {
			case Breakdown::ByMonth:
				$stmt = $Database->prepare('
					SELECT DISTINCT Substring(f.fixture_date, 1, 7) AS month -- yyyy-mm-dd -> yyyy-mm
					FROM round r
						JOIN fixture f ON r.round_id = f.round_id
						/*LEFT JOIN team home ON f.home_team_id = home.team_id
						LEFT JOIN team away ON f.away_team_id = away.team_id*/
					WHERE r.division_id = ? AND f.status = 1
					ORDER BY 1');
				$stmt->execute([$this->id]);

				$row = $stmt->fetch();
				if ($row) {
					echo '<ul>';
					do {
						$yearAndMonth = $row['month'];
						$monthName = monthNameFromIso($yearAndMonth);
						$anyDivisions = true;
						echo "<li><a href='results_by_month.php?did=$this->id&amp;month=$yearAndMonth'>$monthName</a></li>";
					} while ($row = $stmt->fetch());
					echo '</ul>';
					return true;
				} else {
					return false;
				}
				break;

			case Breakdown::ByRound:
				$stmt = $Database->prepare('
					SELECT DISTINCT r.round_id, r.name
					FROM round r
						JOIN fixture f ON r.round_id = f.round_id
					WHERE r.division_id = ? AND f.status = 1
					ORDER BY r.sequence');
				$stmt->execute([$this->id]);

				$row = $stmt->fetch();
				if ($row) {
					echo '<ul>';
					do {
						echo "<li><a href='results_by_round.php?rid=$row[round_id]'>", htmlspecialchars($row['name']), '</a></li>';
					} while ($row = $stmt->fetch());
					echo '</ul>';
					return true;
				} else {
					return false;
				}
		}
	}
}
/*
abstract class DivisionFormat {
	const RoundRobinSingle = 1;
	const RoundRobinDouble = 2;
	const Swiss = 11;
	const Knockout = 21;
}

abstract class Breakdown {
	const ByMonth = 1;
	const ByRound = 2;
}*/
?>