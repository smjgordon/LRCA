<?php
require_once 'private_php/m_player.php';
require_once 'private_php/p_enumerations.php';
header('Content-Type: text/plain; charset=UTF-8');

echo "Club,ECF Code,Forename,Surname,Standard Grade,Standard Category,Rapid Grade,Rapid Category,LRCA Rapid Grade\n";

$allPlayers = Player::loadAll();
foreach ($allPlayers as $player) {
	if ($player->status == PlayerStatus::Active) {
		$player->loadGrades(time(), Season::Winter);
		
		if (($player->standardGrade && $player->standardGrade->category)
				|| ($player->rapidGrade && $player->rapidGrade->category)) {
			echo $player->club->name, ',', $player->ecfGradingCode, ',', $player->forename, ',', $player->surname, ',';
			
			$grade = $player->standardGrade;
			
			if ($grade && $grade->category) {
				echo $grade->grade, ',', $grade->category, ',';
			} else {
				echo ',,';
			}
			
			$grade = $player->rapidGrade;
			
			if ($grade && $grade->category) {
				echo $grade->grade, ',', $grade->category, ',';
			} else {
				echo ',,';
			}
			
			$grade = $player->lrcaRapidGrade;
			
			if ($grade) {
				echo $grade->grade;
			}

			echo "\n";
		}
	}
}
?>