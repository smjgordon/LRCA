<?php
require_once 'private_php/p_global.php';
pageHeader('Archive');
?>

<h2>Archive</h2>
<?php
$stmt = $Database->query('
	SELECT DISTINCT d.year, s.section_id, s.name, s.season, s.url_name
	FROM division d
		JOIN section s ON d.section_id = s.section_id
	ORDER BY s.sequence, d.year');

$lastSectionName = null;
$inList = false;

while ($row = $stmt->fetch()) {
	// first, see if we're starting a new section
	$year = $row['year'];
	$sectionID = $row['section_id'];
	$sectionName = $row['name'];
	$season = $row['season'];

	if ($sectionName != $lastSectionName) {
		if ($inList) {
			echo '</ul>';
			$inList = false;
		}
		echo '<h3>', htmlspecialchars($sectionName), '</h3>';
		$lastSectionName = $sectionName;
	}
	if (!$inList) {
		echo '<ul>';
		$inList = true;
	}

	// now display the list entry
	switch ($season) {
		case Season::Winter:
			$displayYear = $year . '–' . ($year + 1);
			break;
		case Season::Summer:
			$displayYear = $year;
	}
?>
	<li>
		<a href="../<?php echo $year; ?>/<?php echo $row['url_name']; ?>"><?php echo $displayYear; ?></a>
	</li>
<?php
}

if ($inList) echo '</ul>';
?>
	<h3>Previous LRCA Websites</h3>
	<ul>
		<li><a href="http://legacy.leicestershirechess.org/2011-18/">2011-18 site</a></li>
		<li><a href="http://legacy.leicestershirechess.org/2001-11/">2001-11 site</a></li>
	</ul>

<?php
pageFooter();
?>