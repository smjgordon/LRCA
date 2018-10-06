<?php
// TODO: implement new naming convention
function getCurrentUri() {
	$scheme = $_SERVER['REQUEST_SCHEME'];
	if (!$scheme) $scheme = 'http'; // not sure why $_SERVER['REQUEST_SCHEME'] doesn't always work
	
	return "$scheme://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

function backToLevel($level) {
	global $UriBase;
	
	$backLevels = substr_count($_SERVER['REQUEST_URI'], '/') - substr_count($UriBase, '/') - $level;
	return str_repeat('../', $backLevels);
}

function joinUri($from, $to) {
	$parsedUri = parse_url(trim($to));
	$to = @$parsedUri['path'];

	if ($to == null) {
		$result = substr($from, 0, strpos($from, '?')) or $result = $from;
	} else if ($to[0] == '/') {
		$result = $to;
	} else {
		if ($to == '.' || $to == '..' || substr($to, -2) == '/.' || substr($to, -3) == '/..') $to .= '/';

		$result = substr($from, 0, strrpos($from, '/')) . '/' . $to;
		while (substr($result, 0, 2) == './') {
			$result = substr($result, 2);
		}
		while (strpos($result, '/./') !== false) {
			$result = str_replace('/./', '/', $result);
		}
		while (($pos = strpos($result, '/../')) !== false) {
			$posUp = strrpos(substr($result, 0, $pos), '/');
			if ($posUp === false) { // dir/../ (shouldn't ever happen, but just in case)
				$result = substr($result, $pos + 4);
			} else { // ..../dir/../
				$result = substr($result, 0, $posUp) . substr($result, $pos + 3);
			}
		}
	}
	if (!empty($parsedUri['query'])) $result .= '?' . $parsedUri['query'];
	if ($result == $from) return null;
	return $result;
}

function redirect($statusCode, $uri) {
	http_response_code($statusCode);
	header('Location: ' . joinUri(getCurrentUri(), $uri));
	exit;
}
?>