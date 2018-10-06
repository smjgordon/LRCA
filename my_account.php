<?php
require_once 'private_php/p_global.php';
requireLogin();

pageHeader('My Account - Leicestershire and Rutland Chess Association');
?>

<h2>My Account</h2>

<p><a href="change_password">Change password</a></p>

<?php if ($CurrentUser->hasPermission('can_create_users')) { ?>
<p><a href="create_user">Create a new user</a></p>
<?php } ?>

<?php
pageFooter();
?>