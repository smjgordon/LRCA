<?php
// TODO: cache (if any point)
require_once 'p_server.php';

class Committee {
	public static function loadAll() {
		global $Database;

		$result = [];

		$sql = 'SELECT * FROM committee ORDER BY sequence';

		$stmt = $Database->query($sql);
		while ($row = $stmt->fetch()) {
			$newCommittee = new Committee();
			$newCommittee->populateFromDbRow($row);
			$result[] = $newCommittee;
		}
		return $result;
	}

	public static function loadByUri($uri) {
		global $Database;
		
		$uriParts = array_slice(explode('/', trim($uri, '/')), -1);
		if (count($uriParts) != 1) throw new ModelAccessException(ModelAccessException::BadUrl, $uri);
		$stmt = $Database->prepare(' SELECT * FROM committee WHERE url_name = ?');
		$stmt->execute($uriParts);
		if ($row = $stmt->fetch()) {
			$result = new Committee();
			$result->populateFromDbRow($row);
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadClubUrlName, $uriParts[0]);
		}
	}

	private function populateFromDbRow($row) {
		$this->_id = $row['committee_id'];
		$this->_urlName = $row['url_name'];
		$this->_name = $row['name'];
		$this->_longName = $row['long_name'];
	}

	public function id() { return $this->_id; }
	public function urlName() { return $this->_urlName; }
	public function name() { return $this->_name; }
	public function longName() { return $this->_longName; }
	
	private $_id, $_urlName, $_name, $_longName;
}
?>