<?php
require_once 'p_server.php';

class HandicapScheme {
	public static function loadById($id) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT grade_difference_cap, min_grade_difference, handicap_points
			FROM handicap_scheme hs
				JOIN handicap_rule hr ON hs.handicap_scheme_id = hr.handicap_scheme_id
			WHERE hs.handicap_scheme_id = ?
			ORDER BY min_grade_difference DESC');
		$stmt->execute([$id]);

		if ($row = $stmt->fetch()) {
			$result = new HandicapScheme();
			$result->_gradeDifferenceCap = $row['grade_difference_cap'];
			$result->_rules = [];
			$result->_totalGradeDifference = 0;
			
			do {
				$result->_rules[$row['min_grade_difference']] = $row['handicap_points'];
			} while (!!($row = $stmt->fetch()));
			
			return $result;
		} else {
			throw new ModelAccessException(ModelAccessException::BadHandicapSchemeId, $id);
		}
	}
	
	public function addBoard(&$gradeDifference) {
		if ($gradeDifference > $this->_gradeDifferenceCap) {
			$gradeDifference = $this->_gradeDifferenceCap;
		} else if ($gradeDifference < -$this->_gradeDifferenceCap) {
			$gradeDifference = -$this->_gradeDifferenceCap;
		}
		$this->_totalGradeDifference += $gradeDifference;
	}
	
	public function handicapPoints(&$outGradeDifference, &$outHomeHandicap, &$outAwayHandicap) {
		$outGradeDifference = $this->_totalGradeDifference;
		
		if ($outGradeDifference == 0) {
			$outHomeHandicap = $outAwayHandicap = 0;
			return;

		} else if ($outGradeDifference < 0) {
			// home is higher graded, so away gets the handicap points
			$outHomeHandicap = 0;
			$outPoints = &$outAwayHandicap;
			$absGradeDifference = -$outGradeDifference;
			
		} else {
			// away is higher graded, so home gets the handicap points
			$outAwayHandicap = 0;
			$outPoints = &$outHomeHandicap;
			$absGradeDifference = $outGradeDifference;
		}
		
		foreach ($this->_rules as $loopMinDifference => $loopPoints) {
			//echo "rule: $loopMinDifference, $loopPoints"; // debug
			if ($loopMinDifference <= $absGradeDifference) {
				$outPoints = $loopPoints;
				return;
			}
		}

		// we should never wind up here
		throw new Exception('Bad handicap scheme');
	}
	
	private $_gradeDifferenceCap, $_rules, $_totalGradeDifference;
}
?>