<?php
require_once 'private_php/p_global.php';

$userStatus = (integer) @$_GET['status'];
$pageTitle = $userStatus == UserStatus::Active ? 'Password Recovery' : 'Password Creation';
pageHeader($pageTitle);
?>

<h1><?php echo htmlspecialchars($pageTitle); ?></h1>

<p>An email has been sent to your email address.</p>

<p>Please use the link in it to set a new password for your user account.</p>

<?php
pageFooter();
?>