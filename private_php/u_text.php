<?php
function padLeft($string, $width) {
	return str_pad($string, $width - mb_strlen($string, 'utf-8') + strlen($string), ' ', STR_PAD_LEFT);
}

function padRight($string, $width) {
	return str_pad($string, $width - mb_strlen($string, 'utf-8') + strlen($string));
}

function formatScore($score) {
	if ($score == 0.5) return '½';
	return ((integer) $score) . (($score * 2) % 2 == 1 ? '½' : '');
}

function formatGrade($grade) {
	if (!$grade) return '?';
	return $grade;
}

function formatDate($timestamp, $withYear = true) {
	if ($timestamp) {
		return date($withYear ? 'd M Y' : 'd M', $timestamp);
	} else {
		return '';
	}
}

function parseDate($string) {
	$string = trim($string);
	if ($string == '') throw new ReportableException('Missing date');

	$validFormat
		= preg_match('/^20[0-9][0-9]-[01][0-9]-[0123][0-9]$/', $string)
		|| preg_match('/^[0123]?[0-9][- .,\/]*[A-Za-z][A-Za-z][A-Za-z][- .,\/]*20[0-9][0-9]$/', $string);
	$result = strtotime($string);

	if (!$validFormat || !$result) throw new ReportableException("Invalid date: $string");

	// now make sure it's *actually* valid
	$strippedInput = strtolower(strtr($string, [' ' => '', '-' => '', '/' => '', '.' => '', ',' => '']));
	if ($strippedInput != strtolower(date('dMY', $result))            // dd mmm yyyy
			&& $strippedInput != strtolower(date('jMY', $result))     // d mmm yyyy
			&& $strippedInput != strtolower(date('Ymd', $result))) {  // yyyy-mm-dd
		throw new ReportableException("Invalid date: $string");
	}
	return $result;
}
?>