<?php
// TODO: implement new naming convention
function formatGameResult($result, $homeDefault, $awayDefault) {
	switch ($result) {
		case GameResult::HomeWin: // 1-0
			echo '<td class="homeScore">1</td><td class="dash">–</td>';
			echo $awayDefault ? '<td class="awayScore">0d</td>' : '<td class="awayScore">0</td>';
			break;
		case GameResult::Draw: // ½-½
			echo '<td class="homeScore">½</td><td class="dash">–</td><td class="awayScore">½</td>';
			break;
		case GameResult::AwayWin: // 0-1
			echo $homeDefault ? '<td class="homeScore">d0</td>' : '<td class="homeScore">0</td>';
			echo '<td class="dash">–</td><td class="awayScore">1</td>';
			break;
		case GameResult::DoubleDefault: // 0-0
			echo '<td class="homeScore">d0</td><td class="dash">–</td><td class="awayScore">0d</td>';
	}
}

function monthNameFromIso($date) {
	$month = (integer) substr($date, 5, 2);
	return ['January', 'February', 'March', 'April', 'May', 'June',
		'July', 'August', 'September', 'October', 'November', 'December']
		[$month - 1];
}

function renderSelectOption($value, $selectedValue, $displayText) {
	echo '<option value="', $value, '"';
	if ($value == $selectedValue) echo ' selected="selected"';
	echo '>', htmlspecialchars($displayText), '</option>';
}

function carryForwardPostData($exceptions = []) {
	foreach ($_POST as $key => $value) {
		if (!in_array($key, $exceptions)) {
		?>	<input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>" />
		<?php
		}
	}
}

function encodeMultiLine($string) {
	return str_replace("\n", '<br/>', htmlspecialchars($string));
}

function encodeMultiLineArray($strings) {
	$result = '';
	foreach ($strings as $string) {
		if ($result != '') $result .= '<br/>';
		$result .= htmlspecialchars($string);
	}
	return $result;
}

function carryForwardReferrer() {
	$referrer = @$_REQUEST['referrer'] or $referrer = @$_SERVER['HTTP_REFERER'];
	$uriPrefix = 'http://' . $_SERVER['HTTP_HOST'] . '/';
	if ($referrer && substr($referrer, 0, strlen($uriPrefix)) == $uriPrefix) {
	?>	<input type="hidden" name="referrer" value="<?php echo htmlspecialchars($referrer); ?>" />
	<?php
	}
}
?>