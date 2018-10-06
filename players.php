<?php
// TODO: refactor
require_once 'private_php/p_global.php';
require_once 'private_php/p_html_functions.php';

abstract class GradeType {
	const Standard = 0;
	const Rapid = 1;
	const LrcaRapid = 2;
}

$clubID = @$_GET['cid'];
// make sure the club ID exists, and get the name of the club
if ($clubID) {
	if (!is_numeric($clubID)) errorPage(404);
	$clubID = (int) $clubID;
	$stmt = $Database->prepare('SELECT name FROM club WHERE club_id = ?');
	$stmt->execute([$clubID]);
	if (!!($row = $stmt->fetch())) {
		$pageTitle = $row['name'] . ' Player List';
	} else {
		errorPage(404);
	}
} else {
	$pageTitle = 'Player List';
}

if (isset($_GET['ps'])) {
	$playerStatusFilter = $_GET['ps'];
	// string must be a comma-separated list containing only 0/1/2
	if (!preg_match('/^[0-2](,[0-2])*$/', $playerStatusFilter)) errorPage(404);
} else {
	// default to active only
	$playerStatusFilter = '1';
}

$season = @(int) $_GET['season'];

if (!($season == Season::Winter || $season == Season::Summer)) {
	switch (SystemSettings::$currentSeason) {
		case Season::BothDefaultWinter: $season = Season::Winter; break;
		case Season::BothDefaultSummer: $season = Season::Summer; break;
		default: $season = SystemSettings::$currentSeason;
	}
}
$chooseSeason
	= SystemSettings::$currentSeason == Season::BothDefaultWinter
	|| SystemSettings::$currentSeason == Season::BothDefaultSummer;

pageHeader($pageTitle);
?>

<div id="subNav">
	<!--<ul><li><a href="barred">Barred Players</a></li></ul>-->
	
	<form method="get" action="./">
		<p>Club:<br />
			<select name="cid"><?php
				renderSelectOption('', $clubID, 'All');
				$stmt = $Database->query('SELECT club_id, name FROM club WHERE status = 1 ORDER BY name');
				while (!!($row = $stmt->fetch())) {
					renderSelectOption($row['club_id'], $clubID, $row['name']);
				}
			?></select>
		</p>

		<p>Players to show:<br />
			<select name="ps"><?php
				renderSelectOption('0,1,2', $playerStatusFilter, 'Active and Inactive');
				renderSelectOption('1', $playerStatusFilter, 'Active Only');
				renderSelectOption('0,2', $playerStatusFilter, 'Inactive Only');
			?></select>
		</p>

		<?php if ($chooseSeason) { ?>
			<p>Grades to show:<br />
				<select name="season"><?php
					if (SystemSettings::$summerYear <= SystemSettings::$winterYear) {
						renderSelectOption(Season::Summer, $season, 'January ' . SystemSettings::$summerYear);
						renderSelectOption(Season::Winter, $season, 'July ' . SystemSettings::$winterYear);
					} else {
						renderSelectOption(Season::Winter, $season, 'July ' . SystemSettings::$winterYear);
						renderSelectOption(Season::Summer, $season, 'January ' . SystemSettings::$summerYear);
					}
				?></select>
			</p>
		<?php } ?>

		<p><input type="submit" value="Filter" /></p>
	</form>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($pageTitle); ?></h2>
	<h3 class="sub">With <?php
		switch ($season) {
			case Season::Winter:
				echo 'July ', SystemSettings::$winterYear;
				break;
			case Season::Summer:
				echo 'January ', SystemSettings::$summerYear;
				break;
		}
	?> Grades</h3>

	<table class="players">
		<colgroup><col class="ecfCode" /></colgroup>
		<colgroup><col class="name" /></colgroup>
		<?php if (!$clubID) { ?>
			<colgroup><col class="club" /></colgroup>
		<?php } ?>
		<colgroup><col class="grade" /><col class="cat" /></colgroup>
		<colgroup><col class="grade" /><col class="cat" /></colgroup>
		<colgroup><col class="grade" /></colgroup>
		<thead>
			<tr>
				<th rowspan="2">ECF Code</th>
				<th rowspan="2">Name</th>
				<?php if (!$clubID) { ?>
					<th rowspan="2">Club</th>
				<?php } ?>
				<th colspan="2">Standard</th>
				<th colspan="2">Rapid</th>
				<th>LRCA Rapid</th>
			</tr>
			<tr>
				<th>Grade</th>
				<th>Cat</th>
				<th>Grade</th>
				<th>Cat</th>
				<th>Grade</th>
			</tr>
		</thead>
		<tbody><?php
			$sql = "
				SELECT p.player_id, p.forename, p.surname, p.ecf_grading_code, c.name AS club_name
				FROM player p JOIN club c ON p.club_id = c.club_id
				WHERE p.status IN ($playerStatusFilter)";

			if ($clubID) {
				$sql .= " AND c.club_id = $clubID"; // this is safe, as $clubID has been forced to integer type
			}

			$sql .= ' ORDER BY p.surname, p.forename';

			$stmtPlayer = $Database->query($sql);

			// OPTIMISE: cut number of calls to db
			$stmtGrade = $Database->prepare('
				SELECT g1.type, g1.effective_from, NullIf(g1.grade, 0) AS grade, g1.category
				FROM grade g1
					LEFT JOIN grade g2
						ON g1.player_id = g2.player_id
							AND g1.season = g2.season
							AND g1.type = g2.type
							AND g1.effective_from < g2.effective_from
							AND g2.effective_from <= ?
				WHERE g1.effective_from <= ?
					AND g1.player_id = ? AND g1.season = ?
					AND g2.grade_id IS NULL');

			$today = date('c');
			
			while ($player = $stmtPlayer->fetch()) {
				$stmtGrade->execute([$today, $today, $player['player_id'], $season]);
				$grade = $stmtGrade->fetch();
			?>
				<tr>
					<td><?php
						$ecfCode = $player['ecf_grading_code'];
						if ($ecfCode) {
							echo "<a href='http://www.ecfgrading.org.uk/new/player.php?PlayerCode=$ecfCode'>$ecfCode</a>";
						}
					?></td>
					<td>
						<a href="<?php echo $player['player_id']; ?>">
							<?php echo htmlspecialchars($player['surname']), ', ', htmlspecialchars($player['forename']); ?>
						</a>
					</td>
				<?php
					if (!$clubID) {
					?>
						<td><?php echo htmlspecialchars($player['club_name']); ?></td>
					<?php
					}

					// render the player's grades
					if ($grade && $grade['type'] == GradeType::Standard) {
					?>
						<td class="grade"><?php echo $grade['grade']; ?></td>
						<td class="cat"><?php echo $grade['category']; ?></td>
					<?php
						$grade = $stmtGrade->fetch();
					} else {
					?>
						<td class="grade"></td>
						<td class="cat"></td>
					<?php
					}
					if ($grade && $grade['type'] == GradeType::Rapid) {
					?>
						<td class="grade"><?php echo $grade['grade']; ?></td>
						<td class="cat"><?php echo $grade['category']; ?></td>
					<?php
						$grade = $stmtGrade->fetch();
					} else {
					?>
						<td class="grade"></td>
						<td class="cat"></td>
					<?php
					}
					if ($grade && $grade['type'] == GradeType::LrcaRapid) {
					?>
						<td class="grade"><?php echo $grade['grade']; ?></td>
					<?php
						$grade = $stmtGrade->fetch();
					} else {
					?>
						<td class="grade"></td>
					<?php
					}
				?>
				</tr>
			<?php
			}
		?>
		</tbody>
	</table>
</div>

<?php
pageFooter();
?>
