<?php
abstract class ContactType {
	const Secretary = 1;
	const EmailContact = 10;
	const TeamCaptain = 20;
	const LrcaChair = 100;
	const LrcaSecretary = 110;
	const LrcaTreasurer = 120;
}

class Contact {
	public static function loadNonTeamByClub($club) {
		global $Database;

		$result = [];

		$stmt = $Database->prepare('
			SELECT con.contact_id, cc.type, con.name AS contact
			FROM club_contact cc
				JOIN contact con ON cc.contact_id = con.contact_id
			WHERE cc.club_id = ? AND cc.team_id IS NULL
			ORDER BY cc.type');
		$stmt->execute([$club->id()]);
		while (!!($row = $stmt->fetch())) {
			$contact = new Contact();
			$contact->populateFromDbRow($row);
			$result[] = $contact;
		}
		return $result;
	}

	public static function loadByTeam($team) {
		global $Database;

		$result = [];

		$stmt = $Database->prepare('
			SELECT con.contact_id, cc.type, con.name AS contact, t.name AS team, t.sequence, d.name AS division
			FROM club_contact cc
				JOIN contact con ON cc.contact_id = con.contact_id
				JOIN team t ON cc.team_id = t.team_id AND cc.club_id = t.club_id
				JOIN division d ON t.division_id = d.division_id
			WHERE t.team_id = ?
			ORDER BY cc.type, d.sequence, t.sequence');
		$stmt->execute([$team->id()]);
		while (!!($row = $stmt->fetch())) {
			$contact = new Contact();
			$contact->populateFromDbRow($row);
			$result[] = $contact;
		}
		return $result;
	}

	public static function loadByCommittee($committee) {
		global $Database;

		$result = [];
		$stmt = $Database->prepare('
			SELECT o.contact_id, o.type_name AS type, con.name AS contact, co.ex_officio
			FROM committee_officer co
				JOIN officer o ON co.officer_id = o.officer_id
				LEFT JOIN contact con ON o.contact_id = con.contact_id
			WHERE co.committee_id = ?
			ORDER BY co.sequence');

		$stmt->execute([$committee->id()]);
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
		if (@$row['team']) {
			$this->_teamName = $row['team'];
			$this->_divisionName = $row['division'];
		}
		$this->_emails = [];
		$this->_phoneNumbers = [];
		
		if (isset($row['ex_officio'])) {
			$this->_exOfficio = !!$row['ex_officio'];
		}

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
	// not exactly logical to put it here, but it'll do for now
	public function exOfficio() { return $this->_exOfficio; }

	private $_id, $_type, $_name, $_teamName, $_divisionName, $_exOfficio;
	// TODO: logicalise
	private $_emails, $_phoneNumbers;
}
?>