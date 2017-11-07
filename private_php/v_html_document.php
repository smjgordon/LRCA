<?php
require_once 'm_club.php';

function showDocumentIndex($documents) {
	if (!empty($documents)) {
	?>
		<ul>
			<?php foreach ($documents as $doc) { ?>
				<li><a href="docs/<?php echo htmlSpecialChars($doc->fileName()); ?>"
					><?php echo htmlspecialchars($doc->displayName()); ?></a></li>
			<?php } ?>
		</ul>
	<?php
	}
}
?>