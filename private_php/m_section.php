<?php
require_once 'm_division.php';
require_once 'm_document.php';

class Section {
	static public function loadAllCurrent($activeOnly = true) {
		global $Database;

		$result = [];

		$sql = '
			SELECT s.section_id, s.name, s.url_name, s.season, sy.year, sy.barring_system
			FROM section s
				JOIN section_year sy ON s.section_id = sy.section_id
			WHERE (s.season = 1 AND sy.year = ?) OR (s.season = 2 AND sy.year = ?)';
		if ($activeOnly) $sql .= ' AND status = 1';
		$sql .= ' ORDER BY sequence';

		$stmt = $Database->prepare($sql);
		$stmt->execute([SystemSettings::$winterYear, SystemSettings::$summerYear]);

		while (!!($row = $stmt->fetch())) {
			$section = new Section();
			$section->populateFromDbRow($row);
			$result[] = $section;
		}
		return $result;
	}

	static public function loadAllInProgress() {
		global $Database;

		$result = [];

		$sql = '
			SELECT s.section_id, s.name, s.url_name, s.season, sy.year, sy.barring_system
			FROM section s
				JOIN section_year sy ON s.section_id = sy.section_id
			WHERE (s.season = 1 AND sy.year = ?) OR (s.season = 2 AND sy.year = ?)
				AND s.status = 1';

		switch (SystemSettings::$currentSeason) {
			case Season::Winter:
				$sql .= ' AND season = 1 ORDER BY sequence';
				break;
			case Season::Winter:
				$sql .= ' AND season = 2 ORDER BY sequence';
				break;
			default:
				if (SystemSettings::$summerYear > SystemSettings::$winterYear) {
					$sql .= ' ORDER BY season, sequence';
				} else {
					$sql .= ' ORDER BY season DESC, sequence';
				}
		}

		$stmt = $Database->prepare($sql);
		$stmt->execute([SystemSettings::$winterYear, SystemSettings::$summerYear]);
		while (!!($row = $stmt->fetch())) {
			$section = new Section();
			$section->populateFromDbRow($row);
			$result[] = $section;
		}
		return $result;
	}

	static public function loadByYearAndId($year, $id) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT s.section_id, s.name, s.url_name, s.season, sy.year, sy.barring_system
			FROM section s
				JOIN section_year sy ON s.section_id = sy.section_id
			WHERE s.section_id = ? AND sy.year = ?');
		$stmt->execute([(int) $id, (int) $year]);
		if (!!($row = $stmt->fetch())) {
			$result = new Section();
			$result->populateFromDbRow($row);
			$result->_year = $year;
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadSectionId, $id);
		}
	}

	static public function loadByUri($uri) {
		global $Database;

		$uriParts = array_slice(explode('/', trim($uri, '/')), -2);
		if (count($uriParts) != 2) throw new ModelAccessException(ModelAccessException::BadUrl, $uri);
		$stmt = $Database->prepare('
			SELECT s.section_id, s.name, s.url_name, s.season, sy.year, sy.barring_system
			FROM section s
				JOIN section_year sy ON s.section_id = sy.section_id
			WHERE s.url_name = ? AND sy.year = ?');
		$stmt->execute([$uriParts[1], (int) $uriParts[0]]);
		if (!!($row = $stmt->fetch())) {
			$result = new Section();
			$result->populateFromDbRow($row);
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadSectionUrlName, $uriParts[1]);
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = (int) $row['section_id'];
		$this->_name = $row['name'];
		$this->_urlName = $row['url_name'];
		$this->_season = (int) $row['season'];
		$this->_year = (int) $row['year'];
		$this->_barringSystem = (int) $row['barring_system'];
	}

	public function year() { return $this->_year; }
	public function id() { return $this->_id; }
	public function name() { return $this->_name; }
	public function urlName() { return $this->_urlName; }
	public function season() { return $this->_season; }
	public function barringSystem() { return $this->_barringSystem; }

	public function divisions() {
		if (empty($_divisions)) $_divisions = Division::loadBySection($this);
		return $_divisions;
	}

	public function documents() {
		if (empty($_documents)) $_documents = Document::loadBySection($this);
		return $_documents;
	}

	private $_year, $_id, $_name, $_urlName, $_season, $_divisions, $_documents, $_barringSystem;
}

abstract class BarringSystem {
	const LegacyLeague = 1;
	const SummerCup = 2;
	const DeclaredTeamSystem = 3;
}
?>