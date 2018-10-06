<?php
require_once 'p_enumerations.php';

abstract class SystemSettings {
	static $fromEmail = 'LRCA Website <website@leicestershirechess.org>';
	static $replyEmail = 'Stewart Gordon <smjg@iname.com>';
	static $ccEmail = 'Stewart Gordon <smjg@iname.com>';
	
	static $summerYear = 2018;
	static $winterYear = 2018;
	static $currentSeason = Season::BothDefaultWinter;
	
	static $submitResultReminderDays = 3;
	static $registerDateReminderDays = 14;
	static $upcomingFixturesToShow = 5;
}
?>