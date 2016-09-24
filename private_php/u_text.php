<?php
function padLeft($string, $width) {
	echo "[$string $width ", mb_strlen($string), ' ', strlen($string), ']';
	return str_pad($string, $width - mb_strlen($string, 'utf-8') + strlen($string), ' ', STR_PAD_LEFT);
}

function padRight($string, $width) {
	return str_pad($string, $width - mb_strlen($string, 'utf-8') + strlen($string));
}
?>