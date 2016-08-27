<?php
// Simple wrapper for when we don't need to construct the proper object, but just need its ID
// (it quacks like a duck, by returning an ID when asked)
class IdWrapper {
	public function __construct($id) { $this->_id = $id; }
	public function id() { return $_id; }
	private $_id;
}
?>