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
	const BadClubId        = 10;
	const BadClubName      = 11;
	const BadTeamId        = 20;
	const BadDivisionId    = 30;
	const BadRoundId       = 40;
	const BadFixtureId     = 50;
	const BadPlayerId      = 60;
	const BadPlayerEcfCode = 62;
	const BadGradeId       = 70;
	
	public function __construct($code, $customData = null, $previous = null) {
		switch ($code) {
			case self::BadClubId:
				$message = "Club ID not found: $customData";
				break;
				
			case self::BadClubName:
				$message = "Club name not found: $customData";
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
				
			case self::BadPlayerId:
				$message = "Player ID not found: $customData";
				break;
				
			case self::BadPlayerEcfCode:
				$message = "Player ECF code not found: $customData";
				break;
				
			case self::BadGradeId:
				$message = "Grade ID code not found: $customData";
				break;
		}
		
		parent::__construct($message, $code, $previous);
	}
}
?>