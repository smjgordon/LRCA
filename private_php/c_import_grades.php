<?php
require_once 'm_player.php';
require_once 'u_xml.php';

// TODO: MVC-separate the grade table stuff
function importGrades($xml/*, $season, $effectiveDate*/) {
	global $Database;

	$gradesNode = new SimpleXMLElement($xml);
	$effectiveDate = XmlUtils::readDate($gradesNode['effective'], 'Missing or invalid effective date');
	$seasonStr = XmlUtils::readString($gradesNode['season'], 'Missing season');
	switch ($seasonStr) {
		case 'w': $season = Season::Winter; break;
		case 's': $season = Season::Summer; break;
		default: throw new ReportableException('Invalid season');
	}

	$Database->beginTransaction();

	try {
		$allPlayers = Player::loadAll();

		$playersByEcf = [];
		foreach ($allPlayers as $player) {
			// create the grade objects at this stage - we will save a null grade for the effective date if the player is ungraded
			$player->standardGrade  = new Grade($player, $effectiveDate, $season, GradeType::Standard);
			$player->rapidGrade     = new Grade($player, $effectiveDate, $season, GradeType::Rapid);
			$player->lrcaRapidGrade = new Grade($player, $effectiveDate, $season, GradeType::LrcaRapid);

			if ($player->ecfGradingCode) $playersByEcf[$player->ecfGradingCode] = $player;
		}

		foreach ($gradesNode->player as $playerNode) {
			$ecf = XmlUtils::readString($playerNode['code'], 'Missing ECF code');
			if (!isset($playersByEcf[$ecf])) throw new ReportableException('Invalid or duplicate ECF code: ' . $ecf);

			$player = $playersByEcf[$ecf];

			foreach ($playerNode->grade as $gradeNode) {
				$gradeType = XmlUtils::readString($gradeNode['type'], 'Missing grade type');
				switch ($gradeType) {
					case 's': $newGrade = $player->standardGrade; break;
					case 'r': $newGrade = $player->rapidGrade; break;
					default: throw new ReportableException('Invalid grade type');
				}
				if ($newGrade->grade) throw new ReportableException("Duplicate grade type '$gradeNode[type]' for ECF code $ecf");

				$newGrade->category = XmlUtils::readString($gradeNode['category'], 'Missing category');
				$newGrade->grade = XmlUtils::readInt($gradeNode['value'], 'Missing or invalid grade');
			}

			// now determine the LRCA rapid grade to use
			if ($player->rapidGrade->category >= 'A' && $player->rapidGrade->category <= 'D') {
				$player->lrcaRapidGrade->grade = $player->rapidGrade->grade;
			} else if ($player->standardGrade->category >= 'A' && $player->standardGrade->category <= 'D') {
				$player->lrcaRapidGrade->grade = $player->standardGrade->grade;
			} else if ($player->rapidGrade->category >= 'E' && $player->rapidGrade->category <= 'Z') {
				$player->lrcaRapidGrade->grade = $player->rapidGrade->grade;
			} else {
				$player->lrcaRapidGrade->grade = $player->standardGrade->grade;
			}

			//$player->recursiveDump(); // DEBUG
			//$player->saveGrades();
			// make sure the same ECF code isn't processed again
			unset($playersByEcf[$ecf]);
		}

		// now save them all
		foreach ($allPlayers as $player) {
			$player->saveGrades();
		}

		$Database->commit();

	} catch (Exception $ex) {
		$Database->rollBack();
		throw $ex;
	}
}
?>