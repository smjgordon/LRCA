<?php
require_once 'private_php/p_global.php';
require_once 'private_php/m_declared_player_list.php';
require_once 'private_php/v_html_club.php';
require_once 'private_php/v_html_contact.php';
require_once 'private_php/v_html_section.php';
require_once 'private_php/p_html_functions.php';
require_once 'private_php/c_captcha.php';

try {
	$list = DeclaredPlayerList::loadByUri($_SERVER['REQUEST_URI']);
	$club = $list->club();
	//$club = Club::loadByUri($_SERVER['REQUEST_URI']);
} catch (Exception $ex) {
	errorPage(HttpStatus::NotFound);
}

pageHeader($club->name() . ' – Declared Team Players');
?>

<div id="subNav">
	<?php echo clubNavBar(); ?>
</div>

<div id="subBody">
	<h2><?php echo htmlspecialchars($club->name()); ?> Declared Team Players</h2>
	<h3 class="sub">Effective from <?php
		echo formatDate($list->startDate());
		if ($list->endDate()) {
			echo ' to ', formatDate($list->endDate() - 3 * 86400);
		}
	?></h3>

	<table class="players">
		<col class="team" />
		<col class="cat" />
		<col class="name" />
		<col class="grade" />
		<thead>
			<tr>
				<th>Team</th>
				<th>Category</th>
				<th>Name</th>
				<th>Grade</th>
			</tr>
		</thead>
		<tbody>
	<?php
		$sections = Section::loadAllInProgress();
		$anyData = false;

		// TODO: optimise and MVC-separate this
		$stmt = $Database->prepare("
			SELECT Concat(p.surname, ', ', p.forename) AS name, dp.rank, t.name AS team_name, dp.category, p.forename, p.surname,
				(
					SELECT grade FROM grade g
					WHERE p.player_id = g.player_id
						AND s.season = g.season
						AND g.effective_from <= ?
						AND g.type = 0
					ORDER BY g.effective_from DESC
					LIMIT 1
				) AS grade
			FROM section s
				JOIN section_year sy ON s.section_id = sy.section_id
				JOIN declared_player_list dpl ON sy.section_year_id = dpl.section_year_id
				JOIN declared_player dp ON dpl.list_id = dp.list_id
				JOIN team t ON dp.team_id = t.team_id
				JOIN player p ON dp.player_id = p.player_id
				-- JOIN grade g ON p.player_id = g.player_id
			WHERE sy.year = ? AND sy.section_id = ? AND sy.barring_system = 3
				AND dpl.club_id = ? and dpl.list_id = ?
			ORDER BY dp.rank;");

		foreach ($sections as $section) {
			$stmt->execute([date('c'), $section->year(), $section->id(), $club->id(), $list->id()]);
			while (!!($row = $stmt->fetch())) {
				$anyData = true;
			?>
				<tr>
					<td><?php echo htmlspecialchars($row['team_name']); ?></td>
					<td class="cat"><?php echo htmlspecialchars($row['category']); ?></td>
					<td><?php echo htmlspecialchars($row['name']); ?></td>
					<td class="grade"><?php echo htmlspecialchars($row['grade']); ?></td>
				</tr>
			<?php
			}
		}
		if (!$anyData) {
		?>
			<tr>
				<td colspan="4">To be confirmed.</td>
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