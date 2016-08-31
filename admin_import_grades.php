<?php
require_once 'private_php/p_global.php';
require_once 'private_php/c_import_grades.php';
require_once 'private_php/p_html_functions.php';

$error = null;
$effectiveDate = $effectiveDateStr = null;
$season = Season::Winter;
requireLogin(['administrator']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$effectiveDateStr = trim(@$_POST['efd']);
	if (!$effectiveDateStr) {
		$error = 'No effective date entered';
	} else {
		if (preg_match('/^20[0-9][0-9]-[01][0-9]-[0123][0-9]$/', $effectiveDateStr)) {
			$effectiveDate = strtotime($effectiveDateStr);
			// strtotime accepts some nonsensical dates, so converting back to check it's valid
			if ($effectiveDateStr != date('Y-m-d', $effectiveDate)) $effectiveDate = null;
		}
		if (!$effectiveDate) $error = 'Invalid effective date – please enter in ISO (yyyy-mm-dd) format';
	}

	$season = @$_POST['season'];
	if (!($season == Season::Winter || $season == Season::Summer)) {
		$error = 'No valid season selected';
	}

	if (!$error) {
		$filePath = @$_FILES['file']['tmp_name'];
		if ($filePath) {
			$xml = file_get_contents($filePath);
			try {
				importGrades($xml, $season, $effectiveDate);
				redirect(303, 'admin_grades_imported.php');
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		} else {
			$error = 'No file uploaded';
		}
	}
}

if ($effectiveDate) {
	$effectiveDateStr = date('Y-m-d', $effectiveDate);
}

pageHeader('Import Grades');
?>

<h2>Import Grades</h2>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form class="tabForm" enctype="multipart/form-data" method="post"
		action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<p><label for="file">Masterlist file:</label> <input type="file" name="file" id="file" /></p>
	<p><label for="season">Season:</label>
		<select name="season" id="season"><?php
			renderSelectOption(Season::Winter, $season, 'Winter');
			renderSelectOption(Season::Summer, $season, 'Summer');
		?></select>
	</p>
	<p><label for="efd">Effective from:</label>
		<input type="efd" name="efd" id="efd" value="<?php echo htmlspecialchars($effectiveDateStr); ?>" /></p>
	<!--<p><label for="file">Masterlist file:</label> <input type="file" name="file" id="file" /></p>-->

	<p><input type="submit" value="Import" /></p>
</form>

<?php
pageFooter();
?>