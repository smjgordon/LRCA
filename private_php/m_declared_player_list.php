<?php
// TODO: cache
require_once 'p_server.php';
require_once 'm_club.php';
require_once 'u_text.php';

class DeclaredPlayerList {
	public static function loadByUri($uri) {
		$uriParts = array_slice(explode('/', trim($uri, '/')), -3);
		$dateStr = null;
		if (count($uriParts) < 2) {
			throw new ModelAccessException(ModelAccessException::BadUrl, $uri);
		} else if ($uriParts[1] == 'dtp') {
			$clubUrlName = $uriParts[0];
			if (count($uriParts) == 3) $dateStr = $uriParts[2];
		} else if ($uriParts[2] == 'dtp') {
			$clubUrlName = $uriParts[1];
		}
		
		$club = Club::loadByUri('clubs/' . $clubUrlName);
		if ($dateStr !== null) {
			$date = parseDate($dateStr);
		} else {
			$date = time();
		}
		
		return self::loadByClubAndDate($club, $date);
	}
	
	public static function loadByClubAndDate($club, $date) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT * FROM declared_player_list
			WHERE club_id = ? AND start_date <= ? AND (end_date IS NULL OR end_date > ?)
			ORDER BY start_date DESC');
		$dateStr = date('c', $date);
		$stmt->execute([$club->id(), $dateStr, $dateStr]);
		
		if (!!($row = $stmt->fetch())) {
			$result = new DeclaredPlayerList();
			$result->_club = $club;
			$result->populateFromDbRow($row); 
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadDplDate, $club->name() . date(' c', $date));
		}
	}
	
	private function populateFromDbRow($row) {
		$this->_id = $row['list_id'];
		$this->_startDate = strtotime($row['start_date']);
		$this->_endDate = strtotime($row['end_date']);
	}

	public function id() { return $this->_id; }
	public function club() { return $this->_club; }
	public function startDate() { return $this->_startDate; }
	public function endDate() { return $this->_endDate; }

	private $_id, $_club, $_startDate, $_endDate;
}
?>