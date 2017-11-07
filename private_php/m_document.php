<?php
require_once 'p_server.php';

class Document {
	public static function loadBySection($section) {
		global $Database;
		
		$stmt = $Database->prepare('
			SELECT d.document_id, d.file_name, d.display_name
			FROM section_document sd
				JOIN document d ON sd.document_id = d.document_id
			WHERE sd.year = ? AND sd.section_id = ?
			ORDER BY sd.sequence');
		$stmt->execute([$section->year(), $section->id()]);
		
		$result = [];
		while (!!($row = $stmt->fetch())) {
			$doc = new Document();
			$doc->populateFromDbRow($row);
			$result[] = $doc;
		}
		return $result;
	}
	
	function populateFromDbRow($row) {
		$this->_id = (int) $row['document_id'];
		$this->_fileName = $row['file_name'];
		$this->_displayName = $row['display_name'];
	}
	
	public function id() { return $this->_id; }
	public function fileName() { return $this->_fileName; }
	public function displayName() { return $this->_displayName; }
	
	private $_id, $_fileName, $_displayName;
}
?>