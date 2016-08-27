<?php
// TODO: do away with this
class ReportableException extends Exception {
	public function __construct($message = "", $code = 0, $previous = null, $customData = null) {
		parent::__construct($message, $code, $previous);
		$this->customData = $customData;
	}
	
	public $customData;
}

class ModelAccessException extends Exception {
	const BadClubName   = 10;
	const BadClubId     = 11;
	const BadTeamId     = 20;
	const BadDivisionId = 30;
	const BadRoundId    = 40;
	const BadFixtureId  = 50;
	
	public function __construct($code, $customData = null, $previous = null) {
		switch ($code) {
			case self::BadClubName:
				$message = "Club name not found: $customData";
				break;
				
			case self::BadClubId:
				$message = "Club ID not found: $customData";
				break;
				
			case self::BadTeamId:
				$message = "Team ID not found: $customData";
				break;
				
			case self::BadDivisionId:
				$message = "Division ID not found: $customData";
				break;
				
			case self::BadRoundId:
				$message = "Round ID not found: $customData";
				break;
		}
		
		parent::__construct($message, $code, $previous);
	}
}
?>