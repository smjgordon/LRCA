<?php
require_once 'm_club.php';
require_once 'p_email.php';

function createUser($forename, $surname, $email, $clubId) {
	global $Database;
	global $CurrentUser;

	$club = Club::loadById($clubId);

	// check that the logged-in user is allowed to create this new user
	if (!($CurrentUser->hasPermission('administrator')
			|| ($CurrentUser->hasPermission('can_create_users') && $CurrentUser->club()->id() == $clubId))) {
		throw new ReportableException('You do not have permission to create a user for this club.');
	}

	$Database->beginTransaction();
	try {		
		// check that a user with this email doesn't already exist
		$stmt = $Database->prepare('SELECT * FROM user WHERE Lower(email) = ?');
		$stmt->execute([strtolower($email)]);
		if ($row = $stmt->fetch()) {
			throw new ReportableException("Sorry, a user already exists with email address $email.");
		}
		
		// now actually create the user!
		$stmt = $Database->prepare('
			INSERT INTO user(forename, surname, email, club_id, created_user_id,
				status, administrator, can_submit, can_create_users)
			VALUES(?, ?, ?, ?, ?, 2, 0, 1, 1)');
		$stmt->execute([$forename, $surname, $email, $clubId, $CurrentUser->id()]);
		$newUserId = $Database->lastInsertId();
		$newUser = User::loadById($newUserId);
		$resetKeyStr = $newUser->generatePasswordResetKey(2);

		$Database->commit();

	} catch (Exception $ex) {
		$Database->rollBack();
		throw $ex;
	}

	// generate a welcome email and send it
	$url = joinUri(getCurrentUri(), "rpwd.php?key=$resetKeyStr");
	$clubName = $club->name();

	$emailSubject = 'Welcome to the LRCA Results Website';
	$emailMessage = 'Dear ' . $newUser->forename() . ",

A user account has been created for you on the LRCA Results Website, so
that you can submit results for $clubName Chess Club.
This website can be found here:

http://lrca.stewartsplace.org.uk/

If you have difficulty using this website or you find any bugs, large or
small, please do not hesitate to contact the Results Webmaster, Stewart
Gordon, at smjg@iname.com.

You will need to set a password for your user account.  To set a password,
please visit the following page:

$url

This link will expire 48 hours after this email was sent.  If you are not
able to use it within this period, you can still visit the page, but you
will be prompted to generate a new password reset key which will be
emailed to you.";

	emailConfirmation($emailSubject, $emailMessage, [$newUser], 'user_created.php');
}
?>