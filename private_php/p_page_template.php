<?php
require_once 'm_news.php';
require_once 'm_session.php';
require_once 'm_section.php';

function pageHeader($title, $javascripts = null) {
	global $Database, $CurrentUser, $UriBase, $DevNotices;
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
<?php
	if ($javascripts) {
		foreach ($javascripts as $script) {
	?>		<script type="text/javascript" src="<?php echo $UriBase, htmlspecialchars($script); ?>"></script>
	<?php
		}
	}
?>
	</head>

	<body onclick="hideMenus()">
	<div id="head">
	<h1>Leicestershire and Rutland Chess Association</h1>
	<?php if ($CurrentUser) { ?>
		<div id="loggedInUser"><?php
			echo htmlspecialchars($CurrentUser->fullName() . ' – ' . $CurrentUser->club()->name());
		?></div>
	<?php } ?>
	<div id="mainNav">
		<ul id="mainNav1"><?php
			//echo headerLink('Home', '');
			echo headerLink('Home', '', 'home',
				headerLink('Home Page', '') .
				headerLink('About Us', 'about') .
				headerLink('Committees', 'committee/principal') .
				headerLink('Juniors', 'http://www.leicestershirejuniorchess.com/') .
				headerLink('Links', 'links'));

			// news feed links
			$feedLinks = '';

			foreach (NewsFeed::loadAll() as $feed) {
				if (!isset($firstNewsFeed)) $firstNewsFeed = $feed;
				$feedLinks .= headerLink($feed->name(), 'news/' . $feed->urlName());
			}

			echo headerLink('News', 'news/main', 'news', $feedLinks);
			echo headerLink('Clubs', 'clubs/');

			// section links
			$sections = Section::loadAllCurrent();
			foreach ($sections as $section) {
				$sectionUrl = $section->year() . '/' . $section->urlName() . '/';

				$documents = '';
				foreach ($section->documents() as $document) {
					$documents .= headerLink($document->displayName(), 'docs/' . $document->fileName());
				}
				if ($documents != '') $documents = headerLink('Documents', $sectionUrl, $section->urlName() . '.docs', $documents);

				$divisions = '';
				foreach ($section->divisions() as $division) {
					$divisions .= headerLink($division->name(), $sectionUrl . $division->urlName() . '/');
				}

				echo headerLink($section->name(), $sectionUrl, $section->urlName(), $documents . $divisions);
			}

			/*echo headerLink('Players', 'players/', 'players',
				headerLink('Players', 'players/') .
				headerLink('Barred Players', 'players/barred'));*/
			echo headerLink('Players', 'players/');
			echo headerLink('Archive', 'archive/');
		?></ul>
		<ul id="mainNav2"><?php
			if ($CurrentUser) {
				echo headerLink('Account', 'my_account/');
				if ($CurrentUser->hasPermission('can_post_news')) echo headerLink('Post News', 'news/post');
				if ($CurrentUser->hasPermission('can_submit')) echo headerLink('Fixtures', 'my_fixtures/');
				echo headerLink('Log Out', 'logout', null, true);
			} else {
				echo headerLink('Captain Login', 'login', null, true);
			}
		?></ul>
		<div style="clear: both;"></div>
	</div>
	</div>
	<div id="mainBody">
<?php
	$scriptName = array_slice(explode('/', $_SERVER['SCRIPT_NAME']), -1)[0];
	$baseName = explode('.', $scriptName)[0];
	if (isset($DevNotices[$baseName])) {
	?>
		<p class="devNotice"><?php echo $DevNotices[$baseName]; ?></p>
	<?php
	} else if (isset($DevNotices[''])) {
	?>
		<p class="devNotice"><?php echo $DevNotices['']; ?></p>
	<?php
	}
}

function headerLink($title, $uri, $submenuName = null, $children = null, $noFollow = false) {
	global $UriBase;

	if (strpos($uri, ':') === FALSE) $uri = $UriBase . $uri;

	$result = '<li><a href="' . htmlspecialchars($uri) . '"';
	if ($noFollow) $result .= ' rel="nofollow"';
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

function pageFooter() {
?>
	</div>
	</body></html>
<?php
}
?>