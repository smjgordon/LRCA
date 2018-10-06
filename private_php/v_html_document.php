<?php
require_once 'm_club.php';

function showDocumentIndex($documents) {
	global $UriBase;
	
	if (!empty($documents)) {
	?>
		<ul>
			<?php foreach ($documents as $doc) { ?>
				<li><a href="<?php echo $UriBase; ?>docs/<?php echo htmlSpecialChars($doc->fileName()); ?>"
					><?php echo htmlspecialchars($doc->displayName()); ?></a></li>
			<?php } ?>
		</ul>
	<?php
	}
}
?>