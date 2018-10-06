<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_news.php';
pageHeader('Links – Leicestershire and Rutland Chess Association');
?>

<!--<p class="devNotice">At the moment, this website is almost ready for launch.&nbsp; League fixtures have now been loaded in ready for the start of the new season, and the 2016 Summer Cup Data is complete with the exception of the Harrod Cup.</p>-->

<div id="subNav"><ul>
	<li><a href="./">Home</a></li
	><li><a href="about">About Us</a></li
	><li><a href="committee/principal">Committees</a></li
	><li><a href="https://www.leicestershirejuniorchess.com/">Juniors</a></li
	><li><a href="links">Links</a></li
	><li><a href="https://www.eventbrite.co.uk/e/leicester-he-atkins-memorial-rapidplay-sunday-7th-october-2018-tickets-46358564715">Atkins Rapidplay</a></li>
</ul></div>

<div id="subBody">
<h2>Links</h2>
<?php
$stmt = $Database->query('SELECT * FROM link WHERE status = 1 ORDER BY sequence');
while (!!($row = $stmt->fetch())) {
?>	<h3><a href="<?php echo htmlspecialchars($row['uri']); ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
	<p><?php echo htmlspecialchars($row['description']); ?></p>
<?php
}
?>
</div>

<?php
pageFooter();
?>