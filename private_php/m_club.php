<?php
require_once 'p_server.php';
require_once 'p_exceptions.php';

class Club {
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
	
	private function __construct($row) {
		$this->_id = $row['club_id'];
		$this->name = $row['name'];
		$this->ecfCode = $row['ecf_code'];
		$this->status = $row['status'];
	}
	
	public function id() { return $this->_id; }
	public $name, $ecfCode, $status;
	private $_id;
}

abstract class ClubStatus {
	const Inactive = 0;
	const Active = 1;
}
?>