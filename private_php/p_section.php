<?php
// todo: refactor
require_once 'p_enumerations.php';
require_once 'p_server.php';

class Section {
	public $id, $year;
	private $name, $season;

	function __construct($sectionID, $year) {
		global $Database;

		$this->id = $sectionID;
		$this->year = $year;

		$stmt = $Database->prepare('SELECT name, season FROM section WHERE section_id = ?');
		$stmt->execute([$sectionID]);
		$row = $stmt->fetch();

		if (!$row) throw new Exception('Section ID not found');
		$this->name = $row['name'];
		$this->season = $row['season'];
	}

	function displayName() {
		switch ($this->season) {
			case Season::Winter:
				return $this->year . '–' . ($this->year + 1) . ' ' . $this->name;
				break;
			case Season::Summer:
				return $this->year . ' ' . $this->name;
		}
	}

	function divisionIndex() {
		global $Database;

		$stmt = $Database->prepare('SELECT division_id, name FROM division WHERE section_id = ? AND year = ? ORDER BY sequence');
		$stmt->execute([$this->id, $this->year]);

		$row = $stmt->fetch();
		if ($row) {
			echo '<ul>';
			do {
				$anyDivisions = true;
				echo "<li><a href='division.php?did=$row[division_id]'>", htmlspecialchars($row['name']), '</a></li>';
			} while ($row = $stmt->fetch());
			echo '</ul>';
			return true;
		} else {
			return false;
		}
	}
}
?>