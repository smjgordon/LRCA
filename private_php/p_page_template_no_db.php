<?php
require_once 'p_server_no_db.php';

function pageHeaderNoDb($title) {
	global $UriBase;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml"><head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php echo htmlspecialchars($title); ?></title>
	<link rel="shortcut icon" href="<?php echo $UriBase; ?>favicon.png" />
	<link rel="stylesheet" href="<?php echo $UriBase; ?>main.css" />
	<link rel="stylesheet" href="<?php echo $UriBase; ?>desktop.css" media="(min-width: 70em)" />
	<link rel="stylesheet" href="<?php echo $UriBase; ?>tablet.css" media="(min-width: 30em) and (max-width: 69.999em)" />
	<link rel="stylesheet" href="<?php echo $UriBase; ?>mobile.css" media="(max-width: 29.999em)" />
	<script type="text/javascript" src="<?php echo $UriBase; ?>menu.js"></script>
	</head>

	<body>
	<div id="head">
	<h1>Leicestershire &amp; Rutland Chess Association</h1>

	<div id="mainNav">
		<ul id="mainNav1"><?php
			echo headerLinkNoDb('Home', '', 'home',
				headerLinkNoDb('Home Page', '') .
				headerLinkNoDb('About Us', 'about') .
				headerLinkNoDb('Committees', 'committee/principal') .
				headerLinkNoDb('Juniors', 'http://www.leicestershirejuniorchess.com/') .
				headerLinkNoDb('Links', 'links'));
			echo headerLinkNoDb('Clubs', 'clubs/');
			/*echo headerLinkNoDb('Players', 'players/', 'players',
				headerLinkNoDb('Players', 'players/') .
				headerLinkNoDb('Barred Players', 'players/barred'));*/
			echo headerLinkNoDb('Players', 'players/');
			echo headerLinkNoDb('Archive', 'archive/');
		?></ul>
	</div>
	</div>
	<div id="mainBody">
<?php
}

function headerLinkNoDb($title, $url, $submenuName = null, $children = null) {
	global $UriBase;

	if (strpos($url, ':') === FALSE) $url = $UriBase . $url;

	$result = '<li><a href="' . htmlspecialchars($url) . '"';
	if ($submenuName) {
		$result .= ' class="dropdown" onclick="return showMenu(\'' . $submenuName . '\')"';
	}
	$result .= '>';
	$result .= htmlspecialchars($title);
	$result .= '</a>';
	if ($submenuName) $result .= '<ul id="' . $submenuName . '">' . $children . '</ul>';
	$result .= '</li>';

	return $result;
}

function pageFooterNoDb() {
?>
	</div>
	</body></html>
<?php
}
?>