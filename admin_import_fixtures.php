<?php
require_once 'private_php/p_global.php';
require_once 'private_php/c_import_fixtures.php';

$error = null;
requireLogin(['administrator']);

if (isset($_FILES['file'])) {
	$filePath = $_FILES['file']['tmp_name'];
	if ($filePath) {
		$xml = file_get_contents($filePath);
		try {
			importFixtures($xml);
			redirect(303, 'admin_fixtures_imported.php');
		} catch (Exception $ex) {
			$error = $ex->getMessage();
		}
	}
}

pageHeader('Import Fixtures');
?>

<h2>Import Fixtures</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" enctype="multipart/form-data" method="post"
		action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<p><label for="file">Fixture file:</label> <input type="file" name="file" id="file" /></p>

	<p><input type="submit" value="Import" /></p>
</form>

<?php
pageFooter();
?>