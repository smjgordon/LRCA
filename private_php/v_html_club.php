<?php
require_once 'm_club.php';

function clubNavBar() {
	$clubs = Club::loadAll();

	$result = '<ul>';
	foreach ($clubs as $club) {
		$result .= "<li><a href='club.php?cid=" . $club->id() . "'>"
			. htmlspecialchars($club->name())
			. '</a></li>';
	}
	return $result . '</ul>';
}

class HtmlClubView {
	public function __construct($club) {
		$this->_club = $club;
	}
	
	public function mapLink() {
		if ($this->_club->hasMapCoordinates()) {
			$mapUrl = 'https://www.google.com/maps/search/?api=1&query='
				. $this->_club->venueLatitude() . ',' . $this->_club->venueLongitude();
				
			if ($this->_club->venueGooglePlaceId()) {
				$mapUrl .= '&query_place_id=' . $this->_club->venueGooglePlaceId();
			}
			return ' <a href="' . htmlspecialchars($mapUrl) . '">(Map)</a>';
		} else {
			return null;
		}
	}

	private $_club;
}

function showContact($contact, $showContactInfo) {
	$result = '<td>' . htmlspecialchars($contact->name()) . '</td>';
	if ($showContactInfo) {
		$phones = $emails = '';
		foreach ($contact->phoneNumbers() as $phone) {
			if ($phones) $phones .= '<br/>';
			$phones .= htmlspecialchars($phone[0]);
			if ($phone[1]) $phones .= ' (' . htmlspecialchars($phone[1]) . ')';
		}
		foreach ($contact->emails() as $email) {
			if ($emails) $emails .= '<br/>';
			$htmlEmail = htmlspecialchars($email[0]);
			$emails .= "<a href='mailto:$htmlEmail'>$htmlEmail</a>";
			if ($email[1]) $emails .= ' (' . htmlspecialchars($email[1]) . ')';
		}
		$result .= "<td>$phones</td><td>$emails</td>";
	}
	return $result;
}

function showNoContact($showContactInfo) {
	$result = '<td';
	if ($showContactInfo) $result .= ' colspan="3"';
	$result .= '>To be confirmed</td>';
	return $result;
}
?>