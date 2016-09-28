<?php
require_once 'm_session.php';

function pageHeader($title) {
	global $Database, $CurrentUser;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml"><head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php echo htmlspecialchars($title); ?></title>
	<link rel="stylesheet" href="main.css" />
	</head>

	<body>
	<h1>Leicestershire &amp; Rutland Chess Association</h1>

	<div id="mainNav">
		<ul id="mainNav1">
			<li><a href="./">Home</a></li
			><?php
				// TODO: maybe separate this BL out
				$stmt = $Database->query('SELECT section_id, name, season FROM section');
				while ($row = $stmt->fetch()) {
					$year = ($row['season'] == Season::Winter) ? SystemSettings::$winterYear : SystemSettings::$summerYear;
					echo "<li><a href='section.php?year=$year&amp;sid=$row[section_id]'>$row[name]</a></li>";
				}
			?><li><a href="players.php">Players</a></li
			><li><a href="barred_players.php">Barred Players</a></li
			><li><a href="archive.php">Archive</a></li
			><li><a href="http://www.lrca.org.uk/">Main LRCA Website</a></li>
		</ul>
		<ul id="mainNav2">
			<?php if ($CurrentUser) { ?>
				<li class="loggedInUser"><?php echo $CurrentUser->fullName(), ' – ', $CurrentUser->clubName(); ?></li
				><?php
					if ($CurrentUser->hasPermission('can_submit')) {
						echo '<li><a href="my_fixtures.php">My Fixtures</a></li>';
					}
				?><li><a href="logout.php">Log Out</a></li>
			<?php } else { ?>
				<li><a href="login.php">Log In</a></li>
			<?php } ?>
		</ul>
	</div>
	<div id="mainBody">
<?php
}

function pageFooter() {
?>
	</div>
	</body></html>
<?php
}
?>