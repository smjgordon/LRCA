<?php
require_once 'm_committee.php';

function committeeNavBar() {
	$committees = Committee::loadAll();
	
	$backToIndex = backToLevel(1);

	$result = '<ul>';
	foreach ($committees as $committee) {
		$result .= '<li><a href="' . $backToIndex . $committee->urlName() . '">'
			. htmlspecialchars($committee->name())
			. '</a></li>';
	}
	return $result . '</ul>';
}
?>