<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_section.php';
require_once 'private_php/p_html_functions.php';
require_once 'private_php/u_text.php';

$playerID = @$_GET['pid'];
if (!is_numeric($playerID)) errorPage(404);
$playerID = (int) $playerID;

$stmt = $Database->prepare('
	SELECT p.forename, p.surname, c.club_id, c.name AS club
	FROM player p JOIN club c ON p.club_id = c.club_id
	WHERE p.player_id = ?
	');
$stmt->execute([$playerID]);
$player = $stmt->fetch();
if (!$player) errorPage(404);

$fullName = $player['forename'] . ' ' . $player['surname'];
pageHeader($fullName . ' – Player Profile');
?>
	<h2><?php echo htmlspecialchars($fullName); ?></h2>
	<h3 class="sub"><?php echo htmlspecialchars($player['club']); ?></h3>

<?php
// get all-time totals for the player
// stupidity: you can't use a named parameter multiple times in a query
// we've already made sure $playerID is an int
$stmt = $Database->query("
	SELECT
		Sum(CASE WHEN (home_player_id = $playerID AND raw_result = 1) OR (away_player_id = $playerID AND raw_result = -1) THEN 1 ELSE 0 END) AS won,
		Sum(CASE WHEN raw_result = 0 THEN 1 ELSE 0 END) AS drawn,
		Sum(CASE WHEN (home_player_id = $playerID AND raw_result = -1) OR (away_player_id = $playerID AND raw_result = 1) THEN 1 ELSE 0 END) AS lost
	FROM game
	WHERE $playerID IN (home_player_id, away_player_id)
		AND 2 NOT IN (home_player_id, away_player_id)");
$row = $stmt->fetch();

// performance table
if ($row && is_numeric($row['won'])) {
?>
	<h3>Performance</h3>
	<table class="performance">
		<thead>
			<tr>
				<th scope="col">Section</th>
				<th scope="col">Played</th>
				<th scope="col">Won</th>
				<th scope="col">Drawn</th>
				<th scope="col">Lost</th>
			</tr>
		</thead>
		<tfoot>
				<tr>
					<th scope="row">Total</th>
					<th scope="col"><?php echo $row['won'] + $row['drawn'] + $row['lost']; ?></th>
					<th scope="col"><?php echo $row['won']; ?></th>
					<th scope="col"><?php echo $row['drawn']; ?></th>
					<th scope="col"><?php echo $row['lost']; ?></th>
				</tr>
		</tfoot>
		<tbody><?php
			$stmt = $Database->query("
				SELECT d.year, s.section_id,
					Sum(CASE WHEN (home_player_id = $playerID AND g.raw_result = 1) OR (away_player_id = $playerID AND g.raw_result = -1) THEN 1 ELSE 0 END) AS won,
					Sum(CASE WHEN g.raw_result = 0 THEN 1 ELSE 0 END) AS drawn,
					Sum(CASE WHEN (home_player_id = $playerID AND g.raw_result = -1) OR (away_player_id = $playerID AND g.raw_result = 1) THEN 1 ELSE 0 END) AS lost
				FROM game g
					JOIN fixture f ON g.fixture_id = f.fixture_id
					JOIN round r ON f.round_id = r.round_id
					JOIN division d ON r.division_id = d.division_id
					JOIN section s ON d.section_id = s.section_id
				WHERE $playerID IN (g.home_player_id, away_player_id)
					AND 2 NOT IN (g.home_player_id, away_player_id)
				GROUP BY d.year, s.section_id
				ORDER BY d.year DESC, s.season, s.sequence");

			while ($row = $stmt->fetch()) {
			?>
				<tr>
					<td><?php echo (new Section($row['section_id'], $row['year']))->displayName(); ?></td>
					<td><?php echo $row['won'] + $row['drawn'] + $row['lost']; ?></td>
					<td><?php echo $row['won']; ?></td>
					<td><?php echo $row['drawn']; ?></td>
					<td><?php echo $row['lost']; ?></td>
				</tr>
			<?php
			}
		?></tbody>
	</table>
<?php
}

// game history
$stmt = $Database->prepare("
	SELECT
		f.fixture_date, g.home_colour, g.raw_result, g.home_grade, g.away_grade,
		Concat(hp.surname, Coalesce(Concat(', ', NullIf(hp.forename, '')), '')) AS home_player,
		Concat(ap.surname, Coalesce(Concat(', ', NullIf(ap.forename, '')), '')) AS away_player
	FROM game g
		JOIN fixture f ON g.fixture_id = f.fixture_id
		JOIN player hp ON g.home_player_id = hp.player_id
		JOIN player ap ON g.away_player_id = ap.player_id
	WHERE ? IN (g.home_player_id, away_player_id)
		AND 2 NOT IN (g.home_player_id, away_player_id)
	ORDER BY f.fixture_date DESC, g.fixture_id DESC, g.home_colour DESC");
$stmt->execute([$playerID]);

$game = $stmt->fetch();
if ($game) {
?>
	<h3>Game History</h3>

	<table class="result">
		<col class="date"/><col class="colour"/><col class="grade"/><col class="name"/>
		<col class="homeScore"/><col class="dash"/><col class="awayScore"/><col class="name"/><col class="grade"/>
		<?php do { ?>
			<tr>
				<td class="date"><?php echo formatDate(strtotime($game['fixture_date'])); ?></td>
				<td class="colour"><?php echo $game['home_colour']; ?></td>
				<td class="grade"><?php echo $game['home_grade']; ?></td>
				<td class="name"><?php echo $game['home_player']; ?></td>
				<?php formatGameResult($game['raw_result'], false, false); ?>
				<td class="name"><?php echo $game['away_player']; ?></td>
				<td class="grade"><?php echo $game['away_grade']; ?></td>
			</tr>
		<?php } while ($game = $stmt->fetch()); ?>
	</table>
	
	<p>Note: The actual over-the-board result of each game is displayed here, ignoring any penalties applied.&nbsp; Board defaults are not shown.</p>
<?php
} else {
?>
	<p>No games are recorded for this player.</p>
<?php
}

pageFooter();
?>