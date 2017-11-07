<?php
require_once 'm_division.php';
require_once 'm_document.php';

class Section {
	static public function loadByYearAndId($year, $id) {
		global $Database;

		$stmt = $Database->prepare('SELECT * FROM section WHERE section_id = ?');
		$stmt->execute([$id]);
		if ($row = $stmt->fetch()) {
			$result = new Section();
			$result->populateFromDbRow($row);
			$result->_year = $year;
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadSectionId, $id);
		}
	}
	
	private function populateFromDbRow($row) {
		$this->_id = (int) $row['section_id'];
		$this->_name = $row['name'];
		$this->_season = (int) $row['season'];
	}
	
	public function year() { return $this->_year; }
	public function id() { return $this->_id; }
	public function name() { return $this->_name; }
	public function season() { return $this->_season; }
	
	public function divisions() {
		if (empty($_divisions)) $_divisions = Division::loadBySection($this);
		return $_divisions;
	}

	public function documents() {
		if (empty($_documents)) $_documents = Document::loadBySection($this);
		return $_documents;
	}

	private $_year, $_id, $_name, $_season, $_divisions, $_documents;
}
?>