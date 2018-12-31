<?php
require_once 'm_session.php';
require_once 'p_error_page.php';
require_once 'p_match.php';

class CheckTeamController {
	public function __construct() {
		$this->_fixtureId = @$_REQUEST['fid'] or $this->_fixtureId = '';

		$this->_playerIds = [];
		for ($i = 1;; ++$i) {
			$key = "b$i";
			if (!isset($_POST[$key])) break;
			$this->_playerIds[] = $_POST[$key];
		}
	}

	/* Returns:
		null, if no checking has taken place yet
		an empty array, if the team is valid
		an array of error strings, if the team is invalid
	*/
	public function process() {
		global $CurrentUser;

		// get the fixture
		if (!is_numeric($this->_fixtureId)) errorPage(HttpStatus::NotFound);
		$this->_fixtureId = (int) $this->_fixtureId;
		$this->_match = Match::load($this->_fixtureId);

		// verify that this fixture is an upcoming one for the user's club
		$fixtures = $CurrentUser->club()->futureFixtures();
		$loopFixture = null;
		foreach ($fixtures as $loopFixture) {
			if ($loopFixture->id() == $this->_fixtureId) break;
		}
		if ($loopFixture == null || $this->_fixtureId != $loopFixture->id()) errorPage(403);

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// now get the player list from the user
			$players = [];
			foreach ($this->_playerIds as $playerId) {
				if ($playerId == '') {
					$players[] = null;
				} else if (!is_numeric($playerId)) {
					errorPage(HttpStatus::BadRequest);
				} else {
					$player = Player::loadById((int) $playerId);
					$players[] = $player;
				}
			}

			return $this->_match->checkTeam($CurrentUser->club(), $players);

		} else {
			// initial entry to page
			return null;
		}
	}

	public function match() { return $this->_match; }
	public function fixtureId() { return $this->_fixtureId; }

	private $_fixtureId;
	private $_playerIds;
	private $_match;
}
