<?php
function showContact($contact, $showContactInfo) {
	$result = '<td>' . htmlspecialchars($contact->name()) . '</td>';
	if ($showContactInfo) {
		$phones = $emails = '';
		foreach ($contact->phoneNumbers() as $phone) {
			if ($phones) $phones .= '<br/>';
			$phones .= htmlspecialchars($phone[0]);
			if ($phone[1]) $phones .= ' (' . htmlspecialchars($phone[1]) . ')';
		}
		foreach ($contact->emails() as $email) {
			if ($emails) $emails .= '<br/>';
			$htmlEmail = htmlspecialchars($email[0]);
			$emails .= "<a href='mailto:$htmlEmail'>$htmlEmail</a>";
			if ($email[1]) $emails .= ' (' . htmlspecialchars($email[1]) . ')';
		}
		$result .= "<td>$phones</td><td>$emails</td>";
	}
	return $result;
}
?>