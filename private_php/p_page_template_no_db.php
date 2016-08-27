<?php
function pageHeaderNoDb($title) {
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
			><li><a href="archive.php">Archive</a></li
			><li><a href="http://www.lrca.org.uk/">Main LRCA Website</a></li>
		</ul>
	</div>
	<div id="mainBody">
<?php
}

function pageFooterNoDb() {
?>
	</div>
	</body></html>
<?php
}
?>