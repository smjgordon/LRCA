<?php
require_once 'p_section.php';
require_once 'm_division.php';
require_once 'm_club.php';
require_once 'm_team.php';
require_once 'm_round.php';
require_once 'm_fixture.php';

function readString($attribute, $exceptionMessage) {
	$value = (string) $attribute;
	if ($value == '') throw new ReportableException($exceptionMessage);
	return $value;
}

function readInt($attribute, $exceptionMessage) {
	$value = (string) $attribute;
	if (!is_numeric($value)) throw new ReportableException($exceptionMessage);
	return (int) $value;
}
	
function importFixtures($xml) {
	global $Database;
	
	// here, we will build up a dictionary of clubs participating in the League
	$clubsByName = [];
	
	$leagueNode = new SimpleXMLElement($xml);

	$Database->beginTransaction();
	try {
		$sectionId = readString($leagueNode['section'], 'Invalid section ID');
		$year = readInt($leagueNode['year'], 'Invalid year');
		if ($year < 2015) throw new ReportableException('Invalid year');
		
		// TODO: update this when/if Section is refactored
		$section = new Section($sectionId, $year);
		
		// create divisions
		$divisionSequence = 0; // TODO: continue the sequence if divisions already exist in the section
		$divisionsByName = [];
		
		foreach ($leagueNode->division as $divisionNode) {
			$name = readString($divisionNode['name'], 'Missing division name');
			if (isset($divisionsByName[$name])) throw new ReportableException('Duplicate division name: ' . $name);
			$urlName = readString($divisionNode['url-name'], 'Missing URL name for division ' . $name);
			
			$matchStyle = (string) $divisionNode['match-style'];
			switch ($matchStyle) {
				case 'sp': $matchStyle = MatchStyle::Standard; break;
				case 'rp': $matchStyle = MatchStyle::RapidSame; break;
				case 'rpd': $matchStyle = MatchStyle::RapidDifferent; break;
				default: throw new ReportableException('Invalid match-style for division ' . $name);
			}
			
			$breakdown = (string) $divisionNode['breakdown'];
			switch ($breakdown) {
				case 'month': $breakdown = Breakdown::ByMonth; break;
				case 'round': $breakdown = Breakdown::ByRound; break;
				default: throw new ReportableException('Invalid breakdown for division ' . $name);
			}
			
			$format = (string) $divisionNode['format'];
			switch ($format) {
				case 'rr1': $breakdown = DivisionFormat::RoundRobinSingle; break;
				case 'rr2': $breakdown = DivisionFormat::RoundRobinDouble; break;
				case 'swiss': $breakdown = DivisionFormat::Swiss; break;
				case 'ko': $breakdown = DivisionFormat::Knockout; break;
				default: throw new ReportableException('Invalid format for division ' . $name);
			}
			
			$requireGrade = (string) $divisionNode['require-grade'];
			switch ($requireGrade) {
				case '0': $requireGrade = false; break;
				case '1': $requireGrade = true; break;
				default: throw new ReportableException('Invalid require-grade for division ' . $name);
			}
			
			$division = new Division();
			$division->section = $section;
			$division->name = $name;
			$division->urlName = $urlName;
			$division->matchStyle = $matchStyle;
			$division->sequence = ++$divisionSequence;
			$division->breakdown = $breakdown;
			$division->format = $format;
			$division->requireGrade = $requireGrade;
			
			// create teams
			$teamsByName = [];

			foreach ($divisionNode->team as $teamNode) {
				$clubName = readString($teamNode['club'], 'Missing club');
				$club = @$clubsByName[$clubName];
				if (!$club) {
					$clubsByName[$clubName] = $club = Club::loadByName($clubName);
				}
				
				$sequence = readInt($teamNode['sequence'], 'Invalid sequence');
				$name = readString($teamNode['name'], 'Missing team name');
			
				$team = new Team();
				$team->division = $division;
				$team->club = $club;
				$team->sequence = $sequence;
				$team->name = $name;
				
				$teamsByName[$name] = $team;
				$division->teams[] = $team;
			}
			
			// create rounds
			$roundSequence = 0;
			
			foreach ($divisionNode->round as $roundNode) {
				// these attributes are optional
				$name = $roundNode['name'];
				$urlName = $roundNode['url-name'];
				
				$round = new Round();
				$round->division = $division;
				$round->sequence = ++$roundSequence;
				$round->urlName = $urlName;
				$round->name = $name;
				
				$division->rounds[] = $round;

				foreach ($roundNode->fixture as $fixtureNode) {
					$teamName = readString($fixtureNode['home'], 'Missing home team');
					$homeTeam = @$teamsByName[$teamName];
					if (!$homeTeam) throw new ReportableException('Team not found: ' . $teamName);

					$teamName = readString($fixtureNode['away'], 'Missing away team');
					$awayTeam = @$teamsByName[$teamName];
					if (!$awayTeam) throw new ReportableException('Team not found: ' . $teamName);
					
					$fixtureDate = strtotime((string) $fixtureNode['date']);
					
					$fixture = new Fixture();
					$fixture->round = $round;
					$fixture->homeTeam = $homeTeam;
					$fixture->awayTeam = $awayTeam;
					$fixture->date = $fixtureDate;
					
					$round->fixtures[] = $fixture;
				}
			}
			
			$divisionsByName[$division->name] = $division;
			$divisions[] = $division;
		}
		
		foreach ($divisions as $div) $div->recursiveSave();
		$Database->commit();
		
	} catch (Exception $ex) {
		$Database->rollBack();
		throw $ex;
	}
}
?>