<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_news.php';

try {
	$feed = NewsFeed::loadByUri($_SERVER['REQUEST_URI']);
} catch (ModelAccessException $ex) {
	errorPage(HttpStatus::NotFound);
}

pageHeader($feed->name() . ' – Leicestershire and Rutland Chess Association');
?>

<!--<p class="devNotice">At the moment, this website is almost ready for launch.&nbsp; League fixtures have now been loaded in ready for the start of the new season, and the 2016 Summer Cup Data is complete with the exception of the Harrod Cup.</p>-->

<div id="subNav"><ul><?php
	foreach (NewsFeed::loadAll() as $loopFeed) {
		echo '<li><a href="', $loopFeed->urlName(), '">', htmlspecialchars($loopFeed->name()), '</a></li>';
	}
	unset($loopFeed);
?></ul></div>

<div id="subBody">
<h2><?php echo htmlspecialchars($feed->name()); ?></h2>

<?php
$anyNews = false;
foreach ($newsPosts = NewsPost::loadByFeed($feed) as $post) {
	$anyNews = true;
	$view = new HtmlNewsPostView($post);
	echo $view->fullPost();
}

if (!$anyNews) {
	echo '<p>No news yet....</p>';
}
?>
</div>

<?php
pageFooter();
?>