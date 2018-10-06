<?php
require_once 'm_division.php';
require_once 'm_document.php';

class Section {
	static public function loadAllCurrent($activeOnly = true) {
		global $Database;

		$result = [];

		$sql = 'SELECT * FROM section';
		if ($activeOnly) $sql .= ' WHERE status = 1';
		$sql .= ' ORDER BY sequence';

		$stmt = $Database->query($sql);
		while (!!($row = $stmt->fetch())) {
			$section = new Section();
			$section->populateFromDbRow($row);
			// TODO: work out what to do with this
			$section->_year = ($section->season() == Season::Winter) ? SystemSettings::$winterYear : SystemSettings::$summerYear;
			$result[] = $section;
		}
		return $result;
	}
	
	static public function loadAllInProgress() {
		global $Database;

		$result = [];

		$sql = 'SELECT * FROM section WHERE status = 1';

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

		$stmt = $Database->query($sql);
		while (!!($row = $stmt->fetch())) {
			$section = new Section();
			$section->populateFromDbRow($row);
			$section->_year = ($section->season() == Season::Winter) ? SystemSettings::$winterYear : SystemSettings::$summerYear;
			$result[] = $section;
		}
		return $result;
	}

	static public function loadByYearAndId($year, $id) {
		global $Database;

		$stmt = $Database->prepare('SELECT * FROM section WHERE section_id = ?');
		$stmt->execute([$id]);
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
		$stmt = $Database->prepare('SELECT * FROM section WHERE url_name = ?');
		$stmt->execute([$uriParts[1]]);
		if (!!($row = $stmt->fetch())) {
			$result = new Section();
			$result->populateFromDbRow($row);
			$result->_year = (int) $uriParts[0];
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
	}
	
	public function year() { return $this->_year; }
	public function id() { return $this->_id; }
	public function name() { return $this->_name; }
	public function urlName() { return $this->_urlName; }
	public function season() { return $this->_season; }
	
	public function divisions() {
		if (empty($_divisions)) $_divisions = Division::loadBySection($this);
		return $_divisions;
	}

	public function documents() {
		if (empty($_documents)) $_documents = Document::loadBySection($this);
		return $_documents;
	}

	private $_year, $_id, $_name, $_urlName, $_season, $_divisions, $_documents;
}
?>