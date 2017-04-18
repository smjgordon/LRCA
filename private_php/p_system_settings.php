<?php
require_once 'p_enumerations.php';

abstract class SystemSettings {
	static $fromEmail = 'LRCA Results Website <website@lrca.stewartsplace.org.uk>';
	static $replyEmail = 'Stewart Gordon <smjg@iname.com>';
	static $ccEmail = 'Stewart Gordon <smjg@iname.com>';
	
	static $summerYear = 2016;
	static $winterYear = 2016;
	static $currentSeason = Season::Winter;
	
	static $emailReminderDays = 4;
	// TODO: do away with these and use the database-driven values instead
	static $defaultFirstPenalty = 5;
	static $defaultPenaltyEvery = 3;
}
?>