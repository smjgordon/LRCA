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
}
?>