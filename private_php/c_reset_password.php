<?php
require_once 'm_user.php';
require_once 'p_exceptions.php';

class ResetPasswordController {
	public function __construct() {
		$this->_requestMethod = $_SERVER['REQUEST_METHOD'];
		
		$requestParams = array_merge($_GET, $_POST);
		$this->_email = trim(@$requestParams['email']);
		$this->_userId = @$requestParams['uid'];
		$this->_resetKey = @$requestParams['key'];		

		if ($this->_resetKey) $this->_userFromKey   = $this->_resetKey->user();
		if ($this->_userId)   $this->_userFromId    = User::loadById($this->_userId);
		if ($this->_email)    $this->_userFromEmail = User::loadByEmail($this->_email);
	}

	public function process() {
		if ($_requestMethod == 'POST') {
			if (!$this->_email) throw new UserInputException(UserInputException::MissingOwnEmail);
		}
	}
	
	private $_requestMethod, $_email, $_userId, $_resetKey, $_userFromKey, $_userFromId, $_userFromEmail;
}
?>