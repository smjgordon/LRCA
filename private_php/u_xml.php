<?php
require_once 'p_exceptions.php';

abstract class XmlUtils {
	public static function readString($attribute, $exceptionMessage) {
		$value = (string) $attribute;
		if ($value == '') throw new ReportableException($exceptionMessage);
		return $value;
	}

	public static function readInt($attribute, $exceptionMessage) {
		$value = (string) $attribute;
		if (!is_numeric($value)) throw new ReportableException($exceptionMessage);
		return (int) $value;
	}

	public static function readDate($attribute, $exceptionMessage) {
		$dateStr = (string) $attribute;
		if ($dateStr == '') throw new ReportableException($exceptionMessage);

		if (preg_match('/^[12][09][0-9][0-9]-[01][0-9]-[0123][0-9]$/', $dateStr)) {
			$value = strtotime($dateStr);
			// strtotime accepts some nonsensical dates, so converting back to check it's valid
			if ($dateStr != date('Y-m-d', $value)) $value = null;
		}
		if (!$value) throw new ReportableException($exceptionMessage);
		return $value;
	}
}
?>