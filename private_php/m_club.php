<?php
// TODO: cache
require_once 'p_server.php';
require_once 'p_exceptions.php';
require_once 'm_fixture.php';
require_once 'm_team.php';

class Club {
	public static function loadAll($activeOnly = true) {
		global $Database;

		$result = [];

		$sql = 'SELECT * FROM club';
		if ($activeOnly) $sql .= ' WHERE status = 1';
		$sql .= ' ORDER BY name';

		$stmt = $Database->query($sql);
		while ($row = $stmt->fetch()) {
			$result[] = new Club($row);
		}
		return $result;
	}

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
	/*
	public $fixtures;

	public function loadFixtures() {
		global $Database;

		if (!$this->_fixturesLoaded) {
			$this->fixtures = [];
			$stmt = $Database->prepare('
				SELECT f.fixture_id
				FROM fixture f
					JOIN team ht ON f.home_team_id = ht.team_id
					JOIN team at ON f.away_team_id = at.team_id
				WHERE ? IN (ht.club_id, at.club_id)
				ORDER BY f.fixture_date, f.fixture_id');
			$stmt->execute([$this->_id]);
			while ($row = $stmt->fetch()) $this->fixtures[] = Fixture::loadById($row['fixture_id']);

			$this->_fixturesLoaded = true;
		}
	}*/

	public function fixturesPendingSubmission() {
		global $Database;

		$fixtures = [];
		$stmt = $Database->prepare("
			SELECT f.fixture_id
			FROM fixture f
				JOIN team ht ON f.home_team_id = ht.team_id
				JOIN team at ON f.away_team_id = at.team_id
			WHERE ? IN (ht.club_id, at.club_id)
				AND f.status = 0
				AND addtime(fixture_date, '20:00') <= ?
			ORDER BY f.fixture_date, f.fixture_id");
		$stmt->execute([$this->_id, date('c')]);

		while ($row = $stmt->fetch()) $fixtures[] = Fixture::loadById($row['fixture_id']);
		return $fixtures;
	}

	public function fixturesPendingApproval() {
		global $Database;

		$fixtures = [];
		$stmt = $Database->prepare('
			SELECT f.fixture_id
			FROM fixture f
			WHERE approval_club_id = ?
				AND f.status IN (1, 3)
				AND approved_date IS NULL
			ORDER BY f.fixture_date, f.fixture_id');
		$stmt->execute([$this->_id]);

		while ($row = $stmt->fetch()) $fixtures[] = Fixture::loadById($row['fixture_id']);
		return $fixtures;
	}

	public function fixturesPendingDates(&$anyPostponed, &$anyUnscheduled) {
		global $Database;

		$fixtures = [];
		$anyPostponed = $anyUnscheduled = false;
		$stmt = $Database->prepare('
			SELECT *
			FROM fixture f
				JOIN team ht ON f.home_team_id = ht.team_id
				JOIN team at ON f.away_team_id = at.team_id
			WHERE ? IN (ht.club_id, at.club_id)
				AND (f.status = 2 OR (f.status = 0 AND f.fixture_date IS NULL))
			ORDER BY f.fixture_date, f.fixture_id');
		$stmt->execute([$this->_id]);

		while ($row = $stmt->fetch()) {
			$fixtures[] = $fixture = Fixture::loadById($row['fixture_id']);
			switch ($fixture->status) {
				case MatchStatus::Unplayed:
					$anyUnscheduled = true;
					break;
				case MatchStatus::Postponed:
					$anyPostponed = true;
			}
		}
		return $fixtures;
	}

	private function __construct($row) {
		$this->_id = $row['club_id'];
		$this->_name = $row['name'];
		$this->_longName = $row['long_name'];
		$this->_ecfCode = $row['ecf_code'];
		$this->_status = $row['status'];

		$this->_venueName = $row['venue_name'];
		$this->_venueAddress = $row['venue_address_1'];
		for ($iLine = 2; $iLine <= 3; ++$iLine) {
			$addressLine = $row["venue_address_$iLine"];
			if ($addressLine) $this->_venueAddress .= "\n" . $addressLine;
		}
		$this->_venuePostcode = $row['venue_postcode'];
		$this->_venueLatitude = $row['venue_latitude'];
		$this->_venueLongitude = $row['venue_longitude'];
		$this->_venuePlaceId = $row['venue_google_place_id'];
		
		$this->_venueInfo = $row['venue_info_1'];
		for ($iLine = 2; $iLine <= 3; ++$iLine) {
			$infoLine = $row["venue_info_$iLine"];
			if ($infoLine) $this->_venueInfo .= "\n" . $infoLine;
		}

		$this->_meetingDay = $row['meeting_day'];
		$this->_meetingTime = $row['meeting_time'];
		$this->_meetingEndTime = $row['meeting_end_time'];
		$this->_sessionLength = $row['session_length'];
		$this->_digitalClocks = $row['digital_clocks'];
		$this->_websiteUrl = $row['website_url'];
	}

	public function id() { return $this->_id; }
	public function name() { return $this->_name; }
	public function longName() { return $this->_longName; }
	public function ecfCode() { return $this->_ecfCode; }
	public function status() { return $this->_status; }
	public function venueName() { return $this->_venueName; }
	public function venueAddress() { return $this->_venueAddress; }
	public function venuePostcode() { return $this->_venuePostcode; }
	public function venueInfo() { return $this->_venueInfo; }
	
	public function hasMapCoordinates() {
		return $this->_venueLatitude !== null && $this->_venueLongitude !== null;
	}
	public function venueLatitude() { return $this->_venueLatitude; }
	public function venueLongitude() { return $this->_venueLongitude; }
	public function venueGooglePlaceId() { return $this->_venuePlaceId; }
	
	/*public function mapUrl() {
		if ($this->hasMapCoordinates()) {
			return "https://www.google.com/maps/search/?api=1&query=$this->_venueLatitude,$this->_venueLongitude";
		} else {
			return null;
		}
	}*/
	
	public function meetingDay() {
		return array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')[$this->_meetingDay];
	}
	public function meetingTime() { return $this->_meetingTime; }
	public function meetingEndTime() { return $this->_meetingEndTime; }
	public function websiteUrl() { return $this->_websiteUrl; }
	public function sessionLength() { return $this->_sessionLength; }
	public function digitalClocks() { return $this->_digitalClocks; }

	private $_id, $_name, $_longName, $_ecfCode, $_status;//, $_fixturesLoaded;
	private $_venueName, $_venueAddress, $_venuePostcode, $venueInfo;
	private $_venueLatitude, $_venueLongitude, $_venuePlaceId;
	private $_meetingDay, $_meetingTime, $_meetingEndTime, $_sessionLength, $_digitalClocks;
	private $_websiteUrl;
}

abstract class ClubStatus {
	const Inactive = 0;
	const Active = 1;
}

abstract class SessionLength {
	const TwoHours40 = 1;
	const ThreeHours = 2;
	const Negotiable = 3;
}

abstract class DigitalClocks {
	const No = 0;
	const Limited = 1;
	const Yes = 2;
}

abstract class ContactType {
	const Secretary = 1;
	const EmailContact = 10;
	const TeamCaptain = 20;
}

class Contact {
	public static function loadByClub($club) {
		global $Database;

		$result = [];

		$stmt = $Database->prepare('
			SELECT con.contact_id, cc.type, con.name AS contact, t.name AS team, t.sequence, d.name AS division
			FROM club_contact cc
				JOIN contact con ON cc.contact_id = con.contact_id
				LEFT JOIN team t ON cc.team_id = t.team_id AND cc.club_id = t.club_id
				LEFT JOIN division d ON t.division_id = d.division_id
			WHERE cc.club_id = ?
			ORDER BY cc.type, d.sequence, t.sequence');
		$stmt->execute([$club->id()]);
		while (!!($row = $stmt->fetch())) {
			$contact = new Contact();
			$contact->populateFromDbRow($row);
			$result[] = $contact;
		}
		return $result;
	}

	private function populateFromDbRow($row) {
		global $Database;
		
		$this->_id = $row['contact_id'];
		$this->_type = $row['type'];
		$this->_name = $row['contact'];
		$this->_teamName = $row['team'];
		$this->_divisionName = $row['division'];
		$this->_emails = [];
		$this->_phoneNumbers = [];
		
		// TODO: logicalise
		$stmt = $Database->prepare('SELECT email, email_type FROM contact_email WHERE contact_id = ?');
		$stmt->execute([$this->_id]);
		while (!!($row = $stmt->fetch())) {
			$this->_emails[] = [$row['email'], $row['email_type']];
		}
		
		$stmt = $Database->prepare('SELECT phone, phone_type FROM contact_phone WHERE contact_id = ?');
		$stmt->execute([$this->_id]);
		while (!!($row = $stmt->fetch())) {
			$this->_phoneNumbers[] = [$row['phone'], $row['phone_type']];
		}
	}
	
	public function id() { return $this->_id; }
	public function type() { return $this->_type; }
	public function name() { return $this->_name; }
	public function teamName() { return $this->_teamName; }
	public function divisionName() { return $this->_divisionName; }
	public function emails() { return $this->_emails; }
	public function phoneNumbers() { return $this->_phoneNumbers; }	

	private $_id, $_type, $_name, $_teamName, $_divisionName;
	// TODO: logicalise
	private $_emails, $_phoneNumbers;
}
?>