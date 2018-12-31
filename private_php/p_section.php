<?php
// TODO: get everything using the new section class and do away with this
require_once 'p_enumerations.php';
require_once 'p_server.php';

class OldSection {
	public $id, $year, $season;
	private $name;

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
/*
	function divisionIndex() {
		global $Database;

		$stmt = $Database->prepare('SELECT division_id, name FROM division WHERE section_id = ? AND year = ? ORDER BY sequence');
		$stmt->execute([$this->id, $this->year]);

		$row = $stmt->fetch();
		if ($row) {
			echo '<ul>';
			do {
				//$anyDivisions = true;
				echo "<li><a href='division.php?did=$row[division_id]'>", htmlspecialchars($row['name']), '</a></li>';
			} while ($row = $stmt->fetch());
			echo '</ul>';
			return true;
		} else {
			return false;
		}
	}
*/
	function documentList() {
		global $Database;

		$stmt = $Database->prepare('
			SELECT file_name, display_name
			FROM section_document sd
				JOIN document d ON sd.document_id = d.document_id
			WHERE section_id = ? AND year = ?
			ORDER BY sd.sequence');
		$stmt->execute([$this->id, $this->year]);

		$row = $stmt->fetch();
		if ($row) {
			echo '<ul>';
			do {
				//$anyDivisions = true;
				echo "<li><a href='docs/$row[file_name]'>", htmlspecialchars($row['display_name']), '</a></li>';
			} while ($row = $stmt->fetch());
			echo '</ul>';
		}
	}
}
?>