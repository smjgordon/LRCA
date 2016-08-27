<?php
// TODO: implement new naming convention
function generateKey($length) {
	$rawKey = openssl_random_pseudo_bytes((integer) (($length * 3 + 3) / 4));
	// convert to URL-friendly key
	return rtrim(strtr(base64_encode($rawKey), '+/', '-_'), '=');
}
?>