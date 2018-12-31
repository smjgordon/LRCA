<?php
require_once 'private_php/p_global.php';
require_once 'private_php/p_html_functions.php';
require_once 'private_php/c_post_news.php';
require_once 'private_php/v_html_news.php';
requireLogin(['can_post_news']);

$controller = new PostNewsController();

$error = null;
try {
	$postBuilt = $controller->process();
} catch (UserInputException $ex) {
	$postBuilt = $ex->customData;
	$error = $ex->getMessage();
} catch (ModelAccessException $ex) {
	errorPage(HttpStatus::NotFound);
} catch (Exception $ex) {
	errorPage(HttpStatus::InternalError);
}

pageHeader('Post News');
?>

<h2>Post News</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>


<?php if ($postBuilt) { ?>
	<p>Please check the previews to make sure your article displays correctly.&nbsp;
	<?php if ($postBuilt->homepageText() != '' && $postBuilt->hasAttachment()) { ?>
		(The 'Full article' and attachment links will not work in this preview, but will work in the published article.)
	<?php } else if ($postBuilt->hasAttachment()) { ?>
		(The attachment links will not work in this preview, but will work in the published article.)
	<?php } else if ($postBuilt->homepageText() != '' && $postBuilt->detailText() != '') { ?>
		(The 'Full article' link will not work in this preview, but will work in the published article.)
	<?php } ?>
	If you have made a mistake, please use your browser's Back button to return to the posting form.&nbsp; If you are happy with your article, please <?php
		echo $controller->attachmentRequested() ? 'proceed to attach one or more files' : 'press Submit to complete the submission';
	?>.</p>

<?php
	$postView = new HtmlNewsPostView($postBuilt);
	if ($postBuilt->homepageText() != '') {
		echo '<h2>Home Page Preview</h2>';
		echo $postView->homePagePost();
	}
	echo '<h2>News Feed Page Preview</h2>';
	echo $postView->fullPost();
?>
	<form enctype="multipart/form-data" method="post" action="post">
		<?php if ($controller->attachmentRequested() || $error) { ?>
			<h2>Attachments</h2>
			<?php for ($i = 0; $i < 5; ++$i) { ?>
				<p><label>Display name: <input type="text" name="att<?php echo $i; ?>dname" maxlength="255"
					value="<?php echo htmlspecialchars(@$_POST["att$i" . 'dname']); ?>" /></label>
				<label>File: <input type="file" name="att<?php echo $i; ?>" /></label></p>
			<?php } ?>
			<p>
				<?php carryForwardPostData(['attach', 'att0dname', 'att1dname', 'att2dname', 'att3dname', 'att4dname']); ?>
				<input type="submit" value="Next" />
			</p>
		<?php } else { ?>
			<p><?php
				carryForwardPostData(['attach', 'att0dname', 'att1dname', 'att2dname', 'att3dname', 'att4dname']);
				$controller->carryForwardAttachments();
			?>	<input type="hidden" name="confirm" value="yes" />
				<input type="submit" value="Submit" />
			</p>
		<?php } ?>
	</form>

<?php } else { ?>
	<form enctype="multipart/form-data" method="post" action="post">
		<p><label for="fid">Please select the news feed to which you wish to post:</label>
			<select name="fid" id="fid"><?php
				renderSelectOption('', $controller->feedId(), '');
				foreach (NewsFeed::loadAll() as $feed) {
					renderSelectOption($feed->id(), $controller->feedId(), $feed->name());
				}
			?></select></p>
		<p><label for="title">Please enter a headline for the news article:</label><br />
			<input type="text" name="title" id="title" style="width: 100%;"
			maxlength="100" value="<?php echo htmlspecialchars($controller->title()); ?>" /></p>
		<p><label for="homepageText">Please enter the article text to displayed on the home page.&nbsp; If you leave this box blank, the article will not display on the home page.</label><br />
			<textarea style="width: 100%; height: 10em;" name="homepageText" id="homepageText"><?php
				echo htmlspecialchars($controller->homepageText());
		?></textarea></p>
		<p><label for="detailText">Optionally, please enter further article text, which will appear only on the news feed page (after the text in the above box).</label><br />
			<textarea style="width: 100%; height: 10em;" name="detailText" id="detailText"><?php
				echo htmlspecialchars($controller->detailText());
		?></textarea></p>

		<p><label><input type="checkbox" name="attach" /> Tick here if you wish to attach one or more files to the article.</label></p>

		<p><input type="submit" value="Next" /></p>
	</form>

	<h3>Formatting and Links</h3>
	<p>The following syntaxes can be used in the article text boxes.</p>
	<table>
		<tr>
			<th>Markup</th>
			<th>Effect</th>
		</tr>
		<tr>
			<td><code>__Emphasis__</code></td>
			<td><em>Emphasis</em></td>
		</tr>
		<tr>
			<td><code>**Strong emphasis**</code></td>
			<td><strong>Strong emphasis</strong></td>
		</tr>
		<tr>
			<td><code>[[http://www.englishchess.org.uk/|ECF website]]</code></td>
			<td><a href="http://www.englishchess.org.uk/">ECF website</a></td>
		</tr>
	</table>
<?php } ?>

<?php
pageFooter();
?>