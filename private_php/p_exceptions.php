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
	const BadClubId           = 10;
	const BadClubName         = 11;
	const BadClubUrlName      = 12;
	const BadTeamId           = 20;
	const BadTeamUrlName      = 21;
	const BadDivisionId       = 30;
	const BadRoundId          = 40;
	const BadRoundUrlName     = 41;
	const BadFixtureId        = 50;
	const BadPlayerId         = 60;
	const BadPlayerEcfCode    = 62;
	const BadGradeId          = 70;
	const BadUserId           = 80;
	const BadHandicapSchemeId = 90;
	const BadSectionId        = 100;
	const BadSectionUrlName   = 101;
	const BadUrl              = 110;
	const BadNewsFeedUrlName  = 120;
	const BadNewsFeedId       = 121;
	const BadDplDate          = 130;
	
	public function __construct($code, $customData = null, $previous = null) {
		switch ($code) {
			case self::BadClubId:
				$message = "Club ID not found: $customData";
				break;
				
			case self::BadClubName:
				$message = "Club name not found: $customData";
				break;
				
			case self::BadClubUrlName:
				$message = "Club URL name not found: $customData";
				break;
				
			case self::BadTeamId:
				$message = "Team ID not found: $customData";
				break;
				
			case self::BadTeamUrlName:
				$message = "Team URL name not found: $customData";
				break;
				
			case self::BadDivisionId:
				$message = "Division ID not found: $customData";
				break;
				
			case self::BadRoundId:
				$message = "Round ID not found: $customData";
				break;
				
			case self::BadRoundUrlName:
				$message = "Round URL name not found: $customData";
				break;
			
			case self::BadFixtureId:
				$message = "Fixture ID not found: $customData";
				break;
				
			case self::BadPlayerId:
				$message = "Player ID not found: $customData";
				break;
				
			case self::BadPlayerEcfCode:
				$message = "Player ECF code not found: $customData";
				break;
				
			case self::BadGradeId:
				$message = "Grade ID not found: $customData";
				break;
			
			case self::BadUserId:
				$message = "User ID not found: $customData";
				break;
			
			case self::BadHandicapSchemeId:
				$message = "Handicap scheme ID not found: $customData";
				break;
			
			case self::BadSectionId:
				$message = "SectionID not found: $customData";
				break;
			
			case self::BadSectionUrlName:
				$message = "Section URL name not found: $customData";
				break;
			
			case self::BadUrl:
				$message = "Malformed URL: $customData";
				break;
				
			case self::BadNewsFeedUrlName:
				$message = "News feed URL name not found: $customData";
				break;
				
			case self::BadNewsFeedId:
				$message = "News feed ID not found: $customData";
				break;

			case self::BadDplDate:
				$message = "No declared team list found for club and date: $customData";
				break;
		}
		
		parent::__construct($message, $code, $previous);
	}
}

class UserInputException extends Exception {
	const MissingOwnEmail                      = 10;
	const NewsPostMissingTitle                 = 20;
	const NewsPostMissingText                  = 21;
	const NewsPostMissingFeed                  = 22;
	const NewsPostAttachmentWithoutDisplayName = 23;
	const NewsPostDisplayNameWithoutAttachment = 24;
	const IllegalUri                           = 30;
	
	public $customData;
	
	public function __construct($code, $customData = null, $previous = null) {
		switch ($code) {
			case self::MissingOwnEmail:
				$message = 'Please enter your email address';
				break;
			case self::NewsPostMissingTitle:
				$message = 'Please enter a headline';
				break;
			case self::NewsPostMissingText:
				$message = 'Please enter some article text';
				break;
			case self::NewsPostMissingFeed:
				$message = 'Please select a feed';
				break;
			case self::NewsPostAttachmentWithoutDisplayName:
				$message = 'An attachment was uploaded with no display name';
				break;
			case self::NewsPostDisplayNameWithoutAttachment:
				$message = 'An attachment display name was entered but no attachment was uploaded';
				break;
			case self::IllegalUri:
				$message = 'Only http or https URLs are allowed';
				break;
		}
		
		parent::__construct($message, $code, $previous);
		$this->customData = $customData;
	}
}
?>