<?php
require_once 'm_club.php';

function clubNavBar() {
	$clubs = Club::loadAll();

	$backToIndex = backToLevel(1);

	$result = '<ul>';
	foreach ($clubs as $club) {
		$result .= '<li><a href="' . $backToIndex . $club->urlName() . '/">'
			. htmlspecialchars($club->name())
			. '</a></li>';
		/*$result .= "<li><a href='club.php?cid=" . $club->id() . "'>"
			. htmlspecialchars($club->name())
			. '</a></li>';*/
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

function showNoContact($showContactInfo) {
	$result = '<td';
	if ($showContactInfo) $result .= ' colspan="3"';
	$result .= '>To be confirmed</td>';
	return $result;
}
?>