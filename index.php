<?php
require_once 'private_php/p_global.php';
require_once 'private_php/v_html_news.php';
pageHeader('Leicestershire and Rutland Chess Association');
?>

<div id="subNav"><ul>
	<li><a href="./">Home</a></li
	><li><a href="about">About Us</a></li
	><li><a href="committee/principal">Committees</a></li
	><li><a href="https://www.leicestershirejuniorchess.com/">Juniors</a></li
	><li><a href="links">Links</a></li
	><li><a href="https://www.eventbrite.co.uk/e/leicester-he-atkins-memorial-rapidplay-sunday-7th-october-2018-tickets-46358564715">Atkins Rapidplay</a></li>
</ul>
</div>

<div id="subBody">
<p>Leicestershire &amp; Rutland Chess Association is the body recognised by the <a href="http://www.englishchess.org.uk/">English Chess Federation</a> as being responsible for chess in the city of Leicester and the counties of Leicestershire and Rutland.&nbsp; The Association is affiliated to the <a href="http://www.mccu.org.uk/">Midland Counties Chess Union</a>.</p>

<h2>Latest News</h2>
<?php
$news = NewsPost::loadRecent(10);
foreach ($news as $post) {
	echo (new HtmlNewsPostView($post))->homePagePost();
}
?>
</div>

<?php
pageFooter();
?>