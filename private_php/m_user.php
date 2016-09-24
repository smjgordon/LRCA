<?php
// TODO: refactor
require_once 'p_server.php';
require_once 'p_enumerations.php';
require_once 'p_exceptions.php';
require_once 'p_security_functions.php';
require_once 'm_club.php';

class User {
	static public function loadByEmail($email) {
		global $Database;

		$email = trim(strtolower($email));
		$stmt = $Database->prepare('SELECT user_id FROM user WHERE Lower(email) = ? AND status <> 0');
		$stmt->execute([$email]);
		if ($row = $stmt->fetch()) {
			return new User($row['user_id']);
		} else {
			throw new ReportableException("Sorry, no user account has been found for email address $email.");
		}
	}

	public function __construct($userID) {
		global $Database;

		$userID = (integer) $userID;
		// list of fields that denote user permissions
		$permissionFields = ['administrator', 'can_submit', 'can_create_users'];

		$sql = '
			SELECT u.forename, u.surname, u.email, c.club_id, c.name AS club_name, u.status,
				u.' . implode(', u.', $permissionFields) . '
			FROM user u JOIN club c ON u.club_id = c.club_id
			WHERE user_id = ?';
		$stmt = $Database->prepare($sql);
		$stmt->execute([$userID]);

		if ($row = $stmt->fetch()) {
			$this->_id = $userID;
			$this->_forename = $row['forename'];
			$this->_surname = $row['surname'];
			$this->_email = $row['email'];
			$this->_clubID = $row['club_id'];
			$this->_club = Club::loadById($this->_clubID);
			$this->_clubName = $row['club_name'];
			$this->_status = $row['status'];

			$this->_permissions = [];
			foreach ($permissionFields as $perm) {
				if ($row[$perm] == 1) $this->_permissions[] = $perm;
			}

			//if ($row['administrator']
		} else {
			throw new Exception('User ID not found');
		}
	}

	public function id() { return $this->_id; }
	public function forename() { return $this->_forename; }
	public function surname() { return $this->_surname; }
	public function fullName() { return $this->_forename . ' ' . $this->_surname; }
	public function email() { return $this->_email; }
	public function status() { return $this->_status; }
	public function clubName() { return $this->_clubName; }
	public function club() { return $this->_club; }
	public function hasPermission($perm) { return in_array($perm, $this->_permissions); }

	public function generatePasswordResetKey($days) {
		global $Database;

		$resetKey = generateKey(24);
		$generationTime = time();
		$expiryTime = $generationTime + $days * 86400;

		$Database->beginTransaction();
		$stmt = $Database->prepare('
			INSERT INTO password_reset_key(reset_key, user_id, generated_date, expiry_date)
			VALUES(?, ?, ?, ?)');
		$stmt->execute([$resetKey, $this->_id, date('c', $generationTime), date('c', $expiryTime)]);
		$keyID = $Database->lastInsertId();

		// deactivate any previous password reset keys for the same user
		$stmt = $Database->prepare('
			UPDATE password_reset_key SET status = 3
			WHERE user_id = ? AND reset_key_id <> ? AND status = 0');
		$stmt->execute([$this->_id, $keyID]);

		$Database->commit();

		return $resetKey;
	}

	public function logIn($password) {
		global $Database;

		$stmt = $Database->prepare('SELECT hashed_password FROM user WHERE user_id = ?');
		$stmt->execute([$this->_id]);

		$row = $stmt->fetch();
		if (!$row) {
			throw new Exception('User ID not found');
		}
		if (password_verify($password, $row['hashed_password'])) {
			$loginTime = time();
			$loginTimeStr = date('c', $loginTime);
			$expiryTime = $loginTime + 86400; // session expires 24 hours from now

			// TODO: consider what to do with this - whether to support a persistent login (and if so, for how long)
			// or concurrent sessions for the same user
			$Database->beginTransaction();
			// first close any other sessions the user may have
			$stmt = $Database->prepare('
				UPDATE session SET status = 4, logout_date = ? WHERE user_id = ?');
			$stmt->execute([$loginTimeStr, $this->_id]);

			// now create the new session
			$sessionKey = generateKey(40);
			$stmt = $Database->prepare('
				INSERT INTO session(email, user_id, status, user_agent, ip_address, login_date, expiry_date, session_key)
				VALUES(?, ?, 1, ?, ?, ?, ?, ?)');

			$stmt->execute([$this->_email, $this->_id,
				substr($_SERVER['HTTP_USER_AGENT'], 0, 255), $_SERVER['REMOTE_ADDR'],
				$loginTimeStr, date('c', $expiryTime), $sessionKey]);

			$stmt = $Database->prepare('UPDATE user SET last_login = ? WHERE user_id = ?');
			$stmt->execute([$loginTimeStr, $this->_id]);
				
			$Database->commit();

			setcookie('session', $sessionKey);

		} else {
			$stmt = $Database->prepare('
				INSERT INTO session(email, user_id, status, user_agent, ip_address, login_date)
				VALUES(?, ?, 0, ?, ?, ?)');
			$stmt->execute([$this->_email, $this->_id,
				substr($_SERVER['HTTP_USER_AGENT'], 0, 255), $_SERVER['REMOTE_ADDR'],
				date('c')]);
			throw new ReportableException('The password you entered was not correct.  Please try again.');
		}
	}

	private $_id, $_forename, $_surname, $_email, $_club, $_clubID, $_clubName, $_status, $_permissions;
}

class PasswordResetKey {
	public function __construct($key) {
		global $Database;

		$stmt = $Database->prepare('
			SELECT prk.reset_key_id, u.user_id, prk.expiry_date, prk.status AS key_status,
				u.forename, u.surname, u.email, c.name AS club, u.status AS user_status
			FROM password_reset_key prk
				JOIN user u ON prk.user_id = u.user_id
				JOIN club c ON u.club_id = c.club_id
			WHERE reset_key = ?');
		$stmt->execute([$key]);

		if ($row = $stmt->fetch()) {
			$this->_key = $key;
			$this->_id = $row['reset_key_id'];
			$this->_status = $row['key_status'];
			$this->_expiryDate = strtotime($row['expiry_date']);

			// check whether the reset key has expired since we last checked
			if ($this->_expiryDate <= time() && $this->_status == PasswordResetKeyStatus::Unused) {
				$stmt = $Database->prepare('
					UPDATE password_reset_key SET status = 2
					WHERE reset_key_id = ?');
				$stmt->execute([$this->_id]);
				$this->_status = PasswordResetKeyStatus::Expired;
			}

			$this->_user = new User($row['user_id']);

			// check whether the reset key is in a suitable status
			// CHEAT: error messages relate to the UI context, so not proper MVC separation
			switch ($this->_status) {
				case PasswordResetKeyStatus::Used:
					throw new ReportableException('This password reset key has already been used.  '
						.'If you have forgotten your password again, please use this form to generate a new key.',
						0, null, $this->_user);

				case PasswordResetKeyStatus::Expired:
					throw new ReportableException('This password reset key has expired.  Please use this form to generate a new key.',
						0, null, $this->_user);

				case PasswordResetKeyStatus::Superseded:
					throw new ReportableException('This password reset key has been superseded.  '
						.'Please use the most recent key that was sent to you, or use this form to generate a new key.',
						0, null, $this->_user);
			}

		} else {
			throw new Exception('Password reset key not found');
		}
	}

	public function id() { return $this->_id; }
	public function user() { return $this->_user; }
	public function key() { return $this->_key; }

	public function setNewPassword($password) {
		global $Database;

		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

		$Database->beginTransaction();
		$stmt = $Database->prepare('UPDATE user SET hashed_password = ?, status = 1 WHERE user_id = ?');
		$stmt->execute([$hashedPassword, $this->_user->id()]);

		$stmt = $Database->prepare('UPDATE password_reset_key SET status = 2 WHERE reset_key_id = ?');
		$stmt->execute([$this->_id]);

		$Database->commit();
	}

	private $_id, $_status, $_expiryDate, $_user;
}
?>