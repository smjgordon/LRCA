<?php
require_once 'private_php/p_global.php';
requireLogin();

pageHeader('My Account - Leicestershire and Rutland Chess Association');
?>

<h2>My Account</h2>

<p><a href="change_password.php">Change password</a></p>

<p><a href="create_user.php">Create a new user</a></p>

<?php
pageFooter();
?>