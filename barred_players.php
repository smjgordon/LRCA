<?php
// TODO: refactor
require_once 'private_php/p_global.php';
//require_once 'private_php/p_html_functions.php';

$stmtTeams = $Database->prepare('
	SELECT c.club_id, t.team_id, t.sequence, t.name
	FROM division d
		JOIN team t ON d.division_id = t.division_id
		JOIN club c ON t.club_id = c.club_id
	WHERE year = ? and section_id = ?
	ORDER BY c.name, t.sequence');
// TODO: find a better way of determining the section ID
$stmtTeams->execute([SystemSettings::$winterYear, 1]);
$lastTeam = $stmtTeams->fetch();

$stmtPlayers = $Database->prepare("
	SELECT Concat(p.forename, ' ', p.surname) AS name
	FROM barred_player bp
		JOIN player p ON bp.player_id = p.player_id
	WHERE bp.team_id = ?
	ORDER BY p.surname, p.forename");	

pageHeader('Barred Players');
?>

<h2>Barred Players</h2>

<table class="barred"><?php
	// we are looking a row ahead in the query, in order to filter out each club's lowest team
	while ($currentTeam = $stmtTeams->fetch()) {
		// this condition will fail when we have just loaded the first team of the next club
		if ($lastTeam['club_id'] == $currentTeam['club_id']) {
			$stmtPlayers->execute([$lastTeam['team_id']]);
			
		?>	<tr>
				<td><?php echo htmlspecialchars($lastTeam['name']); ?></td>
				<td><?php
					if ($player = $stmtPlayers->fetch()) {
						echo htmlspecialchars($player['name']);
						while ($player = $stmtPlayers->fetch()) echo '; ', htmlspecialchars($player['name']);
					} else {
						echo 'To be confirmed';
					}
				?></td>
			</tr>
		<?php
		}
		$lastTeam = $currentTeam;
	}
?></table>

<?php
pageFooter();
?>